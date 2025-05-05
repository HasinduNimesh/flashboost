<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'includes/auth.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Process login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
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
    <title>FlashBoost - Smart Flashcards for Better Learning</title>
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
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
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
        
        /* Modern hero section */
        .hero {
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -10%;
            right: -10%;
            width: 60%;
            height: 70%;
            background-color: rgba(56, 161, 105, 0.06);
            border-radius: 50%;
            z-index: -1;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            bottom: -10%;
            left: -5%;
            width: 40%;
            height: 60%;
            background-color: rgba(44, 82, 130, 0.04);
            border-radius: 50%;
            z-index: -1;
        }
        
        .hero .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3rem;
        }
        
        .hero-content {
            flex: 1;
        }
        
        .hero-content h1 {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
            font-weight: 800;
        }
        
        .hero-content p {
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: var(--text-color);
            opacity: 0.9;
            margin-bottom: 2rem;
            max-width: 600px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .hero-image {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            position: relative;
        }
        
        .hero-image img {
            max-width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            transform: perspective(1000px) rotateY(-5deg);
        }
        
        .hero-image:hover img {
            transform: perspective(1000px) rotateY(0);
        }
        
        /* Button styles */
        .btn {
            display: inline-block;
            padding: 0.95rem 1.8rem;
            font-weight: 600;
            text-align: center;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
            text-decoration: none;
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
        
        .btn-outline {
            background-color: transparent;
            color: var(--secondary-color);
            border: 2px solid var(--secondary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(44, 82, 130, 0.15);
        }
        
        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }
        
        /* Features section */
        .features {
            padding: 5rem 0;
            background-color: #FBFCFF;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.2rem;
            color: var(--secondary-color);
            margin-bottom: 3rem;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -0.8rem;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1.2rem;
            display: inline-block;
            background: linear-gradient(45deg, rgba(56, 161, 105, 0.1), rgba(44, 82, 130, 0.1));
            width: 70px;
            height: 70px;
            line-height: 70px;
            text-align: center;
            border-radius: 50%;
        }
        
        .feature-card h3 {
            font-size: 1.3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .feature-card p {
            color: var(--text-color);
            opacity: 0.8;
            flex-grow: 1;
        }
        
        /* CTA Section */
        .cta {
            padding: 5rem 0;
            background: linear-gradient(160deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            text-align: center;
        }
        
        .cta .container {
            max-width: 800px;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .cta p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .cta .btn-primary {
            background-color: white;
            color: var(--primary-color);
            font-size: 1.1rem;
            padding: 1rem 2.5rem;
        }
        
        .cta .btn-primary:hover {
            background-color: var(--accent-color);
            color: var(--text-color);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        /* Footer */
        footer {
            background-color: var(--card-back);
            padding: 4rem 0 1.5rem;
            margin-top: auto;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .footer-col h4 {
            font-size: 1.2rem;
            color: var(--secondary-color);
            margin-bottom: 1.2rem;
            position: relative;
            padding-bottom: 0.8rem;
        }
        
        .footer-col h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .footer-col p {
            color: #718096;
            margin-bottom: 1.5rem;
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 0.8rem;
        }
        
        .footer-col ul li a {
            color: #718096;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
        }
        
        .footer-col ul li a:hover {
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .footer-col ul li a i {
            margin-right: 0.5rem;
            font-size: 0.75rem;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #E2E8F0;
            color: #718096;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .hero .container {
                flex-direction: column;
                text-align: center;
            }
            
            .hero-content {
                order: 1;
            }
            
            .hero-image {
                order: 0;
                justify-content: center;
                margin-bottom: 2rem;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .feature-card {
                text-align: center;
            }
            
            .feature-icon {
                margin: 0 auto 1.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem 1.5rem;
            }
            
            .nav-links {
                gap: 1.2rem;
            }
            
            .feature-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar">
            <div class="logo">FlashBoost</div>
            <nav class="nav-links">
                <a href="index.php" class="active">Home</a>
                <a href="register.php">Sign Up</a>
                <a href="login.php">Login</a>
            </nav>
        </div>
    </header>
    
    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Learn Smarter,<br>Not Harder</h1>
                    <p>FlashBoost uses proven memory science to help you master any subject with less effort and more fun.</p>
                    <div class="hero-buttons">
                        <a href="register.php" class="btn btn-primary">
                            <i class="fas fa-user-plus" style="margin-right: 8px;"></i>Get Started Free
                        </a>
                        <a href="#features" class="btn btn-outline">
                            <i class="fas fa-list" style="margin-right: 8px;"></i>See Features
                        </a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="images/hero-image.png" alt="FlashBoost app illustration">
                </div>
            </div>
        </section>
        
        <section id="features" class="features">
            <div class="container">
                <h2 class="section-title">Why FlashBoost Works</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🧠</div>
                        <h3>Science-Based Learning</h3>
                        <p>Our system uses spaced repetition science (SRS) to show you cards at the perfect time for memory formation.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📚</div>
                        <h3>Organize by Modules</h3>
                        <p>Create separate modules for different subjects and organize your flashcards efficiently.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h3>Track Your Progress</h3>
                        <p>Detailed analytics show you what you've mastered and what needs more work.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h3>Study Smarter</h3>
                        <p>Focus your study time on what you're about to forget, not what you already know.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="cta">
            <div class="container">
                <h2>Ready to boost your learning?</h2>
                <p>Join thousands of students, professionals, and lifelong learners who are using FlashBoost to learn faster.</p>
                <a href="register.php" class="btn btn-primary btn-lg">Create Your Free Account</a>
            </div>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="logo">FlashBoost</div>
                    <p>Modern flashcard app powered by brain science to help you learn more effectively.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="register.php"><i class="fas fa-chevron-right"></i> Sign Up</a></li>
                        <li><a href="login.php"><i class="fas fa-chevron-right"></i> Login</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Help</h4>
                    <ul>
                        <li><a href="contactus.php"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> FlashBoost. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>