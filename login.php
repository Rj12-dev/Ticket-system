<?php 
require_once 'config.php'; 

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container { 
            min-height: 100vh; 
            background: linear-gradient(135deg, #4760cc 0%, #4970ee 100%); 
        }
        .login-card { 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
            border: none; 
            border-radius: 20px;
        }
        .btn-login { 
            background: linear-gradient(45deg, #667eea, #ee2a2a); 
            border: none; 
        }
        .btn-login:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 20px rgba(0,0,0,0.2); 
        }
        /* Custom Logo Styles */
        .custom-logo {
            max-width: 150px;
            max-height: 100px;
            width: auto;
            height: auto;
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
            transition: transform 0.3s ease;
        }
        .custom-logo:hover {
            transform: scale(1.05);
        }
        .logo-fallback {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="login-container d-flex align-items-center justify-content-center p-3">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 col-xl-4">
                <!-- LOGIN CARD -->
                <div class="card login-card">
                    <div class="card-body p-5">
                        <!-- HEADER WITH CUSTOM LOGO -->
                        <div class="text-center mb-4">
                            <!-- Replace 'images/logo.png' with your logo path -->
                            <img src="images/logo.png" alt="Ticket System Logo" class="custom-logo" 
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <!-- Fallback icon if logo fails to load -->
                            <i class="fas fa-ticket-alt logo-fallback d-none"></i>
                            
                            <h2 class="fw-bold text-dark mb-1">Ticket System</h2>
                            <p class="text-muted lead">Sign in to continue</p>
                        </div>

                        <!-- MESSAGES -->
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- LOGIN FORM -->
                        <form method="POST" action="login_process.php">
                            <div class="mb-4">
                                <label class="form-label fw-bold fs-6 mb-2">Username or Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-primary">
                                        <i class="fas fa-user text-primary"></i>
                                    </span>
                                    <input type="text" name="username" class="form-control form-control-lg border-primary" 
                                           placeholder="Username or email" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold fs-6 mb-2">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-primary">
                                        <i class="fas fa-lock text-primary"></i>
                                    </span>
                                    <input type="password" name="password" class="form-control form-control-lg border-primary" 
                                           placeholder="Enter password" required>
                                </div>
                            </div>

                            <!-- SUBMIT BUTTON -->
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-login btn-lg fw-bold text-white py-3">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Sign In
                                </button>
                            </div>
                        </form>

                        <!-- CREATE ACCOUNT LINK -->
                        <div class="text-center mb-3">
                            <p class="mb-3">
                                <span class="opacity-75">Don't have an account?</span>
                            </p>
                            <a href="register.php" class="btn btn-outline-primary btn-lg w-100 mb-2">
                                <i class="fas fa-user-plus me-2"></i>
                                <strong>Create Account</strong>
                            </a>
                        </div>

                        <!-- FORGOT PASSWORD -->
                        <div class="text-center">
                            <a href="forgot_password.php" class="btn btn-link p-0">
                                <i class="fas fa-question-circle me-1"></i>
                                Forgot Password?
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>