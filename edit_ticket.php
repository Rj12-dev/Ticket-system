<?php 
require_once 'config.php'; 
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$ticket_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT t.*, u.username 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticket_id, $_SESSION['user_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: dashboard.php');
    exit;
}

// UPDATE TICKET
if ($_POST) {
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];
    $pc_remote = trim($_POST['pc_remote'] ?? '');
    
    $stmt = $pdo->prepare("
        UPDATE tickets 
        SET subject = ?, description = ?, priority = ?, pc_remote = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$subject, $description, $priority, $pc_remote, $ticket_id, $_SESSION['user_id']]);

}

 
    
    // 🔥 MULTIPLE FILE UPLOAD HANDLER (PASTE HERE)
if (!empty($_FILES['attachments']['name'][0])) {
    $upload_dir = 'uploads/';
    
    // Create uploads folder if missing
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Loop through ALL uploaded files
    foreach ($_FILES['attachments']['name'] as $key => $original_name) {
        if ($_FILES['attachments']['error'][$key] == 0 && !empty($original_name)) {
            $tmp_name = $_FILES['attachments']['tmp_name'][$key];
            $file_type = $_FILES['attachments']['type'][$key];
            $file_size = $_FILES['attachments']['size'][$key];
            
            // Validate file
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 
                              'video/mp4', 'video/avi', 'video/mov'];
            $max_size = 10 * 1024 * 1024; // 10MB
            
            if (in_array($file_type, $allowed_types) && $file_size <= $max_size && $file_size > 0) {
                // Generate unique filename
                $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $filename = uniqid('file_') . '_' . time() . '_' . $key . '.' . $file_ext;
                $target_path = $upload_dir . $filename;
                
                // Move file
                if (move_uploaded_file($tmp_name, $target_path)) {
                    // Save to database
                    $stmt = $pdo->prepare("
                        INSERT INTO ticket_files 
                        (ticket_id, filename, original_name, file_type, file_size, uploaded_by) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $ticket_id, 
                        $filename, 
                        $original_name, 
                        $file_type, 
                        $file_size, 
                        $_SESSION['user_id']
                    ]);
                } else {
                    error_log("Failed to upload: " . $original_name);
                }
            } else {
                error_log("Invalid file: " . $original_name . " Type: " . $file_type . " Size: " . $file_size);
            }
        }
    }
}



?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Ticket #<?php echo $ticket['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Ticket System</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="view_ticket.php?id=<?php echo $ticket_id; ?>">View Ticket</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-warning text-dark py-4">
                        <h2 class="mb-0 fw-bold">
                            <i class="fas fa-edit me-3"></i>
                            Edit Ticket #<?php echo $ticket['id']; ?>
                        </h2>
                    </div>
                    <div class="card-body p-5">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <!-- SUBJECT -->
                            <div class="mb-4">
                                <label class="form-label fw-bold fs-5 mb-2">
                                    Subject <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="subject" class="form-control form-control-lg" 
                                       value="<?php echo htmlspecialchars($ticket['subject']); ?>" required>
                            </div>

                            <!-- PRIORITY -->
                            <div class="mb-4">
                                <label class="form-label fw-bold fs-5 mb-2">Priority</label>
                                <select name="priority" class="form-select form-select-lg">
                                    <option value="low" <?php echo $ticket['priority']=='low'?'selected':'';?>>Low</option>
                                    <option value="medium" <?php echo $ticket['priority']=='medium'?'selected':'';?>>Medium</option>
                                    <option value="high" <?php echo $ticket['priority']=='high'?'selected':'';?>>High</option>
                                </select>
                            </div>

                            <!-- DESCRIPTION -->
                            <div class="mb-4">
                                <label class="form-label fw-bold fs-5 mb-2">
                                    Description <span class="text-danger">*</span>
                                </label>
                                <textarea name="description" rows="6" class="form-control form-control-lg" 
                                          required><?php echo htmlspecialchars($ticket['description']); ?></textarea>
                            </div>

                           
                            <!-- ADD MORE FILES -->
                            <!-- 🔥 MULTIPLE FILE UPLOAD (PASTE HERE) -->
<div id="file-upload-section" class="mb-5">
    <label class="form-label fw-bold fs-5 mb-3">
        📎 Attach Files 
        <span class="badge bg-success fs-6">Multiple Files OK</span>
    </label>
    
    <!-- DROP ZONE -->
    <div class="drop-zone border border-2 border-dashed border-primary rounded-4 p-5 mb-3 text-center hover-drop"
         id="fileDropZone" style="transition: all 0.3s ease;">
        <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3 d-block"></i>
        <h5 class="text-primary mb-1 fw-bold">Drag & Drop Files Here</h5>
        <p class="text-muted small mb-3">or <strong>Click to Browse</strong></p>
        <p class="text-muted small mb-0">JPG, PNG, GIF, MP4, AVI, MOV • Max 10MB each</p>
        
        <!-- HIDDEN INPUT -->
        <input type="file" name="attachments[]" id="fileInput" class="d-none mt-3" 
               multiple accept="image/jpeg,image/png,image/gif,video/mp4,video/avi,video/mov">
    </div>
    
    <!-- FILE PREVIEW LIST -->
    <div id="filePreviewList" class="mb-4"></div>
    
    <!-- UPLOAD PROGRESS -->
    <div class="progress mb-3 d-none" id="uploadProgress" style="height: 8px;">
        <div class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
    </div>
</div>

<!-- 🔥 JAVASCRIPT (PASTE BEFORE </body>) -->
<script>
const dropZone = document.getElementById('fileDropZone');
const fileInput = document.getElementById('fileInput');
const previewList = document.getElementById('filePreviewList');
const progressBar = document.getElementById('uploadProgress');

// Click to open file dialog
dropZone.addEventListener('click', () => fileInput.click());

// Drag & Drop
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-warning', 'bg-warning-subtle');
});
dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-warning', 'bg-warning-subtle');
});
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-warning', 'bg-warning-subtle');
    handleFiles(e.dataTransfer.files);
});

// File input change
fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

// Handle files
function handleFiles(files) {
    Array.from(files).forEach(file => {
        // Validate
        if (file.size > 10*1024*1024) {
            alert(`${file.name} too big! Max 10MB`);
            return;
        }
        const allowed = ['image/jpeg','image/png','image/gif','video/mp4','video/avi','video/mov'];
        if (!allowed.includes(file.type)) {
            alert(`${file.name} not allowed!`);
            return;
        }
        
        // Create preview
        const div = document.createElement('div');
        div.className = 'file-preview-item border rounded p-3 mb-2 d-flex align-items-center bg-light hover-shadow';
        div.style.transition = 'all 0.3s ease';
        
        const thumb = document.createElement('div');
        thumb.className = 'flex-shrink-0 me-3';
        if (file.type.startsWith('image/')) {
            thumb.innerHTML = `<img src="${URL.createObjectURL(file)}" class="rounded shadow-sm" style="width:60px;height:60px;object-fit:cover;">`;
        } else {
            thumb.innerHTML = `<video src="${URL.createObjectURL(file)}" class="rounded shadow-sm" muted style="width:60px;height:60px;object-fit:cover;" preload="metadata"></video>`;
        }
        
        const info = document.createElement('div');
        info.className = 'flex-grow-1';
        info.innerHTML = `
            <div class="fw-bold small mb-1">${file.name}</div>
            <small class="text-muted">${Math.round(file.size/1024)} KB</small>
        `;
        
        const removeBtn = document.createElement('button');
        removeBtn.className = 'btn btn-sm btn-outline-danger ms-2';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.onclick = () => div.remove();
        
        div.appendChild(thumb);
        div.appendChild(info);
        div.appendChild(removeBtn);
        previewList.appendChild(div);
    });
}

// Hover effect
document.querySelectorAll('.file-preview-item').forEach(item => {
    item.addEventListener('mouseenter', () => item.style.transform = 'translateY(-2px)');
    item.addEventListener('mouseleave', () => item.style.transform = 'translateY(0)');
});
</script>

<style>
/* 🔥 MULTIPLE UPLOAD STYLES */
.drop-zone {
    border: 3px dashed #0d6efd !important;
    transition: all 0.3s ease;
    cursor: pointer;
}
.drop-zone:hover {
    border-color: #ffc107 !important;
    background-color: rgba(255, 193, 7, 0.1) !important;
    transform: scale(1.02);
}
.hover-drop:hover {
    border-color: #28a745 !important;
}
.hover-shadow:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.2) !important;
}
.file-preview-item:hover {
    background-color: #f8f9fa !important;
}
</style>
                            <!-- BUTTONS -->
                            <div class="d-grid gap-3 d-md-flex justify-content-between">
                                <button type="submit" class="btn btn-warning btn-lg px-5 py-3 flex-fill">
                                    <i class="fas fa-save me-2"></i>
                                    <strong>Update Ticket</strong>
                                </button>
                                <a href="view_ticket.php?id=<?php echo $ticket_id; ?>" 
                                   class="btn btn-outline-primary btn-lg px-5 py-3 flex-fill">
                                    <i class="fas fa-eye me-2"></i>
                                    <strong>View Ticket</strong>
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-secondary btn-lg px-5 py-3 flex-fill">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    <strong>Dashboard</strong>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>