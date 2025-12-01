<?php
// save_result.php
session_start();
header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo "ERROR:not_logged_in";
    exit;
}

require 'db.php';

$user_id = (int)$_SESSION['user_id'];
$semester_no = isset($_POST['semester_no']) ? (int)$_POST['semester_no'] : 0;
$sgpa = isset($_POST['sgpa']) ? floatval($_POST['sgpa']) : null;
$total_credits = isset($_POST['total_credits']) ? floatval($_POST['total_credits']) : null;

if ($semester_no <= 0 || $sgpa === null || $total_credits === null) {
    echo "ERROR:invalid_input";
    exit;
}

// Check existing
$stmt = $mysqli->prepare("SELECT id FROM semesters WHERE user_id = ? AND semester_no = ?");
if (!$stmt) { echo "ERROR:db_prepare"; exit; }
$stmt->bind_param('ii', $user_id, $semester_no);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $stmt->close();
    $stmt = $mysqli->prepare("UPDATE semesters SET sgpa = ?, total_credits = ? WHERE id = ? AND user_id = ?");
    if (!$stmt) { echo "ERROR:db_prepare"; exit; }
    $stmt->bind_param('ddii', $sgpa, $total_credits, $row['id'], $user_id);
    $ok = $stmt->execute();
    $stmt->close();
    echo $ok ? "OK" : "ERROR:db_exec";
    exit;
} else {
    $stmt->close();
    $stmt = $mysqli->prepare("INSERT INTO semesters (user_id, semester_no, sgpa, total_credits) VALUES (?, ?, ?, ?)");
    if (!$stmt) { echo "ERROR:db_prepare"; exit; }
    $stmt->bind_param('iidd', $user_id, $semester_no, $sgpa, $total_credits);
    $ok = $stmt->execute();
    $stmt->close();
    echo $ok ? "OK" : "ERROR:db_exec";
    exit;
}
