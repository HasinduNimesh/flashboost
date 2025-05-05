<?php
require_once 'includes/auth.php';

// Initialize variables
$success = false;
$error = '';

// Process contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Here you would normally send the email
        // For now, we'll just simulate success
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - FlashBoost</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #F0FFF4;
            --primary-color: #38A169;
            --primary-light: rgba(56, 161, 105, 0.1);
            --secondary-color: #2C5282;
            --accent-color: #ECC94B;
            --text-color: #1A202C;
            --text-muted: #718096;
            --card-front: #FFFFFF;
            --card-back: #EDF2F7;
            --border-radius: 12px;
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
            position: relative;
            background-image: 
                radial-gradient(circle at 90% 10%, var(--primary-light) 0%, transparent 8%),
                radial-gradient(circle at 10% 90%, rgba(44, 82, 130, 0.08) 0%, transparent 8%);
            z-index: 0;
        }
        
        .decorator {
            position: fixed;
            z-index: -1;
            opacity: 0.4;
        }
        
        .decorator-1 {
            top: 15%;
            right: 10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, var(--primary-light) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .decorator-2 {
            bottom: 10%;
            left: 5%;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(44, 82, 130, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .navbar {
            padding: 1.2rem 2.5rem;
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
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .logo::before {
            content: '';
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--primary-color);
            border-radius: 50%;
            margin-right: 8px;
            box-shadow: 0 0 0 3px var(--primary-light);
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        main {
            flex: 1;
            padding: 3rem 0;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 2.5rem;
            position: relative;
        }
        
        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--secondary-color);
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .page-header h1::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 60px;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        
        .page-header p {
            margin-top: 1.5rem;
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 700px;
        }
        
        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem;
        }
        
        .contact-form {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .contact-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background-color: var(--primary-color);
        }
        
        .contact-form:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .contact-info {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .contact-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background-color: var(--secondary-color);
        }
        
        .contact-info:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .contact-info h3 {
            font-size: 1.3rem;
            color: var(--secondary-color);
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item i {
            font-size: 1.2rem;
            color: var(--primary-color);
            background-color: var(--primary-light);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .info-item .content h4 {
            font-size: 1.05rem;
            margin-bottom: 0.3rem;
            color: var(--text-color);
        }
        
        .info-item .content p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            transition: var(--transition);
            text-decoration: none;
        }
        
        .social-link:hover {
            transform: translateY(-3px);
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 5px 15px rgba(56, 161, 105, 0.2);
        }
        
        .form-group {
            margin-bottom: 1.8rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.7rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem;
            border: 1.5px solid #E2E8F0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: rgba(255, 255, 255, 0.8);
            font-family: 'Inter', sans-serif;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(56, 161, 105, 0.15);
            background-color: white;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 150px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
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
            box-shadow: 0 8px 20px rgba(56, 161, 105, 0.2);
        }
        
        .alert {
            padding: 1.2rem 1.4rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            animation: fadeIn 0.5s ease-out;
        }
        
        .alert-error {
            background-color: rgba(229, 62, 62, 0.1);
            border-left: 4px solid #E53E3E;
        }
        
        .alert-success {
            background-color: rgba(56, 161, 105, 0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert i {
            margin-right: 0.8rem;
            font-size: 1.1rem;
        }
        
        .alert-error i {
            color: #E53E3E;
        }
        
        .alert-success i {
            color: var(--primary-color);
        }
        
        footer {
            padding: 1.5rem 0;
            background-color: var(--card-back);
            margin-top: auto;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .footer-bottom {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        @media (max-width: 992px) {
            .contact-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem 1.5rem;
            }
            
            .nav-links {
                gap: 1.2rem;
            }
            
            .contact-form, .contact-info {
                padding: 2rem;
            }
            
            .decorator {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Decorative background elements -->
    <div class="decorator decorator-1"></div>
    <div class="decorator decorator-2"></div>
    
    <header>
        <div class="navbar">
            <div class="logo">FlashBoost</div>
            <nav class="nav-links">
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="modules.php"><i class="fas fa-layer-group"></i> Modules</a>
                <?php else: ?>
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php"><i class="fas fa-user-plus"></i> Sign Up</a>
                <?php endif; ?>
                <a href="contact.php" class="active"><i class="fas fa-envelope"></i> Contact</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-paper-plane"></i> Contact Us</h1>
                <p>Have questions or feedback about FlashBoost? We'd love to hear from you! Fill out the form below or reach out through any of our contact channels.</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <p>Your message has been sent successfully! We'll get back to you soon.</p>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="contact-container">
                <div class="contact-form">
                    <form action="contact.php" method="post">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> Your Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject"><i class="fas fa-heading"></i> Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="What is your message about?">
                        </div>
                        
                        <div class="form-group">
                            <label for="message"><i class="fas fa-comment-alt"></i> Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" placeholder="Type your message here..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
                
                <div class="contact-info">
                    <div>
                        <h3><i class="fas fa-info-circle"></i> Contact Information</h3>
                        
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div class="content">
                                <h4>Email Us</h4>
                                <p>hasindunimesh89"gmail.com</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div class="content">
                                <h4>Support Hours</h4>
                                <p>Monday - Friday: 9am to 5pm</p>
                                <p>Weekend: Limited support</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-globe"></i>
                            <div class="content">
                                <h4>Location</h4>
                                <p>We're a remote team working across the globe!</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3><i class="fas fa-link"></i> Connect With Us</h3>
                        <p>Follow us on social media or check out our code repository:</p>
                        
                        <div class="social-links">
                            <a href="https://github.com/HasinduNimesh" class="social-link" target="_blank" title="GitHub">
                                <i class="fab fa-github"></i>
                            </a>
                            <a href="https://www.linkedin.com/in/hasindu-nimesh-6457521b6/" class="social-link" target="_blank" title="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="https://discord.gg/flashboost" class="social-link" target="_blank" title="Discord">
                                <i class="fab fa-discord"></i>
                            </a>
                        </div>
                        
                        <div class="info-item" style="margin-top: 1.5rem;">
                            <i class="fab fa-github"></i>
                            <div class="content">
                                <h4>Open Source</h4>
                                <p>FlashBoost is an open-source project. Check out our repositories and contribute!</p>
                                <p><a href="https://github.com/HasinduNimesh/flashboost" target="_blank">github.com/flashboost</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> FlashBoost. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>