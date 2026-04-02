<?php
include 'db.php';
if (isLoggedIn()) { header("Location: index.php"); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $confirm  =      $_POST['confirm']  ?? '';

    if (!$username || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = 'Username: 3–30 chars, letters/numbers/underscore only.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Username or email already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hash);
            if ($stmt->execute()) {
                $_SESSION['user_id']  = $conn->insert_id;
                $_SESSION['username'] = $username;
                // Go to onboarding to fill profile before seeing portfolio
                header("Location: setup.php");
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account · Portfolio Engine</title>
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
    <div class="card w-full max-w-md p-8 anim shadow-2xl">
        <div class="text-center mb-8">
            <p class="text-xs font-semibold uppercase tracking-widest mb-3" style="color:#00d2ff;">Portfolio Engine</p>
            <h1 style="font-family:'Playfair Display',serif; font-size:2rem; font-weight:900; color:#f1f1f1;">
                Create your portfolio<span style="color:#00d2ff">.</span>
            </h1>
            <p class="text-sm mt-2" style="color:rgba(255,255,255,0.4);">Set up your personal brand in seconds</p>
        </div>

        <?php if ($error): ?>
        <div class="mb-5 px-4 py-3 rounded-xl text-sm" style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.3);color:#f87171;">
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="flex flex-col gap-4">
            <div>
                <label>Username</label>
                <input class="input" type="text" name="username" placeholder="yourhandle" value="<?= e($_POST['username'] ?? '') ?>" required>
                <p class="text-xs mt-1" style="color:rgba(255,255,255,0.25);">Your portfolio will be at <code>?u=yourhandle</code></p>
            </div>
            <div>
                <label>Email</label>
                <input class="input" type="email" name="email" placeholder="you@example.com" value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div>
                <label>Password</label>
                <input class="input" type="password" name="password" placeholder="Min. 6 characters" required>
            </div>
            <div>
                <label>Confirm Password</label>
                <input class="input" type="password" name="confirm" placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn mt-2">Create Account →</button>
        </form>

        <p class="text-center text-sm mt-6" style="color:rgba(255,255,255,0.35);">
            Already have an account?
            <a href="login.php" style="color:#00d2ff;" class="font-medium hover:underline">Sign in</a>
        </p>
    </div>
</body>
</html>