<?php 
require_once 'config.php'; 

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// --- 1. PROFILE IMAGE LOGIC ---
$user_img = $_SESSION['profile_image'] ?? 'default-avatar.png';
$img_path = "uploads/profiles/" . $user_img;
if (empty($user_img) || !file_exists($img_path)) {
    $img_path = "images/default-avatar.png"; 
}

// --- 2. ADMIN ACTIONS ---
if (isAdmin()) {
    if (isset($_POST['bulk_delete']) && !empty($_POST['ticket_ids'])) {
        $ids = $_POST['ticket_ids'];
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'deleted' WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        header('Location: dashboard.php?msg=archived');
        exit;
    }
    if (isset($_POST['mark_done'])) {
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'closed' WHERE id = ?");
        $stmt->execute([$_POST['ticket_id']]);
        header('Location: dashboard.php?msg=success');
        exit;
    }
}

// Stats for Admin
$total_tickets = 0;
$open_tickets = 0;
if (isAdmin()) {
    $total_tickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status != 'deleted'")->fetchColumn();
    $open_tickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket System | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --danger-red: #dc3545;
            --nav-bg: #0f172a;
            --surface-white: rgba(255, 255, 255, 0.95);
        }
        
        html, body { height: 100%; font-family: 'Inter', -apple-system, sans-serif; }
        
        body { 
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #60a5fa 100%);
            background-attachment: fixed;
            display: flex; 
            flex-direction: column; 
            color: #334155;
        }
        
        .content-wrapper { flex: 1 0 auto; }

        .navbar { 
            background: var(--nav-bg) !important; 
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .navbar-logo-circle {
            background: #fff;
            padding: 6px;
            border-radius: 50%;
            height: 42px;
            width: 42px;
            object-fit: contain;
            transition: transform 0.3s ease;
            box-shadow: 0 0 10px rgba(255,255,255,0.2);
        }
        
        .navbar-brand:hover .navbar-logo-circle { transform: scale(1.08); }

        .brand-text {
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: #fff;
        }

        .user-profile-toggle {
            display: flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }

        .navbar-profile-img { 
            width: 32px; 
            height: 32px; 
            object-fit: cover; 
            border-radius: 50%; 
            border: 1px solid var(--primary-blue);
        }

        /* Standard Hover Effect */
        .btn-hover-animate {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .btn-hover-animate:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2) !important;
            background-color: #ffffff !important;
            color: var(--primary-blue) !important;
        }

        /* SPECIFIC DANGER RED HOVER FOR ARCHIVE */
        .btn-archive-danger {
            transition: all 0.3s ease;
        }

        .btn-archive-danger:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(220, 53, 69, 0.4) !important;
            background-color: var(--danger-red) !important;
            border-color: var(--danger-red) !important;
            color: #ffffff !important;
        }

        .stat-card {
            background: var(--surface-white); border-radius: 16px; padding: 24px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border: 1px solid #fff;
        }
        .table-container { 
            background: var(--surface-white); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            overflow: hidden; border: 1px solid #fff;
        }
        .table thead th { background: #f8fafc; color: #64748b; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; padding: 16px; }
        .table tbody td { padding: 16px; vertical-align: middle; }

        footer { background: rgba(255,255,255,0.1); backdrop-filter: blur(5px); color: #fff; padding: 20px 0; margin-top: 50px; }
    </style>
</head>
<body>

    <div class="content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                    <img src="images/logo.png" alt="Logo" class="navbar-logo-circle me-3">
                    <div class="brand-text">
                        TICKET<span class="text-primary ms-1">SYSTEM</span>
                    </div>
                </a>
                
                <div class="navbar-nav ms-auto align-items-center">
                    <?php if (isAdmin()): ?>
                        <a class="btn btn-primary btn-sm me-4 px-4 rounded-pill" href="admin_tickets.php">
                            <i class="fas fa-list-ul me-2"></i>All Tickets
                        </a>
                    <?php endif; ?>

                    <div class="user-profile-toggle me-3">
                        <img src="<?php echo $img_path; ?>" class="navbar-profile-img" alt="Profile">
                        <div class="ms-2 d-none d-md-block">
                            <div class="text-white fw-bold small" style="line-height: 1;"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                            <small class="text-muted" style="font-size: 10px;"><?php echo isAdmin() ? 'ADMIN' : 'CLIENT'; ?></small>
                        </div>
                    </div>
                    
                    <a class="btn btn-link text-danger text-decoration-none fw-bold small" href="logout.php">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
            </div>
        </nav>

        <div class="container mt-5 pb-5">
            <?php if (isAdmin()): ?>
                <div class="row mb-5 g-4 text-dark">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="fas fa-inbox text-primary"></i>
                                </div>
                                <div>
                                    <small class="text-muted text-uppercase fw-bold">Total Requests</small>
                                    <h2 class="fw-bold mb-0"><?php echo $total_tickets; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="fas fa-clock text-warning"></i>
                                </div>
                                <div>
                                    <small class="text-muted text-uppercase fw-bold">Pending</small>
                                    <h2 class="fw-bold mb-0 text-primary"><?php echo $open_tickets; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="fas fa-shield-alt text-success"></i>
                                </div>
                                <div>
                                    <small class="text-muted text-uppercase fw-bold">Status</small>
                                    <h2 class="fw-bold mb-0 text-success">Optimal</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-end mb-3 text-white">
                    <div>
                        <h4 class="fw-bold mb-1">Queue Management</h4>
                        <p class="small opacity-75 mb-0">Active support requests</p>
                    </div>
                    <div class="btn-group">
                        <a href="closed_tickets.php" class="btn btn-light btn-sm px-3 shadow-sm border-0"><i class="fas fa-history me-1"></i> Logs</a>
                        <button type="submit" form="bulkForm" name="bulk_delete" class="btn btn-danger btn-sm px-3 shadow-sm border-0 ms-2" onclick="return confirm('Archive selected?')">Archive</button>
                    </div>
                </div>

                <form id="bulkForm" method="POST">
                    <div class="table-container">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                    <th>Ref ID</th><th>User</th><th>Subject</th><th>Priority</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT t.*, u.username FROM tickets t JOIN users u ON t.user_id = u.id 
                                                     WHERE t.status NOT IN ('closed', 'deleted') ORDER BY created_at DESC");
                                while ($ticket = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="ticket_ids[]" value="<?php echo $ticket['id']; ?>" class="form-check-input ticket-checkbox"></td>
                                    <td><span class="badge bg-light text-dark border">#<?php echo $ticket['id']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($ticket['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $ticket['priority']=='high' ? 'bg-danger' : 'bg-primary'; ?> bg-opacity-10 <?php echo $ticket['priority']=='high' ? 'text-danger' : 'text-primary'; ?> px-3">
                                            <?php echo strtoupper($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">Manage</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

            <?php else: ?>
                <div class="text-center py-5 text-white">
                    <h2 class="fw-bold mb-2">My Support Tickets</h2>
                    <p class="opacity-75 mb-4">Need help? Create a ticket or view your resolved issues.</p>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="create_ticket.php" class="btn btn-light text-primary fw-bold shadow-lg px-5 py-3 rounded-pill btn-hover-animate">
                            <i class="fas fa-plus-circle me-2"></i>New Support Ticket
                        </a>
                        <a href="archived_tickets.php" class="btn btn-outline-light fw-bold shadow-lg px-5 py-3 rounded-pill btn-archive-danger">
                            <i class="fas fa-archive me-2"></i>Archived Tickets
                        </a>
                    </div>
                </div>

                <div class="table-container mt-4">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ticket ID</th><th>Subject</th><th>Status</th><th>Priority</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? AND status NOT IN ('closed', 'deleted') ORDER BY created_at DESC");
                            $stmt->execute([$_SESSION['user_id']]);
                            $hasTickets = false;
                            while ($ticket = $stmt->fetch()):
                                $hasTickets = true;
                            ?>
                            <tr>
                                <td><span class="fw-bold text-muted">#<?php echo $ticket['id']; ?></span></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 rounded-pill"><?php echo strtoupper($ticket['status']); ?></span>
                                </td>
                                <td><span class="text-muted small fw-bold"><?php echo strtoupper($ticket['priority']); ?></span></td>
                                <td>
                                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">Review</a>
                                </td>
                            </tr>
                            <?php endwhile; 
                            if (!$hasTickets): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No active tickets found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <small class="opacity-75">Jobs Esolution IT Ticket Management System &copy; <?php echo date('Y'); ?></small>
        </div>
    </footer>

    <script>
        document.getElementById('selectAll')?.addEventListener('change', function() {
            document.querySelectorAll('.ticket-checkbox').forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>
</html>