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
    SELECT 
        b.*,
        books.title,
        books.author,
        books.available_quantity,
        u.username,
        u.email
    FROM borrowed_books b
    JOIN books ON b.book_id = books.id
    JOIN users u ON b.user_id = u.id
    WHERE b.return_date IS NULL
    ORDER BY b.borrow_date DESC
");
$stmt->execute();
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
            'fine' => $fine,
            'days_overdue' => $interval->days
        ];
    } else {
        return [
            'status' => 'active',
            'message' => 'Time remaining: ' . $interval->format('%a days, %h hours, %i minutes'),
            'fine' => 0,
            'days_overdue' => 0
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Borrowed Books | Admin Dashboard</title>
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
            max-width: 1400px;
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

        .books-table {
            width: 100%;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .books-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .books-table th,
        .books-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .books-table th {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }

        .books-table tr:hover {
            background: var(--primary-light);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-badge.active {
            background: var(--success-bg);
            color: var(--success);
        }

        .status-badge.overdue {
            background: #ffebee;
            color: var(--danger);
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn.return {
            background: var(--success);
            color: white;
        }

        .action-btn.return:hover {
            background: #3db8d8;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--primary-light);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .filter-select {
            padding: 12px;
            border: 2px solid var(--primary-light);
            border-radius: 8px;
            font-size: 16px;
            background: white;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .fine-amount {
            font-weight: 600;
            color: var(--danger);
        }

        .days-overdue {
            font-size: 0.9rem;
            color: var(--danger);
            margin-top: 5px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .user-info span {
            display: block;
        }

        .user-info .username {
            font-weight: 600;
            color: var(--primary);
        }

        .user-info .email {
            color: var(--gray);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-book-reader"></i>
                Book Lending System
            </div>
        </div>
    </header>

    <div class="container">
        <div class="nav-container">
            <nav>
                <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="add_book.php"><i class="fas fa-plus"></i> Add Book</a>
                <a href="admin_manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="admin_borrowed_books.php" class="active"><i class="fas fa-book"></i> Borrowed Books</a>
                <a href="generate_report.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>

        <div class="search-container">
            <input type="text" class="search-input" id="searchInput" placeholder="Search by book name, author, or username...">
            <select class="filter-select" id="statusFilter">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="overdue">Overdue</option>
            </select>
        </div>

        <div class="books-table">
            <table>
                <thead>
                    <tr>
                        <th>Book Details</th>
                        <th>Borrower</th>
                        <th>Borrowed Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Fine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrowed_books as $book): 
                        $timeStatus = calculateTimeStatus($book['due_date']);
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                            <span style="color: var(--gray);">By <?php echo htmlspecialchars($book['author']); ?></span><br>
                            <span style="font-size: 0.9rem;">Available Copies: <?php echo htmlspecialchars($book['available_quantity']); ?></span>
                        </td>
                        <td>
                            <div class="user-info">
                                <span class="username"><?php echo htmlspecialchars($book['username']); ?></span>
                                <span class="email"><?php echo htmlspecialchars($book['email']); ?></span>
                            </div>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($book['borrow_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $timeStatus['status']; ?>">
                                <?php echo ucfirst($timeStatus['status']); ?>
                            </span>
                            <?php if ($timeStatus['status'] === 'overdue'): ?>
                                <div class="days-overdue">
                                    <?php echo $timeStatus['message']; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($timeStatus['fine'] > 0): ?>
                                <span class="fine-amount">₹<?php echo number_format($timeStatus['fine'], 2); ?></span>
                                <div class="days-overdue">
                                    (₹10 per minute, max ₹10)
                                </div>
                            <?php else: ?>
                                <span style="color: var(--success);">No Fine</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        // Status filter functionality
        document.getElementById('statusFilter').addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    const statusCell = row.querySelector('.status-badge');
                    row.style.display = statusCell.classList.contains(status) ? '' : 'none';
                }
            });
        });
    </script>
</body>
</html> 