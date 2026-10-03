<?php
/**
 * Member Profile / Digital ID Card View
 */

$page_title = 'My Digital ID Card';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Member.php';
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;

// Ensure user is logged in
requireLogin();

$member_id = ((isAdmin() || isStaff()) && !empty($_GET['id'])) ? (int)$_GET['id'] : ($_SESSION['member_id'] ?? null);
$memberModel = new Member($pdo);

// Fallback: some existing user accounts may not have member_id in session.
// Resolve from the logged-in user record first, then by email as a fallback.
if (!$member_id && !empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT member_id, email FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $userRow = $stmt->fetch();

    if (!empty($userRow['member_id'])) {
        $member_id = $userRow['member_id'];
        $_SESSION['member_id'] = $member_id;
    } else {
        $sessionEmail = $_SESSION['email'] ?? ($userRow['email'] ?? null);
        $sessionName = $_SESSION['name'] ?? null;
        if ($sessionEmail) {
            $_SESSION['email'] = $sessionEmail;
            $resolvedMember = $memberModel->findByEmail($sessionEmail);
            if ($resolvedMember && !empty($resolvedMember['member_id'])) {
                $member_id = $resolvedMember['member_id'];
                $_SESSION['member_id'] = $member_id;
            }
        }

        // Additional compatibility fallback: match by full name
        if (!$member_id && $sessionName) {
            $stmt = $pdo->prepare("SELECT member_id FROM members WHERE full_name = ? LIMIT 1");
            $stmt->execute([$sessionName]);
            $nameMatchedMemberId = $stmt->fetchColumn();
            if ($nameMatchedMemberId) {
                $member_id = (int)$nameMatchedMemberId;
                $_SESSION['member_id'] = $member_id;
            }
        }

        // Last resort for member-side access:
        // create a minimal member record and link it to the current user.
        if (
            !$member_id &&
            !isAdmin() &&
            !isStaff() &&
            (!empty($sessionName) || !empty($sessionEmail))
        ) {
            $newMemberId = $memberModel->create([
                'full_name' => $sessionName ?: ($sessionEmail ?: 'Member'),
                'email' => $sessionEmail ?: null,
                'status' => 'active',
            ]);
            if ($newMemberId) {
                $stmt = $pdo->prepare("UPDATE users SET member_id = ? WHERE user_id = ?");
                $stmt->execute([$newMemberId, $_SESSION['user_id']]);
                $member_id = (int)$newMemberId;
                $_SESSION['member_id'] = $member_id;
            }
        }
    }
}

if (!$member_id) {
    if (isAdmin() || isStaff()) {
        // If Admin/Staff, they might be viewing a specific member
        $member_id = $_GET['id'] ?? null;
    }
}

if (!$member_id) {
    header("Location: dashboard.php");
    exit;
}

$member = $memberModel->find($member_id);

if (!$member) {
    die("Member not found.");
}

// Generate QR SVG inline — avoids a separate HTTP sub-request and any
// cross-PHP-environment issues with the generate_qr.php endpoint.
$qrSvgContent = '';
$qrImageUrl   = '';
$qrError      = '';

if (!empty($member['qr_token'])) {
    if (class_exists('Endroid\QrCode\QrCode')) {
        try {
            $qrCode = new QrCode($member['qr_token']);
            $qrCode->setSize(200);
            $qrCode->setMargin(8);
            $qrCode->setWriterByName('svg');
            $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM());
            $qrSvgContent = $qrCode->writeString();
        } catch (\Throwable $e) {
            $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($member['qr_token']);
        }
    } else {
        // Fallback for environments where vendor/ directory was omitted
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($member['qr_token']);
    }
} else {
    $qrError = 'No QR token assigned. Please contact an administrator.';
}

// Check permissions
if (!isAdmin() && !isStaff() && (int)($_SESSION['member_id'] ?? 0) !== (int)$member_id) {
    die("Unauthorized Access: You can only view your own ID card.");
}

// Include Layout Header
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="page-title mb-0" style="font-size: 1.4rem; font-weight: 800;">Digital Member ID</h1>
            <p class="text-muted small mb-0">Official membership identification &amp; access pass</p>
        </div>
        <div class="d-flex gap-2 no-print">
            <?php if (!empty($member['phone'])): 
                $clean_phone = preg_replace('/[^0-9+]/', '', $member['phone']);
                $body = urlencode("Hello " . $member['full_name'] . ", peace be with you from God's Family UMC.");
            ?>
                <a href="sms:<?php echo $clean_phone; ?>?&body=<?php echo $body; ?>" class="btn btn-outline-success font-weight-600 d-inline-flex align-items-center gap-2">
                    <i class='bx bx-message-rounded-dots'></i> Send SMS
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-primary font-weight-600 d-inline-flex align-items-center gap-2">
                <i class='bx bx-printer'></i> Print ID Card
            </button>
        </div>
    </div>
</div>

<div class="mb-3 text-center no-print">
    <span class="badge bg-light text-dark border px-3 py-2" style="font-size: 0.85rem; font-weight: 600;">
        <i class='bx bx-credit-card-front'></i> Standard CR80 ID Card &bull; 3.375" &times; 2.125" &bull; Double-Sided (Back to Back)
    </span>
</div>

<div id="printable-id-card" class="d-flex justify-content-center align-items-stretch gap-4 flex-wrap my-4">
    <!-- FRONT CARD -->
    <div class="id-card-wrapper text-center">
        <div class="badge bg-primary text-white mb-2 no-print" style="font-size: 11px; letter-spacing: 1px; font-weight: 700;">FRONT SIDE</div>
        <div class="id-card-modern shadow-lg">
            <div class="id-card-top-header d-flex align-items-center justify-content-center gap-3">
                <img src="assets/images/logo.png" alt="Logo" style="width: 44px; height: 44px; border-radius: 50%; border: 2px solid var(--cms-gold, #f59e0b); object-fit: cover;">
                <div class="id-church-titles">
                    <div class="id-church-name">GOD'S FAMILY</div>
                    <div class="id-church-sub">United Methodist Church</div>
                </div>
            </div>
            
            <div class="id-card-content">
                <div class="id-avatar-container">
                    <?php if (!empty($member['photo_path']) && file_exists(__DIR__ . '/' . $member['photo_path'])): ?>
                        <img src="<?php echo htmlspecialchars($member['photo_path']); ?>" alt="Profile">
                    <?php else: ?>
                        <div class="id-avatar-placeholder">
                            <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h2 class="id-member-name"><?php echo htmlspecialchars($member['full_name']); ?></h2>
                <div class="id-member-role"><?php echo htmlspecialchars($member['role_name'] ?? 'MEMBER'); ?></div>
                <div class="small text-muted mb-2 font-weight-700" style="letter-spacing: 1px;">ID #<?php echo str_pad($member['member_id'], 4, '0', STR_PAD_LEFT); ?></div>
                
                <?php if ($member['phone']): ?>
                <div class="id-stats-grid mb-3">
                    <div class="id-stat-item" style="grid-column: span 2;">
                        <div class="id-stat-label">Contact</div>
                        <div class="id-stat-value d-flex align-items-center justify-content-center gap-2">
                            <span><?php echo htmlspecialchars($member['phone']); ?></span>
                            <?php 
                                $clean_phone = preg_replace('/[^0-9+]/', '', $member['phone']);
                                $body = urlencode("Hello " . $member['full_name'] . ", peace be with you from God's Family UMC.");
                            ?>
                            <a href="sms:<?php echo $clean_phone; ?>?&body=<?php echo $body; ?>" class="no-print btn btn-xs btn-outline-success" style="font-size: 10px; padding: 1px 6px; border-radius: 4px; text-decoration: none;" title="Send SMS">
                                <i class='bx bx-message-rounded-dots'></i> SMS
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="id-qr-box">
                    <?php if ($qrSvgContent): ?>
                        <?php echo $qrSvgContent; ?>
                    <?php elseif ($qrImageUrl): ?>
                        <img src="<?php echo htmlspecialchars($qrImageUrl); ?>" alt="QR Code" style="max-width: 140px; width: 100%; height: auto; object-fit: contain;">
                    <?php else: ?>
                        <p style="color:#c53030;font-size:10px;text-align:center;"><?php echo htmlspecialchars($qrError); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="id-card-footer-modern">
                <div style="font-weight: 700; color: #1e293b;">Official Member Access Pass</div> 
                <div style="margin-top: 4px; opacity: 0.8; font-size: 10px;">&ldquo;Therefore go, grow in love, mission and service.&rdquo;</div>
            </div>
        </div>
    </div>

    <!-- BACK CARD -->
    <div class="id-card-wrapper text-center">
        <div class="badge bg-dark text-white mb-2 no-print" style="font-size: 11px; letter-spacing: 1px; font-weight: 700;">BACK SIDE</div>
        <div class="id-card-back shadow-lg">
            <div class="id-card-back-bg"></div>
            <div class="id-card-back-inner">
                <div class="id-card-back-header">
                    <img src="assets/images/logo.png" alt="Church Logo" class="id-card-back-logo">
                    <div class="id-card-back-church">GOD'S FAMILY</div>
                    <div class="id-card-back-sub">United Methodist Church</div>
                    <div class="id-card-back-address">South Nueva Ecija Philippine Annual Conference<br>115 Rizal St, Pob 3, Peñaranda, Philippines, 3103</div>
                </div>

                <div class="id-card-back-body">
                    <div class="id-card-back-verse">
                        &ldquo;Therefore go, grow in love, mission and service.&rdquo;
                    </div>
                    
                    <div class="id-card-back-notice">
                        <strong style="color: #f59e0b; display: block; margin-bottom: 4px; font-size: 10px;">CHURCH MEMBERSHIP PASS</strong>
                        This card certifies that the cardholder is a registered member of God's Family United Methodist Church. 
                        This pass is non-transferable and must be presented upon request during church events and services.
                        <div style="margin-top: 6px; font-size: 9px; opacity: 0.9;">
                            <strong>If found, please return to:</strong><br>
                            Church Administration Office &bull; (+63) 917 123 4567
                        </div>
                    </div>
                </div>

                <div class="id-card-back-signature">
                    <div class="id-card-back-sig-line" style="margin-right: 12px;">
                        <div class="sig-rule"></div>
                        <div>Cardholder Signature</div>
                    </div>
                    <div class="id-card-back-sig-line">
                        <div class="sig-rule"></div>
                        <div>Authorized Church Signatory</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php 
// Include Layout Footer
include __DIR__ . '/layout/footer.php';
?>
