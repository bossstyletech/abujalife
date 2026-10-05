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
    $skinTone = cleanInput($_POST['skin_tone'] ?? '#704225');
    $hairStyle = cleanInput($_POST['hair_style'] ?? 'fade');
    $hairColor = cleanInput($_POST['hair_color'] ?? '#111111');
    $outfit = cleanInput($_POST['outfit'] ?? 'casual');
    $archetype = cleanInput($_POST['archetype'] ?? 'hustler');

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

    // Archetype attributes
    $startCash = 15000.00;
    $startBank = 5000.00;
    $startDistrict = 'Kubwa';
    $startCred = 15;
    $startIQ = 20;
    $startEdu = 'SSCE';
    $primaryCar = null;
    $originMessage = "Arrived at Kubwa with ₦15,000 cash and pure grit. Time to conquer Abuja!";

    if ($archetype === 'rich') {
        $startCash = 1500000.00;
        $startBank = 8500000.00;
        $startDistrict = 'Maitama';
        $startCred = 45;
        $startIQ = 45;
        $startEdu = 'BSc';
        $primaryCar = 4; // Lexus RX 350
        $originMessage = "Born with a silver spoon in Maitama! Your family connections gave you ₦10,000,000 and a Lexus SUV.";
    } elseif ($archetype === 'middle') {
        $startCash = 120000.00;
        $startBank = 350000.00;
        $startDistrict = 'Gwarinpa';
        $startCred = 25;
        $startIQ = 35;
        $startEdu = 'BSc';
        $primaryCar = 2; // Toyota Corolla Big Daddy
        $originMessage = "Raised in a respectable civil servant home in Gwarinpa. You have a UniAbuja BSc and steady savings.";
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        $userId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO characters (
                user_id, full_name, gender, skin_tone, hair_style, hair_color, outfit, archetype,
                cash, bank, district, street_cred, intelligence, education_level, primary_vehicle_id
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, $fullName, $gender, $skinTone, $hairStyle, $hairColor, $outfit, $archetype,
            $startCash, $startBank, $startDistrict, $startCred, $startIQ, $startEdu, $primaryCar
        ]);
        $charId = (int)$pdo->lastInsertId();

        if ($primaryCar) {
            $stmtVeh = $pdo->prepare("INSERT INTO character_vehicles (character_id, vehicle_id) VALUES (?, ?)");
            $stmtVeh->execute([$charId, $primaryCar]);
        }

        logActivity($charId, 'origin', $originMessage, $startCash, 100, 100);

        $pdo->commit();

        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;

        jsonResponse([
            'success' => true,
            'message' => 'Character created! Welcome to Abuja Life.',
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

    $archetypes = ['hustler', 'middle', 'rich'];
    $selectedArchetype = $archetypes[array_rand($archetypes)];
    $skinTones = ['#3d2314', '#593822', '#704225', '#8d5524', '#c68642'];
    $skin = $skinTones[array_rand($skinTones)];
    $hairStyles = ['fade', 'afro', 'dreads', 'cornrows', 'buzz'];
    $hair = $hairStyles[array_rand($hairStyles)];
    $outfits = ['street', 'techie', 'corporate', 'agbada'];
    $outfit = $outfits[array_rand($outfits)];

    $startCash = 15000.00;
    $startBank = 5000.00;
    $startDistrict = 'Kubwa';
    $startCred = 15;
    $startIQ = 20;
    $startEdu = 'SSCE';
    $primaryCar = null;
    $originMessage = "Guest grinder $fullName started in Kubwa from scratch!";

    if ($selectedArchetype === 'rich') {
        $startCash = 1500000.00;
        $startBank = 8500000.00;
        $startDistrict = 'Maitama';
        $startCred = 45;
        $startIQ = 45;
        $startEdu = 'BSc';
        $primaryCar = 4;
        $originMessage = "Guest high-roller $fullName was born into wealth in Maitama!";
    } elseif ($selectedArchetype === 'middle') {
        $startCash = 120000.00;
        $startBank = 350000.00;
        $startDistrict = 'Gwarinpa';
        $startCred = 25;
        $startIQ = 35;
        $startEdu = 'BSc';
        $primaryCar = 2;
        $originMessage = "Guest professional $fullName started with middle-class civil servant roots in Gwarinpa.";
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        $userId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO characters (
                user_id, full_name, gender, skin_tone, hair_style, hair_color, outfit, archetype,
                cash, bank, district, street_cred, intelligence, education_level, primary_vehicle_id
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, $fullName, $gender, $skin, $hair, '#111111', $outfit, $selectedArchetype,
            $startCash, $startBank, $startDistrict, $startCred, $startIQ, $startEdu, $primaryCar
        ]);
        $charId = (int)$pdo->lastInsertId();

        if ($primaryCar) {
            $stmtVeh = $pdo->prepare("INSERT INTO character_vehicles (character_id, vehicle_id) VALUES (?, ?)");
            $stmtVeh->execute([$charId, $primaryCar]);
        }

        logActivity($charId, 'origin', $originMessage, $startCash, 100, 100);

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
