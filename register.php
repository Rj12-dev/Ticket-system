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
    <title>Register - Ticket System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* 🎨 MATCHING THEME */
        body { 
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 30%, #60a5fa 70%, #93c5fd 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .card { 
            border: none; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        /* 🚀 LOGO STYLING */
        .brand-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 15px;
            filter: drop-shadow(0 5px 15px rgba(0,0,0,0.1));
        }

        .card-header { 
            background: transparent; 
            color: #1e3a8a; 
            border: none;
            padding-top: 2rem; 
        }

        #imagePreview img { 
            width: 90px; 
            height: 90px; 
            object-fit: cover; 
            border: 3px solid #3b82f6; 
            padding: 2px;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }

        .form-control {
            border-left: none;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #ced4da;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header text-center">
                        <img src="images/logo.png" alt="System Logo" class="brand-logo">
                        <p class="text-muted small">Fill in the details to create your account</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <small><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></small>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="register_process.php" enctype="multipart/form-data">
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user text-primary"></i></span>
                                    <input type="text" name="username" class="form-control" placeholder="Choose a username" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope text-primary"></i></span>
                                    <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted">Profile Photo</label>
                                <input type="file" name="profile_image" id="profile_image" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                                <div id="imagePreview" class="mt-2 text-center"></div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-uppercase text-muted">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock text-primary"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required minlength="6">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">
                                    Get Started
                                </button>
                                <a href="login.php" class="btn btn-link text-decoration-none small text-center mt-2">
                                    Already have an account? <span class="fw-bold">Login here</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                    <div class="animate__animated animate__fadeIn">
                        <img src="${e.target.result}" class="rounded-circle shadow-sm">
                    </div>`;
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.innerHTML = '';
        }
    }
    </script>
</body>
</html>