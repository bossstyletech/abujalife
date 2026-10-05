<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();
$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? 'random');
$pdo = getDbConnection();
$char = getUserCharacter($userId);

if (!$char) {
    jsonResponse(['success' => false, 'error' => 'Character not found.'], 404);
}

if ($action === 'random') {
    // 40% probability of triggering a random life event when checked
    $chance = mt_rand(1, 100);
    if ($chance > 45) {
        jsonResponse(['success' => true, 'has_event' => false]);
    }

    $stmt = $pdo->query("SELECT * FROM random_events ORDER BY RAND() LIMIT 1");
    $event = $stmt->fetch();

    if (!$event) {
        jsonResponse(['success' => true, 'has_event' => false]);
    }

    jsonResponse([
        'success' => true,
        'has_event' => true,
        'event' => $event
    ]);
}

if ($action === 'resolve') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $choice = cleanInput($_POST['choice'] ?? 'a'); // 'a' or 'b'

    $stmt = $pdo->prepare("SELECT * FROM random_events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event) {
        jsonResponse(['success' => false, 'error' => 'Event not found.'], 404);
    }

    if ($choice === 'a') {
        $cashMod = (float)$event['option_a_cash'];
        $healthMod = (int)$event['option_a_health'];
        $hapMod = (int)$event['option_a_happiness'];
        $credMod = (int)$event['option_a_cred'];
        $msg = $event['option_a_msg'];
    } else {
        $cashMod = (float)$event['option_b_cash'];
        $healthMod = (int)$event['option_b_health'];
        $hapMod = (int)$event['option_b_happiness'];
        $credMod = (int)$event['option_b_cred'];
        $msg = $event['option_b_msg'];
    }

    // Apply adjustments
    $newCash = max(0, (float)$char['cash'] + $cashMod);
    $newHealth = min(100, max(5, (int)$char['health'] + $healthMod));
    $newHappiness = min(100, max(0, (int)$char['happiness'] + $hapMod));
    $newCred = max(0, (int)$char['street_cred'] + $credMod);

    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET cash = ?, health = ?, happiness = ?, street_cred = ? 
        WHERE id = ?
    ");
    $stmtUpdate->execute([$newCash, $newHealth, $newHappiness, $newCred, $char['id']]);

    logActivity($char['id'], 'random_event', "Life Event: {$event['title']} -> $msg", $cashMod, 0, $hapMod);

    jsonResponse([
        'success' => true,
        'message' => $msg,
        'outcome' => [
            'cash' => $cashMod,
            'health' => $healthMod,
            'happiness' => $hapMod,
            'cred' => $credMod
        ],
        'character' => getUserCharacter($userId)
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
