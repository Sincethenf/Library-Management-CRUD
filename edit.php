<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

if (!isset($_GET['id'])) {
    die("Book ID is missing.");
}

$id = intval($_GET['id']); 

$result = $conn->query("SELECT * FROM books WHERE id = $id");

if (!$result || $result->num_rows === 0) {
    die("Book not found.");
}

$book = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $author = $conn->real_escape_string($_POST['author']);
    $year = intval($_POST['year']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $copies = intval($_POST['copies']); // NEW FIELD

    $imagePath = $book['image']; // Keep old image if none uploaded

    // Handle image upload
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
                // Delete old image if exists
                if (!empty($book['image']) && file_exists($book['image'])) {
                    unlink($book['image']);
                }
                $imagePath = $targetFile;
            }
        }
    }

    // Determine status based on copies
    $status = ($copies > 0) ? 'Available' : 'Borrowed';

    // Update query including copies
    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, year=?, category=?, description=?, image=?, copies=?, status=? WHERE id=?");
    $stmt->bind_param("ssisssisi", $title, $author, $year, $category, $description, $imagePath, $copies, $status, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Book updated successfully!'); window.location='booklist.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating book!');</script>";
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
<title>Edit Book</title>
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

/* Image Preview */
.image-preview-container {
    text-align: center;
    margin: 15px 0;
}

.image-preview {
    width: 150px;
    height: 200px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: var(--shadow);
    border: 3px solid #e2e8f0;
    transition: var(--transition);
}

.image-preview:hover {
    transform: scale(1.05);
    border-color: var(--secondary);
}

.preview-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text-dark);
    font-size: 0.9rem;
}

/* Buttons */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 25px;
}

.submit-btn {
    flex: 2;
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
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 33, 71, 0.3);
}

.cancel-btn {
    flex: 1;
    padding: 16px;
    background: linear-gradient(135deg, #6c757d, #868e96);
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
    text-decoration: none;
    text-align: center;
}

.cancel-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
    color: white;
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
    .sidebar a i {
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
    .form-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .form-box {
        padding: 20px 15px;
    }
    .form-header h1 {
        font-size: 1.8rem;
    }
    .image-preview {
        width: 120px;
        height: 160px;
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
        <a href="dashboard.php"><i class="fas fa-plus-circle"></i><span>Add Book</span></a>
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
            <h1>Edit Book</h1>
            <p>Update the book information below</p>
        </div>

        <div class="form-box">
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <div class="form-group">
                    <label class="form-label">Book Title</label>
                    <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Author</label>
                    <input type="text" name="author" class="form-input" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Publication Year</label>
                    <input type="number" name="year" class="form-input" value="<?php echo htmlspecialchars($book['year']); ?>" min="1000" max="<?php echo date('Y'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <option value="" disabled>Select a category</option>
                        <?php
                        $categories = ['Horror','Romance','Mystery/Thriller','Science Fiction','Historical Fiction','Literary Fiction'];
                        foreach($categories as $cat) {
                            $selected = ($book['category'] == $cat) ? 'selected' : '';
                            echo "<option value=\"$cat\" $selected>$cat</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" placeholder="Enter book description"><?php echo htmlspecialchars($book['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Number of Copies</label>
                    <input type="number" name="copies" class="form-input" value="<?php echo htmlspecialchars($book['copies']); ?>" min="0" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Book Cover Image</label>
                    <div class="file-input-container">
                        <input type="file" name="book_image" class="file-input" id="bookImage" accept="image/*">
                        <label for="bookImage" class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Change book cover image (optional)</span>
                        </label>
                    </div>
                    <div class="file-name" id="fileName">No file chosen</div>
                </div>

                <?php if (!empty($book['image'])): ?>
                <div class="image-preview-container">
                    <span class="preview-label">Current Image:</span>
                    <img src="<?php echo htmlspecialchars($book['image']); ?>" alt="Current Book Cover" class="image-preview">
                </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        Update Book
                    </button>
                    <a href="booklist.php" class="cancel-btn">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// File input display
document.getElementById('bookImage').addEventListener('change', function(e) {
    const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
    document.getElementById('fileName').textContent = fileName;
});

// Form validation
document.getElementById('editForm').addEventListener('submit', function(e) {
    const yearInput = document.querySelector('input[name="year"]');
    const currentYear = new Date().getFullYear();
    
    if (yearInput.value < 1000 || yearInput.value > currentYear) {
        e.preventDefault();
        alert('Please enter a valid publication year (1000 - ' + currentYear + ')');
        yearInput.focus();
        return false;
    }
    
    const copiesInput = document.querySelector('input[name="copies"]');
    if (copiesInput.value < 0) {
        e.preventDefault();
        alert('Please enter a valid number of copies (0 or more)');
        copiesInput.focus();
        return false;
    }
    
    return true;
});

// Show image preview when new image is selected
document.getElementById('bookImage').addEventListener('change', function(e) {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Create or update preview container
            let previewContainer = document.querySelector('.image-preview-container');
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.className = 'image-preview-container';
                document.getElementById('bookImage').closest('.form-group').after(previewContainer);
            }
            previewContainer.innerHTML = `
                <span class="preview-label">New Image Preview:</span>
                <img src="${e.target.result}" alt="New Book Cover" class="image-preview">
            `;
        }
        reader.readAsDataURL(file);
    }
});
</script>

</body>
</html>