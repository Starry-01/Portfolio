<?php
include 'db.php';
// Fetch all users with a complete profile
$stmt = $conn->prepare("
    SELECT id, username, full_name, bio, photo, skills, job_status, active_since,
           (SELECT COUNT(*) FROM projects WHERE admin_id = users.id) AS project_count
    FROM users
    WHERE full_name IS NOT NULL AND full_name != ''
      AND bio IS NOT NULL AND bio != ''
    ORDER BY created_at DESC
");
$stmt->execute();
$allUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$currentUid = getCurrentUserId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Dashboard · Discover Talent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #f1f1f1; }

        /* Navbar */
        .nav-bg { background: rgba(15,12,41,0.9); border-bottom: 1px solid rgba(255,255,255,0.07); backdrop-filter: blur(16px); }

        /* Cards */
        .portfolio-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            backdrop-filter: blur(12px);
            transition: transform .3s ease, box-shadow .3s ease, border-color .3s;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }
        .portfolio-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            border-color: rgba(0,210,255,0.35);
        }
        .portfolio-card.busy:hover { border-color: rgba(255,75,43,0.5); }

        /* Status dot */
        .dot-avail { background: #00d2ff; box-shadow: 0 0 8px #00d2ff88; }
        .dot-busy  { background: #ff4b2b; box-shadow: 0 0 8px #ff4b2b88; }
        @keyframes pulseDot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.6;transform:scale(1.3)} }
        .dot-pulse { animation: pulseDot 2s infinite; }

        /* Skill tags */
        .skill-tag { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 20px; padding: 3px 10px; font-size: 11px; color: rgba(255,255,255,0.6); white-space: nowrap; }

        /* Search / Filter */
        .search-input { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); color: #f1f1f1; border-radius: 10px; padding: 10px 16px 10px 40px; font-size: 14px; width: 100%; transition: all .2s; font-family: 'DM Sans', sans-serif; }
        .search-input::placeholder { color: rgba(255,255,255,0.3); }
        .search-input:focus { outline: none; border-color: #00d2ff; box-shadow: 0 0 0 3px rgba(0,210,255,0.12); }
        .filter-btn { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.6); border-radius: 8px; padding: 8px 16px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all .2s; }
        .filter-btn.active, .filter-btn:hover { background: rgba(0,210,255,0.15); border-color: rgba(0,210,255,0.4); color: #00d2ff; }

        /* Animations */
        @keyframes fadeIn  { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
        .anim { animation: fadeIn .6s ease both; }
        .card-grid > a:nth-child(1)  { animation-delay:.05s }
        .card-grid > a:nth-child(2)  { animation-delay:.1s  }
        .card-grid > a:nth-child(3)  { animation-delay:.15s }
        .card-grid > a:nth-child(4)  { animation-delay:.2s  }
        .card-grid > a:nth-child(5)  { animation-delay:.25s }
        .card-grid > a:nth-child(6)  { animation-delay:.3s  }
        .card-grid > a:nth-child(7)  { animation-delay:.35s }
        .card-grid > a:nth-child(8)  { animation-delay:.4s  }

        /* No results */
        #no-results { display: none; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="nav-bg sticky top-0 z-40 w-full">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="index.php" style="font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:900; color:#f1f1f1; text-decoration:none;">
            Portfolio Engine<span style="color:#00d2ff">.</span>
        </a>
        <div class="flex items-center gap-3">
            <?php if (isLoggedIn()): ?>
            <a href="index.php?u=<?= urlencode($_SESSION['username']) ?>"
               class="text-sm px-4 py-2 rounded-full font-medium transition-all hover:scale-105"
               style="background:rgba(0,210,255,0.12); border:1px solid rgba(0,210,255,0.3); color:#00d2ff;">
                My Portfolio
            </a>
            <a href="logout.php" class="text-xs opacity-40 hover:opacity-80 transition-opacity">Log out</a>
            <?php else: ?>
            <a href="login.php"
               class="text-sm px-4 py-2 rounded-full font-medium transition-all hover:scale-105"
               style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); color:#f1f1f1;">
                Sign In
            </a>
            <a href="register.php"
               class="text-sm px-4 py-2 rounded-full font-medium transition-all hover:scale-105"
               style="background:linear-gradient(90deg,#00d2ff,#3a7bd5); color:white;">
                Get Started
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="max-w-6xl mx-auto px-6 py-12">

    <!-- Header -->
    <div class="text-center mb-12 anim">
        <p class="text-xs font-semibold uppercase tracking-widest mb-3" style="color:#00d2ff;">Discover Talent</p>
        <h1 style="font-family:'Playfair Display',serif; font-size:2.8rem; font-weight:900; line-height:1.1;">
            Profiles<span style="color:#00d2ff">.</span>
        </h1>
        <p class="mt-3 text-base" style="color:rgba(255,255,255,0.45);">
            <?= count($allUsers) ?> creator<?= count($allUsers) !== 1 ? 's' : '' ?> showcasing their work
        </p>
    </div>

    <!-- Search & Filter bar -->
    <div class="flex flex-col sm:flex-row gap-3 mb-8 anim" style="animation-delay:.15s;">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" id="searchInput" class="search-input" placeholder="Search by name or skill…" oninput="filterCards()">
        </div>
        <div class="flex gap-2">
            <button class="filter-btn active" data-filter="all"       onclick="setFilter('all',       this)">All</button>
            <button class="filter-btn"        data-filter="available" onclick="setFilter('available', this)">Available</button>
            <button class="filter-btn"        data-filter="busy"      onclick="setFilter('busy',      this)">Busy</button>
        </div>
    </div>

    <!-- Cards grid -->
    <?php if (!empty($allUsers)): ?>
    <div class="card-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="cardGrid">
        <?php foreach ($allUsers as $u):
            $uName    = !empty($u['full_name']) ? $u['full_name'] : $u['username'];
            $uBio     = !empty($u['bio'])       ? mb_strimwidth($u['bio'], 0, 90, '…') : '';
            $uPhoto   = !empty($u['photo'])     ? $u['photo'] : '';
            $uBusy    = ($u['job_status'] === 'Busy');
            $uSkills  = array_filter(array_map('trim', explode(',', $u['skills'] ?? '')));
            $isMe     = ($currentUid === (int)$u['id']);
        ?>
        <a href="index.php?u=<?= urlencode($u['username']) ?>"
           class="portfolio-card anim <?= $uBusy ? 'busy' : '' ?>"
           data-name="<?= e(strtolower($uName)) ?>"
           data-skills="<?= e(strtolower($u['skills'] ?? '')) ?>"
           data-status="<?= $uBusy ? 'busy' : 'available' ?>">

            <!-- Photo header -->
            <div class="relative h-36 overflow-hidden" style="background:linear-gradient(135deg,<?= $uBusy ? '#1a0a08,#2d0f0f' : '#0a1020,#0f1a35' ?>);">
                <?php if ($uPhoto): ?>
                <img src="<?= e($uPhoto) ?>" alt="<?= e($uName) ?>"
                     class="w-full h-full object-cover opacity-60">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-5xl opacity-30">🧑</div>
                <?php endif; ?>
                <!-- Gradient overlay -->
                <div class="absolute inset-0" style="background:linear-gradient(to bottom, transparent 40%, rgba(0,0,0,0.6));"></div>
                <!-- Status dot -->
                <div class="absolute top-3 right-3 flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                     style="background:rgba(0,0,0,0.5); backdrop-filter:blur(8px); <?= $uBusy ? 'color:#ff6b4b;' : 'color:#00d2ff;' ?>">
                    <span class="w-2 h-2 rounded-full dot-pulse <?= $uBusy ? 'dot-busy' : 'dot-avail' ?>"></span>
                    <?= e($u['job_status']) ?>
                </div>
                <?php if ($isMe): ?>
                <div class="absolute top-3 left-3 px-2 py-0.5 rounded text-xs font-semibold"
                     style="background:rgba(0,210,255,0.2);border:1px solid rgba(0,210,255,0.4);color:#00d2ff;">You</div>
                <?php endif; ?>
            </div>

            <!-- Card body -->
<div class="p-5 flex flex-col flex-1">
    <div class="flex items-start justify-between mb-2">
        <div>
            <h3 style="font-family:'Playfair Display',serif; font-size:1.05rem; font-weight:700; line-height:1.3;">
                <?= e($uName) ?>
            </h3>
            <p class="text-xs opacity-40 mt-0.5">@<?= e($u['username']) ?></p>
        </div>
        <?php if ($u['project_count'] > 0): ?>
        <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0 mt-1"
              style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.4);">
            <?= (int)$u['project_count'] ?> proj
        </span>
        <?php endif; ?>
    </div>

    <?php if ($uBio): ?>
<p class="text-xs leading-relaxed mb-3 flex-1" style="color:rgba(255,255,255,0.5);"><?= e($uBio) ?></p>
<?php endif; ?>

<!-- Active Since -->
<?php if (!empty($u['active_since'])): ?>
<p class="text-xs mb-4" style="color:rgba(0,210,255,0.7);">
    Since <span class="font-medium"><?= e($u['active_since']) ?></span>
</p>
<?php endif; ?>
    
    <!-- Skill tags -->
    <?php if (!empty($uSkills)): ?>
    <div class="flex flex-wrap gap-1.5 mt-auto">
        <?php foreach (array_slice($uSkills, 0, 4) as $sk): ?>
        <span class="skill-tag"><?= e($sk) ?></span>
        <?php endforeach; ?>
        <?php if (count($uSkills) > 4): ?>
        <span class="skill-tag">+<?= count($uSkills) - 4 ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
            
            <!-- Footer -->
            <div class="px-5 pb-4 pt-2 flex items-center justify-between"
                 style="border-top:1px solid rgba(255,255,255,0.06);">
                <span class="text-xs opacity-30">View portfolio</span>
                <span class="text-xs font-bold" style="color:<?= $uBusy ? '#ff6b4b' : '#00d2ff' ?>;">→</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- No results -->
    <div id="no-results" class="text-center py-20 opacity-40">
        <p class="text-4xl mb-3">🔍</p>
        <p class="text-sm">No portfolios match your search.</p>
    </div>

    <?php else: ?>
    <div class="text-center py-24 opacity-40">
        <p class="text-5xl mb-4">🌱</p>
        <p class="text-sm">No portfolios yet. Be the first!</p>
        <a href="register.php" class="mt-4 inline-block text-sm underline" style="color:#00d2ff;">Create yours →</a>
    </div>
    <?php endif; ?>

</div>

<!-- Footer -->
<footer class="text-center py-8 text-xs opacity-20">
    Portfolio Engine · <?= date('Y') ?>
</footer>

<script>
let activeFilter = 'all';

function setFilter(filter, btn) {
    activeFilter = filter;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterCards();
}

function filterCards() {
    const query   = document.getElementById('searchInput').value.toLowerCase().trim();
    const cards   = document.querySelectorAll('#cardGrid .portfolio-card');
    let   visible = 0;

    cards.forEach(card => {
        const name   = card.dataset.name   || '';
        const skills = card.dataset.skills || '';
        const status = card.dataset.status || '';

        const matchSearch = !query || name.includes(query) || skills.includes(query);
        const matchFilter = activeFilter === 'all' || status === activeFilter;

        if (matchSearch && matchFilter) {
            card.style.display = 'flex';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    document.getElementById('no-results').style.display = visible === 0 ? 'block' : 'none';
}
</script>
</body>
</html>
