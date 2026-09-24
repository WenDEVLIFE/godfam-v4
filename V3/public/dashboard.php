<?php
/**
 * Dashboard View - Primary landing after login.
 * Adapts to Admin, Staff, and Member roles.
 */

$page_title = 'Dashboard Overview';
$body_class = 'dashboard-page';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CSRF.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/Member.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Collection.php';

// Ensure user is logged in
requireLogin();

$csrf_token = generateCsrfToken();

$memberModel     = new Member($pdo);
$eventModel      = new Event($pdo);
$attendanceModel = new Attendance($pdo);

$total_members          = 0;
$upcoming_events_count  = 0;
$today_attendance       = 0;
$my_attendance_count    = 0;
$my_qr_token            = '';
$birthday_celebrants    = [];
$anniversary_celebrants = [];
$today_events           = [];
$upcoming_birthdays     = [];
$upcoming_anniversaries = [];
$today_collection       = 0;
$month_collection       = 0;

if (isAdmin() || isStaff()) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM members");
    $total_members = $stmt->fetchColumn();

    $upcoming_events_count = $eventModel->countUpcoming();

    $stmt = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE()");
    $today_attendance = $stmt->fetchColumn();

    $birthday_celebrants    = $memberModel->getBirthdayCelebrantsToday();
    $anniversary_celebrants = $memberModel->getWeddingAnniversariesToday();
    $today_events           = $eventModel->getTodayEvents();
    $upcoming_birthdays     = $memberModel->getBirthdayCelebrantsUpcoming(7);
    $upcoming_anniversaries = $memberModel->getWeddingAnniversariesUpcoming(7);

    $collectionModel  = new Collection($pdo);
    $today_collection = $collectionModel->getTodayTotal();
    $month_collection = $collectionModel->getMonthTotal();
}

if (isMember()) {
    $member_id = $_SESSION['member_id'] ?? null;
    if (!$member_id && !empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT member_id FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $member_id = $stmt->fetchColumn();
        if ($member_id) $_SESSION['member_id'] = (int)$member_id;
    }

    $my_attendance_logs = [];
    $missed_sessions    = 0;

    if ($member_id) {
        $my_attendance_count = $attendanceModel->countAttendedServices($member_id);
        $my_attendance_logs  = $attendanceModel->getByMember($member_id);

        $past_events       = $eventModel->getPast();
        $missed_sessions   = max(0, count($past_events) - $my_attendance_count);

        $member_data  = $memberModel->find($member_id);
        $my_qr_token  = $member_data['qr_token'] ?? '';
    }
}

$recent_events = $eventModel->getUpcoming(5);

// Bible Verse of the Day — rotates daily, no external dependency
$verses = [
    ["For God so loved the world that he gave his one and only Son, that whoever believes in him shall not perish but have eternal life.", "John 3:16"],
    ["I can do all this through him who gives me strength.", "Philippians 4:13"],
    ["The Lord is my shepherd, I lack nothing.", "Psalm 23:1"],
    ["Trust in the Lord with all your heart and lean not on your own understanding.", "Proverbs 3:5"],
    ["Be strong and courageous. Do not be afraid; do not be discouraged, for the Lord your God will be with you wherever you go.", "Joshua 1:9"],
    ["And we know that in all things God works for the good of those who love him.", "Romans 8:28"],
    ["Cast all your anxiety on him because he cares for you.", "1 Peter 5:7"],
    ["But those who hope in the Lord will renew their strength.", "Isaiah 40:31"],
    ["The Lord bless you and keep you; the Lord make his face shine on you.", "Numbers 6:24-25"],
    ["Do not be anxious about anything, but in every situation, by prayer and petition, with thanksgiving, present your requests to God.", "Philippians 4:6"],
    ["For I know the plans I have for you, declares the Lord, plans to prosper you and not to harm you.", "Jeremiah 29:11"],
    ["Love is patient, love is kind. It does not envy, it does not boast, it is not proud.", "1 Corinthians 13:4"],
    ["The name of the Lord is a fortified tower; the righteous run to it and are safe.", "Proverbs 18:10"],
    ["Come to me, all you who are weary and burdened, and I will give you rest.", "Matthew 11:28"],
    ["Even though I walk through the darkest valley, I will fear no evil, for you are with me.", "Psalm 23:4"],
    ["This is the day the Lord has made; let us rejoice and be glad in it.", "Psalm 118:24"],
    ["Do everything in love.", "1 Corinthians 16:14"],
    ["The Lord is close to the brokenhearted and saves those who are crushed in spirit.", "Psalm 34:18"],
    ["Greater love has no one than this: to lay down one's life for one's friends.", "John 15:13"],
    ["Be joyful in hope, patient in affliction, faithful in prayer.", "Romans 12:12"],
    ["Blessed are the peacemakers, for they will be called children of God.", "Matthew 5:9"],
    ["For it is by grace you have been saved, through faith.", "Ephesians 2:8"],
    ["Let your light shine before others, that they may see your good deeds and glorify your Father in heaven.", "Matthew 5:16"],
    ["God is our refuge and strength, an ever-present help in trouble.", "Psalm 46:1"],
    ["Seek first his kingdom and his righteousness, and all these things will be given to you as well.", "Matthew 6:33"],
    ["I have hidden your word in my heart that I might not sin against you.", "Psalm 119:11"],
    ["Be kind and compassionate to one another, forgiving each other, just as in Christ God forgave you.", "Ephesians 4:32"],
    ["The earth is the Lord's, and everything in it, the world, and all who live in it.", "Psalm 24:1"],
    ["No weapon forged against you will prevail.", "Isaiah 54:17"],
    ["With man this is impossible, but with God all things are possible.", "Matthew 19:26"],
    ["My grace is sufficient for you, for my power is made perfect in weakness.", "2 Corinthians 12:9"],
    ["Jesus Christ is the same yesterday and today and forever.", "Hebrews 13:8"],
    ["The joy of the Lord is your strength.", "Nehemiah 8:10"],
    ["Do not conform to the pattern of this world, but be transformed by the renewing of your mind.", "Romans 12:2"],
    ["I am the way and the truth and the life.", "John 14:6"],
    ["Therefore, if anyone is in Christ, the new creation has come: The old has gone, the new is here!", "2 Corinthians 5:17"],
];
$verse_index   = (int) date('z') % count($verses);
$bible_verse   = $verses[$verse_index][0];
$bible_ref     = $verses[$verse_index][1];

include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="flat-dashboard">
<!-- ===== DASHBOARD HERO BANNER ===== -->
<div class="dashboard-hero-banner mb-4">
    <div class="dashboard-hero-overlay">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-white text-primary font-weight-700 px-3 py-1 mb-2 shadow-sm" style="border-radius: 20px; font-size: 11px; letter-spacing: 0.5px;">GOD'S FAMILY UM CHURCH</span>
                <h1 class="dashboard-hero-title">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
                <p class="dashboard-hero-sub"><?php echo date('l, F j, Y'); ?> &bull; System Management &amp; Pastoral Portal</p>
            </div>
        </div>
    </div>
</div>

<!-- ===== BIBLE VERSE OF THE DAY ===== -->
<div class="bible-verse-card mb-4">
    <div class="bible-verse-icon"><i class='bx bx-book-open'></i></div>
    <div class="bible-verse-body">
        <div class="bible-verse-label">Bible Verse of the Day</div>
        <blockquote class="bible-verse-text">"<?php echo htmlspecialchars($bible_verse); ?>"</blockquote>
        <cite class="bible-verse-ref">&mdash; <?php echo htmlspecialchars($bible_ref); ?></cite>
    </div>
</div>

<?php if (isAdmin() || isStaff()): ?>

<!-- ===== REMINDERS & GREETINGS CENTER WIDGET ===== -->
<div class="reminders-widget-card mb-4">
    <!-- Top Header -->
    <div class="reminders-widget-header">
        <div class="d-flex align-items-center gap-3">
            <div class="reminders-bell-icon">
                <i class='bx bxs-bell-ring'></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-800 text-dark" style="font-size: 1.2rem; letter-spacing: -0.3px;">Reminders &amp; Greetings Center</h4>
                <p class="text-muted small mb-0 font-weight-500">Stay updated with upcoming events and celebrate the special moments of our church family.</p>
            </div>
        </div>
        <button id="btn-send-daily-reminders" class="btn btn-primary font-weight-600 px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2" onclick="triggerDailyReminders()" style="border-radius: 24px;">
            <i class='bx bx-paper-plane'></i> Trigger Reminders &amp; Send Greetings
        </button>
    </div>

    <!-- Grid Container: 2 Equal Columns -->
    <div class="reminders-grid-container">
        <!-- LEFT COLUMN: UPCOMING REMINDERS (Blue Theme) -->
        <div class="reminders-blue-panel">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="reminders-blue-header-icon">
                            <i class='bx bxs-calendar'></i>
                        </div>
                        <h6 class="mb-0 font-weight-800 text-dark" style="font-size: 1.05rem;">Upcoming Reminders</h6>
                    </div>
                    <a href="events.php" class="text-primary font-weight-700 small text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 12px;">
                        View All <i class='bx bx-right-arrow-alt'></i>
                    </a>
                </div>

                <div class="d-flex flex-column">
                    <?php 
                    $display_events = !empty($recent_events) ? array_slice($recent_events, 0, 4) : [];
                    if (!empty($display_events)):
                        $badge_colors = [
                            ['bg' => '#e0f2fe', 'text' => '#0369a1'],
                            ['bg' => '#d1fae5', 'text' => '#047857'],
                            ['bg' => '#fef3c7', 'text' => '#b45309'],
                            ['bg' => '#ffe4e6', 'text' => '#be123c']
                        ];
                        $idx = 0;
                        foreach ($display_events as $ev):
                            $color = $badge_colors[$idx % count($badge_colors)];
                            $idx++;
                            $event_date = strtotime($ev['date']);
                            $month_str  = strtoupper(date('M', $event_date));
                            $day_str    = date('d', $event_date);
                    ?>
                        <a href="events.php" class="reminder-item-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="reminder-date-badge" style="background: <?php echo $color['bg']; ?>; color: <?php echo $color['text']; ?>;">
                                    <span class="reminder-date-month"><?php echo $month_str; ?></span>
                                    <span class="reminder-date-day"><?php echo $day_str; ?></span>
                                </div>
                                <div>
                                    <div class="font-weight-700 text-dark mb-1" style="font-size: 13.5px;"><?php echo htmlspecialchars($ev['title']); ?></div>
                                    <div class="text-muted extra-small font-weight-500 d-flex align-items-center gap-2" style="font-size: 11px;">
                                        <span><i class='bx bx-time-five me-1 text-primary'></i><?php echo !empty($ev['time']) ? date('h:i A', strtotime($ev['time'])) : 'All Day'; ?></span>
                                        <?php if (!empty($ev['location'])): ?>
                                            <span>&bull; <i class='bx bx-map me-1 text-muted'></i><?php echo htmlspecialchars($ev['location']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <i class='bx bx-chevron-right text-muted fs-5'></i>
                        </a>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <div class="text-center py-4 px-3 bg-white rounded-3 border border-dashed" style="border-radius: 12px !important;">
                            <i class='bx bxs-calendar-x text-muted opacity-50 mb-2' style="font-size: 2rem;"></i>
                            <div class="font-weight-700 text-dark small">No Upcoming Reminders</div>
                            <div class="text-muted extra-small">Check back later for new church events and schedules.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: BIRTHDAY & ANNIVERSARY GREETINGS (Purple Theme) -->
        <div class="greetings-purple-panel">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="greetings-purple-header-icon">
                            <i class='bx bxs-gift'></i>
                        </div>
                        <h6 class="mb-0 font-weight-800 text-dark" style="font-size: 1.05rem;">Birthday &amp; Anniversary Greetings</h6>
                    </div>
                    <a href="members.php" class="font-weight-700 small text-decoration-none d-inline-flex align-items-center gap-1" style="color: #7c3aed; font-size: 12px;">
                        View All <i class='bx bx-right-arrow-alt'></i>
                    </a>
                </div>

                <!-- TAB CONTROLS -->
                <div class="greetings-tab-bar">
                    <button type="button" class="greetings-tab-btn active" id="tab-btn-birthdays" onclick="switchGreetingsTab('birthdays')">
                        Birthdays
                    </button>
                    <button type="button" class="greetings-tab-btn" id="tab-btn-anniversaries" onclick="switchGreetingsTab('anniversaries')">
                        Wedding Anniversaries
                    </button>
                </div>

                <!-- BIRTHDAYS TAB CONTENT -->
                <div id="tab-content-birthdays">
                    <?php 
                    $all_birthdays = array_merge($birthday_celebrants, $upcoming_birthdays);
                    if (!empty($all_birthdays)):
                        $wishes = [
                            "May your new year be filled with God's endless blessings!",
                            "God's favor and joy be with you always!",
                            "Continue to shine for His glory! Happy Birthday!",
                            "Wishing you abundant grace and peace on your special day!"
                        ];
                        $b_idx = 0;
                        foreach (array_slice($all_birthdays, 0, 3) as $b_item):
                            $wish = $wishes[$b_idx % count($wishes)];
                            $b_idx++;
                            $b_date = !empty($b_item['birthday']) ? date('F j', strtotime($b_item['birthday'])) : 'Today';
                    ?>
                        <div class="greeting-item-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="greeting-avatar">
                                    <?php echo strtoupper(substr($b_item['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="font-weight-700 text-dark small mb-0.5"><?php echo htmlspecialchars($b_item['full_name']); ?></div>
                                    <div class="text-muted extra-small" style="font-size: 11px;"><?php echo $b_date; ?></div>
                                </div>
                            </div>
                            <div class="text-end ps-2" style="max-width: 48%;">
                                <div class="text-muted extra-small italic" style="font-size: 11px;"><?php echo $wish; ?></div>
                            </div>
                            <i class='bx bxs-cake me-1 fs-5' style="color: #8b5cf6;"></i>
                        </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <div class="text-center py-4 px-3 bg-white rounded-3 border border-dashed" style="border-color: #ddd6fe !important; border-radius: 12px !important;">
                            <i class='bx bxs-cake text-purple opacity-40 mb-2' style="color: #a78bfa; font-size: 2rem;"></i>
                            <div class="font-weight-700 text-dark small">No Birthday Celebrants</div>
                            <div class="text-muted extra-small">No upcoming birthdays found in the next 7 days.</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ANNIVERSARIES TAB CONTENT -->
                <div id="tab-content-anniversaries" style="display: none;">
                    <?php 
                    $all_anniversaries = array_merge($anniversary_celebrants, $upcoming_anniversaries);
                    if (!empty($all_anniversaries)):
                        $anniv_wishes = [
                            "May God continue to bless your marriage and journey together!",
                            "Celebrating your love and commitment in Christ!",
                            "Wishing you many more years of joy, love, and unity!"
                        ];
                        $a_idx = 0;
                        foreach (array_slice($all_anniversaries, 0, 3) as $a_item):
                            $a_wish = $anniv_wishes[$a_idx % count($anniv_wishes)];
                            $a_idx++;
                            $a_date = !empty($a_item['wedding_anniversary']) ? date('F j', strtotime($a_item['wedding_anniversary'])) : 'Today';
                    ?>
                        <div class="greeting-item-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="greeting-avatar" style="background: #ffe4e6 !important; color: #e11d48 !important;">
                                    <i class='bx bxs-heart'></i>
                                </div>
                                <div>
                                    <div class="font-weight-700 text-dark small mb-0.5"><?php echo htmlspecialchars($a_item['full_name']); ?></div>
                                    <div class="text-muted extra-small" style="font-size: 11px;"><?php echo $a_date; ?></div>
                                </div>
                            </div>
                            <div class="text-end ps-2" style="max-width: 48%;">
                                <div class="text-muted extra-small italic" style="font-size: 11px;"><?php echo $a_wish; ?></div>
                            </div>
                            <i class='bx bxs-heart text-danger me-1 fs-5'></i>
                        </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <div class="text-center py-4 px-3 bg-white rounded-3 border border-dashed" style="border-color: #fecdd3 !important; border-radius: 12px !important;">
                            <i class='bx bxs-heart text-danger opacity-40 mb-2' style="font-size: 2rem;"></i>
                            <div class="font-weight-700 text-dark small">No Wedding Anniversaries</div>
                            <div class="text-muted extra-small">No upcoming wedding anniversaries found.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Scripture / Inspiration Footer Quote -->
            <div class="greetings-footer-banner">
                <div class="d-flex align-items-center gap-2">
                    <i class='bx bxs-heart text-purple fs-5' style="color: #7c3aed;"></i>
                    <span class="font-weight-600 extra-small" style="color: #5b21b6; font-size: 11.5px; line-height: 1.4;">
                        Every celebration is a reminder of God's faithfulness and the beautiful people He has given us.
                    </span>
                </div>
                <i class='bx bx-sparkles opacity-75 fs-5' style="color: #8b5cf6;"></i>
            </div>
        </div>
    </div>
</div>

<script>
function switchGreetingsTab(tabName) {
    const btnBirthdays = document.getElementById('tab-btn-birthdays');
    const btnAnniversaries = document.getElementById('tab-btn-anniversaries');
    const contentBirthdays = document.getElementById('tab-content-birthdays');
    const contentAnniversaries = document.getElementById('tab-content-anniversaries');

    if (tabName === 'birthdays') {
        btnBirthdays.classList.add('active');
        btnBirthdays.style.background = '#7c3aed';
        btnBirthdays.style.color = '#ffffff';

        btnAnniversaries.classList.remove('active');
        btnAnniversaries.style.background = 'transparent';
        btnAnniversaries.style.color = '#6b21a8';

        contentBirthdays.style.display = 'block';
        contentAnniversaries.style.display = 'none';
    } else {
        btnAnniversaries.classList.add('active');
        btnAnniversaries.style.background = '#7c3aed';
        btnAnniversaries.style.color = '#ffffff';

        btnBirthdays.classList.remove('active');
        btnBirthdays.style.background = 'transparent';
        btnBirthdays.style.color = '#6b21a8';

        contentAnniversaries.style.display = 'block';
        contentBirthdays.style.display = 'none';
    }
}
</script>

<!-- ===== ADMIN STATS ===== -->
<div class="stats-grid">
    <a href="members.php" class="stat-card" style="text-decoration:none; color:inherit; cursor:pointer;" title="View Member Directory">
        <div class="stat-icon"><i class='bx bxs-group'></i></div>
        <div>
            <div class="stat-label">Total Members</div>
            <div class="stat-number"><?php echo number_format($total_members); ?></div>
        </div>
    </a>
    <a href="events.php" class="stat-card" style="text-decoration:none; color:inherit; cursor:pointer;" title="View Church Events">
        <div class="stat-icon"><i class='bx bxs-calendar'></i></div>
        <div>
            <div class="stat-label">Upcoming Events</div>
            <div class="stat-number"><?php echo number_format($upcoming_events_count); ?></div>
        </div>
    </a>
    <a href="reports.php?start_date=<?php echo date('Y-m-d'); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="stat-card" style="text-decoration:none; color:inherit; cursor:pointer;" title="View Today's Attendance Reports">
        <div class="stat-icon"><i class='bx bxs-check-square'></i></div>
        <div>
            <div class="stat-label">Attended Today</div>
            <div class="stat-number"><?php echo number_format($today_attendance); ?></div>
        </div>
    </a>
    <a href="financial_reports.php" class="stat-card" style="text-decoration:none; color:inherit; cursor:pointer;" title="View Financial Reports">
        <div class="stat-icon" style="background:#d1fae5;color:#059669;font-weight:800;font-size:1.5rem;">₱</div>
        <div>
            <div class="stat-label">Today's Collection</div>
            <div class="stat-number" style="color:#059669;">₱<?php echo number_format($today_collection, 2); ?></div>
            <div class="stat-sublabel">This month: ₱<?php echo number_format($month_collection, 2); ?></div>
        </div>
    </a>
</div>

<!-- ===== QUICK ACTIONS ===== -->
<div class="card mb-4">
    <div class="card-header">Management Quick Actions</div>
    <div class="card-body">
        <div class="d-flex gap-2 flex-wrap">
            <a href="events.php" class="btn btn-outline-primary"><i class='bx bxs-calendar'></i> Manage Events</a>
            <a href="scan_attendance.php" class="btn btn-primary"><i class='bx bx-qr-scan'></i> Scan QR Attendance</a>
            <a href="reports.php" class="btn btn-outline-primary"><i class='bx bxs-report'></i> Attendance Reports</a>
            <a href="financial_reports.php" class="btn btn-outline-primary"><i class='bx bx-spreadsheet'></i> Financial Reports</a>
            <a href="attendance_export.php?format=pdf&start_date=<?php echo date('Y-m-d'); ?>&end_date=<?php echo date('Y-m-d'); ?>" target="_blank" class="btn btn-outline-primary"><i class='bx bxs-file-pdf'></i> Export Today PDF</a>
            <a href="attendance_export.php?format=excel&start_date=<?php echo date('Y-m-d'); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-primary"><i class='bx bxs-file'></i> Export Today Excel</a>
        </div>
    </div>
</div>

<!-- ===== ATTENDANCE CHART ===== -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class='bx bx-bar-chart-alt-2' style="margin-right:6px;"></i>Attendance Overview</span>
        <div class="chart-toggle-group">
            <button class="chart-toggle-btn active" id="btn-weekly" onclick="showWeekly()">This Week</button>
            <button class="chart-toggle-btn" id="btn-monthly" onclick="showMonthly()">This Year</button>
        </div>
    </div>
    <div class="card-body">
        <div class="chart-container">
            <canvas id="attendanceChart"></canvas>
        </div>
        <div id="chart-loading" class="chart-loading-state">Loading chart data...</div>
    </div>
</div>

<!-- ===== MEMBERS GROWTH CHART ===== -->
<div class="card mb-4">
    <div class="card-header">
        <span><i class='bx bx-trending-up' style="margin-right:6px;"></i>Members Growth (Last 12 Months)</span>
    </div>
    <div class="card-body">
        <div class="chart-container">
            <canvas id="growthChart"></canvas>
        </div>
    </div>
</div>

<?php endif; ?>

<?php if (isMember()): ?>
<!-- ===== MEMBER DASHBOARD ===== -->
<div class="stats-grid">
    <a href="profile.php" class="stat-card" style="text-decoration:none; color:inherit; cursor:pointer;" title="View My Attendance Log">
        <div class="stat-icon"><i class='bx bxs-check-circle'></i></div>
        <div>
            <div class="stat-label">Services Attended</div>
            <div class="stat-number"><?php echo number_format($my_attendance_count); ?></div>
        </div>
    </a>
    <a href="events.php" class="stat-card" style="text-decoration:none; color:inherit; cursor:pointer;" title="View Church Events">
        <div class="stat-icon" style="background:#fef2f2;color:#dc2626;"><i class='bx bxs-x-circle'></i></div>
        <div>
            <div class="stat-label">Missed Services</div>
            <div class="stat-number text-danger"><?php echo number_format($missed_sessions); ?></div>
        </div>
    </a>
    <a href="profile.php" class="stat-card" style="text-decoration:none; color:inherit; cursor:pointer;" title="View My Digital ID Card">
        <div class="stat-icon"><i class='bx bxs-id-card'></i></div>
        <div>
            <div class="stat-label">My Digital ID</div>
            <div class="stat-number">View QR</div>
        </div>
    </a>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>My Recent Attendance</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($my_attendance_logs)): ?>
            <p class="p-4 text-center text-muted">No attendance recorded yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Event Title</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($my_attendance_logs, 0, 5) as $log): ?>
                        <tr>
                            <td class="small"><?php echo date('M d, Y', strtotime($log['date'])); ?></td>
                            <td class="font-weight-600"><?php echo htmlspecialchars($log['event_title'] ?? 'Church Service'); ?></td>
                            <td><span class="badge badge-success"><?php echo htmlspecialchars($log['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== UPCOMING EVENTS (ALL ROLES) ===== -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recent &amp; Upcoming Events</span>
        <a href="events.php" class="small">View All</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recent_events)): ?>
            <p class="p-4 text-center text-muted">No events scheduled.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Event Title</th>
                            <th>Location</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_events as $event): ?>
                        <tr>
                            <td class="small"><?php echo date('M d, Y', strtotime($event['date'])); ?></td>
                            <td class="font-weight-600"><?php echo htmlspecialchars($event['title']); ?></td>
                            <td><?php echo htmlspecialchars($event['location'] ?? 'Main Hall'); ?></td>
                            <td class="small"><?php echo date('h:i A', strtotime($event['time'] ?? '00:00:00')); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isAdmin() || isStaff()): ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
let attendanceChart = null;
let chartData       = null;

// Fetch chart data from API
fetch('api/dashboard_stats.php')
    .then(r => r.json())
    .then(data => {
        chartData = data;
        document.getElementById('chart-loading').style.display = 'none';
        buildAttendanceChart('weekly');
        buildGrowthChart(data.growth);
    })
    .catch(() => {
        document.getElementById('chart-loading').textContent = 'Could not load chart data.';
    });

function buildAttendanceChart(mode) {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    if (attendanceChart) attendanceChart.destroy();

    const isWeekly  = mode === 'weekly';
    const labels    = isWeekly ? chartData.weekly.labels   : chartData.monthly.labels;
    const data      = isWeekly ? chartData.weekly.data     : chartData.monthly.data;
    const title     = isWeekly ? 'Daily Attendance (Last 7 Days)' : `Monthly Attendance (${chartData.monthly.year})`;

    attendanceChart = new Chart(ctx, {
        type: isWeekly ? 'bar' : 'line',
        data: {
            labels,
            datasets: [{
                label: 'Members Present',
                data,
                backgroundColor: isWeekly ? 'rgba(21,101,192,0.75)' : 'rgba(46,125,50,0.12)',
                borderColor: isWeekly ? '#1565C0' : '#2E7D32',
                borderWidth: 2,
                borderRadius: isWeekly ? 6 : 0,
                fill: !isWeekly,
                tension: 0.4,
                pointBackgroundColor: '#2E7D32',
                pointRadius: isWeekly ? 0 : 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: { display: true, text: title, font: { size: 13, family: 'Inter' }, color: '#1F2937' },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, precision: 0 },
                    grid: { color: 'rgba(0,0,0,0.04)' }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

function buildGrowthChart(growth) {
    const ctx = document.getElementById('growthChart').getContext('2d');

    // Gradient fill beneath the line
    const gradient = ctx.createLinearGradient(0, 0, 0, ctx.canvas.clientHeight || 220);
    gradient.addColorStop(0, 'rgba(21, 101, 192, 0.25)');
    gradient.addColorStop(1, 'rgba(21, 101, 192, 0.00)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: growth.labels.length ? growth.labels : ['No data yet'],
            datasets: [{
                label: 'New Members',
                data: growth.data.length ? growth.data : [0],
                fill: true,
                backgroundColor: gradient,
                borderColor: '#1565C0',
                borderWidth: 2.5,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#1565C0',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(30,20,60,0.85)',
                    titleColor: '#c4b5fd',
                    bodyColor: '#e9e4ff',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.y} new member${ctx.parsed.y !== 1 ? 's' : ''}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, precision: 0, color: '#8b7ec8' },
                    grid: { color: 'rgba(113,87,209,0.08)' },
                    border: { dash: [4, 4] }
                },
                x: {
                    ticks: { color: '#8b7ec8' },
                    grid: { display: false }
                }
            }
        }
    });
}

function showWeekly() {
    if (!chartData) return;
    buildAttendanceChart('weekly');
    document.getElementById('btn-weekly').classList.add('active');
    document.getElementById('btn-monthly').classList.remove('active');
}
function showMonthly() {
    if (!chartData) return;
    buildAttendanceChart('monthly');
    document.getElementById('btn-monthly').classList.add('active');
    document.getElementById('btn-weekly').classList.remove('active');
}

function triggerDailyReminders() {
    const btn = document.getElementById('btn-send-daily-reminders');
    if (!btn) return;
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Dispatching Greetings & Reminders...";

    fetch('api/trigger_birthday_reminder.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf_token: '<?php echo $csrf_token; ?>' })
    })
    .then(async r => {
        const text = await r.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error("Server returned non-JSON response.");
        }
    })
    .then(data => {
        if (data.success) {
            alert(data.message || 'Reminders and greetings triggered successfully!');
            btn.innerHTML = "<i class='bx bx-check-circle'></i> Processed & Dispatched!";
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
        } else {
            alert('Notice: ' + (data.error || 'Failed to dispatch reminders.'));
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    })
    .catch(err => {
        alert('An error occurred while dispatching reminders: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = origText;
    });
}

function sendBirthdayReminders() {
    triggerDailyReminders();
}
</script>
<?php endif; ?>

</div><!-- /.flat-dashboard -->

<?php
include __DIR__ . '/layout/footer.php';
?>
