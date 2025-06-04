<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Check if the book ID is provided in the URL
if (isset($_GET['id'])) {
    $book_id = $_GET['id'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // First delete associated records from returned_books table
        $stmt = $conn->prepare("DELETE FROM returned_books WHERE book_id = ?");
        $stmt->execute([$book_id]);

        // Then delete associated records from borrowed_books table
        $stmt = $conn->prepare("DELETE FROM borrowed_books WHERE book_id = ?");
        $stmt->execute([$book_id]);

        // Finally delete the book
        $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$book_id]);

        // Commit transaction
        $conn->commit();
        
        // Set success message in session
        $_SESSION['message'] = "Book deleted successfully!";
        $_SESSION['message_type'] = "success";
        
        // Redirect back to admin dashboard
        header('Location: admin_dashboard.php');
        exit();
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        
        // Set error message in session
        $_SESSION['message'] = "Failed to delete book: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        
        // Redirect back to admin dashboard
        header('Location: admin_dashboard.php');
        exit();
    }
} else {
    // Set error message in session
    $_SESSION['message'] = "Invalid request!";
    $_SESSION['message_type'] = "error";
    
    // Redirect back to admin dashboard
    header('Location: admin_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Book</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Delete Book</h1>
    <nav>
        <a href="admin_dashboard.php">Back to Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</body>
</html>