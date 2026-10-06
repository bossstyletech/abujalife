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
    $mode = cleanInput($_POST['mode'] ?? 'green_cab');

    $costs = [
        'walk' => ['fare' => 0, 'energy' => -15, 'desc' => 'trekked through the morning breeze'],
        'keke' => ['fare' => 400, 'energy' => -5, 'desc' => 'hopped into a Keke Napep dodging junction traffic'],
        'green_cab' => ['fare' => 800, 'energy' => -3, 'desc' => 'boarded an authentic green-and-white Abuja taxi cab to Central Area'],
        'bolt' => ['fare' => 2200, 'energy' => 0, 'desc' => 'took a smooth air-conditioned Bolt cab on Shehu Shagari Way'],
        'car' => ['fare' => 0, 'energy' => -2, 'desc' => 'drove your personal vehicle smoothly through the estate gates']
    ];

    if (!isset($costs[$mode])) $mode = 'green_cab';
    $spec = $costs[$mode];

    if ($mode === 'car' && empty($char['primary_vehicle_id'])) {
        jsonResponse(['success' => false, 'error' => 'You do not have a car in your garage yet! Take an Abuja Green Cab or Keke.'], 400);
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

// ----------------------------------------------------
// 10. FURNITURE STORE & HOME CUSTOMIZATION
// ----------------------------------------------------
if ($action === 'buy_furniture') {
    $itemId = cleanInput($_POST['item_id'] ?? '');
    
    $catalog = [
        'bed_orthopedic'   => ['name' => 'Orthopedic Luxury Bed', 'price' => 120000, 'energy_boost' => 40, 'icon' => 'fa-bed'],
        'fridge_haier'     => ['name' => 'Thermocool Refrigerator', 'price' => 180000, 'hunger_boost' => 35, 'icon' => 'fa-snowflake'],
        'gas_cooker'       => ['name' => '4-Burner Gas Cooker & Oven', 'price' => 95000, 'hunger_boost' => 50, 'icon' => 'fa-fire-burner'],
        'solar_inverter'   => ['name' => '3.5KVA Solar Inverter & Battery', 'price' => 450000, 'power' => true, 'icon' => 'fa-solar-panel'],
        'mikano_gen'       => ['name' => '3.5KVA Mikano Petrol Gen', 'price' => 150000, 'power' => true, 'icon' => 'fa-bolt'],
        'mac_workstation'  => ['name' => 'M3 Max Studio Workstation Desk', 'price' => 520000, 'hustle_mult' => 1.5, 'icon' => 'fa-laptop-code'],
        'smart_tv'         => ['name' => '65" OLED 4K Smart TV', 'price' => 280000, 'fun_boost' => 40, 'icon' => 'fa-tv'],
        'split_ac'         => ['name' => '2HP Inverter Split AC', 'price' => 210000, 'comfort' => 35, 'icon' => 'fa-wind'],
        'leather_sofa'     => ['name' => 'Italian Leather Sectional Sofa', 'price' => 260000, 'comfort' => 30, 'icon' => 'fa-couch'],
        'water_dispenser'  => ['name' => 'Executive Cold Water Dispenser', 'price' => 65000, 'health_boost' => 20, 'icon' => 'fa-faucet-drip'],
    ];

    if (!isset($catalog[$itemId])) {
        jsonResponse(['success' => false, 'error' => 'Invalid furniture item.'], 400);
    }

    $item = $catalog[$itemId];
    if ((float)$char['cash'] < $item['price']) {
        jsonResponse(['success' => false, 'error' => "Insufficient cash! You need " . formatNaira($item['price']) . " for {$item['name']}."], 400);
    }

    $homeState = !empty($char['home_state']) ? json_decode($char['home_state'], true) : [];
    if (!is_array($homeState)) $homeState = [];
    if (!isset($homeState['furniture']) || !is_array($homeState['furniture'])) {
        $homeState['furniture'] = [];
    }

    if (in_array($itemId, $homeState['furniture'])) {
        jsonResponse(['success' => false, 'error' => "You already own this {$item['name']} in your house!"], 400);
    }

    $homeState['furniture'][] = $itemId;
    $newCash = (float)$char['cash'] - $item['price'];

    $stmt = $pdo->prepare("UPDATE characters SET cash = ?, home_state = ? WHERE id = ?");
    $stmt->execute([$newCash, json_encode($homeState), $char['id']]);

    logActivity($char['id'], 'buy_furniture', "Bought {$item['name']} for " . formatNaira($item['price']) . " to furnish house.", -$item['price'], 0, 15);

    jsonResponse([
        'success' => true,
        'message' => "🎉 Purchased {$item['name']}! It has been placed inside your Abuja residence.",
        'home_state' => $homeState,
        'character' => getUserCharacter($userId)
    ]);
}

// ----------------------------------------------------
// 11. INTERACT WITH HOME FURNITURE & APPLIANCES
// ----------------------------------------------------
if ($action === 'interact_furniture') {
    $itemId = cleanInput($_POST['item_id'] ?? 'bed');
    $subAction = cleanInput($_POST['sub_action'] ?? 'interact');

    $deltas = ['cash' => 0, 'energy' => 0, 'happiness' => 0, 'health' => 0, 'cred' => 0];
    $msg = '';

    if ($itemId === 'bed' || $itemId === 'bed_orthopedic' || $itemId === 'floor_mat') {
        $deltas['energy'] = 50;
        $deltas['health'] = 15;
        $deltas['happiness'] = 10;
        $msg = "😴 You slept peacefully on your bed and woke up energized and refreshed! (+50 Energy, +15 Health)";
    } elseif ($itemId === 'bucket' || $itemId === 'bucket_red' || $itemId === 'bucket_blue') {
        $deltas['energy'] = 15;
        $deltas['health'] = 10;
        $deltas['happiness'] = 15;
        $msg = "🚿 You took a refreshing bath with clean water from your bucket! Hygiene restored, feeling great. (+15 Energy, +15 Happiness)";
    } elseif ($itemId === 'cooler' || $itemId === 'fridge' || $itemId === 'fridge_haier') {
        $deltas['energy'] = 25;
        $deltas['health'] = 10;
        $deltas['happiness'] = 20;
        $msg = "🍱 You opened the cooler and enjoyed delicious Abuja chow! Fullness and mood boosted. (+25 Energy, +20 Happiness)";
    } elseif ($itemId === 'books') {
        $deltas['energy'] = -5;
        $deltas['happiness'] = 10;
        $deltas['cred'] = 2;
        $msg = "📚 You read and studied from your books on the shelf! Intelligence and street smarts boosted (+5 IQ, +2 Street Cred)";
        try {
            $pdo->prepare("UPDATE characters SET intelligence = intelligence + 5 WHERE id = ?")->execute([$char['id']]);
        } catch(Exception $e) {}
    } elseif ($itemId === 'radio') {
        $deltas['happiness'] = 20;
        $deltas['energy'] = 5;
        $msg = "📻 You tuned in to Afrobeats on Wazobia FM! Chilling to sweet tunes in your crib. (+20 Happiness)";
    } elseif ($itemId === 'chair') {
        $deltas['energy'] = 10;
        $deltas['happiness'] = 10;
        $msg = "🪑 You sat down on your chair to relax, chill, and ponder your next big Abuja move. (+10 Happiness)";
    } elseif ($itemId === 'gas_cooker') {
        $deltas['energy'] = -5;
        $deltas['health'] = 15;
        $deltas['happiness'] = 25;
        $msg = "🍳 You cooked a hot plate of smoky Nigerian Party Jollof on your gas cooker! Super delicious.";
    } elseif ($itemId === 'mac_workstation' || $itemId === 'desk' || $itemId === 'laptop') {
        $payout = (float)mt_rand(18000, 36000);
        $deltas['cash'] = $payout;
        $deltas['energy'] = -15;
        $deltas['happiness'] = 10;
        $msg = "💻 Worked freelance engineering and P2P trades from your home desk! Earned " . formatNaira($payout) . ".";
    } elseif ($itemId === 'smart_tv') {
        $deltas['happiness'] = 30;
        $deltas['energy'] = 5;
        $msg = "📺 Relaxed on the sofa watching Premier League live on your 4K Smart TV! High spirits.";
    } elseif ($itemId === 'water_dispenser') {
        $deltas['energy'] = 10;
        $deltas['health'] = 15;
        $msg = "💧 Drank a crisp glass of chilled mineral water from the dispenser. Hydrated and alert!";
    } elseif ($itemId === 'split_ac') {
        $deltas['happiness'] = 25;
        $msg = "❄️ Chilled your apartment down to 18°C. Beating the Abuja heat in total luxury!";
    } else {
        $deltas['happiness'] = 10;
        $msg = "Interacted with your home appliance.";
    }

    $newCash = max(0, (float)$char['cash'] + $deltas['cash']);
    $newEnergy = min(100, max(0, (int)$char['energy'] + $deltas['energy']));
    $newHealth = min(100, max(0, (int)$char['health'] + $deltas['health']));
    $newHappy  = min(100, max(0, (int)$char['happiness'] + $deltas['happiness']));

    $stmt = $pdo->prepare("UPDATE characters SET cash = ?, energy = ?, health = ?, happiness = ? WHERE id = ?");
    $stmt->execute([$newCash, $newEnergy, $newHealth, $newHappy, $char['id']]);

    logActivity($char['id'], 'furniture_use', $msg, $deltas['cash'], $deltas['energy'], $deltas['happiness']);

    jsonResponse([
        'success' => true,
        'message' => $msg,
        'character' => getUserCharacter($userId)
    ]);
}

// ----------------------------------------------------
// 12. TRAVEL TO ABUJA DESTINATION (WALK, GREEN CAB, BOLT)
// ----------------------------------------------------
if ($action === 'travel_destination') {
    $destId = cleanInput($_POST['destination_id'] ?? 'jabi_lake');
    $mode = cleanInput($_POST['travel_mode'] ?? 'taxi'); // walk, taxi (green cab), bolt

    $destNames = [
        'gym'          => 'Maitama Executive Gym',
        'restaurant'   => 'Jabi Lake Grill & Suya Restaurant',
        'banex'        => 'Banex Plaza Tech Hub (Wuse 2)',
        'jabi_lake'    => 'Jabi Lake Waterfront & Boat Club',
        'secretariat'  => 'Federal Secretariat Complex',
        'market'       => 'Wuse Modern Market',
        'cbd_bank'     => 'Central Business District & Corporate Towers',
        'stadium'      => 'Moshood Abiola National Stadium',
        'airport'      => 'Nnamdi Azikiwe International Airport',
        'fraser'       => 'Fraser Suites Presidential Hotel'
    ];

    $destName = $destNames[$destId] ?? 'Central Abuja';
    $cost = 0;
    $energyCost = 0;
    $msg = '';

    if ($mode === 'walk') {
        $cost = 0;
        $energyCost = 15;
        if ((int)$char['energy'] < $energyCost) {
            jsonResponse(['success' => false, 'error' => "You're too exhausted to trek! Rest or take a Green Cab taxi."], 400);
        }
        $msg = "🚶 Walked along the Abuja boulevards to {$destName}. Exercised your legs and enjoyed the view!";
    } elseif ($mode === 'taxi') {
        $cost = 800.00;
        $energyCost = 2;
        if ((float)$char['cash'] < $cost) {
            jsonResponse(['success' => false, 'error' => "You need ₦800 cash for an Abuja Green Cab!"], 400);
        }
        $msg = "🚕 Boarded an authentic green-and-white Abuja taxi cab straight to {$destName} (₦800 fare).";
    } elseif ($mode === 'bolt') {
        $cost = 2200.00;
        $energyCost = 0;
        if ((float)$char['cash'] < $cost) {
            jsonResponse(['success' => false, 'error' => "You need ₦2,200 cash for a Bolt ride!"], 400);
        }
        $msg = "🚗 Chilled in an air-conditioned Bolt ride with smooth music arriving in style at {$destName} (₦2,200).";
    }

    $newCash = max(0, (float)$char['cash'] - $cost);
    $newEnergy = max(0, (int)$char['energy'] - $energyCost);

    $homeState = !empty($char['home_state']) ? json_decode($char['home_state'], true) : [];
    if (!is_array($homeState)) $homeState = [];
    $homeState['current_location'] = $destId;

    $stmt = $pdo->prepare("UPDATE characters SET cash = ?, energy = ?, home_state = ? WHERE id = ?");
    $stmt->execute([$newCash, $newEnergy, json_encode($homeState), $char['id']]);

    logActivity($char['id'], 'travel', $msg, -$cost, -$energyCost, 5);

    jsonResponse([
        'success' => true,
        'message' => $msg,
        'current_location' => $destId,
        'destination_name' => $destName,
        'character' => getUserCharacter($userId)
    ]);
}

// ----------------------------------------------------
// 13. DESTINATION ACTIVITIES (GYM, RESTAURANT, BANEX FLIP, LAKE CRUISE)
// ----------------------------------------------------
if ($action === 'do_destination_activity') {
    $act = cleanInput($_POST['activity'] ?? '');
    $sub = cleanInput($_POST['sub_activity'] ?? '');

    $deltas = ['cash' => 0, 'energy' => 0, 'happiness' => 0, 'health' => 0, 'cred' => 0];
    $msg = '';

    if ($act === 'gym') {
        if ((int)$char['energy'] < 20) {
            jsonResponse(['success' => false, 'error' => "Too tired for workout! Rest in your home first."], 400);
        }
        $gymFee = 2500.00;
        if ((float)$char['cash'] < $gymFee) {
            jsonResponse(['success' => false, 'error' => "Gym session ticket is ₦2,500."], 400);
        }
        $deltas['cash'] = -$gymFee;
        $deltas['energy'] = -20;
        $deltas['health'] = 20;
        $deltas['cred'] = 8;
        $deltas['happiness'] = 15;
        $msg = "💪 Completed intense workout session at Maitama Executive Gym! Muscles pumping, health +20!";
    } elseif ($act === 'restaurant') {
        $meals = [
            'jollof'   => ['name' => 'Smoky Party Jollof & Asun', 'cost' => 3500, 'energy' => 25, 'happy' => 25],
            'tilapia'  => ['name' => 'Grilled Jabi Lake Tilapia & Plantain', 'cost' => 6000, 'energy' => 40, 'happy' => 35],
            'suya'     => ['name' => 'Abuja Special Beef Suya & Masa', 'cost' => 2500, 'energy' => 20, 'happy' => 20],
            'chapman'  => ['name' => 'Chilled Angostura Chapman Cocktail', 'cost' => 2000, 'energy' => 10, 'happy' => 15]
        ];
        $meal = $meals[$sub] ?? $meals['jollof'];
        if ((float)$char['cash'] < $meal['cost']) {
            jsonResponse(['success' => false, 'error' => "You need " . formatNaira($meal['cost']) . " cash for {$meal['name']}."], 400);
        }
        $deltas['cash'] = -$meal['cost'];
        $deltas['energy'] = $meal['energy'];
        $deltas['happiness'] = $meal['happy'];
        $deltas['health'] = 10;
        $msg = "🍲 Savored {$meal['name']} at Jabi Lake restaurant! Hunger crushed and spirits soaring.";
    } elseif ($act === 'banex_flip') {
        // High return gadget flip hustle
        $cost = 50000.00;
        if ((float)$char['cash'] < $cost) {
            jsonResponse(['success' => false, 'error' => "You need ₦50,000 capital to purchase a Banex wholesale gadget lot."], 400);
        }
        $payout = (float)mt_rand(80000, 115000);
        $profit = $payout - $cost;
        $deltas['cash'] = $profit;
        $deltas['energy'] = -15;
        $deltas['cred'] = 12;
        $deltas['happiness'] = 20;
        $msg = "📱 Banex Flip Success! Bought wholesale smartphone lot for ₦50k and flipped online for " . formatNaira($payout) . "! Net profit: +" . formatNaira($profit) . "!";
    } elseif ($act === 'lake_cruise') {
        $cost = 6500.00;
        if ((float)$char['cash'] < $cost) {
            jsonResponse(['success' => false, 'error' => "Jabi Lake boat cruise costs ₦6,500."], 400);
        }
        $deltas['cash'] = -$cost;
        $deltas['energy'] = 10;
        $deltas['happiness'] = 40;
        $deltas['cred'] = 10;
        $msg = "🛥️ Enjoyed sunset boat cruise on Jabi Lake with music and cool breeze. VIP vibes (+40 Happiness)!";
    } elseif ($act === 'beach_relax') {
        $cost = 4000.00;
        if ((float)$char['cash'] < $cost) {
            jsonResponse(['success' => false, 'error' => "Beach Cabana reservation costs ₦4,000."], 400);
        }
        $deltas['cash'] = -$cost;
        $deltas['energy'] = 15;
        $deltas['health'] = 5;
        $deltas['happiness'] = 35;
        $msg = "🏖️ Relaxed under a beach cabana at Jabi Lake Beach sipping fresh coconut water! Peaceful vibes (+35 Happiness, +15 Energy).";
    } elseif ($act === 'beach_volleyball') {
        if ((int)$char['energy'] < 10) {
            jsonResponse(['success' => false, 'error' => "Too exhausted for volleyball! Rest first."], 400);
        }
        $deltas['energy'] = -10;
        $deltas['health'] = 25;
        $deltas['cred'] = 15;
        $deltas['happiness'] = 20;
        $msg = "🏐 Played a spirited beach volleyball match in the warm sand! Met cool folks (+25 Health, +15 Street Cred).";
    } elseif ($act === 'crypto_p2p') {
        $payout = (float)mt_rand(35000, 75000);
        $deltas['cash'] = $payout;
        $deltas['energy'] = -10;
        $deltas['happiness'] = 15;
        $msg = "🪙 P2P Dollar Arbitrage closed! Traded USDT on OTC exchange for +" . formatNaira($payout) . " instant profit!";
    } elseif ($act === 'pos_kiosk') {
        $payout = (float)mt_rand(15000, 28000);
        $deltas['cash'] = $payout;
        $deltas['energy'] = -12;
        $deltas['cred'] = 6;
        $msg = "🏪 Managed neighborhood POS cashout terminal! Collected +" . formatNaira($payout) . " in transaction charges.";
    } elseif ($act === 'tender_bid') {
        $formFee = 20000.00;
        if ((float)$char['cash'] < $formFee) {
            jsonResponse(['success' => false, 'error' => "Federal tender application fee is ₦20,000."], 400);
        }
        if (mt_rand(1, 100) <= 65) {
            $tenderPayout = (float)mt_rand(350000, 850000);
            $deltas['cash'] = $tenderPayout - $formFee;
            $deltas['cred'] = 25;
            $deltas['happiness'] = 35;
            $msg = "🏛️ TENDER APPROVED! Federal Ministry awarded your supply contract! Payout: +" . formatNaira($tenderPayout) . "!";
        } else {
            $deltas['cash'] = -$formFee;
            $deltas['cred'] = 5;
            $msg = "🏛️ Tender bid was marked pending for review. Form fee ₦20,000 paid. Try again next cycle.";
        }
    }

    $newCash = max(0, (float)$char['cash'] + $deltas['cash']);
    $newEnergy = min(100, max(0, (int)$char['energy'] + $deltas['energy']));
    $newHealth = min(100, max(0, (int)$char['health'] + $deltas['health']));
    $newHappy  = min(100, max(0, (int)$char['happiness'] + $deltas['happiness']));
    $newCred   = min(200, max(0, (int)$char['street_cred'] + $deltas['cred']));

    $stmt = $pdo->prepare("UPDATE characters SET cash = ?, energy = ?, health = ?, happiness = ?, street_cred = ? WHERE id = ?");
    $stmt->execute([$newCash, $newEnergy, $newHealth, $newHappy, $newCred, $char['id']]);

    logActivity($char['id'], 'destination_act', $msg, $deltas['cash'], $deltas['energy'], $deltas['happiness']);

    jsonResponse([
        'success' => true,
        'message' => $msg,
        'character' => getUserCharacter($userId)
    ]);
}

// ----------------------------------------------------
// 14. DAILY GEM HUNT CASH PRIZE
// ----------------------------------------------------
if ($action === 'claim_gem') {
    $prize = 3000.00;
    $newCash = (float)$char['cash'] + $prize;
    $newHappy = min(100, (int)$char['happiness'] + 10);

    $stmt = $pdo->prepare("UPDATE characters SET cash = ?, happiness = ? WHERE id = ?");
    $stmt->execute([$newCash, $newHappy, $char['id']]);

    logActivity($char['id'], 'gem_hunt', "Found hidden Abuja gem! Collected ₦3,000 cash prize.", $prize, 0, 10);

    jsonResponse([
        'success' => true,
        'message' => "💎 Found a hidden gem! ₦3,000 cash added to your wallet.",
        'character' => getUserCharacter($userId)
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);

