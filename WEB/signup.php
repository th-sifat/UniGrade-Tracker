<?php
// signup.php - handle signup form (simple)
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.html');
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($full_name === '' || $username === '' || $password === '') {
    header('Location: signup.html?error=1');
    exit;
}

if ($password !== $confirm) {
    header('Location: signup.html?error=2');
    exit;
}

// check username unique
$stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    // username taken
    header('Location: signup.html?error=3');
    exit;
}
$stmt->close();

// insert user
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("INSERT INTO users (full_name, username, password) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $full_name, $username, $hash);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // auto-login (set session)
    $user_id = $mysqli->insert_id;
    $_SESSION['user_id'] = (int)$user_id;
    $_SESSION['user_name'] = $full_name;
    $_SESSION['user_username'] = $username;
    header('Location: home.html');
    exit;
} else {
    header('Location: signup.html?error=4');
    exit;
}
