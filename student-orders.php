<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit(); }
$user_id = (int)$_SESSION['user_id'];
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/header.php';

$msg = ""; $error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id=(int)$_POST['order_id']; $new_status=$_POST['status']??'';
    $valid=['in_progress','completed','cancelled'];
    if (in_array($new_status,$valid)) {
        $s=$conn->prepare("SELECT o.client_id,o.student_id,g.price FROM orders o JOIN gigs g ON o.gig_id=g.id WHERE o.orderId=? AND o.student_id=?");
        if($s){$s->bind_param("ii",$order_id,$user_id);$s->execute();$od=$s->get_result()->fetch_assoc();$s->close();
            if($od){
                $s=$conn->prepare("UPDATE orders SET status=? WHERE orderId=? AND student_id=?");
                if($s){$s->bind_param("sii",$new_status,$order_id,$user_id);
                    if($s->execute()){
                        $msg="✓ Order #$order_id status updated to ".ucfirst(str_replace('_',' ',$new_status)).".";
                        if($new_status==='completed'){
                            $cid=(int)$od['client_id']; $amt=(float)$od['price'];
                            $pc=$conn->prepare("SELECT paymentId FROM payment WHERE orderId=? LIMIT 1");
                            if($pc){$pc->bind_param("i",$order_id);$pc->execute();$has=$pc->get_result()->num_rows>0;$pc->close();
                                if(!$has){$ps=$conn->prepare("INSERT INTO payment (orderId,client_id,student_id,amount,payment_status) VALUES(?,?,?,?,'completed')");
                                    if($ps){$ps->bind_param("iiid",$order_id,$cid,$user_id,$amt);$ps->execute();$ps->close();$msg.=" Payment of Rs. ".number_format($amt,2)." registered.";}
                                }
                            }
                        }
                    } else { $error_msg="Failed to update order status."; }
                    $s->close();
                }
            } else { $error_msg="Unauthorized or invalid order."; }
        }
    } else { $error_msg="Invalid status request."; }
}

$orders=[];
$s=$conn->prepare("SELECT o.orderId,o.status,o.created_at,g.title AS gig_title,g.price AS gig_price,u.fullname AS client_name FROM orders o JOIN gigs g ON o.gig_id=g.id JOIN users u ON o.client_id=u.id WHERE o.student_id=? ORDER BY o.created_at DESC");
if($s){$s->bind_param("i",$user_id);$s->execute();$res=$s->get_result();while($r=$res->fetch_assoc())$orders[]=$r;$s->close();}

// Get parameter for active chat panel if available
$open_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="css/student.css">
<link rel="stylesheet" href="css/student_orders.css">



<div class="wrap">
    <aside class="sidebar"><h2>Student Hub</h2><nav>
        <a href="student-dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="student-post-job.php"><i class="fas fa-briefcase"></i> Post Gig</a>
        <a href="student-orders.php" class="active"><i class="fas fa-shopping-basket"></i> Orders</a>
        <a href="my-gigs.php"><i class="fas fa-tasks"></i> My Reviews</a>

    </nav></aside>
    <main class="main">
        <h1>Manage Client Orders</h1>
        <?php if(!empty($msg)): ?><div class="status-alert" style="background:rgba(16,185,129,.1);border:1px solid var(--primary);color:var(--primary);padding:1rem;border-radius:8px;margin-bottom:1rem;max-height:200px;overflow:hidden;transition:opacity 0.5s ease, padding 0.5s ease, margin-bottom 0.5s ease, max-height 0.5s ease, border-color 0.5s ease;"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <?php if(!empty($error_msg)): ?><div class="status-alert" style="background:rgba(239,68,68,.1);border:1px solid #ef4444;color:#ef4444;padding:1rem;border-radius:8px;margin-bottom:1rem;max-height:200px;overflow:hidden;transition:opacity 0.5s ease, padding 0.5s ease, margin-bottom 0.5s ease, max-height 0.5s ease, border-color 0.5s ease;"><?php echo htmlspecialchars($error_msg); ?></div><?php endif; ?>
        
        <div class="container">
            <div class="section-header"><i class="fas fa-list"></i> Order Activity Queue</div>
            <div class="orders-list">
                <?php if(empty($orders)): ?><p style="color:var(--text-muted);text-align:center;padding:2rem 0;">📭 No orders received yet.</p>
                <?php else: foreach($orders as $o): 
                    $orderId = $o['orderId'];
                    // Calculate progress width and color dynamically based on order state
                    if ($o['status'] === 'pending') { $p_width = '25%'; $p_color = '#f59e0b'; }
                    elseif ($o['status'] === 'in_progress') { $p_width = '65%'; $p_color = '#1D9E75'; }
                    elseif ($o['status'] === 'completed') { $p_width = '100%'; $p_color = '#0F6E56'; }
                    else { $p_width = '0%'; $p_color = '#ef4444'; }
                ?>
                    <div class="order" id="order-row-<?php echo $orderId; ?>">
                        <div class="order-header">
                            <div>
                                <div class="order-title"><?php echo htmlspecialchars($o['gig_title']); ?></div>
                                <div class="order-meta" style="margin-top:5px;">
                                    <strong>Client:</strong> <?php echo htmlspecialchars($o['client_name']); ?> &nbsp;|&nbsp;
                                    <strong>Order ID:</strong> #<?php echo $orderId; ?> &nbsp;|&nbsp;
                                    <strong>Value:</strong> Rs. <?php echo number_format($o['gig_price'],2); ?>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span class="badge badge-<?php echo str_replace('_','-',$o['status']); ?>"><?php echo ucfirst(str_replace('_',' ',$o['status'])); ?></span>
                                <button id="chat-btn-<?php echo $orderId; ?>" onclick="toggleChat(<?php echo $orderId; ?>)" class="filter-btn" style="display:flex; align-items:center; gap:6px; font-weight:500; height:32px; padding:0 12px; border-color:rgba(16, 185, 129, 0.25); color: #10b981; background: rgba(16, 185, 129, 0.05);">
                                    <i class="ti ti-message"></i> Open Chat
                                </button>
                            </div>
                        </div>
                        <div class="order-desc" style="font-size:.85rem;color:var(--text-muted); margin-bottom:10px;"><strong>Received On:</strong> <?php echo date('M d, Y, h:i A',strtotime($o['created_at'])); ?></div>
                        
                        <div style='margin-bottom:15px; background:rgba(255,255,255,0.05); border-radius:20px; height:6px; overflow:hidden;'>
                            <div style='width:<?php echo $p_width; ?>; height:100%; background:<?php echo $p_color; ?>; border-radius:20px;'></div>
                        </div>

                        <div class="actions">
                            <?php if($o['status']==='pending'): ?>
                                <form method="POST" action="student-orders.php" style="display:inline;margin:0;padding:0;"><input type="hidden" name="order_id" value="<?php echo $orderId; ?>"><input type="hidden" name="status" value="in_progress"><button type="submit" name="update_status" class="btn-small" style="background:var(--primary);border:none;color:#fff;cursor:pointer;margin:0;">Accept Order</button></form>
                                <form method="POST" action="student-orders.php" style="display:inline;margin:0;padding:0;" onsubmit="return confirm('Decline this order?');"><input type="hidden" name="order_id" value="<?php echo $orderId; ?>"><input type="hidden" name="status" value="cancelled"><button type="submit" name="update_status" class="btn-small" style="background:transparent;border:1px solid #ef4444;color:#ef4444;cursor:pointer;margin:0;">Decline</button></form>
                            <?php elseif($o['status']==='in_progress'): ?>
                                <form method="POST" action="student-orders.php" style="display:inline;margin:0;padding:0;" onsubmit="return confirm('Mark as delivered?');"><input type="hidden" name="order_id" value="<?php echo $orderId; ?>"><input type="hidden" name="status" value="completed"><button type="submit" name="update_status" class="btn-small" style="background:var(--primary);border:none;color:#fff;cursor:pointer;margin:0;">Deliver &amp; Complete</button></form>
                            <?php endif; ?>
                        </div>

                        <div id="chat-box-<?php echo $orderId; ?>" class="chat-container" style="display:none; text-align: left;">
                            <div class="chat-header">
                                <i class="ti ti-brand-hipchat" style="color:#1D9E75; font-size:16px;"></i>
                                <span>Order Discussion Panel with <strong><?php echo htmlspecialchars($o['client_name']); ?></strong></span>
                            </div>
                            
                            <div class="chat-history">
                                <?php
                                 $msg_stmt = $conn->prepare("
                                     SELECT om.id, om.sender_id, om.message, om.file_path, om.sent_at, u.fullname 
                                     FROM order_messages om 
                                     JOIN users u ON om.sender_id = u.id 
                                     WHERE om.order_id = ? AND om.deleted_by_student = 0
                                     ORDER BY om.sent_at ASC
                                 ");
                                 if($msg_stmt) {
                                     $msg_stmt->bind_param("i", $orderId);
                                     $msg_stmt->execute();
                                     $chat_history = $msg_stmt->get_result();

                                     if ($chat_history->num_rows > 0) {
                                         while ($msg = $chat_history->fetch_assoc()) {
                                             $is_current_user = (intval($msg['sender_id']) === $user_id);
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
                                                 <div style="font-weight: 600; font-size: 11px; color: <?php echo $is_current_user ? '#34d399' : 'var(--color-text-secondary)'; ?>;">
                                                     <?php echo htmlspecialchars($msg['fullname']); ?>
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
                                                     <div style="margin-top: 8px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 6px;">
                                                         <a href="<?php echo htmlspecialchars($msg['file_path']); ?>" download class="attachment-btn" style="color: #10b981; text-decoration: none; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px; font-weight: 500;">
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
                                         echo "<div class='no-messages' style='text-align:center; padding: 2rem 0; color:var(--color-text-secondary); font-size: 13px;'>
                                                 <i class='ti ti-messages' style='font-size: 24px; color:#1D9E75; display:block; margin-bottom: 6px;'></i>
                                                 No messages yet. Send a query to start discussing.
                                               </div>";
                                     }
                                     $msg_stmt->close();
                                 }   
                                ?>
                            </div>

                            <form class="chat-input-form" id="chat-form-<?php echo $orderId; ?>" onsubmit="submitChatMessage(event, <?php echo $orderId; ?>)" enctype="multipart/form-data">
                                <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                                <button type="button" onclick="document.getElementById('attachment-<?php echo $orderId; ?>').click()" class="filter-btn" style="height: 44px; width: 44px; display: flex; align-items: center; justify-content: center; padding: 0; font-size: 16px; border-color: var(--color-border-tertiary); background: rgba(255,255,255,0.02);">
                                    <i class="ti ti-paperclip"></i>
                                </button>
                                <input type="file" name="attachment" id="attachment-<?php echo $orderId; ?>" style="display: none;" onchange="handleFileSelected(<?php echo $orderId; ?>)">
                                
                                <div style="flex: 1; display: flex; flex-direction: column;">
                                    <textarea name="message" id="message-text-<?php echo $orderId; ?>" class="chat-textarea" placeholder="Type your message here..." onkeydown="handleTextareaKeydown(event, <?php echo $orderId; ?>)"></textarea>
                                    <div id="file-name-badge-<?php echo $orderId; ?>" style="display: none; align-items: center; gap: 6px; font-size: 11px; color: #10b981; margin-top: 4px; background: rgba(16, 185, 129, 0.08); padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.15); width: fit-content;">
                                        <i class="ti ti-file"></i> <span class="file-txt"></span>
                                        <button type="button" onclick="clearFileSelected(<?php echo $orderId; ?>)" style="background:none; border:none; color:#ef4444; font-weight:bold; cursor:pointer; font-size: 13px; line-height: 1; margin-left: 4px;">&times;</button>
                                    </div>
                                </div>
                                
                                <button type="submit" class="chat-btn-send">
                                    <i class="ti ti-send"></i> Send
                                </button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
    function toggleChat(orderId) {
        var chatBox = document.getElementById('chat-box-' + orderId);
        if (chatBox) {
            var btn = document.getElementById('chat-btn-' + orderId);
            if (chatBox.style.display === 'none' || chatBox.style.display === '') {
                chatBox.style.display = 'flex';
                if (btn) {
                    btn.innerHTML = '<i class="ti ti-message-off"></i> Close Chat';
                    btn.style.background = 'rgba(239, 68, 68, 0.12)';
                    btn.style.color = '#ef4444';
                    btn.style.borderColor = 'rgba(239, 68, 68, 0.2)';
                }
                var historyDiv = chatBox.querySelector('.chat-history');
                if (historyDiv) {
                    historyDiv.scrollTop = historyDiv.scrollHeight;
                }
            } else {
                chatBox.style.display = 'none';
                if (btn) {
                    btn.innerHTML = '<i class="ti ti-message"></i> Open Chat';
                    btn.style.background = '';
                    btn.style.color = '';
                    btn.style.borderColor = '';
                }
            }
        }
    }

    const currentUserId = <?php echo json_encode($user_id); ?>;

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function renderMessageBubble(msg, currentUserId) {
        let isCurrentUser = (msg.sender_id === currentUserId);
        let bubbleClass = isCurrentUser ? 'chat-bubble-sent' : 'chat-bubble-received';
        let metaClass = isCurrentUser ? 'bubble-meta-sent' : 'bubble-meta-received';
        let labelColor = isCurrentUser ? '#34d399' : 'var(--color-text-secondary)';
        
        let messageTextHtml = msg.message ? `<div class="message-text" style="word-break: break-word; white-space: pre-wrap;">${escapeHtml(msg.message)}</div>` : '';
        let fileAttachmentHtml = '';
        
        if (msg.file_path) {
            let filename = msg.file_path.split('/').pop();
            fileAttachmentHtml = `
                <div style="margin-top: 8px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 6px;">
                    <a href="${escapeHtml(msg.file_path)}" download class="attachment-btn" style="color: #10b981; text-decoration: none; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px; font-weight: 500;">
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
                <div style="font-weight: 600; font-size: 11px; color: ${labelColor};">
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
            if (dropdown.id !== 'bubble-dropdown-' + msgId) {
                dropdown.style.display = 'none';
            }
        });
        
        const dropdown = document.getElementById('bubble-dropdown-' + msgId);
        if (dropdown) {
            dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
        }
    }
    
    document.addEventListener('click', function() {
        document.querySelectorAll('.bubble-menu-dropdown').forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    });

    function editMessageInline(msgId) {
        const bubble = document.querySelector(`[data-msg-id="${msgId}"]`);
        if (!bubble) return;
        
        const dropdown = document.getElementById('bubble-dropdown-' + msgId);
        if (dropdown) dropdown.style.display = 'none';
        if (bubble.querySelector('.edit-input-container')) return;
        
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
        
        if (newText === '') {
            alert('Message content cannot be empty.');
            return;
        }
        
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
        
        const formData = new FormData();
        formData.append('message_id', msgId);
        formData.append('message', newText);
        
        fetch('edit_message_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                const textContainer = bubble.querySelector('.message-text');
                if (textContainer) {
                    textContainer.textContent = result.data.message;
                    textContainer.style.display = 'block';
                }
                const attachment = bubble.querySelector('.attachment-btn');
                if (attachment) attachment.parentElement.style.display = 'block';
                
                if (!bubble.querySelector('.edited-indicator')) {
                    const meta = bubble.querySelector('.bubble-meta');
                    const editedSpan = document.createElement('span');
                    editedSpan.className = 'edited-indicator';
                    editedSpan.style.fontSize = '9px';
                    editedSpan.style.opacity = '0.7';
                    editedSpan.textContent = ' (edited)';
                    meta.appendChild(editedSpan);
                }
                editContainer.remove();
            } else {
                alert('Error: ' + result.message);
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save';
            }
        })
        .catch(error => {
            console.error('Error saving edit:', error);
            alert('Failed to save message.');
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save';
        });
    }

    function deleteMessage(msgId) {
        if (!confirm('Are you sure you want to delete this message?')) return;
        
        const dropdown = document.getElementById('bubble-dropdown-' + msgId);
        if (dropdown) dropdown.style.display = 'none';
        
        const formData = new FormData();
        formData.append('message_id', msgId);
        
        fetch('delete_message_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                const bubble = document.querySelector(`[data-msg-id="${msgId}"]`);
                if (bubble) {
                    bubble.style.transition = 'opacity 0.3s';
                    bubble.style.opacity = '0';
                    setTimeout(() => { bubble.remove(); }, 300);
                }
            } else {
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error deleting message:', error);
            alert('Failed to delete message.');
        });
    }

    function handleFileSelected(orderId) {
        const fileInput = document.getElementById('attachment-' + orderId);
        const badge = document.getElementById('file-name-badge-' + orderId);
        if (fileInput.files.length > 0) {
            const filename = fileInput.files[0].name;
            badge.querySelector('.file-txt').textContent = filename;
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    }

    function clearFileSelected(orderId) {
        const fileInput = document.getElementById('attachment-' + orderId);
        const badge = document.getElementById('file-name-badge-' + orderId);
        fileInput.value = '';
        badge.style.display = 'none';
        badge.querySelector('.file-txt').textContent = '';
    }

    function handleTextareaKeydown(event, orderId) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            submitChatMessage(event, orderId);
        }
    }

    function submitChatMessage(event, orderId) {
        if (event) event.preventDefault();
        const form = document.getElementById('chat-form-' + orderId);
        const textarea = document.getElementById('message-text-' + orderId);
        const fileInput = document.getElementById('attachment-' + orderId);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        const messageText = textarea.value.trim();
        const hasFile = fileInput.files.length > 0;
        
        if (!messageText && !hasFile) return;
        
        const formData = new FormData(form);
        
        textarea.disabled = true;
        fileInput.disabled = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ti ti-loader" style="font-size:16px;"></i>';
        
        fetch('send_message_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                textarea.value = '';
                clearFileSelected(orderId);
                
                const chatHistory = document.querySelector('#chat-box-' + orderId + ' .chat-history');
                if (chatHistory) {
                    const noMsg = chatHistory.querySelector('.no-messages');
                    if (noMsg) noMsg.remove();
                    
                    chatHistory.innerHTML += renderMessageBubble(result.data, currentUserId);
                    chatHistory.scrollTop = chatHistory.scrollHeight;
                }
            } else {
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            alert('Failed to send message.');
        })
        .finally(() => {
            textarea.disabled = false;
            fileInput.disabled = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ti ti-send"></i> Send';
            textarea.focus();
        });
    }

    function pollNewMessages(orderId) {
        const chatBox = document.getElementById('chat-box-' + orderId);
        if (!chatBox || chatBox.style.display !== 'flex') return;
        
        const chatHistory = chatBox.querySelector('.chat-history');
        if (!chatHistory) return;
        
        if (chatBox.dataset.polling === 'true') return;
        chatBox.dataset.polling = 'true';
        
        fetch(`get_messages_ajax.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                if (result.data && result.data.length > 0) {
                    const noMsg = chatHistory.querySelector('.no-messages');
                    if (noMsg) noMsg.remove();
                    
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
                                    editedSpan.className = 'edited-indicator';
                                    editedSpan.style.fontSize = '9px';
                                    editedSpan.style.opacity = '0.7';
                                    editedSpan.textContent = ' (edited)';
                                    meta.appendChild(editedSpan);
                                }
                            }
                        } else {
                            chatHistory.innerHTML += renderMessageBubble(msg, currentUserId);
                        }
                    });
                    
                    chatHistory.querySelectorAll('.chat-bubble').forEach(bubble => {
                        let msgId = parseInt(bubble.getAttribute('data-msg-id'));
                        if (msgId && !activeIds.has(msgId)) {
                            bubble.style.transition = 'opacity 0.3s';
                            bubble.style.opacity = '0';
                            setTimeout(() => bubble.remove(), 300);
                        }
                    });
                    
                    if (isAtBottom) {
                        chatHistory.scrollTop = chatHistory.scrollHeight;
                    }
                } else {
                    chatHistory.querySelectorAll('.chat-bubble').forEach(bubble => { bubble.remove(); });
                    if (!chatHistory.querySelector('.no-messages')) {
                        chatHistory.innerHTML = `<div class='no-messages' style='text-align:center; padding: 2rem 0; color:var(--color-text-secondary); font-size: 13px;'>
                                <i class='ti ti-messages' style='font-size: 24px; color:#1D9E75; display:block; margin-bottom: 6px;'></i>
                                No messages yet. Send a query to start discussing.
                              </div>`;
                    }
                }
            }
        })
        .catch(error => { console.error('Error polling messages:', error); })
        .finally(() => { chatBox.dataset.polling = 'false'; });
    }

    // Global interval loader for real-time streaming
    setInterval(function() {
        document.querySelectorAll('.chat-container').forEach(function(chatBox) {
            if (chatBox.style.display === 'flex') {
                let orderId = chatBox.id.replace('chat-box-', '');
                pollNewMessages(orderId);
            }
        });
    }, 2000);

    document.addEventListener("DOMContentLoaded", function() {

        var openOrderId = <?php echo json_encode($open_order_id); ?>;
        if (openOrderId > 0) {
            toggleChat(openOrderId);
            var row = document.getElementById('order-row-' + openOrderId);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
</script>

<script src="js/student.js"></script>
<?php include_once __DIR__ . '/includes/footer.php'; ?>