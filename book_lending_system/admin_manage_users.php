<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Display message if set in session
$message = '';
$message_class = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_class = isset($_SESSION['message_class']) ? $_SESSION['message_class'] : 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_class']);
}

// Fetch all users from the database
$stmt = $conn->query("SELECT * FROM users");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Admin Panel</title>
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

        .users-container {
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

        .action-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .add-user-btn {
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

        .add-user-btn:hover {
            background: linear-gradient(135deg, #3ab0d3, var(--success));
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .add-user-btn i {
            margin-right: 8px;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            background-color: var(--primary);
            color: white;
            padding: 15px;
            text-align: left;
        }

        .users-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .users-table tr:hover {
            background-color: #f8f9fa;
        }

        .delete-btn {
            color: var(--danger);
            cursor: pointer;
            transition: color 0.3s;
        }

        .delete-btn:hover {
            color: #ff728d;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message.success {
            background-color: #e8f5e9;
            color: var(--success);
            border-left: 5px solid var(--success);
        }

        .message.error {
            background-color: #ffebee;
            color: var(--danger);
            border-left: 5px solid var(--danger);
        }

        .no-users {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }

        .no-users i {
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

            .users-table {
                display: block;
                overflow-x: auto;
            }

            .action-container {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-users-cog"></i>
                <span>Manage Users</span>
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
                <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="add_book.php"><i class="fas fa-plus"></i> Add Book</a>
                <a href="admin_manage_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a>
                <a href="admin_borrowed_books.php"><i class="fas fa-book"></i> Borrowed Books</a>
                <a href="generate_report.php"><i class="fas fa-chart-bar"></i> Reports</a>
            </nav>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_class; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="users-container">
            <div class="action-container">
                <h2 class="section-title">User Management</h2>
                <a href="add_user.php" class="add-user-btn">
                    <i class="fas fa-user-plus"></i>
                    Add New User
                </a>
            </div>

            <?php if (count($users) > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username'] ?? $user['name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <button class="delete-btn" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-users">
                    <i class="fas fa-user-slash"></i>
                    <p>No users registered in the system</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user and all their records? This action cannot be undone.')) {
                // Create a form to submit the delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_users.php';
                
                // Add user_id as hidden input
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_id';
                input.value = userId;
                form.appendChild(input);
                
                // Submit the form
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>