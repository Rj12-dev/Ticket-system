<?php 
require_once 'config.php'; 
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$success = $error = '';
if ($_POST) {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
        $stmt->execute([$email, $token]);
        
        // ✅ SHOW TOKEN DIRECTLY (No email needed!)
        $reset_link = $reset_link = "/ticket-system/reset_password.php?token=$token";
        $success = "
            <div class='alert alert-success'>
                <h5>✅ Password reset ready!</h5>
                <strong>Copy this link:</strong><br>
                <div class='bg-light p-3 rounded mt-2'>
                    <strong><a href='$reset_link' target='_blank'>$reset_link</a></strong>
                </div>
                <small class='text-muted mt-2 d-block'>Valid for 1 hour. Share with user securely.</small>
            </div>";
        
    } else {
        $error = "Email not found!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Ticket System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-key fa-4x text-primary mb-3"></i>
                            <h2 class="fw-bold">Forgot Password?</h2>
                            <p class="text-muted">Enter email to get reset link</p>
                        </div>

                        <?php if ($success): echo $success; endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if (!$success && !$error): ?>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" required 
                                           placeholder="your@email.com">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-paper-plane me-2"></i>Get Reset Link
                            </button>
                        </form>
                        <?php endif; ?>

                        <div class="text-center">
                            <a href="login.php" class="btn btn-link">← Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>