<?php
require_once 'config.php';

// Check if user is admin and if any data was sent
if (!isAdmin() || (!isset($_POST['ticket_id']) && !isset($_POST['ticket_ids']))) {
    header('Location: admin_tickets.php');
    exit;
}

try {
    // --- 1. HANDLE BULK DELETION (Checklist) ---
    if (isset($_POST['ticket_ids']) && is_array($_POST['ticket_ids'])) {
        $ids = $_POST['ticket_ids'];
        
        // Sanitize all IDs to integers
        $ids = array_map('intval', $ids);
        
        // Create placeholders: ?,?,?
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'deleted' WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        
        $count = $stmt->rowCount();
        $_SESSION['success'] = "Successfully archived $count selected tickets.";
    } 
    
    // --- 2. HANDLE SINGLE DELETION (Button) ---
    else if (isset($_POST['ticket_id'])) {
        $id = (int)$_POST['ticket_id'];
        
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Ticket #$id archived and moved to history.";
        } else {
            $_SESSION['error'] = "Could not update ticket. Check if ID exists.";
        }
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Database Error: " . $e->getMessage();
}

// Redirect back to the inventory
header('Location: admin_tickets.php');
exit;