<?php 
require_once 'config.php'; 

// 1. SECURITY CHECK
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;

// 2. FETCH TICKET DATA
$stmt = $pdo->prepare("SELECT t.*, u.username, u.profile_image FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket || ($ticket['user_id'] != $_SESSION['user_id'] && !isAdmin())) {
    header('Location: dashboard.php');
    exit;
}

// 3. MANAGEMENT ZONE ACTIONS (Admin Only)
if (isAdmin()) {
    if (isset($_POST['delete_ticket'])) {
        $stmt = $pdo->prepare("DELETE FROM ticket_files WHERE ticket_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("DELETE FROM ticket_comments WHERE ticket_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: dashboard.php');
        exit;
    }
    if (isset($_POST['close_ticket'])) {
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'closed' WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: view_ticket.php?id=$id");
        exit;
    }
}

// 4. ADD COMMENT
if ($_POST && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$id, $_SESSION['user_id'], $comment]);
        header("Location: view_ticket.php?id=$id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $ticket['id']; ?> - Ticket System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 30%, #60a5fa 70%, #93c5fd 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .ticket-header {
            background: rgba(30, 58, 138, 0.05);
            border-bottom: 2px solid #e2e8f0;
            padding: 25px;
        }
        .sidebar-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .comment-bubble {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #edf2f7;
            position: relative;
        }
        /* RESTORED PREVIEW STYLES */
        .hover-zoom { transition: all 0.3s ease; overflow: hidden; cursor: pointer; border-radius: 10px; position: relative; }
        .hover-zoom:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .preview-img, .preview-video { width: 100%; height: 140px; object-fit: cover; }
        .preview-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4); color: white;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: 0.3s;
        }
        .hover-zoom:hover .preview-overlay { opacity: 1; }
        #pdf-template { display: none; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">Ticket System</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link btn btn-danger btn-sm text-white ms-3 px-3" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="glass-card mb-4">
                    <div class="ticket-header d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary mb-2">Ref: #<?php echo $ticket['id']; ?></span>
                            <h3 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></h3>
                        </div>
                        <span class="badge bg-<?php echo $ticket['status']=='open'?'success':'secondary'; ?> py-2 px-3">
                            <?php echo strtoupper($ticket['status']); ?>
                        </span>
                    </div>

                    <div class="p-4">
                        <h6 class="sidebar-label">Initial Report</h6>
                        <div class="bg-light p-4 rounded-3 border mb-4">
                            <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                        </div>

                        <?php
                        $stmt = $pdo->prepare("SELECT tf.* FROM ticket_files tf WHERE tf.ticket_id = ? ORDER BY tf.created_at DESC");
                        $stmt->execute([$id]);
                        $files = $stmt->fetchAll();
                        if (!empty($files)): ?>
                        <h6 class="sidebar-label mb-3">Supporting Files (<?php echo count($files); ?>)</h6>
                        <div class="row g-3 mb-4">
                            <?php foreach ($files as $file): ?>
                            <div class="col-md-4 col-sm-6">
                                <div class="card h-100 border-0 shadow-sm hover-zoom">
                                    <?php if (strpos($file['file_type'], 'image/') === 0): ?>
                                        <img src="uploads/<?php echo $file['filename']; ?>" class="preview-img" onclick="window.open(this.src)">
                                        <div class="preview-overlay"><i class="fas fa-search-plus fa-2x"></i></div>
                                    <?php elseif (strpos($file['file_type'], 'video/') === 0): ?>
                                        <div class="position-relative" onclick="openVideoModal('uploads/<?php echo $file['filename']; ?>', '<?php echo htmlspecialchars($file['original_name']); ?>')">
                                            <video class="preview-video" muted loop><source src="uploads/<?php echo $file['filename']; ?>"></video>
                                            <div class="preview-overlay" style="opacity:1; background:rgba(0,0,0,0.2)"><i class="fas fa-play-circle fa-3x"></i></div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-footer bg-white border-0 p-2 text-center">
                                        <small class="d-block text-truncate fw-bold"><?php echo htmlspecialchars($file['original_name']); ?></small>
                                        <a href="uploads/<?php echo $file['filename']; ?>" download class="btn btn-sm btn-link text-decoration-none p-0">Download</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <hr class="my-4">

                        <h6 class="sidebar-label mb-4">Activity & Comments</h6>
                        <?php
                        $stmt = $pdo->prepare("SELECT tc.*, u.username, u.role, u.profile_image FROM ticket_comments tc JOIN users u ON tc.user_id = u.id WHERE ticket_id = ? ORDER BY tc.created_at ASC");
                        $stmt->execute([$id]);
                        $comments_list = $stmt->fetchAll();
                        foreach ($comments_list as $comment):
                            $img_path = "uploads/profiles/" . ($comment['profile_image'] ?: 'default-avatar.png');
                        ?>
                        <div class="d-flex mb-4">
                            <img src="<?php echo $img_path; ?>" class="rounded-circle me-3" style="width: 45px; height: 45px; object-fit: cover;">
                            <div class="comment-bubble flex-grow-1 shadow-sm">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-bold text-primary small"><?php echo htmlspecialchars($comment['username']); ?></span>
                                    <small class="text-muted"><?php echo date('M j, g:i A', strtotime($comment['created_at'])); ?></small>
                                </div>
                                <p class="mb-0 text-dark small"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <form method="POST" class="mt-4 p-3 bg-white rounded-3 border shadow-sm text-end">
                            <textarea name="comment" rows="2" class="form-control mb-2 shadow-none" placeholder="Write your response..." required></textarea>
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold rounded-pill">Post Reply</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="glass-card p-4 mb-4">
                    <h5 class="fw-bold mb-4">Quick Actions</h5>
                    <button onclick="generatePDF()" class="btn btn-primary w-100 mb-3 shadow-sm">
                        <i class="fas fa-file-pdf me-2"></i>Download as PDF
                    </button>
                    
                    <div class="sidebar-label">PC Name / ID</div>
                    <div class="fw-bold mb-3"><i class="fas fa-desktop me-2 text-primary"></i><?php echo !empty($ticket['pc_remote']) ? htmlspecialchars($ticket['pc_remote']) : 'Not Listed'; ?></div>
                    
                    <div class="sidebar-label">Priority Level</div>
                    <span class="badge w-100 py-2 mb-3 bg-<?php echo $ticket['priority']=='high'?'danger':($ticket['priority']=='medium'?'warning text-dark':'success'); ?>">
                        <?php echo strtoupper($ticket['priority']); ?>
                    </span>
                    
                    <div class="sidebar-label">Date Submitted</div>
                    <div class="small fw-bold text-muted"><?php echo date('M j, Y', strtotime($ticket['created_at'])); ?></div>
                </div>

                <?php if (isAdmin()): ?>
                <div class="glass-card p-4 border-top border-danger border-4">
                    <h6 class="text-danger fw-bold mb-2">Management Zone</h6>
                    <form method="POST" class="mb-2">
                        <button type="submit" name="close_ticket" class="btn btn-outline-dark btn-sm w-100">Close Ticket</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Delete permanently?');">
                        <button type="submit" name="delete_ticket" class="btn btn-danger btn-sm w-100">Remove Ticket</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-dark border-0">
                <div class="modal-header border-0 text-white pb-0">
                    <h6 class="modal-title" id="videoModalLabel">Video Attachment</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <video id="mainVideoPlayer" controls class="w-100" style="max-height: 80vh; background: #000;"><source id="videoSource" src=""></video>
                </div>
            </div>
        </div>
    </div>

    <div id="pdf-template" class="p-5 bg-white text-dark">
        <h1 style="border-bottom: 2px solid #333; padding-bottom: 10px;">Ticket Report #<?php echo $ticket['id']; ?></h1>
        <p><strong>Subject:</strong> <?php echo htmlspecialchars($ticket['subject']); ?></p>
        <p><strong>Status:</strong> <?php echo strtoupper($ticket['status']); ?> | <strong>Priority:</strong> <?php echo strtoupper($ticket['priority']); ?></p>
        <p><strong>Device:</strong> <?php echo htmlspecialchars($ticket['pc_remote']); ?></p>
        <hr>
        <h3>Description</h3>
        <p><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
        <hr>
        <h3>Conversation History</h3>
        <?php foreach ($comments_list as $comment): ?>
            <div style="margin-bottom: 10px; padding: 5px; border-bottom: 1px solid #eee;">
                <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong> <?php echo htmlspecialchars($comment['comment']); ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // PDF Logic
        function generatePDF() {
            const element = document.getElementById('pdf-template');
            element.style.display = 'block';
            html2pdf().set({
                margin: 10,
                filename: 'Ticket_<?php echo $ticket['id']; ?>.pdf',
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            }).from(element).save().then(() => { element.style.display = 'none'; });
        }

        // Video Modal Logic
        const videoModal = new bootstrap.Modal(document.getElementById('videoModal'));
        const mainVideo = document.getElementById('mainVideoPlayer');
        const videoSource = document.getElementById('videoSource');

        function openVideoModal(src, filename) {
            videoSource.src = src;
            document.getElementById('videoModalLabel').innerText = filename;
            mainVideo.load();
            videoModal.show();
            mainVideo.play();
        }

        document.getElementById('videoModal').addEventListener('hidden.bs.modal', () => mainVideo.pause());
    </script>
</body>
</html>