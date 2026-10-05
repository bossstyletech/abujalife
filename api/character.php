<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();
$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? 'get');
$pdo = getDbConnection();
$char = getUserCharacter($userId);

if (!$char && $action !== 'create') {
    jsonResponse(['success' => false, 'error' => 'No character found for this user.'], 404);
}

if ($action === 'get') {
    // Calculate total net worth (Cash + Bank + Properties market value + Cars value - Loans)
    $stmtProp = $pdo->prepare("
        SELECT COALESCE(SUM(p.price), 0) AS total_realestate, COUNT(cp.id) AS property_count
        FROM character_properties cp
        JOIN properties p ON cp.property_id = p.id
        WHERE cp.character_id = ?
    ");
    $stmtProp->execute([$char['id']]);
    $propData = $stmtProp->fetch();

    $stmtVeh = $pdo->prepare("
        SELECT COALESCE(SUM(v.price), 0) AS total_vehicles, COUNT(cv.id) AS vehicle_count
        FROM character_vehicles cv
        JOIN vehicles v ON cv.vehicle_id = v.id
        WHERE cv.character_id = ?
    ");
    $stmtVeh->execute([$char['id']]);
    $vehData = $stmtVeh->fetch();

    $netWorth = (float)$char['cash'] + (float)$char['bank'] + (float)$propData['total_realestate'] + (float)$vehData['total_vehicles'] - (float)$char['loan_balance'];

    // Fetch recent 15 activities
    $stmtLogs = $pdo->prepare("
        SELECT * FROM activity_logs 
        WHERE character_id = ? 
        ORDER BY id DESC LIMIT 15
    ");
    $stmtLogs->execute([$char['id']]);
    $logs = $stmtLogs->fetchAll();

    jsonResponse([
        'success' => true,
        'character' => $char,
        'net_worth' => $netWorth,
        'property_count' => (int)$propData['property_count'],
        'vehicle_count' => (int)$vehData['vehicle_count'],
        'districts' => ABUJA_DISTRICTS,
        'logs' => $logs
    ]);
}

if ($action === 'advance_day') {
    // 1. Calculate rental income from rented out properties
    $stmtRent = $pdo->prepare("
        SELECT COALESCE(SUM(p.daily_rent_yield), 0) AS total_rent
        FROM character_properties cp
        JOIN properties p ON cp.property_id = p.id
        WHERE cp.character_id = ? AND cp.is_rented_out = 1
    ");
    $stmtRent->execute([$char['id']]);
    $rentIncome = (float)$stmtRent->fetch()['total_rent'];

    // 2. Calculate daily vehicle maintenance upkeep
    $stmtUpkeep = $pdo->prepare("
        SELECT COALESCE(SUM(v.daily_upkeep), 0) AS total_upkeep
        FROM character_vehicles cv
        JOIN vehicles v ON cv.vehicle_id = v.id
        WHERE cv.character_id = ?
    ");
    $stmtUpkeep->execute([$char['id']]);
    $vehicleUpkeep = (float)$stmtUpkeep->fetch()['total_upkeep'];

    // 3. Bank interest (0.05% daily on savings)
    $bankInterest = round((float)$char['bank'] * 0.0005, 2);

    // 4. Loan interest (0.2% daily if loan exists)
    $loanInterest = round((float)$char['loan_balance'] * 0.002, 2);

    // Net cash changes
    $newCash = (float)$char['cash'] + $rentIncome - $vehicleUpkeep;
    $newBank = (float)$char['bank'] + $bankInterest;
    $newLoan = (float)$char['loan_balance'] + $loanInterest;

    // Advance days_lived, energy recharge by 20, natural age increase every 30 days
    $daysLived = (int)$char['days_lived'] + 1;
    $newAge = (int)$char['age'] + ($daysLived % 30 === 0 ? 1 : 0);
    $newEnergy = min((int)$char['max_energy'], (int)$char['energy'] + 25);

    // Jail decrement if jailed
    $newJail = max(0, (int)$char['jail_days'] - 1);

    // Slight natural health & happiness variation
    $newHealth = max(10, min(100, (int)$char['health'] - mt_rand(0, 2)));
    $newHappiness = max(10, min(100, (int)$char['happiness'] + ($char['primary_property_id'] ? 2 : -1)));

    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET days_lived = ?, age = ?, energy = ?, health = ?, happiness = ?, 
            cash = ?, bank = ?, loan_balance = ?, jail_days = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([
        $daysLived, $newAge, $newEnergy, $newHealth, $newHappiness,
        $newCash, $newBank, $newLoan, $newJail, $char['id']
    ]);

    $summaryLog = "Day $daysLived in Abuja: ";
    if ($rentIncome > 0) $summaryLog .= "Earned " . formatNaira($rentIncome) . " property rent. ";
    if ($vehicleUpkeep > 0) $summaryLog .= "Paid " . formatNaira($vehicleUpkeep) . " fleet upkeep. ";
    if ($bankInterest > 0) $summaryLog .= "Bank interest +" . formatNaira($bankInterest) . ". ";

    logActivity($char['id'], 'day_pass', $summaryLog, ($rentIncome - $vehicleUpkeep), 25, 0);

    jsonResponse([
        'success' => true,
        'message' => "Advanced to Day $daysLived! $summaryLog",
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'sleep') {
    // Rest full energy
    $stmt = $pdo->prepare("
        UPDATE characters 
        SET energy = max_energy, happiness = LEAST(100, happiness + 5)
        WHERE id = ?
    ");
    $stmt->execute([$char['id']]);

    logActivity($char['id'], 'rest', "You took a restful sleep in {$char['district']} and fully recharged your energy.", 0, 100, 5);

    jsonResponse([
        'success' => true,
        'message' => 'You rested well! Energy restored to 100%.',
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'hospital') {
    $cost = 15000.00;
    if ((float)$char['cash'] < $cost) {
        jsonResponse(['success' => false, 'error' => "Treatment at National Hospital Abuja costs " . formatNaira($cost) . ". Insufficient cash."], 400);
    }

    $stmt = $pdo->prepare("
        UPDATE characters 
        SET cash = cash - ?, health = 100, happiness = LEAST(100, happiness + 10)
        WHERE id = ?
    ");
    $stmt->execute([$cost, $char['id']]);

    logActivity($char['id'], 'hospital', "VIP medical checkup at National Hospital Abuja. Full health restored!", -$cost, 0, 10);

    jsonResponse([
        'success' => true,
        'message' => 'Doctor discharged you with a clean bill of health! Health is at 100%.',
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'relocate') {
    $targetDistrict = cleanInput($_POST['district'] ?? '');
    if (!isset(ABUJA_DISTRICTS[$targetDistrict])) {
        jsonResponse(['success' => false, 'error' => 'Invalid Abuja district.'], 400);
    }

    $info = ABUJA_DISTRICTS[$targetDistrict];
    if ((int)$char['street_cred'] < $info['min_cred']) {
        jsonResponse([
            'success' => false, 
            'error' => "You need at least {$info['min_cred']} Street Cred to move to $targetDistrict. Current: {$char['street_cred']}."
        ], 400);
    }

    $travelCost = (float)$info['travel_cost'];
    if ((float)$char['cash'] < $travelCost) {
        jsonResponse(['success' => false, 'error' => "Relocating to $targetDistrict costs " . formatNaira($travelCost) . " for logistics."], 400);
    }

    $stmt = $pdo->prepare("UPDATE characters SET district = ?, cash = cash - ? WHERE id = ?");
    $stmt->execute([$targetDistrict, $travelCost, $char['id']]);

    logActivity($char['id'], 'travel', "Relocated to $targetDistrict! New neighborhood vibes.", -$travelCost, 0, 5);

    jsonResponse([
        'success' => true,
        'message' => "Welcome to $targetDistrict! {$info['desc']}",
        'character' => getUserCharacter($userId)
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
