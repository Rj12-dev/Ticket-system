<?php 
require_once 'config.php'; 
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_POST) {
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];
    $pc_remote = trim($_POST['pc_remote'] ?? ''); 
    
    // --- STEP 1: CREATE TICKET ---
    $stmt = $pdo->prepare("
        INSERT INTO tickets (user_id, subject, description, priority, assigned_to, pc_remote) 
        VALUES (?, ?, ?, ?, 1, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $subject, $description, $priority, $pc_remote]);
    $ticket_id = $pdo->lastInsertId();
    
    // --- STEP 2: MULTIPLE FILE UPLOAD ---
    if (isset($_FILES['attachment']) && !empty($_FILES['attachment']['name'][0])) {
        $files = $_FILES['attachment'];
        $allowed_types = ['image/jpeg','image/png','image/gif','video/mp4','video/avi','video/mov'];
        $max_size = 500 * 1024 * 1024; // 500MB internal limit
        
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // Loop through each uploaded file
        foreach ($files['name'] as $key => $val) {
            $file_error = $files['error'][$key];
            
            if ($file_error == 0) {
                $file_tmp  = $files['tmp_name'][$key];
                $file_type = $files['type'][$key];
                $file_size = $files['size'][$key];
                $file_name = $files['name'][$key];
                
                if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '_' . time() . '_' . $key . '.' . $file_ext;
                    $target = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($file_tmp, $target)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO ticket_files (ticket_id, filename, original_name, file_type, file_size, uploaded_by) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$ticket_id, $new_filename, $file_name, $file_type, $file_size, $_SESSION['user_id']]);
                    }
                }
            }
        }
    }
    
    // --- STEP 3: REDIRECT ---
    header('Location: view_ticket.php?id=' . $ticket_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Support Ticket - Ticket System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 30%, #60a5fa 70%, #93c5fd 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .form-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 750px;
            padding: 45px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .form-label {
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 18px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }

        .input-group-text {
            background-color: #f1f5f9;
            border-radius: 12px 0 0 12px;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .btn-submit {
            background: linear-gradient(45deg, #1e3a8a, #2563eb);
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.3);
        }

        .cancel-link {
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            transition: color 0.3s;
        }

        .cancel-link:hover { color: #dc2626; }

        .logo-box {
            background: white;
            padding: 8px;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: inline-block;
        }
    </style>
</head>
<body>

    <div class="form-card">
        <div class="text-center mb-5">
            <div class="logo-box mb-3">
                <img src="images/logo.png" alt="Logo" height="65" width="65" class="rounded-circle" style="object-fit: cover;">
            </div>
            <h2 class="fw-bold text-dark mb-1">New Support Request</h2>
            <p class="text-muted">Fill in the details below. Our IT team will review it shortly.</p>
        </div>

        <form id="ticketForm" method="POST" enctype="multipart/form-data">
            
            <div class="row">
                <div class="col-md-8 mb-4">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                        <input type="text" name="subject" class="form-control" placeholder="Ex: System Login Error" required>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="low">🔵 Low</option>
                        <option value="medium" selected>🟡 Medium</option>
                        <option value="high">🔴 High</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Detailed Description <span class="text-danger">*</span></label>
                <textarea name="description" rows="5" class="form-control" placeholder="Please explain the issue step-by-step..." required></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label">PC Name / Remote ID</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-desktop"></i></span>
                        <input type="text" name="pc_remote" class="form-control" placeholder="Ex: PC-OFFICE-01">
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <label class="form-label">Attachments (Max 100MB)</label>
                    <input type="file" name="attachment[]" class="form-control" accept="image/*,video/mp4,video/avi,video/mov" multiple>
                </div>
            </div>

            <div class="d-grid gap-3 mt-2">
                <button type="submit" id="submitBtn" class="btn btn-primary btn-submit text-white">
                    <i class="fas fa-paper-plane me-2"></i>Create Ticket
                </button>
                <div class="text-center">
                    <a href="dashboard.php" class="cancel-link">
                        <i class="fas fa-times me-1"></i> Cancel and Return
                    </a>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent double submission and show loading state
        document.getElementById('ticketForm').addEventListener('submit', function() {
            var btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing Uploads...';
        });
    </script>
</body>
</html>