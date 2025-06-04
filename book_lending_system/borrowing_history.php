<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch borrowing history for the user
$stmt = $conn->prepare("
    SELECT b.book_name, b.author, bb.borrowed_date, bb.due_date, bb.return_date, bb.fine_amount 
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.id
    WHERE bb.user_id = ?
");
$stmt->execute([$user_id]);
$borrowed_books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Borrowing History</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Borrowing History</h1>
    <nav>
        <a href="user_dashboard.php">Back to Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>

    <table border="1">
        <tr>
            <th>Book Name</th>
            <th>Author</th>
            <th>Borrowed Date</th>
            <th>Due Date</th>
            <th>Return Date</th>
            <th>Fine Amount</th>
        </tr>
        <?php foreach ($borrowed_books as $book): ?>
        <tr>
            <td><?php echo htmlspecialchars($book['book_name']); ?></td>
            <td><?php echo htmlspecialchars($book['author']); ?></td>
            <td><?php echo htmlspecialchars($book['borrowed_date']); ?></td>
            <td><?php echo htmlspecialchars($book['due_date']); ?></td>
            <td><?php echo htmlspecialchars($book['return_date'] ?? 'Not returned'); ?></td>
            <td><?php echo htmlspecialchars($book['fine_amount']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>