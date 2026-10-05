<?php
/**
 * Abuja Life - Citizens Social Directory & Citizen Finder API
 * Handles @username search, citizen profile inspection, peer transfers, and street challenges.
 */

require_once __DIR__ . '/../config.php';

$userId = requireAuth();
$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? 'search');
$pdo    = getDbConnection();
$char   = getUserCharacter($userId);

if (!$char) {
    jsonResponse(['success' => false, 'error' => 'Character not found.'], 404);
}

// ----------------------------------------------------
// 1. SEARCH ABUJA CITIZENS BY @USERNAME OR NAME
// ----------------------------------------------------
if ($action === 'search') {
    $query = trim(cleanInput($_GET['q'] ?? $_POST['q'] ?? ''));

    if (empty($query)) {
        // Return top recent citizens / active players in Abuja
        $stmt = $pdo->prepare("
            SELECT c.id, c.user_id, c.full_name, c.district, c.archetype, c.street_cred,
                   c.cash, c.bank, c.gender, c.avatar, c.skin_tone, c.hair_style, c.outfit,
                   u.username, j.title AS job_title
            FROM characters c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN jobs j ON c.current_job_id = j.id
            WHERE c.user_id != ? AND c.is_alive = 1
            ORDER BY c.street_cred DESC, c.id DESC
            LIMIT 15
        ");
        $stmt->execute([$userId]);
        $citizens = $stmt->fetchAll();
    } else {
        $searchPattern = '%' . ltrim($query, '@') . '%';
        $stmt = $pdo->prepare("
            SELECT c.id, c.user_id, c.full_name, c.district, c.archetype, c.street_cred,
                   c.cash, c.bank, c.gender, c.avatar, c.skin_tone, c.hair_style, c.outfit,
                   u.username, j.title AS job_title
            FROM characters c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN jobs j ON c.current_job_id = j.id
            WHERE c.user_id != ? AND c.is_alive = 1 
              AND (u.username LIKE ? OR c.full_name LIKE ? OR c.district LIKE ?)
            ORDER BY c.street_cred DESC
            LIMIT 20
        ");
        $stmt->execute([$userId, $searchPattern, $searchPattern, $searchPattern]);
        $citizens = $stmt->fetchAll();
    }

    // Format net worth and badges
    $results = array_map(function($c) {
        $netWorth = (float)$c['cash'] + (float)$c['bank'];
        return [
            'id' => (int)$c['id'],
            'user_id' => (int)$c['user_id'],
            'username' => '@' . $c['username'],
            'full_name' => $c['full_name'],
            'district' => $c['district'],
            'archetype' => ucfirst($c['archetype']),
            'street_cred' => (int)$c['street_cred'],
            'job_title' => $c['job_title'] ?? 'Independent Hustler',
            'net_worth' => $netWorth,
            'avatar' => $c['avatar'],
            'outfit' => $c['outfit'],
            'gender' => $c['gender']
        ];
    }, $citizens);

    jsonResponse([
        'success' => true,
        'query' => $query,
        'count' => count($results),
        'citizens' => $results
    ]);
}

// ----------------------------------------------------
// 2. VIEW FULL CITIZEN DOSSIER
// ----------------------------------------------------
if ($action === 'profile') {
    $citizenId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT c.*, u.username, j.title AS job_title, j.daily_salary AS job_salary,
               v.name AS vehicle_name, v.brand AS vehicle_brand,
               p.name AS property_name, p.district AS property_district
        FROM characters c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN jobs j ON c.current_job_id = j.id
        LEFT JOIN vehicles v ON c.primary_vehicle_id = v.id
        LEFT JOIN properties p ON c.primary_property_id = p.id
        WHERE c.id = ? AND c.is_alive = 1
        LIMIT 1
    ");
    $stmt->execute([$citizenId]);
    $target = $stmt->fetch();

    if (!$target) {
        jsonResponse(['success' => false, 'error' => 'Citizen profile not found.'], 404);
    }

    $netWorth = (float)$target['cash'] + (float)$target['bank'];

    jsonResponse([
        'success' => true,
        'profile' => [
            'id' => (int)$target['id'],
            'user_id' => (int)$target['user_id'],
            'username' => '@' . $target['username'],
            'full_name' => $target['full_name'],
            'district' => $target['district'],
            'archetype' => ucfirst($target['archetype']),
            'street_cred' => (int)$target['street_cred'],
            'intelligence' => (int)$target['intelligence'],
            'net_worth' => $netWorth,
            'job_title' => $target['job_title'] ?? 'Self-Employed Hustler',
            'job_salary' => (float)($target['job_salary'] ?? 0),
            'vehicle' => $target['vehicle_name'] ? "{$target['vehicle_brand']} {$target['vehicle_name']}" : 'Trekking / Public Transit',
            'residence' => $target['property_name'] ?? 'Face-Me-I-Face-You Compound',
            'education' => $target['education_level'],
            'avatar' => $target['avatar'],
            'outfit' => $target['outfit'],
            'days_lived' => (int)$target['days_lived']
        ]
    ]);
}

// ----------------------------------------------------
// 3. SEND MONEY / OPay PEER TRANSFER
// ----------------------------------------------------
if ($action === 'transfer') {
    $citizenId = (int)($_POST['recipient_id'] ?? 0);
    $amount    = (float)($_POST['amount'] ?? 0);
    $memo      = trim(cleanInput($_POST['memo'] ?? 'Transfer from Abuja citizen'));

    if ($amount <= 0) {
        jsonResponse(['success' => false, 'error' => 'Enter a valid amount to send.'], 400);
    }

    if ($citizenId === (int)$char['id']) {
        jsonResponse(['success' => false, 'error' => 'You cannot transfer money to yourself.'], 400);
    }

    // Check sender balance (uses cash first, or bank)
    $source = 'bank';
    if ((float)$char['bank'] >= $amount) {
        $source = 'bank';
    } elseif ((float)$char['cash'] >= $amount) {
        $source = 'cash';
    } else {
        jsonResponse(['success' => false, 'error' => 'Insufficient funds in both bank and cash! Need ' . formatNaira($amount) . '.'], 400);
    }

    $stmt = $pdo->prepare("SELECT c.id, c.full_name, u.username FROM characters c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->execute([$citizenId]);
    $recipient = $stmt->fetch();

    if (!$recipient) {
        jsonResponse(['success' => false, 'error' => 'Recipient citizen not found.'], 404);
    }

    try {
        $pdo->beginTransaction();

        // Deduct from sender
        if ($source === 'bank') {
            $pdo->prepare("UPDATE characters SET bank = bank - ? WHERE id = ?")->execute([$amount, $char['id']]);
        } else {
            $pdo->prepare("UPDATE characters SET cash = cash - ? WHERE id = ?")->execute([$amount, $char['id']]);
        }

        // Credit recipient bank
        $pdo->prepare("UPDATE characters SET bank = bank + ? WHERE id = ?")->execute([$amount, $citizenId]);

        // Record transfer
        $pdo->prepare("
            INSERT INTO citizen_transfers (sender_id, recipient_id, amount, note)
            VALUES (?, ?, ?, ?)
        ")->execute([$char['id'], $citizenId, $amount, $memo]);

        $logMsg = "Sent " . formatNaira($amount) . " to @{$recipient['username']} ({$recipient['full_name']}) via AbujaPay. Note: \"$memo\"";
        logActivity($char['id'], 'peer_transfer', $logMsg, -$amount, 0, 5);

        // Boost street cred for generosity
        if ($amount >= 10000) {
            $pdo->prepare("UPDATE characters SET street_cred = MIN(200, street_cred + 2) WHERE id = ?")->execute([$char['id']]);
        }

        $pdo->commit();

        $refNo = 'ABJ-' . strtoupper(substr(md5(uniqid()), 0, 10));

        jsonResponse([
            'success' => true,
            'message' => "Transfer of " . formatNaira($amount) . " to @{$recipient['username']} completed successfully!",
            'receipt' => [
                'reference' => $refNo,
                'recipient' => "{$recipient['full_name']} (@{$recipient['username']})",
                'amount' => formatNaira($amount),
                'date' => date('Y-m-d H:i:s'),
                'memo' => $memo,
                'status' => 'SUCCESSFUL'
            ],
            'character' => getUserCharacter($userId)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'error' => 'Transfer failed: ' . $e->getMessage()], 500);
    }
}

// ----------------------------------------------------
// 4. STREET CRED FLEX & DICE CHALLENGE
// ----------------------------------------------------
if ($action === 'challenge') {
    $citizenId = (int)($_POST['recipient_id'] ?? 0);
    $stake     = (float)($_POST['stake'] ?? 5000);

    if ((float)$char['cash'] < $stake) {
        jsonResponse(['success' => false, 'error' => "You need at least " . formatNaira($stake) . " cash to stake a street challenge!"], 400);
    }

    $stmt = $pdo->prepare("SELECT c.*, u.username FROM characters c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
    $stmt->execute([$citizenId]);
    $opponent = $stmt->fetch();

    if (!$opponent) {
        jsonResponse(['success' => false, 'error' => 'Opponent citizen not found.'], 404);
    }

    // High/Low Dice Challenge
    $playerRoll = mt_rand(1, 6) + mt_rand(1, 6);
    $oppRoll    = mt_rand(1, 6) + mt_rand(1, 6);

    $playerWon = ($playerRoll > $oppRoll) || ($playerRoll === $oppRoll && (int)$char['street_cred'] >= (int)$opponent['street_cred']);

    if ($playerWon) {
        $winnings = $stake * 1.8;
        $netGain = $winnings - $stake;
        $newCash = (float)$char['cash'] + $netGain;
        $newCred = min(200, (int)$char['street_cred'] + 4);

        $pdo->prepare("UPDATE characters SET cash = ?, street_cred = ? WHERE id = ?")->execute([$newCash, $newCred, $char['id']]);

        $msg = "Street Flex Victory! Rolled $playerRoll vs @{$opponent['username']}'s $oppRoll. Won " . formatNaira($winnings) . " and gained +4 Street Cred!";
        logActivity($char['id'], 'street_challenge_won', $msg, $netGain, 0, 10);

        jsonResponse([
            'success' => true,
            'won' => true,
            'player_roll' => $playerRoll,
            'opp_roll' => $oppRoll,
            'payout' => $winnings,
            'message' => $msg,
            'character' => getUserCharacter($userId)
        ]);
    } else {
        $newCash = max(0, (float)$char['cash'] - $stake);
        $newCred = max(0, (int)$char['street_cred'] - 2);

        $pdo->prepare("UPDATE characters SET cash = ?, street_cred = ? WHERE id = ?")->execute([$newCash, $newCred, $char['id']]);

        $msg = "Challenge lost! Rolled $playerRoll vs @{$opponent['username']}'s $oppRoll. Lost " . formatNaira($stake) . ".";
        logActivity($char['id'], 'street_challenge_lost', $msg, -$stake, 0, -5);

        jsonResponse([
            'success' => true,
            'won' => false,
            'player_roll' => $playerRoll,
            'opp_roll' => $oppRoll,
            'payout' => 0,
            'message' => $msg,
            'character' => getUserCharacter($userId)
        ]);
    }
}

jsonResponse(['success' => false, 'error' => 'Invalid citizen action.'], 400);
