<?php
/**
 * EstateCraft Ethiopia - Live Chat with Administrator
 * Direct, verified communication between buyers/renters and Admin Abdi Habtamu
 */

$pageTitle = "Messages & Property Inquiries";
$activePage = "chat";

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/i18n.php';

// Requires authentication
$currentUser = requireAuth();
$isAdmin = (strtolower(trim($currentUser['email'] ?? '')) === 'abdihabtamu368@gmail.com');

$propertyId = (int)($_GET['property_id'] ?? 0);
$selectedConvId = (int)($_GET['conv_id'] ?? 0);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 1200px; margin: 30px auto; padding: 0 16px;">
    <!-- Breadcrumb -->
    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <a href="<?= BASE_URL ?>/index.php" style="color: var(--text-muted); text-decoration: none;">Home</a>
        <span>&gt;</span>
        <span style="color: var(--primary); font-weight: 600;">
            <?= $isAdmin ? 'Admin Message Center' : 'Chat with Admin Abdi Habtamu' ?>
        </span>
    </div>

    <!-- Main Chat Card -->
    <div class="chat-master-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; box-shadow: var(--shadow-md); overflow: hidden; display: flex; height: 750px; min-height: 600px;">
        
        <!-- Sidebar (Visible to Admin or for navigation) -->
        <div class="chat-sidebar" id="chatSidebar" style="width: 340px; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; background: var(--bg-main); flex-shrink: 0;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); background: var(--bg-card); display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 800; margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 6px;">
                        <span>💬</span> <?= $isAdmin ? 'User Inquiries' : 'Admin Support' ?>
                    </h3>
                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                        <?= $isAdmin ? 'All property inquiry threads' : 'Direct channel with Abdi Habtamu' ?>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="loadConversation(currentConvId)" title="Refresh Messages" style="padding: 4px 8px; font-size: 12px;">
                    🔄
                </button>
            </div>

            <!-- Thread List Container -->
            <div id="threadListContainer" style="flex: 1; overflow-y: auto; padding: 8px 10px;">
                <div style="text-align: center; padding: 30px 10px; color: var(--text-muted); font-size: 13px;">
                    Loading messages...
                </div>
            </div>

            <!-- User Quick Profile Footer -->
            <div style="padding: 14px 18px; border-top: 1px solid var(--border-color); background: var(--bg-card); display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: #0866ff; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px;">
                    <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 13px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-main);">
                        <?= htmlspecialchars($currentUser['full_name']) ?>
                    </div>
                    <div style="font-size: 11px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($currentUser['email']) ?>
                    </div>
                </div>
                <span class="badge" style="font-size: 10px; background: rgba(8, 102, 255, 0.1); color: #0866ff;">
                    <?= $isAdmin ? 'Admin' : 'Member' ?>
                </span>
            </div>
        </div>

        <!-- Chat Conversation Area -->
        <div class="chat-main" style="flex: 1; display: flex; flex-direction: column; background: var(--bg-card); position: relative;">
            
            <!-- Active Chat Header -->
            <div id="chatHeader" style="padding: 14px 22px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; background: var(--bg-card); z-index: 5;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div id="chatHeaderAvatar" style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #0866ff, #0052cc); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800; box-shadow: 0 2px 6px rgba(8, 102, 255, 0.3);">
                        A
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <strong id="chatHeaderName" style="font-size: 16px; color: var(--text-main);">Abdi Habtamu</strong>
                            <span id="chatHeaderBadge" class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.4); font-size: 11px;">
                                🛡️ Verified Administrator
                            </span>
                        </div>
                        <div id="chatHeaderSub" style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                            abdihabtamu368@gmail.com &bull; <span style="color: #10b981; font-weight: 600;">● Active Support</span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <a href="tel:+251909090909" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px;">
                        📞 Call Support
                    </a>
                </div>
            </div>

            <!-- Attached Property Card Header (Visible when chat is about a specific home) -->
            <div id="attachedPropertyBanner" style="display: none; background: rgba(8, 102, 255, 0.04); border-bottom: 1px solid var(--border-color); padding: 12px 20px; transition: all 0.3s ease;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px;">
                    <div style="display: flex; align-items: center; gap: 14px; min-width: 0;">
                        <img id="attachedPropImg" src="" alt="Property" style="width: 58px; height: 58px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color); flex-shrink: 0;">
                        <div style="min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                                <span class="badge" style="background: #0866ff; color: #fff; font-size: 10px; padding: 2px 6px;">Inquiring Regarding This Home</span>
                                <span id="attachedPropType" class="badge" style="background: #e2e8f0; color: #334155; font-size: 10px; padding: 2px 6px;">Villa</span>
                            </div>
                            <h4 id="attachedPropTitle" style="font-size: 14px; font-weight: 800; margin: 0; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Property Title</h4>
                            <div style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                <strong id="attachedPropPrice" style="color: #0866ff; font-weight: 800;">ETB 0</strong>
                                <span>&bull;</span>
                                <span id="attachedPropLoc">Location</span>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; flex-shrink: 0;">
                        <a id="attachedPropLink" href="#" target="_blank" class="btn btn-secondary btn-sm" style="font-size: 12px; padding: 5px 12px;">
                            View Listing &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Messages Stream Area -->
            <div id="messagesStream" style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 14px; background: var(--bg-main);">
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    Loading messages...
                </div>
            </div>

            <!-- Quick Inquiry Suggestions Pill Bar (for Users: Sale, Buy, Rent & Inquiries) -->
            <div id="quickSuggestionsBar" style="padding: 8px 16px; background: var(--bg-card); border-top: 1px solid var(--border-color); display: flex; gap: 8px; overflow-x: auto; white-space: nowrap; scrollbar-width: none;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickMessage('Hello Admin Abdi, I would like to list my home for SALE. Please review and post it.')" style="font-size: 11.5px; border-radius: 16px; padding: 4px 10px; background: rgba(8, 102, 255, 0.08); color: #0866ff; font-weight: 700;">
                    🏡 List Home for Sale
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickMessage('Hello Admin Abdi, I would like to list my property for RENT. Please assist me with publishing it.')" style="font-size: 11.5px; border-radius: 16px; padding: 4px 10px; background: rgba(16, 185, 129, 0.08); color: #059669; font-weight: 700;">
                    🔑 List Home for Rent
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickMessage('Hello Admin Abdi, I am looking to BUY a home. My preferred neighborhood is [Bole / CMC / Kazanchis] and budget is [ETB].')" style="font-size: 11.5px; border-radius: 16px; padding: 4px 10px;">
                    🔍 Looking to Buy
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickMessage('Hello Admin Abdi, I am looking to RENT a home/apartment. Can you recommend verified options?')" style="font-size: 11.5px; border-radius: 16px; padding: 4px 10px;">
                    🏢 Looking to Rent
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickMessage('Hello Admin Abdi, is this property still available for purchase/rent?')" style="font-size: 11.5px; border-radius: 16px; padding: 4px 10px;">
                    👋 Still available?
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setQuickMessage('Can I schedule a physical site inspection this week?')" style="font-size: 11.5px; border-radius: 16px; padding: 4px 10px;">
                    📅 Schedule viewing
                </button>
            </div>

            <!-- Message Input Form -->
            <form id="chatMessageForm" onsubmit="handleSendMessage(event)" style="padding: 14px 20px; background: var(--bg-card); border-top: 1px solid var(--border-color); display: flex; align-items: flex-end; gap: 10px;">
                <div style="flex: 1; position: relative;">
                    <textarea id="chatInputMessage" rows="1" class="form-input" placeholder="Type your message to <?= $isAdmin ? 'the user' : 'Admin Abdi Habtamu' ?>..." style="resize: none; min-height: 44px; max-height: 120px; padding: 10px 14px; border-radius: 22px; font-size: 14px; line-height: 1.4;" onkeydown="if(event.key === 'Enter' && !event.shiftKey){ event.preventDefault(); handleSendMessage(event); }"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" id="btnSendChat" style="border-radius: 50%; width: 44px; height: 44px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #0866ff; box-shadow: 0 4px 10px rgba(8, 102, 255, 0.3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </form>

        </div>
    </div>
</div>

<style>
/* Chat Responsive & Modern Aesthetics */
.chat-thread-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s ease;
    margin-bottom: 4px;
    text-decoration: none;
    color: var(--text-main);
}
.chat-thread-item:hover {
    background: rgba(8, 102, 255, 0.06);
}
.chat-thread-item.active-thread {
    background: #0866ff !important;
    color: #ffffff !important;
}
.chat-thread-item.active-thread .chat-thread-name,
.chat-thread-item.active-thread .chat-thread-sub,
.chat-thread-item.active-thread .chat-thread-msg {
    color: #ffffff !important;
}
.chat-msg-row {
    display: flex;
    gap: 8px;
    max-width: 80%;
}
.chat-msg-row.sent {
    align-self: flex-end;
    flex-direction: row-reverse;
}
.chat-msg-row.received {
    align-self: flex-start;
}
.chat-bubble {
    padding: 10px 15px;
    border-radius: 18px;
    font-size: 14px;
    line-height: 1.45;
    position: relative;
    word-break: break-word;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.chat-msg-row.sent .chat-bubble {
    background: #0866ff;
    color: #ffffff;
    border-bottom-right-radius: 4px;
}
.chat-msg-row.received .chat-bubble {
    background: var(--bg-card);
    color: var(--text-main);
    border: 1px solid var(--border-color);
    border-bottom-left-radius: 4px;
}
.chat-msg-time {
    font-size: 10.5px;
    margin-top: 4px;
    opacity: 0.75;
}
.chat-msg-row.sent .chat-msg-time {
    text-align: right;
    color: rgba(255, 255, 255, 0.85);
}
.chat-msg-row.received .chat-msg-time {
    text-align: left;
    color: var(--text-muted);
}
@media (max-width: 768px) {
    .chat-master-card {
        flex-direction: column !important;
        height: 85vh !important;
    }
    .chat-sidebar {
        width: 100% !important;
        height: 180px !important;
    }
}
</style>

<script>
let currentConvId = <?= json_encode($selectedConvId) ?>;
let activePropertyId = <?= json_encode($propertyId) ?>;
const isUserAdmin = <?= json_encode($isAdmin) ?>;
let pollTimer = null;

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

function formatDisplayDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(/-/g, '/'));
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function setQuickMessage(text) {
    const input = document.getElementById('chatInputMessage');
    if (input) {
        input.value = text;
        input.focus();
    }
}

// Load Threads (For Admin sidebar or recent channels)
function loadThreads() {
    if (!isUserAdmin) {
        // For regular user, render single Admin contact item
        const container = document.getElementById('threadListContainer');
        if (container) {
            container.innerHTML = `
                <div class="chat-thread-item active-thread">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #ffffff; color: #0866ff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; flex-shrink: 0;">
                        A
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div class="chat-thread-name" style="font-weight: 700; font-size: 13.5px;">Abdi Habtamu</div>
                        <div class="chat-thread-sub" style="font-size: 11.5px; opacity: 0.9;">Official Administrator</div>
                    </div>
                    <span style="font-size: 10px; background: rgba(255,255,255,0.25); color: #fff; padding: 2px 6px; border-radius: 10px;">Support</span>
                </div>
            `;
        }
        return;
    }

    fetch('<?= BASE_URL ?>/api/chat.php?action=get_threads', { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('threadListContainer');
            if (!container) return;
            if (!data.success || !data.threads || data.threads.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 30px 10px; color: var(--text-muted); font-size: 13px;">
                        No user inquiries received yet.
                    </div>
                `;
                return;
            }

            container.innerHTML = data.threads.map(t => {
                const isActive = (currentConvId === parseInt(t.id));
                const initial = (t.user_name || 'U').charAt(0).toUpperCase();
                return `
                    <div class="chat-thread-item ${isActive ? 'active-thread' : ''}" onclick="selectConversation(${t.id}, ${t.property_id || 0})">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: #0866ff; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0;">
                            ${initial}
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <strong class="chat-thread-name" style="font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(t.user_name)}</strong>
                                <span style="font-size: 10.5px; opacity: 0.7;">${formatDisplayDate(t.last_message_at)}</span>
                            </div>
                            ${t.property ? `
                                <div style="font-size: 11px; font-weight: 600; color: #f59e0b; margin: 1px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    🏡 ${escapeHtml(t.property.title)}
                                </div>
                            ` : ''}
                            <div class="chat-thread-msg" style="font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                ${escapeHtml(t.last_message || 'No messages yet')}
                            </div>
                        </div>
                        ${parseInt(t.admin_unread_count) > 0 ? `
                            <span style="background: #ef4444; color: #fff; font-size: 10.5px; font-weight: 800; border-radius: 10px; padding: 2px 6px;">
                                ${t.admin_unread_count}
                            </span>
                        ` : ''}
                    </div>
                `;
            }).join('');
        })
        .catch(err => console.error("Error loading threads:", err));
}

function selectConversation(convId, propId) {
    currentConvId = convId;
    if (propId > 0) activePropertyId = propId;
    loadThreads();
    loadConversation(convId);
}

// Load Messages for active conversation
function loadConversation(convId = null) {
    let url = '<?= BASE_URL ?>/api/chat.php?action=get_conversation';
    if (convId && convId > 0) url += '&conversation_id=' + convId;
    if (activePropertyId && activePropertyId > 0) url += '&property_id=' + activePropertyId;

    fetch(url, { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('messagesStream').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                        ${data.message || 'Could not load conversation.'}
                    </div>
                `;
                return;
            }

            currentConvId = parseInt(data.conversation.id);

            // Update Header Information
            if (isUserAdmin && data.user) {
                document.getElementById('chatHeaderName').textContent = data.user.full_name;
                document.getElementById('chatHeaderAvatar').textContent = data.user.full_name.charAt(0).toUpperCase();
                document.getElementById('chatHeaderBadge').innerHTML = '👤 Inquiring Buyer / Client';
                document.getElementById('chatHeaderSub').textContent = data.user.email + (data.user.phone ? ' • ' + data.user.phone : '');
            } else if (!isUserAdmin && data.admin) {
                document.getElementById('chatHeaderName').textContent = data.admin.full_name;
                document.getElementById('chatHeaderAvatar').textContent = 'A';
                document.getElementById('chatHeaderBadge').innerHTML = '🛡️ Verified Administrator';
                document.getElementById('chatHeaderSub').innerHTML = data.admin.email + ' &bull; <span style="color: #10b981; font-weight: 600;">● Active Support</span>';
            }

            // Attached Property Banner Linkage
            const banner = document.getElementById('attachedPropertyBanner');
            if (data.property) {
                banner.style.display = 'block';
                document.getElementById('attachedPropImg').src = data.property.image_url;
                document.getElementById('attachedPropTitle').textContent = data.property.title;
                document.getElementById('attachedPropType').textContent = (data.property.property_type || 'Home').toUpperCase();
                document.getElementById('attachedPropPrice').textContent = 'ETB ' + Number(data.property.price).toLocaleString();
                document.getElementById('attachedPropLoc').textContent = data.property.city + (data.property.state ? ', ' + data.property.state : '');
                document.getElementById('attachedPropLink').href = '<?= BASE_URL ?>/properties/details.php?id=' + data.property.id;
            } else {
                banner.style.display = 'none';
            }

            // Render Messages Stream
            const stream = document.getElementById('messagesStream');
            if (!data.messages || data.messages.length === 0) {
                stream.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 13.5px;">
                        👋 Start the conversation! Ask Admin Abdi Habtamu anything about home prices, inspections, or listings in Ethiopia.
                    </div>
                `;
                return;
            }

            const currentUserId = <?= (int)$currentUser['id'] ?>;
            stream.innerHTML = data.messages.map(m => {
                const isSentByMe = (parseInt(m.sender_id) === currentUserId);
                const roleBadge = m.sender_role === 'admin' ? '<span style="font-size: 10px; background: rgba(255,255,255,0.25); padding: 1px 5px; border-radius: 8px; margin-left: 4px;">Admin</span>' : '';
                return `
                    <div class="chat-msg-row ${isSentByMe ? 'sent' : 'received'}">
                        <div class="chat-bubble">
                            <div style="font-weight: 700; font-size: 11px; margin-bottom: 2px; opacity: 0.9;">
                                ${escapeHtml(m.sender_name)} ${roleBadge}
                            </div>
                            <div>${escapeHtml(m.message)}</div>
                            <div class="chat-msg-time">
                                ${formatDisplayDate(m.created_at)} ${isSentByMe ? (parseInt(m.is_read) === 1 ? '✓✓' : '✓') : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // Scroll to newest message
            stream.scrollTop = stream.scrollHeight;
        })
        .catch(err => console.error("Error loading conversation:", err));
}

// Send Message
function handleSendMessage(e) {
    if (e) e.preventDefault();
    const input = document.getElementById('chatInputMessage');
    const msg = input.value.trim();
    if (!msg) return;

    input.value = '';
    const btn = document.getElementById('btnSendChat');
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('conversation_id', currentConvId || 0);
    formData.append('message', msg);
    if (activePropertyId > 0) formData.append('property_id', activePropertyId);

    fetch('<?= BASE_URL ?>/api/chat.php', {
        method: 'POST',
        credentials: 'include',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            currentConvId = data.conversation_id;
            loadConversation(currentConvId);
            loadThreads();
        } else {
            alert(data.message || 'Failed to send message.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        console.error("Error sending message:", err);
    });
}

function setChatTemplate(type) {
    if (type === 'sale') setQuickMessage("Hello Admin Abdi, I would like to list my property for SALE. Please guide me on verification.");
    else if (type === 'rent') setQuickMessage("Hello Admin Abdi, I have a property available for RENT. How can I list it?");
    else if (type === 'buy') setQuickMessage("Hello Admin Abdi, I am looking to BUY a verified home in Addis Ababa.");
}

// Initial Kickoff & Background Polling
document.addEventListener('DOMContentLoaded', function() {
    loadThreads();
    loadConversation(currentConvId);

    // Check if user came to chat about listing a property (Sale, Rent, Buy)
    const urlParams = new URLSearchParams(window.location.search);
    const intent = urlParams.get('intent');
    const input = document.getElementById('chatInputMessage');
    if (input && !input.value && intent) {
        if (intent === 'sale') {
            input.value = "Hello Admin Abdi, I would like to list my property for SALE.\n- Property Type (Villa / Apartment / House / Condo):\n- Neighborhood / City: (e.g. Bole / CMC / Kazanchis):\n- Asking Price (ETB):\n- Bedrooms / Bathrooms:\n- Contact Phone Number: ";
            input.focus();
        } else if (intent === 'rent') {
            input.value = "Hello Admin Abdi, I would like to list my property for RENT.\n- Property Type (Apartment / Villa / Commercial):\n- Neighborhood / City: (e.g. Bole / CMC / Kazanchis):\n- Monthly Rent (ETB):\n- Bedrooms / Bathrooms:\n- Contact Phone Number: ";
            input.focus();
        } else if (intent === 'buy') {
            input.value = "Hello Admin Abdi, I am looking to BUY a property in Ethiopia.\n- Preferred Neighborhood / City:\n- Target Budget (ETB):\n- Property Type (Villa / Condo / Apartment):\n- Contact Phone Number: ";
            input.focus();
        } else if (intent === 'list_property') {
            input.value = "Hello Admin Abdi, I would like to list my property for [Sale / Rent]. Here are my property details:\n- Purpose: (Sale / Rent)\n- Property Type: (Villa / Apartment / House / Condo)\n- Neighborhood: (e.g. Bole / CMC / Kazanchis)\n- Price (ETB):\n- Bedrooms / Baths:\n- Phone Number: ";
            input.focus();
        }
    }

    // Live Polling every 3.5 seconds
    pollTimer = setInterval(function() {
        if (currentConvId) {
            loadConversation(currentConvId);
        }
        if (isUserAdmin) {
            loadThreads();
        }
    }, 3500);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
