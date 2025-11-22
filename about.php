<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About - Library Management System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #002147;
    --primary-light: #01336b;
    --secondary: #6c63ff;
    --accent: #ff6b6b;
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
    flex-direction: column;
    min-height: 100vh;
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

/* About Content */
.about-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.about-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 30px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.about-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, var(--secondary), var(--accent));
}

.about-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.about-card h2 {
    color: var(--primary);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.5rem;
}

.about-card h2 i {
    color: var(--secondary);
}

.about-card p {
    color: var(--text-dark);
    line-height: 1.7;
    margin-bottom: 15px;
}

/* Features Grid */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-top: 40px;
}

.feature-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 25px;
    text-align: center;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--secondary), var(--accent));
}

.feature-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.feature-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--secondary), #8a84ff);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 1.8rem;
}

.feature-card h3 {
    color: var(--primary);
    margin-bottom: 12px;
    font-size: 1.3rem;
}

.feature-card p {
    color: var(--text-light);
    line-height: 1.6;
}

/* Stats Section */
.stats-section {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border-radius: var(--border-radius);
    padding: 40px 30px;
    color: white;
    margin-top: 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.stats-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    transform: rotate(30deg);
}

.stats-section h2 {
    margin-bottom: 30px;
    position: relative;
    z-index: 1;
    font-size: 1.8rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    position: relative;
    z-index: 1;
}

.stat-item {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: var(--border-radius);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 8px;
    display: block;
}

.stat-label {
    font-size: 1rem;
    opacity: 0.9;
}

/* Footer */
.footer {
    background: var(--card-bg);
    padding: 20px;
    text-align: center;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-top: auto;
}

.footer p {
    color: var(--text-light);
    margin: 0;
}

/* Responsive */
@media (max-width: 1100px) {
    .about-container {
        grid-template-columns: 1fr;
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
    .features-grid {
        grid-template-columns: 1fr;
    }
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
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
        <a href="return.php"><i class="fas fa-exchange-alt"></i><span>Return Book</span></a>
        <a href="about.php" class="active"><i class="fas fa-info-circle"></i><span>About</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="dashboard-header">
        <h1>About Our System</h1>
        <div class="user-info">
            <div class="user-avatar">
                <?php 
                    if (isset($_SESSION['username'])) {
                        $initial = strtoupper(substr($_SESSION['username'], 0, 1));
                        echo $initial;
                    } else {
                        echo "U";
                    }
                ?>
            </div>
            <div>
                <div class="user-name">
                    <?php 
                        if (isset($_SESSION['username'])) {
                            echo htmlspecialchars($_SESSION['username']);
                        } else {
                            echo "User";
                        }
                    ?>
                </div>
                <div class="user-role">Library User</div>
            </div>
        </div>
    </div>

    <div class="about-container">
        <div class="about-card">
            <h2><i class="fas fa-book-open"></i> System Overview</h2>
            <p>
                The Library Management System is a comprehensive digital platform designed to streamline the process of managing books within a library environment. 
                This system allows librarians and users to efficiently add, track, borrow, and return books with ease.
            </p>
            <p>
                By digitizing library operations, the system ensures that all library resources are properly cataloged, easily accessible, and efficiently monitored, 
                ultimately improving overall library management and user experience.
            </p>
            <p>
                Our mission is to bridge the gap between traditional library systems and modern digital solutions, providing an intuitive platform that serves both librarians and patrons effectively.
            </p>
        </div>

        <div class="about-card">
            <h2><i class="fas fa-bullseye"></i> Our Mission</h2>
            <p>
                To revolutionize library management through innovative technology that simplifies complex processes, enhances accessibility, and promotes literacy and learning in our community.
            </p>
            <p>
                We believe that efficient library management should be accessible to institutions of all sizes, from small community libraries to large academic institutions.
            </p>
            <p>
                Through continuous improvement and user feedback, we strive to create a system that adapts to the evolving needs of modern libraries and their patrons.
            </p>
        </div>
    </div>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3>Easy Book Discovery</h3>
            <p>Advanced search and filtering capabilities to help users find books quickly and efficiently.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-qrcode"></i>
            </div>
            <h3>QR Code Integration</h3>
            <p>Streamlined borrowing and returning process using QR code technology for instant book identification.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3>Real-time Analytics</h3>
            <p>Comprehensive dashboard with visual analytics to track library usage and popular books.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h3>Responsive Design</h3>
            <p>Fully responsive interface that works seamlessly across all devices and screen sizes.</p>
        </div>
    </div>

    <div class="stats-section">
        <h2>Library System Impact</h2>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number">500+</span>
                <span class="stat-label">Books Cataloged</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">1.2K+</span>
                <span class="stat-label">Successful Returns</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">95%</span>
                <span class="stat-label">User Satisfaction</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">24/7</span>
                <span class="stat-label">System Availability</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2023 Library Management System. All rights reserved. | Designed for Educational Institutions</p>
    </div>
</div>
<div class="loader"></div>
<script>
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