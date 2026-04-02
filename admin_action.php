<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';
requireLogin();
$user = getCurrentUser($conn);
$uid = (int)$user['id'];

// ====================== UPDATE PROFILE ======================
if (isset($_POST['update_profile'])) {
    $full_name    = trim($_POST['full_name'] ?? '');
    $bio          = trim($_POST['bio'] ?? '');
    $skills       = trim($_POST['skills'] ?? '');        // now single skill
    $active_since = trim($_POST['active_since'] ?? '');

    $photo = handleUpload('photo', 'photo', $uid);

    if ($photo) {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, bio=?, skills=?, active_since=?, photo=? WHERE id=?");
        $stmt->bind_param("sssssi", $full_name, $bio, $skills, $active_since, $photo, $uid);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, bio=?, skills=?, active_since=? WHERE id=?");
        $stmt->bind_param("ssssi", $full_name, $bio, $skills, $active_since, $uid);
    }

    if ($stmt->execute()) {
        header("Location: admin.php?tab=profile&saved=1");
        exit();
    } else {
        die("Update failed: " . $conn->error);   // temporary debug
    }
}

// ====================== ADD PROJECT ======================
if (isset($_POST['add_project'])) {
    $title       = trim($_POST['title'] ?? '');
    $url         = trim($_POST['url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $screenshot = handleUpload('screenshot', 'project', $uid);
    $stmt = $conn->prepare("INSERT INTO projects (admin_id, title, link, description, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $uid, $title, $url, $description, $screenshot);
    $stmt->execute();
    header("Location: admin.php?tab=projects&added=1");
    exit();
}

// ====================== EDIT PROJECT ======================
if (isset($_POST['edit_project'])) {
    $project_id  = (int)$_POST['project_id'];
    $title       = trim($_POST['title'] ?? '');
    $url         = trim($_POST['url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $screenshot = handleUpload('screenshot', 'project', $uid);
    if ($screenshot) {
        $stmt = $conn->prepare("UPDATE projects SET title=?, link=?, description=?, image=? WHERE id=? AND admin_id=?");
        $stmt->bind_param("ssssii", $title, $url, $description, $screenshot, $project_id, $uid);
    } else {
        $stmt = $conn->prepare("UPDATE projects SET title=?, link=?, description=? WHERE id=? AND admin_id=?");
        $stmt->bind_param("sssii", $title, $url, $description, $project_id, $uid);
    }
    $stmt->execute();
    header("Location: admin.php?tab=projects");
    exit();
}

// ====================== DELETE PROJECT ======================
if (isset($_POST['delete_project'])) {
    $project_id = (int)$_POST['project_id'];
    $stmt = $conn->prepare("DELETE FROM projects WHERE id=? AND admin_id=?");
    $stmt->bind_param("ii", $project_id, $uid);
    $stmt->execute();
    header("Location: admin.php?tab=projects&deleted=1");
    exit();
}

// ====================== TOGGLE STATUS ======================
if (isset($_POST['toggle_status'])) {
    $current = getStatus($conn, $uid);
    $newStatus = ($current === 'Busy') ? 'Available' : 'Busy';

    $stmt = $conn->prepare("UPDATE users SET job_status=? WHERE id=?");
    $stmt->bind_param("si", $newStatus, $uid);
    $stmt->execute();

    header("Location: admin.php?tab=status");
    exit();
}

// Quick toggle from portfolio page
if (isset($_GET['quick_toggle'])) {
    $current = getStatus($conn, $uid);
    $newStatus = ($current === 'Busy') ? 'Available' : 'Busy';

    $stmt = $conn->prepare("UPDATE users SET job_status=? WHERE id=?");
    $stmt->bind_param("si", $newStatus, $uid);
    $stmt->execute();

    header("Location: " . ($_GET['ref'] ?? 'admin.php'));
    exit();
}

echo "No action performed.";
?>