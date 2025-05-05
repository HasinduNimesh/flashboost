<?php
require_once 'includes/auth.php';
require_once 'includes/modules.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Fetch all modules for the current user
$modules = getAllModules();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modules - FlashBoost</title>
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
            --box-shadow-hover: 0 20px 35px rgba(0,0,0,0.1);
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
                radial-gradient(circle at 20% 20%, var(--primary-light) 0%, transparent 8%),
                radial-gradient(circle at 80% 60%, var(--primary-light) 0%, transparent 8%);
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
            gap: 2.5rem;
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2.5rem;
            position: relative;
        }
        
        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--secondary-color);
            position: relative;
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
            box-shadow: 0 8px 20px rgba(56, 161, 105, 0.2);
        }
        
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
        
        .alert-success {
            background-color: rgba(56, 161, 105, 0.15);
            border-left: 4px solid var(--primary-color);
        }
        
        .mb-4 {
            margin-bottom: 1.5rem;
        }
        
        /* Module grid */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }
        
        .module-card {
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.8rem;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background-color: var(--primary-color);
            transform: scaleX(0.3);
            transform-origin: left;
            transition: var(--transition);
        }
        
        .module-card:hover::before {
            transform: scaleX(1);
        }
        
        .module-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--box-shadow-hover);
        }
        
        .module-card h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .module-card h3 i {
            color: var(--primary-color);
            font-size: 0.9em;
        }
        
        .module-card p {
            color: var(--text-muted);
            margin-bottom: 1.8rem;
            flex-grow: 1;
            line-height: 1.7;
        }
        
        .module-meta {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-muted);
            padding-top: 0.5rem;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .created-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background-color: var(--card-front);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .empty-state::before {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 200px;
            height: 200px;
            background-color: var(--primary-light);
            border-radius: 50%;
            z-index: 0;
        }
        
        .empty-state::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 250px;
            height: 250px;
            background-color: rgba(44, 82, 130, 0.05);
            border-radius: 50%;
            z-index: 0;
        }
        
        .empty-state-content {
            position: relative;
            z-index: 1;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }
        
        .empty-state h2 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        
        .empty-state p {
            color: var(--text-muted);
            max-width: 500px;
            margin: 0 auto 2rem;
            font-size: 1.1rem;
        }
        
        .empty-state .btn-primary {
            padding: 0.9rem 2rem;
            font-size: 1.05rem;
        }
        
        /* Footer */
        footer {
            padding: 1.5rem 0;
            background-color: var(--card-back);
            margin-top: 3rem;
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 2rem;
            }
            
            .modules-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                gap: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container navbar">
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
                <h1>Your Modules</h1>
                <a href="create-module.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Module
                </a>
            </div>
            
            <?php if (isset($_GET['created']) && $_GET['created'] == 'true'): ?>
                <div class="alert alert-success mb-4">
                    <i class="fas fa-check-circle" style="margin-right: 0.8rem; color: var(--primary-color);"></i>
                    <p>Module created successfully!</p>
                </div>
            <?php endif; ?>
            
            <?php if ($modules['success'] && count($modules['modules']) > 0): ?>
                <div class="modules-grid">
                    <?php foreach ($modules['modules'] as $module): ?>
                        <div class="module-card">
                            <h3><i class="fas fa-cube"></i> <?php echo htmlspecialchars($module['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($module['description'], 0, 100)) . (strlen($module['description']) > 100 ? '...' : ''); ?></p>
                            <div class="module-meta">
                                <span class="created-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('M j, Y', strtotime($module['created_at'])); ?>
                                </span>
                                <span class="card-count">
                                    <i class="fas fa-clone"></i>
                                    <?php echo isset($module['card_count']) ? $module['card_count'] : '0'; ?> cards
                                </span>
                            </div>
                            <a href="module.php?id=<?php echo $module['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-folder-open"></i> Open Module
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-content">
                        <div class="empty-state-icon">
                            <i class="fas fa-folder-plus"></i>
                        </div>
                        <h2>No modules found</h2>
                        <p>You haven't created any modules yet. Create your first module to get started!</p>
                        <a href="create-module.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Your First Module
                        </a>
                    </div>
                </div>
            <?php endif; ?>
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