<?php
require_once 'config.php';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        // Inside login_process.php after successful password check:
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['profile_image'] = $user['profile_image']; // Save this!
        header('Location: dashboard.php');
    } else {
        $_SESSION['error'] = 'Invalid credentials';
        header('Location: login.php');
    }
}
?>