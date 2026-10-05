<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();
$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? 'info');
$pdo = getDbConnection();
$char = getUserCharacter($userId);

if (!$char) {
    jsonResponse(['success' => false, 'error' => 'Character not found.'], 404);
}

if ($action === 'bet') {
    $stake = (float)($_POST['stake'] ?? 0);
    $risk = cleanInput($_POST['risk'] ?? 'safe'); // safe, medium, high

    if ($stake <= 0) {
        jsonResponse(['success' => false, 'error' => 'Enter a valid stake amount.'], 400);
    }

    if ((float)$char['cash'] < $stake) {
        jsonResponse(['success' => false, 'error' => 'You do not have enough cash for this bet slip.'], 400);
    }

    $multiplier = 1.6;
    $winChance = 65; // 65% chance

    if ($risk === 'medium') {
        $multiplier = 3.2;
        $winChance = 35;
    } elseif ($risk === 'high') {
        $multiplier = 12.0;
        $winChance = 10;
    }

    $roll = mt_rand(1, 100);
    $isWin = ($roll <= $winChance);

    if ($isWin) {
        $winnings = round($stake * $multiplier, 2);
        $netProfit = $winnings - $stake;
        $newHap = min(100, (int)$char['happiness'] + 15);

        $stmt = $pdo->prepare("UPDATE characters SET cash = cash + ?, happiness = ? WHERE id = ?");
        $stmt->execute([$netProfit, $newHap, $char['id']]);

        $msg = "BOOM! Your " . strtoupper($risk) . " sports ticket cut through! Won " . formatNaira($winnings) . " on " . formatNaira($stake) . " stake!";
        logActivity($char['id'], 'bet_win', $msg, $netProfit, 0, 15);

        jsonResponse([
            'success' => true,
            'result' => 'win',
            'multiplier' => $multiplier,
            'payout' => $winnings,
            'message' => $msg,
            'character' => getUserCharacter($userId)
        ]);
    } else {
        $newHap = max(0, (int)$char['happiness'] - 5);
        $stmt = $pdo->prepare("UPDATE characters SET cash = cash - ?, happiness = ? WHERE id = ?");
        $stmt->execute([$stake, $newHap, $char['id']]);

        $msg = "Heartbreak! One team played 0-0 in the 94th minute and cut your slip. Lost " . formatNaira($stake) . ".";
        logActivity($char['id'], 'bet_loss', $msg, -$stake, 0, -5);

        jsonResponse([
            'success' => true,
            'result' => 'loss',
            'multiplier' => $multiplier,
            'payout' => 0,
            'message' => $msg,
            'character' => getUserCharacter($userId)
        ]);
    }
}

if ($action === 'dice') {
    $stake = (float)($_POST['stake'] ?? 0);
    $prediction = cleanInput($_POST['prediction'] ?? 'high'); // low (2-6), high (8-12), seven (7)

    if ($stake <= 0) {
        jsonResponse(['success' => false, 'error' => 'Enter a valid dice stake.'], 400);
    }

    if ((float)$char['cash'] < $stake) {
        jsonResponse(['success' => false, 'error' => 'Insufficient cash to roll.'], 400);
    }

    $die1 = mt_rand(1, 6);
    $die2 = mt_rand(1, 6);
    $total = $die1 + $die2;

    $isWin = false;
    $multiplier = 2.0;

    if ($prediction === 'low' && $total >= 2 && $total <= 6) {
        $isWin = true;
        $multiplier = 2.0;
    } elseif ($prediction === 'high' && $total >= 8 && $total <= 12) {
        $isWin = true;
        $multiplier = 2.0;
    } elseif ($prediction === 'seven' && $total === 7) {
        $isWin = true;
        $multiplier = 5.0;
    }

    if ($isWin) {
        $winnings = round($stake * $multiplier, 2);
        $net = $winnings - $stake;
        $newHap = min(100, (int)$char['happiness'] + 10);
        $stmt = $pdo->prepare("UPDATE characters SET cash = cash + ?, happiness = ? WHERE id = ?");
        $stmt->execute([$net, $newHap, $char['id']]);

        $msg = "Dice rolled ($die1 + $die2 = $total)! You won " . formatNaira($winnings) . "!";
        logActivity($char['id'], 'dice_win', $msg, $net, 0, 10);

        jsonResponse([
            'success' => true,
            'result' => 'win',
            'dice' => [$die1, $die2, $total],
            'payout' => $winnings,
            'message' => $msg,
            'character' => getUserCharacter($userId)
        ]);
    } else {
        $newHap = max(0, (int)$char['happiness'] - 3);
        $stmt = $pdo->prepare("UPDATE characters SET cash = cash - ?, happiness = ? WHERE id = ?");
        $stmt->execute([$stake, $newHap, $char['id']]);

        $msg = "Dice rolled ($die1 + $die2 = $total). Better luck next roll! Lost " . formatNaira($stake) . ".";
        logActivity($char['id'], 'dice_loss', $msg, -$stake, 0, -3);

        jsonResponse([
            'success' => true,
            'result' => 'loss',
            'dice' => [$die1, $die2, $total],
            'payout' => 0,
            'message' => $msg,
            'character' => getUserCharacter($userId)
        ]);
    }
}

jsonResponse(['success' => false, 'error' => 'Invalid casino action.'], 400);
