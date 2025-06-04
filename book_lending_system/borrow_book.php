<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Check if the book ID is provided in the URL
if (isset($_GET['book_id'])) {
    $book_id = $_GET['book_id'];
    $user_id = $_SESSION['user_id'];

    // Fetch book details
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        echo "<div class='message error'>Book not found!</div>";
        exit();
    }

    // Check if the book is available
    if ($book['available_quantity'] <= 0) {
        echo "<div class='message error'>No copies available for borrowing.</div>";
        exit();
    }

    // Check if user already has an active borrow for this book
    $stmt = $conn->prepare("SELECT * FROM borrowed_books 
                           WHERE user_id = ? AND book_id = ? AND return_date IS NULL");
    $stmt->execute([$user_id, $book_id]);
    $existing_borrow = $stmt->fetch();

    if ($existing_borrow) {
        echo "<div class='message error'>You have already borrowed this book and not returned it yet.</div>";
        exit();
    }

    // Generate borrow date and due date
    $borrow_date = date('Y-m-d H:i:s');
    $due_date = date('Y-m-d H:i:s', strtotime('+15 days')); // Set due date to 15 days after borrow date

    // Calculate time remaining
    $now = new DateTime();
    $due = new DateTime($due_date);
    $interval = $now->diff($due);
    
    $time_remaining = "";
    if ($interval->invert) {
        $time_remaining = "Overdue by: " . $interval->format('%a days, %h hours, %i minutes');
    } else {
        $time_remaining = "Time remaining: " . $interval->format('%a days, %h hours, %i minutes');
    }

    // Start a transaction
    $conn->beginTransaction();

    try {
        // Insert into borrowed_books table
        $stmt = $conn->prepare("INSERT INTO borrowed_books (user_id, book_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $book_id, $borrow_date, $due_date]);

        // Update the available_quantity in the books table
        $stmt = $conn->prepare("UPDATE books SET available_quantity = available_quantity - 1 WHERE id = ?");
        $stmt->execute([$book_id]);

        // Commit the transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollBack();
        $error_message = "Failed to borrow book. Please try again.";
    }
} else {
    $error_message = "Invalid request!";
    exit();
}

// Function to check for overdue books and calculate fines
function checkOverdueBooks($conn, $user_id) {
    $current_time = date('Y-m-d H:i:s');
    $fine_per_day = 10; // ₹10 per day
    
    // Get all overdue books for this user
    $stmt = $conn->prepare("SELECT * FROM borrowed_books 
                           WHERE user_id = ? AND return_date IS NULL AND due_date < ?");
    $stmt->execute([$user_id, $current_time]);
    $overdue_books = $stmt->fetchAll();
    
    foreach ($overdue_books as $book) {
        $due_date = new DateTime($book['due_date']);
        $current_date = new DateTime();
        $interval = $current_date->diff($due_date);
        
        // Calculate total days overdue
        $total_days_overdue = $interval->days;
        
        // Calculate fine amount (₹10 per day)
        $fine_amount = $total_days_overdue * $fine_per_day;
        
        // Update the fine amount in the database
        $stmt = $conn->prepare("UPDATE borrowed_books SET fine_amount = ? WHERE id = ?");
        $stmt->execute([$fine_amount, $book['id']]);
    }
}

// Check for overdue books for this user
checkOverdueBooks($conn, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Borrow Book</title>
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

        .message {
            max-width: 800px;
            margin: 20px auto;
            padding: 25px;
            text-align: center;
            border-radius: var(--border-radius);
            font-weight: 500;
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .message.success {
            background: var(--success-bg);
            color: var(--success);
            border-left: 4px solid var(--success);
            margin: 20px auto;
            max-width: 800px;
            padding: 30px;
            text-align: center;
        }

        .message.success h2 {
            color: var(--success);
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .borrow-details {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
            text-align: left;
        }

        .borrow-details p {
            margin: 10px 0;
            font-size: 1.1rem;
            color: var(--dark);
        }

        .borrow-id {
            font-family: monospace;
            font-size: 1.1rem;
            color: var(--primary);
            font-weight: 600;
        }

        .time-remaining {
            color: var(--success);
            font-weight: 600;
        }

        .message.error {
            background: #ffebee;
            color: var(--danger);
            border-left: 4px solid var(--danger);
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

            .detail-row {
                flex-direction: column;
                gap: 5px;
            }

            .detail-label {
                width: 100%;
                margin-bottom: 4px;
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

    <?php if (isset($borrow_date)): ?>
    <div class="message success">
        <h2>Book borrowed successfully!</h2>
        <div class="borrow-details">
            <p>Borrowed Date: <?php echo date('d M Y H:i', strtotime($borrow_date)); ?></p>
            <p>Due Date: <?php echo date('d M Y H:i', strtotime($due_date)); ?></p>
            <p><?php echo $time_remaining; ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="message error">
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>

    <div class="container">
        <div class="nav-container">
            <nav>
                <a href="user_dashboard.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <a href="view_borrowed_books.php">
                    <i class="fas fa-book-reader"></i>
                    View Borrowed Books
                </a>
                <a href="return_history.php">
                    <i class="fas fa-history"></i>
                    Return History
                </a>
            </nav>
        </div>
    </div>

    <script>
        // Auto-refresh the page every 60 seconds to check for overdue books
        setTimeout(function(){
            window.location.reload();
        }, 60000);
    </script>
</body>
</html>