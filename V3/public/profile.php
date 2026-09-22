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

$member_id = $_SESSION['member_id'] ?? null;
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
$qrError = '';
if (!class_exists('Endroid\QrCode\QrCode')) {
    $qrError = 'Vendor library missing. Please copy the "vendor" folder to C:\xampp\htdocs\V3\vendor or run "composer install".';
} elseif (!empty($member['qr_token'])) {
    try {
        // Using endroid/qr-code v3.x fluent API for maximum PHP 8.0 compatibility.
        // This version does not use the Builder pattern.
        $qrCode = new QrCode($member['qr_token']);
        $qrCode->setSize(200);
        $qrCode->setMargin(8);
        $qrCode->setWriterByName('svg');
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM());
        
        $qrSvgContent = $qrCode->writeString();
    } catch (\Throwable $e) {
        $qrError = 'QR Error: ' . $e->getMessage();
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
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Digital Member ID</h1>
            <p class="text-muted small">Official identification</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-primary no-print">
            Print ID Card
        </button>
    </div>
</div>

<div id="printable-id-card" class="d-flex justify-content-center">
    <div class="id-card-modern">
        <div class="id-card-top-header d-flex align-items-center justify-content-center gap-3">
            <img src="assets/images/logo.png" alt="Logo" style="width: 44px; height: 44px; border-radius: 50%; border: 2px solid var(--cms-gold); object-fit: cover;">
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
            
            <?php if ($member['phone']): ?>
            <div class="id-stats-grid">
                <div class="id-stat-item" style="grid-column: span 2;">
                    <div class="id-stat-label">Contact</div>
                    <div class="id-stat-value"><?php echo htmlspecialchars($member['phone']); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="id-qr-box">
                <?php if ($qrSvgContent): ?>
                    <?php echo $qrSvgContent; ?>
                <?php else: ?>
                    <p style="color:#c53030;font-size:10px;text-align:center;"><?php echo htmlspecialchars($qrError); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="id-card-footer-modern">
            <div style="font-weight: 600;"></div> 
            <div style="margin-top: 10px; opacity: 0.7;">Therefore go, grow in love, mission and service.</div>
        </div>
    </div>
</div>


<?php 
// Include Layout Footer
include __DIR__ . '/layout/footer.php';
?>
