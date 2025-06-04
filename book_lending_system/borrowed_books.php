<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'];

// Fetch borrowed books for the user
$stmt = $conn->prepare("
    SELECT b.id, books.title, books.author, b.borrow_date, b.due_date, b.return_date, b.fine_amount 
    FROM borrowed_books b
    JOIN books ON b.book_id = books.id
    WHERE b.user_id = ? AND b.return_date IS NULL
");
$stmt->execute([$user_id]);
$borrowed_books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Books | Book Lending System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --warning: #f8961e;
            --gray: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 0;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 10px;
        }

        .nav-container {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        nav {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        nav a {
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }

        nav a:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        nav a i {
            margin-right: 8px;
        }

        .books-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            position: relative;
            padding-left: 15px;
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 5px;
            height: 70%;
            width: 5px;
            background: var(--primary);
            border-radius: 5px;
        }

        .books-table {
            width: 100%;
            border-collapse: collapse;
        }

        .books-table th {
            background-color: var(--primary);
            color: white;
            padding: 15px;
            text-align: left;
        }

        .books-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .books-table tr:last-child td {
            border-bottom: none;
        }

        .books-table tr:hover {
            background-color: #f8f9fa;
        }

        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .return-btn {
            background-color: var(--success);
            color: white;
        }

        .return-btn:hover {
            background-color: #3ab0d3;
        }

        .logout-btn {
            background-color: var(--danger);
            color: white;
        }

        .logout-btn:hover {
            background-color: #e5177a;
        }

        .action-btn i {
            margin-right: 5px;
        }

        .message {
            text-align: center;
            padding: 30px;
            color: var(--gray);
            font-size: 1.1rem;
        }

        .borrow-id {
            font-family: monospace;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-overdue {
            background-color: #ffebee;
            color: var(--danger);
        }

        .status-ontime {
            background-color: #e8f5e9;
            color: var(--success);
        }

        .pay-fine-btn {
            background-color: var(--warning);
            color: white;
            margin-left: 10px;
        }

        .pay-fine-btn:hover {
            background-color: #e68a00;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .logo {
                margin-bottom: 15px;
            }

            nav {
                flex-direction: column;
            }

            nav a {
                width: 100%;
                justify-content: center;
            }

            .books-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-book-open"></i>
                <span>Book Lending System</span>
            </div>
            <div class="user-info">
                <a href="logout.php" class="action-btn logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_class']; ?>">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_class']);
                ?>
            </div>
        <?php endif; ?>

        <div class="nav-container">
            <nav>
                <a href="user_dashboard.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <a href="view_books.php">
                    <i class="fas fa-book"></i>
                    View All Books
                </a>
            </nav>
        </div>

        <div class="books-container">
            <h2 class="section-title">My Borrowed Books</h2>
            
            <?php if (count($borrowed_books) > 0): ?>
                <table class="books-table">
                    <thead>
                        <tr>
                            <th>Borrow ID</th>
                            <th>Book Name</th>
                            <th>Author</th>
                            <th>Borrowed Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowed_books as $book): 
                            $due_date = new DateTime($book['due_date']);
                            $today = new DateTime();
                            $is_overdue = $today > $due_date;
                            $status_class = $is_overdue ? 'status-overdue' : 'status-ontime';
                            $status_text = $is_overdue ? 'Overdue' : 'On Time';
                        ?>
                        <tr>
                            <td><span class="borrow-id"><?php echo htmlspecialchars($book['id']); ?></span></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['borrow_date']); ?></td>
                            <td><?php echo htmlspecialchars($book['due_date']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($is_overdue): ?>
                                    <a href="payment.php" class="action-btn pay-fine-btn">
                                        <i class="fas fa-rupee-sign"></i>
                                        Pay Fine
                                    </a>
                                <?php else: ?>
                                    <form method="POST" action="return_book.php" style="display: inline;">
                                        <input type="hidden" name="borrowed_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" class="action-btn return-btn">
                                            <i class="fas fa-undo"></i>
                                            Return
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="message">
                    <i class="fas fa-book-open"></i>
                    <p>You haven't borrowed any books yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>