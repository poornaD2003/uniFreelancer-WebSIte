// student.js - Client-side UI enhancements for the Student Dashboard

document.addEventListener('DOMContentLoaded', () => {
    // 1. Automatically highlight active sidebar link based on current path
    const currentPath = window.location.pathname.split('/').pop();
    const sidebarLinks = document.querySelectorAll('.sidebar nav a');

    sidebarLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (currentPath === linkPath) {
            sidebarLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        }
    });

<<<<<<< Updated upstream
    // 2. Auto-dismiss alert notification banners after 4 seconds
    const alerts = document.querySelectorAll('.main div[style*="background"]');
=======
    const alerts = document.querySelectorAll('.status-alert');
>>>>>>> Stashed changes
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => { alert.style.display = 'none'; }, 500);
        }, 4000);
    });
});

// --- Chat & Messaging Functions ---

function toggleChat(orderId) {
    const chatBox = document.getElementById('chat-box-' + orderId);
    if (chatBox) {
        const btn = document.getElementById('chat-btn-' + orderId);
        if (chatBox.style.display === 'none' || chatBox.style.display === '') {
            chatBox.style.display = 'flex';
            if (btn) {
                btn.innerHTML = '<i class="ti ti-message-off"></i> Close Chat';
                btn.style.background = 'rgba(239, 68, 68, 0.12)';
                btn.style.color = '#ef4444';
                btn.style.borderColor = 'rgba(239, 68, 68, 0.2)';
            }
            const historyDiv = chatBox.querySelector('.chat-history');
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
    const isCurrentUser = (msg.sender_id === currentUserId);
    const bubbleClass = isCurrentUser ? 'chat-bubble-sent' : 'chat-bubble-received';
    const metaClass = isCurrentUser ? 'bubble-meta-sent' : 'bubble-meta-received';
    const labelColor = isCurrentUser ? '#34d399' : 'var(--text-muted)';
    
    const messageTextHtml = msg.message ? `<div class="message-text" style="word-break: break-word; white-space: pre-wrap;">${escapeHtml(msg.message)}</div>` : '';
    let fileAttachmentHtml = '';
    
    if (msg.file_path) {
        const filename = msg.file_path.split('/').pop();
        fileAttachmentHtml = `
            <div style="margin-top: 8px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 6px;">
                <a href="${escapeHtml(msg.file_path)}" download class="attachment-btn" style="color: #10b981; text-decoration: none; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px; font-weight: 500;">
                    <i class="ti ti-download"></i> Download ${escapeHtml(filename)}
                </a>
            </div>
        `;
    }
    
    const menuOptionsHtml = isCurrentUser ? `
        <button onclick="editMessageInline(${msg.id})"><i class="ti ti-edit"></i> Edit</button>
        <button class="delete-btn" onclick="deleteMessage(${msg.id})"><i class="ti ti-trash"></i> Delete</button>
    ` : `
        <button class="delete-btn" onclick="deleteMessage(${msg.id})"><i class="ti ti-trash"></i> Delete for Me</button>
    `;
    
    const threeDotMenuHtml = `
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
                
                // Clear out globally deleted messages
                chatHistory.querySelectorAll('.chat-bubble').forEach(bubble => {
                    let id = parseInt(bubble.dataset.msgId);
                    if (!activeIds.has(id)) {
                        bubble.remove();
                    }
                });
                
                if (isAtBottom) {
                    chatHistory.scrollTop = chatHistory.scrollHeight;
                }
            } else {
                chatHistory.innerHTML = `<div class='no-messages' style='text-align:center; padding: 2rem 0; color:var(--text-muted); font-size: 13px;'>
                                            <i class='ti ti-messages' style='font-size: 24px; color:#10b981; display:block; margin-bottom: 6px;'></i>
                                            No messages yet. Send a message to start discussing.
                                          </div>`;
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

// Run global polling for any open chat window every 2 seconds
setInterval(function() {
    document.querySelectorAll('.chat-container').forEach(function(chatBox) {
        if (chatBox.style.display === 'flex') {
            const orderId = chatBox.id.replace('chat-box-', '');
            pollNewMessages(orderId);
        }
    });
}, 2000);
