<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();
$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? 'list');
$pdo = getDbConnection();
$char = getUserCharacter($userId);

if (!$char) {
    jsonResponse(['success' => false, 'error' => 'Character not found.'], 404);
}

$hustles = [
    'pos' => [
        'id' => 'pos',
        'title' => 'Kubwa/Lugbe POS Cash Point',
        'desc' => 'Dispense cash withdrawals and transfer money for market shoppers and commuters.',
        'energy' => 15,
        'min_cash' => 5000,
        'min_cred' => 0,
        'icon' => 'fa-credit-card'
    ],
    'gadget' => [
        'id' => 'gadget',
        'title' => 'Banex Plaza Gadget Flipping',
        'desc' => 'Scout UK-used iPhones, MacBooks, and Androids, swap parts, and resell in Wuse 2.',
        'energy' => 20,
        'min_cash' => 25000,
        'min_cred' => 10,
        'icon' => 'fa-mobile-screen-button'
    ],
    'crypto' => [
        'id' => 'crypto',
        'title' => 'P2P Crypto Arbitrage Trading',
        'desc' => 'Buy USDT low on foreign platforms and sell high on Nigerian P2P desks.',
        'energy' => 20,
        'min_cash' => 30000,
        'min_cred' => 15,
        'icon' => 'fa-bitcoin-sign'
    ],
    'mc' => [
        'id' => 'mc',
        'title' => 'Abuja Owambe & Lounge Hypeman / MC',
        'desc' => 'Host lavish birthday parties, wedding receptions, and club events in Wuse 2.',
        'energy' => 25,
        'min_cash' => 0,
        'min_cred' => 25,
        'icon' => 'fa-microphone'
    ],
    'contract' => [
        'id' => 'contract',
        'title' => 'Federal Ministry Contract Brokerage',
        'desc' => 'Facilitate stationery, borehole, and logistics procurement tenders with civil service directors.',
        'energy' => 30,
        'min_cash' => 100000,
        'min_cred' => 45,
        'icon' => 'fa-briefcase'
    ]
];

if ($action === 'list') {
    jsonResponse([
        'success' => true,
        'hustles' => array_values($hustles)
    ]);
}

if ($action === 'perform') {
    $hustleKey = cleanInput($_POST['hustle_id'] ?? '');
    if (!isset($hustles[$hustleKey])) {
        jsonResponse(['success' => false, 'error' => 'Invalid side hustle.'], 400);
    }

    $h = $hustles[$hustleKey];

    if ((int)$char['energy'] < $h['energy']) {
        jsonResponse(['success' => false, 'error' => "Not enough energy ({$h['energy']}% needed). Rest or sleep first!"], 400);
    }

    if ((int)$char['street_cred'] < $h['min_cred']) {
        jsonResponse(['success' => false, 'error' => "You need at least {$h['min_cred']} Street Cred for this hustle."], 400);
    }

    if ((float)$char['cash'] < $h['min_cash']) {
        jsonResponse(['success' => false, 'error' => "You need working capital of at least " . formatNaira($h['min_cash']) . " in cash."], 400);
    }

    $earned = 0;
    $credChange = 0;
    $msg = '';
    $success = true;

    if ($hustleKey === 'pos') {
        $earned = mt_rand(4000, 9500);
        $credChange = 1;
        $msg = "You ran busy POS queues at the junction and cleared " . formatNaira($earned) . " in charges!";
    } elseif ($hustleKey === 'gadget') {
        $roll = mt_rand(1, 10);
        if ($roll <= 8) {
            $earned = mt_rand(22000, 48000);
            $credChange = 3;
            $msg = "Clean deal! You flipped two iPhone 13s at Banex Plaza and pocketed " . formatNaira($earned) . " profit.";
        } else {
            $loss = mt_rand(8000, 15000);
            $earned = -$loss;
            $credChange = -1;
            $msg = "Bad deal! A supplier sold you a locked phone with a bad Face ID. Lost " . formatNaira($loss) . ".";
            $success = false;
        }
    } elseif ($hustleKey === 'crypto') {
        $roll = mt_rand(1, 100);
        if ($roll <= 65) {
            $profit = mt_rand(35000, 90000);
            $earned = $profit;
            $credChange = 4;
            $msg = "Arbitrage hit! Fast bank transfers and USDT spread yielded " . formatNaira($profit) . " profit.";
        } else {
            $loss = mt_rand(15000, 30000);
            $earned = -$loss;
            $credChange = -2;
            $msg = "Market dipped before orders filled! You took a loss of " . formatNaira($loss) . ".";
            $success = false;
        }
    } elseif ($hustleKey === 'mc') {
        $earned = mt_rand(40000, 120000);
        $credChange = 5;
        $msg = "Crowd loved your energy! The celebrant and guests sprayed you " . formatNaira($earned) . " in crisp notes.";
    } elseif ($hustleKey === 'contract') {
        $roll = mt_rand(1, 100);
        if ($roll <= 70) {
            $earned = mt_rand(250000, 750000);
            $credChange = 10;
            $msg = "Contract verified and awarded! You received your consultant facilitation fee of " . formatNaira($earned) . "!";
        } else {
            $bribe = mt_rand(40000, 80000);
            $earned = -$bribe;
            $credChange = -5;
            $msg = "Due process audit flag! You had to settle liaison paperwork costs of " . formatNaira($bribe) . " to clear your name.";
            $success = false;
        }
    }

    $newCash = max(0, (float)$char['cash'] + $earned);
    $newEnergy = max(0, (int)$char['energy'] - $h['energy']);
    $newCred = max(0, (int)$char['street_cred'] + $credChange);

    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET cash = ?, energy = ?, street_cred = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([$newCash, $newEnergy, $newCred, $char['id']]);

    logActivity($char['id'], 'hustle', $msg, $earned, -$h['energy'], ($success ? 5 : -5));

    jsonResponse([
        'success' => true,
        'message' => $msg,
        'earned' => $earned,
        'character' => getUserCharacter($userId)
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
