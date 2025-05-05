<?php
require_once 'includes/auth.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Process login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = loginUser($email, $password);
    
    if ($result['success']) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FlashBoost</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #F0FFF4;
            --primary-color: #38A169;
            --secondary-color: #2C5282;
            --accent-color: #ECC94B;
            --text-color: #1A202C;
            --card-front: #FFFFFF;
            --card-back: #EDF2F7;
            --border-radius: 10px;
            --box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Modern navbar */
        .navbar {
            padding: 1rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            background-color: var(--card-front);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary-color);
            letter-spacing: -0.5px;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            position: relative;
            padding: 0.5rem 0;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary-color);
            transition: var(--transition);
        }
        
        .nav-links a:hover::after, 
        .nav-links a.active::after {
            width: 100%;
        }
        
        .nav-links a:hover, 
        .nav-links a.active {
            color: var(--primary-color);
        }
        
        /* Modern auth container */
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .auth-container {
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        
        .auth-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        /* Modern form elements */
        .text-center {
            text-align: center;
        }
        
        h1 {
            margin-bottom: 1.5rem;
            font-weight: 700;
            font-size: 2rem;
            color: var(--secondary-color);
        }
        
        .mb-4 {
            margin-bottom: 1.5rem;
        }
        
        .mt-3 {
            margin-top: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.9rem;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
        }
        
        .form-control {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 1.5px solid var(--card-back);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: rgba(255, 255, 255, 0.8);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(56, 161, 105, 0.15);
            background-color: white;
        }
        
        .checkbox {
            display: flex;
            align-items: center;
        }
        
        .checkbox input {
            margin-right: 0.7rem;
            cursor: pointer;
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
        }
        
        .checkbox label {
            margin-bottom: 0;
            font-weight: 500;
            font-size: 0.9rem;
            user-select: none;
        }
        
        .btn {
            display: inline-block;
            padding: 0.95rem 1.5rem;
            font-weight: 600;
            text-align: center;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(56, 161, 105, 0.15);
        }
        
        .btn-primary:hover {
            background-color: #2F8A5B;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(56, 161, 105, 0.2);
        }
        
        .btn-primary:active {
            transform: translateY(-1px);
        }
        
        .btn-block {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert {
            padding: 1rem 1.2rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
        }
        
        .alert-error {
            background-color: rgba(236, 201, 75, 0.15);
            border-left: 4px solid var(--accent-color);
        }
        
        .alert-error i {
            margin-right: 0.8rem;
            color: var(--accent-color);
        }
        
        /* Links */
        a {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 500;
            transition: var(--transition);
        }
        
        a:hover {
            color: #2F8A5B;
        }
        
        /* Footer */
        footer {
            padding: 1.5rem 0;
            background-color: var(--card-back);
            text-align: center;
            font-size: 0.9rem;
            margin-top: auto;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem 1.5rem;
            }
            
            .nav-links {
                gap: 1.2rem;
            }
            
            main {
                padding: 1rem;
            }
            
            .auth-container {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar">
            <div class="logo">FlashBoost</div>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="register.php">Sign Up</a>
                <a href="login.php" class="active">Login</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="auth-container">
            <h1 class="text-center mb-4">Welcome Back</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="input-icon fas fa-envelope"></i>
                        <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="input-icon fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <p class="text-center mt-3">
                Don't have an account? <a href="register.php">Create one now</a>
            </p>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> FlashBoost. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>