<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

// Handle search and category filter
$search = '';
$selectedCategory = '';
$queryParams = [];
$sql = "SELECT * FROM books WHERE 1=1";

// Search filter
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $sql .= " AND (id LIKE ? OR title LIKE ? OR year LIKE ? OR category LIKE ?)";
    $like = "%$search%";
    $queryParams[] = $like;
    $queryParams[] = $like;
    $queryParams[] = $like;
    $queryParams[] = $like;
}

// Category filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $selectedCategory = $_GET['category'];
    $queryParams[] = $selectedCategory;
    $sql .= " AND category = ?";
}

$sql .= " ORDER BY id ASC";

$stmt = $conn->prepare($sql);

if (!empty($queryParams)) {
    $types = str_repeat("s", count($queryParams));
    $stmt->bind_param($types, ...$queryParams);
}

$stmt->execute();
$books = $stmt->get_result();

// Borrow book handling
if (isset($_GET['borrow']) && !empty($_GET['borrow'])) {
    $book_id = intval($_GET['borrow']);
    $username = $_SESSION['username'];

    // Get copies
    $stmtCheck = $conn->prepare("SELECT copies FROM books WHERE id = ?");
    $stmtCheck->bind_param("i", $book_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $bookCheck = $resultCheck->fetch_assoc();
    $stmtCheck->close();

    if ($bookCheck && $bookCheck['copies'] > 0) {
        // Update book: decrease copies, update status, set borrowed_by, and increment borrow_count
        $stmtBorrow = $conn->prepare("UPDATE books SET copies = copies - 1, status = CASE WHEN copies - 1 = 0 THEN 'Borrowed' ELSE 'Available' END, borrowed_by = ?, borrow_count = borrow_count + 1 WHERE id = ?");
        $stmtBorrow->bind_param("si", $username, $book_id);
        $stmtBorrow->execute();
        $stmtBorrow->close();

        echo "<script>alert('Book borrowed successfully!'); window.location='booklist.php';</script>";
    } else {
        echo "<script>alert('No available copies left.'); window.location='booklist.php';</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>BookList</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #002147;
    --primary-light: #01336b;
    --secondary: #6c63ff;
    --accent: #ff6b6b;
    --success: #28a745;
    --light-bg: #f8faff;
    --card-bg: #ffffff;
    --text-dark: #2d3748;
    --text-light: #718096;
    --border-radius: 16px;
    --shadow: 0 10px 30px rgba(0, 33, 71, 0.1);
    --transition: all 0.3s ease;
}

* { 
    box-sizing: border-box; 
    margin: 0; 
    padding: 0; 
}

body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    display: flex;
    min-height: 100vh;
    color: var(--text-dark);
    background: linear-gradient(135deg, #f5f7ff 0%, #e6eeff 100%);
    line-height: 1.6;
}

/* Sidebar */
.sidebar {
    width: 260px;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
    padding: 24px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    overflow-y: auto;
    box-shadow: var(--shadow);
    z-index: 100;
}

.sidebar-header {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 30px;
}

.sidebar img.top-image { 
    width: 120px; 
    height: auto; 
    border-radius: 12px; 
    margin-bottom: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.sidebar-title {
    color: white;
    font-size: 1.2rem;
    font-weight: 600;
    text-align: center;
}

.sidebar-nav {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.sidebar a {
    display: flex;
    align-items: center;
    color: rgba(255, 255, 255, 0.85);
    text-decoration: none;
    padding: 14px 16px;
    width: 100%;
    border-radius: 12px;
    transition: var(--transition);
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.sidebar a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.5s;
}

.sidebar a:hover::before {
    left: 100%;
}

.sidebar a:hover { 
    background-color: rgba(255, 255, 255, 0.1); 
    color: white;
    transform: translateX(5px);
}

.sidebar a.active {
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.sidebar a img { 
    width: 20px; 
    height: 20px; 
    margin-right: 12px;
    filter: brightness(0) invert(1);
}

.sidebar a i {
    margin-right: 12px;
    width: 20px;
    text-align: center;
    font-size: 1.1rem;
}

/* Main Content */
.main {
    margin-left: 260px;
    padding: 30px;
    flex: 1;
    background-color: transparent;
}

/* Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.dashboard-header h1 {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--primary);
    position: relative;
    display: inline-block;
}

.dashboard-header h1::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    width: 60px;
    height: 4px;
    background: var(--secondary);
    border-radius: 2px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--card-bg);
    padding: 12px 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--secondary), var(--accent));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

/* Search & Filter Section */
.search-section {
    background: var(--card-bg);
    padding: 24px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-bottom: 30px;
}

.search-container {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 300px;
    position: relative;
}

.search-box i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
    z-index: 2;
}

.search-box input {
    width: 100%;
    padding: 14px 16px 14px 46px;
    border: 1px solid #e2e8f0;
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: var(--transition);
    background: var(--light-bg);
}

.search-box input:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.1);
}

.filter-select {
    padding: 14px 16px;
    border: 1px solid #e2e8f0;
    border-radius: var(--border-radius);
    font-size: 1rem;
    background: var(--light-bg);
    color: var(--text-dark);
    min-width: 200px;
    cursor: pointer;
    transition: var(--transition);
}

.filter-select:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.1);
}

.search-btn {
    padding: 14px 24px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0, 33, 71, 0.2);
}

/* Books Grid */
.books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.book-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: var(--transition);
    position: relative;
}

.book-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.book-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, var(--secondary), var(--accent));
}

.book-content {
    display: flex;
    gap: 20px;
    padding: 20px;
}

.book-cover-container {
    flex: 0 0 120px;
    height: 160px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 8px;
}

.book-cover {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.book-card:hover .book-cover {
    transform: scale(1.05);
}

.book-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.book-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 12px;
    line-height: 1.3;
}

.book-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.book-meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-light);
    font-size: 0.9rem;
}

.book-meta-item i {
    width: 16px;
    color: var(--secondary);
}

.book-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding: 10px 0;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-available {
    background: rgba(40, 167, 69, 0.1);
    color: var(--success);
}

.status-borrowed {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.copies-count {
    font-size: 0.9rem;
    color: var(--text-light);
    font-weight: 600;
}

/* QR Code Section */
.qr-section {
    flex: 0 0 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.qr-title {
    font-size: 0.8rem;
    color: var(--text-light);
    margin-bottom: 8px;
    font-weight: 600;
}

.qr-code {
    width: 80px;
    height: 80px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 6px;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.qr-code img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.book-actions {
    position: relative;
    left: -110px;
    display: flex;
    justify-content: left;
    gap: 10px;
    margin-top: auto;
}

.btn {
    padding: 10px 16px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: var(--transition);
    border: none;
    cursor: pointer;
    flex: 1;
}

.btn-borrow { 
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
}

.btn-borrow:hover { 
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 33, 71, 0.2);
}

.btn-edit { 
    background: linear-gradient(135deg, var(--secondary), #8a84ff);
}

.btn-edit:hover { 
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 99, 255, 0.2);
}

.btn-delete { 
    background: linear-gradient(135deg, #dc3545, #e35d6a);
}

.btn-delete:hover { 
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
}

.btn-disabled { 
    background: #6c757d; 
    cursor: not-allowed;
    opacity: 0.7;
}

.btn-return { 
    background: var(--success); 
    display: block; 
    margin-top: 18px; 
    width: fit-content; 
    padding: 12px 20px; 
    margin-left: auto;
}

.btn-return:hover { 
    background: #218838; 
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    overflow: auto;
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: var(--card-bg);
    margin: 5% auto;
    padding: 30px;
    border-radius: var(--border-radius);
    width: 90%;
    max-width: 700px;
    position: relative;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 1.5em;
    font-weight: bold;
    color: var(--text-light);
    cursor: pointer;
    transition: var(--transition);
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.modal-close:hover {
    background: #f1f5f9;
    color: var(--text-dark);
}

.modal-header {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.modal-cover {
    width: 150px;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 8px;
}

.modal-author {
    font-size: 1.1rem;
    color: var(--text-light);
    margin-bottom: 16px;
}

.modal-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
}

.modal-detail {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-size: 0.85rem;
    color: var(--text-light);
    margin-bottom: 4px;
}

.detail-value {
    font-weight: 600;
    color: var(--text-dark);
}

.modal-description {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.modal-description .detail-label {
    margin-bottom: 8px;
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary);
}

/* QR Code Section in Modal */
.modal-qr-section {
    margin-top: 25px;
    padding-top: 25px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
}

.modal-qr-title {
    font-size: 1.1rem;
    color: var(--primary);
    margin-bottom: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.modal-qr-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.modal-qr-code {
    width: 180px;
    height: 180px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 15px;
    background: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.modal-qr-code img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.qr-instruction {
    font-size: 0.9rem;
    color: var(--text-light);
    text-align: center;
    max-width: 300px;
    line-height: 1.4;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-light);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #cbd5e0;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 12px;
    color: var(--text-dark);
}

/* Responsive */
@media (max-width: 1100px) {
    .books-grid {
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 70px;
        padding: 20px 10px;
    }
    .sidebar-header, .sidebar a span {
        display: none;
    }
    .sidebar a {
        justify-content: center;
        padding: 16px 8px;
    }
    .sidebar a i, .sidebar a img {
        margin-right: 0;
    }
    .main {
        margin-left: 70px;
        padding: 20px;
    }
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    .search-container {
        flex-direction: column;
        align-items: stretch;
    }
    .search-box {
        min-width: 100%;
    }
    .book-content {
        flex-direction: column;
        gap: 15px;
    }
    .qr-section {
        flex: none;
        margin-top: 15px;
    }
    .modal-header {
        flex-direction: column;
        text-align: center;
    }
    .modal-cover {
        align-self: center;
    }
    .modal-details {
        grid-template-columns: 1fr;
    }
    .modal-content {
        margin: 10% auto;
        padding: 20px;
    }
    .modal-qr-code {
        width: 150px;
        height: 150px;
    }
}
.loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f7f9fb;
    transition: opacity 0.75s, visibility 0.75s;
}
.loader-hidden {
    opacity: 0;
    visibility: hidden;
}
.loader::after{
    content: "";
    width: 75px;
    height: 75px;
    border: 15px solid #dddddd;
    border-top-color: #7449f5;
    border-radius: 50%;
    animation: loading 0.75s ease infinite;
}
@keyframes loading{
    from{
        transform: rotate(0turn);
    }
    to{
        transform: rotate(1turn);
    }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="nemsu-logo.png" alt="NEMSU Logo" class="top-image">
        <div class="sidebar-title">Library System</div>
    </div>
    <div class="sidebar-nav">
        <a href="landpage.php"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="booklist.php" class="active"><i class="fas fa-book"></i><span>Booklist</span></a>
        <a href="dashboard.php"><i class="fas fa-plus-circle"></i><span>Add Book</span></a>
        <a href="return.php"><i class="fas fa-exchange-alt"></i><span>Return Book</span></a>
        <a href="about.php"><i class="fas fa-info-circle"></i><span>About</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="dashboard-header">
        <h1>Book Collection</h1>
        <div class="user-info">
            <div class="user-avatar">
                <?php 
                    $initial = strtoupper(substr($_SESSION['username'], 0, 1));
                    echo $initial;
                ?>
            </div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <div class="user-role">Librarian</div>
            </div>
        </div>
    </div>

    <div class="search-section">
        <form method="GET" action="booklist.php" class="search-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by title, author, year, or category" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <select name="category" class="filter-select">
                <option value="">All Categories</option>
                <?php
                $categories = ['Horror', 'Romance', 'Mystery/Thriller', 'Science Fiction', 'Historical Fiction', 'Literary Fiction'];
                foreach ($categories as $cat) {
                    $selected = ($selectedCategory == $cat) ? 'selected' : '';
                    echo "<option value=\"$cat\" $selected>$cat</option>";
                }
                ?>
            </select>
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>

    <?php if ($books->num_rows > 0): ?>
        <div class="books-grid">
            <?php while ($book = $books->fetch_assoc()): ?>
                <?php
                // Generate unique QR code data for each book
                $qrData = "Book ID: " . $book['id'];
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qrData);
                ?>
                <div class="book-card" 
                    data-title="<?php echo htmlspecialchars($book['title']); ?>"
                    data-author="<?php echo htmlspecialchars($book['author']); ?>"
                    data-year="<?php echo htmlspecialchars($book['year']); ?>"
                    data-category="<?php echo htmlspecialchars($book['category']); ?>"
                    data-status="<?php echo htmlspecialchars($book['status']); ?>"
                    data-borrowed="<?php echo htmlspecialchars($book['borrowed_by'] ?? ''); ?>"
                    data-description="<?php echo htmlspecialchars($book['description'] ?? 'No description available.'); ?>"
                    data-image="<?php echo htmlspecialchars($book['image'] ?: 'default-book.png'); ?>"
                    data-copies="<?php echo htmlspecialchars($book['copies'] ?? 0); ?>"
                    data-qr="<?php echo $qrUrl; ?>"
                    data-bookid="<?php echo $book['id']; ?>"
                >
                    <div class="book-content">
                        <div class="book-cover-container">
                            <img src="<?php echo htmlspecialchars($book['image'] ?: 'default-book.png'); ?>" alt="Book Cover" class="book-cover">
                        </div>
                        
                        <div class="book-info">
                            <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <div class="book-meta">
                                <div class="book-meta-item">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($book['author']); ?></span>
                                </div>
                                <div class="book-meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo htmlspecialchars($book['year']); ?></span>
                                </div>
                                <div class="book-meta-item">
                                    <i class="fas fa-tag"></i>
                                    <span><?php echo htmlspecialchars($book['category']); ?></span>
                                </div>
                            </div>
                            <div class="book-status">
                                <span class="status-badge <?php echo ($book['copies'] ?? 0) > 0 ? 'status-available' : 'status-borrowed'; ?>">
                                    <?php echo ($book['copies'] ?? 0) > 0 ? 'Available' : 'Borrowed'; ?>
                                </span>
                                <span class="copies-count"><?php echo htmlspecialchars($book['copies'] ?? 0); ?> copies</span>
                            </div>
                            
                            <div class="book-actions">
                                <?php if (($book['copies'] ?? 0) > 0): ?>
                                    <a href="?borrow=<?php echo $book['id']; ?>" class="btn btn-borrow">
                                        <i class="fas fa-book-open"></i> Borrow
                                    </a>
                                <?php else: ?>
                                    <span class="btn btn-disabled">
                                        <i class="fas fa-times-circle"></i> Borrowed
                                    </span>
                                <?php endif; ?>
                                <a href="edit.php?id=<?php echo $book['id']; ?>" class="btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete.php?id=<?php echo $book['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this book?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                        
                        <!-- QR Code Section on the right -->
                        <div class="qr-section">
                            <div class="qr-title">Book QR</div>
                            <div class="qr-code">
                                <img src="<?php echo $qrUrl; ?>" alt="QR Code for Book ID: <?php echo $book['id']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-book-open"></i>
            <h3>No books found</h3>
            <p>Try adjusting your search or filter to find what you're looking for.</p>
        </div>
    <?php endif; ?>

    <a href="return.php" class="btn btn-return">
        <i class="fas fa-exchange-alt"></i> Go to Return Book
    </a>
</div>

<!-- Modal -->
<div class="modal" id="bookModal">
    <div class="modal-content">
        <span class="modal-close" id="modalClose">&times;</span>
        <div class="modal-header">
            <img id="modalImage" src="" alt="Book Cover" class="modal-cover">
            <div>
                <h2 id="modalTitle" class="modal-title"></h2>
                <p id="modalAuthor" class="modal-author"></p>
                <div class="modal-details">
                    <div class="modal-detail">
                        <span class="detail-label">Year</span>
                        <span id="modalYear" class="detail-value"></span>
                    </div>
                    <div class="modal-detail">
                        <span class="detail-label">Category</span>
                        <span id="modalCategory" class="detail-value"></span>
                    </div>
                    <div class="modal-detail">
                        <span class="detail-label">Status</span>
                        <span id="modalStatus" class="detail-value"></span>
                    </div>
                    <div class="modal-detail">
                        <span class="detail-label">Available Copies</span>
                        <span id="modalCopies" class="detail-value"></span>
                    </div>
                    <div class="modal-detail">
                        <span class="detail-label">Borrowed By</span>
                        <span id="modalBorrowed" class="detail-value"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-description">
            <span class="detail-label">Description</span>
            <p id="modalDescription" class="detail-value"></p>
        </div>
        
        <!-- QR Code Section after description -->
        <div class="modal-qr-section">
            <h3 class="modal-qr-title">
                <i class="fas fa-qrcode"></i> Book QR Code
            </h3>
            <div class="modal-qr-container">
                <div class="modal-qr-code">
                    <img id="modalQr" src="" alt="QR Code">
                </div>
                <p class="qr-instruction" id="qrInstruction">
                    Scan this QR code to borrow or return this book
                </p>
            </div>
        </div>
    </div>
</div>
<div class="loader"></div>
<script>
// Modal handling
const modal = document.getElementById('bookModal');
const closeModal = document.getElementById('modalClose');

document.querySelectorAll('.book-card').forEach(card => {
    card.addEventListener('click', e => {
        if (!e.target.classList.contains('btn') && !e.target.closest('.book-actions') && !e.target.closest('.qr-section')) {
            document.getElementById('modalTitle').textContent = card.dataset.title;
            document.getElementById('modalAuthor').textContent = card.dataset.author;
            document.getElementById('modalYear').textContent = card.dataset.year;
            document.getElementById('modalCategory').textContent = card.dataset.category;
            document.getElementById('modalStatus').textContent = card.dataset.status;
            document.getElementById('modalCopies').textContent = card.dataset.copies;
            document.getElementById('modalBorrowed').textContent = card.dataset.borrowed || 'Not borrowed';
            document.getElementById('modalDescription').textContent = card.dataset.description;
            document.getElementById('modalImage').src = card.dataset.image;
            document.getElementById('modalQr').src = card.dataset.qr;
            
            // Update QR code instruction based on book status
            const qrInstruction = document.getElementById('qrInstruction');
            const isAvailable = card.dataset.copies > 0;
            qrInstruction.textContent = isAvailable 
                ? 'Scan this QR code to borrow this book' 
                : 'Scan this QR code to return this book';
            
            modal.style.display = 'block';
        }
    });
});

closeModal.onclick = () => modal.style.display = 'none';
window.onclick = e => { if (e.target == modal) modal.style.display = 'none'; };
window.addEventListener("load", () => {
    const loader = document.querySelector(".loader");
    loader.classList.add("loader-hidden");

    loader.addEventListener("transitioned", () => {
        document.body.removeChild("loader");
    })
})
</script>

</body>
</html>