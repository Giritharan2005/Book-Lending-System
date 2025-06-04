<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Fetch all borrowed books with user and book details
$stmt = $conn->prepare("
    SELECT b.id AS borrow_id, u.id AS user_id, u.username, u.email, books.id AS book_id, books.title, books.author, 
           b.borrow_date, b.due_date, b.return_date, b.fine_amount 
    FROM borrowed_books b
    JOIN users u ON b.user_id = u.id
    JOIN books ON b.book_id = books.id
    ORDER BY b.borrow_date DESC
");
$stmt->execute();
$borrowed_books = $stmt->fetchAll();

// Generate a unique Report ID
$report_id = "REPORT-" . date('Ymd') . "-" . uniqid();

// Check if Excel download was requested
if (isset($_GET['download']) && $_GET['download'] == 'excel') {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="library_report_'.$report_id.'.xls"');
    
    // Start Excel content
    echo "<table border='1'>";
    echo "<tr><th colspan='11' style='background:#4CAF50;color:white;font-size:18px;'>Library Borrowed Books Report - $report_id</th></tr>";
    echo "<tr style='background:#f2f2f2;'>
            <th>Borrow ID</th>
            <th>User ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Book ID</th>
            <th>Book Name</th>
            <th>Author</th>
            <th>Borrowed Date</th>
            <th>Due Date</th>
            <th>Return Date</th>
            <th>Fine Amount</th>
          </tr>";
    
    foreach ($borrowed_books as $book) {
        $status_color = $book['return_date'] ? 'background:#e8f5e9;' : (strtotime($book['due_date']) < time() ? 'background:#ffebee;' : 'background:#e3f2fd;');
        echo "<tr style='$status_color'>
                <td>".htmlspecialchars($book['borrow_id'])."</td>
                <td>".htmlspecialchars($book['user_id'])."</td>
                <td>".htmlspecialchars($book['username'])."</td>
                <td>".htmlspecialchars($book['email'])."</td>
                <td>".htmlspecialchars($book['book_id'])."</td>
                <td>".htmlspecialchars($book['title'])."</td>
                <td>".htmlspecialchars($book['author'])."</td>
                <td>".htmlspecialchars($book['borrow_date'])."</td>
                <td>".htmlspecialchars($book['due_date'])."</td>
                <td>".($book['return_date'] ? htmlspecialchars($book['return_date']) : 'Not returned')."</td>
                <td>".htmlspecialchars($book['fine_amount'])."</td>
              </tr>";
    }
    
    echo "</table>";
    exit(); // Stop further script execution after sending Excel file
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report | Admin Panel</title>
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

        .logout-btn {
            background-color: var(--danger);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }

        .logout-btn:hover {
            background-color: #e5177a;
            transform: translateY(-2px);
        }

        .logout-btn i {
            margin-right: 8px;
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

        .report-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .section-title {
            font-size: 1.5rem;
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

        .report-id {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: bold;
            color: var(--dark);
        }

        .download-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--success), #3ab0d3);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .download-btn:hover {
            background: linear-gradient(135deg, #3ab0d3, var(--success));
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .download-btn i {
            margin-right: 8px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th {
            background-color: var(--primary);
            color: white;
            padding: 15px;
            text-align: left;
        }

        .report-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .report-table tr:last-child td {
            border-bottom: none;
        }

        .report-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-returned {
            background-color: #e8f5e9;
        }

        .status-overdue {
            background-color: #ffebee;
        }

        .status-active {
            background-color: #e3f2fd;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
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

            .report-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .report-table {
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
                <i class="fas fa-file-alt"></i>
                <span>Library Reports</span>
            </div>
            <div class="user-info">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="nav-container">
            <nav>
                <a href="admin_dashboard.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <a href="admin_manage_users.php">
                    <i class="fas fa-users-cog"></i>
                    Manage Users
                </a>
                <a href="view_borrowed_books.php">
                    <i class="fas fa-book-reader"></i>
                    Borrowed Books
                </a>
            </nav>
        </div>

        <div class="report-container">
            <div class="report-header">
                <h2 class="section-title">Borrowed Books Report</h2>
                <div class="report-id">
                    <i class="fas fa-hashtag"></i>
                    Report ID: <?php echo htmlspecialchars($report_id); ?>
                </div>
            </div>

            <a href="generate_report.php?download=excel" class="download-btn">
                <i class="fas fa-file-excel"></i>
                Download as Excel
            </a>

            <?php if (count($borrowed_books) > 0): ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Borrow ID</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Book ID</th>
                            <th>Book Name</th>
                            <th>Author</th>
                            <th>Borrowed Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Fine Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowed_books as $book): 
                            $status_class = '';
                            $status_text = '';
                            
                            if ($book['return_date']) {
                                $status_class = 'status-returned';
                                $status_text = 'Returned';
                            } elseif (strtotime($book['due_date']) < time()) {
                                $status_class = 'status-overdue';
                                $status_text = 'Overdue';
                            } else {
                                $status_class = 'status-active';
                                $status_text = 'Active';
                            }
                        ?>
                        <tr class="<?php echo $status_class; ?>">
                            <td><?php echo htmlspecialchars($book['borrow_id']); ?></td>
                            <td><?php echo htmlspecialchars($book['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($book['username']); ?></td>
                            <td><?php echo htmlspecialchars($book['email']); ?></td>
                            <td><?php echo htmlspecialchars($book['book_id']); ?></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['borrow_date']); ?></td>
                            <td><?php echo htmlspecialchars($book['due_date']); ?></td>
                            <td><?php echo $status_text; ?></td>
                            <td>$<?php echo number_format($book['fine_amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-book-open"></i>
                    <p>No borrowed books found in the system</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>