<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Fetch all books from the database
$stmt = $conn->query("SELECT * FROM books");
$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Book Lending System</title>
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

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        .logout-btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .logout-btn i {
            margin-right: 5px;
        }

        .dashboard-nav {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-card {
            background: white;
            border-radius: 10px;
            padding: 15px 25px;
            text-align: center;
            min-width: 180px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            cursor: pointer;
            color: var(--dark);
            text-decoration: none;
        }

        .nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-card i {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .nav-card h3 {
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .nav-card p {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .section-title {
            font-size: 1.5rem;
            margin: 25px 0 15px;
            color: var(--dark);
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
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
        }

        .books-table tr:last-child td {
            border-bottom: none;
        }

        .books-table tr:hover {
            background-color: #f8f9fa;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .delete-btn {
            background-color: var(--danger);
            color: white;
        }

        .delete-btn:hover {
            background-color: #d1144a;
        }

        .action-btn i {
            margin-right: 5px;
            font-size: 0.8rem;
        }

        .no-books {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .no-books i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 15px;
        }

        .no-books p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .add-book-btn {
            display: inline-block;
            background-color: var(--success);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .add-book-btn:hover {
            background-color: #3ab0d3;
        }

        .add-book-btn i {
            margin-right: 8px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .logo {
                margin-bottom: 15px;
            }

            .dashboard-nav {
                flex-direction: column;
                align-items: center;
            }

            .nav-card {
                width: 100%;
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
                <span>Admin DashBoard</span>
            </div>
            <div class="user-info">
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-nav">
            <a href="add_book.php" class="nav-card">
                <i class="fas fa-plus-circle"></i>
                <h3>Add Book</h3>
                <p>Add new books to the library</p>
            </a>
            <a href="admin_manage_users.php" class="nav-card">
                <i class="fas fa-users-cog"></i>
                <h3>Manage Users</h3>
                <p>View and manage system users</p>
            </a>
            <a href="admin_borrowed_books.php" class="nav-card">
                <i class="fas fa-book-reader"></i>
                <h3>Borrowed Books</h3>
                <p>Track all borrowed books</p>
            </a>
            <a href="generate_report.php" class="nav-card">
                <i class="fas fa-chart-pie"></i>
                <h3>Generate Report</h3>
                <p>Create system reports</p>
            </a>
        </div>

        <h2 class="section-title">Available Books</h2>

        <?php if (count($books) > 0): ?>
            <table class="books-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>Available Copies</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['id']); ?></td>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['available_quantity']); ?></td>
                        <td>
                            <a href="delete_book.php?id=<?php echo $book['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this book?');">
                                <i class="fas fa-trash-alt"></i>
                                Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-books">
                <i class="fas fa-book"></i>
                <p>No books available in the library</p>
                <a href="add_book.php" class="add-book-btn">
                    <i class="fas fa-plus"></i>
                    Add New Book
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Simple confirmation for delete actions
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!confirm('Are you sure you want to delete this book?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>