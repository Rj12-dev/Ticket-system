<?php 
require_once 'config.php'; 

// Ensure only admins can access this page
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// --- NAVBAR LOGIC (For the Admin logged in) ---
$admin_img = $_SESSION['profile_image'] ?? 'default-avatar.png';
$admin_img_path = "uploads/profiles/" . $admin_img;
if (empty($admin_img) || !file_exists($admin_img_path)) {
    $admin_img_path = "images/default-avatar.png"; 
}

// --- DELETE USER LOGIC ---
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id']; 
    $current_admin_id = $_SESSION['user_id'];

    if ($user_id === $current_admin_id) {
        $_SESSION['error'] = "Action denied: You cannot delete your own account.";
    } elseif ($user_id === 1) {
        $_SESSION['error'] = "Action denied: System Administrator (ID 1) cannot be deleted.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $email = $stmt->fetchColumn();

            if ($email) {
                // Delete linked data
                $pdo->prepare("DELETE FROM ticket_files WHERE ticket_id IN (SELECT id FROM tickets WHERE user_id = ?)")->execute([$user_id]);
                $pdo->prepare("DELETE FROM ticket_comments WHERE ticket_id IN (SELECT id FROM tickets WHERE user_id = ?)")->execute([$user_id]);
                $pdo->prepare("DELETE FROM ticket_comments WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM tickets WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);

                $pdo->commit();
                $_SESSION['success'] = "User and all associated data deleted successfully.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Database Error: " . $e->getMessage();
        }
    }
    header('Location: admin_users.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* 🎨 MATCHING ORIGINAL THEME */
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 30%, #60a5fa 70%, #93c5fd 100%);
            min-height: 100vh;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #3b82f6;
        }

        .navbar-profile-img {
            width: 35px;
            height: 35px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.8);
        }

        .navbar {
            background: rgba(0, 0, 0, 0.2) !important;
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-lg py-2">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
                <i class="fas fa-ticket-alt me-2"></i>
                <span>Ticket System</span>
            </a>
            
            <div class="navbar-nav ms-auto align-items-center">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="admin_tickets.php">All Tickets</a>
                <a class="nav-link active fw-bold text-info" href="admin_users.php">Users</a>

                <div class="d-flex align-items-center ms-3 border-start ps-3 border-white border-opacity-25">
                    <img src="<?php echo $admin_img_path; ?>" class="navbar-profile-img shadow-sm" alt="Admin">
                    <div class="ms-2 text-white text-end me-3">
                        <small class="d-block opacity-75" style="font-size: 0.6rem; text-transform: uppercase;">Admin</small>
                        <span class="fw-bold" style="font-size: 0.9rem;"><?php echo $_SESSION['username']; ?></span>
                    </div>
                    <a class="btn btn-danger btn-sm px-3" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>Log Out
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 px-4">
        
        <div class="row justify-content-center">
            <div class="col-md-11">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow">
                        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-11">
                <h2 class="text-white mb-4"><i class="fas fa-users-cog me-2"></i>User Management</h2>
                
                <div class="table-responsive table-container">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Profile</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
                            while ($user = $stmt->fetch()):
                                $u_img = $user['profile_image'] ?? 'default-avatar.png';
                                $u_path = "uploads/profiles/" . $u_img;
                                if (!file_exists($u_path) || empty($u_img)) { $u_path = "images/default-avatar.png"; }
                            ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?php echo $user['id']; ?></td>
                                <td>
                                    <img src="<?php echo $u_path; ?>" class="user-avatar shadow-sm">
                                </td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="text-center">
                                    <form method="POST" onsubmit="return confirm('Delete user and all data?')" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="delete_user" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-danger px-3">
                                            <i class="fas fa-user-times me-1"></i> Delete User
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>