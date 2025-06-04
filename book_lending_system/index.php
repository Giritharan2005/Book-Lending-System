<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $login_type = $_POST['login_type'];

    if ($login_type === 'admin') {
        if ($email === 'admin@gmail.com' && $password === 'admin123') {
            $_SESSION['admin'] = true;
            $_SESSION['user_id'] = null;
            header('Location: admin_dashboard.php');
            exit();
        } else {
            $error = "Invalid admin credentials!";
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['admin'] = false;
            header('Location: user_dashboard.php');
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Lending System - Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 400px;
            padding: 30px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .login-type {
            display: flex;
            margin-bottom: 20px;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #ddd;
        }
        .login-type input[type="radio"] {
            display: none;
        }
        .login-type label {
            flex: 1;
            text-align: center;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .login-type label.user {
            background-color: #e9f7fe;
            color: #0d6efd;
        }
        .login-type label.admin {
            background-color: #f8e8e8;
            color: #dc3545;
        }
        .login-type input[type="radio"]:checked + label.user {
            background-color: #0d6efd;
            color: white;
        }
        .login-type input[type="radio"]:checked + label.admin {
            background-color: #dc3545;
            color: white;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #218838;
        }
        .reset-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .reset-link a {
            color: #0d6efd;
            text-decoration: none;
        }
        .reset-link a:hover {
            text-decoration: underline;
        }
        .error {
            color: #dc3545;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Book Lending System</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="login-type">
                <input type="radio" id="user" name="login_type" value="user" checked>
                <label for="user" class="user">User Login</label>
                
                <input type="radio" id="admin" name="login_type" value="admin">
                <label for="admin" class="admin">Admin Login</label>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="reset-link">
            <a href="reset_password.php">Reset Password</a>
        </div>
    </div>
</body>
</html>