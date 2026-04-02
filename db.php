<?php
// ── Database connection (must come first — session handler needs it) ──────────
$host = 'sql106.infinityfree.com';
$user = 'if0_41204836';
$pass = '6Mp1aML6LHvBw';
$db   = 'if0_41204836_db_name';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// ── DB-backed session handler ─────────────────────────────────────────────────
// Required on Vercel: each request may hit a different serverless instance,
// so PHP's default file-based sessions won't persist. Sessions are stored in
// the `sessions` MySQL table instead.
class DBSessionHandler implements SessionHandlerInterface {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string {
        $stmt = $this->conn->prepare(
            "SELECT session_data FROM sessions WHERE session_id = ? AND session_expiry > UNIX_TIMESTAMP()"
        );
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $row['session_data'] : '';
    }

    public function write(string $id, string $data): bool {
        $expiry = time() + 86400; // 24-hour sessions
        $stmt   = $this->conn->prepare(
            "INSERT INTO sessions (session_id, session_data, session_expiry)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE session_data = VALUES(session_data), session_expiry = VALUES(session_expiry)"
        );
        $stmt->bind_param("ssi", $id, $data, $expiry);
        return $stmt->execute();
    }

    public function destroy(string $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE session_id = ?");
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }

    public function gc(int $maxlifetime): int|false {
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE session_expiry < UNIX_TIMESTAMP()");
        $stmt->execute();
        return $stmt->affected_rows;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    $handler = new DBSessionHandler($conn);
    session_set_save_handler($handler, true);
    session_start();
}

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

// ── Projects ──────────────────────────────────────────────────────────────────

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

// ── File upload helper (base64 — works on Vercel serverless) ─────────────────
// Vercel has no persistent filesystem, so uploaded files are converted to
// base64 data URLs and stored directly in the database.
// ⚠️  Make sure your `photo` column is MEDIUMTEXT (handles up to ~3MB images).
//     Run: ALTER TABLE users MODIFY photo MEDIUMTEXT;
//          ALTER TABLE projects MODIFY image MEDIUMTEXT;
function handleUpload(string $fieldName, string $prefix, int $uid): string {
    if (empty($_FILES[$fieldName]['tmp_name'])) return '';

    $ext     = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed)) return '';

    // Limit to ~2 MB before base64 expansion
    if ($_FILES[$fieldName]['size'] > 2 * 1024 * 1024) return '';

    $raw = file_get_contents($_FILES[$fieldName]['tmp_name']);
    if (!$raw) return '';

    $mimeMap = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
    ];
    $mime = $mimeMap[$ext] ?? 'image/jpeg';

    return 'data:' . $mime . ';base64,' . base64_encode($raw);
}