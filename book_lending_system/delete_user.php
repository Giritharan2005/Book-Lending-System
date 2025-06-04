<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Check if the user ID is provided in the URL
if (!isset($_GET['id'])) {
    header('Location: admin_manage_users.php');
    exit();
}

$user_id = $_GET['id'];

try {
    // Start a transaction to ensure data integrity
    $conn->beginTransaction();
    
    // First, check if the user exists
    $check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $check_user->execute([$user_id]);
    
    if ($check_user->rowCount() === 0) {
        // User doesn't exist
        $_SESSION['message'] = "User not found.";
        header('Location: admin_manage_users.php');
        exit();
    }
    
    // Delete any borrowed books records for this user
    // Note: In a real system, you might want to handle this differently
    // For example, you might want to mark the books as returned before deleting the user
    $delete_borrowed = $conn->prepare("DELETE FROM borrowed_books WHERE user_id = ?");
    $delete_borrowed->execute([$user_id]);
    
    // Delete any returned books records for this user
    $delete_returned = $conn->prepare("DELETE FROM returned_books WHERE user_id = ?");
    $delete_returned->execute([$user_id]);
    
    // Delete the user
    $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delete_user->execute([$user_id]);
    
    // Commit the transaction
    $conn->commit();
    
    $_SESSION['message'] = "User deleted successfully.";
} catch (PDOException $e) {
    // Rollback the transaction if something failed
    $conn->rollBack();
    $_SESSION['message'] = "Error deleting user: " . $e->getMessage();
}

// Redirect back to the manage users page
header('Location: admin_manage_users.php');
exit();
?>