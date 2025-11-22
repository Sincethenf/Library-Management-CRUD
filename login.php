<?php
session_start();
include("db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['username'] = $username;
            header("Location: landpage.php");
            exit();
        } else {
            echo "<script>alert('Invalid password');</script>";
        }
    } else {
        echo "<script>alert('No account found with this email');</script>";
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
<title>Login</title>
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

.main {
    margin-left: 260px;
    padding: 30px;
    flex: 1;
    background-color: transparent;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.form-container {
    width: 100%;
    max-width: 450px;
}

.form-header {
    text-align: center;
    margin-bottom: 40px;
}

.form-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary);
    position: relative;
    display: inline-block;
    margin-bottom: 12px;
}

.form-header h1::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: var(--secondary);
    border-radius: 2px;
}

.form-header p {
    color: var(--text-light);
    font-size: 1.1rem;
}
.form-box {
    background: var(--card-bg);
    padding: 50px 40px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.form-group {
    margin-bottom: 25px;
    position: relative;
}

.form-input {
    width: 100%;
    padding: 16px 20px;
    border: 2px solid #e2e8f0;
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: var(--transition);
    background: var(--light-bg);
    font-family: inherit;
    padding-left: 50px;
}

.form-input:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.1);
    background: white;
}

.input-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
    font-size: 1.1rem;
    z-index: 2;
}

.submit-btn {
    width: 100%;
    padding: 18px;
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

.register-link {
    text-align: center;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid #e2e8f0;
}

.register-link p {
    color: var(--text-light);
    margin-bottom: 15px;
}

.register-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    background: linear-gradient(135deg, var(--secondary), #8a84ff);
    color: white;
    text-decoration: none;
    border-radius: var(--border-radius);
    font-weight: 600;
    transition: var(--transition);
}

.register-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(108, 99, 255, 0.3);
    color: white;
}

/* basta sa screen */
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
    .form-box {
        padding: 40px 30px;
    }
    .form-header h1 {
        font-size: 2rem;
    }
}

@media (max-width: 480px) {
    .form-box {
        padding: 30px 20px;
    }
    .form-header h1 {
        font-size: 1.8rem;
    }
    .form-input {
        padding: 14px 16px;
        padding-left: 45px;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-box {
    animation: fadeInUp 0.6s ease-out;
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
        <a href="login.php" class="active"><i class="fas fa-sign-in-alt"></i><span>Login</span></a>
        <a href="register.php"><i class="fas fa-user-plus"></i><span>Register</span></a>
    </div>
</div>

<div class="main">
    <div class="form-container">
        <div class="form-header">
            <h1>Welcome Back</h1>
            <p>Sign in to your account to continue</p>
        </div>

        <div class="form-box">
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" class="form-input" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <div class="register-link">
                <p>Don't have an account?</p>
                <a href="register.php" class="register-btn">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </a>
            </div>
        </div>
    </div>
</div>
<div class="loader"></div>
<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const email = document.querySelector('input[name="email"]');
    const password = document.querySelector('input[name="password"]');
    
    if (!email.value || !password.value) {
        e.preventDefault();
        alert('Please fill in all fields');
        return false;
    }
    
    return true;
});

document.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.querySelector('.input-icon').style.color = 'var(--secondary)';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.querySelector('.input-icon').style.color = 'var(--text-light)';
    });
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