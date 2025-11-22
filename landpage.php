<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

// Define all categories
$allCategories = ['Horror', 'Romance', 'Mystery/Thriller', 'Science Fiction', 'Historical Fiction', 'Literary Fiction'];

// Initialize category counts
$categoryCounts = array_fill_keys($allCategories, 0);

// Fetch counts from database
$result = $conn->query("SELECT category, COUNT(*) as count FROM books GROUP BY category");
while ($row = $result->fetch_assoc()) {
    if (in_array($row['category'], $allCategories)) {
        $categoryCounts[$row['category']] = (int)$row['count'];
    }
}

// Fetch most borrowed books based on borrow_count
$mostBorrowedResult = $conn->query("
    SELECT * FROM books 
    WHERE borrow_count > 0 
    ORDER BY borrow_count DESC, title ASC 
    LIMIT 4
");
$mostBorrowedBooks = [];
while ($row = $mostBorrowedResult->fetch_assoc()) {
    $mostBorrowedBooks[] = $row;
}

// Get total statistics
$totalBooks = $conn->query("SELECT COUNT(*) as total FROM books")->fetch_assoc()['total'];
$activeBorrows = $conn->query("SELECT COUNT(*) as active FROM books WHERE copies = 0")->fetch_assoc()['active'];
$mostPopularCount = max($categoryCounts);
$totalCategories = count(array_filter($categoryCounts));

// Prepare Chart.js data
$categories = json_encode(array_keys($categoryCounts));
$counts = json_encode(array_values($categoryCounts));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Library Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #002147;
    --primary-light: #01336b;
    --secondary: #6c63ff;
    --accent: #ff6b6b;
    --success: #28a745;
    --warning: #ffc107;
    --light-bg: #f8faff;
    --card-bg: #ffffff;
    --text-dark: #2d3748;
    --text-light: #718096;
    --border-radius: 16px;
    --shadow: 0 10px 30px rgba(0, 33, 71, 0.1);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    display: flex;
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f7ff 0%, #e6eeff 100%);
    color: var(--text-dark);
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

/* Main content */
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

/* Stats cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: var(--card-bg);
    padding: 24px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--secondary), var(--accent));
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    font-size: 1.5rem;
    color: white;
    transition: var(--transition);
}

.stat-card:hover .stat-icon {
    transform: scale(1.1);
}

.stat-card:nth-child(1) .stat-icon {
    background: linear-gradient(135deg, #6c63ff, #9d94ff);
}

.stat-card:nth-child(2) .stat-icon {
    background: linear-gradient(135deg, #ff6b6b, #ff9e9e);
}

.stat-card:nth-child(3) .stat-icon {
    background: linear-gradient(135deg, #4cd964, #8ae6a1);
}

.stat-card:nth-child(4) .stat-icon {
    background: linear-gradient(135deg, #5ac8fa, #8bdfff);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 8px;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-light);
    font-weight: 500;
}

/* Two-column top section */
.top-section {
    display: flex;
    justify-content: space-between;
    align-items: stretch;
    width: 100%;
    gap: 24px;
    margin-bottom: 40px;
}

/* Left side: Welcome message */
.welcome-box {
    width: 50%;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    padding: 30px;
    border-radius: var(--border-radius);
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-shadow: var(--shadow);
    text-align: left;
    position: relative;
    overflow: hidden;
    transition: var(--transition);
}

.welcome-box::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    transform: rotate(30deg);
}

.welcome-box h1 {
    font-size: 2rem;
    margin-bottom: 12px;
    position: relative;
    z-index: 1;
}

.welcome-box p {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 20px;
    max-width: 90%;
    line-height: 1.6;
    position: relative;
    z-index: 1;
}

.welcome-box img.book-decor {
    position: absolute;
    right: 20px;
    bottom: 20px;
    width: 140px;
    height: auto;
    transform: rotate(5deg);
    filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3));
    transition: var(--transition);
    z-index: 1;
}

.welcome-box:hover img.book-decor {
    transform: rotate(0deg) scale(1.05);
    filter: drop-shadow(0 10px 20px rgba(0,0,0,0.4));
}

.welcome-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

/* Right side: Chart */
.chart-container {
    width: 50%;
    height: 300px;
    background: var(--card-bg);
    padding: 24px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    transition: var(--transition);
    position: relative;
}

.chart-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.chart-title {
    position: absolute;
    top: 20px;
    left: 20px;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary);
    z-index: 2;
}

/* Section headers */
.section-header {
    display: flex;
    align-items: center;
    margin: 40px 0 30px;
    position: relative;
}

.section-header h2 {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary);
    margin-right: 16px;
}

.section-header::after {
    content: '';
    flex: 1;
    height: 2px;
    background: linear-gradient(90deg, var(--primary), transparent);
    margin-left: 16px;
}

/* Most borrowed books */
.most-borrowed-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.borrowed-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: var(--card-bg);
    padding: 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    transition: var(--transition);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.borrowed-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, var(--secondary), var(--accent));
}

.borrowed-card img {
    width: 120px;
    height: 160px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 16px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    transition: var(--transition);
}

.borrowed-card h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-dark);
    line-height: 1.3;
}

.borrowed-card p {
    font-size: 0.9rem;
    color: var(--text-light);
    margin-bottom: 6px;
}

.borrow-count {
    background: linear-gradient(135deg, var(--secondary), var(--accent));
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-top: 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.borrowed-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.borrowed-card:hover img {
    transform: scale(1.05);
}

/* Empty state */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.empty-state i {
    font-size: 4rem;
    color: var(--text-light);
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.5rem;
    color: var(--text-dark);
    margin-bottom: 12px;
}

.empty-state p {
    color: var(--text-light);
    max-width: 400px;
    margin: 0 auto;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 40px;
}

.action-card {
    background: var(--card-bg);
    padding: 25px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    text-align: center;
    transition: var(--transition);
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    color: inherit;
}

.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: white;
}

.action-card:nth-child(1) .action-icon {
    background: linear-gradient(135deg, var(--secondary), #8a84ff);
}

.action-card:nth-child(2) .action-icon {
    background: linear-gradient(135deg, var(--success), #5cd85c);
}

.action-card:nth-child(3) .action-icon {
    background: linear-gradient(135deg, var(--warning), #ffd54f);
}

.action-card h4 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary);
}

.action-card p {
    font-size: 0.85rem;
    color: var(--text-light);
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 1100px) {
    .top-section {
        flex-direction: column;
    }
    .welcome-box, .chart-container {
        width: 100%;
    }
    .chart-container {
        height: 300px;
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
    .sidebar a i {
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
    .most-borrowed-container {
        grid-template-columns: 1fr;
    }
    .stats-container {
        grid-template-columns: repeat(2, 1fr);
    }
    .quick-actions {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    .welcome-box h1 {
        font-size: 1.5rem;
    }
    .welcome-box img.book-decor {
        width: 100px;
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
        <a href="landpage.php" class="active"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="booklist.php"><i class="fas fa-book"></i><span>Booklist</span></a>
        <a href="dashboard.php"><i class="fas fa-plus-circle"></i><span>Add Book</span></a>
        <a href="return.php"><i class="fas fa-exchange-alt"></i><span>Return Book</span></a>
        <a href="about.php"><i class="fas fa-info-circle"></i><span>About</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="dashboard-header">
        <h1>Library Dashboard</h1>
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
  
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-value"><?php echo $totalBooks; ?></div>
            <div class="stat-label">Total Books</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?php echo $activeBorrows; ?></div>
            <div class="stat-label">Active Borrows</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-value"><?php echo $mostPopularCount; ?></div>
            <div class="stat-label">Most Popular Category</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-value"><?php echo $totalCategories; ?></div>
            <div class="stat-label">Active Categories</div>
        </div>
    </div>

    <div class="top-section">
        <!-- Left side: Welcome message -->
        <div class="welcome-box">
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>
                Step into your digital library where managing, discovering, 
                and borrowing is made simple and fun. Check out your favorite genres, 
                explore trending titles, and keep track of your reading journey—all in one place.
            </p>
            <img src="sources/stackofbooks.png" alt="Book Image" class="book-decor">
        </div>

        <!-- Right side: Chart -->
        <div class="chart-container">
            <div class="chart-title"></div>
            <canvas id="categoryChart"></canvas>
        </div>
    </div>

    <div class="section-header">
        <h2>Most Borrowed Books</h2>
    </div>
    
    <div class="most-borrowed-container">
        <?php if (count($mostBorrowedBooks) > 0): ?>
            <?php foreach ($mostBorrowedBooks as $book): ?>
                <div class="borrowed-card">
                    <img src="<?php echo htmlspecialchars($book['image'] ?: 'default-book.png'); ?>" alt="Book Cover">
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
                    <div class="borrow-count">
                        <i class="fas fa-book-reader"></i>
                        Borrowed <?php echo $book['borrow_count']; ?> times
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>No Borrow History Yet</h3>
                <p>Books will appear here once they've been borrowed by users. Start by adding books to your library!</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="section-header">
        <h2>Quick Actions</h2>
    </div>

    <div class="quick-actions">
        <a href="dashboard.php" class="action-card">
            <div class="action-icon">
                <i class="fas fa-plus-circle"></i>
            </div>
            <h4>Add New Book</h4>
            <p>Add a new book to the library collection</p>
        </a>
        <a href="booklist.php" class="action-card">
            <div class="action-icon">
                <i class="fas fa-list"></i>
            </div>
            <h4>Browse Books</h4>
            <p>View and manage all books in the library</p>
        </a>
        <a href="return.php" class="action-card">
            <div class="action-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <h4>Return Books</h4>
            <p>Process book returns using QR codes</p>
        </a>
    </div>
</div>
<div class="loader"></div>
<script>
const ctx = document.getElementById('categoryChart').getContext('2d');

// Create gradients for the line chart
const gradientLine = ctx.createLinearGradient(0, 0, 0, 300);
gradientLine.addColorStop(0, 'rgba(108, 99, 255, 0.8)');
gradientLine.addColorStop(1, 'rgba(108, 99, 255, 0.2)');

const gradientFill = ctx.createLinearGradient(0, 0, 0, 300);
gradientFill.addColorStop(0, 'rgba(108, 99, 255, 0.3)');
gradientFill.addColorStop(1, 'rgba(108, 99, 255, 0.05)');

const categoryChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo $categories; ?>,
        datasets: [{
            label: 'Number of Books',
            data: <?php echo $counts; ?>,
            borderColor: '#6c63ff',
            backgroundColor: gradientFill,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#6c63ff',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            pointHoverBackgroundColor: '#6c63ff',
            pointHoverBorderColor: '#ffffff',
            pointHoverBorderWidth: 3
        }]
    },
    options: {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 33, 71, 0.9)',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                bodyFont: {
                    weight: 'bold'
                },
                padding: 12,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                    title: function(tooltipItems) {
                        return tooltipItems[0].label;
                    },
                    label: function(context) {
                        return `Books: ${context.parsed.y}`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    font: {
                        size: 11
                    },
                    stepSize: 1
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        },
        animation: {
            duration: 1000,
            easing: 'easeOutQuart'
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// Add hover effect to chart container
document.querySelector('.chart-container').addEventListener('mouseenter', function() {
    categoryChart.options.scales.y.ticks.font.size = 12;
    categoryChart.options.scales.x.ticks.font.size = 12;
    categoryChart.update();
});

document.querySelector('.chart-container').addEventListener('mouseleave', function() {
    categoryChart.options.scales.y.ticks.font.size = 11;
    categoryChart.options.scales.x.ticks.font.size = 11;
    categoryChart.update();
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