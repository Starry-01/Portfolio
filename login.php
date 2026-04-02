<?php
include 'db.php';
if (isLoggedIn()) { header("Location: index.php"); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (!$login || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        // Allow login with username OR email — both from `users` table
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            // If profile not complete, go to setup. Otherwise go to their portfolio.
            if (!isProfileComplete($user)) {
                header("Location: setup.php");
            } else {
                header("Location: index.php?u=" . urlencode($user['username']));
            }
            exit();
        } else {
            $error = 'Incorrect username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In · Portfolio Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; }
        .card { background: rgba(255,255,255,0.06); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.12); border-radius: 20px; }
        .input { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); color: #f1f1f1; border-radius: 10px; padding: 12px 16px; width: 100%; font-size: 14px; transition: all .2s; font-family: 'DM Sans', sans-serif; }
        .input::placeholder { color: rgba(255,255,255,0.3); }
        .input:focus { outline: none; border-color: #00d2ff; box-shadow: 0 0 0 3px rgba(0,210,255,0.12); background: rgba(255,255,255,0.1); }
        .btn { background: linear-gradient(90deg, #00d2ff, #3a7bd5); color: white; border-radius: 10px; padding: 13px; font-weight: 600; width: 100%; transition: all .2s; font-size: 15px; }
        .btn:hover { filter: brightness(1.1); transform: translateY(-1px); }
        label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,0.4); display: block; margin-bottom: 6px; }
        @keyframes slideUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        .anim { animation: slideUp .6s ease both; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="card w-full max-w-sm p-8 anim shadow-2xl">
        <div class="text-center mb-8">
            <p class="text-xs font-semibold uppercase tracking-widest mb-3" style="color:#00d2ff;">Portfolio Engine</p>
            <h1 style="font-family:'Playfair Display',serif; font-size:2rem; font-weight:900; color:#f1f1f1;">
                Welcome back<span style="color:#00d2ff">.</span>
            </h1>
            <p class="text-sm mt-2" style="color:rgba(255,255,255,0.4);">Sign in to manage your portfolio</p>
        </div>

        <?php if ($error): ?>
        <div class="mb-5 px-4 py-3 rounded-xl text-sm" style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.3);color:#f87171;">
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($_GET['registered'])): ?>
        <div class="mb-5 px-4 py-3 rounded-xl text-sm" style="background:rgba(0,255,150,0.08);border:1px solid rgba(0,255,150,0.25);color:#4ade80;">
            Account created! Please sign in.
        </div>
        <?php endif; ?>

        <form method="POST" class="flex flex-col gap-4">
            <div>
                <label>Username or Email</label>
                <input class="input" type="text" name="login" placeholder="yourhandle or you@example.com" value="<?= e($_POST['login'] ?? '') ?>" required>
            </div>
            <div>
                <label>Password</label>
                <input class="input" type="password" name="password" placeholder="Your password" required>
            </div>
            <button type="submit" class="btn mt-2">Sign In →</button>
        </form>

        <p class="text-center text-sm mt-6" style="color:rgba(255,255,255,0.35);">
            No account yet?
            <a href="register.php" style="color:#00d2ff;" class="font-medium hover:underline">Create one free</a>
        </p>
    </div>
</body>
</html>