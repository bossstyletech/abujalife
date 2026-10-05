<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();
$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? 'list');
$pdo = getDbConnection();
$char = getUserCharacter($userId);

if (!$char) {
    jsonResponse(['success' => false, 'error' => 'Character not found.'], 404);
}

$activities = [
    'suya' => [
        'id' => 'suya',
        'name' => 'Millennium Park Suya & Chilled Drinks',
        'desc' => 'Enjoy hot spicy beef and chicken suya on open lawns under Maitama trees.',
        'cost' => 5000,
        'energy' => 10,
        'hap_gain' => 18,
        'health_gain' => 2,
        'cred_gain' => 1,
        'karma_gain' => 2,
        'icon' => 'fa-fire-burner'
    ],
    'jabi_lake' => [
        'id' => 'jabi_lake',
        'name' => 'Jabi Lake Mall Cinema & Boat Cruise',
        'desc' => 'Watch the latest Nollywood/Hollywood blockbuster and take a speedboat ride across the lake.',
        'cost' => 15000,
        'energy' => 15,
        'hap_gain' => 30,
        'health_gain' => 0,
        'cred_gain' => 4,
        'karma_gain' => 0,
        'icon' => 'fa-water'
    ],
    'wuse_club' => [
        'id' => 'wuse_club',
        'name' => 'Wuse 2 VIP Clubbing & Bottle Service',
        'desc' => 'Party all night with top Abuja high-rollers, DJs, and sparkler bottle trains.',
        'cost' => 50000,
        'energy' => 30,
        'hap_gain' => 45,
        'health_gain' => -5,
        'cred_gain' => 15,
        'karma_gain' => -2,
        'icon' => 'fa-champagne-glasses'
    ],
    'gym' => [
        'id' => 'gym',
        'name' => 'Maitama Elite Gym & Spa Session',
        'desc' => 'Weightlifting, cardio conditioning, steam room, and protein shakes.',
        'cost' => 8000,
        'energy' => 20,
        'hap_gain' => 15,
        'health_gain' => 15,
        'cred_gain' => 2,
        'karma_gain' => 0,
        'icon' => 'fa-dumbbell'
    ],
    'owambe' => [
        'id' => 'owambe',
        'name' => 'Abuja Owambe Saturday Party',
        'desc' => 'Don crisp Agbada / gele, dance to live Fuji/Highlife band, and network with political elites.',
        'cost' => 25000,
        'energy' => 20,
        'hap_gain' => 35,
        'health_gain' => 0,
        'cred_gain' => 10,
        'karma_gain' => 5,
        'icon' => 'fa-music'
    ],
    'charity' => [
        'id' => 'charity',
        'name' => 'Abuja IDP Camp / Street Charity Outreach',
        'desc' => 'Donate food, bags of rice, and relief packages to families in need around Area 1 and Kuchingoro.',
        'cost' => 12000,
        'energy' => 15,
        'hap_gain' => 25,
        'health_gain' => 0,
        'cred_gain' => 5,
        'karma_gain' => 20,
        'icon' => 'fa-hand-holding-heart'
    ]
];

if ($action === 'list') {
    jsonResponse([
        'success' => true,
        'activities' => array_values($activities)
    ]);
}

if ($action === 'perform') {
    $actId = cleanInput($_POST['activity_id'] ?? '');
    if (!isset($activities[$actId])) {
        jsonResponse(['success' => false, 'error' => 'Invalid activity.'], 400);
    }

    $act = $activities[$actId];
    if ((float)$char['cash'] < (float)$act['cost']) {
        jsonResponse(['success' => false, 'error' => "You need at least " . formatNaira($act['cost']) . " in cash for this activity."], 400);
    }

    if ((int)$char['energy'] < $act['energy']) {
        jsonResponse(['success' => false, 'error' => "Not enough energy ({$act['energy']}% required). Rest or take a nap."], 400);
    }

    $newCash = (float)$char['cash'] - (float)$act['cost'];
    $newEnergy = (int)$char['energy'] - $act['energy'];
    $newHappiness = min(100, max(0, (int)$char['happiness'] + $act['hap_gain']));
    $newHealth = min(100, max(5, (int)$char['health'] + $act['health_gain']));
    $newCred = (int)$char['street_cred'] + $act['cred_gain'];
    $newKarma = (int)$char['karma'] + $act['karma_gain'];

    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET cash = ?, energy = ?, happiness = ?, health = ?, street_cred = ?, karma = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([$newCash, $newEnergy, $newHappiness, $newHealth, $newCred, $newKarma, $char['id']]);

    logActivity($char['id'], 'lifestyle', "Attended {$act['name']}. Happiness +{$act['hap_gain']}!", -$act['cost'], -$act['energy'], $act['hap_gain']);

    jsonResponse([
        'success' => true,
        'message' => "You attended {$act['name']}! Felt amazing and refreshed.",
        'character' => getUserCharacter($userId)
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
