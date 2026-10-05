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
        SET energy = max_energy, happiness = CASE WHEN happiness + 5 > 100 THEN 100 ELSE happiness + 5 END
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
        SET cash = cash - ?, health = 100, happiness = CASE WHEN happiness + 10 > 100 THEN 100 ELSE happiness + 10 END
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

if ($action === 'morning_routine') {
    $cost = 2000.00;
    if ((float)$char['cash'] < $cost) {
        $cost = 0.00; // manage empty stomach
    }

    $newEnergy = min(100, (int)$char['energy'] + 15);
    $newHappiness = min(100, (int)$char['happiness'] + 5);

    $stmt = $pdo->prepare("
        UPDATE characters 
        SET cash = cash - ?, energy = ?, happiness = ?, time_of_day = 'Morning' 
        WHERE id = ?
    ");
    $stmt->execute([$cost, $newEnergy, $newHappiness, $char['id']]);

    logActivity($char['id'], 'morning', "Woke up early in {$char['district']}! Hot shower & Nigerian breakfast (yam & eggs).", -$cost, 15, 5);

    jsonResponse([
        'success' => true,
        'message' => "Good morning Abuja! You had breakfast and got dressed. Ready for the day's hustle.",
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'start_shift') {
    if (!$char['current_job_id']) {
        jsonResponse(['success' => false, 'error' => "You don't have a job yet! Browse available careers or run a side hustle."], 400);
    }

    $stmtJob = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmtJob->execute([$char['current_job_id']]);
    $job = $stmtJob->fetch();

    if (!$job) {
        jsonResponse(['success' => false, 'error' => 'Job not found.'], 404);
    }

    $energyReq = (int)$job['energy_cost'];
    if ((int)$char['energy'] < $energyReq) {
        jsonResponse(['success' => false, 'error' => "You are too tired ({$energyReq}% energy needed). Rest or take a coffee before your shift."], 400);
    }

    jsonResponse([
        'success' => true,
        'job' => $job,
        'shift_duration_seconds' => 45,
        'energy_cost' => $energyReq,
        'base_salary' => (float)$job['daily_salary']
    ]);
}

if ($action === 'finish_shift') {
    if (!$char['current_job_id']) {
        jsonResponse(['success' => false, 'error' => "No active job found."], 400);
    }

    $stmtJob = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmtJob->execute([$char['current_job_id']]);
    $job = $stmtJob->fetch();

    if (!$job) {
        jsonResponse(['success' => false, 'error' => 'Job not found.'], 404);
    }

    $energyReq = (int)$job['energy_cost'];
    $tips = max(0, (float)($_POST['tips'] ?? 0));
    $bonuses = max(0, (float)($_POST['bonuses'] ?? 0));
    $penalties = max(0, (float)($_POST['penalties'] ?? 0));

    $baseSalary = (float)$job['daily_salary'];
    $finalPay = max(0, $baseSalary + $tips + $bonuses - $penalties);

    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET cash = cash + ?, 
            energy = CASE WHEN energy - ? < 0 THEN 0 ELSE energy - ? END, 
            time_of_day = 'Evening', 
            intelligence = intelligence + 1,
            street_cred = street_cred + 2,
            happiness = CASE WHEN happiness + 5 > 100 THEN 100 ELSE happiness + 5 END
        WHERE id = ?
    ");
    $stmtUpdate->execute([$finalPay, $energyReq, $energyReq, $char['id']]);

    $logMsg = "Completed full 8-hour workday as {$job['title']}. Salary: " . formatNaira($baseSalary);
    if ($tips > 0) $logMsg .= " + Tips: " . formatNaira($tips);
    if ($bonuses > 0) $logMsg .= " + Performance: " . formatNaira($bonuses);
    if ($penalties > 0) $logMsg .= " - Deductions: " . formatNaira($penalties);

    logActivity($char['id'], 'work_shift', $logMsg, $finalPay, -$energyReq, 5);

    jsonResponse([
        'success' => true,
        'message' => "Shift officially closed! Net pay of " . formatNaira($finalPay) . " credited to your wallet.",
        'final_pay' => $finalPay,
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'abandon_shift') {
    if (!$char['current_job_id']) {
        jsonResponse(['success' => false, 'error' => "No active job found."], 400);
    }

    $stmtJob = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmtJob->execute([$char['current_job_id']]);
    $job = $stmtJob->fetch();

    $progress = min(100, max(0, (float)($_POST['progress'] ?? 20)));
    $partialSalary = ($progress >= 50) ? round(((float)$job['daily_salary'] * ($progress / 100)) * 0.4, 2) : 0;
    $energyCost = round((int)$job['energy_cost'] * ($progress / 100));

    // Severe penalty for walking out early
    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET cash = cash + ?, 
            energy = CASE WHEN energy - ? < 0 THEN 0 ELSE energy - ? END, 
            street_cred = CASE WHEN street_cred - 12 < 0 THEN 0 ELSE street_cred - 12 END,
            happiness = CASE WHEN happiness - 15 < 0 THEN 0 ELSE happiness - 15 END,
            time_of_day = 'Evening'
        WHERE id = ?
    ");
    $stmtUpdate->execute([$partialSalary, $energyCost, $energyCost, $char['id']]);

    $msg = "You sneaked out of work at {$progress}% of shift! Oga caught you leaving: 'You dey leave desk?!' Docked salary and issued a formal query! -12 Street Cred.";
    logActivity($char['id'], 'work_abandon', $msg, $partialSalary, -$energyCost, -15);

    jsonResponse([
        'success' => true,
        'message' => $msg,
        'partial_pay' => $partialSalary,
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'go_to_work') {
    if (!$char['current_job_id']) {
        jsonResponse(['success' => false, 'error' => "You don't have a job yet! Browse available careers or run a side hustle."], 400);
    }

    $stmtJob = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmtJob->execute([$char['current_job_id']]);
    $job = $stmtJob->fetch();

    if (!$job) {
        jsonResponse(['success' => false, 'error' => 'Job not found.'], 404);
    }

    $energyReq = (int)$job['energy_cost'];
    if ((int)$char['energy'] < $energyReq) {
        jsonResponse(['success' => false, 'error' => "You are too tired ({$energyReq}% energy needed). Rest or take a coffee."], 400);
    }

    $salary = (float)$job['daily_salary'];
    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET cash = cash + ?, energy = energy - ?, time_of_day = 'Evening', intelligence = intelligence + 1
        WHERE id = ?
    ");
    $stmtUpdate->execute([$salary, $energyReq, $char['id']]);

    logActivity($char['id'], 'work', "Commuted to workplace and finished shift as {$job['title']}. Earned " . formatNaira($salary) . ".", $salary, -$energyReq, 0);

    jsonResponse([
        'success' => true,
        'message' => "Completed your shift at {$job['title']}! Received daily pay of " . formatNaira($salary) . ".",
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'update_looks') {
    $rawAvatarConfig = $_POST['avatar_config'] ?? '';
    $decoded = json_decode($rawAvatarConfig, true);
    $avatarConfig = is_array($decoded) ? json_encode($decoded) : null;
    $skin = cleanInput($_POST['skin_tone'] ?? $char['skin_tone'] ?? '#704225');
    $hair = cleanInput($_POST['hair_style'] ?? $char['hair_style'] ?? 'fade');
    $hairColor = cleanInput($_POST['hair_color'] ?? $char['hair_color'] ?? '#111111');
    $outfit = cleanInput($_POST['outfit'] ?? $char['outfit'] ?? 'casual');

    if ($avatarConfig) {
        $stmt = $pdo->prepare("
            UPDATE characters 
            SET avatar = ?, skin_tone = ?, hair_style = ?, hair_color = ?, outfit = ? 
            WHERE id = ?
        ");
        $stmt->execute([$avatarConfig, $skin, $hair, $hairColor, $outfit, $char['id']]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE characters 
            SET skin_tone = ?, hair_style = ?, hair_color = ?, outfit = ? 
            WHERE id = ?
        ");
        $stmt->execute([$skin, $hair, $hairColor, $outfit, $char['id']]);
    }

    logActivity($char['id'], 'wardrobe', "Refreshed character 3D appearance & wardrobe style.", 0, 0, 5);

    jsonResponse([
        'success' => true,
        'message' => 'Your look has been refreshed in 3D!',
        'character' => getUserCharacter($userId)
    ]);
}

// ----------------------------------------------------
// RESIDENCE & HOUSE MANAGEMENT
// ----------------------------------------------------
if ($action === 'enter_residence') {
    $prop = null;
    if (!empty($char['primary_property_id'])) {
        $stmtP = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
        $stmtP->execute([$char['primary_property_id']]);
        $prop = $stmtP->fetch();
    }

    $homeName = $prop ? $prop['name'] : 'Face-Me-I-Face-You Compound';
    $district = $prop ? $prop['district'] : ($char['district'] ?? 'Kubwa');
    $tier = $prop ? ($prop['type'] ?? 'Serviced Apartment') : 'Grassroots Tenement';
    $hasPool = in_array($district, ['Maitama', 'Asokoro', 'Wuse 2']) && !empty($prop);
    $hasBalcony = !empty($prop);

    $homeState = !empty($char['home_state']) ? json_decode($char['home_state'], true) : [];
    if (!is_array($homeState) || empty($homeState)) {
        $homeState = [
            'power_mode' => 'nepa',
            'inverter_battery' => 85,
            'gen_fuel_liters' => 6,
            'is_borehole_running' => true,
            'cleanliness' => 90
        ];
    }

    jsonResponse([
        'success' => true,
        'home_name' => $homeName,
        'district' => $district,
        'tier' => $tier,
        'has_pool' => $hasPool,
        'has_balcony' => $hasBalcony,
        'home_state' => $homeState,
        'character' => $char
    ]);
}

if ($action === 'manage_residence') {
    $subAction = cleanInput($_POST['sub_action'] ?? 'rest');
    $homeState = !empty($char['home_state']) ? json_decode($char['home_state'], true) : [];
    if (!is_array($homeState) || empty($homeState)) {
        $homeState = [
            'power_mode' => 'nepa',
            'inverter_battery' => 85,
            'gen_fuel_liters' => 6,
            'is_borehole_running' => true,
            'cleanliness' => 90
        ];
    }

    $msg = '';
    $deltas = ['cash' => 0, 'energy' => 0, 'happiness' => 0, 'health' => 0, 'cred' => 0];

    if ($subAction === 'rest') {
        $deltas['energy'] = 40;
        $deltas['health'] = 15;
        $deltas['happiness'] = 10;
        $msg = "You laid down to rest in your bedroom. Energy restored (+40%) and fatigue washed away.";
    } elseif ($subAction === 'manage_power') {
        $mode = cleanInput($_POST['power_mode'] ?? 'nepa');
        if ($mode === 'generator') {
            if ($homeState['gen_fuel_liters'] <= 0) {
                jsonResponse(['success' => false, 'error' => 'Generator fuel tank is dry! Buy ₦5,000 petrol first.'], 400);
            }
            $homeState['gen_fuel_liters'] = max(0, $homeState['gen_fuel_liters'] - 2);
            $homeState['power_mode'] = 'generator';
            $deltas['happiness'] = 10;
            $msg = "Pulled the generator cord! Mikano roaring smoothly. 2 Liters consumed. AC & appliances powered!";
        } elseif ($mode === 'inverter') {
            if ($homeState['inverter_battery'] < 10) {
                jsonResponse(['success' => false, 'error' => 'Inverter battery low! Connect to NEPA or gen to charge.'], 400);
            }
            $homeState['power_mode'] = 'inverter';
            $homeState['inverter_battery'] = max(0, $homeState['inverter_battery'] - 10);
            $deltas['happiness'] = 15;
            $msg = "Switched to Pure Sine-Wave Inverter. Whispering quiet power and zero generator smoke!";
        } elseif ($mode === 'buy_fuel') {
            $cost = 5000;
            if ((float)$char['cash'] < $cost) {
                jsonResponse(['success' => false, 'error' => 'You need ₦5,000 cash for 10 Liters of petrol!'], 400);
            }
            $deltas['cash'] = -$cost;
            $homeState['gen_fuel_liters'] = min(30, $homeState['gen_fuel_liters'] + 10);
            $msg = "Purchased 10 Liters of fuel from nearby filling station. Generator tank replenished!";
        } else {
            $homeState['power_mode'] = 'nepa';
            $homeState['inverter_battery'] = min(100, $homeState['inverter_battery'] + 15);
            $msg = "Connected to AEDC / NEPA grid. Inverter batteries charging.";
        }
    } elseif ($subAction === 'compound_meet') {
        $deltas['cred'] = 5;
        $deltas['happiness'] = 5;
        $msg = "Attended compound / estate security meeting. Settled water pump roster and earned neighbors' respect!";
    } elseif ($subAction === 'pool_balcony') {
        $deltas['happiness'] = 30;
        $deltas['energy'] = 10;
        $msg = "Relaxed on the terrace with a chilled drink overlooking the Abuja skyline. Peace of mind restored!";
    }

    $newCash = max(0, (float)$char['cash'] + $deltas['cash']);
    $newEnergy = min(100, max(0, (int)$char['energy'] + $deltas['energy']));
    $newHealth = min(100, max(0, (int)$char['health'] + $deltas['health']));
    $newHappy  = min(100, max(0, (int)$char['happiness'] + $deltas['happiness']));
    $newCred   = min(200, max(0, (int)$char['street_cred'] + $deltas['cred']));

    $stmt = $pdo->prepare("
        UPDATE characters 
        SET cash = ?, energy = ?, health = ?, happiness = ?, street_cred = ?, home_state = ?
        WHERE id = ?
    ");
    $stmt->execute([$newCash, $newEnergy, $newHealth, $newHappy, $newCred, json_encode($homeState), $char['id']]);

    logActivity($char['id'], 'home_' . $subAction, $msg, $deltas['cash'], $deltas['energy'], $deltas['happiness']);

    jsonResponse([
        'success' => true,
        'message' => $msg,
        'home_state' => $homeState,
        'character' => getUserCharacter($userId)
    ]);
}

// ----------------------------------------------------
// COMMUTE FROM RESIDENCE TO WORKPLACE
// ----------------------------------------------------
if ($action === 'commute_to_work') {
    $mode = cleanInput($_POST['mode'] ?? 'danfo');

    $costs = [
        'walk' => ['fare' => 0, 'energy' => -15, 'desc' => 'trekked through the morning heat'],
        'okada' => ['fare' => 400, 'energy' => -5, 'desc' => 'hopped on an Okada dodging morning traffic'],
        'danfo' => ['fare' => 500, 'energy' => -8, 'desc' => 'entered a yellow Danfo bus to Central Area'],
        'bolt' => ['fare' => 2500, 'energy' => 0, 'desc' => 'took a smooth air-conditioned Bolt cab'],
        'car' => ['fare' => 0, 'energy' => -2, 'desc' => 'drove your personal vehicle through the gates']
    ];

    if (!isset($costs[$mode])) $mode = 'danfo';
    $spec = $costs[$mode];

    if ($mode === 'car' && empty($char['primary_vehicle_id'])) {
        jsonResponse(['success' => false, 'error' => 'You do not have a car in your garage yet! Take Danfo or Okada.'], 400);
    }

    if ((float)$char['cash'] < $spec['fare']) {
        jsonResponse(['success' => false, 'error' => "Insufficient cash for commute! Fare is " . formatNaira($spec['fare']) . "."], 400);
    }

    $newCash = max(0, (float)$char['cash'] - $spec['fare']);
    $newEnergy = max(5, (int)$char['energy'] + $spec['energy']);

    $stmt = $pdo->prepare("UPDATE characters SET cash = ?, energy = ? WHERE id = ?");
    $stmt->execute([$newCash, $newEnergy, $char['id']]);

    $gateSalute = "The estate security guard opened the gate with a salute: 'Oga safe journey!'. You {$spec['desc']} and arrived at your office building.";
    logActivity($char['id'], 'commute', $gateSalute, -$spec['fare'], $spec['energy'], 0);

    jsonResponse([
        'success' => true,
        'message' => $gateSalute,
        'character' => getUserCharacter($userId)
    ]);
}

// ----------------------------------------------------
// START A NEW LIFE (CHARACTER REBIRTH WITHOUT LOGOUT)
// ----------------------------------------------------
if ($action === 'start_new_life') {
    $newName = cleanInput($_POST['new_name'] ?? '');
    $newGender = cleanInput($_POST['gender'] ?? 'Male');
    $newArchetype = cleanInput($_POST['archetype'] ?? 'hustler');
    $newDistrict = cleanInput($_POST['district'] ?? 'Kubwa');

    if (empty($newName)) {
        jsonResponse(['success' => false, 'error' => 'Please choose a name for your new life.'], 400);
    }

    // Default starter stats based on archetype
    $startCash = 15000.00;
    $startBank = 5000.00;
    $startCred = 15;
    $startIQ = 20;
    $startCar = null;

    if ($newArchetype === 'rich') {
        $startCash = 1500000.00;
        $startBank = 8500000.00;
        $startDistrict = 'Maitama';
        $startCred = 45;
        $startIQ = 45;
        $startCar = 4;
    } elseif ($newArchetype === 'middle') {
        $startCash = 120000.00;
        $startBank = 350000.00;
        $startDistrict = 'Gwarinpa';
        $startCred = 25;
        $startIQ = 35;
        $startCar = 2;
    }

    $stmtReset = $pdo->prepare("
        UPDATE characters
        SET full_name = ?, gender = ?, archetype = ?, district = ?,
            cash = ?, bank = ?, loan_balance = 0, street_cred = ?, intelligence = ?,
            energy = 100, health = 100, happiness = 100, days_lived = 1, age = 18,
            time_of_day = 'Morning', current_job_id = NULL, primary_property_id = NULL,
            primary_vehicle_id = ?, active_shift = NULL, home_state = NULL
        WHERE id = ?
    ");
    $stmtReset->execute([
        $newName, $newGender, $newArchetype, $newDistrict,
        $startCash, $startBank, $startCred, $startIQ,
        $startCar, $char['id']
    ]);

    // Clear old properties and vehicles
    $pdo->prepare("DELETE FROM character_properties WHERE character_id = ?")->execute([$char['id']]);
    $pdo->prepare("DELETE FROM character_vehicles WHERE character_id = ?")->execute([$char['id']]);
    if ($startCar) {
        $pdo->prepare("INSERT INTO character_vehicles (character_id, vehicle_id) VALUES (?, ?)")->execute([$char['id'], $startCar]);
    }

    $rebirthMsg = "Started a brand new life in Abuja as $newName ($newArchetype) in $newDistrict! Old slate wiped clean.";
    logActivity($char['id'], 'rebirth', $rebirthMsg, $startCash, 100, 100);

    jsonResponse([
        'success' => true,
        'message' => "Welcome to your new life, $newName! Clean start unlocked.",
        'character' => getUserCharacter($userId)
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);

