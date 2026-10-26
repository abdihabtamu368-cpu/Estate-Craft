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

            <!-- Hidden inputs for file/audio/image uploads -->
            <input type="file" id="chatImgInput" accept="image/*" style="display:none;" onchange="handleChatAttachmentSelected(this, 'image')">
            <input type="file" id="chatDocInput" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" style="display:none;" onchange="handleChatAttachmentSelected(this, 'file')">
            <input type="file" id="chatAudioInput" accept="audio/*" style="display:none;" onchange="handleChatAttachmentSelected(this, 'audio')">

            <!-- Attachment Preview Tray -->
            <div id="chatAttachmentTray" style="display: none; padding: 8px 18px; background: rgba(8, 102, 255, 0.05); border-top: 1px solid var(--border-color); align-items: center; justify-content: space-between; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                    <div id="chatAttachPreviewBox" style="width: 40px; height: 40px; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: var(--bg-card); border: 1px solid var(--border-color); flex-shrink: 0;">
                        <span id="chatAttachIcon" style="font-size: 20px;">📎</span>
                        <img id="chatAttachThumb" src="" alt="Preview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="min-width: 0;">
                        <div id="chatAttachName" style="font-size: 13px; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">file.png</div>
                        <div id="chatAttachSize" style="font-size: 11.5px; color: var(--text-muted);">120 KB</div>
                    </div>
                </div>
                <button type="button" onclick="clearChatAttachment()" style="background: none; border: none; font-size: 18px; color: var(--text-muted); cursor: pointer; padding: 4px; line-height: 1;" title="Remove attachment">&times;</button>
            </div>

            <!-- Voice Note Recording Bar -->
            <div id="chatVoiceRecordingBar" style="display: none; padding: 10px 18px; background: #fee2e2; border-top: 1px solid #fca5a5; align-items: center; justify-content: space-between; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="rec-pulse-dot"></span>
                    <strong style="color: #b91c1c; font-size: 13px;">Recording Voice Note...</strong>
                    <span id="chatRecordTimer" style="font-family: monospace; font-size: 13px; font-weight: 800; color: #991b1b; background: rgba(255,255,255,0.8); padding: 2px 8px; border-radius: 6px;">00:00</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" onclick="stopChatVoiceRecording(true)" class="btn btn-sm" style="background: #dc2626; color: #fff; font-size: 12px; font-weight: 700; border-radius: 16px; padding: 4px 12px; border: none; display: flex; align-items: center; gap: 4px;">
                        <span>⏹</span> Send Note
                    </button>
                    <button type="button" onclick="cancelChatVoiceRecording()" class="btn btn-secondary btn-sm" style="font-size: 12px; border-radius: 16px; padding: 4px 10px;">
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Message Input Form & Media Controls -->
            <div style="position: relative; background: var(--bg-card); border-top: 1px solid var(--border-color);">
                <!-- Emoji Picker Popover -->
                <div id="chatEmojiPicker" class="chat-emoji-picker-pop">
                    <div class="chat-emoji-tabs">
                        <button type="button" class="chat-emoji-tab-btn active" onclick="switchChatEmojiTab('real_estate', this)">🏡 Property</button>
                        <button type="button" class="chat-emoji-tab-btn" onclick="switchChatEmojiTab('smileys', this)">😊 Smileys</button>
                        <button type="button" class="chat-emoji-tab-btn" onclick="switchChatEmojiTab('business', this)">💼 Business</button>
                    </div>
                    <div id="chatEmojiGrid" class="chat-emoji-grid">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <form id="chatMessageForm" onsubmit="handleSendMessage(event)" style="padding: 12px 18px; display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="chat-attachment-btn" onclick="toggleChatEmojiPicker()" title="Insert Emoji">😊</button>
                    <button type="button" class="chat-attachment-btn" onclick="document.getElementById('chatImgInput').click()" title="Send Photo/Image">📷</button>
                    <button type="button" class="chat-attachment-btn" onclick="document.getElementById('chatDocInput').click()" title="Attach Document (PDF, Word, etc)">📎</button>
                    <button type="button" class="chat-attachment-btn" id="chatMicBtn" onclick="toggleChatVoiceRecording()" title="Record Voice Note">🎙️</button>
                    
                    <div style="flex: 1; position: relative;">
                        <input type="text" id="chatInputMessage" class="form-input" placeholder="Type your message to <?= $isAdmin ? 'the user' : 'Admin Abdi Habtamu' ?>..." style="height: 42px; border-radius: 21px; padding: 0 16px; font-size: 14px; width: 100%;" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary" id="btnSendChat" style="border-radius: 50%; width: 42px; height: 42px; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #0866ff; box-shadow: 0 4px 10px rgba(8, 102, 255, 0.3);" title="Send">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<!-- Universal Chat Image Lightbox Modal -->
<div id="chatImageLightbox" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.88); z-index: 10000; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(6px);" onclick="closeChatLightbox(event)">
    <div style="position: relative; max-width: 90vw; max-height: 90vh; display: flex; flex-direction: column; align-items: center;" onclick="event.stopPropagation()">
        <button type="button" onclick="closeChatLightbox()" style="position: absolute; top: -44px; right: 0; background: rgba(255,255,255,0.25); color: #fff; border: none; font-size: 26px; line-height: 1; cursor: pointer; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background 0.15s ease;" onmouseover="this.style.background='rgba(255,255,255,0.45)'" onmouseout="this.style.background='rgba(255,255,255,0.25)'">&times;</button>
        <img id="chatLightboxImg" src="" alt="Enlarged Attachment" style="max-width: 100%; max-height: 82vh; border-radius: 12px; object-fit: contain; box-shadow: 0 15px 45px rgba(0,0,0,0.6);">
        <div id="chatLightboxCaption" style="color: #ffffff; margin-top: 12px; font-size: 13px; font-weight: 600; text-align: center; text-shadow: 0 2px 4px rgba(0,0,0,0.8);"></div>
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
/* Chat Media & Attachment Controls */
.chat-attachment-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid var(--border-color);
    background: var(--bg-main);
    color: var(--text-main);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.18s ease;
    flex-shrink: 0;
    padding: 0;
}
.chat-attachment-btn:hover {
    background: rgba(8, 102, 255, 0.1);
    color: #0866ff;
    border-color: #0866ff;
    transform: scale(1.08);
}
.chat-emoji-picker-pop {
    position: absolute;
    bottom: 100%;
    left: 14px;
    margin-bottom: 8px;
    width: 310px;
    max-width: calc(100vw - 32px);
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    box-shadow: 0 12px 36px rgba(0,0,0,0.22);
    z-index: 100;
    overflow: hidden;
    display: none;
    flex-direction: column;
}
.chat-emoji-tabs {
    display: flex;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-main);
    padding: 4px;
    gap: 4px;
}
.chat-emoji-tab-btn {
    flex: 1;
    font-size: 11.5px;
    font-weight: 700;
    padding: 6px 4px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    border-radius: 8px;
    cursor: pointer;
    text-align: center;
    transition: all 0.15s ease;
}
.chat-emoji-tab-btn.active, .chat-emoji-tab-btn:hover {
    background: var(--bg-card);
    color: #0866ff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.chat-emoji-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 4px;
    padding: 10px;
    max-height: 180px;
    overflow-y: auto;
}
.chat-emoji-item {
    font-size: 20px;
    padding: 6px;
    border-radius: 8px;
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.12s ease, background 0.12s ease;
}
.chat-emoji-item:hover {
    background: rgba(8, 102, 255, 0.12);
    transform: scale(1.2);
}
@keyframes recordingPulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(1.25); }
    100% { opacity: 1; transform: scale(1); }
}
.rec-pulse-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ef4444;
    animation: recordingPulse 1.2s infinite ease-in-out;
}
</style>

<script>
let currentConvId = <?= json_encode($selectedConvId) ?>;
let activePropertyId = <?= json_encode($propertyId) ?>;
const isUserAdmin = <?= json_encode($isAdmin) ?>;
let pollTimer = null;

// =============================================================================
// Universal Cross-Device Cloud Sync Engine (ntfy.sh Real-Time Relay)
// =============================================================================
const CLOUD_CHAT_TOPIC = 'estatecraft_ethiopia_live_hub_v3';
let cloudChatEventSource = null;
const cloudSyncedMsgIds = new Set();

function initCloudChatSync() {
    if (!cloudChatEventSource && 'EventSource' in window) {
        try {
            cloudChatEventSource = new EventSource(`https://ntfy.sh/${CLOUD_CHAT_TOPIC}/sse`);
            cloudChatEventSource.onmessage = function(event) {
                if (!event || !event.data) return;
                try {
                    const item = JSON.parse(event.data);
                    if (item && item.event === 'message') {
                        processIncomingCloudMessage(item);
                    }
                } catch (e) {}
            };
            cloudChatEventSource.onerror = function() {
                setTimeout(pollCloudChatHistory, 2000);
            };
        } catch (e) {}
    }

    pollCloudChatHistory();

    if (!window.cloudChatPollInterval) {
        window.cloudChatPollInterval = setInterval(pollCloudChatHistory, 3500);
    }
}

async function pollCloudChatHistory() {
    try {
        const res = await fetch(`https://ntfy.sh/${CLOUD_CHAT_TOPIC}/json?poll=1&since=all`);
        if (res.ok) {
            const text = await res.text();
            const lines = text.trim().split('\n');
            for (const line of lines) {
                if (!line.trim()) continue;
                try {
                    const item = JSON.parse(line);
                    if (item && item.event === 'message') {
                        processIncomingCloudMessage(item);
                    }
                } catch (e) {}
            }
        }
    } catch (e) {}
}

function playChatChime() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.14);
        gain.gain.setValueAtTime(0.08, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.28);
        osc.start();
        osc.stop(ctx.currentTime + 0.28);
    } catch (e) {}
}

function processIncomingCloudMessage(item) {
    if (!item || !item.id) return;
    if (cloudSyncedMsgIds.has(item.id)) return;
    cloudSyncedMsgIds.add(item.id);

    let packet = null;
    if (item.attachment && item.attachment.url) {
        if (item.title) {
            try {
                packet = JSON.parse(decodeURIComponent(item.title));
            } catch (e) {
                try { packet = JSON.parse(item.title); } catch (e2) {}
            }
        }
    } else if (item.message) {
        try {
            packet = typeof item.message === 'string' ? JSON.parse(item.message) : item.message;
        } catch (e) {}
    }

    if (!packet || !packet.convKey || !packet.msg) return;

    const myEmail = <?= json_encode(strtolower(trim($currentUser['email']))) ?>;
    const isMeAdmin = isUserAdmin;

    const matchesEmail = (packet.userEmail && packet.userEmail.toLowerCase() === myEmail);
    const matchesConv = (packet.convKey.includes(myEmail.replace(/[^a-zA-Z0-9_]/g, '_')));

    if (!isMeAdmin && !matchesEmail && !matchesConv) {
        return;
    }

    // Refresh active messages and thread list
    playChatChime();
    if (currentConvId) {
        loadConversation(currentConvId);
    }
    if (isUserAdmin) {
        loadThreads();
    }
}

async function broadcastMessageToCloud(packet, pendingAttachment = null) {
    try {
        if (packet.msg && packet.msg.id) {
            cloudSyncedMsgIds.add(String(packet.msg.id));
        }

        if (pendingAttachment && (pendingAttachment.file || pendingAttachment.blob)) {
            const blob = pendingAttachment.file || pendingAttachment.blob;
            const fileName = pendingAttachment.name || (pendingAttachment.type === 'image' ? 'photo.jpg' : (pendingAttachment.type === 'audio' ? 'voice.webm' : 'document.pdf'));

            const meta = {
                convKey: packet.convKey,
                userName: packet.userName,
                userEmail: packet.userEmail,
                userPhone: packet.userPhone,
                propertyTitle: packet.propertyTitle,
                propertyPrice: packet.propertyPrice,
                attType: pendingAttachment.type,
                attName: fileName,
                attSize: pendingAttachment.size || '',
                msg: packet.msg
            };

            await fetch(`https://ntfy.sh/${CLOUD_CHAT_TOPIC}`, {
                method: 'PUT',
                headers: {
                    'Filename': fileName,
                    'Title': encodeURIComponent(JSON.stringify(meta))
                },
                body: blob
            });
        } else {
            await fetch('https://ntfy.sh', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    topic: CLOUD_CHAT_TOPIC,
                    message: JSON.stringify(packet)
                })
            });
        }
    } catch (err) {
        console.warn("Cloud relay notice:", err);
    }
}

// =============================================================================
// Emoji Picker, Attachments (File, Image, Sound) & Voice Note Recorder
// =============================================================================
window.pendingPageAttachment = null;
let pageMediaRecorder = null;
let pageAudioChunks = [];
let pageRecordingTimer = null;
let pageRecordingSeconds = 0;

const CHAT_PAGE_EMOJIS = {
    real_estate: ['🏡', '🏢', '🏬', '🏠', '🔑', '🚪', '📍', '🛋️', '🚿', '🏗️', '📐', '🌲', '🏊', '🚗', '💰', '🛡️', '🌇', '🛖'],
    smileys: ['😀', '😊', '😂', '😍', '😎', '👍', '👏', '🙏', '👋', '🤝', '❤️', '🔥', '✨', '🎉', '👌', '🙌', '🤩', '💯'],
    business: ['💼', '📄', '📅', '📞', '✉️', '💵', '💳', '🕒', 'ℹ️', '⭐', '✅', '🎯', '📌', '💡', '🏷️', '📊', '🤝', '⚖️']
};

function toggleChatEmojiPicker() {
    const picker = document.getElementById('chatEmojiPicker');
    if (!picker) return;
    if (picker.style.display === 'flex') {
        picker.style.display = 'none';
    } else {
        picker.style.display = 'flex';
        renderChatEmojiGrid('real_estate');
    }
}

function switchChatEmojiTab(category, btn) {
    document.querySelectorAll('.chat-emoji-tab-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    renderChatEmojiGrid(category);
}

function renderChatEmojiGrid(category) {
    const grid = document.getElementById('chatEmojiGrid');
    if (!grid) return;
    const list = CHAT_PAGE_EMOJIS[category] || CHAT_PAGE_EMOJIS.real_estate;
    grid.innerHTML = list.map(em => `
        <button type="button" class="chat-emoji-item" onclick="insertChatEmoji('${em}')">${em}</button>
    `).join('');
}

function insertChatEmoji(emoji) {
    const input = document.getElementById('chatInputMessage');
    if (input) {
        const start = input.selectionStart || input.value.length;
        const end = input.selectionEnd || input.value.length;
        const val = input.value;
        input.value = val.substring(0, start) + emoji + val.substring(end);
        input.selectionStart = input.selectionEnd = start + emoji.length;
        input.focus();
    }
}

// Close emoji picker on outside click
document.addEventListener('click', function(e) {
    const picker = document.getElementById('chatEmojiPicker');
    if (!picker || picker.style.display !== 'flex') return;
    const btn = e.target.closest('.chat-attachment-btn');
    if (!picker.contains(e.target) && (!btn || !btn.getAttribute('onclick') || !btn.getAttribute('onclick').includes('Emoji'))) {
        picker.style.display = 'none';
    }
});

// File / Image / Audio selection handler
function handleChatAttachmentSelected(input, type) {
    if (!input || !input.files || input.files.length === 0) return;
    const file = input.files[0];

    let sizeStr = '';
    if (file.size >= 1048576) {
        sizeStr = (file.size / 1048576).toFixed(1) + ' MB';
    } else {
        sizeStr = Math.max(1, Math.round(file.size / 1024)) + ' KB';
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const dataUrl = e.target.result;
        window.pendingPageAttachment = {
            type: type,
            url: dataUrl,
            name: file.name,
            size: sizeStr,
            file: file
        };

        const tray = document.getElementById('chatAttachmentTray');
        const nameEl = document.getElementById('chatAttachName');
        const sizeEl = document.getElementById('chatAttachSize');
        const thumb = document.getElementById('chatAttachThumb');
        const icon = document.getElementById('chatAttachIcon');

        if (nameEl) nameEl.textContent = file.name;
        if (sizeEl) sizeEl.textContent = sizeStr;

        if (type === 'image' && thumb && icon) {
            thumb.src = dataUrl;
            thumb.style.display = 'block';
            icon.style.display = 'none';
        } else if (icon && thumb) {
            thumb.style.display = 'none';
            icon.style.display = 'block';
            icon.textContent = (type === 'audio' ? '🎙️' : '📄');
        }

        if (tray) tray.style.display = 'flex';
        input.value = '';
    };
    reader.readAsDataURL(file);
}

function clearChatAttachment() {
    window.pendingPageAttachment = null;
    const tray = document.getElementById('chatAttachmentTray');
    if (tray) tray.style.display = 'none';
    const imgIn = document.getElementById('chatImgInput');
    const docIn = document.getElementById('chatDocInput');
    const audIn = document.getElementById('chatAudioInput');
    if (imgIn) imgIn.value = '';
    if (docIn) docIn.value = '';
    if (audIn) audIn.value = '';
}

// Voice Note Recording via Microphone
async function toggleChatVoiceRecording() {
    if (pageMediaRecorder && pageMediaRecorder.state === 'recording') {
        stopChatVoiceRecording(true);
    } else {
        startChatVoiceRecording();
    }
}

async function startChatVoiceRecording() {
    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert("Microphone recording is not supported in this browser. You can select an audio file instead.");
            const audIn = document.getElementById('chatAudioInput');
            if (audIn) audIn.click();
            return;
        }

        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        pageMediaRecorder = new MediaRecorder(stream);
        pageAudioChunks = [];

        pageMediaRecorder.ondataavailable = e => {
            if (e.data && e.data.size > 0) pageAudioChunks.push(e.data);
        };

        pageMediaRecorder.onstop = () => {
            stream.getTracks().forEach(t => t.stop());
        };

        pageMediaRecorder.start();
        pageRecordingSeconds = 0;

        const bar = document.getElementById('chatVoiceRecordingBar');
        const timer = document.getElementById('chatRecordTimer');
        if (bar) bar.style.display = 'flex';
        if (timer) timer.textContent = '00:00';

        if (pageRecordingTimer) clearInterval(pageRecordingTimer);
        pageRecordingTimer = setInterval(() => {
            pageRecordingSeconds++;
            const mins = String(Math.floor(pageRecordingSeconds / 60)).padStart(2, '0');
            const secs = String(pageRecordingSeconds % 60).padStart(2, '0');
            if (timer) timer.textContent = `${mins}:${secs}`;
        }, 1000);

    } catch (err) {
        console.error("Mic access error:", err);
        alert("Microphone access was denied or is unavailable. You can upload an audio file directly.");
        const audIn = document.getElementById('chatAudioInput');
        if (audIn) audIn.click();
    }
}

function stopChatVoiceRecording(sendImmediately = true) {
    if (!pageMediaRecorder || pageMediaRecorder.state === 'inactive') return;
    if (pageRecordingTimer) clearInterval(pageRecordingTimer);

    const durationSec = pageRecordingSeconds;
    const mins = String(Math.floor(durationSec / 60)).padStart(2, '0');
    const secs = String(durationSec % 60).padStart(2, '0');
    const durationStr = `${mins}:${secs}`;

    pageMediaRecorder.onstop = () => {
        const audioBlob = new Blob(pageAudioChunks, { type: 'audio/webm;codecs=opus' });
        const reader = new FileReader();
        reader.onloadend = () => {
            const dataUrl = reader.result;
            window.pendingPageAttachment = {
                type: 'audio',
                url: dataUrl,
                name: `Voice_Note_${mins}m${secs}s.webm`,
                size: `Voice Note (${durationStr})`,
                blob: audioBlob
            };

            const bar = document.getElementById('chatVoiceRecordingBar');
            if (bar) bar.style.display = 'none';

            if (sendImmediately) {
                handleSendMessage(new Event('submit'));
            } else {
                const tray = document.getElementById('chatAttachmentTray');
                const nameEl = document.getElementById('chatAttachName');
                const sizeEl = document.getElementById('chatAttachSize');
                const thumb = document.getElementById('chatAttachThumb');
                const icon = document.getElementById('chatAttachIcon');
                if (nameEl) nameEl.textContent = window.pendingPageAttachment.name;
                if (sizeEl) sizeEl.textContent = 'Voice Note (' + durationStr + ')';
                if (thumb) thumb.style.display = 'none';
                if (icon) { icon.style.display = 'block'; icon.textContent = '🎙️'; }
                if (tray) tray.style.display = 'flex';
            }
        };
        reader.readAsDataURL(audioBlob);
    };

    pageMediaRecorder.stop();
}

function cancelChatVoiceRecording() {
    if (pageMediaRecorder && pageMediaRecorder.state !== 'inactive') {
        pageMediaRecorder.onstop = () => {};
        pageMediaRecorder.stop();
    }
    if (pageRecordingTimer) clearInterval(pageRecordingTimer);
    const bar = document.getElementById('chatVoiceRecordingBar');
    if (bar) bar.style.display = 'none';
}

// Lightbox Modal for Full-size Image Viewing
function openChatLightbox(src, caption) {
    const box = document.getElementById('chatImageLightbox');
    const img = document.getElementById('chatLightboxImg');
    const cap = document.getElementById('chatLightboxCaption');
    if (box && img) {
        img.src = src;
        if (cap) cap.textContent = caption || '';
        box.style.display = 'flex';
    }
}

function closeChatLightbox(e) {
    if (e && e.target && e.target.id === 'chatLightboxImg') return;
    const box = document.getElementById('chatImageLightbox');
    if (box) box.style.display = 'none';
}

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
                window.activeChatUserEmail = data.user.email;
                window.activeChatUserName = data.user.full_name;
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

                // Attachment Rendering (Image, Audio Voice Note, Document)
                let attachmentHtml = '';
                const attType = m.attachment_type;
                const attUrl = m.attachment_url;
                const attName = m.attachment_name;
                const attSize = m.attachment_size;

                if (attUrl && attType && attType !== 'none') {
                    if (attType === 'image') {
                        attachmentHtml = `
                            <div style="margin-top: 6px; cursor: pointer;" onclick="openChatLightbox('${attUrl}', '${escapeHtml(attName || 'Photo')}')">
                                <img src="${attUrl}" alt="Attachment" style="max-width: 240px; max-height: 190px; border-radius: 10px; object-fit: cover; display: block; border: 1px solid rgba(0,0,0,0.12); box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div style="font-size: 10px; opacity: 0.8; margin-top: 3px;">🔍 Click to enlarge (${escapeHtml(attSize || 'Photo')})</div>
                            </div>
                        `;
                    } else if (attType === 'audio') {
                        attachmentHtml = `
                            <div style="margin-top: 6px; min-width: 220px; max-width: 280px;">
                                <div style="font-size: 11px; font-weight: 700; margin-bottom: 3px; display: flex; align-items: center; gap: 4px;">
                                    <span>🎙️</span> Voice Note ${attSize ? `(${escapeHtml(attSize)})` : ''}
                                </div>
                                <audio controls src="${attUrl}" style="width: 100%; height: 34px; border-radius: 17px; outline: none;"></audio>
                            </div>
                        `;
                    } else if (attType === 'file') {
                        attachmentHtml = `
                            <a href="${attUrl}" download="${escapeHtml(attName || 'document')}" target="_blank" style="display: flex; align-items: center; gap: 8px; margin-top: 6px; padding: 8px 11px; background: ${isSentByMe ? 'rgba(255,255,255,0.18)' : 'rgba(0,0,0,0.05)'}; border-radius: 8px; text-decoration: none; color: inherit; border: 1px solid ${isSentByMe ? 'rgba(255,255,255,0.3)' : 'var(--border-color)'};">
                                <span style="font-size: 22px;">📄</span>
                                <div style="min-width: 0; flex: 1;">
                                    <div style="font-size: 12.5px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        ${escapeHtml(attName || 'Document')}
                                    </div>
                                    <div style="font-size: 10.5px; opacity: 0.8;">
                                        ${escapeHtml(attSize || 'Attachment')} &bull; Download ⬇
                                    </div>
                                </div>
                            </a>
                        `;
                    }
                }

                return `
                    <div class="chat-msg-row ${isSentByMe ? 'sent' : 'received'}">
                        <div class="chat-bubble">
                            <div style="font-weight: 700; font-size: 11px; margin-bottom: 2px; opacity: 0.9;">
                                ${escapeHtml(m.sender_name)} ${roleBadge}
                            </div>
                            <div>${escapeHtml(m.message)}</div>
                            ${attachmentHtml}
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
    const pendingAtt = window.pendingPageAttachment;
    if (!msg && !pendingAtt) return;

    input.value = '';
    clearChatAttachment();

    let sendMsg = msg;
    if (!sendMsg && pendingAtt) {
        if (pendingAtt.type === 'image') sendMsg = '📷 Photo';
        else if (pendingAtt.type === 'audio') sendMsg = '🎙️ Voice Note';
        else if (pendingAtt.type === 'file') sendMsg = '📄 ' + (pendingAtt.name || 'Document');
    }

    const btn = document.getElementById('btnSendChat');
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('conversation_id', currentConvId || 0);
    formData.append('message', sendMsg);
    if (activePropertyId > 0) formData.append('property_id', activePropertyId);

    if (pendingAtt) {
        formData.append('attachment_type', pendingAtt.type);
        formData.append('attachment_name', pendingAtt.name || '');
        formData.append('attachment_size', pendingAtt.size || '');
        if (pendingAtt.file) {
            formData.append('attachment_file', pendingAtt.file);
        } else if (pendingAtt.blob) {
            formData.append('attachment_file', pendingAtt.blob, pendingAtt.name || 'voice.webm');
        } else if (pendingAtt.url) {
            formData.append('attachment_data', pendingAtt.url);
        }
    }

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

            // Broadcast to cloud relay for cross-device sync
            const myEmail = <?= json_encode(strtolower(trim($currentUser['email']))) ?>;
            const clientEmail = isUserAdmin ? (window.activeChatUserEmail || 'client@estatecraft.et') : myEmail;
            const clientName = isUserAdmin ? (window.activeChatUserName || 'Client') : <?= json_encode($currentUser['full_name']) ?>;
            const cloudPacket = {
                convKey: 'user_' + clientEmail.replace(/[^a-zA-Z0-9_]/g, '_'),
                userName: clientName,
                userEmail: clientEmail,
                userPhone: isUserAdmin ? '' : <?= json_encode($currentUser['phone'] ?? '') ?>,
                propertyTitle: (data.property && data.property.title) || null,
                propertyPrice: (data.property && ('ETB ' + Number(data.property.price).toLocaleString())) || null,
                msg: {
                    id: Date.now(),
                    sender: isUserAdmin ? 'admin' : 'user',
                    name: <?= json_encode($currentUser['full_name']) ?>,
                    role: isUserAdmin ? 'admin' : 'user',
                    message: sendMsg,
                    time: new Date().toISOString()
                }
            };
            broadcastMessageToCloud(cloudPacket, pendingAtt);
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
    initCloudChatSync();
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
