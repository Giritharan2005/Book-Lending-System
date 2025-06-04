<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch all borrowed books
try {
    $stmt = $pdo->prepare("
        SELECT bb.id, b.book_name, b.author, u.username, 
               bb.borrowed_date, bb.due_date, bb.return_date, bb.fine_amount
        FROM borrowed_books bb
        JOIN books b ON bb.book_id = b.id
        JOIN users u ON bb.user_id = u.id
        ORDER BY bb.borrowed_date DESC
    ");
    $stmt->execute();
    $borrowed_books = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error fetching borrowed books: ' . $e->getMessage();
    header('Location: ../admin_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Books | Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>

    <div class="container">
        <h1 class="page-title"><i class="fas fa-book-reader"></i> Borrowed Books</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>All Borrowed Books</h2>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Book Name</th>
                            <th>Author</th>
                            <th>Borrowed By</th>
                            <th>Borrowed Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Fine Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($borrowed_books)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No borrowed books found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($borrowed_books as $book): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($book['book_name']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['username']); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($book['borrowed_date'])); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($book['due_date'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($book['return_date']) {
                                            echo date('d M Y H:i', strtotime($book['return_date']));
                                        } else {
                                            echo '<span class="text-danger">Not returned</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($book['fine_amount'] > 0) {
                                            echo '₱' . number_format($book['fine_amount'], 2);
                                        } else {
                                            echo '₱0.00';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if (!$book['return_date']): ?>
                                                <a href="../return_book.php?id=<?php echo $book['id']; ?>" 
                                                   class="btn btn-primary btn-sm" 
                                                   onclick="return confirm('Are you sure you want to mark this book as returned?')">
                                                    <i class="fas fa-check"></i> Mark Returned
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>
