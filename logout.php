<?php
session_start();

// Hapus session user
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);

// Redirect ke halaman utama
header('Location: index.php');
exit;
?>