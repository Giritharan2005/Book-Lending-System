<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}
include 'db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_name = $_POST['book_name'];
    $author = $_POST['author'];
    $copies_available = $_POST['copies_available'];
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/books/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid() . '_' . basename($_FILES['book_image']['name']);
        $target_file = $upload_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is actual image
        $check = getimagesize($_FILES['book_image']['tmp_name']);
        if ($check === false) {
            $message = '<div class="error-message">File is not an image.</div>';
        }
        // Check file size (max 2MB)
        elseif ($_FILES['book_image']['size'] > 2000000) {
            $message = '<div class="error-message">Image is too large (max 2MB).</div>';
        }
        // Allow certain file formats
        elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $message = '<div class="error-message">Only JPG, JPEG, PNG & GIF files are allowed.</div>';
        }
        // Upload file
        elseif (move_uploaded_file($_FILES['book_image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        } else {
            $message = '<div class="error-message">Error uploading image.</div>';
        }
    }
    
    if (empty($message)) {
        $stmt = $conn->prepare("INSERT INTO books (title, author, available_quantity, cover_image) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$book_name, $author, $copies_available, $image_path])) {
            $message = '<div class="success-message">Book added successfully!</div>';
        } else {
            $message = '<div class="error-message">Failed to add book!</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book | Admin Dashboard</title>
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
            max-width: 800px;
            margin: 30px auto;
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

        .back-btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .back-btn i {
            margin-right: 5px;
        }

        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .form-title {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: var(--primary);
            text-align: center;
            position: relative;
            padding-bottom: 10px;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--accent);
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
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(72, 149, 239, 0.2);
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            transform: translateY(-2px);
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .image-preview {
            width: 150px;
            height: 200px;
            border: 2px dashed #ddd;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f9f9f9;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .image-preview span {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .file-input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: block;
            padding: 10px 15px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--dark);
        }

        .file-input-label:hover {
            background-color: #e0e0e0;
        }

        .file-input-label i {
            margin-right: 8px;
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .logo {
                margin-bottom: 15px;
            }

            .container {
                padding: 15px;
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
                <i class="fas fa-book-open"></i>
                <span>Add Books</span>
            </div>
            <div class="user-info">
                <a href="admin_dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="form-container">
            <h1 class="form-title">Add New Book</h1>
            
            <?php echo $message; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="book_name">Book Name</label>
                    <input type="text" id="book_name" name="book_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="author">Author</label>
                    <input type="text" id="author" name="author" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="copies_available">Copies Available</label>
                    <input type="number" id="copies_available" name="copies_available" class="form-control" required min="1">
                </div>
                
                <div class="form-group">
                    <label>Book Cover Image</label>
                    <div class="image-preview" id="imagePreview">
                        <span>No image selected</span>
                    </div>
                    <div class="file-input-wrapper">
                        <label for="book_image" class="file-input-label">
                            <i class="fas fa-upload"></i> Choose Image
                        </label>
                        <input type="file" id="book_image" name="book_image" accept="image/*">
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-plus-circle"></i> Add Book
                </button>
            </form>
        </div>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('book_image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            const reader = new FileReader();
            
            preview.innerHTML = '';
            
            if (file) {
                if (file.type.match('image.*')) {
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        preview.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = '<span>File is not an image</span>';
                }
            } else {
                preview.innerHTML = '<span>No image selected</span>';
            }
        });
    </script>
</body>
</html>