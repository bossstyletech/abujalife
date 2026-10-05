<?php
require_once __DIR__ . '/../config.php';

$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? '');
$pdo = getDbConnection();

if ($action === 'register') {
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = cleanInput($_POST['full_name'] ?? '');
    $gender = cleanInput($_POST['gender'] ?? 'Male');

    if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
        jsonResponse(['success' => false, 'error' => 'All fields are required.'], 400);
    }

    if (strlen($password) < 4) {
        jsonResponse(['success' => false, 'error' => 'Password must be at least 4 characters.'], 400);
    }

    // Check if user or email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'error' => 'Username or Email is already registered.'], 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        $userId = (int)$pdo->lastInsertId();

        // Create character for user
        $avatar = ($gender === 'Female') ? 'female_default.png' : 'male_default.png';
        $stmt = $pdo->prepare("
            INSERT INTO characters (user_id, full_name, gender, avatar, cash, bank, district)
            VALUES (?, ?, ?, ?, 35000.00, 10000.00, 'Kubwa')
        ");
        $stmt->execute([$userId, $fullName, $gender, $avatar]);
        $charId = (int)$pdo->lastInsertId();

        logActivity($charId, 'birth', "Welcome to Abuja! You landed in Kubwa with ₦35,000 cash and high dreams.", 35000, 100, 100);

        $pdo->commit();

        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;

        jsonResponse([
            'success' => true,
            'message' => 'Registration successful! Welcome to Abuja Life.',
            'redirect' => 'game.php'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()], 500);
    }
}

if ($action === 'login') {
    $login = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        jsonResponse(['success' => false, 'error' => 'Please enter both username/email and password.'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid login credentials.'], 401);
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];

    jsonResponse([
        'success' => true,
        'message' => 'Login successful! Entering Abuja...',
        'redirect' => 'game.php'
    ]);
}

if ($action === 'guest') {
    // Friction-free instant guest account for fast testing
    $guestNum = mt_rand(1000, 99999);
    $username = 'abuja_boss_' . $guestNum;
    $email = 'guest_' . $guestNum . '@abujalife.ng';
    $password = 'demo1234';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $names = ['Emeka Okafor', 'Fatima Bello', 'Tunde Bakare', 'Aisha Danjuma', 'Chinedu Eze', 'Zainab Aliyu'];
    $fullName = $names[array_rand($names)];
    $gender = (strpos($fullName, 'Fatima') !== false || strpos($fullName, 'Aisha') !== false || strpos($fullName, 'Zainab') !== false) ? 'Female' : 'Male';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        $userId = (int)$pdo->lastInsertId();

        $avatar = ($gender === 'Female') ? 'female_default.png' : 'male_default.png';
        $stmt = $pdo->prepare("
            INSERT INTO characters (user_id, full_name, gender, avatar, cash, bank, district)
            VALUES (?, ?, ?, ?, 50000.00, 15000.00, 'Kubwa')
        ");
        $stmt->execute([$userId, $fullName, $gender, $avatar]);
        $charId = (int)$pdo->lastInsertId();

        logActivity($charId, 'birth', "Guest player $fullName landed in Abuja with ₦50,000 cash!", 50000, 100, 100);

        $pdo->commit();

        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;

        jsonResponse([
            'success' => true,
            'message' => 'Guest account created! Enjoy Abuja.',
            'redirect' => 'game.php'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'error' => 'Guest login error: ' . $e->getMessage()], 500);
    }
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Logged out successfully.', 'redirect' => 'index.php']);
}

if ($action === 'me') {
    $userId = getAuthUserId();
    if (!$userId) {
        jsonResponse(['success' => false, 'logged_in' => false]);
    }
    $char = getUserCharacter($userId);
    jsonResponse([
        'success' => true,
        'logged_in' => true,
        'user' => [
            'id' => $userId,
            'username' => $_SESSION['username'] ?? '',
        ],
        'character' => $char
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
