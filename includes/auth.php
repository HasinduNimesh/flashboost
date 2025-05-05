<?php
require_once 'db.php';

// Start the session only once at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function registerUser(string $username, string $email, string $password): array {
    global $pdo;
    
    // Validate inputs
    $username = trim($username);
    $email = trim($email);
    
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'All fields are required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email format'];
    }
    
    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters'];
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'error' => 'Email already registered'];
        }
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'error' => 'Username already taken'];
        }
        
        // Hash password using bcrypt
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password_hash]);
        
        $userId = $pdo->lastInsertId();
        
        return ['success' => true, 'userId' => $userId];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function loginUser(string $email, string $password): array {
    global $pdo;
    
    $email = trim($email);
    
    try {
        // Fetch user by email
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }
        
        // Start session and set user data
        $_SESSION['userId'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['logged_in'] = true;
        
        return ['success' => true, 'userId' => $user['id'], 'username' => $user['username']];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function logoutUser(): void {
    // Clear all session variables
    $_SESSION = [];
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
}

function isLoggedIn(): bool {
    // Don't call session_start() here as we do it at the top of the file
    return isset($_SESSION['userId']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function getCurrentUserId(): ?int {
    // Don't call session_start() here as we do it at the top of the file
    return $_SESSION['userId'] ?? null;
}

function getUserInfo(): ?array {
    // Don't call session_start() here as we do it at the top of the file
    if (!isset($_SESSION['userId'])) {
        return null;
    }
    
    return [
        'userId' => $_SESSION['userId'],
        'username' => $_SESSION['username'] ?? null
    ];
}
?>