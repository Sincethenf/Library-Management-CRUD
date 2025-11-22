<?php
session_start();


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include("db.php");


if (!isset($_GET['id'])) {
    echo "<script>alert('No book ID provided.'); window.location='booklist.php';</script>";
    exit();
}

$id = intval($_GET['id']); 

$stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "<script>alert('Book deleted successfully.'); window.location='booklist.php';</script>";
    exit();
} else {
    echo "<script>alert('Error deleting book.'); window.location='booklist.php';</script>";
}

$stmt->close();
$conn->close();
?>
