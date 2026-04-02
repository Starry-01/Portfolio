<?php
include 'db.php';
requireLogin();
$user = getCurrentUser($conn);
$uid = (int)$user['id'];

if (isProfileComplete($user)) {
    header("Location: index.php?u=" . urlencode($user['username']));
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name    = trim($_POST['full_name']    ?? '');
    $bio          = trim($_POST['bio']          ?? '');
    $skill        = trim($_POST['skill']        ?? '');
    $active_since = trim($_POST['active_since'] ?? '');
    $gender       = trim($_POST['gender']       ?? '');

    if (!$full_name || !$bio || !$skill || !$gender) {
        $error = 'Please fill in Name, Bio, select a Skill, and choose a Gender.';
    } else {
        $photo = handleUpload('photo', 'photo', $uid);
        if ($photo) {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, bio=?, skills=?, active_since=?, photo=?, gender=? WHERE id=?");
            $stmt->bind_param("ssssssi", $full_name, $bio, $skill, $active_since, $photo, $gender, $uid);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, bio=?, skills=?, active_since=?, gender=? WHERE id=?");
            $stmt->bind_param("sssssi", $full_name, $bio, $skill, $active_since, $gender, $uid);
        }
        if ($stmt->execute()) {
            header("Location: index.php?u=" . urlencode($user['username']) . "&welcome=1");
            exit();
        } else {
            $error = "Database error: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Build Your Portfolio · <?= e($user['username']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #f1f1f1; }

        /* Progress bar */
        .progress-track { background: rgba(255,255,255,0.08); border-radius: 999px; height: 4px; }
        .progress-fill  { background: linear-gradient(90deg, #00d2ff, #3a7bd5); border-radius: 999px; height: 4px; transition: width .4s ease; }

        /* Card */
        .card { background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; }

        /* Inputs */
        .input { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); color: #f1f1f1; border-radius: 12px; padding: 12px 16px; width: 100%; font-size: 14px; transition: all .2s; font-family: 'DM Sans', sans-serif; }
        .input::placeholder { color: rgba(255,255,255,0.25); }
        .input:focus { outline: none; border-color: #00d2ff; box-shadow: 0 0 0 3px rgba(0,210,255,0.12); background: rgba(255,255,255,0.1); }

        /* Label */
        label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,0.4); display: block; margin-bottom: 6px; }
        .required { color: #00d2ff; }

        /* Gender buttons */
        .gender-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; font-size: 14px; color: rgba(255,255,255,0.5); cursor: pointer; transition: all .2s; }
        .gender-btn:hover { border-color: rgba(0,210,255,0.4); color: #00d2ff; }
        .gender-btn.active { background: rgba(0,210,255,0.12); border-color: #00d2ff; color: #00d2ff; font-weight: 600; }

        /* Skill pill */
        .skill-pill { background: rgba(0,210,255,0.12); border: 1px solid rgba(0,210,255,0.35); color: #00d2ff; padding: 8px 18px; border-radius: 999px; font-size: 13px; cursor: pointer; transition: all .2s; font-weight: 500; }
        .skill-pill:hover { background: rgba(0,210,255,0.22); }

        /* Submit button */
        .btn-launch { background: linear-gradient(90deg, #00d2ff, #3a7bd5); color: white; border-radius: 14px; padding: 15px 32px; font-weight: 700; font-size: 15px; transition: all .25s; letter-spacing: .02em; }
        .btn-launch:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,210,255,0.3); }

        /* Preview card */
        .preview-card { background: rgba(255,255,255,0.72); border-radius: 20px; overflow: hidden; border: 1px solid rgba(58,123,213,0.15); }
        .preview-photo { width: 64px; height: 80px; border-radius: 12px; object-fit: cover; border: 3px solid #00d2ff; }
        .preview-tag { background: rgba(0,210,255,0.12); border: 1px solid rgba(0,210,255,0.3); color: #0099bb; padding: 3px 12px; border-radius: 999px; font-size: 11px; font-weight: 600; }

        /* Skill modal */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); align-items: center; justify-content: center; z-index: 1000; }
        .modal.open { display: flex; }

        /* Perks */
        .perk { display: flex; align-items: center; gap: 10px; font-size: 13px; color: rgba(255,255,255,0.55); }
        .perk-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; background: rgba(0,210,255,0.1); flex-shrink: 0; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        .anim { animation: fadeUp .6s ease both; }
    </style>
</head>
<body class="min-h-screen py-10 px-4">

<!-- Top bar -->
<div class="max-w-5xl mx-auto mb-8 flex items-center justify-between anim">
    <span style="font-family:'Playfair Display',serif; font-size:1.3rem; font-weight:900;">
        Profile<span style="color:#00d2ff">.</span>
    </span>
    <div class="flex items-center gap-4">
        <span class="text-xs font-semibold uppercase tracking-widest" style="color:rgba(255,255,255,0.3);">
            Profile Setup
        </span>
        <div class="progress-track w-28">
            <div class="progress-fill" id="progressBar" style="width:10%"></div>
        </div>
        <span class="text-xs font-bold" style="color:#00d2ff;" id="progressPct">10%</span>
    </div>
</div>

<!-- Main layout -->
<div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-5 gap-6">

    <!-- LEFT: Form (3/5) -->
    <div class="lg:col-span-3 anim" style="animation-delay:.1s">
        <div class="card p-8">

            <!-- Header -->
            <div class="mb-7">
                <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:#00d2ff;">One last step</p>
                <h1 style="font-family:'Playfair Display',serif; font-size:1.75rem; font-weight:900;" class="mb-1">
                    Build your portfolio<span style="color:#00d2ff">.</span>
                </h1>
                <p style="color:rgba(255,255,255,0.4); font-size:13px;">Takes less than 60 seconds. Your page goes live instantly.</p>
            </div>

            <?php if ($error): ?>
            <div class="mb-5 px-4 py-3 rounded-xl text-sm" style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.3);color:#f87171;">
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-5" id="setupForm">

                <!-- Photo + Name row -->
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 flex flex-col items-center gap-2">
                        <div id="photoPreview"
                             class="w-20 h-24 rounded-2xl flex items-center justify-center text-3xl overflow-hidden"
                             style="background:rgba(0,210,255,0.08); border:2px dashed rgba(0,210,255,0.3);">
                            🧑
                        </div>
                        <label class="cursor-pointer text-center block text-xs" style="color:rgba(0,210,255,0.8);">
                            <input type="file" name="photo" accept="image/*" class="hidden" onchange="previewPhoto(this)" id="photoInput">
                            Upload photo
                        </label>
                    </div>
                    <div class="flex-1">
                        <label>Display Name <span class="required">*</span></label>
                        <input class="input" type="text" name="full_name" id="fieldName"
                               placeholder="Your Full Name"
                               value="<?= e($_POST['full_name'] ?? '') ?>"
                               oninput="updatePreview()" required>
                    </div>
                </div>

                <!-- Gender -->
                <div>
                    <label>Gender <span class="required">*</span></label>
                    <input type="hidden" id="selected_gender" name="gender" value="<?= e($_POST['gender'] ?? '') ?>">
                    <div class="flex gap-3">
                        <button type="button" class="gender-btn <?= ($_POST['gender'] ?? '') === 'Male' ? 'active' : '' ?>"
                                onclick="selectGender('Male', this)">♂ Male</button>
                        <button type="button" class="gender-btn <?= ($_POST['gender'] ?? '') === 'Female' ? 'active' : '' ?>"
                                onclick="selectGender('Female', this)">♀ Female</button>
                    </div>
                </div>

                <!-- Bio -->
                <div>
                    <label>Short Bio <span class="required">*</span></label>
                    <textarea class="input" name="bio" id="fieldBio" rows="3"
                              placeholder="One punchy sentence about who you are..."
                              oninput="updatePreview()" required><?= e($_POST['bio'] ?? '') ?></textarea>
                </div>

                <!-- Skill -->
                <div>
                    <label>Skill / Role <span class="required">*</span></label>
                    <div class="flex items-center gap-3">
                        <input type="hidden" id="selected_skill" name="skill" value="<?= e($_POST['skill'] ?? '') ?>">
                        <div id="skill_display" class="flex-1 px-4 py-3 rounded-xl text-sm min-h-[46px] flex items-center"
                             style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.15);">
                            <?php if (!empty($_POST['skill'])): ?>
                                <span style="color:#00d2ff;">● <?= e($_POST['skill']) ?></span>
                            <?php else: ?>
                                <span style="color:rgba(255,255,255,0.25);">No skill selected</span>
                            <?php endif; ?>
                        </div>
                        <button type="button" onclick="showSkillModal()" class="skill-pill flex items-center gap-1">
                            <span class="text-lg leading-none">+</span> Add
                        </button>
                    </div>
                </div>

                <!-- Active Since -->
                <div>
                    <label>Active Since <span style="color:rgba(255,255,255,0.2);">(optional)</span></label>
                    <input class="input" type="text" name="active_since"
                           placeholder="2022 or March 2022" maxlength="20"
                           value="<?= e($_POST['active_since'] ?? '') ?>">
                </div>

                <!-- Submit -->
                <div class="pt-2 flex items-center justify-between">
                    <a href="logout.php" class="text-xs" style="color:rgba(255,255,255,0.25);">Cancel & log out</a>
                    <button type="submit" class="btn-launch flex items-center gap-2">
                        🚀 Launch My Portfolio
                    </button>
                </div>

            </form>
        </div>

        <!-- Perks below form -->
        <div class="mt-4 grid grid-cols-3 gap-3">
            <div class="perk card px-4 py-3">
                <span class="perk-icon">⚡</span> Goes live instantly
            </div>
            <div class="perk card px-4 py-3">
                <span class="perk-icon">🎨</span> Beautiful by default
            </div>
            <div class="perk card px-4 py-3">
                <span class="perk-icon">✏️</span> Edit anytime
            </div>
        </div>
    </div>

    <!-- RIGHT: Live Preview (2/5) -->
    <div class="lg:col-span-2 anim" style="animation-delay:.2s">
        <div class="card p-5 sticky top-6">
            <p class="text-xs font-bold uppercase tracking-widest mb-4" style="color:rgba(255,255,255,0.3);">
                ✦ Live Preview
            </p>

            <!-- Mini portfolio mockup -->
            <div class="preview-card overflow-hidden">
                <!-- Fake nav -->
                <div class="px-4 py-3 flex items-center justify-between" style="background:rgba(238,242,255,0.9); border-bottom:1px solid rgba(58,123,213,0.1);">
                    <span id="previewName" class="font-bold text-sm" style="font-family:'Playfair Display',serif; color:#1a202c;">
                        Your Name<span style="color:#00d2ff">.</span>
                    </span>
                    <span class="text-xs px-2 py-1 rounded-full font-bold" style="background:rgba(0,210,255,0.1); border:1px solid rgba(0,210,255,0.3); color:#0099bb;">
                        ● AVAILABLE
                    </span>
                </div>

                <!-- Fake hero -->
                <div class="p-5" style="background:linear-gradient(135deg,#eef2ff,#dce8ff);">
                    <div class="flex items-start gap-4">
                        <img id="previewPhoto"
                             src="https://placehold.co/64x80/dce8ff/94a3b8?text=📷"
                             class="preview-photo" alt="preview">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:#00d2ff;">Hello, I'm</p>
                            <p id="previewNameHero" class="font-bold text-lg leading-tight mb-2 truncate"
                               style="font-family:'Playfair Display',serif; color:#1a202c;">Your Name</p>
                            <p id="previewBio" class="text-xs leading-relaxed mb-3"
                               style="color:rgba(26,32,60,0.55); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                Your bio will appear here...
                            </p>
                            <span id="previewSkill" class="preview-tag">Your Skill</span>
                        </div>
                    </div>
                </div>

                <!-- Fake projects placeholder -->
                <div class="px-5 py-4" style="background:#f8faff;">
                    <p class="text-xs font-bold mb-3" style="color:rgba(26,32,60,0.4); text-transform:uppercase; letter-spacing:.08em;">Latest Projects</p>
                    <div class="flex flex-col gap-2">
                        <div class="rounded-xl h-10 flex items-center px-3 text-xs" style="background:rgba(58,123,213,0.06); color:rgba(26,32,60,0.3); border:1px dashed rgba(58,123,213,0.15);">
                            + Add your first project after launch
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-center text-xs mt-3" style="color:rgba(255,255,255,0.2);">
                This is how visitors will see your portfolio
            </p>
        </div>
    </div>
</div>

<!-- Skill Modal -->
<div id="skillModal" class="modal" onclick="if(event.target===this)hideSkillModal()">
    <div class="card p-8 max-w-md w-full mx-4" style="max-height:80vh; overflow-y:auto;">
        <h3 style="font-family:'Playfair Display',serif;" class="text-xl font-bold mb-6">Choose your main skill / role</h3>
        <div class="grid grid-cols-2 gap-3" id="skillOptions"></div>
        <button onclick="hideSkillModal()" class="mt-6 w-full py-3 text-sm rounded-xl hover:bg-white/5 transition-colors" style="color:rgba(255,255,255,0.4);">
            Cancel
        </button>
    </div>
</div>

<script>
const skills = [
    "Web Developer","UI/UX Designer","Graphic Designer","Frontend Developer",
    "Backend Developer","Full Stack Developer","Mobile Developer","Digital Marketer",
    "Content Creator","Photographer","Video Editor","Brand Designer","Illustrator",
    "Motion Designer","Game Developer","Data Analyst","Copywriter","3D Artist"
];

// Track filled fields for progress
const fields = { name: false, gender: false, bio: false, skill: false };

function updateProgress() {
    const filled = Object.values(fields).filter(Boolean).length;
    const pct = Math.round(10 + (filled / 4) * 90);
    document.getElementById('progressBar').style.width = pct + '%';
    document.getElementById('progressPct').textContent = pct + '%';
}

function updatePreview() {
    const name = document.getElementById('fieldName').value.trim() || 'Your Name';
    const bio  = document.getElementById('fieldBio').value.trim()  || 'Your bio will appear here...';
    const skill = document.getElementById('selected_skill').value  || 'Your Skill';

    document.getElementById('previewName').innerHTML     = name + '<span style="color:#00d2ff">.</span>';
    document.getElementById('previewNameHero').textContent = name;
    document.getElementById('previewBio').textContent    = bio;
    document.getElementById('previewSkill').textContent  = skill;

    fields.name = !!document.getElementById('fieldName').value.trim();
    fields.bio  = !!document.getElementById('fieldBio').value.trim();
    updateProgress();
}

function selectGender(value, el) {
    document.getElementById('selected_gender').value = value;
    document.querySelectorAll('.gender-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    fields.gender = true;
    updateProgress();
}

function showSkillModal() {
    const container = document.getElementById('skillOptions');
    container.innerHTML = '';
    const current = document.getElementById('selected_skill').value;
    skills.forEach(s => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'skill-pill text-left px-4 py-3 rounded-2xl w-full' + (s === current ? ' ring-2 ring-cyan-400' : '');
        btn.textContent = s;
        btn.onclick = () => selectSkill(s);
        container.appendChild(btn);
    });
    document.getElementById('skillModal').classList.add('open');
}

function selectSkill(skill) {
    document.getElementById('selected_skill').value = skill;
    document.getElementById('skill_display').innerHTML = '<span style="color:#00d2ff;">● ' + skill + '</span>';
    fields.skill = true;
    updatePreview();
    hideSkillModal();
}

function hideSkillModal() {
    document.getElementById('skillModal').classList.remove('open');
}

function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photoPreview').innerHTML =
                '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
            document.getElementById('previewPhoto').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Init progress with any pre-filled values (on error re-render)
document.addEventListener('DOMContentLoaded', () => {
    fields.name   = !!document.getElementById('fieldName').value;
    fields.bio    = !!document.getElementById('fieldBio').value;
    fields.gender = !!document.getElementById('selected_gender').value;
    fields.skill  = !!document.getElementById('selected_skill').value;
    updateProgress();
    updatePreview();
});
</script>
</body>
</html>