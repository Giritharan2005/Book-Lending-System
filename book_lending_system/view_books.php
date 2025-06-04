<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Initialize search and filter variables
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Build the SQL query based on search and filter
$sql = "SELECT * FROM books WHERE 1=1";
if (!empty($search)) {
    $sql .= " AND (title LIKE :search OR author LIKE :search)";
}
if ($filter === 'available') {
    $sql .= " AND available_quantity > 0";
}

$stmt = $conn->prepare($sql);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->execute();
$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Books | Book Lending System</title>
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
            gap: 15px;
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
            font-size: 14px;
        }

        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .back-btn i {
            margin-right: 5px;
        }

        .search-filter-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .search-filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-filter input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        .search-filter select {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        .search-filter button {
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-filter button:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            transform: translateY(-2px);
        }

        .books-container {
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

        .books-table {
            width: 100%;
            border-collapse: collapse;
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
            vertical-align: middle;
        }

        .books-table tr:last-child td {
            border-bottom: none;
        }

        .books-table tr:hover {
            background-color: #f8f9fa;
        }

        .book-image {
            width: 80px;
            height: 110px;
            object-fit: cover;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .no-image {
            width: 80px;
            height: 110px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            color: var(--gray);
        }

        .no-image i {
            font-size: 1.5rem;
        }

        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .borrow-btn {
            background-color: var(--success);
            color: white;
        }

        .borrow-btn:hover {
            background-color: #3ab0d3;
        }

        .unavailable-btn {
            background-color: var(--gray);
            color: white;
            cursor: not-allowed;
        }

        .action-btn i {
            margin-right: 5px;
        }

        .message {
            text-align: center;
            padding: 30px;
            color: var(--gray);
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .logo {
                margin-bottom: 15px;
            }

            .user-info {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }

            .back-btn {
                width: 100%;
                justify-content: center;
            }

            .search-filter {
                flex-direction: column;
            }

            .books-table {
                display: block;
                overflow-x: auto;
            }

            .book-image, .no-image {
                width: 60px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-book-open"></i>
                <span>Book Collection</span>
            </div>
            <div class="user-info">
                <a href="javascript:history.back()" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="search-filter-container">
            <form method="GET" action="view_books.php">
                <div class="search-filter">
                    <input type="text" name="search" placeholder="Search by book name or author" value="<?php echo htmlspecialchars($search); ?>">
                    <select name="filter">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Books</option>
                        <option value="available" <?php echo $filter === 'available' ? 'selected' : ''; ?>>Available Only</option>
                    </select>
                    <button type="submit">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <div class="books-container">
            <h2 class="section-title">Available Books</h2>
            
            <?php if (count($books) > 0): ?>
                <table class="books-table">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Book Name</th>
                            <th>Author</th>
                            <th>Available</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                        <tr>
                            <td>
                                <?php if (!empty($book['cover_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-image">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-book"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['available_quantity']); ?></td>
                            <td>
                                <?php if ($book['available_quantity'] > 0): ?>
                                    <a href="borrow_book.php?book_id=<?php echo $book['id']; ?>" class="action-btn borrow-btn">
                                        <i class="fas fa-hand-holding"></i> Borrow
                                    </a>
                                <?php else: ?>
                                    <span class="action-btn unavailable-btn">
                                        <i class="fas fa-times-circle"></i> Unavailable
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="message">
                    <i class="fas fa-book-open"></i>
                    <p>No books found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>