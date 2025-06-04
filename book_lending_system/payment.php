<?php
// payment.php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Set fixed fine amount to ₹10
$fine_amount = 10;
$borrowed_id = isset($_SESSION['borrowed_id']) ? $_SESSION['borrowed_id'] : null;
$user_id = $_SESSION['user_id'];

// Function to generate QR code data for UPI
function generateUPIQRData($amount) {
    $upi_id = "gtharan834@okicici";
    $name = "Book Lending System";
    $note = "Fine Payment for Book Return";
    $amount = number_format($amount, 2, '.', '');
    $upi_url = "upi://pay?pa={$upi_id}&pn={$name}&am={$amount}&cu=INR&tn={$note}";
    return $upi_url;
}

$qr_data = generateUPIQRData($fine_amount);

$payment_success = false;
$returned_book = null;
$error_message = '';
$return_date = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $borrowed_id) {
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Get borrowed book details
        $stmt = $conn->prepare("
            SELECT b.*, books.title 
            FROM borrowed_books b
            JOIN books ON b.book_id = books.id
            WHERE b.id = ? AND b.user_id = ?
        ");
        $stmt->execute([$borrowed_id, $user_id]);
        $borrowed_book = $stmt->fetch();

        if (!$borrowed_book) {
            throw new Exception('Borrowed book record not found');
        }

        // Update books table to increase available quantity
        $stmt = $conn->prepare("UPDATE books SET available_quantity = available_quantity + 1 WHERE id = ?");
        $stmt->execute([$borrowed_book['book_id']]);

        // Update borrowed_books table to mark as returned
        $stmt = $conn->prepare("
            UPDATE borrowed_books 
            SET return_date = ?, 
                fine_amount = ? 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$return_date, $fine_amount, $borrowed_id, $user_id]);

        // Insert into returned_books table
        $stmt = $conn->prepare("
            INSERT INTO returned_books (
                borrow_id,
                book_id,
                user_id,
                title,
                borrow_date,
                due_date,
                return_date,
                fine_amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $borrowed_id,
            $borrowed_book['book_id'],
            $user_id,
            $borrowed_book['title'],
            $borrowed_book['borrow_date'],
            $borrowed_book['due_date'],
            $return_date,
            $fine_amount
        ]);

        // Commit transaction
        $conn->commit();

        // Prepare returned book data for display
        $returned_book = [
            'title' => $borrowed_book['title'],
            'borrow_date' => $borrowed_book['borrow_date'],
            'due_date' => $borrowed_book['due_date'],
            'return_date' => $return_date,
            'fine_amount' => $fine_amount
        ];
        $payment_success = true;
        
        // Clear session variables
        unset($_SESSION['fine_amount']);
        unset($_SESSION['borrowed_id']);

        // Set success message for borrowed_books.php
        $_SESSION['message'] = "Book '{$borrowed_book['title']}' has been returned successfully after paying fine amount of ₹{$fine_amount}!";
        $_SESSION['message_class'] = "success";

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Fine | Book Lending System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
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

        .payment-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--box-shadow);
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }

        .payment-title {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .payment-title i {
            margin-right: 10px;
            color: var(--accent);
        }

        .fine-amount {
            font-size: 2.5rem;
            color: var(--danger);
            font-weight: 700;
            margin: 20px 0;
        }

        .qr-container {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            display: inline-block;
            margin: 20px 0;
            box-shadow: var(--box-shadow);
            position: relative;
        }

        #qrcode {
            padding: 10px;
            background: white;
        }

        .payment-instructions {
            margin: 20px 0;
            padding: 20px;
            background: var(--primary-light);
            border-radius: var(--border-radius);
            text-align: left;
        }

        .payment-instructions h3 {
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .payment-instructions h3 i {
            margin-right: 10px;
            color: var(--accent);
        }

        .payment-instructions ol {
            padding-left: 20px;
        }

        .payment-instructions li {
            margin-bottom: 10px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            background: var(--success);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .action-btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .action-btn i {
            margin-right: 8px;
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

            .payment-container {
                padding: 20px;
            }

            .fine-amount {
                font-size: 2rem;
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
        <div class="nav-container">
            <nav>
                <a href="view_borrowed_books.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Borrowed Books
                </a>
            </nav>
        </div>

        <?php if ($payment_success): ?>
            <div class="payment-container">
                <i class="fas fa-check-circle success-icon" style="font-size:4rem;color:#4cc9f0;margin-bottom:20px;"></i>
                <h1 class="payment-title">Payment Successful!</h1>
                <div class="success-message" style="margin: 20px 0; padding: 20px; background: var(--success-bg); border-radius: var(--border-radius);">
                    <p style="font-size: 1.1rem; color: var(--success); font-weight: 500;">Book '<?php echo htmlspecialchars($returned_book['title']); ?>' has been returned successfully after paying fine amount of ₹<?php echo $fine_amount; ?>!</p>
                </div>
                <?php if ($returned_book): ?>
                <div class="return-summary" style="margin:30px 0;text-align:left;">
                    <h3>Returned Book Details:</h3>
                    <ul>
                        <li><strong>Book Title:</strong> <?php echo htmlspecialchars($returned_book['title']); ?></li>
                        <li><strong>Borrowed Date:</strong> <?php echo date('M d, Y', strtotime($returned_book['borrow_date'])); ?></li>
                        <li><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($returned_book['due_date'])); ?></li>
                        <li><strong>Return Date:</strong> <?php echo date('M d, Y', strtotime($returned_book['return_date'])); ?></li>
                        <li><strong>Fine Paid:</strong> ₹<?php echo htmlspecialchars($returned_book['fine_amount']); ?></li>
                    </ul>
                </div>
                <?php endif; ?>
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 20px;">
                    <a href="borrowed_books.php" class="btn" style="display:inline-flex;align-items:center;padding:12px 24px;background:#4361ee;color:white;text-decoration:none;border-radius:8px;font-weight:500;transition:all 0.3s ease;border:none;cursor:pointer;">
                        <i class="fas fa-book"></i>
                        Back to Borrowed Books
                    </a>
                </div>
            </div>
        <?php elseif ($error_message): ?>
            <div class="payment-container">
                <div class="error-message" style="color:#f72585;font-weight:600;margin-bottom:20px;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>
        <?php else: ?>
            <div class="payment-container">
                <h1 class="payment-title">
                    <i class="fas fa-rupee-sign"></i>
                    Pay Fine Amount
                </h1>
                <div class="fine-amount">
                    ₹<?php echo number_format($fine_amount, 2); ?>
                </div>
                <div class="qr-container">
                    <div id="qrcode"></div>
                </div>
                <div class="payment-instructions">
                    <h3><i class="fas fa-info-circle"></i>Payment Instructions</h3>
                    <ol>
                        <li>Open any UPI app on your phone (Google Pay, PhonePe, Paytm, etc.)</li>
                        <li>Scan the QR code above</li>
                        <li>Verify the amount and recipient details</li>
                        <li>Complete the payment</li>
                        <li>Click the "Payment Done" button below after successful payment</li>
                    </ol>
                </div>
                <div class="payment-options">
                    <form method="POST" action="">
                        <input type="hidden" name="borrowed_id" value="<?php echo htmlspecialchars($borrowed_id); ?>">
                        <div class="payment-options">
                            <div class="payment-option">
                                <h4>Manual Payment Confirmation</h4>
                                <button type="submit" name="payment_done" class="action-btn">
                                    <i class="fas fa-check"></i>
                                    Payment Done
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script>
        // Generate QR Code
        <?php if (!$payment_success): ?>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $qr_data; ?>",
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        <?php endif; ?>
    </script>
</body>
</html>