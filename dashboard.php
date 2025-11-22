<?php
session_start();
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $year = intval($_POST['year']);
    $category = $_POST['category'];
    $description = $_POST['description'];
    $copies = intval($_POST['copies']); // NEW FIELD
    $status = 'Available';
    $borrowed_by = NULL;

    // Handle Image Upload
    $imagePath = NULL;
    if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] == 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = time() . "_" . basename($_FILES["book_image"]["name"]);
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["book_image"]["tmp_name"], $targetFile)) {
                $imagePath = $targetFile;
            }
        }
    }

    // Insert into database (ADDED copies column)
    $stmt = $conn->prepare("INSERT INTO books 
        (title, author, year, category, description, image, status, borrowed_by, copies) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssssi", 
        $title, $author, $year, $category, $description, $imagePath, $status, $borrowed_by, $copies
    );

    if ($stmt->execute()) {
        echo "<script>alert('Book added successfully!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Error adding book.');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>
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
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
}

/* Form Container */
.form-container {
    width: 100%;
    max-width: 500px;
    margin-top: 40px;
}

.form-header {
    text-align: center;
    margin-bottom: 30px;
}

.form-header h1 {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--primary);
    position: relative;
    display: inline-block;
    margin-bottom: 8px;
}

.form-header h1::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 4px;
    background: var(--secondary);
    border-radius: 2px;
}

.form-header p {
    color: var(--text-light);
    font-size: 1.1rem;
}

/* Form Box */
.form-box {
    background: var(--card-bg);
    padding: 40px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    backdrop-filter: blur(10px);
}

.form-group {
    margin-bottom: 20px;
    position: relative;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text-dark);
    font-size: 0.95rem;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e2e8f0;
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: var(--transition);
    background: var(--light-bg);
    font-family: inherit;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.1);
    background: white;
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.file-input-container {
    position: relative;
}

.file-input {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-input-label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    border: 2px dashed #cbd5e0;
    border-radius: var(--border-radius);
    background: var(--light-bg);
    cursor: pointer;
    transition: var(--transition);
    color: var(--text-light);
}

.file-input-label:hover {
    border-color: var(--secondary);
    background: rgba(108, 99, 255, 0.05);
}

.file-input-label i {
    color: var(--secondary);
    font-size: 1.2rem;
}

.file-name {
    margin-top: 8px;
    font-size: 0.85rem;
    color: var(--text-light);
    font-style: italic;
}

/* Submit Button */
.submit-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 33, 71, 0.3);
}

.submit-btn:active {
    transform: translateY(0);
}

/* User Info */
.user-info {
    position: absolute;
    top: 30px;
    right: 30px;
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

.user-details {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 600;
    color: var(--text-dark);
}

.user-role {
    font-size: 0.85rem;
    color: var(--text-light);
}

/* Responsive */
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
    .form-container {
        margin-top: 20px;
    }
    .form-box {
        padding: 30px 20px;
    }
    .user-info {
        position: relative;
        top: 0;
        right: 0;
        margin-bottom: 20px;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .form-box {
        padding: 20px 15px;
    }
    .form-header h1 {
        font-size: 1.8rem;
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
        <a href="booklist.php"><i class="fas fa-book"></i><span>Booklist</span></a>
        <a href="dashboard.php" class="active"><i class="fas fa-plus-circle"></i><span>Add Book</span></a>
        <a href="return.php"><i class="fas fa-exchange-alt"></i><span>Return Book</span></a>
        <a href="about.php"><i class="fas fa-info-circle"></i><span>About</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main">
    <?php if (isset($_SESSION['username'])): ?>
    <div class="user-info">
        <div class="user-avatar">
            <?php 
                $initial = strtoupper(substr($_SESSION['username'], 0, 1));
                echo $initial;
            ?>
        </div>
        <div class="user-details">
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
            <div class="user-role">Librarian</div>
        </div>
    </div>
    <?php endif; ?>

    <div class="form-container">
        <div class="form-header">
            <h1>Add New Book</h1>
            <p>Fill in the details to add a new book to the library</p>
        </div>

        <div class="form-box">
            <form method="POST" enctype="multipart/form-data" id="bookForm">
                <div class="form-group">
                    <label class="form-label">Book Title</label>
                    <input type="text" name="title" class="form-input" placeholder="Enter book title" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Author</label>
                    <input type="text" name="author" class="form-input" placeholder="Enter author name" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Publication Year</label>
                    <input type="number" name="year" class="form-input" placeholder="Enter publication year" min="1000" max="<?php echo date('Y'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <option value="" disabled selected>Select a category</option>
                        <option value="Horror">Horror</option>
                        <option value="Romance">Romance</option>
                        <option value="Mystery/Thriller">Mystery/Thriller</option>
                        <option value="Science Fiction">Science Fiction</option>
                        <option value="Historical Fiction">Historical Fiction</option>
                        <option value="Literary Fiction">Literary Fiction</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" placeholder="Enter book description (optional)"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Number of Copies</label>
                    <input type="number" name="copies" class="form-input" placeholder="Enter number of copies" min="1" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Book Cover Image</label>
                    <div class="file-input-container">
                        <input type="file" name="book_image" class="file-input" id="bookImage" accept="image/*" required>
                        <label for="bookImage" class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Choose book cover image</span>
                        </label>
                    </div>
                    <div class="file-name" id="fileName">No file chosen</div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-plus-circle"></i>
                    Add Book to Library
                </button>
            </form>
        </div>
    </div>
</div>
<div class="loader"></div>
<script>
// File input display
document.getElementById('bookImage').addEventListener('change', function(e) {
    const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
    document.getElementById('fileName').textContent = fileName;
});


document.getElementById('bookForm').addEventListener('submit', function(e) {
    const yearInput = document.querySelector('input[name="year"]');
    const currentYear = new Date().getFullYear();
    
    if (yearInput.value < 1000 || yearInput.value > currentYear) {
        e.preventDefault();
        alert('Please enter a valid publication year (1000 - ' + currentYear + ')');
        yearInput.focus();
        return false;
    }
    
    const copiesInput = document.querySelector('input[name="copies"]');
    if (copiesInput.value < 1) {
        e.preventDefault();
        alert('Please enter at least 1 copy');
        copiesInput.focus();
        return false;
    }
    
    return true;
});
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