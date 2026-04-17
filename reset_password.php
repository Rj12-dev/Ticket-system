<?php 
require_once 'config.php'; 
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$token = $_GET['token'] ?? '';
$success = $error = '';

if ($_POST) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if ($password !== $confirm) {
        $error = 'Passwords do not match!';
    } else {
        // Verify token
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires > NOW() AND used = 0");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if ($reset) {
            // Update password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $reset['email']]);
            
            // Mark token as used
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            $success = 'Password reset successfully! <a href="login.php">Login now →</a>';
        } else {
            $error = 'Invalid or expired token!';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Ticket System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-lock-open fa-4x text-success mb-3"></i>
                            <h2 class="fw-bold">Reset Password</h2>
                            <p class="text-muted">Enter new password</p>
                        </div>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if (!$success && !$error): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">New Password</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-2"></i>Reset Password
                            </button>
                        </form>
                        <?php endif; ?>

                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-link">← Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>