<?php
require_once 'config.php';

if ($_POST) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    // Note: Hash the password later to ensure validation passes first
    $password_plain = $_POST['password']; 
    
    // 1. Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = 'Username or email already exists';
        header('Location: register.php');
        exit;
    }

    // 2. Handle Profile Image Upload Logic
    $profile_image = 'default-avatar.png'; // Default if none uploaded

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/profiles/";
        
        // Ensure folder exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = time() . '_' . uniqid() . '.' . $file_ext; 
        $target_file = $target_dir . $new_filename;

        // Move the file to your folder
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = $new_filename;
        }
    }

    // 3. Create user with the image filename included
    $hashed_password = password_hash($password_plain, PASSWORD_DEFAULT);
    
    // Updated INSERT to include profile_image column
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, profile_image, role) VALUES (?, ?, ?, ?, 'user')");
    
    if ($stmt->execute([$username, $email, $hashed_password, $profile_image])) {
        $_SESSION['success'] = 'Account created! Please login.';
        header('Location: login.php');
    } else {
        $_SESSION['error'] = 'Something went wrong. Please try again.';
        header('Location: register.php');
    }
    exit;
}
?>