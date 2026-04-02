<?php
include 'db.php';
$portfolioUser = null;
if (!empty($_GET['u'])) {
    $portfolioUser = getUserByUsername($conn, trim($_GET['u']));
} elseif (!empty($_GET['id'])) {
    $portfolioUser = getUserById($conn, (int)$_GET['id']);
} elseif (isLoggedIn()) {
    $portfolioUser = getCurrentUser($conn);
}
// Landing page if no user found
if (!$portfolioUser):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family:'DM Sans',sans-serif; background: linear-gradient(135deg,#0f0c29,#302b63,#24243e); min-height:100vh; color:#f1f1f1; }
        .btn-main { background:linear-gradient(90deg,#00d2ff,#3a7bd5); color:white; border-radius:50px; padding:14px 32px; font-weight:600; transition:all .2s; display:inline-block; }
        .btn-main:hover { filter:brightness(1.1); transform:translateY(-2px); }
        .btn-sec { background:rgba(255,255,255,0.08); color:#f1f1f1; border-radius:50px; padding:14px 32px; font-weight:600; border:1px solid rgba(255,255,255,0.15); transition:all .2s; display:inline-block; }
        .btn-sec:hover { background:rgba(255,255,255,0.14); transform:translateY(-2px); }
        @keyframes fadeIn { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        .anim { animation:fadeIn .8s ease both; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-8 text-center">
    <div class="anim max-w-lg">
        <p class="text-xs font-semibold uppercase tracking-widest mb-5" style="color:#00d2ff;">Personal Brand Engine</p>
        <h1 style="font-family:'Playfair Display',serif; font-size:3.5rem; font-weight:900; line-height:1.1;" class="mb-5">
            Your portfolio,<br>your rules<span style="color:#00d2ff">.</span>
        </h1>
        <p class="text-lg mb-10 leading-relaxed" style="color:rgba(255,255,255,0.55);">
            Create a stunning personal portfolio in minutes.<br>Manage projects, toggle your status, and share your work.
        </p>
        <div class="flex gap-4 justify-center flex-wrap">
            <a href="register.php" class="btn-main">Get Started — Free</a>
            <a href="login.php" class="btn-sec">Sign In</a>
        </div>
        <a href="dashboard.php" class="block mt-8 text-sm font-semibold underline underline-offset-4 hover:opacity-80 transition-opacity" style="color:#00d2ff;">Browse portfolios →</a>
    </div>
</body>
</html>
<?php
    exit();
endif;
// ── Portfolio data ────────────────────────────────────────────────────────────
$uid = (int)$portfolioUser['id'];
$status = $portfolioUser['job_status'] ?? 'Available';
$isBusy = ($status === 'Busy');
$isOwner = isLoggedIn() && (getCurrentUserId() === $uid);
$skillList = array_filter(array_map('trim', explode(',', $portfolioUser['skills'] ?? '')));
if (empty($skillList)) $skillList = ['Design', 'Development'];
$socialLinks = [];
if (!empty($portfolioUser['github'])) $socialLinks['GitHub'] = $portfolioUser['github'];
if (!empty($portfolioUser['linkedin'])) $socialLinks['LinkedIn'] = $portfolioUser['linkedin'];
if (!empty($portfolioUser['dribbble'])) $socialLinks['Dribbble'] = $portfolioUser['dribbble'];
$projects = getUserProjects($conn, $uid);
$waNumber = preg_replace('/\D/', '', $portfolioUser['whatsapp'] ?? '');
$fullName = !empty($portfolioUser['full_name']) ? $portfolioUser['full_name'] : $portfolioUser['username'];
$bio = !empty($portfolioUser['bio']) ? $portfolioUser['bio'] : 'Welcome to my portfolio.';
$photoSrc = !empty($portfolioUser['photo']) ? $portfolioUser['photo'] : 'https://placehold.co/280x380/1a1a2e/555?text=Photo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($fullName) ?> · Portfolio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }
        <?php if ($isBusy): ?>
        :root { --glow: #ff4b2b; --glow2: #ff416c; }
        body { background: linear-gradient(135deg, #0f0c29, #302b63); color: #f1f1f1; }
        .card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,75,43,0.25); }
        .card:hover { box-shadow: 0 0 30px rgba(255,75,43,0.18); border-color: rgba(255,75,43,0.5); }
        .glow-shadow { box-shadow: 0 0 22px #ff4b2b88; }
        .img-ring { border-color: #ff4b2b; }
        .divider { border-color: rgba(255,75,43,0.2); }
        .modal-overlay { background: rgba(15,12,41,0.9); }
        .modal-card { background: #1a1a2e; border: 1px solid rgba(255,75,43,0.3); color: #f1f1f1; }
        .form-input { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,75,43,0.4); color: #f1f1f1; }
        .form-input::placeholder { color: rgba(255,255,255,0.3); }
        .form-input:focus { outline: none; border-color: #ff4b2b; box-shadow: 0 0 0 3px rgba(255,75,43,0.12); }
        .btn-primary { background: #ff4b2b; color: white; }
        .btn-primary:hover { background: #ff416c; }
        .status-pill { border-color: rgba(255,75,43,0.5); color: #ff4b2b; }
        .status-dot { background: #ff4b2b; }
        .nav-bg { background: rgba(15,12,41,0.82); border-bottom: 1px solid rgba(255,75,43,0.15); }
        .tag { background: rgba(255,75,43,0.1); border: 1px solid rgba(255,75,43,0.3); color: #ff6b4b; }
        .link-text { color: #ff6b4b; }
        .nav-switcher { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,75,43,0.25); color: #ff6b4b; }
        .nav-switcher:hover { background: rgba(255,75,43,0.12); border-color: rgba(255,75,43,0.5); }
        .fab-profile { background: #ff4b2b; box-shadow: 0 4px 20px rgba(255,75,43,0.4); }
        .fab-profile:hover { filter: brightness(1.1); }
        <?php else: ?>
        :root { --glow: #00d2ff; --glow2: #3a7bd5; }
        body { background: linear-gradient(135deg, #eef2ff, #dce8ff); color: #1a202c; }
        .card { background: rgba(255,255,255,0.72); border: 1px solid rgba(58,123,213,0.12); }
        .card:hover { box-shadow: 0 0 30px rgba(0,210,255,0.15); border-color: rgba(0,210,255,0.4); }
        .glow-shadow { box-shadow: 0 0 22px #00d2ff88; }
        .img-ring { border-color: #00d2ff; }
        .divider { border-color: rgba(58,123,213,0.15); }
        .modal-overlay { background: rgba(10,20,60,0.72); }
        .modal-card { background: #ffffff; border: 1px solid rgba(0,210,255,0.25); color: #1a202c; }
        .form-input { background: #f0f8ff; border: 1px solid rgba(0,210,255,0.35); color: #1a202c; }
        .form-input::placeholder { color: rgba(26,32,64,0.35); }
        .form-input:focus { outline: none; border-color: #00d2ff; box-shadow: 0 0 0 3px rgba(0,210,255,0.1); }
        .btn-primary { background: linear-gradient(90deg, #00d2ff, #3a7bd5); color: white; }
        .btn-primary:hover { filter: brightness(1.08); }
        .status-pill { border-color: rgba(0,210,255,0.5); color: #0099bb; }
        .status-dot { background: #00d2ff; }
        .nav-bg { background: rgba(238,242,255,0.85); border-bottom: 1px solid rgba(58,123,213,0.08); }
        .tag { background: rgba(0,210,255,0.1); border: 1px solid rgba(0,210,255,0.3); color: #0099bb; }
        .link-text { color: #3a7bd5; }
        .nav-switcher { background: rgba(255,255,255,0.5); border: 1px solid rgba(0,210,255,0.25); color: #3a7bd5; }
        .nav-switcher:hover { background: rgba(0,210,255,0.1); border-color: rgba(0,210,255,0.5); }
        .fab-profile { background: linear-gradient(135deg, #00d2ff, #3a7bd5); box-shadow: 0 4px 20px rgba(0,210,255,0.4); }
        .fab-profile:hover { filter: brightness(1.1); }
        <?php endif; ?>

        /* ── Floating profile button — always visible for owner ── */
        .fab-profile {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 50;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            transition: all .25s;
            letter-spacing: .02em;
        }
        .fab-profile:hover { transform: translateY(-3px) scale(1.04); }
        @media (max-width: 640px) {
            .fab-profile { padding: 11px 18px; font-size: 12.5px; }
        }

        .backdrop { backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
        .card { border-radius: 18px; backdrop-filter: blur(10px); transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s; cursor: pointer; }
        .card:hover { transform: translateY(-7px); }
        .img-ring { border-width: 4px; border-style: solid; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
        @keyframes slideUp { from{opacity:0;transform:translateY(28px)} to{opacity:1;transform:translateY(0)} }
        @keyframes pulseDot{ 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.6;transform:scale(1.25)} }
        .anim-fade { animation: fadeIn 0.7s ease both; }
        .anim-slide { animation: slideUp 0.65s ease both; }
        .anim-pulse { animation: pulseDot 2s infinite; }
        #modal { display: none; } #modal.open { display: flex; }
        #statusModal { display: none; } #statusModal.open { display: flex; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: var(--glow); border-radius: 10px; }
        <?php if (!empty($_GET['welcome'])): ?>
        .welcome-banner { display: flex; }
        <?php else: ?>
        .welcome-banner { display: none; }
        <?php endif; ?>

        @media (max-width: 640px) {
            .nav-bg .max-w-6xl { padding-left: 16px; padding-right: 16px; }
            .flex.items-center.gap-16 { flex-direction: column; text-align: center; gap: 32px; }
            .img-ring.w-44.h-60 { width: 160px; height: 200px; }
            .grid.grid-cols-1.md\\:grid-cols-2 { grid-template-columns: 1fr; gap: 20px; }
            .flex.gap-5 { justify-content: center; }
            .nav-switcher { display: inline-flex !important; font-size: 13px; padding: 8px 14px; }
        }
    </style>
</head>
<body class="min-h-screen">
<!-- Welcome banner (shows after onboarding) -->
<div class="welcome-banner items-center justify-center px-5 py-3 text-sm font-medium"
     style="background: linear-gradient(90deg,#00d2ff22,#3a7bd522); border-bottom: 1px solid rgba(0,210,255,0.3); color: var(--glow);">
    🎉 Your portfolio is live! Share it at:
    <code class="ml-2 px-2 py-0.5 rounded text-xs" style="background:rgba(0,0,0,0.1);">
        <?= htmlspecialchars((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/index.php?u=' . urlencode($portfolioUser['username'])) ?>
    </code>
</div>

<!-- Navbar -->
<nav class="nav-bg backdrop sticky top-0 z-40 w-full">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
        <!-- LEFT: Logo + Dashboard -->
        <div class="flex items-center gap-3">
            <a href="javascript:history.back()" 
               class="flex items-center justify-center w-8 h-8 rounded-full hover:opacity-80 transition-opacity"
               style="background:rgba(128,128,128,0.12);" title="Go back">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <a href="index.php?u=<?= urlencode($portfolioUser['username']) ?>"
               style="font-family:'Playfair Display',serif; font-size:1.35rem; font-weight:900;">
                <?= e($fullName) ?><span style="color:var(--glow)">.</span>
            </a>
            
            <?php if ($isOwner): ?>
            <!-- Dashboard button - visible on both mobile and desktop, but styled nicely -->
            <a href="dashboard.php" 
               class="nav-switcher text-sm px-4 py-2 bg-white/10 hover:bg-white/20 rounded-full transition-all hidden sm:inline-flex">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Status + buttons -->
        <div class="flex items-center gap-3">
            <!-- Status pill -->
            <?php if ($isOwner): ?>
            <a href="admin_action.php?quick_toggle=1&ref=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
               class="status-pill flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest border hover:scale-105 transition-all"
               style="cursor:pointer; text-decoration:none;">
                <span class="status-dot anim-pulse inline-block w-2 h-2 rounded-full"></span>
                <?= e($status) ?>
            </a>
            <?php else: ?>
            <span class="status-pill flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest border">
                <span class="status-dot anim-pulse inline-block w-2 h-2 rounded-full"></span>
                <?= e($status) ?>
            </span>
            <?php endif; ?>

            <?php if ($isOwner): ?>
            <a href="logout.php" class="text-xs opacity-40 hover:opacity-80 transition-opacity font-medium hidden sm:inline">Log out</a>
            <?php else: ?>
            <?php if (!empty($waNumber)): ?>
            <button onclick="openModal()" 
                class="btn-primary text-white text-sm font-semibold px-5 py-2.5 rounded-full shadow-lg transition-transform hover:scale-105">
                Work With Me
            </button>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero / About -->
<section id="about" class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 pb-16 md:pt-20 md:pb-20">
    <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16">
        <div class="flex-shrink-0 anim-fade" style="animation-delay:.1s">
            <div class="relative">
                <div class="img-ring absolute inset-0 rounded-2xl scale-105 opacity-30 anim-pulse" style="animation-duration:3s;"></div>
                <img src="<?= e($photoSrc) ?>" alt="<?= e($fullName) ?>"
                     class="img-ring w-44 h-60 md:w-52 md:h-72 rounded-2xl object-cover relative z-10 glow-shadow">
            </div>
        </div>
        <div class="text-center md:text-left">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] mb-3 anim-fade" style="animation-delay:.2s; color:var(--glow)">Hello, I'm</p>
            <h1 style="font-family:'Playfair Display',serif; font-size:2.8rem; md:font-size:3rem; font-weight:900; line-height:1.1; animation-delay:.25s;" class="mb-5 anim-slide">
                <?= e($fullName) ?>
            </h1>
            <p class="text-base md:text-lg leading-relaxed max-w-xl opacity-70 anim-slide" style="animation-delay:.35s;">
                <?= e($bio) ?>
            </p>
            <div class="flex flex-wrap gap-2 mt-6 justify-center md:justify-start anim-fade" style="animation-delay:.5s;">
                <?php foreach ($skillList as $sk): ?>
                <span class="tag text-xs px-3 py-1 rounded-full font-medium"><?= e($sk) ?></span>
                <?php endforeach; ?>
                <?php if (!empty($portfolioUser['active_since'])): ?>
                <span class="tag text-xs px-3 py-1 rounded-full font-medium" style="opacity:0.7;">
                    📅 Since <?= e($portfolioUser['active_since']) ?>
                </span>
                <?php endif; ?>
            </div>
            <?php if ($socialLinks): ?>
            <div class="flex gap-5 mt-8 justify-center md:justify-start anim-fade" style="animation-delay:.6s;">
                <?php foreach ($socialLinks as $name => $url): ?>
                <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"
                   class="link-text text-sm font-medium hover:opacity-80 underline underline-offset-4 transition-opacity">
                    <?= e($name) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Projects section remains exactly the same -->
<section id="projects" class="max-w-6xl mx-auto px-4 sm:px-6 pb-28">
    <div class="divider border-t mb-16"></div>
    <div class="flex items-end justify-between mb-12">
        <h2 style="font-family:'Playfair Display',serif; font-size:2rem; md:font-size:2.2rem; font-weight:700;">Latest Projects</h2>
        <span class="text-xs opacity-35 font-medium uppercase tracking-wider">
            <?= count($projects) ?> project<?= count($projects) !== 1 ? 's' : '' ?>
        </span>
    </div>
    <?php if (!empty($projects)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
        <?php $i = 1; foreach ($projects as $row):
            $pUrl = !empty($row['link']) ? $row['link'] : '';
            $pHost = $pUrl ? (parse_url($pUrl, PHP_URL_HOST) ?: $pUrl) : '';
            $pThumb = !empty($row['image'])
                ? $row['image']
                : ($pUrl
                    ? 'https://s0.wp.com/mshots/v1/' . urlencode($pUrl) . '?w=700&h=420'
                    : 'https://placehold.co/700x420/e2e8f0/94a3b8?text=No+Preview');
        ?>
        <a href="<?= $pUrl ? e($pUrl) : '#' ?>"
           <?= $pUrl ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
           class="card overflow-hidden group anim-slide flex flex-col"
           style="animation-delay:<?= 0.1*$i ?>s; padding:0; text-decoration:none; color:inherit;">
            <div class="relative overflow-hidden" style="height:200px; background:#111;">
                <img src="<?= e($pThumb) ?>" alt="Preview of <?= e($row['title']) ?>"
                     loading="lazy"
                     onerror="this.src='https://placehold.co/700x420/e2e8f0/94a3b8?text=Preview'"
                     class="w-full h-full object-cover object-top transition-transform duration-700 group-hover:scale-105">
                <div class="absolute inset-0 bg-black opacity-0 group-hover:opacity-30 transition-opacity duration-300"></div>
                <?php if ($pUrl): ?>
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <span class="flex items-center gap-2 px-4 py-2 rounded-full text-white text-xs font-bold uppercase tracking-widest"
                          style="background:var(--glow); box-shadow:0 0 20px var(--glow)88;">
                        ↗ Visit Project
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <div class="p-5 flex flex-col flex-1">
                <h3 style="font-family:'Playfair Display',serif; font-size:1.1rem; font-weight:700;" class="mb-1.5 leading-snug">
                    <?= e($row['title']) ?>
                </h3>
                <p class="text-sm leading-relaxed opacity-60 flex-1"><?= e($row['description'] ?? '') ?></p>
                <div class="mt-4 pt-4 flex items-center justify-between" style="border-top:1px solid rgba(128,128,128,0.12);">
                    <?php if ($pUrl): ?>
                    <span class="text-xs opacity-40 truncate max-w-[60%]"><?= e($pHost) ?></span>
                    <span class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-widest" style="color:var(--glow);">Open ↗</span>
                    <?php else: ?>
                    <span class="text-xs opacity-25 uppercase tracking-wider">No link</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php $i++; endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-16 opacity-80">
        <p class="text-5xl mb-4">📁</p>
        <p class="text-sm font-medium">No projects yet.</p>
        <?php if ($isOwner): ?>
        <a href="admin.php?tab=projects" class="mt-4 inline-block text-sm underline" style="color:var(--glow);">Add your first project →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>

<!-- Work With Me Modal and Footer remain exactly the same as your original code -->
<?php if (!empty($waNumber)): ?>
<div id="modal" class="modal-overlay fixed inset-0 z-50 items-center justify-center p-4" onclick="outsideClose(event)">
    <div class="modal-card backdrop rounded-2xl w-full max-w-lg p-8 shadow-2xl relative anim-slide">
        <button onclick="closeModal()" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full text-lg opacity-40 hover:opacity-100">✕</button>
        <h2 style="font-family:'Playfair Display',serif; font-size:1.8rem; font-weight:700;" class="mb-1">Work With Me</h2>
        <p class="text-sm opacity-45 mb-7">Tell me about your project — I'll reply within 24 hours.</p>
        <form id="waForm" onsubmit="sendToWhatsApp(event)" class="flex flex-col gap-4">
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider opacity-50 block mb-1.5">Name</label>
                <input id="waName" type="text" placeholder="Your full name" required class="form-input w-full px-4 py-3 rounded-xl text-sm">
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider opacity-50 block mb-1.5">Email</label>
                <input id="waEmail" type="email" placeholder="you@example.com" required class="form-input w-full px-4 py-3 rounded-xl text-sm">
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider opacity-50 block mb-1.5">Project Details</label>
                <textarea id="waMessage" rows="4" placeholder="Describe your project, timeline, and budget..."
                    class="form-input w-full px-4 py-3 rounded-xl text-sm resize-none"></textarea>
            </div>
            <button type="submit"
                class="btn-primary font-semibold py-3 rounded-xl transition-all hover:scale-[1.02] flex items-center justify-center gap-2">
                Send via WhatsApp
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<footer class="text-center py-8 text-xs opacity-25">
    © <?= date('Y') ?> <?= e($fullName) ?> · Built with ♥ ·
    <a href="dashboard.php" class="underline hover:opacity-60 transition-opacity">Browse all portfolios</a>
</footer>

<?php if ($isOwner): ?>
<a href="admin.php?tab=profile" class="fab-profile">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
    </svg>
    Edit Profile
</a>
<?php endif; ?>

<script>
const WA_NUMBER = '<?= e($waNumber) ?>';
function openModal() { document.getElementById('modal').classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal() { document.getElementById('modal').classList.remove('open'); document.body.style.overflow=''; }
function outsideClose(e) { if (e.target.id==='modal') closeModal(); }
document.addEventListener('keydown', e => { if (e.key==='Escape') { closeModal(); } });
function sendToWhatsApp(e) {
    e.preventDefault();
    const name = document.getElementById('waName').value.trim();
    const email = document.getElementById('waEmail').value.trim();
    const message = document.getElementById('waMessage').value.trim();
    const text = `Hi! I found your portfolio and I'd love to work with you 👋\n\n*Name:* ${name}\n*Email:* ${email}\n*Project Details:*\n${message}`;
    window.open(`https://wa.me/${WA_NUMBER}?text=${encodeURIComponent(text)}`, '_blank');
    document.getElementById('waForm').reset();
    setTimeout(closeModal, 400);
}
setTimeout(() => {
    const b = document.querySelector('.welcome-banner');
    if (b) { b.style.transition = 'opacity 1s'; b.style.opacity = '0'; setTimeout(() => b.remove(), 1000); }
}, 8000);
</script>
</body>
</html>