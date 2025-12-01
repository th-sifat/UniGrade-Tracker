<?php
// is_logged_in.php
session_start();
header('Content-Type: text/plain; charset=utf-8');

echo isset($_SESSION['user_id']) ? '1' : '0';
