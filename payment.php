<?php
include 'includes/db.php';
include 'includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the user is logged in as a client
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Testing purposes
}
$client_id = $_SESSION['user_id'];

// Get Order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$error_msg = "";
$success_msg = "";

// 1. Backend: Fetch Order and Gig Details FIRST (To get the student_id safely)
$order_details = null;
if ($order_id > 0) {
    $fetch_query = $conn->prepare("
        SELECT o.orderId, o.status, o.student_id, g.title, g.price, u.fullname AS student_name 
        FROM orders o 
        JOIN gigs g ON o.gig_id = g.id 
        JOIN users u ON o.student_id = u.id 
        WHERE o.orderId = ? AND o.client_id = ? LIMIT 1
    ");
    if ($fetch_query) {
        $fetch_query->bind_param("ii", $order_id, $client_id);
        $fetch_query->execute();
        $result = $fetch_query->get_result();
        if ($result && $result->num_rows > 0) {
            $order_details = $result->fetch_assoc();
            
            // Prevent paying if it's already paid
            if ($order_details['status'] === 'paid') {
                $error_msg = "This order has already been paid for.";
                $order_details = null;
            }
        } else {
            $error_msg = "Invalid Order ID or unauthorized access.";
        }
        $fetch_query->close();
    }
} else {
    $error_msg = "No Order ID specified.";
}

// 2. Backend: Process Payment Form Submission & Save to Database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment']) && $order_details) {
    $pay_order_id = intval($_POST['order_id']);
    $amount = (float)$_POST['amount'];
    $payment_method = $conn->real_escape_string($_POST['payment_method']); 
    
    // FIXED: Fetching student_id directly from the order details we just verified
    $student_id = intval($order_details['student_id']);
    
    $payment_status = 'completed'; 
    $current_date = date('Y-m-d H:i:s');

    // Start a database transaction to ensure consistency
    $conn->begin_transaction();

    try {
        // A. Insert into payments table including the required student_id
        // (Make sure the column names below match your exact DB: paymentId is auto-incremented)
        $pay_stmt = $conn->prepare("INSERT INTO payment (orderId, client_id, student_id, amount, payment_status, payment_date) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$pay_stmt) {
            throw new Exception("Payments SQL prepare failed: " . $conn->error);
        }
        
        // bind_param structure: i=integer, d=double/float, s=string
        $pay_stmt->bind_param("iiidss", $pay_order_id, $client_id, $student_id, $amount, $payment_status, $current_date);
        $pay_stmt->execute();
        $payment_id = $conn->insert_id;
        $pay_stmt->close();

        // A2. Check if student has a club affiliation and route the contribution
        $club_stmt = $conn->prepare("
            SELECT c.id, c.contribution_rate 
            FROM student_profiles sp 
            JOIN clubs c ON sp.club_id = c.id 
            WHERE sp.user_id = ? AND c.status = 'approved' 
            LIMIT 1
        ");
        if ($club_stmt) {
            $club_stmt->bind_param("i", $student_id);
            $club_stmt->execute();
            $club_res = $club_stmt->get_result()->fetch_assoc();
            $club_stmt->close();
            
            if ($club_res) {
                $club_id = (int)$club_res['id'];
                $rate = (float)$club_res['contribution_rate'];
                $club_amount = ($amount * $rate) / 100.0;
                
                $ledger_desc = "Contribution share of " . number_format($rate, 2) . "% from order #" . $pay_order_id;
                
                $ledger_stmt = $conn->prepare("INSERT INTO club_ledger (club_id, payment_id, amount, description) VALUES (?, ?, ?, ?)");
                if ($ledger_stmt) {
                    $ledger_stmt->bind_param("iids", $club_id, $payment_id, $club_amount, $ledger_desc);
                    $ledger_stmt->execute();
                    $ledger_stmt->close();
                }
            }
        }

        // B. Update order status in orders table (If ENUM doesn't have 'paid', change 'paid' to 'completed')
        $order_stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE orderId = ? AND client_id = ?");
        if (!$order_stmt) {
            throw new Exception("Orders SQL prepare failed: " . $conn->error);
        }
        $order_stmt->bind_param("ii", $pay_order_id, $client_id);
        $order_stmt->execute();
        $order_stmt->close();

        // Commit transaction if both succeeded
        $conn->commit();
        $success_msg = "Payment of Rs. " . number_format($amount, 2) . " successful! The transaction has been recorded.";
        
        // Clear order details so the form doesn't show again on success
        $order_details = null; 

    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Payment failed to process: " . $e->getMessage();
    }
}

// Calculate Fees for UI display
$gig_price = $order_details ? $order_details['price'] : 0;
$platform_fee = $gig_price * 0.05;
$total_amount = $gig_price + $platform_fee;
?>
<link rel="stylesheet" href="css/payment.css">

<div class="payment-container">
    <?php if (!empty($error_msg)): ?>
        <div style="background: #fee2e2; border: 1px solid #f87171; color: #b91c1c; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; text-align: center; font-weight: 600;">
            <?php echo $error_msg; ?>
            <br><a href="client-dashboard.php" style="color: #b91c1c; text-decoration: underline; font-size: 0.9rem; margin-top: 10px; display: inline-block;">Return to Dashboard</a>
        </div>
    <?php elseif (!empty($success_msg)): ?>
        <div style="background: #d1fae5; border: 1px solid #34d399; color: #047857; padding: 3rem; border-radius: 24px; text-align: center; box-shadow: 0 10px 30px rgba(16,185,129,0.1);">
            <div style="width: 80px; height: 80px; background: #10b981; color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem;">✓</div>
            <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 1rem;">Payment Successful!</h2>
            <p style="font-size: 1.1rem; color: #065f46; margin-bottom: 2rem;"><?php echo $success_msg; ?></p>
            <a href="client-dashboard.php" style="background: #10b981; color: white; padding: 12px 30px; border-radius: 10px; text-decoration: none; font-weight: 600;">Go to Dashboard</a>
        </div>
    <?php elseif ($order_details): ?>
        <div style="margin-bottom: 2.5rem; text-align: center;">
            <h1 style="font-size: 2.5rem; font-weight: 800; color: #0f172a;">Secure Checkout</h1>
            <p style="color: #64748b;">Complete your payment to finalize your project order.</p>
        </div>

        <div class="payment-grid">
            <div class="summary-card">
                <div class="summary-title">Order Summary</div>
                <div class="summary-item">
                    <span style="font-weight: 600; color: #0f172a; font-size: 1.1rem;"><?php echo htmlspecialchars($order_details['title']); ?></span>
                </div>
                <div class="summary-item" style="margin-bottom: 2rem;">
                    <span>Developer</span>
                    <span style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($order_details['student_name']); ?></span>
                </div>
                <div class="summary-item">
                    <span>Subtotal</span>
                    <span>Rs. <?php echo number_format($gig_price, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>Processing Fee (5%)</span>
                    <span>Rs. <?php echo number_format($platform_fee, 2); ?></span>
                </div>
                <div class="summary-item total">
                    <span>Total Amount</span>
                    <span style="color: #10b981;">Rs. <?php echo number_format($total_amount, 2); ?></span>
                </div>
            </div>

            <div class="payment-card">
                <div class="payment-tabs">
                    <div class="pay-tab active" onclick="switchMethod('card')">Card Payment</div>
                    <div class="pay-tab" onclick="switchMethod('bank')">Bank Deposit</div>
                </div>

                <form method="POST" action="payment.php?order_id=<?php echo $order_id; ?>" id="paymentForm">
                    <input type="hidden" name="process_payment" value="1">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <input type="hidden" name="amount" value="<?php echo $total_amount; ?>">
                    <input type="hidden" name="payment_method" id="payment_method_input" value="card">

                    <div id="card_section" class="form-section active">
                        <div class="input-group"><label>Cardholder Name</label><input type="text" id="cc_name" placeholder="John Doe" required></div>
                        <div class="input-group"><label>Card Number</label><input type="text" id="cc_num" placeholder="1234 5678 9101 1121" maxlength="19" required></div>
                        <div style="display:flex; gap:1rem;">
                            <div class="input-group" style="flex:1;"><label>Expiry</label><input type="text" id="cc_exp" placeholder="MM/YY" maxlength="5" required></div>
                            <div class="input-group" style="flex:1;"><label>CVV</label><input type="password" id="cc_cvc" placeholder="***" maxlength="3" required></div>
                        </div>
                    </div>

                    <div id="bank_section" class="form-section">
                        <div style="background: rgba(16, 185, 129, 0.05); border: 1px dashed #10b981; padding: 1.2rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.9rem; color: #0f172a; line-height: 1.6;">
                            <strong>Bank Name:</strong> Commercial Bank<br>
                            <strong>Account Name:</strong> UniLance Holdings<br>
                            <strong>Account Number:</strong> 100054783210<br>
                            <strong>Branch:</strong> Colombo Fort
                        </div>
                        <div class="input-group"><label>Your Account Name</label><input type="text" id="bank_name" placeholder="e.g. A.B. Perera"></div>
                        <div class="input-group"><label>Transaction Reference / Slip ID</label><input type="text" id="bank_acc" placeholder="e.g. TXN9982415"></div>
                    </div>

                    <button type="submit" class="btn-pay" id="payBtn">
                        <span id="btnText">Pay Rs. <?php echo number_format($total_amount, 2); ?></span>
                        <div class="loader" id="btnLoader"></div>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function switchMethod(method) {

    document.querySelectorAll('.pay-tab').forEach(tab => {
        tab.classList.remove('active');
    });

    document.querySelectorAll('.form-section').forEach(section => {
        section.classList.remove('active');
    });

    const isCard = (method === 'card');

    // Required fields
    document.getElementById('cc_name').required = isCard;
    document.getElementById('cc_num').required = isCard;
    document.getElementById('cc_exp').required = isCard;
    document.getElementById('cc_cvc').required = isCard;

    document.getElementById('bank_name').required = !isCard;
    document.getElementById('bank_acc').required = !isCard;

    if (isCard) {
        document.querySelectorAll('.pay-tab')[0].classList.add('active');
        document.getElementById('card_section').classList.add('active');
        document.getElementById('payment_method_input').value = 'card';
        document.getElementById('btnText').innerText =
            "Pay Rs. <?php echo number_format($total_amount, 2); ?>";
    } else {
        document.querySelectorAll('.pay-tab')[1].classList.add('active');
        document.getElementById('bank_section').classList.add('active');
        document.getElementById('payment_method_input').value = 'bank';
        document.getElementById('btnText').innerText =
            "Submit Bank Deposit";
    }
}

/* =========================
   CARD NUMBER FORMAT
   1234567812345678
   =>
   1234 5678 1234 5678
========================= */
document.getElementById('cc_num').addEventListener('input', function () {

    let value = this.value.replace(/\D/g, '');

    if (value.length > 16) {
        value = value.substring(0, 16);
    }

    value = value.replace(/(\d{4})(?=\d)/g, '$1 ');

    this.value = value;
});


/* =========================
   EXPIRY FORMAT
   1226 => 12/26
========================= */
document.getElementById('cc_exp').addEventListener('input', function () {

    let value = this.value.replace(/\D/g, '');

    if (value.length > 4) {
        value = value.substring(0, 4);
    }

    if (value.length >= 3) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }

    this.value = value;
});


/* =========================
   CVV NUMBERS ONLY
========================= */
document.getElementById('cc_cvc').addEventListener('input', function () {

    this.value = this.value.replace(/\D/g, '');

    if (this.value.length > 3) {
        this.value = this.value.substring(0, 3);
    }
});


/* =========================
   FORM VALIDATION
========================= */
document.getElementById('paymentForm').addEventListener('submit', function(e) {

    const paymentMethod =
        document.getElementById('payment_method_input').value;

    if (paymentMethod === 'card') {

        let cardNum =
            document.getElementById('cc_num').value.replace(/\s/g, '');

        let expiry =
            document.getElementById('cc_exp').value;

        let cvv =
            document.getElementById('cc_cvc').value;

        if (cardNum.length !== 16) {
            e.preventDefault();
            alert('Card number must contain 16 digits');
            return false;
        }

        if (!/^\d{2}\/\d{2}$/.test(expiry)) {
            e.preventDefault();
            alert('Expiry date must be in MM/YY format');
            return false;
        }

        let month = parseInt(expiry.substring(0, 2));

        if (month < 1 || month > 12) {
            e.preventDefault();
            alert('Invalid expiry month');
            return false;
        }

        if (cvv.length !== 3) {
            e.preventDefault();
            alert('CVV must contain 3 digits');
            return false;
        }
    }

    document.getElementById('payBtn').style.pointerEvents = 'none';
    document.getElementById('payBtn').style.opacity = '0.7';

    document.getElementById('btnText').innerText =
        'Processing secure transfer...';

    document.getElementById('btnLoader').style.display = 'block';
});


/* Default */
switchMethod('card');
</script>

<?php include 'includes/footer.php'; ?>
