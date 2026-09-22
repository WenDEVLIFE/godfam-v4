<?php
/**
 * Full Digital Member ID Card Generator
 * Renders printable and downloadable whole digital ID cards featuring member details,
 * photo, QR token, and church branding.
 */

$page_title = 'Digital Member ID Generator';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
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

// Generate QR Code SVG
$qrSvgContent = '';
$qrError = '';
if (!empty($member['qr_token'])) {
    try {
        $qrCode = new QrCode($member['qr_token']);
        $qrCode->setSize(200);
        $qrCode->setMargin(8);
        $qrCode->setWriterByName('svg');
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM());
        $qrSvgContent = $qrCode->writeString();
    } catch (\Throwable $e) {
        $qrError = 'QR Generation Error: ' . $e->getMessage();
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

    <!-- ID Card Container -->
    <div id="printable-id-card" class="d-flex justify-content-center align-items-center gap-4 flex-wrap my-4">
        <!-- FRONT CARD -->
        <div class="id-card-modern shadow-lg">
            <div class="id-card-top-header d-flex align-items-center justify-content-center gap-3">
                <img src="assets/images/logo.png" alt="Church Logo" style="width: 44px; height: 44px; border-radius: 50%; border: 2px solid var(--cms-gold); object-fit: cover;">
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

        <!-- BACK CARD -->
        <div class="id-card-modern shadow-lg no-print-mobile" style="background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); position: relative;">
            <div class="id-card-top-header d-flex align-items-center justify-content-center gap-2" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="id-church-titles text-center">
                    <div class="id-church-name" style="letter-spacing: 1.5px;">TERMS &amp; CONDITIONS</div>
                    <div class="id-church-sub">God's Family UM Church</div>
                </div>
            </div>

            <div class="id-card-content text-left" style="padding: 24px; font-size: 11px; line-height: 1.6; color: #334155;">
                <div class="mb-3">
                    <strong>1. Membership Verification:</strong> This digital ID card serves as an official identification pass for church services, events, and ministry activities.
                </div>
                <div class="mb-3">
                    <strong>2. Non-Transferable:</strong> This pass is strictly non-transferable and issued solely to the designated member.
                </div>
                <div class="mb-3">
                    <strong>3. QR Scanner Access:</strong> Present the front QR code at scanner kiosks for automated attendance logging.
                </div>
                <?php if (!empty($member['address'])): ?>
                    <div class="mb-3">
                        <strong>Address:</strong> <?php echo htmlspecialchars($member['address']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($member['contact_info'])): ?>
                    <div class="mb-3">
                        <strong>Emergency Info:</strong> <?php echo htmlspecialchars($member['contact_info']); ?>
                    </div>
                <?php endif; ?>
                <div class="mt-4 pt-3 border-top text-center">
                    <div style="font-family: 'Playfair Display', serif; font-size: 14px; font-style: italic; color: #0f172a;">Rev. Benjamin Santos</div>
                    <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 700;">Resident Pastor</div>
                </div>
            </div>

            <div class="id-card-footer-modern" style="position: absolute; bottom: 0; left: 0; right: 0;">
                <div style="font-size: 10px; color: #64748b;">Property of God's Family UMC. If found, please return to Church Administration.</div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
