<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

$message = '';
$message_class = '';
$show_form = true;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $username = $_POST['username'] ?? '';
    
    try {
        // First check what columns exist in the users table
        $columns = $conn->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        
        // Check if email already exists
        $check_email_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email_stmt->execute([$email]);
        
        if ($check_email_stmt->rowCount() > 0) {
            $message = "This email is already registered. Please use a different email.";
            $message_class = "error";
            $show_form = true;
        } else {
            // Only check username uniqueness if the column exists
            $username_exists = false;
            if (in_array('username', $columns)) {
                $check_username_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $check_username_stmt->execute([$username]);
                $username_exists = $check_username_stmt->rowCount() > 0;
            }
            
            if ($username_exists) {
                $message = "This username is already taken. Please choose a different one.";
                $message_class = "error";
                $show_form = true;
            } else {
                // Prepare the appropriate INSERT statement
                if (in_array('username', $columns)) {
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    $result = $stmt->execute([$username, $email, $password]);
                } elseif (in_array('name', $columns)) {
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                    $result = $stmt->execute([$username, $email, $password]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
                    $result = $stmt->execute([$email, $password]);
                }
                
                if ($result) {
                    $message = "User added successfully!";
                    $message_class = "success";
                    $show_form = false;
                } else {
                    $message = "Error adding user. Please try again.";
                    $message_class = "error";
                    $show_form = true;
                }
            }
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $message_class = "error";
        $show_form = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User | Admin Panel</title>
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

        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            max-width: 600px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            position: relative;
            padding-left: 15px;
            text-align: center;
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: -8px;
            height: 4px;
            width: 80px;
            background: var(--primary);
            border-radius: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .submit-btn i {
            margin-right: 8px;
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

        .add-another-btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--success), #3ab0d3);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 20px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .add-another-btn:hover {
            background: linear-gradient(135deg, #3ab0d3, var(--success));
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .add-another-btn i {
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

            .logout-btn {
                margin-top: 15px;
            }

            nav {
                flex-direction: column;
            }

            nav a {
                width: 100%;
                justify-content: center;
            }

            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-user-plus"></i>
                <span>Add New User</span>
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
                <a href="admin_manage_users.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Users
                </a>
                <a href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </nav>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_class; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <?php if ($show_form): ?>
                <h2 class="section-title">Create New User Account</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-user-plus"></i>
                        Create User
                    </button>
                </form>
            <?php else: ?>
                <div style="text-align: center;">
                    <h2 class="section-title">User Added Successfully</h2>
                    <p>You can now add another user or return to the user management page.</p>
                    <a href="add_user.php" class="add-another-btn">
                        <i class="fas fa-plus"></i>
                        Add Another User
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>