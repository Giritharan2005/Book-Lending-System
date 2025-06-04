<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Check if the borrow ID is provided in the URL
if (isset($_GET['borrow_id'])) {
    $borrow_id = $_GET['borrow_id'];
    $user_id = $_SESSION['user_id'];
    $return_date = date('Y-m-d H:i:s');

    // Check if the book is actually borrowed by this user
    $stmt = $conn->prepare("SELECT * FROM borrowed_books 
                           WHERE id = ? AND user_id = ? AND return_date IS NULL");
    $stmt->execute([$borrow_id, $user_id]);
    $borrow = $stmt->fetch();

    if (!$borrow) {
        echo "<div class='message error'>Invalid borrow record or book already returned.</div>";
        exit();
    }

    // Calculate fine if the book is returned late
    $current_date = new DateTime();
    $due_date = new DateTime($borrow['due_date']);
    $fine_amount = 0;

    if ($current_date > $due_date) {
        $interval = $current_date->diff($due_date);
        $days_overdue = $interval->days;
        $fine_amount = $days_overdue * 10; // ₹10 per day
        
        // If there's a fine, redirect to payment page
        if ($fine_amount > 0) {
            $_SESSION['fine_amount'] = $fine_amount;
            $_SESSION['borrowed_id'] = $borrow_id;
            header('Location: payment.php');
            exit();
        }
    }

    // Start a transaction
    $conn->beginTransaction();

    try {
        // Update the borrowed_books record
        $stmt = $conn->prepare("UPDATE borrowed_books 
                               SET return_date = ?, fine_amount = ? 
                               WHERE id = ?");
        $stmt->execute([$return_date, $fine_amount, $borrow_id]);

        // Update the available_quantity in the books table
        $stmt = $conn->prepare("UPDATE books 
                               SET available_quantity = available_quantity + 1 
                               WHERE id = ?");
        $stmt->execute([$borrow['book_id']]);

        // Get book title
        $stmt = $conn->prepare("SELECT title FROM books WHERE id = ?");
        $stmt->execute([$borrow['book_id']]);
        $book = $stmt->fetch();

        // Insert into returned_books table
        $stmt = $conn->prepare("INSERT INTO returned_books 
                               (book_id, user_id, borrow_id, title, borrow_date, due_date, return_date, fine_amount) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $borrow['book_id'],
            $user_id,
            $borrow_id,
            $book['title'],
            $borrow['borrow_date'],
            $borrow['due_date'],
            $return_date,
            $fine_amount
        ]);

        // Commit the transaction
        $conn->commit();
        
        // Set success message with book name and redirect
        $_SESSION['message'] = "Book '{$book['title']}' has been returned successfully!";
        $_SESSION['message_class'] = "success";
        header('Location: borrowed_books.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollBack();
        $_SESSION['message'] = "Failed to return book. Please try again.";
        $_SESSION['message_class'] = "error";
        header('Location: borrowed_books.php');
        exit();
    }
} else {
    $_SESSION['message'] = "Invalid request!";
    $_SESSION['message_class'] = "error";
    $error_message = "Invalid request!";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Return Book</title>
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
        }

        .message.error {
            background: #ffebee;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .return-details {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
            text-align: left;
        }

        .return-details p {
            margin: 10px 0;
            font-size: 1.1rem;
            color: var(--dark);
        }

        .fine-amount {
            color: var(--danger);
            font-weight: 600;
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

    <?php if (isset($success_message)): ?>
    <div class="message success">
        <h2>Book returned successfully!</h2>
        <div class="return-details">
            <p>Return Date: <?php echo date('d M Y H:i', strtotime($return_date)); ?></p>
            <?php if ($fine_amount > 0): ?>
            <p>Fine Amount: <span class="fine-amount">₹<?php echo $fine_amount; ?></span></p>
            <?php endif; ?>
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
</body>
</html>