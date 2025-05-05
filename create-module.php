<?php
require_once 'includes/auth.php';
require_once 'includes/modules.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userInfo = getUserInfo();
$error = '';
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($title)) {
        $error = 'Module title is required';
    } else {
        $result = createModule($title, $description);
        
        if ($result['success']) {
            header('Location: modules.php?created=true');
            exit;
        } else {
            $error = $result['error'] ?? 'Failed to create module';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Module - FlashBoost</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            background-image: 
                radial-gradient(circle at 90% 10%, var(--primary-light) 0%, transparent 8%),
                radial-gradient(circle at 10% 90%, var(--primary-light) 0%, transparent 8%);
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
        
        /* Main content */
        main {
            flex: 1;
            padding: 3rem 0;
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
        
        /* Card styles */
        .card {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background-color: var(--primary-color);
        }
        
        .card:hover {
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 2.5rem;
        }
        
        /* Form elements */
        .form-group {
            margin-bottom: 2rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-color);
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
            min-height: 120px;
        }
        
        small {
            display: block;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 0.6rem;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2.5rem;
        }
        
        /* Button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.6rem;
            font-weight: 600;
            text-align: center;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.95rem;
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
            box-shadow: 0 8px 20px rgba(56, 161, 105, 0.2);
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--text-color);
            border: 1.5px solid #CBD5E0;
        }
        
        .btn-outline:hover {
            border-color: var(--text-color);
            background-color: rgba(0, 0, 0, 0.05);
            transform: translateY(-3px);
        }
        
        /* Alert styles */
        .alert {
            padding: 1.2rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            animation: slideDown 0.3s ease-out forwards;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .alert-error {
            background-color: rgba(229, 62, 62, 0.1);
            border-left: 4px solid #E53E3E;
        }
        
        .mb-4 {
            margin-bottom: 1.5rem;
        }
        
        /* Footer */
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container.navbar {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1.8rem;
            }
            
            .form-actions {
                flex-direction: column-reverse;
                gap: 0.8rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar">
            <div class="logo">FlashBoost</div>
            <nav class="nav-links">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="modules.php" class="active"><i class="fas fa-layer-group"></i> Modules</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>
    
    <main>
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-folder-plus"></i> Create New Module</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error mb-4">
                    <i class="fas fa-exclamation-circle" style="color: #E53E3E; margin-right: 0.8rem;"></i>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form action="create-module.php" method="post">
                        <div class="form-group">
                            <label for="title"><i class="fas fa-heading"></i> Module Title</label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="Enter a meaningful title for your module" required>
                            <small><i class="fas fa-lightbulb"></i> For example: "Biology 101", "Spanish Vocabulary", "Web Development"</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="description"><i class="fas fa-align-left"></i> Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Describe what this module contains or what it's for"></textarea>
                            <small><i class="fas fa-info-circle"></i> A good description helps you organize and find your content later</small>
                        </div>
                        
                        <div class="form-actions">
                            <a href="modules.php" class="btn btn-outline"><i class="fas fa-times"></i> Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Module</button>
                        </div>
                    </form>
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