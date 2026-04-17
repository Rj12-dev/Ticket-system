<?php 
require_once 'config.php'; 

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$img_path = "uploads/profiles/" . ($_SESSION['profile_image'] ?? 'default-avatar.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archived Tickets | Ticket System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reusing your existing styles */
        body { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); min-height: 100vh; color: #fff; }
        .table-container { background: rgba(255, 255, 255, 0.95); border-radius: 16px; color: #334155; overflow: hidden; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="fas fa-archive me-2"></i>Archived Tickets</h2>
            <a href="dashboard.php" class="btn btn-primary rounded-pill px-4">Back to Dashboard</a>
        </div>

        <div class="table-container shadow-lg">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Resolution Date</th>
                        <th>Priority</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? AND status = 'closed' ORDER BY created_at DESC");
                    $stmt->execute([$user_id]);
                    $archived = $stmt->fetchAll();

                    if (count($archived) > 0):
                        foreach ($archived as $ticket):
                    ?>
                    <tr>
                        <td><span class="text-muted">#<?php echo $ticket['id']; ?></span></td>
                        <td><strong><?php echo htmlspecialchars($ticket['subject']); ?></strong></td>
                        <td><span class="small text-muted"><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></span></td>
                        <td><span class="badge bg-secondary"><?php echo strtoupper($ticket['priority']); ?></span></td>
                        <td>
                            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-dark px-3 rounded-pill">View History</a>
                        </td>
                    </tr>
                    <?php 
                        endforeach; 
                    else: 
                    ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">No archived tickets found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>