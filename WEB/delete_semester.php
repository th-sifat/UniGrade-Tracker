<?php
// delete_semester.php
session_start();
header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo "ERROR:not_logged_in";
    exit;
}

require 'db.php';
$user_id = (int)$_SESSION['user_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo "ERROR:invalid_id";
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM semesters WHERE id = ? AND user_id = ?");
if (!$stmt) { echo "ERROR:db_prepare"; exit; }
$stmt->bind_param('ii', $id, $user_id);
$ok = $stmt->execute();
$stmt->close();

echo $ok ? "OK" : "ERROR:db_exec";
