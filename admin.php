<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db.php';
requireLogin();
$user = getCurrentUser($conn);
$uid = (int)$user['id'];
$projects = getUserProjects($conn, $uid);
$tab = $_GET['tab'] ?? 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel · <?= e($user['username']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: linear-gradient(135deg,#0f0c29,#302b63); color: #f1f1f1; min-height:100vh; }
        .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; backdrop-filter: blur(12px); }
        .input { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); color: #f1f1f1; border-radius: 10px; padding: 12px 16px; width: 100%; font-size: 15px; transition: all .2s; }
        .input:focus { outline: none; border-color: #00d2ff; box-shadow: 0 0 0 3px rgba(0,210,255,0.12); }
        .btn-primary { background: linear-gradient(90deg,#00d2ff,#3a7bd5); color: white; border-radius: 10px; padding: 13px 24px; font-weight: 600; transition: all .2s; }
        .btn-primary:hover { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-ghost { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #f1f1f1; border-radius: 8px; padding: 10px 16px; font-size: 13px; }
        .tab-link { padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 500; transition: all .2s; opacity: 0.5; white-space: nowrap; }
        .tab-link.active { background: rgba(0,210,255,0.15); color: #00d2ff; opacity: 1; border: 1px solid rgba(0,210,255,0.3); }
        .project-row:hover { background: rgba(255,255,255,0.03); }
        label { font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:rgba(255,255,255,0.4); display:block; margin-bottom:6px; }
        .gender-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 14px; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; font-size: 14px; color: rgba(255,255,255,0.6); cursor: pointer; transition: all .2s; }
        .gender-btn:hover { border-color: rgba(0,210,255,0.4); color: #00d2ff; }
        .gender-btn.active { background: rgba(0,210,255,0.12); border-color: #00d2ff; color: #00d2ff; }

        /* Phone Friendly Improvements */
        @media (max-width: 640px) {
            .card { margin: 0 8px; padding: 20px 16px; }
            .grid-cols-1.md\\:grid-cols-2 { grid-template-columns: 1fr !important; }
            .flex.items-center.gap-6 { flex-direction: column; align-items: flex-start; }
            .btn-primary, .btn-ghost { width: 100%; text-align: center; }
            .flex.gap-3.mt-4 { flex-direction: column; gap: 12px; }
        }
    </style>
</head>
<body>
<nav class="sticky top-0 z-40 w-full" style="background:rgba(15,12,41,0.9);border-bottom:1px solid rgba(255,255,255,0.07);backdrop-filter:blur(16px);">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <span style="font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:900;">Admin Panel<span style="color:#00d2ff">.</span></span>
        <div class="flex items-center gap-3">
            <a href="index.php?u=<?= urlencode($user['username']) ?>" class="btn-ghost text-xs">View Portfolio</a>
            <a href="logout.php" class="btn-danger text-xs">Log Out</a>
        </div>
    </div>
</nav>

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
    <?php if (isset($_GET['saved'])): ?>
    <div class="anim mb-6 px-5 py-4 rounded-xl text-sm" style="background:rgba(0,255,150,0.08);border:1px solid rgba(0,255,150,0.25);color:#4ade80;">
        ✓ Changes saved successfully.
    </div>
    <?php endif; ?>

    <!-- Tabs - scrollable on mobile -->
    <div class="flex gap-2 mb-8 overflow-x-auto pb-2 scrollbar-hide">
        <a href="?tab=profile" class="tab-link <?= $tab==='profile' ? 'active' : '' ?>">Profile</a>
        <a href="?tab=projects" class="tab-link <?= $tab==='projects' ? 'active' : '' ?>">Projects</a>
        <a href="?tab=status" class="tab-link <?= $tab==='status' ? 'active' : '' ?>">Status</a>
    </div>

    <!-- PROFILE TAB -->
    <?php if ($tab === 'profile'): ?>
    <div class="anim">
        <form method="POST" action="admin_action.php" enctype="multipart/form-data" class="card p-6 sm:p-8 flex flex-col gap-6">
            <h2 style="font-family:'Playfair Display',serif; font-size:1.5rem; font-weight:800;">Edit Profile</h2>
           
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Photo Upload -->
                <div class="md:col-span-2 flex flex-col sm:flex-row items-center gap-6">
                    <?php if (!empty($user['photo'])): ?>
                        <img src="<?= e($user['photo']) ?>" alt="" class="w-20 h-20 rounded-2xl object-cover flex-shrink-0" id="photoPreview" style="border:2px solid rgba(0,210,255,0.4);">
                    <?php else: ?>
                        <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-3xl overflow-hidden flex-shrink-0" id="photoPreview"
                             style="background:rgba(0,210,255,0.08);border:2px dashed rgba(0,210,255,0.3);">🧑</div>
                    <?php endif; ?>
                    <div class="flex-1 w-full">
                        <label>Profile Photo</label>
                        <input class="input" type="file" name="photo" accept="image/*" style="padding:7px 14px;" onchange="previewPhoto(this)">
                        <p class="text-xs mt-1" style="color:rgba(255,255,255,0.3);">JPG, PNG, WebP · max ~2MB</p>
                    </div>
                </div>

                <div>
                    <label>Display Name</label>
                    <input class="input" type="text" name="full_name" value="<?= e($user['full_name'] ?? '') ?>">
                </div>

                <div>
                    <label>Active Since</label>
                    <input class="input" type="text" name="active_since" value="<?= e($user['active_since'] ?? '') ?>" placeholder="2022 or March 2022">
                </div>

                <!-- Gender -->
                <div class="md:col-span-2">
                    <label>Gender</label>
                    <input type="hidden" id="selected_gender" name="gender" value="<?= e($user['gender'] ?? '') ?>">
                    <div class="flex gap-3">
                        <button type="button" class="gender-btn <?= ($user['gender'] ?? '') === 'Male' ? 'active' : '' ?>" id="gender-male" onclick="selectGender('Male', this)">
                            <span>♂</span> Male
                        </button>
                        <button type="button" class="gender-btn <?= ($user['gender'] ?? '') === 'Female' ? 'active' : '' ?>" id="gender-female" onclick="selectGender('Female', this)">
                            <span>♀</span> Female
                        </button>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label>Short Bio</label>
                    <textarea class="input" name="bio" rows="3"><?= e($user['bio'] ?? '') ?></textarea>
                </div>

                <!-- Single Skill -->
                <div class="md:col-span-2">
                    <label>Main Skill / Role</label>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="hidden" id="selected_skill" name="skills" value="<?= e($user['skills'] ?? '') ?>">
                        <div id="skill_display" class="flex-1 px-4 py-3 bg-[rgba(255,255,255,0.07)] border border-[rgba(255,255,255,0.15)] rounded-2xl text-sm">
                            <?= $user['skills'] ? '● ' . e($user['skills']) : 'No skill selected' ?>
                        </div>
                        <button type="button" onclick="showSkillModal()"
                                class="px-6 py-3 bg-[rgba(0,210,255,0.15)] hover:bg-[rgba(0,210,255,0.25)] text-[#00d2ff] rounded-2xl text-sm font-medium whitespace-nowrap">
                            + Change
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 mt-4">
                <button type="submit" name="update_profile" class="btn-primary">Save Profile</button>
                <a href="index.php?u=<?= urlencode($user['username']) ?>" class="btn-ghost text-center">Preview Portfolio</a>
            </div>
        </form>
    </div>

    <!-- Skill Modal -->
    <div id="skillModal" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 p-4">
        <div class="bg-[#1a1a2e] rounded-3xl p-6 sm:p-8 max-w-md w-full max-h-[85vh] overflow-y-auto">
            <h3 class="text-xl font-bold mb-6">Choose your main skill/role</h3>
            <div class="grid grid-cols-2 gap-3" id="skillOptions"></div>
            <button onclick="hideSkillModal()" class="mt-8 w-full py-4 text-sm text-gray-400 hover:text-white">Cancel</button>
        </div>
    </div>

    <?php elseif ($tab === 'projects'): ?>
    <!-- PROJECTS TAB - unchanged except minor mobile spacing -->
    <div class="anim flex flex-col gap-6">
        <?php if (isset($_GET['added'])): ?>
        <div class="px-5 py-3 rounded-xl text-sm" style="background:rgba(0,255,150,0.08);border:1px solid rgba(0,255,150,0.25);color:#4ade80;">✓ Project added.</div>
        <?php elseif (isset($_GET['deleted'])): ?>
        <div class="px-5 py-3 rounded-xl text-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;">Project deleted.</div>
        <?php endif; ?>

        <form method="POST" action="admin_action.php" enctype="multipart/form-data" class="card p-6 sm:p-7 flex flex-col gap-4">
            <h2 style="font-family:'Playfair Display',serif; font-size:1.3rem; font-weight:800;">Add New Project</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label>Project Title *</label>
                    <input class="input" type="text" name="title" placeholder="My Awesome Project" required>
                </div>
                <div>
                    <label>Live URL</label>
                    <input class="input" type="url" name="url" placeholder="https://yourproject.com">
                </div>
                <div class="md:col-span-2">
                    <label>Description</label>
                    <textarea class="input" name="description" rows="3" placeholder="What this project is about..."></textarea>
                </div>
                <div class="md:col-span-2">
                    <label>Screenshot / Preview Image</label>
                    <input class="input" type="file" name="screenshot" accept="image/*" style="padding:7px 14px;">
                    <p class="text-xs mt-1" style="color:rgba(255,255,255,0.3);">Leave empty to auto-generate from URL.</p>
                </div>
            </div>
            <div><button type="submit" name="add_project" class="btn-primary">Add Project</button></div>
        </form>

        <?php if (!empty($projects)): ?>
        <div class="card overflow-hidden">
            <div class="px-6 sm:px-7 py-5" style="border-bottom:1px solid rgba(255,255,255,0.07);">
                <p style="font-family:'Playfair Display',serif; font-size:1rem; font-weight:700;">
                    Your Projects (<?= count($projects) ?>)
                </p>
            </div>
            <?php foreach ($projects as $proj): ?>
            <div class="project-row px-6 sm:px-7 py-5 transition-colors" style="border-bottom:1px solid rgba(255,255,255,0.06);">
                <div class="flex items-start gap-5">
                    <div class="flex-shrink-0 w-24 h-16 rounded-xl overflow-hidden" style="background:#111;border:1px solid rgba(255,255,255,0.1);">
                        <?php
                        $pUrl = !empty($proj['link']) ? $proj['link'] : '';
                        $pShot = !empty($proj['image']) ? $proj['image'] : '';
                        $pThumb = $pShot ?: ($pUrl ? 'https://s0.wp.com/mshots/v1/' . urlencode($pUrl) . '?w=400&h=240' : 'https://placehold.co/400x240/1a1a2e/555?text=No+Preview');
                        ?>
                        <img src="<?= e($pThumb) ?>" alt="" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm truncate"><?= e($proj['title']) ?></p>
                        <p class="text-xs mt-1 opacity-50"><?= e(mb_strimwidth($proj['description'] ?? '', 0, 80, '…')) ?></p>
                        <?php if ($pUrl): ?>
                        <a href="<?= e($pUrl) ?>" target="_blank" class="text-xs mt-1 inline-block opacity-50 hover:opacity-100" style="color:#00d2ff;">
                            ↗ <?= e(parse_url($pUrl, PHP_URL_HOST) ?: $pUrl) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <button onclick="toggleEdit(<?= (int)$proj['id'] ?>)" class="btn-ghost">Edit</button>
                        <form method="POST" action="admin_action.php" onsubmit="return confirm('Delete this project?')">
                            <input type="hidden" name="project_id" value="<?= (int)$proj['id'] ?>">
                            <button type="submit" name="delete_project" class="btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card p-8 text-center opacity-40">
            <p class="text-4xl mb-3">📁</p>
            <p class="text-sm">No projects yet. Add your first one above!</p>
        </div>
        <?php endif; ?>
    </div>

    <?php elseif ($tab === 'status'): ?>
    <div class="anim card p-8 max-w-sm mx-auto">
        <h2 style="font-family:'Playfair Display',serif; font-size:1.5rem; font-weight:800;" class="mb-6">Availability</h2>
        <p>Status tab content (you can restore later)</p>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleEdit(id) {
    const el = document.getElementById('edit-' + id);
    if (el) el.classList.toggle('hidden');
}
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover rounded-2xl">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

const skillsList = ["Web Developer","UI/UX Designer","Architecture","Visual Communication Design","Cybersecurity Specialist","Data Analyst"];

function showSkillModal() {
    let html = '';
    skillsList.forEach(s => {
        html += `<button onclick="selectSkill('${s}')" class="w-full text-left px-5 py-3 bg-white/10 hover:bg-cyan-500/20 rounded-xl mb-2">${s}</button>`;
    });
    document.getElementById('skillOptions').innerHTML = html;
    document.getElementById('skillModal').classList.remove('hidden');
    document.getElementById('skillModal').classList.add('flex');
}
function hideSkillModal() {
    document.getElementById('skillModal').classList.add('hidden');
    document.getElementById('skillModal').classList.remove('flex');
}
function selectSkill(skill) {
    document.getElementById('selected_skill').value = skill;
    document.getElementById('skill_display').innerHTML = '● ' + skill;
    hideSkillModal();
}

function selectGender(value, el) {
    document.getElementById('selected_gender').value = value;
    document.querySelectorAll('.gender-btn').forEach(btn => btn.classList.remove('active'));
    el.classList.add('active');
}
</script>
</body>
</html>