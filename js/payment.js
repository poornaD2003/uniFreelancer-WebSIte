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
document.getElementById('paymentForm').addEventListener('submit', function (e) {

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