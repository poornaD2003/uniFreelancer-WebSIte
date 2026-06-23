<?php
include 'includes/db.php';
include 'includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}
$client_id = $_SESSION['user_id'];

$user_query = $conn->query("SELECT fullname FROM users WHERE id = '$client_id'");
if ($user_query && $user_query->num_rows > 0) {
    $user_data = $user_query->fetch_assoc();
    $customer_name = $user_data['fullname'];
} else {
    $customer_name = "Guest User";
}

$words = explode(" ", $customer_name);
$initials = "";
foreach ($words as $w) {
    if (!empty($w)) $initials .= strtoupper($w[0]);
}
$initials = substr($initials, 0, 2); 

$total_orders_res = $conn->query("SELECT COUNT(*) as total FROM orders WHERE client_id = '$client_id'");
$total_orders = $total_orders_res ? $total_orders_res->fetch_assoc()['total'] : 0;

$pending_orders_res = $conn->query("SELECT COUNT(*) as total FROM orders WHERE client_id = '$client_id' AND status = 'pending'");
$pending_orders = $pending_orders_res ? $pending_orders_res->fetch_assoc()['total'] : 0;

$completed_orders_res = $conn->query("SELECT COUNT(*) as total FROM orders WHERE client_id = '$client_id' AND status = 'completed'");
$completed_orders = $completed_orders_res ? $completed_orders_res->fetch_assoc()['total'] : 0;

$open_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/client_dashboard.css">


<div class="dashboard-wrapper">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <i class="ti ti-activity" style="font-size: 1.5rem;"></i> Client Analytics
    </div>
    <ul class="sidebar-menu">
      <li class="sidebar-item active">
        <a href="client-dashboard.php"><i class="ti ti-smart-home"></i> Pipeline Hub</a>
      </li>
      <li class="sidebar-item">
       <a href="jobs.php"><i class="ti ti-square-plus"></i> Launch Order</a>
      </li>
    </ul>
  </aside>

  <div class="main-content">
    <div class="header-section">
      <div>
        <h1 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 6px; color: var(--text);">Welcome Back!</h1>
        <p style="color: var(--muted); font-size: 0.9rem; font-weight: 500;">Monitor your real-time processing operations and contract status.</p>
      </div>
      <div class="user-profile-preview">
        <div class="avatar-circle"><?php echo $initials; ?></div>
        <div>
          <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text);"><?php echo htmlspecialchars($customer_name); ?></h4>
          <p style="font-size: 0.75rem; color: var(--muted); font-weight: 600;">Client Node Account</p>
        </div>
      </div>
    </div>

    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-icon blue"><i class="ti ti-folders"></i></div>
        <div>
          <p class="metric-label">Total Transactions</p>
          <p class="metric-value"><?php echo $total_orders; ?></p>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon amber"><i class="ti ti-hourglass-low"></i></div>
        <div>
          <p class="metric-label">Awaiting Dispatch</p>
          <p class="metric-value"><?php echo $pending_orders; ?></p>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon emerald"><i class="ti ti-circle-check"></i></div>
        <div>
          <p class="metric-label">Settled Contracts</p>
          <p class="metric-value"><?php echo $completed_orders; ?></p>
        </div>
      </div>
    </div>

    <div class="section-card">
      <div class="section-title">
        <i class="ti ti-git-pull-request" style="color: var(--green); font-size: 1.3rem;"></i> Active Operational Queues
      </div>
      
      <div class="pipeline-list">
        <?php
        $orders_query = $conn->query("
            SELECT o.orderId, o.status, g.title, g.price, u.fullname AS freelancer_name 
            FROM orders o 
            JOIN gigs g ON o.gig_id = g.id 
            LEFT JOIN users u ON o.student_id = u.id
            WHERE o.client_id = '$client_id' 
            ORDER BY o.orderId DESC
        ");

        if ($orders_query && $orders_query->num_rows > 0) {
            while ($row = $orders_query->fetch_assoc()) {
                $orderId = $row['orderId'];
                
                if ($row['status'] == 'pending') {
    $badge = 'badge-pending'; $progress_width = '25%'; $progress_color = '#f59e0b';
} elseif ($row['status'] == 'in_progress') {
    $badge = 'badge-progress'; $progress_width = '65%'; $progress_color = 'var(--blue)';
} elseif ($row['status'] == 'completed') {
    $badge = 'badge-completed'; $progress_width = '100%'; $progress_color = 'var(--green)';
} else {
    $badge = 'badge-cancelled'; $progress_width = '0%'; $progress_color = '#ef4444';
}
                
                $freelancer_display = !empty($row['freelancer_name']) ? htmlspecialchars($row['freelancer_name']) : 'Not Assigned Yet';
                ?>
                <div class="pipeline-item" id="order-row-<?php echo $orderId; ?>">
                    <div style="display:flex;justify-content:space-between;align-items:center; width:100%; flex-wrap:wrap; gap:15px;">
                        <div style="flex:1; min-width:250px;">
                            <h4 style="font-size:1rem;font-weight:700;margin-bottom:6px;color: var(--text);"><?php echo htmlspecialchars($row['title']); ?></h4>
                            <p style='font-size:0.85rem;color:var(--muted); font-weight: 500;'>
                                <strong>Order ID:</strong> #<?php echo $orderId; ?> &nbsp;|&nbsp; 
                                <strong>Assigned Freelancer:</strong> <?php echo $freelancer_display; ?>
                            </p>
                        </div>
                        <div style='display:flex;align-items:center;gap:14px;'>
                            <span style='font-size:1.05rem;font-weight:700;color:var(--text);'>Rs. <?php echo number_format($row['price'], 2); ?></span>
                            <span class='badge <?php echo $badge; ?>'><?php echo ucfirst(str_replace('_',' ',$row['status'])); ?></span>
                            <?php if ($row['status'] === 'completed'): ?>
        <a href="payment.php?order_id=<?php echo $orderId; ?>" class="chat-btn-send" style="height: 38px; padding: 0 16px; font-size: 0.8rem; text-decoration: none; display: flex; align-items: center; gap: 6px; box-shadow: none;">
            <i class="ti ti-credit-card"></i> Pay Now
        </a>
    <?php endif; ?>
                            <button id="chat-btn-<?php echo $orderId; ?>" onclick="toggleChat(<?php echo $orderId; ?>)" class="filter-btn" style="display:flex; align-items:center; gap:6px;">
                                <i class="ti ti-message"></i> Discussion Panel
                            </button>
                        </div>  
                    </div>
                    
                    <div style='margin-top:14px; margin-bottom: 5px;'>
                        <div style='background:rgba(0,0,0,0.06);border-radius:20px;height:6px;overflow:hidden;'>
                            <div style='width:<?php echo $progress_width; ?>;height:100%;background:<?php echo $progress_color; ?>;border-radius:20px;'></div>
                        </div>
                    </div>

                    <div id="chat-box-<?php echo $orderId; ?>" class="chat-container" style="display:none; text-align: left;">
                        <div class="chat-header">
                            <i class="ti ti-brand-hipchat" style="color: var(--green); font-size:16px;"></i>
                            <span>Chat Workspace Matrix with Developer: <strong><?php echo $freelancer_display; ?></strong></span>
                        </div>
                        
                        <div class="chat-history">
                            <?php
                             $msg_stmt = $conn->prepare("
                                 SELECT om.id, om.sender_id, om.message, om.file_path, om.sent_at, u.fullname 
                                 FROM order_messages om 
                                 JOIN users u ON om.sender_id = u.id 
                                 WHERE om.order_id = ? AND om.deleted_by_client = 0
                                 ORDER BY om.sent_at ASC
                             ");
                             if($msg_stmt) {
                                 $msg_stmt->bind_param("i", $orderId);
                                 $msg_stmt->execute();
                                 $chat_history = $msg_stmt->get_result();

                                 if ($chat_history->num_rows > 0) {
                                     while ($msg = $chat_history->fetch_assoc()) {
                                         $is_current_user = (intval($msg['sender_id']) === intval($client_id));
                                         $bubble_class = $is_current_user ? 'chat-bubble-sent' : 'chat-bubble-received';
                                         $meta_class = $is_current_user ? 'bubble-meta-sent' : 'bubble-meta-received';
                                         $formatted_time = date('M d, g:i A', strtotime($msg['sent_at']));
                                         
                                         if ($is_current_user) {
                                             $menu_options = '
                                                 <button onclick="editMessageInline(' . $msg['id'] . ')"><i class="ti ti-edit"></i> Edit</button>
                                                 <button class="delete-btn" onclick="deleteMessage(' . $msg['id'] . ')"><i class="ti ti-trash"></i> Delete</button>
                                             ';
                                         } else {
                                             $menu_options = '
                                                 <button class="delete-btn" onclick="deleteMessage(' . $msg['id'] . ')"><i class="ti ti-trash"></i> Delete for Me</button>
                                             ';
                                         }
                                         ?>
                                         <div class="chat-bubble <?php echo $bubble_class; ?>" data-msg-id="<?php echo $msg['id']; ?>">
<div style="font-weight: 700; font-size: 11px; color: <?php echo $is_current_user ? 'var(--green)' : 'var(--muted)'; ?>; margin-bottom: 2px;">                                                 <?php echo htmlspecialchars($msg['fullname']); ?>
                                             </div>
                                             
                                             <div class="bubble-menu-container">
                                                 <button class="bubble-menu-btn" onclick="toggleBubbleMenu(event, <?php echo $msg['id']; ?>)">
                                                     <i class="ti ti-dots-vertical"></i>
                                                 </button>
                                                 <div class="bubble-menu-dropdown" id="bubble-dropdown-<?php echo $msg['id']; ?>">
                                                     <?php echo $menu_options; ?>
                                                 </div>
                                             </div>

                                             <?php if (!empty($msg['message'])): ?>
                                                 <div class="message-text" style="word-break: break-word; white-space: pre-wrap;"><?php echo htmlspecialchars($msg['message']); ?></div>
                                             <?php endif; ?>
                                             <?php if (!empty($msg['file_path'])): 
                                                 $filename = basename($msg['file_path']);
                                             ?>
                                                 <div style="margin-top: 8px; border-top: 1px solid var(--border); padding-top: 6px;">
                                                     <a href="<?php echo htmlspecialchars($msg['file_path']); ?>" download class="attachment-btn" style="color: var(--green); text-decoration: none; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
                                                         <i class="ti ti-download"></i> Download <?php echo htmlspecialchars($filename); ?>
                                                     </a>
                                                 </div>
                                             <?php endif; ?>
                                             <div class="bubble-meta <?php echo $meta_class; ?>">
                                                 <span><?php echo $formatted_time; ?></span>
                                             </div>
                                         </div>
                                         <?php
                                     }
                                 } else {
                                     echo "<div class='no-messages' style='text-align:center; padding: 2rem 0; color:var(--muted); font-size: 13px;'>
                                             <i class='ti ti-messages' style='font-size: 26px; color:var(--green); display:block; margin-bottom: 6px;'></i>
                                             No conversation history found. Drop a line below to sync up.
                                           </div>";
                                 }
                                 $msg_stmt->close();
                             }   
                            ?>
                        </div>

                        <form class="chat-input-form" id="chat-form-<?php echo $orderId; ?>" onsubmit="submitChatMessage(event, <?php echo $orderId; ?>)" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                            <button type="button" onclick="document.getElementById('attachment-<?php echo $orderId; ?>').click()" class="filter-btn" style="height: 44px; width: 44px; display: flex; align-items: center; justify-content: center; padding: 0; font-size: 16px;">
                                <i class="ti ti-paperclip"></i>
                            </button>
                            <input type="file" name="attachment" id="attachment-<?php echo $orderId; ?>" style="display: none;" onchange="handleFileSelected(<?php echo $orderId; ?>)">
                            
                            <div style="flex: 1; display: flex; flex-direction: column;">
                                <textarea name="message" id="message-text-<?php echo $orderId; ?>" class="chat-textarea" placeholder="Type transmission variables here..." onkeydown="handleTextareaKeydown(event, <?php echo $orderId; ?>)"></textarea>
                                <div id="file-name-badge-<?php echo $orderId; ?>" style="display: none; align-items: center; gap: 6px; font-size: 11px; color: var(--green); margin-top: 4px; background: var(--green-dim); padding: 4px 10px; border-radius: var(--radius); border: 1px solid var(--border); width: fit-content;">
                                    <i class="ti ti-file"></i> <span class="file-txt"></span>
                                    <button type="button" onclick="clearFileSelected(<?php echo $orderId; ?>)" style="background:none; border:none; color:#ef4444; font-weight:bold; cursor:pointer; font-size: 13px; line-height: 1; margin-left: 4px;">&times;</button>
                                </div>
                            </div>
                            
                            <button type="submit" class="chat-btn-send">
                                <i class="ti ti-send"></i> Transmit
                            </button>
                        </form>
                    </div>

                </div>
                <?php
            }
        } else {
            echo "<p style='color:var(--muted);text-align:center;padding:20px;font-weight:500;'>No registered transactional pipeline records tracked.</p>";
        }
        ?>
      </div>
    </div>
  </div>
</div>

<script>
    function toggleChat(orderId) {
        var chatBox = document.getElementById('chat-box-' + orderId);
        if (chatBox) {
            var btn = document.getElementById('chat-btn-' + orderId);
            if (chatBox.style.display === 'none' || chatBox.style.display === '') {
                chatBox.style.display = 'flex';
                if (btn) {
                    btn.innerHTML = '<i class="ti ti-message-off"></i> Close Panel';
                    btn.style.background = 'rgba(239, 68, 68, 0.08)'; btn.style.color = '#ef4444'; btn.style.borderColor = 'rgba(239, 68, 68, 0.2)';
                }
                var historyDiv = chatBox.querySelector('.chat-history');
                if (historyDiv) { historyDiv.scrollTop = historyDiv.scrollHeight; }
            } else {
                chatBox.style.display = 'none';
                if (btn) {
                    btn.innerHTML = '<i class="ti ti-message"></i> Discussion Panel';
                    btn.style.background = ''; btn.style.color = ''; btn.style.borderColor = '';
                }
            }
        }
    }

    const currentUserId = <?php echo json_encode($client_id); ?>;

    function escapeHtml(text) {
        if (!text) return '';
        return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function renderMessageBubble(msg, currentUserId) {
        let isCurrentUser = (parseInt(msg.sender_id) === parseInt(currentUserId));
        let bubbleClass = isCurrentUser ? 'chat-bubble-sent' : 'chat-bubble-received';
        let metaClass = isCurrentUser ? 'bubble-meta-sent' : 'bubble-meta-received';
        let labelColor = isCurrentUser ? 'var(--green)' : 'var(--muted)';
        
        let messageTextHtml = msg.message ? `<div class="message-text" style="word-break: break-word; white-space: pre-wrap;">${escapeHtml(msg.message)}</div>` : '';
        let fileAttachmentHtml = '';
        
        if (msg.file_path) {
            let filename = msg.file_path.split('/').pop();
            fileAttachmentHtml = `
                <div style="margin-top: 8px; border-top: 1px solid var(--border); padding-top: 6px;">
                    <a href="${escapeHtml(msg.file_path)}" download class="attachment-btn" style="color: var(--green); text-decoration: none; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
                        <i class="ti ti-download"></i> Download ${escapeHtml(filename)}
                    </a>
                </div>
            `;
        }
        
        let menuOptionsHtml = isCurrentUser ? `
            <button onclick="editMessageInline(${msg.id})"><i class="ti ti-edit"></i> Edit</button>
            <button class="delete-btn" onclick="deleteMessage(${msg.id})"><i class="ti ti-trash"></i> Delete</button>
        ` : `
            <button class="delete-btn" onclick="deleteMessage(${msg.id})"><i class="ti ti-trash"></i> Delete for Me</button>
        `;
        
        let threeDotMenuHtml = `
            <div class="bubble-menu-container">
                <button class="bubble-menu-btn" onclick="toggleBubbleMenu(event, ${msg.id})">
                    <i class="ti ti-dots-vertical"></i>
                </button>
                <div class="bubble-menu-dropdown" id="bubble-dropdown-${msg.id}">
                    ${menuOptionsHtml}
                </div>
            </div>
        `;
        
        return `
            <div class="chat-bubble ${bubbleClass}" data-msg-id="${msg.id}">
                <div style="font-weight: 700; font-size: 11px; color: ${labelColor}; margin-bottom: 2px;">
                    ${escapeHtml(msg.fullname)}
                </div>
                ${threeDotMenuHtml}
                ${messageTextHtml}
                ${fileAttachmentHtml}
                <div class="bubble-meta ${metaClass}">
                    <span>${escapeHtml(msg.sent_at)}</span>
                </div>
            </div>
        `;
    }

    function toggleBubbleMenu(event, msgId) {
        event.stopPropagation();
        document.querySelectorAll('.bubble-menu-dropdown').forEach(dropdown => {
            if (dropdown.id !== 'bubble-dropdown-' + msgId) dropdown.style.display = 'none';
        });
        const dropdown = document.getElementById('bubble-dropdown-' + msgId);
        if (dropdown) dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
    }
    
    document.addEventListener('click', function() {
        document.querySelectorAll('.bubble-menu-dropdown').forEach(dropdown => dropdown.style.display = 'none');
    });

    function editMessageInline(msgId) {
        const bubble = document.querySelector(`[data-msg-id="${msgId}"]`);
        if (!bubble || bubble.querySelector('.edit-input-container')) return;
        
        const textContainer = bubble.querySelector('.message-text');
        const originalText = textContainer ? textContainer.textContent.trim() : '';
        
        if (textContainer) textContainer.style.display = 'none';
        const attachment = bubble.querySelector('.attachment-btn');
        if (attachment) attachment.parentElement.style.display = 'none';
        
        const editContainer = document.createElement('div');
        editContainer.className = 'edit-input-container';
        editContainer.innerHTML = `
            <textarea class="edit-textarea">${escapeHtml(originalText)}</textarea>
            <div class="edit-actions">
                <button class="edit-btn-save" onclick="saveInlineEdit(${msgId})">Save</button>
                <button class="edit-btn-cancel" onclick="cancelInlineEdit(${msgId})">Cancel</button>
            </div>
        `;
        bubble.insertBefore(editContainer, bubble.querySelector('.bubble-meta'));
        editContainer.querySelector('.edit-textarea').focus();
    }

    function cancelInlineEdit(msgId) {
        const bubble = document.querySelector(`[data-msg-id="${msgId}"]`);
        if (!bubble) return;
        const editContainer = bubble.querySelector('.edit-input-container');
        if (editContainer) editContainer.remove();
        const textContainer = bubble.querySelector('.message-text');
        if (textContainer) textContainer.style.display = 'block';
        const attachment = bubble.querySelector('.attachment-btn');
        if (attachment) attachment.parentElement.style.display = 'block';
    }

    function saveInlineEdit(msgId) {
        const bubble = document.querySelector(`[data-msg-id="${msgId}"]`);
        if (!bubble) return;
        
        const editContainer = bubble.querySelector('.edit-input-container');
        const textarea = editContainer.querySelector('.edit-textarea');
        const saveBtn = editContainer.querySelector('.edit-btn-save');
        const newText = textarea.value.trim();
        
        if (newText === '') { alert('Message content payload cannot be blank.'); return; }
        
        saveBtn.disabled = true; saveBtn.textContent = 'Saving...';
        const formData = new FormData();
        formData.append('message_id', msgId); formData.append('message', newText);
        
        fetch('edit_message_ajax.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                const textContainer = bubble.querySelector('.message-text');
                if (textContainer) { textContainer.textContent = result.data.message; textContainer.style.display = 'block'; }
                const attachment = bubble.querySelector('.attachment-btn');
                if (attachment) attachment.parentElement.style.display = 'block';
                
                if (!bubble.querySelector('.edited-indicator')) {
                    const meta = bubble.querySelector('.bubble-meta');
                    const editedSpan = document.createElement('span');
                    editedSpan.className = 'edited-indicator'; editedSpan.style.fontSize = '9px'; editedSpan.style.opacity = '0.7'; editedSpan.textContent = ' (edited)';
                    meta.appendChild(editedSpan);
                }
                editContainer.remove();
            } else {
                alert('Error: ' + result.message); saveBtn.disabled = false; saveBtn.textContent = 'Save';
            }
        }).catch(err => { alert('Failed to edit message structure.'); saveBtn.disabled = false; saveBtn.textContent = 'Save'; });
    }

    function deleteMessage(msgId) {
        if (!confirm('Are you sure you want to delete this message?')) return;
        const formData = new FormData();
        formData.append('message_id', msgId);
        
        fetch('delete_message_ajax.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                const bubble = document.querySelector(`[data-msg-id="${msgId}"]`);
                if (bubble) {
                    bubble.style.transition = 'opacity 0.3s'; bubble.style.opacity = '0';
                    setTimeout(() => bubble.remove(), 300);
                }
            } else { alert('Error: ' + result.message); }
        }).catch(err => alert('Failed to delete trace packet.'));
    }

    function handleFileSelected(orderId) {
        const fileInput = document.getElementById('attachment-' + orderId);
        const badge = document.getElementById('file-name-badge-' + orderId);
        if (fileInput.files.length > 0) {
            badge.querySelector('.file-txt').textContent = fileInput.files[0].name;
            badge.style.display = 'inline-flex';
        } else { badge.style.display = 'none'; }
    }

    function clearFileSelected(orderId) {
        const fileInput = document.getElementById('attachment-' + orderId);
        const badge = document.getElementById('file-name-badge-' + orderId);
        fileInput.value = ''; badge.style.display = 'none'; badge.querySelector('.file-txt').textContent = '';
    }

    function handleTextareaKeydown(event, orderId) {
        if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); submitChatMessage(event, orderId); }
    }

    function submitChatMessage(event, orderId) {
        if (event) event.preventDefault();
        const form = document.getElementById('chat-form-' + orderId);
        const textarea = document.getElementById('message-text-' + orderId);
        const fileInput = document.getElementById('attachment-' + orderId);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (!textarea.value.trim() && fileInput.files.length === 0) return;
        
        const formData = new FormData(form);
        textarea.disabled = true; fileInput.disabled = true; submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ti ti-loader"></i>';
        
        fetch('send_message_ajax.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                textarea.value = ''; clearFileSelected(orderId);
                const chatHistory = document.querySelector('#chat-box-' + orderId + ' .chat-history');
                if (chatHistory) {
                    const noMsg = chatHistory.querySelector('.no-messages'); if (noMsg) noMsg.remove();
                    chatHistory.innerHTML += renderMessageBubble(result.data, currentUserId);
                    chatHistory.scrollTop = chatHistory.scrollHeight;
                }
            } else { alert('Error: ' + result.message); }
        }).catch(err => alert('Terminal sending failure.'))
        .finally(() => {
            textarea.disabled = false; fileInput.disabled = false; submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ti ti-send"></i> Transmit'; textarea.focus();
        });
    }

    function pollNewMessages(orderId) {
        const chatBox = document.getElementById('chat-box-' + orderId);
        if (!chatBox || chatBox.style.display !== 'flex' || chatBox.dataset.polling === 'true') return;
        
        chatBox.dataset.polling = 'true';
        const chatHistory = chatBox.querySelector('.chat-history');
        
        fetch(`get_messages_ajax.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                if (result.data && result.data.length > 0) {
                    const noMsg = chatHistory.querySelector('.no-messages'); if (noMsg) noMsg.remove();
                    const isAtBottom = chatHistory.scrollHeight - chatHistory.clientHeight <= chatHistory.scrollTop + 50;
                    let activeIds = new Set();
                    
                    result.data.forEach(msg => {
                        activeIds.add(msg.id);
                        let bubble = chatHistory.querySelector(`[data-msg-id="${msg.id}"]`);
                        if (bubble) {
                            let textContainer = bubble.querySelector('.message-text');
                            if (textContainer && textContainer.textContent !== msg.message && !bubble.querySelector('.edit-input-container')) {
                                textContainer.textContent = msg.message;
                                if (!bubble.querySelector('.edited-indicator')) {
                                    const meta = bubble.querySelector('.bubble-meta');
                                    const editedSpan = document.createElement('span');
                                    editedSpan.className = 'edited-indicator'; editedSpan.style.fontSize = '9px'; editedSpan.style.opacity = '0.7'; editedSpan.textContent = ' (edited)';
                                    meta.appendChild(editedSpan);
                                }
                            }
                        } else { chatHistory.innerHTML += renderMessageBubble(msg, currentUserId); }
                    });
                    
                    chatHistory.querySelectorAll('.chat-bubble').forEach(bubble => {
                        let msgId = parseInt(bubble.getAttribute('data-msg-id'));
                        if (msgId && !activeIds.has(msgId)) {
                            bubble.style.transition = 'opacity 0.3s'; bubble.style.opacity = '0';
                            setTimeout(() => bubble.remove(), 300);
                        }
                    });
                    if (isAtBottom) chatHistory.scrollTop = chatHistory.scrollHeight;
                } else {
                    chatHistory.querySelectorAll('.chat-bubble').forEach(b => b.remove());
                    if (!chatHistory.querySelector('.no-messages')) {
                        chatHistory.innerHTML = `<div class='no-messages' style='text-align:center; padding: 2rem 0; color:var(--muted); font-size: 13px;'><i class='ti ti-messages' style='font-size: 24px; color:var(--green); display:block; margin-bottom: 6px;'></i>No updates.</div>`;
                    }
                }
            }
        }).catch(err => console.log(err)).finally(() => chatBox.dataset.polling = 'false');
    }

    setInterval(function() {
        document.querySelectorAll('.chat-container').forEach(function(chatBox) {
            if (chatBox.style.display === 'flex') {
                let orderId = chatBox.id.replace('chat-box-', ''); pollNewMessages(orderId);
            }
        });
    }, 2000);

    document.addEventListener("DOMContentLoaded", function() {
        var openOrderId = <?php echo json_encode($open_order_id); ?>;
        if (openOrderId > 0) {
            toggleChat(openOrderId);
            var row = document.getElementById('order-row-' + openOrderId);
            if (row) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
