<?php
/**
 * Dummy Data Seeder Script for Church Management System
 * Populates realistic sample data across all system tables.
 *
 * Usage: php database/seed_dummy_data.php
 */

require_once __DIR__ . '/../config/database.php';

echo "Starting dummy data seeding...\n";

try {
    $pdo->beginTransaction();

    // 1. Roles (Ensure system roles exist)
    $roles = [
        [1, 'Administrator', 'administrator', 'System Administrator with full access', 1],
        [2, 'Pastor', 'pastor', 'Pastoral leadership', 1],
        [3, 'Staff', 'staff', 'Operational church staff', 1],
        [4, 'Member', 'member', 'Standard church member', 1],
        [5, 'Secretary', 'secretary', 'Administrative secretary', 1],
        [6, 'Committee Head', 'committee_head', 'Committee department leader', 1],
        [9, 'Treasurer', 'treasurer', 'Finance and collections controller', 1],
    ];

    $stmt = $pdo->prepare("INSERT INTO roles (role_id, role_name, role_slug, description, is_system) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE role_name=VALUES(role_name)");
    foreach ($roles as $r) {
        $stmt->execute($r);
    }
    echo "✔ Roles updated.\n";

    // 2. Members
    $dummyMembers = [
        ['full_name' => 'Camille Jane Madrid',  'email' => 'camillejanemadrid629@gmail.com', 'phone' => '+639171234567', 'birthday' => '1995-06-29', 'gender' => 'Female', 'address' => '123 Main St, Cabanatuan City', 'status' => 'active', 'role_id' => 1],
        ['full_name' => 'Rev. Benjamin Santos', 'email' => 'pastor.benjamin@church.org',     'phone' => '+639182345678', 'birthday' => '1978-04-12', 'gender' => 'Male',   'address' => '45 Church Compound, Gapan City', 'status' => 'active', 'role_id' => 2],
        ['full_name' => 'Maria Clara Dela Cruz','email' => 'maria.delacruz@gmail.com',       'phone' => '+639193456789', 'birthday' => '1992-09-16', 'gender' => 'Female', 'address' => '78 Rizal St, San Jose City',     'status' => 'active', 'role_id' => 5],
        ['full_name' => 'Juan Dela Cruz',       'email' => 'juan.delacruz@yahoo.com',        'phone' => '+639204567890', 'birthday' => '1988-11-20', 'gender' => 'Male',   'address' => '12 Mabini Ave, Talavera',       'status' => 'active', 'role_id' => 3],
        ['full_name' => 'Grace Gonzales',       'email' => 'grace.gonzales@gmail.com',       'phone' => '+639215678901', 'birthday' => '2001-03-15', 'gender' => 'Female', 'address' => '56 Luna St, Santa Rosa',         'status' => 'active', 'role_id' => 4],
        ['full_name' => 'Joshua Ramos',         'email' => 'joshua.ramos@outlook.com',       'phone' => '+639226789012', 'birthday' => '1999-08-04', 'gender' => 'Male',   'address' => '89 Bonifacio St, Cabanatuan City','status' => 'active', 'role_id' => 4],
        ['full_name' => 'Angelica Reyes',       'email' => 'angelica.reyes@gmail.com',       'phone' => '+639237890123', 'birthday' => '1996-12-10', 'gender' => 'Female', 'address' => '34 Quezon Blvd, Gapan City',    'status' => 'active', 'role_id' => 4],
        ['full_name' => 'Daniel Padilla',       'email' => 'daniel.padilla@gmail.com',       'phone' => '+639248901234', 'birthday' => '1994-04-26', 'gender' => 'Male',   'address' => '90 Del Pilar St, Cabanatuan',   'status' => 'active', 'role_id' => 4],
        ['full_name' => 'Kathryn Bernardo',     'email' => 'kathryn.bernardo@gmail.com',     'phone' => '+639259012345', 'birthday' => '1996-03-26', 'gender' => 'Female', 'address' => '15 Roxas St, San Leonardo',      'status' => 'active', 'role_id' => 4],
        ['full_name' => 'Ricardo Dalisay',      'email' => 'cardo.dalisay@gmail.com',        'phone' => '+639260123456', 'birthday' => '1985-01-14', 'gender' => 'Male',   'address' => '77 Zamora St, Cabanatuan City', 'status' => 'active', 'role_id' => 4],
        ['full_name' => 'Liza Soberano',        'email' => 'liza.soberano@gmail.com',        'phone' => '+639271234567', 'birthday' => '1998-01-04', 'gender' => 'Female', 'address' => '22 Burgos St, Palayan City',     'status' => 'active', 'role_id' => 4],
        ['full_name' => 'Enrique Gil',          'email' => 'enrique.gil@gmail.com',          'phone' => '+639282345678', 'birthday' => '1992-03-30', 'gender' => 'Male',   'address' => '88 Aquino St, Cabanatuan City', 'status' => 'active', 'role_id' => 4],
        ['full_name' => 'Sarah Geronimo',       'email' => 'sarah.geronimo@gmail.com',       'phone' => '+639293456789', 'birthday' => '1988-07-25', 'gender' => 'Female', 'address' => '11 Balingit St, Gapan City',    'status' => 'visiting', 'role_id' => 4],
        ['full_name' => 'Matteo Guidicelli',    'email' => 'matteo.guidicelli@gmail.com',    'phone' => '+639304567890', 'birthday' => '1990-03-26', 'gender' => 'Male',   'address' => '55 Recto Ave, San Isidro',      'status' => 'inactive', 'role_id' => 4],
        ['full_name' => 'Bea Alonzo',           'email' => 'bea.alonzo@gmail.com',           'phone' => '+639315678901', 'birthday' => '1987-10-17', 'gender' => 'Female', 'address' => '33 Valenzuela St, Cabanatuan',  'status' => 'active', 'role_id' => 4],
    ];

    $memberStmt = $pdo->prepare("INSERT INTO members (full_name, email, phone, contact_info, birthday, birthdate, gender, address, status, role_id, qr_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $memberIds = [];

    foreach ($dummyMembers as $m) {
        // Check if exists
        $chk = $pdo->prepare("SELECT member_id FROM members WHERE email = ? LIMIT 1");
        $chk->execute([$m['email']]);
        $existingId = $chk->fetchColumn();

        if ($existingId) {
            $memberIds[$m['email']] = (int)$existingId;
        } else {
            $qrToken = bin2hex(random_bytes(16));
            $memberStmt->execute([
                $m['full_name'],
                $m['email'],
                $m['phone'],
                $m['phone'],
                $m['birthday'],
                $m['birthday'],
                $m['gender'],
                $m['address'],
                $m['status'],
                $m['role_id'],
                $qrToken
            ]);
            $memberIds[$m['email']] = (int)$pdo->lastInsertId();
        }
    }
    echo "✔ Dummy members inserted/verified.\n";

    // 3. Users (linked to members)
    $passwordHash = password_hash('Admin123!', PASSWORD_DEFAULT);
    $dummyUsers = [
        ['role_id' => 1, 'email' => 'camillejanemadrid629@gmail.com', 'name' => 'Camille Jane Madrid'],
        ['role_id' => 2, 'email' => 'pastor.benjamin@church.org',     'name' => 'Rev. Benjamin Santos'],
        ['role_id' => 5, 'email' => 'maria.delacruz@gmail.com',       'name' => 'Maria Clara Dela Cruz'],
        ['role_id' => 3, 'email' => 'juan.delacruz@yahoo.com',        'name' => 'Juan Dela Cruz'],
        ['role_id' => 4, 'email' => 'grace.gonzales@gmail.com',       'name' => 'Grace Gonzales'],
    ];

    $userStmt = $pdo->prepare("INSERT INTO users (role_id, member_id, name, email, password) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
    $userIds = [];

    foreach ($dummyUsers as $u) {
        $mId = $memberIds[$u['email']] ?? null;
        
        $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $chk->execute([$u['email']]);
        $uId = $chk->fetchColumn();

        if ($uId) {
            $userIds[$u['email']] = (int)$uId;
        } else {
            $userStmt->execute([$u['role_id'], $mId, $u['name'], $u['email'], $passwordHash]);
            $userIds[$u['email']] = (int)$pdo->lastInsertId();
        }
    }
    echo "✔ Dummy users inserted/verified.\n";

    // 4. Events
    $dummyEvents = [
        ['title' => 'Sunday Worship & Holy Communion', 'description' => 'Weekly Sunday morning worship service & celebration.', 'date' => date('Y-m-d', strtotime('-14 days')), 'time' => '09:00:00', 'location' => 'Main Sanctuary', 'status' => 'completed'],
        ['title' => 'Midweek Prayer & Bible Study',    'description' => 'Wednesday evening prayer meeting and Bible study.',   'date' => date('Y-m-d', strtotime('-10 days')), 'time' => '18:30:00', 'location' => 'Fellowship Hall', 'status' => 'completed'],
        ['title' => 'Sunday Praise & Thanksgiving',    'description' => 'Sunday morning praise and sermon service.',          'date' => date('Y-m-d', strtotime('-7 days')),  'time' => '09:00:00', 'location' => 'Main Sanctuary', 'status' => 'completed'],
        ['title' => 'Youth Fellowship Night',          'description' => 'Youth fellowship, music worship, and games.',         'date' => date('Y-m-d', strtotime('-3 days')),  'time' => '17:00:00', 'location' => 'Youth Center',   'status' => 'completed'],
        ['title' => 'Sunday Celebration Service',     'description' => 'Upcoming Sunday morning service.',                   'date' => date('Y-m-d'),                       'time' => '09:00:00', 'location' => 'Main Sanctuary', 'status' => 'upcoming'],
        ['title' => 'Church Officers Meeting',        'description' => 'Monthly administrative and pastoral planning.',       'date' => date('Y-m-d', strtotime('+3 days')),  'time' => '14:00:00', 'location' => 'Conference Room','status' => 'upcoming'],
        ['title' => 'Annual Church Thanksgiving',     'description' => 'Grand annual thanksgiving service & fellowship.',    'date' => date('Y-m-d', strtotime('+14 days')), 'time' => '08:30:00', 'location' => 'Main Sanctuary', 'status' => 'upcoming'],
    ];

    $eventStmt = $pdo->prepare("INSERT INTO events (title, description, date, time, location, status) VALUES (?, ?, ?, ?, ?, ?)");
    $eventIds = [];

    foreach ($dummyEvents as $e) {
        $chk = $pdo->prepare("SELECT event_id FROM events WHERE title = ? AND date = ? LIMIT 1");
        $chk->execute([$e['title'], $e['date']]);
        $evId = $chk->fetchColumn();

        if ($evId) {
            $eventIds[] = (int)$evId;
        } else {
            $eventStmt->execute([$e['title'], $e['description'], $e['date'], $e['time'], $e['location'], $e['status']]);
            $eventIds[] = (int)$pdo->lastInsertId();
        }
    }
    echo "✔ Dummy events inserted/verified.\n";

    // 5. Attendance
    $attStmt = $pdo->prepare("INSERT INTO attendance (member_id, event_id, date, status, created_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
    $allMemberIds = array_values($memberIds);

    if (!empty($eventIds)) {
        foreach ($eventIds as $idx => $evId) {
            $evDate = $dummyEvents[$idx]['date'] ?? date('Y-m-d');
            // Record attendance for 10 random members for each event
            foreach ($allMemberIds as $mIdx => $mId) {
                if ($mIdx % 2 === 0 || $idx % 2 === 0) {
                    $status = ($mIdx % 5 === 0) ? 'Absent' : 'Present';
                    $attStmt->execute([$mId, $evId, $evDate, $status, $evDate . ' 08:50:00']);
                }
            }
        }
    }
    echo "✔ Dummy attendance logs inserted.\n";

    // 6. Collections (Tithes, Offerings, Special Giving)
    $colStmt = $pdo->prepare("INSERT INTO collections (member_id, amount, category, payment_method, collection_date, recorded_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $adminUserId = reset($userIds) ?: 1;

    $dummyCollections = [
        [array_values($memberIds)[0] ?? null, 1500.00, 'Tithe',    'Cash',  date('Y-m-d', strtotime('-14 days')), $adminUserId, 'Weekly Tithe'],
        [array_values($memberIds)[1] ?? null, 2500.00, 'Offering', 'GCash', date('Y-m-d', strtotime('-14 days')), $adminUserId, 'Sunday Offering'],
        [array_values($memberIds)[2] ?? null, 1000.00, 'Tithe',    'Cash',  date('Y-m-d', strtotime('-10 days')), $adminUserId, 'Midweek Tithe'],
        [array_values($memberIds)[3] ?? null, 3000.00, 'Donation', 'Bank',  date('Y-m-d', strtotime('-7 days')),  $adminUserId, 'Building Fund Giving'],
        [array_values($memberIds)[4] ?? null,  500.00, 'Offering', 'Cash',  date('Y-m-d', strtotime('-7 days')),  $adminUserId, 'Sunday Offering'],
        [array_values($memberIds)[5] ?? null, 2000.00, 'Tithe',    'GCash', date('Y-m-d', strtotime('-3 days')),  $adminUserId, 'Youth Fellowship Tithe'],
        [null,                                1250.00, 'Offering', 'Cash',  date('Y-m-d'),                        $adminUserId, 'Anonymous Visitor Offering'],
        [array_values($memberIds)[6] ?? null, 5000.00, 'Donation', 'GCash', date('Y-m-d'),                        $adminUserId, 'Missionary Support Donation'],
    ];

    foreach ($dummyCollections as $c) {
        $colStmt->execute($c);
    }
    echo "✔ Dummy collection records inserted.\n";

    // 7. Expenses
    $expStmt = $pdo->prepare("INSERT INTO expenses (amount, category, expense_date, description, recorded_by) VALUES (?, ?, ?, ?, ?)");
    $dummyExpenses = [
        [1200.00, 'Utilities',    date('Y-m-d', strtotime('-12 days')), 'Electricity bill payment', $adminUserId],
        [ 800.00, 'Maintenance',  date('Y-m-d', strtotime('-8 days')),  'Sanctuary aircon cleaning', $adminUserId],
        [ 500.00, 'Ministry',     date('Y-m-d', strtotime('-5 days')),  'Sunday school materials',   $adminUserId],
        [1500.00, 'Honorarium',   date('Y-m-d', strtotime('-2 days')),  'Guest speaker honorarium',  $adminUserId],
    ];

    foreach ($dummyExpenses as $ex) {
        $expStmt->execute($ex);
    }
    echo "✔ Dummy expense records inserted.\n";

    // 8. Announcements
    $annStmt = $pdo->prepare("INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)");
    $dummyAnnouncements = [
        ['Sunday Worship Schedule', 'Please be reminded that our Sunday Service starts promptly at 9:00 AM. See you with your family!', $adminUserId],
        ['Youth Fellowship Camp 2026', 'Registration is now open for the upcoming Summer Youth Camp. Contact the youth coordinator for details.', $adminUserId],
        ['Annual Thanksgiving Preparation', 'We invite all church committees to join the coordination meeting this coming Saturday.', $adminUserId],
    ];

    foreach ($dummyAnnouncements as $an) {
        $chk = $pdo->prepare("SELECT announcement_id FROM announcements WHERE title = ? LIMIT 1");
        $chk->execute([$an[0]]);
        if (!$chk->fetchColumn()) {
            $annStmt->execute($an);
        }
    }
    echo "✔ Dummy announcements inserted.\n";

    // 9. Notifications
    $notifStmt = $pdo->prepare("INSERT INTO notifications (member_id, type, title, message, reminder_date) VALUES (?, ?, ?, ?, ?)");
    $dummyNotifs = [
        [array_values($memberIds)[2] ?? null, 'birthday', '🎂 Birthday Celebrants Today', 'Celebrating Maria Clara Dela Cruz birthday today!', date('Y-m-d')],
        [array_values($memberIds)[3] ?? null, 'event',    '📅 Upcoming Service',        'Reminder: Sunday Worship Service tomorrow at 9:00 AM', date('Y-m-d', strtotime('+1 day'))],
    ];

    foreach ($dummyNotifs as $n) {
        $notifStmt->execute($n);
    }
    echo "✔ Dummy notifications inserted.\n";

    // 10. Audit Logs
    $auditStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $dummyAudits = [
        [$adminUserId, 'LOGIN', 'Admin logged into dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
        [$adminUserId, 'RECORD_COLLECTION', 'Recorded collection ₱1,500.00 (Tithe)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
        [$adminUserId, 'CREATE_EVENT', 'Created new event: Youth Fellowship Night', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
        [$adminUserId, 'SCAN_ATTENDANCE', 'Scanned QR attendance for member ID 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
    ];

    foreach ($dummyAudits as $au) {
        $auditStmt->execute($au);
    }
    echo "✔ Dummy audit log entries inserted.\n";

    $pdo->commit();
    echo "\n🎉 SUCCESS: All dummy data seeded successfully!\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ ERROR during seeding: " . $e->getMessage() . "\n";
    exit(1);
}
