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
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    die("Book not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Book Details</title>
<style>
body { font-family: Arial, sans-serif; background: #e6f0ff; color: #002147; padding: 30px; }
.container { max-width: 700px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
h1 { text-align: center; margin-bottom: 20px; }
.book-cover { width: 200px; height: 250px; object-fit: cover; display:block; margin: 0 auto 20px auto; border-radius:8px; }
p { margin-bottom: 12px; line-height:1.5; }
a.back { display:inline-block; margin-top: 15px; padding: 8px 14px; background:#002147; color:#fff; border-radius:6px; text-decoration:none; }
a.back:hover { background:#01336b; }
</style>
</head>
<body>
<div class="container">
    <h1><?php echo htmlspecialchars($book['title']); ?></h1>

    <?php if (!empty($book['image'])): ?>
        <img src="<?php echo htmlspecialchars($book['image']); ?>" class="book-cover" alt="Book Cover">
    <?php else: ?>
        <img src="default-book.png" class="book-cover" alt="No Cover">
    <?php endif; ?>

    <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
    <p><strong>Year:</strong> <?php echo htmlspecialchars($book['year']); ?></p>
    <p><strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
    <p><strong>Status:</strong> <?php echo htmlspecialchars($book['status']); ?></p>
    <p><strong>Borrowed By:</strong> <?php echo htmlspecialchars($book['borrowed_by'] ?? ''); ?></p>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($book['description'] ?? 'No description available.')); ?></p>

    <a href="booklist.php" class="back">Back to Book List</a>
</div>
</body>
</html>
