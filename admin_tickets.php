<?php 
require_once 'config.php'; 

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// --- 1. PROFILE IMAGE LOGIC ---
$user_img = $_SESSION['profile_image'] ?? 'default-avatar.png';
$img_path = "uploads/profiles/" . $user_img;
if (empty($user_img) || !file_exists($img_path)) {
    $img_path = "images/default-avatar.png"; 
}

// --- 2. BULK DELETE LOGIC ---
// This handles the request when the "Delete Selected" button is clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['bulk_delete']) || isset($_POST['ticket_ids']))) {
    if (!empty($_POST['ticket_ids'])) {
        $ids = $_POST['ticket_ids'];
        $ids = array_map('intval', $ids); // Security: force all IDs to be integers
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        // Update status to 'deleted' so they move to the history/hidden view
        $sql = "UPDATE tickets SET status = 'deleted' WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($ids)) {
            header('Location: admin_tickets.php?msg=deleted');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tickets - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 30%, #60a5fa 70%, #93c5fd 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar-profile-img {
            width: 40px; height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.8);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border: none;
        }

        .badge-status { font-size: 0.75rem; padding: 6px 12px; border-radius: 8px; }

        .bulk-actions-bar {
            display: none; 
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        
        .form-check-input { cursor: pointer; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark shadow-lg py-2 mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
                <img src="images/logo.png" alt="Logo" height="40" width="40" class="me-2 rounded-circle border border-2 border-white">
                <span>Ticket Admin</span>
            </a>
            
            <div class="navbar-nav ms-auto align-items-center">
                <a class="nav-link me-3" href="dashboard.php"><i class="fas fa-chart-bar me-1"></i> Dashboard</a>
                <a class="nav-link active me-3" href="admin_tickets.php"><i class="fas fa-list me-1"></i> All Tickets</a>
                <a class="nav-link me-3" href="admin_users.php"><i class="fas fa-users me-1"></i> Users</a>

                <div class="d-flex align-items-center border-start ps-3 border-white border-opacity-25">
                    <img src="<?php echo $img_path; ?>" class="navbar-profile-img shadow-sm" alt="Profile">
                    <div class="ms-2 text-white text-end">
                        <span class="fw-bold d-block"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <small class="opacity-75" style="font-size: 0.7rem;">ADMIN <i class="fas fa-crown text-warning ms-1"></i></small>
                    </div>
                </div>
                <a class="nav-link btn btn-danger btn-sm px-3 ms-3" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 mb-5">
        <div class="card table-container border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-list me-2"></i>Ticket Inventory</h4>
                
                <div class="d-flex align-items-center">
                    <div id="bulkActions" class="bulk-actions-bar me-3">
                        <button type="button" class="btn btn-danger btn-sm fw-bold" onclick="submitBulkDelete()">
                            <i class="fas fa-trash-alt me-1"></i> Delete Selected (<span id="selectedCount">0</span>)
                        </button>
                    </div>

                    <a href="print_report.php" class="btn btn-outline-info btn-sm fw-bold">
                        <i class="fas fa-print me-1"></i> Generate Report
                    </a>
                </div>
            </div>
            
            <div class="card-body p-0">
                <form id="ticketsForm" method="POST" action="admin_tickets.php">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-uppercase small">
                                <tr>
                                    <th class="ps-4" width="40">
                                        <input type="checkbox" class="form-check-input" id="selectAll">
                                    </th>
                                    <th># ID</th>
                                    <th>Client Info</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Date Created</th>
                                    <th class="pe-4 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Note: Added 'deleted' filter to the SQL query
                                $query = "SELECT t.*, u.username, u.email 
                                          FROM tickets t 
                                          JOIN users u ON t.user_id = u.id 
                                          WHERE t.status NOT IN ('closed', 'deleted') 
                                          ORDER BY t.created_at DESC";
                                $stmt = $pdo->query($query);
                                
                                while ($ticket = $stmt->fetch()):
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <input type="checkbox" name="ticket_ids[]" value="<?php echo $ticket['id']; ?>" class="form-check-input ticket-checkbox">
                                    </td>
                                    <td><span class="fw-bold">#<?php echo $ticket['id']; ?></span></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($ticket['username']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($ticket['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($ticket['subject'], 0, 40)); ?>...</td>
                                    <td>
                                        <span class="badge badge-status bg-<?php 
                                            echo $ticket['status'] == 'open' ? 'primary' : ($ticket['status'] == 'in_progress' ? 'warning text-dark' : 'success'); 
                                        ?> text-uppercase">
                                            <?php echo $ticket['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-<?php echo $ticket['priority'] == 'high' ? 'danger' : ($ticket['priority'] == 'medium' ? 'warning' : 'success'); ?> fw-bold small">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                            <?php echo strtoupper($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small fw-bold"><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></div>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
                </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.ticket-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCountLabel = document.getElementById('selectedCount');

        // Logic to show/hide the delete button and update count
        function updateBulkActions() {
            const checkedCount = document.querySelectorAll('.ticket-checkbox:checked').length;
            selectedCountLabel.textContent = checkedCount;
            bulkActions.style.display = checkedCount > 0 ? 'block' : 'none';
        }

        // Handle "Select All" click
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateBulkActions();
        });

        // Handle individual checkbox clicks
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!this.checked) selectAll.checked = false;
                if (document.querySelectorAll('.ticket-checkbox:checked').length === checkboxes.length) selectAll.checked = true;
                updateBulkActions();
            });
        });

        // Function to manually submit the form with the bulk delete flag
        function submitBulkDelete() {
            if (confirm('Are you sure you want to archive the selected tickets?')) {
                const form = document.getElementById('ticketsForm');
                
                // Create a hidden input so PHP knows it was a bulk delete action
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'bulk_delete';
                hiddenInput.value = '1';
                
                form.appendChild(hiddenInput);
                form.submit();
            }
        }
    </script>
</body>
</html>