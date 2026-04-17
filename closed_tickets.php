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
    // SINGLE & BULK DELETE LOGIC
    if (isset($_POST['delete_permanent']) && !empty($_POST['ticket_ids'])) {
        $ids = $_POST['ticket_ids'];
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'deleted' WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        header('Location: dashboard.php?msg=deleted');
        exit;
    }
}

$total_tickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status != 'deleted'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Portal | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
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

        /* NAVBAR UI */
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
        }

        .user-profile-toggle {
            display: flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.05);
        }

        .navbar-profile-img { 
            width: 32px; height: 32px; 
            object-fit: cover; border-radius: 50%; 
            border: 1px solid var(--primary-blue);
        }

        /* TABLE & CHECKBOX STYLES */
        .table-container { 
            background: var(--surface-white); border-radius: 16px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            overflow: hidden; border: 1px solid #fff;
        }
        .table thead th { background: #f8fafc; color: #64748b; font-size: 0.8rem; text-transform: uppercase; padding: 16px; }
        .table tbody td { padding: 16px; vertical-align: middle; }
        
        /* Checkbox Customization */
        .form-check-input:checked { background-color: var(--primary-blue); border-color: var(--primary-blue); }

        /* Report Button Specific Style */
        .btn-report { background-color: #0ea5e9; color: white; border: none; }
        .btn-report:hover { background-color: #0284c7; color: white; }

        footer { background: rgba(255,255,255,0.1); backdrop-filter: blur(5px); color: #fff; padding: 20px 0; margin-top: 50px; }
    </style>
</head>
<body>

    <div class="content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                    <img src="images/logo.png" alt="Logo" class="navbar-logo-circle me-3">
                    <div class="brand-text text-white fw-bold">TICKET<span class="text-primary ms-1">SYSTEM</span></div>
                </a>
                
                <div class="navbar-nav ms-auto align-items-center">
                    <?php if (isAdmin()): ?>
                        <a class="nav-link me-3 fw-bold" href="admin_tickets.php">All Tickets</a>
                    <?php endif; ?>
                    <div class="user-profile-toggle me-3">
                        <img src="<?php echo $img_path; ?>" class="navbar-profile-img" alt="Profile">
                    </div>
                    <a class="btn btn-link text-danger text-decoration-none fw-bold" href="logout.php"><i class="fas fa-power-off"></i></a>
                </div>
            </div>
        </nav>

        <div class="container mt-5 pb-5">
            <?php if (isAdmin()): ?>
                <div class="d-flex justify-content-between align-items-center mb-4 text-white">
                    <div>
                        <h3 class="fw-bold mb-0">Resolved History</h3>
                        <p class="small opacity-75">Select items to delete or generate a summary report</p>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="print_report.php" class="btn btn-report rounded-pill px-4 shadow">
                            <i class="fas fa-file-pdf me-2"></i>Generate Report
                        </a>

                        <button type="submit" form="bulkDeleteForm" class="btn btn-danger rounded-pill px-4 shadow" id="deleteBtn" disabled>
                            <i class="fas fa-trash-alt me-2"></i>Delete Selected
                        </button>
                    </div>
                </div>

                <form id="bulkDeleteForm" method="POST" onsubmit="return confirm('Are you sure you want to delete the selected tickets?')">
                    <input type="hidden" name="delete_permanent" value="1">
                    <div class="table-container">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Ref ID</th><th>User</th><th>Subject</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT t.*, u.username FROM tickets t JOIN users u ON t.user_id = u.id 
                                                     WHERE t.status = 'closed' ORDER BY created_at DESC");
                                while ($ticket = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="ticket_ids[]" value="<?php echo $ticket['id']; ?>" class="form-check-input ticket-checkbox">
                                    </td>
                                    <td><span class="badge bg-light text-dark border">#<?php echo $ticket['id']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($ticket['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success px-3 rounded-pill">RESOLVED</span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

            <?php else: ?>
                <div class="text-center py-5 text-white">
                    <h2 class="fw-bold">My Support Tickets</h2>
                    <a href="create_ticket.php" class="btn btn-light text-primary fw-bold shadow-lg px-5 py-3 rounded-pill">New Support Ticket</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <small class="opacity-75">IT Ticket Management System &copy; <?php echo date('Y'); ?></small>
        </div>
    </footer>

    <script>
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.ticket-checkbox');
        const deleteBtn = document.getElementById('deleteBtn');

        // Toggle all checkboxes
        selectAll?.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            toggleDeleteButton();
        });

        // Toggle delete button visibility based on selection
        checkboxes.forEach(cb => {
            cb.addEventListener('change', toggleDeleteButton);
        });

        function toggleDeleteButton() {
            const checkedCount = document.querySelectorAll('.ticket-checkbox:checked').length;
            deleteBtn.disabled = checkedCount === 0;
            
            if(checkedCount > 0) {
                deleteBtn.innerHTML = `<i class="fas fa-trash-alt me-2"></i>Delete (${checkedCount})`;
            } else {
                deleteBtn.innerHTML = `<i class="fas fa-trash-alt me-2"></i>Delete Selected`;
            }
        }
    </script>
</body>
</html>