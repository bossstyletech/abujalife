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
    $stmt = $pdo->query("SELECT * FROM properties ORDER BY price ASC");
    $properties = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'properties' => $properties
    ]);
}

if ($action === 'my_properties') {
    $stmt = $pdo->prepare("
        SELECT cp.id AS ownership_id, cp.is_rented_out, cp.purchased_at,
               p.id AS property_id, p.name, p.district, p.type, p.price, p.daily_rent_yield, p.happiness_bonus, p.prestige_points, p.description
        FROM character_properties cp
        JOIN properties p ON cp.property_id = p.id
        WHERE cp.character_id = ?
        ORDER BY cp.id DESC
    ");
    $stmt->execute([$char['id']]);
    $myProperties = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'primary_property_id' => $char['primary_property_id'],
        'properties' => $myProperties
    ]);
}

if ($action === 'buy') {
    $propertyId = (int)($_POST['property_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();

    if (!$property) {
        jsonResponse(['success' => false, 'error' => 'Property not found.'], 404);
    }

    $price = (float)$property['price'];
    if ((float)$char['cash'] < $price) {
        jsonResponse(['success' => false, 'error' => "Insufficient cash. {$property['name']} in {$property['district']} costs " . formatNaira($price) . "."], 400);
    }

    try {
        $pdo->beginTransaction();

        // Deduct cash
        $stmtDeduct = $pdo->prepare("UPDATE characters SET cash = cash - ?, street_cred = street_cred + ? WHERE id = ?");
        $stmtDeduct->execute([$price, (int)$property['prestige_points'], $char['id']]);

        // Insert ownership
        $stmtInsert = $pdo->prepare("INSERT INTO character_properties (character_id, property_id, is_rented_out) VALUES (?, ?, 0)");
        $stmtInsert->execute([$char['id'], $propertyId]);

        // Set as primary residence if character has none
        if (!$char['primary_property_id']) {
            $stmtHome = $pdo->prepare("UPDATE characters SET primary_property_id = ?, district = ? WHERE id = ?");
            $stmtHome->execute([$propertyId, $property['district'], $char['id']]);
        }

        logActivity($char['id'], 'property_bought', "Purchased {$property['name']} in {$property['district']} for " . formatNaira($price) . "! Prestige +{$property['prestige_points']}.", -$price, 0, 20);

        $pdo->commit();

        jsonResponse([
            'success' => true,
            'message' => "Congratulations on acquiring {$property['name']} in {$property['district']}! You are an Abuja property landlord.",
            'character' => getUserCharacter($userId)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'error' => 'Purchase transaction failed: ' . $e->getMessage()], 500);
    }
}

if ($action === 'toggle_rent') {
    $ownershipId = (int)($_POST['ownership_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM character_properties WHERE id = ? AND character_id = ?");
    $stmt->execute([$ownershipId, $char['id']]);
    $own = $stmt->fetch();

    if (!$own) {
        jsonResponse(['success' => false, 'error' => 'Property ownership record not found.'], 404);
    }

    $newRentStatus = $own['is_rented_out'] ? 0 : 1;
    $stmtUpdate = $pdo->prepare("UPDATE character_properties SET is_rented_out = ? WHERE id = ?");
    $stmtUpdate->execute([$newRentStatus, $ownershipId]);

    $stateText = $newRentStatus ? "rented out to tenants (generating passive daily Naira)" : "reserved for personal stay";
    logActivity($char['id'], 'property_status', "Property was marked as $stateText.", 0, 0, 0);

    jsonResponse([
        'success' => true,
        'message' => "Property is now $stateText.",
        'is_rented_out' => $newRentStatus
    ]);
}

if ($action === 'sell') {
    $ownershipId = (int)($_POST['ownership_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT cp.id AS ownership_id, cp.property_id, p.name, p.price 
        FROM character_properties cp
        JOIN properties p ON cp.property_id = p.id
        WHERE cp.id = ? AND cp.character_id = ?
    ");
    $stmt->execute([$ownershipId, $char['id']]);
    $item = $stmt->fetch();

    if (!$item) {
        jsonResponse(['success' => false, 'error' => 'Property record not found.'], 404);
    }

    $sellPayout = round((float)$item['price'] * 0.85, 2);

    try {
        $pdo->beginTransaction();

        $stmtDel = $pdo->prepare("DELETE FROM character_properties WHERE id = ?");
        $stmtDel->execute([$ownershipId]);

        $stmtCash = $pdo->prepare("UPDATE characters SET cash = cash + ? WHERE id = ?");
        $stmtCash->execute([$sellPayout, $char['id']]);

        // Unset primary residence if this was it
        if ((int)$char['primary_property_id'] === (int)$item['property_id']) {
            $stmtUnset = $pdo->prepare("UPDATE characters SET primary_property_id = NULL WHERE id = ?");
            $stmtUnset->execute([$char['id']]);
        }

        logActivity($char['id'], 'property_sold', "Sold {$item['name']} for " . formatNaira($sellPayout) . " (liquidated at 85% market rate).", $sellPayout, 0, 0);

        $pdo->commit();

        jsonResponse([
            'success' => true,
            'message' => "Property sold! " . formatNaira($sellPayout) . " credited to your cash account.",
            'character' => getUserCharacter($userId)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'error' => 'Sale transaction failed.'], 500);
    }
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
