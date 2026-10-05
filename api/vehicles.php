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
    $stmt = $pdo->query("SELECT * FROM vehicles ORDER BY price ASC");
    $vehicles = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'vehicles' => $vehicles
    ]);
}

if ($action === 'my_vehicles') {
    $stmt = $pdo->prepare("
        SELECT cv.id AS ownership_id, cv.purchased_at,
               v.id AS vehicle_id, v.name, v.brand, v.price, v.daily_upkeep, v.happiness_bonus, v.cred_bonus, v.description
        FROM character_vehicles cv
        JOIN vehicles v ON cv.vehicle_id = v.id
        WHERE cv.character_id = ?
        ORDER BY cv.id DESC
    ");
    $stmt->execute([$char['id']]);
    $myVehicles = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'primary_vehicle_id' => $char['primary_vehicle_id'],
        'vehicles' => $myVehicles
    ]);
}

if ($action === 'buy') {
    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        jsonResponse(['success' => false, 'error' => 'Vehicle not found.'], 404);
    }

    $price = (float)$vehicle['price'];
    if ((float)$char['cash'] < $price) {
        jsonResponse(['success' => false, 'error' => "Insufficient cash. {$vehicle['name']} costs " . formatNaira($price) . "."], 400);
    }

    try {
        $pdo->beginTransaction();

        $stmtDeduct = $pdo->prepare("UPDATE characters SET cash = cash - ?, street_cred = street_cred + ? WHERE id = ?");
        $stmtDeduct->execute([$price, (int)$vehicle['cred_bonus'], $char['id']]);

        $stmtInsert = $pdo->prepare("INSERT INTO character_vehicles (character_id, vehicle_id) VALUES (?, ?)");
        $stmtInsert->execute([$char['id'], $vehicleId]);

        // Auto assign primary vehicle if none exists
        if (!$char['primary_vehicle_id']) {
            $stmtCar = $pdo->prepare("UPDATE characters SET primary_vehicle_id = ? WHERE id = ?");
            $stmtCar->execute([$vehicleId, $char['id']]);
        }

        logActivity($char['id'], 'vehicle_bought', "Acquired {$vehicle['name']} for " . formatNaira($price) . "! Road cred +{$vehicle['cred_bonus']}.", -$price, 0, 15);

        $pdo->commit();

        jsonResponse([
            'success' => true,
            'message' => "Congratulations! You just bought a {$vehicle['name']}.",
            'character' => getUserCharacter($userId)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'error' => 'Purchase transaction failed.'], 500);
    }
}

if ($action === 'cruise') {
    if (!$char['primary_vehicle_id']) {
        jsonResponse(['success' => false, 'error' => 'You need an active car in your garage to go for a cruise!'], 400);
    }

    if ((int)$char['energy'] < 10) {
        jsonResponse(['success' => false, 'error' => 'Too tired to drive! Rest first.'], 400);
    }

    $fuelCost = 2500.00;
    if ((float)$char['cash'] < $fuelCost) {
        jsonResponse(['success' => false, 'error' => "Fuel station billing! You need at least " . formatNaira($fuelCost) . " for PMS petrol."], 400);
    }

    $stmtCar = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmtCar->execute([$char['primary_vehicle_id']]);
    $car = $stmtCar->fetch();

    $credGain = max(1, (int)round($car['cred_bonus'] / 5));
    $hapGain = max(5, (int)round($car['happiness_bonus'] / 3));
    $newHappiness = min(100, (int)$char['happiness'] + $hapGain);

    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET cash = cash - ?, energy = energy - 10, happiness = ?, street_cred = street_cred + ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([$fuelCost, $newHappiness, $credGain, $char['id']]);

    $routes = [
        "cruising along the smooth curves of Shehu Shagari Way past Aso Rock.",
        "turning heads along Aminu Kano Crescent in Wuse 2 with your windows down.",
        "blasting Afrobeat songs along the Airport Expressway under the Abuja sun.",
        "gliding across the scenic Jabi Lake bridge."
    ];
    $routeText = $routes[array_rand($routes)];

    logActivity($char['id'], 'cruise', "Cruised in your {$car['name']} $routeText", -$fuelCost, -10, $hapGain);

    jsonResponse([
        'success' => true,
        'message' => "Enjoyed a smooth ride in your {$car['name']}! $routeText (+{$hapGain}% Happiness, +{$credGain} Cred)",
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'sell') {
    $ownershipId = (int)($_POST['ownership_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT cv.id AS ownership_id, cv.vehicle_id, v.name, v.price 
        FROM character_vehicles cv
        JOIN vehicles v ON cv.vehicle_id = v.id
        WHERE cv.id = ? AND cv.character_id = ?
    ");
    $stmt->execute([$ownershipId, $char['id']]);
    $item = $stmt->fetch();

    if (!$item) {
        jsonResponse(['success' => false, 'error' => 'Vehicle not found.'], 404);
    }

    $sellPayout = round((float)$item['price'] * 0.80, 2);

    try {
        $pdo->beginTransaction();

        $stmtDel = $pdo->prepare("DELETE FROM character_vehicles WHERE id = ?");
        $stmtDel->execute([$ownershipId]);

        $stmtCash = $pdo->prepare("UPDATE characters SET cash = cash + ? WHERE id = ?");
        $stmtCash->execute([$sellPayout, $char['id']]);

        if ((int)$char['primary_vehicle_id'] === (int)$item['vehicle_id']) {
            $stmtUnset = $pdo->prepare("UPDATE characters SET primary_vehicle_id = NULL WHERE id = ?");
            $stmtUnset->execute([$char['id']]);
        }

        logActivity($char['id'], 'vehicle_sold', "Sold {$item['name']} for " . formatNaira($sellPayout) . " to an Abuja car dealer.", $sellPayout, 0, 0);

        $pdo->commit();

        jsonResponse([
            'success' => true,
            'message' => "Sold {$item['name']} for " . formatNaira($sellPayout) . "!",
            'character' => getUserCharacter($userId)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'error' => 'Sale failed.'], 500);
    }
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
