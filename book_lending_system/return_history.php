<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Fetch returned books history
$stmt = $conn->prepare("
    SELECT rb.*, u.username 
    FROM returned_books rb
    JOIN users u ON rb.user_id = u.id
    ORDER BY rb.return_date DESC
");
$stmt->execute();
$returned_books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return History | Book Lending System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #ebedfc;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4cc9f0;
            --success-bg: #e8f7fa;
            --warning: #f8961e;
            --warning-bg: #fff3cd;
            --gray: #6c757d;
            --border-radius: 12px;
            --box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f7ff;
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
            font-size: 1.5rem;
        }

        .nav-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 15px;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        nav {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        nav a {
            padding: 12px 20px;
            background: var(--primary-light);
            color: var(--primary);
            border: none;
            border-radius: 8px;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        nav a:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }

        nav a i {
            margin-right: 8px;
        }

        .table-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }

        tr:hover {
            background-color: #f8f9ff;
        }

        .fine-badge {
            background-color: #ffebee;
            color: var(--danger);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .no-books {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .no-books i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .no-books h3 {
            color: var(--gray);
            margin-bottom: 10px;
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
                gap: 10px;
            }

            nav a {
                width: 100%;
                justify-content: center;
            }

            .table-container {
                padding: 10px;
            }

            th, td {
                padding: 10px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-history"></i>
                Book Lending System
            </div>
        </div>
    </header>

    <div class="container">
        <div class="nav-container">
            <nav>
                <a href="user_dashboard.php">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="view_books.php">
                    <i class="fas fa-book"></i>
                    View Books
                </a>
                <a href="view_borrowed_books.php">
                    <i class="fas fa-hand-holding"></i>
                    Borrowed Books
                </a>
                <a href="return_history.php" class="active">
                    <i class="fas fa-history"></i>
                    Return History
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </nav>
        </div>

        <div class="table-container">
            <h2><i class="fas fa-history"></i> Return History</h2>
            <?php if (empty($returned_books)): ?>
                <div class="no-books">
                    <i class="fas fa-history"></i>
                    <h3>No Return History</h3>
                    <p>You haven't returned any books yet.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Borrowed By</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Fine Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returned_books as $book): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['username']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($book['borrow_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($book['return_date'])); ?></td>
                                <td>
                                    <?php if ($book['fine_amount'] > 0): ?>
                                        <span class="fine-badge">₹<?php echo number_format($book['fine_amount'], 2); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>