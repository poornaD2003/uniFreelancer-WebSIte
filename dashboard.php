<?php
include 'includes/db.php';
include 'includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure only logged-in students can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get student name
$user_stmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
$user_stmt->execute([$student_id]);
$user_data = $user_stmt->fetch();
$student_name = $user_data ? $user_data['fullname'] : "Freelancer";

// Generate initials for profile preview
$words = explode(" ", $student_name);
$initials = "";
foreach ($words as $w) {
    if (!empty($w)) $initials .= strtoupper($w[0]);
}
$initials = substr($initials, 0, 2);

// Calculate student metrics
// Total orders
$total_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE student_id = ?");
$total_stmt->execute([$student_id]);
$total_orders = $total_stmt->fetch()['total'] ?? 0;

// Pending orders
$pending_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE student_id = ? AND status = 'pending'");
$pending_stmt->execute([$student_id]);
$pending_orders = $pending_stmt->fetch()['total'] ?? 0;

// Completed orders
$completed_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE student_id = ? AND status = 'completed'");
$completed_stmt->execute([$student_id]);
$completed_orders = $completed_stmt->fetch()['total'] ?? 0;

// Earnings (sum of pricing of completed orders)
$earnings_stmt = $pdo->prepare("SELECT SUM(g.price) as earnings FROM orders o JOIN gigs g ON o.gig_id = g.id WHERE o.student_id = ? AND o.status = 'completed'");
$earnings_stmt->execute([$student_id]);
$total_earnings = $earnings_stmt->fetch()['earnings'] ?? 0.00;

// Get parameter for active chat panel
$open_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UniLance Student Hub</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --color-background-primary: #0b0f19;
            --color-background-secondary: #111827;
            --color-border-tertiary: #1f2937;
            --color-text-primary: #f3f4f6;
            --color-text-secondary: #9ca3af;
            --border-radius-md: 8px;
            --border-radius-lg: 12px;
        }
        body { background: var(--color-background-primary); color: var(--color-text-primary); font-family: sans-serif; padding: 20px; }
        .sidebar {
            width: 240px; min-height: 600px;
            background: var(--color-background-secondary);
            border-right: 0.5px solid var(--color-border-tertiary);
            padding: 1.5rem 0;
            display: flex; flex-direction: column; gap: 4px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 20px;
            font-size: 14px; color: var(--color-text-secondary);
            cursor: pointer; border: none; background: none;
            width: 100%; text-align: left; transition: all 0.2s;
        }
        .nav-item.active {
            background: var(--color-background-primary);
            color: var(--color-text-primary);
            font-weight: 500;
            border-left: 3px solid #1D9E75;
            padding-left: 17px;
        }
        .nav-item i { font-size: 18px; }
        .metric-card {
            background: var(--color-background-secondary);
            border-radius: var(--border-radius-md);
            padding: 1rem 1.25rem;
            flex: 1; min-width: 200px;
            border: 0.5px solid var(--color-border-tertiary);
        }
        .metric-label { font-size: 12px; color: var(--color-text-secondary); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .metric-value { font-size: 22px; font-weight: 500; color: var(--color-text-primary); }
        .metric-sub { font-size: 11px; color: var(--color-text-secondary); margin-top: 4px; }
        .badge { font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 500; display: inline-block; }
        .badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .badge-completed { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        .section { display: none; }
        .section.visible { display: block; }
        .filter-btn {
            font-size: 12px; padding: 6px 14px;
            border: 0.5px solid var(--color-border-tertiary);
            background: var(--color-background-primary);
            border-radius: var(--border-radius-md);
            color: var(--color-text-secondary); cursor: pointer;
        }
        .filter-btn.active-filter { background: #1D9E75; color: #fff; border-color: #1D9E75; }
        
        /* Glassmorphic Order Chat Box Styles */
        .chat-container {
            margin-top: 1rem;
            background: rgba(17, 24, 39, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--color-border-tertiary);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: slideDown 0.3s ease-out;
        }
        .chat-header {
            padding: 10px 16px;
            background: rgba(31, 41, 55, 0.4);
            border-bottom: 1px solid var(--color-border-tertiary);
            font-size: 13px;
            color: var(--color-text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .chat-history {
            max-height: 280px;
            overflow-y: auto;
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .chat-bubble {
            max-width: 75%;
            padding: 10px 14px;
            font-size: 13.5px;
            line-height: 1.5;
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
            padding-right: 30px !important; /* Make room for the menu button */
        }
        .chat-bubble-sent {
            align-self: flex-end;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 14px 14px 2px 14px;
        }
        .chat-bubble-received {
            align-self: flex-start;
            background: rgba(31, 41, 55, 0.5);
            border: 1px solid var(--color-border-tertiary);
            border-radius: 14px 14px 14px 2px;
        }
        .bubble-meta {
            font-size: 10px;
            color: var(--color-text-secondary);
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }
        .bubble-meta-sent {
            align-self: flex-end;
        }
        .bubble-meta-received {
            align-self: flex-start;
        }
        .chat-input-form {
            padding: 12px 15px;
            background: rgba(17, 24, 39, 0.8);
            border-top: 1px solid var(--color-border-tertiary);
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        .chat-textarea {
            flex: 1;
            height: 44px;
            min-height: 44px;
            max-height: 100px;
            background: rgba(11, 15, 25, 0.8);
            border: 1px solid var(--color-border-tertiary);
            border-radius: var(--border-radius-md);
            padding: 10px 12px;
            color: var(--color-text-primary);
            font-family: inherit;
            font-size: 13px;
            resize: none;
            outline: none;
            transition: border-color 0.2s;
        }
        .chat-textarea:focus {
            border-color: #1D9E75;
        }
        .chat-btn-send {
            background: #1D9E75;
            color: #fff;
            border: none;
            padding: 0 16px;
            height: 44px;
            border-radius: var(--border-radius-md);
            font-weight: 500;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .chat-btn-send:hover {
            background: #0F6E56;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Three dot menu styles */
        .bubble-menu-container {
            position: absolute;
            top: 6px;
            right: 8px;
            display: inline-block;
        }
        .bubble-menu-btn {
            background: none;
            border: none;
            color: var(--color-text-secondary);
            cursor: pointer;
            padding: 2px 4px;
            font-size: 14px;
            border-radius: 4px;
            transition: background 0.2s, color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 20px;
            width: 20px;
        }
        .bubble-menu-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--color-text-primary);
        }
        .bubble-menu-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 22px;
            background: #1f2937;
            border: 1px solid var(--color-border-tertiary);
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            z-index: 100;
            min-width: 120px;
            overflow: hidden;
        }
        .bubble-menu-dropdown button {
            width: 100%;
            background: none;
            border: none;
            color: var(--color-text-primary);
            padding: 8px 12px;
            text-align: left;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .bubble-menu-dropdown button:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .bubble-menu-dropdown button.delete-btn {
            color: #ef4444;
        }
        .bubble-menu-dropdown button.delete-btn:hover {
            background: rgba(239, 68, 68, 0.08);
        }
        
        /* Edit mode styles */
        .edit-input-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 6px;
            width: 100%;
        }
        .edit-textarea {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #10b981;
            color: var(--color-text-primary);
            border-radius: 6px;
            padding: 6px 8px;
            font-size: 13px;
            font-family: inherit;
            resize: none;
            outline: none;
            min-height: 48px;
        }
        .edit-actions {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
        }
        .edit-btn-save {
            background: #10b981;
            color: white;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            font-weight: 500;
        }
        .edit-btn-save:hover {
            background: #059669;
        }
        .edit-btn-cancel {
            background: none;
            border: 1px solid var(--color-border-tertiary);
            color: var(--color-text-secondary);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
        }
        .edit-btn-cancel:hover {
            background: rgba(255, 255, 255, 0.02);
            color: var(--color-text-primary);
        }
        .edited-indicator {
            font-style: italic;
            opacity: 0.7;
        }
    </style>

    <script>
        function switchTab(name, btn) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('visible'));
            var target = document.getElementById(name);
            if (target) {
                target.classList.add('visible');
            }
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
            if (btn) {
                btn.classList.add('active');
            } else {
                var activeBtn = document.querySelector(`.nav-item[onclick*="${name}"]`);
                if (activeBtn) activeBtn.classList.add('active');
            }
            if (history.pushState) {
                history.pushState(null, null, '#' + name);
            } else {
                location.hash = name;
            }
        }

        function handleRouting() {
            var tab = window.location.hash.substring(1);
            var match = window.location.search.match(/[?&]tab=([^&]+)/);
            var tabParam = match ? match[1] : null;

            if (!tab && tabParam) tab = tabParam;
            if (!tab) tab = 'dashboard';

            var allowedTabs = ['dashboard', 'status'];
            if (tab && allowedTabs.indexOf(tab) !== -1) {
                switchTab(tab);
            }
        }

        window.addEventListener('DOMContentLoaded', handleRouting);
        window.addEventListener('hashchange', handleRouting);

        function filterOrders(type, btn) {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active-filter'));
            btn.classList.add('active-filter');

            document.querySelectorAll('.order-row-container').forEach(row => {
                if (type === 'all' || row.dataset.status === type) {
                    row.style.display = 'block';
                } else {
                    row.style.display = 'none';
                }
            });
        }

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
                    // Scroll history to bottom
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

        const currentUserId = <?php echo json_encode($student_id); ?>;

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
                        setTimeout(() => {
                            bubble.remove();
                        }, 300);
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
            
            if (!messageText && !hasFile) {
                return;
            }
            
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
                        chatHistory.querySelectorAll('.chat-bubble').forEach(bubble => {
                            bubble.remove();
                        });
                        if (!chatHistory.querySelector('.no-messages')) {
                            chatHistory.innerHTML = `<div class='no-messages' style='text-align:center; padding: 2rem 0; color:var(--color-text-secondary); font-size: 13px;'>
                                    <i class='ti ti-messages' style='font-size: 24px; color:#1D9E75; display:block; margin-bottom: 6px;'></i>
                                    No messages yet. Send a query to start discussing.
                                  </div>`;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error polling messages:', error);
            })
            .finally(() => {
                chatBox.dataset.polling = 'false';
            });
        }

        // Global open chat interval polling
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
                switchTab('status');
                toggleChat(openOrderId);
                var row = document.getElementById('order-row-' + openOrderId);
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    </script>
</head>
<body>

<?php
// Handle potential toast alerts
$msg_status = $_GET['msg_status'] ?? '';
if (!empty($msg_status)):
    $toast_bg = 'rgba(239, 68, 68, 0.95)';
    $toast_text = 'Something went wrong.';
    $toast_icon = 'ti-alert-circle';
    
    if ($msg_status === 'success') {
        $toast_bg = 'rgba(29, 158, 117, 0.95)';
        $toast_text = 'Message sent successfully!';
        $toast_icon = 'ti-circle-check';
    } elseif ($msg_status === 'empty_message') {
        $toast_text = 'Cannot send an empty message.';
    } elseif ($msg_status === 'unauthorized') {
        $toast_text = 'You do not have access to this chat thread.';
    } elseif ($msg_status === 'order_not_found') {
        $toast_text = 'Requested order record not found.';
    }
?>
    <div id="toast-message" style="position: fixed; top: 80px; right: 20px; background: <?php echo $toast_bg; ?>; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); color: white; padding: 14px 24px; border-radius: var(--border-radius-md); box-shadow: 0 8px 32px 0 rgba(0,0,0,0.37); border: 1px solid rgba(255,255,255,0.1); z-index: 1000; display: flex; align-items: center; gap: 12px; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); transform: translateY(-20px); opacity: 0;">
        <i class="ti <?php echo $toast_icon; ?>" style="font-size: 20px;"></i>
        <span style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($toast_text); ?></span>
        <button onclick="dismissToast()" style="background: none; border: none; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 18px; margin-left: 8px; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px;">&times;</button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toast = document.getElementById('toast-message');
            if (toast) {
                toast.offsetHeight; // Trigger reflow
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
                setTimeout(function() { dismissToast(); }, 5000);
            }
        });
        function dismissToast() {
            var toast = document.getElementById('toast-message');
            if (toast) {
                toast.style.transform = 'translateY(-20px)';
                toast.style.opacity = '0';
                setTimeout(function() { toast.remove(); }, 400);
            }
        }
    </script>
<?php endif; ?>

<div style="display:flex; border: 0.5px solid var(--color-border-tertiary); border-radius: var(--border-radius-lg); overflow: hidden; min-height: 650px;">
  
  <div class="sidebar">
    <div style="padding: 0 20px 1rem; border-bottom: 0.5px solid var(--color-border-tertiary); margin-bottom: 0.5rem;">
      <div style="display:flex; align-items:center; gap:10px;">
        <div style="width:38px;height:38px;border-radius:50%;background:#1D9E75;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:500;color:white;"><?php echo $initials; ?></div>
        <div>
          <div style="font-size:13px;font-weight:500;color:var(--color-text-primary);"><?php echo htmlspecialchars($student_name); ?></div>
          <div style="font-size:11px;color:var(--color-text-secondary);">Student Portal</div>
        </div>
      </div>
    </div>
    <button class="nav-item active" onclick="switchTab('dashboard',this)"><i class="ti ti-layout-dashboard" aria-hidden="true"></i> Dashboard</button>
    <button class="nav-item" onclick="switchTab('status',this)"><i class="ti ti-list-check" aria-hidden="true"></i> Received Orders</button>
  </div>

  <div style="flex:1; padding: 1.5rem; overflow:auto;">

    <div id="dashboard" class="section visible">
      <div style="margin-bottom:1.25rem;">
        <div style="font-size:18px;font-weight:500;">Welcome back, <?php echo htmlspecialchars($student_name); ?> 👋</div>
        <div style="font-size:13px;color:var(--color-text-secondary);margin-top:4px;">Keep track of your gigs, earnings, and client messages</div>
      </div>

      <div style="display:flex;gap:12px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <div class="metric-card">
          <div class="metric-label"><i class="ti ti-shopping-bag"></i> Received Orders</div>
          <div class="metric-value"><?php echo $total_orders; ?></div>
          <div class="metric-sub">Lifetime projects</div>
        </div>
        <div class="metric-card">
          <div class="metric-label"><i class="ti ti-clock"></i> Active Pending</div>
          <div class="metric-value" style="color:#f59e0b;"><?php echo $pending_orders; ?></div>
          <div class="metric-sub">Work in progress</div>
        </div>
        <div class="metric-card">
          <div class="metric-label"><i class="ti ti-circle-check"></i> Completed</div>
          <div class="metric-value" style="color:#10b981;"><?php echo $completed_orders; ?></div>
          <div class="metric-sub">Delivered tasks</div>
        </div>
        <div class="metric-card">
          <div class="metric-label"><i class="ti ti-currency-dollar"></i> Net Earnings</div>
          <div class="metric-value" style="color:#10b981;">Rs. <?php echo number_format($total_earnings, 2); ?></div>
          <div class="metric-sub">Cleared payouts</div>
        </div>
      </div>

      <div style="background:var(--color-background-secondary);border-radius:var(--border-radius-md);padding:1rem 1.25rem; border: 0.5px solid var(--color-border-tertiary);">
        <div style="font-size:14px;font-weight:500;margin-bottom:12px;">Recent Work Orders</div>
        <table style="width:100%;font-size:13px;border-collapse:collapse;">
          <thead>
            <tr style="color:var(--color-text-secondary); text-align: left;">
              <th style="padding:8px 0;">Gig Title</th>
              <th style="padding:8px 0;">Client Name</th>
              <th style="padding:8px 0;">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $recent_stmt = $pdo->prepare("SELECT o.status, g.title, u.fullname as client_name FROM orders o JOIN gigs g ON o.gig_id = g.id JOIN users u ON o.client_id = u.id WHERE o.student_id = ? ORDER BY o.orderId DESC LIMIT 3");
            $recent_stmt->execute([$student_id]);
            $recent_orders = $recent_stmt->fetchAll();

            if (!empty($recent_orders)) {
                foreach ($recent_orders as $r) {
                    $badge = ($r['status'] === 'pending') ? 'badge-pending' : 'badge-completed';
                    echo "<tr style='border-top:0.5px solid var(--color-border-tertiary);'>
                            <td style='padding:12px 0;'>" . htmlspecialchars($r['title']) . "</td>
                            <td style='padding:12px 0;color:var(--color-text-secondary);'>" . htmlspecialchars($r['client_name']) . "</td>
                            <td style='padding:12px 0;'><span class='badge {$badge}'>" . ucfirst($r['status']) . "</span></td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='3' style='padding:10px 0;color:var(--color-text-secondary);'>No work orders received yet.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="status" class="section">
      <div style="font-size:18px;font-weight:500;margin-bottom:1.25rem;">My Work Pipelines</div>
      <div style="display:flex;gap:8px;margin-bottom:1rem;flex-wrap:wrap;">
        <button class="filter-btn active-filter" onclick="filterOrders('all',this)">All Work Orders</button>
        <button class="filter-btn" onclick="filterOrders('pending',this)">Pending</button>
        <button class="filter-btn" onclick="filterOrders('completed',this)">Completed</button>
      </div>

      <div id="order-rows" style="display:flex;flex-direction:column;gap:10px;">
        <?php
        $status_stmt = $pdo->prepare("SELECT o.orderId, o.status, g.title, u.fullname as client_name, g.price FROM orders o JOIN gigs g ON o.gig_id = g.id JOIN users u ON o.client_id = u.id WHERE o.student_id = ? ORDER BY o.orderId DESC");
        $status_stmt->execute([$student_id]);
        $orders = $status_stmt->fetchAll();

        if (!empty($orders)) {
            foreach ($orders as $row) {
                $orderId = $row['orderId'];
                $badge = ($row['status'] === 'pending') ? 'badge-pending' : 'badge-completed';
                $progress_width = ($row['status'] === 'pending') ? '45%' : '100%';
                $progress_color = ($row['status'] === 'pending') ? '#1D9E75' : '#0F6E56';
                ?>
                <div class='order-row-container' id='order-row-<?php echo $orderId; ?>' data-status='<?php echo $row['status']; ?>'>
                    <div style='border:0.5px solid var(--color-border-tertiary);border-radius:var(--border-radius-md);padding:1rem 1.25rem; background: var(--color-background-secondary);'>
                        <div style='display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;'>
                            <div>
                                <div style='font-size:14px;font-weight:500;'><?php echo htmlspecialchars($row['title']); ?></div>
                                <div style='font-size:12px;color:var(--color-text-secondary);margin-top:2px;'>Client: <?php echo htmlspecialchars($row['client_name']); ?></div>
                            </div>
                            <div style='display:flex;align-items:center;gap:12px;'>
                                <span style='font-size:13px;font-weight:500;'>Rs. <?php echo number_format($row['price']); ?></span>
                                <span class='badge <?php echo $badge; ?>'><?php echo ucfirst($row['status']); ?></span>
                                <button id="chat-btn-<?php echo $orderId; ?>" onclick="toggleChat(<?php echo $orderId; ?>)" class="filter-btn" style="display:flex; align-items:center; gap:6px; font-weight:500; height:32px; padding:0 12px; border-color:rgba(16, 185, 129, 0.25); color: #10b981; background: rgba(16, 185, 129, 0.05);">
                                    <i class="ti ti-message"></i> Open Chat
                                </button>
                            </div>
                        </div>
                        <div style='margin-top:10px;'>
                            <div style='background:var(--color-background-primary);border-radius:20px;height:6px;overflow:hidden;'>
                                <div style='width:<?php echo $progress_width; ?>;height:100%;background:<?php echo $progress_color; ?>;border-radius:20px;'></div>
                            </div>
                        </div>

                        <!-- 2. Frontend UI Panel Component (Embedded Order Messages Box) -->
                        <div id="chat-box-<?php echo $orderId; ?>" class="chat-container" style="display:none;">
                            <div class="chat-header">
                                <i class="ti ti-brand-hipchat" style="color:#1D9E75; font-size:16px;"></i>
                                <span>Order Discussion Panel with <strong><?php echo htmlspecialchars($row['client_name']); ?></strong></span>
                            </div>
                            
                            <!-- History box of previous messages -->
                            <div class="chat-history">
                                <?php
                                 $msg_stmt = $pdo->prepare("
                                     SELECT om.id, om.sender_id, om.message, om.file_path, om.sent_at, u.fullname 
                                     FROM order_messages om 
                                     JOIN users u ON om.sender_id = u.id 
                                     WHERE om.order_id = ? AND om.deleted_by_student = 0
                                     ORDER BY om.sent_at ASC
                                 ");
                                 $msg_stmt->execute([$orderId]);
                                 $chat_history = $msg_stmt->fetchAll();

                                 if (!empty($chat_history)) {
                                     foreach ($chat_history as $msg) {
                                         $is_current_user = (intval($msg['sender_id']) === $student_id);
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
                </div>
                <?php
            }
        } else {
            echo "<p style='color:var(--color-text-secondary);'>No received pipeline work orders assigned yet.</p>";
        }
        ?>
      </div>
    </div>

  </div>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
