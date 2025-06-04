<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

// Re-establish database connection
require_once 'db.php';

// Check if user_id is provided
if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    $_SESSION['message'] = "Invalid user ID provided.";
    $_SESSION['message_class'] = "error";
    header('Location: admin_manage_users.php');
    exit();
}

$user_id = (int)$_POST['user_id'];

try {
    // Start transaction with timeout
    $conn->setAttribute(PDO::ATTR_TIMEOUT, 30);
    $conn->beginTransaction();

    // First, delete any returned books records for this user
    $stmt = $conn->prepare("DELETE FROM returned_books WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Then delete any borrowed books records for this user
    $stmt = $conn->prepare("DELETE FROM borrowed_books WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Finally, delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    // Commit the transaction
    $conn->commit();

    $_SESSION['message'] = "User and all associated records have been permanently deleted.";
    $_SESSION['message_class'] = "success";
} catch (PDOException $e) {
    // Rollback the transaction in case of error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $_SESSION['message'] = "Error deleting user: " . $e->getMessage();
    $_SESSION['message_class'] = "error";
} finally {
    // Close the connection
    $conn = null;
}

// Redirect back to manage users page
header('Location: admin_manage_users.php');
exit();
