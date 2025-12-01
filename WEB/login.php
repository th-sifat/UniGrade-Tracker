<?php
// login.php - simple login (POST username + password)
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: login.html?error=1');
    exit;
}

$stmt = $mysqli->prepare("SELECT id, full_name, username, password FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_username'] = $user['username'];
    header('Location: home.html');
    exit;
} else {
    header('Location: login.html?error=1');
    exit;
}
