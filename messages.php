<?php
/**
 * صفحة الرسائل الخاصة
 * Musican
 */

define('MUSICAN_APP', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/templates_loader.php';
startSession();
requireLogin();

$currentUser = getCurrentUser();
$activePage  = 'messages';
$contactId   = (int)($_GET['id'] ?? 0);
$conversations = getConversations($currentUser['id']);
$chatHistory   = [];
$contactUser   = null;

if ($contactId) {
    if ($contactId === $currentUser['id']) {
        redirect(BASE_URL . '/messages.php');
    }
    $contactUser = getUser($contactId);
    if ($contactUser) {
        $chatHistory = getChatHistory($currentUser['id'], $contactId);
    }
}

// معالجة إرسال رسالة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    checkCsrf();
    $msgText = clean($_POST['message'] ?? '');
    if ($contactId && !empty($msgText)) {
        if (sendMessage($currentUser['id'], $contactId, $msgText)) {
            redirect(BASE_URL . '/messages.php?id=' . $contactId);
        }
    }
}

$pageTitle = 'الرسائل الخاصة';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top:30px; margin-bottom:50px;">
    <div class="chat-wrapper card" style="display:grid; grid-template-columns: 320px 1fr; height: 75vh; padding:0; overflow:hidden;">
        
        <!-- قائمة المحادثات -->
        <div class="chat-sidebar" style="border-left: 1px solid var(--border); background: var(--card-bg); overflow-y: auto;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border); font-weight: 700;">المحادثات</div>
            <div class="conv-list">
                <?php if (empty($conversations)): ?>
                    <div style="padding: 40px 20px; text-align:center; color: var(--text-muted);">لا توجد محادثات بعد</div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <a href="?id=<?= $conv['contact_id'] ?>" class="conv-item <?= ($contactId == $conv['contact_id']) ? 'active' : '' ?>" style="display:flex; gap:12px; padding:15px; text-decoration:none; border-bottom: 1px solid var(--border); transition: 0.2s;">
                            <img src="<?= $conv['avatar'] ? getImageUrl('avatars', $conv['avatar']) : 'https://ui-avatars.com/api/?name='.urlencode($conv['username']).'&background=d4af37&color=0d0d1a' ?>" alt="" style="width:45px; height:45px; border-radius:50%; object-fit:cover;">
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; justify-content:space-between; align-items:baseline;">
                                    <strong style="color:var(--text-dark); font-size:0.9rem;"><?= clean($conv['username']) ?></strong>
                                    <span style="font-size:0.7rem; color:var(--text-muted);"><?= timeAgo($conv['created_at']) ?></span>
                                </div>
                                <div style="font-size:0.8rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= ($conv['sender_id'] == $currentUser['id']) ? 'أنت: ' : '' ?><?= clean($conv['last_message']) ?>
                                </div>
                            </div>
                            <?php if (!$conv['is_read'] && $conv['sender_id'] != $currentUser['id']): ?>
                                <span style="width:10px; height:10px; background:var(--gold); border-radius:50%; align-self:center;"></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- منطقة الشات -->
        <div class="chat-main" style="display:flex; flex-direction:column; background: #fafafa;">
            <?php if ($contactUser): ?>
                <!-- الهيدر -->
                <div style="padding:15px 20px; background:white; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px;">
                    <img src="<?= $contactUser['avatar'] ? getImageUrl('avatars', $contactUser['avatar']) : 'https://ui-avatars.com/api/?name='.urlencode($contactUser['username']).'&background=d4af37&color=0d0d1a' ?>" alt="" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                    <div>
                        <div style="font-weight:700; color:var(--text-dark);"><?= clean($contactUser['username']) ?></div>
                        <div style="font-size:0.75rem; color:#27ae60;">متصل الآن (تجريبي)</div>
                    </div>
                </div>

                <!-- مساحة الرسائل -->
                <div id="chat-messages" style="flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:15px;">
                    <?php if (empty($chatHistory)): ?>
                        <div style="text-align:center; margin-top:50px; color:var(--text-muted);">ابدأ المحادثة مع <?= clean($contactUser['username']) ?></div>
                    <?php else: ?>
                        <?php foreach ($chatHistory as $msg): ?>
                            <?php $isMine = ($msg['sender_id'] == $currentUser['id']); ?>
                            <div style="align-self: <?= $isMine ? 'flex-end' : 'flex-start' ?>; max-width: 70%;">
                                <div style="padding:10px 15px; border-radius:<?= $isMine ? '15px 15px 0 15px' : '15px 15px 15px 0' ?>; background:<?= $isMine ? 'var(--gold)' : 'white' ?>; color:<?= $isMine ? 'white' : 'var(--text-dark)' ?>; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                                    <?= nl2br(clean($msg['message_text'])) ?>
                                </div>
                                <div style="font-size:0.65rem; color:var(--text-muted); text-align:<?= $isMine ? 'left' : 'right' ?>; margin-top:4px;">
                                    <?= date('H:i', strtotime($msg['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- مدخل الرسائل -->
                <div style="padding:15px 20px; background:white; border-top:1px solid var(--border);">
                    <form method="post" style="display:flex; gap:10px;">
                        <?= csrfField() ?>
                        <input type="text" name="message" class="form-control" placeholder="اكتب رسالتك..." required autocomplete="off" style="border-radius:25px; padding-right:20px;">
                        <button type="submit" name="send_message" class="btn btn-gold" style="border-radius:50%; width:45px; height:45px; padding:0; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--text-muted);">
                    <i class="fas fa-comments" style="font-size:4rem; margin-bottom:20px; opacity:0.2;"></i>
                    <p>اختر محادثة للبدء في المراسلة</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.conv-item:hover { background: rgba(212,175,55,0.05); }
.conv-item.active { background: rgba(212,175,55,0.1); border-right: 3px solid var(--gold); }
#chat-messages::-webkit-scrollbar { width: 5px; }
#chat-messages::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }
</style>

<script>
// النزول لآخر الشات تلقائياً
const chatMsgs = document.getElementById('chat-messages');
if(chatMsgs) {
    chatMsgs.scrollTop = chatMsgs.scrollHeight;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
