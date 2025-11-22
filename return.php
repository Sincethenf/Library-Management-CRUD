<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qrcode_data'])) {
    $qrcode_data = trim($_POST['qrcode_data']);
    
    // Extract book ID from scanned QR code
    if (preg_match('/Book ID:\s*(\d+)/', $qrcode_data, $matches)) {
        $id = intval($matches[1]);

        // Increase copies by 1, mark status Available, clear borrowed_by
        $stmt = $conn->prepare("
            UPDATE books 
            SET copies = copies + 1, 
                status = 'Available', 
                borrowed_by = NULL 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "<script>alert('Book returned successfully!'); window.location='booklist.php';</script>";
        } else {
            echo "<script>alert('Failed to return the book!'); window.location='booklist.php';</script>";
        }

        $stmt->close();
        exit();
    } else {
        echo "<script>alert('Invalid QR Code format!'); window.location='return.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Return Book</title>
<script src="https://unpkg.com/html5-qrcode"></script>
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

/* Main content */
.main-content {
    width: 100%;
    padding: 40px;
    background-color: #fff;
    border-radius: 12px;
    margin: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    text-align: center;
}

#qr-reader {
    width: 320px;
    margin: 20px auto;
    border: 2px solid #002147;
    border-radius: 8px;
    padding: 10px;
    background: #ffffff;
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
        <a href="dashboard.php"><i class="fas fa-plus-circle"></i><span>Add Book</span></a>
        <a href="return.php" class="active"><i class="fas fa-exchange-alt"></i><span>Return Book</span></a>
        <a href="about.php"><i class="fas fa-info-circle"></i><span>About</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="main-content">
        <h2>Return Book (Scan QR Code)</h2>
        <form id="qr-form" method="POST">
            <input type="hidden" id="qrcode_data" name="qrcode_data">
        </form>
        <div id="qr-reader"></div>
    </div>
</div>
<div class="loader"></div>
<script>
function onScanSuccess(decodedText) {
    alert(`Scanned: ${decodedText}`);
    document.getElementById('qrcode_data').value = decodedText;
    document.getElementById('qr-form').submit();
}

const html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", {
    fps: 10,
    qrbox: { width: 250, height: 250 },
    rememberLastUsedCamera: true
});
html5QrcodeScanner.render(onScanSuccess);
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