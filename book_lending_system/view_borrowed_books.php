<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Fetch borrowed books for the current user
$stmt = $conn->prepare("
    SELECT b.*, books.title, books.author, u.username
    FROM borrowed_books b
    JOIN books ON b.book_id = books.id
    JOIN users u ON b.user_id = u.id
    WHERE b.return_date IS NULL AND b.user_id = ?
    ORDER BY b.borrow_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$borrowed_books = $stmt->fetchAll();

// Function to calculate time remaining or overdue status
function calculateTimeStatus($due_date) {
    $now = new DateTime();
    $due = new DateTime($due_date);
    $interval = $now->diff($due);
    
    if ($interval->invert) {
        // Calculate minutes overdue
        $total_minutes_overdue = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
        // Calculate fine (₹10 per minute) but cap at ₹10
        $fine = min(($total_minutes_overdue * 10), 10);
        
        return [
            'status' => 'overdue',
            'message' => 'Overdue by: ' . $interval->format('%a days, %h hours, %i minutes'),
            'fine' => $fine
        ];
    } else {
        return [
            'status' => 'active',
            'message' => 'Time remaining: ' . $interval->format('%a days, %h hours, %i minutes'),
            'fine' => 0
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrowed Books | Book Lending System</title>
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

        .books-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .book-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }

        .book-card:hover {
            transform: translateY(-5px);
        }

        .book-title {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .book-title i {
            margin-right: 10px;
            color: var(--accent);
        }

        .book-details {
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed rgba(67, 97, 238, 0.2);
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            width: 120px;
            color: var(--gray);
            display: flex;
            align-items: center;
        }

        .detail-label i {
            margin-right: 8px;
            width: 20px;
            color: var(--primary);
        }

        .detail-value {
            flex: 1;
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .status-badge.active {
            background: var(--success-bg);
            color: var(--success);
        }

        .status-badge.overdue {
            background: #ffebee;
            color: var(--danger);
        }

        .status-badge i {
            margin-right: 8px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 15px;
            width: 100%;
        }

        .return-btn {
            background: var(--primary);
            color: white;
        }

        .return-btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
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

            .books-container {
                grid-template-columns: 1fr;
            }
        }

        /* Table Styles */
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

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .return-btn {
            background-color: var(--success);
            color: white;
        }

        .return-btn:hover {
            background-color: #3db8d8;
        }

        .fine-badge {
            background-color: #ffebee;
            color: var(--danger);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .message {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .message.success {
            background-color: var(--success-bg);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .message.error {
            background-color: #fde8e8;
            color: #e53e3e;
            border: 1px solid #e53e3e;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .return-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .return-btn:hover {
            background: #3ab7d8;
            transform: translateY(-2px);
        }

        .return-btn i {
            font-size: 1.1rem;
        }

        .return-btn.disabled {
            background: var(--gray);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .return-btn.disabled:hover {
            transform: none;
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
                <a href="return_history.php">
                    <i class="fas fa-history"></i>
                    Return History
                </a>
            </nav>
        </div>

        <div class="table-container">
            <h2><i class="fas fa-book"></i> Borrowed Books</h2>
            <?php if (empty($borrowed_books)): ?>
                <div class="no-books">
                    <i class="fas fa-book-open"></i>
                    <h3>No books borrowed yet</h3>
                    <p>Visit the library to borrow some books!</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Borrowed On</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Fine</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowed_books as $book): 
                            $time_status = calculateTimeStatus($book['due_date']);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($book['borrow_date'])); ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($book['due_date'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $time_status['status']; ?>">
                                    <?php echo $time_status['message']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($time_status['fine'] > 0): ?>
                                    <span class="fine-badge">₹<?php echo number_format($time_status['fine'], 2); ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="return_book.php?borrow_id=<?php echo $book['id']; ?>" class="return-btn">
                                    <i class="fas fa-undo"></i>
                                    Return
                                </a>
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