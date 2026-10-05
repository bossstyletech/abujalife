<?php
/**
 * Abuja Life - High-Yield Side Hustles & Lucrative Wealth Engine
 * Handles POS Agency, Banex Gadget Flipping, P2P FX Arbitrage, and Federal Ministry Procurement Contracts.
 */

require_once __DIR__ . '/../config.php';

$userId = requireAuth();
$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? 'status');
$pdo    = getDbConnection();
$char   = getUserCharacter($userId);

if (!$char) {
    jsonResponse(['success' => false, 'error' => 'Character not found.'], 404);
}

// ----------------------------------------------------
// LIST AVAILABLE HIGH-YIELD SIDE HUSTLES
// ----------------------------------------------------
if ($action === 'list') {
    $hustles = [
        [
            'id' => 'pos_agency',
            'title' => 'OPay / Moniepoint POS Terminal Agency',
            'desc' => 'Deploy cash-in / cash-out terminal at busy bus stops. Charge ₦200 to ₦1,200 per withdrawal. Watch out for disputed debits!',
            'icon' => 'fa-calculator',
            'min_cash' => 30000,
            'min_cred' => 5,
            'energy' => 15,
            'reward_range' => '₦8,000 - ₦25,000'
        ],
        [
            'id' => 'gadget_flip',
            'title' => 'Banex Plaza Electronics Flipping',
            'desc' => 'Buy faulty iPhones & MacBooks from Banex, repair with local technicians, and flip on Jiji Abuja for massive markup.',
            'icon' => 'fa-mobile-screen',
            'min_cash' => 95000,
            'min_cred' => 15,
            'energy' => 20,
            'reward_range' => '₦40,000 - ₦115,000'
        ],
        [
            'id' => 'p2p_trade',
            'title' => 'P2P Crypto & Parallel FX Arbitrage',
            'desc' => 'Buy USDT low on Binance P2P and sell on parallel street cash market in Wuse Zone 4. High returns, bank freeze risk.',
            'icon' => 'fa-arrow-right-arrow-left',
            'min_cash' => 100000,
            'min_cred' => 20,
            'energy' => 15,
            'reward_range' => '₦35,000 - ₦120,000'
        ],
        [
            'id' => 'ministry_contract',
            'title' => 'Federal Ministry Procurement Contracts',
            'desc' => 'Execute supplies of stationery, inverters, and laptops to ministries along Shehu Shagari Way. Huge government payout!',
            'icon' => 'fa-file-signature',
            'min_cash' => 150000,
            'min_cred' => 30,
            'energy' => 25,
            'reward_range' => '₦300,000 - ₦3,200,000'
        ]
    ];
    jsonResponse(['success' => true, 'hustles' => $hustles]);
}

// Route perform action from GameApp.performHustle
if ($action === 'perform') {
    $hustleId = cleanInput($_POST['hustle_id'] ?? '');
    if ($hustleId === 'pos_agency') $action = 'pos_session';
    elseif ($hustleId === 'gadget_flip') $action = 'flip_gadget';
    elseif ($hustleId === 'p2p_trade') $action = 'p2p_trade';
    elseif ($hustleId === 'ministry_contract') $action = 'bid_contract';
}

// ----------------------------------------------------
// 1. POS AGENCY TERMINAL HUSTLE
// ----------------------------------------------------
if ($action === 'pos_session') {
    $energyCost = 15;
    if ((int)$char['energy'] < $energyCost) {
        jsonResponse(['success' => false, 'error' => "Too tired to run POS terminal ({$energyCost}% energy required)! Rest first."], 400);
    }

    if ((float)$char['cash'] < 30000) {
        jsonResponse(['success' => false, 'error' => 'You need at least ₦30,000 cash float in your till to handle POS customer withdrawals!'], 400);
    }

    // Simulate 3 customer transactions
    $fee1 = mt_rand(300, 500);
    $fee2 = mt_rand(600, 1200);
    $fee3 = mt_rand(1200, 2500);
    $totalFees = $fee1 + $fee2 + $fee3;

    // 15% chance of a network dispute or counterfeit note
    $hasIncident = (mt_rand(1, 100) <= 15);
    $penalty = $hasIncident ? 3000 : 0;
    $netProfit = max(1000, $totalFees - $penalty);

    $newCash = (float)$char['cash'] + $netProfit;
    $newEnergy = max(0, (int)$char['energy'] - $energyCost);
    $newCred = min(200, (int)$char['street_cred'] + 2);

    $stmt = $pdo->prepare("UPDATE characters SET cash = ?, energy = ?, street_cred = ? WHERE id = ?");
    $stmt->execute([$newCash, $newEnergy, $newCred, $char['id']]);

    $incidentMsg = $hasIncident 
        ? " (Customer disputed ₦3,000 pending debit, had to settle)." 
        : " Smooth OPay POS network, zero downtime!";
    $msg = "POS Stall Rush Hour! Processed ₦75,000 total volume. Collected " . formatNaira($totalFees) . " in transaction charges." . $incidentMsg . " Net profit: " . formatNaira($netProfit) . "!";

    logActivity($char['id'], 'hustle_pos', $msg, $netProfit, -$energyCost, 5);

    jsonResponse([
        'success' => true,
        'net_profit' => $netProfit,
        'has_incident' => $hasIncident,
        'message' => $msg,
        'character' => getUserCharacter($userId)
    ]);
}

// ----------------------------------------------------
// 2. BANEX GADGET FLIPPING HUSTLE
// ----------------------------------------------------
if ($action === 'flip_gadget') {
    $gadgetType = cleanInput($_POST['gadget_type'] ?? 'iphone12');

    $catalog = [
        'iphone12' => [
            'name' => 'UK-Used iPhone 12 (Cracked OLED Screen)',
            'buy_price' => 95000,
            'repair_cost' => 20000,
            'sell_price' => 155000,
            'min_iq' => 15
        ],
        'macbook_m1' => [
            'name' => 'MacBook Pro M1 (Dead Battery & Dusty Logic Board)',
            'buy_price' => 220000,
            'repair_cost' => 45000,
            'sell_price' => 380000,
            'min_iq' => 30
        ],
        'samsung_s22' => [
            'name' => 'Samsung Galaxy S22 Ultra (Camera Glass Broken)',
            'buy_price' => 160000,
            'repair_cost' => 30000,
            'sell_price' => 270000,
            'min_iq' => 25
        ]
    ];

    if (!isset($catalog[$gadgetType])) {
        jsonResponse(['success' => false, 'error' => 'Invalid gadget selected.'], 400);
    }

    $g = $catalog[$gadgetType];
    $totalInvestment = $g['buy_price'] + $g['repair_cost'];

    if ((float)$char['cash'] < $totalInvestment) {
        jsonResponse(['success' => false, 'error' => "Insufficient cash! Need " . formatNaira($totalInvestment) . " for purchase + repair parts."], 400);
    }

    if ((int)$char['intelligence'] < $g['min_iq']) {
        jsonResponse(['success' => false, 'error' => "You need at least {$g['min_iq']} Intelligence to diagnose and negotiate hardware repairs!"], 400);
    }

    $profit = $g['sell_price'] - $totalInvestment;
    $newCash = (float)$char['cash'] + $profit;
    $newCred = min(200, (int)$char['street_cred'] + 3);

    $stmt = $pdo->prepare("UPDATE characters SET cash = ?, street_cred = ? WHERE id = ?");
    $stmt->execute([$newCash, $newCred, $char['id']]);

    $msg = "Banex Flip Success! Bought {$g['name']} for " . formatNaira($g['buy_price']) . ", repaired at Banex for " . formatNaira($g['repair_cost']) . ", sold on Jiji Abuja for " . formatNaira($g['sell_price']) . "! Net profit: " . formatNaira($profit) . "!";
    logActivity($char['id'], 'gadget_flip', $msg, $profit, -10, 10);

    jsonResponse([
        'success' => true,
        'profit' => $profit,
        'message' => $msg,
        'character' => getUserCharacter($userId)
    ]);
}

// ----------------------------------------------------
// 3. P2P CRYPTO & PARALLEL FX ARBITRAGE
// ----------------------------------------------------
if ($action === 'p2p_trade') {
    $capital = (float)($_POST['capital'] ?? 100000);
    if ($capital < 50000 || $capital > 1000000) {
        jsonResponse(['success' => false, 'error' => 'Capital must be between ₦50,000 and ₦1,000,000.'], 400);
    }

    if ((float)$char['bank'] < $capital && (float)$char['cash'] < $capital) {
        jsonResponse(['success' => false, 'error' => 'Insufficient funds in bank or cash for P2P arbitrage trade!'], 400);
    }

    // 5% to 12% arbitrage spread
    $spreadPct = mt_rand(5, 12) / 100;
    $profit = round($capital * $spreadPct);

    // 10% chance of CBN/Bank compliance review freeze
    $isFrozen = (mt_rand(1, 100) <= 10);
    if ($isFrozen) {
        $freezeFine = 15000;
        $profit -= $freezeFine;
        $newBank = max(0, (float)$char['bank'] + $profit);
        $pdo->prepare("UPDATE characters SET bank = ? WHERE id = ?")->execute([$newBank, $char['id']]);

        $msg = "P2P Arbitrage: Bought USDT low, but your commercial bank flagged the transaction for PND compliance! Settled compliance desk for " . formatNaira($freezeFine) . ". Net profit reduced to " . formatNaira($profit) . ".";
        logActivity($char['id'], 'p2p_arbitrage', $msg, $profit, 0, -5);
    } else {
        $newBank = (float)$char['bank'] + $profit;
        $pdo->prepare("UPDATE characters SET bank = ? WHERE id = ?")->execute([$newBank, $char['id']]);

        $msg = "Wuse Zone 4 Parallel FX Spread Cleared! Disbursed USDT on Bybit and collected parallel cash. Net Arbitrage Profit: " . formatNaira($profit) . "!";
        logActivity($char['id'], 'p2p_arbitrage', $msg, $profit, 0, 10);
    }

    jsonResponse([
        'success' => true,
        'profit' => $profit,
        'is_frozen' => $isFrozen,
        'message' => $msg,
        'character' => getUserCharacter($userId)
    ]);
}

// ----------------------------------------------------
// 4. FEDERAL MINISTRY PROCUREMENT CONTRACT TENDERS
// ----------------------------------------------------
if ($action === 'bid_contract') {
    $tenderId = cleanInput($_POST['tender_id'] ?? 'stationery');

    $tenders = [
        'stationery' => [
            'title' => 'Supply 600 Cartons Double-A Paper to Ministry of Works',
            'required_cred' => 30,
            'capital' => 150000,
            'payout' => 450000
        ],
        'inverters' => [
            'title' => 'Supply & Install 25 Hybrid Solar Inverters to Federal Secretariat',
            'required_cred' => 50,
            'capital' => 750000,
            'payout' => 2200000
        ],
        'cbn_laptops' => [
            'title' => 'Procure 50 Core-i7 Enterprise Laptops for CBN Satellite Audit',
            'required_cred' => 70,
            'capital' => 1600000,
            'payout' => 4800000
        ]
    ];

    if (!isset($tenders[$tenderId])) {
        jsonResponse(['success' => false, 'error' => 'Tender not recognized.'], 400);
    }

    $t = $tenders[$tenderId];

    if ((int)$char['street_cred'] < $t['required_cred']) {
        jsonResponse(['success' => false, 'error' => "Your Street Cred is too low ({$t['required_cred']} required). The Procurement Director doesn't know you!"], 400);
    }

    if ((float)$char['cash'] < $t['capital'] && (float)$char['bank'] < $t['capital']) {
        jsonResponse(['success' => false, 'error' => "You do not have enough working capital (" . formatNaira($t['capital']) . ") to fund contract supplies!"], 400);
    }

    $profit = $t['payout'] - $t['capital'];
    $newBank = (float)$char['bank'] + $profit;
    $newCred = min(200, (int)$char['street_cred'] + 10);

    $stmt = $pdo->prepare("UPDATE characters SET bank = ?, street_cred = ? WHERE id = ?");
    $stmt->execute([$newBank, $newCred, $char['id']]);

    $msg = "FEDERAL CONTRACT AWARDED & EXECUTED! Tender '{$t['title']}' paid out " . formatNaira($t['payout']) . " into your account. Net Profit: " . formatNaira($profit) . "! Street Cred +10!";
    logActivity($char['id'], 'procurement_contract', $msg, $profit, -20, 25);

    jsonResponse([
        'success' => true,
        'profit' => $profit,
        'message' => $msg,
        'character' => getUserCharacter($userId)
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid hustle action.'], 400);
