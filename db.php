<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'sql106.infinityfree.com';
$user = 'if0_41204836';
$pass = '6Mp1aML6LHvBw';
$db   = 'if0_41204836_db_name';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// ── Auth helpers ─────────────────────────────────────────────────────────────

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// ── User fetchers ─────────────────────────────────────────────────────────────

function getCurrentUser(mysqli $conn): ?array {
    if (!isLoggedIn()) return null;
    $id   = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function getUserByUsername(mysqli $conn, string $username): ?array {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function getUserById(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

// ── Status ────────────────────────────────────────────────────────────────────

function getStatus(mysqli $conn, int $userId): string {
    $stmt = $conn->prepare("SELECT job_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['job_status'] ?? 'Available';
}

function toggleStatus(mysqli $conn, int $userId): void {
    $stmt = $conn->prepare("
        UPDATE users
        SET job_status = IF(job_status = 'Busy', 'Available', 'Busy')
        WHERE id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}

// ── Projects — columns: id, admin_id, title, description, image, link, category, created_at
function getUserProjects(mysqli $conn, int $userId): array {
    $stmt = $conn->prepare("SELECT * FROM projects WHERE admin_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ── Profile completeness check ───────────────────────────────────────────────

function isProfileComplete(array $user): bool {
    return !empty($user['full_name']) && !empty($user['bio']) && !empty($user['skills']);
}

// ── Output escaping ──────────────────────────────────────────────────────────

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// ── File upload helper ───────────────────────────────────────────────────────

function handleUpload(string $fieldName, string $prefix, int $uid): string {
    if (empty($_FILES[$fieldName]['tmp_name'])) return '';
    $ext     = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed)) return '';
    $dir = 'uploads/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = $prefix . '_' . $uid . '_' . time() . '.' . $ext;
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $dir . $filename)) {
        return $dir . $filename;
    }
    return '';
}
