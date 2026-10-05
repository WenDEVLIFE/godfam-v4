<?php
/**
 * Full Digital Member ID Card Generator
 * Renders printable and downloadable whole digital ID cards featuring member details,
 * photo, QR token, and church branding.
 */

$page_title = 'Digital Member ID Generator';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CSRF.php';
require_once __DIR__ . '/../models/Member.php';
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;

// Require login
requireLogin();

$memberModel = new Member($pdo);
$member_id   = $_GET['id'] ?? ($_SESSION['member_id'] ?? null);
$message     = '';
$error       = '';

// Handle QR Token Regeneration (Admin/Staff only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'regenerate_token') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF Token Validation Failed.";
    } else if (!isAdmin() && !isStaff()) {
        $error = "Unauthorized to regenerate QR tokens.";
    } else {
        $newToken = $memberModel->regenerateToken($member_id);
        if ($newToken) {
            $message = "QR Token successfully regenerated for this member.";
        } else {
            $error = "Failed to regenerate QR token.";
        }
    }
}

// Fallback: If no member_id, resolve from logged-in user
if (!$member_id && !empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT member_id FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $member_id = $stmt->fetchColumn();
}

if (!$member_id) {
    header("Location: dashboard.php");
    exit;
}

$member = $memberModel->find($member_id);
if (!$member) {
    die("Member record not found.");
}

// Authorization check: Admin/Staff can view any; Members can only view their own
if (!isAdmin() && !isStaff() && (int)($_SESSION['member_id'] ?? 0) !== (int)$member_id) {
    die("Unauthorized Access: You can only view your own Digital ID Card.");
}

// Generate QR Code SVG or Fallback URL
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
            $rawSvg = $qrCode->writeString();
            $qrSvgContent = preg_replace('/^<\?xml[^>]*\?>\s*/i', '', $rawSvg);
        } catch (\Throwable $e) {
            $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($member['qr_token']);
        }
    } else {
        // Fallback for environments where vendor/ directory was omitted
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($member['qr_token']);
    }
} else {
    $qrError = 'No QR token assigned. Please regenerate token.';
}

$csrf_token = generateCsrfToken();

include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="flat-dashboard mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title" style="margin:0;">Digital Member ID Card</h1>
            <p class="text-muted small">Official Membership Identification &amp; QR Access Pass</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button onclick="window.print()" class="btn btn-primary font-weight-600 no-print">
                <i class='bx bx-printer'></i> Print ID Card
            </button>
            <?php if (isAdmin() || isStaff()): ?>
                <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to regenerate this QR token? The old QR code will become invalid.');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="regenerate_token">
                    <button type="submit" class="btn btn-outline-secondary font-weight-600 no-print">
                        <i class='bx bx-refresh'></i> Regenerate QR
                    </button>
                </form>
            <?php endif; ?>
            <a href="<?php echo (isAdmin() || isStaff()) ? 'members.php' : 'dashboard.php'; ?>" class="btn btn-outline-primary font-weight-600 no-print">
                <i class='bx bx-left-arrow-alt'></i> Back
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success mb-4 no-print"><i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger mb-4 no-print"><i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="mb-3 text-center no-print">
        <span class="badge bg-light text-dark border px-3 py-2" style="font-size: 0.85rem; font-weight: 600;">
            <i class='bx bx-credit-card-front'></i> Standard CR80 ID Card &bull; 3.375" &times; 2.125" &bull; Double-Sided (Back to Back)
        </span>
    </div>

    <!-- ID Card Container -->
    <div id="printable-id-card" class="d-flex justify-content-center align-items-stretch gap-4 flex-wrap my-4">
        <!-- FRONT CARD -->
        <div class="id-card-wrapper text-center">
            <div class="badge bg-primary text-white mb-2 no-print" style="font-size: 11px; letter-spacing: 1px; font-weight: 700;">FRONT SIDE</div>
            <div class="id-card-modern shadow-lg">
                <div class="id-card-top-header d-flex align-items-center justify-content-center gap-3">
                    <img src="assets/images/logo.png" alt="Church Logo" style="width: 44px; height: 44px; border-radius: 50%; border: 2px solid var(--cms-gold, #f59e0b); object-fit: cover;">
                    <div class="id-church-titles">
                        <div class="id-church-name">GOD'S FAMILY</div>
                        <div class="id-church-sub">United Methodist Church</div>
                    </div>
                </div>
                
                <div class="id-card-content">
                    <div class="id-avatar-container">
                        <?php if (!empty($member['photo_path']) && file_exists(__DIR__ . '/' . $member['photo_path'])): ?>
                            <img src="<?php echo htmlspecialchars($member['photo_path']); ?>" alt="Member Photo">
                        <?php else: ?>
                            <div class="id-avatar-placeholder">
                                <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h2 class="id-member-name"><?php echo htmlspecialchars($member['full_name']); ?></h2>
                    <div class="id-member-role"><?php echo htmlspecialchars($member['role_name'] ?? 'OFFICIAL MEMBER'); ?></div>
                    <div class="small text-muted mb-2 font-weight-700" style="letter-spacing: 1px;">ID #<?php echo str_pad($member['member_id'], 4, '0', STR_PAD_LEFT); ?></div>
                    
                    <div class="id-stats-grid mb-3">
                        <?php if (!empty($member['phone'])): ?>
                            <div class="id-stat-item" style="grid-column: span 2;">
                                <div class="id-stat-label">Phone Contact</div>
                                <div class="id-stat-value"><?php echo htmlspecialchars($member['phone']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="id-qr-box">
                        <?php if ($qrSvgContent): ?>
                            <?php echo $qrSvgContent; ?>
                        <?php elseif ($qrImageUrl): ?>
                            <img src="<?php echo htmlspecialchars($qrImageUrl); ?>" alt="QR Code" style="max-width: 140px; width: 100%; height: auto; object-fit: contain;">
                        <?php else: ?>
                            <p style="color:#dc2626; font-size:11px; text-align:center;"><?php echo htmlspecialchars($qrError); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="id-card-footer-modern">
                    <div style="font-weight: 700; color: #1e293b;">Official Member Access Pass</div>
                    <div style="margin-top: 4px; opacity: 0.8; font-size: 10px;">"Therefore go, grow in love, mission and service."</div>
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
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
