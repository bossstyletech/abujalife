<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();
$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? 'list');
$pdo = getDbConnection();
$char = getUserCharacter($userId);

if (!$char) {
    jsonResponse(['success' => false, 'error' => 'Character not found.'], 404);
}

if ($action === 'list') {
    $stmt = $pdo->query("SELECT * FROM education_courses ORDER BY cost ASC");
    $courses = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'current_education' => $char['education_level'],
        'intelligence' => (int)$char['intelligence'],
        'courses' => $courses
    ]);
}

if ($action === 'enroll') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM education_courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();

    if (!$course) {
        jsonResponse(['success' => false, 'error' => 'Education program not found.'], 404);
    }

    $cost = (float)$course['cost'];
    $energyCost = (int)$course['energy_cost'];

    if ((float)$char['cash'] < $cost) {
        jsonResponse(['success' => false, 'error' => "Tuition fee for {$course['name']} is " . formatNaira($cost) . ". You do not have enough cash."], 400);
    }

    if ((int)$char['energy'] < $energyCost) {
        jsonResponse(['success' => false, 'error' => "You do not have enough mental energy ({$energyCost}% required) to attend lectures today."], 400);
    }

    // Update character intelligence and education level
    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET cash = cash - ?, 
            energy = energy - ?, 
            intelligence = intelligence + ?, 
            education_level = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([
        $cost, 
        $energyCost, 
        (int)$course['intelligence_gain'], 
        $course['qualification'], 
        $char['id']
    ]);

    logActivity(
        $char['id'], 
        'education', 
        "Enrolled and graduated from {$course['name']} at {$course['institution']}! Qualification updated to {$course['qualification']}.", 
        -$cost, 
        -$energyCost, 
        10
    );

    jsonResponse([
        'success' => true,
        'message' => "Congratulations! You completed {$course['name']}. Your intelligence increased by +{$course['intelligence_gain']}!",
        'character' => getUserCharacter($userId)
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
