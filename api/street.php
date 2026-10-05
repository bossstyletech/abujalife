<?php
/**
 * Abuja Life - Street & Transport Economy API
 * Handles all street/transport/economy mini-game actions:
 *   danfo_rush, lastma_checkpoint, okada_ride, goslow_hawker,
 *   agbero_encounter, ajo_contribution, market_haggle, owambe_party,
 *   religious_service, nepa_roulette, suya_spot, flash_flood
 */

require_once __DIR__ . '/../config.php';

$userId = requireAuth();
$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? '');
$pdo    = getDbConnection();
$char   = getUserCharacter($userId);

if (!$char) {
    jsonResponse(['success' => false, 'error' => 'Character not found. Create one first!'], 404);
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPER: clamp a stat between min and max
// ─────────────────────────────────────────────────────────────────────────────
function clampStat(int|float $value, int|float $min = 0, int|float $max = 100): int|float
{
    return max($min, min($max, $value));
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPER: apply delta to character stats and persist
// $deltas = ['cash'=>0,'energy'=>0,'happiness'=>0,'health'=>0,'street_cred'=>0,'karma'=>0]
// Returns the fresh character row.
// ─────────────────────────────────────────────────────────────────────────────
function applyStatDeltas(array $char, array $deltas, $pdo, int $userId): array
{
    $newCash      = max(0, (float)$char['cash']        + ($deltas['cash']        ?? 0));
    $newEnergy    = (int) clampStat((int)$char['energy']    + ($deltas['energy']    ?? 0));
    $newHappiness = (int) clampStat((int)$char['happiness'] + ($deltas['happiness'] ?? 0));
    $newHealth    = (int) clampStat((int)$char['health']    + ($deltas['health']    ?? 0));
    $newCred      = (int) clampStat((int)$char['street_cred'] + ($deltas['street_cred'] ?? 0), 0, 200);
    $newKarma     = (int) clampStat((int)($char['karma'] ?? 50) + ($deltas['karma'] ?? 0), 0, 100);

    $stmt = $pdo->prepare(
        'UPDATE characters SET cash=?, energy=?, happiness=?, health=?, street_cred=?, karma=? WHERE id=?'
    );
    $stmt->execute([$newCash, $newEnergy, $newHappiness, $newHealth, $newCred, $newKarma, $char['id']]);

    return getUserCharacter($userId);
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPER: read and write avatar JSON blob
// ─────────────────────────────────────────────────────────────────────────────
function getAvatarData(array $char): array
{
    if (!empty($char['avatar']) && str_starts_with(trim((string)$char['avatar']), '{')) {
        return json_decode($char['avatar'], true) ?? [];
    }
    return [];
}

function saveAvatarData(array $char, array $avatarData, $pdo): void
{
    $stmt = $pdo->prepare('UPDATE characters SET avatar=? WHERE id=?');
    $stmt->execute([json_encode($avatarData, JSON_UNESCAPED_UNICODE), $char['id']]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. DANFO_RUSH – Danfo Bus Rush Hour Mini-Game
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'danfo_rush') {
    $isRaining = (int)($_POST['is_raining'] ?? 0);
    $baseFare  = 500;
    $fare      = $isRaining ? ($baseFare * 2) : $baseFare;   // 500 or 1000

    $won = (mt_rand(1, 100) <= 40); // 40% chance of winning a seat

    if ($won) {
        if ((float)$char['cash'] < $fare) {
            jsonResponse(['success' => false, 'error' => 'E no reach! You no get enough cash for danfo fare (' . formatNaira($fare) . ').'], 400);
        }
        $deltas = ['cash' => -$fare, 'energy' => 5, 'happiness' => 10];
        $rainMsg = $isRaining ? ' Rain dey fall but you still secure seat – respect!' : '';
        $msg = "You sharp! You hustled into the danfo before the conductor shouted 'Full'." . $rainMsg . " Fare paid: " . formatNaira($fare) . ".";
    } else {
        // Missed the bus – okada to chase
        $okadaCost = 200;
        if ((float)$char['cash'] < $okadaCost) {
            $okadaCost = 0; // walk of shame, no deduction
        }
        $deltas = ['cash' => -$okadaCost, 'energy' => -3, 'happiness' => -5];
        $msg = "You missed the danfo! It zoomed off leaving you chasing dust. You took okada to catch up – " . formatNaira($okadaCost) . " down the drain.";
    }

    $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
    logActivity($char['id'], 'danfo_rush', $msg, $deltas['cash'], $deltas['energy'] ?? 0, $deltas['happiness']);

    jsonResponse([
        'success'    => true,
        'won'        => $won,
        'fare_paid'  => $won ? $fare : 200,
        'is_raining' => (bool)$isRaining,
        'message'    => $msg,
        'character'  => $updatedChar,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. LASTMA_CHECKPOINT – LASTMA Traffic Law Negotiation
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'lastma_checkpoint') {
    $negotiationChoice = cleanInput($_POST['negotiation_choice'] ?? '');
    $hasVehicle        = !empty($char['primary_vehicle_id']);

    // Violation probability
    $violationChance = $hasVehicle ? 30 : 60;
    $hasViolation    = (mt_rand(1, 100) <= $violationChance);

    // First trigger – no choice yet
    if ($negotiationChoice === '') {
        $scenarios = [
            'Your vehicle plate number expired two months ago and LASTMA officer just spotted it.',
            'You parked on a no-stopping zone on Herbert Macaulay Way – LASTMA van pulled up.',
            'You crossed a solid yellow line at the Berger interchange. Officer flagged you down.',
            'Your okada rider took a one-way street and LASTMA is waiting at the other end.',
            'You were caught doing a U-turn on a restricted segment of the Airport Road.',
        ];
        $violationText = $hasViolation
            ? $scenarios[array_rand($scenarios)]
            : 'LASTMA officer waved you down for a routine check but found no violation. You are free to go!';

        jsonResponse([
            'success'         => true,
            'event_triggered' => true,
            'has_violation'   => $hasViolation,
            'violation'       => $violationText,
            'choices'         => ['bribe', 'argue', 'show_receipt'],
            'message'         => $hasViolation
                ? 'LASTMA stop! Officer approaching your window…'
                : 'Routine check. You are clean – carry on!',
        ]);
    }

    // Player made a choice
    $cashChange  = 0;
    $credChange  = 0;
    $happyChange = 0;
    $msg         = '';
    $choiceResult = '';

    if (!$hasViolation) {
        jsonResponse([
            'success'       => true,
            'event_triggered' => false,
            'choice_result' => 'no_violation',
            'cash_change'   => 0,
            'cred_change'   => 0,
            'message'       => 'Officer waved you through — no violation recorded. Lucky you!',
            'character'     => $char,
        ]);
    }

    switch ($negotiationChoice) {
        case 'bribe':
            $bribeAmount = mt_rand(3000, 8000);
            if ((float)$char['cash'] < $bribeAmount) {
                jsonResponse(['success' => false, 'error' => 'You no get money to settle! Try another option.'], 400);
            }
            $cashChange   = -$bribeAmount;
            $credChange   = -2;
            $happyChange  = 3;
            $choiceResult = 'bribed';
            $msg = "You quietly slid " . formatNaira($bribeAmount) . " through the window. Officer smiled and waved you off. Gbas gbos – e don settle.";
            break;

        case 'argue':
            $won = (mt_rand(1, 100) <= 50);
            if ($won) {
                $credChange   = 5;
                $happyChange  = 2;
                $choiceResult = 'argued_won';
                $msg = "You stood your ground, quoted the Traffic Act, and the officer backed down! No fine, and respect gained. Abuja man no dey fall cheap!";
            } else {
                $fine         = 5000;
                if ((float)$char['cash'] < $fine) {
                    $fine = (float)$char['cash']; // drain whatever is left
                }
                $cashChange   = -$fine;
                $credChange   = -5;
                $happyChange  = -10;
                $choiceResult = 'argued_lost';
                $msg = "Your argument no hold! Officer called his supervisor and they slammed you with a " . formatNaira($fine) . " fine. You for just settle quietly.";
            }
            break;

        case 'show_receipt':
            $won = (mt_rand(1, 100) <= 80);
            if ($won) {
                $credChange   = 5;
                $choiceResult = 'receipt_accepted';
                $msg = "You presented your valid vehicle papers and road-worthiness certificate. Officer saluted and cleared you. Proper conduct = respect!";
            } else {
                $fine         = 2500;
                if ((float)$char['cash'] < $fine) {
                    $fine = (float)$char['cash'];
                }
                $cashChange   = -$fine;
                $credChange   = -1;
                $happyChange  = -5;
                $choiceResult = 'receipt_rejected';
                $msg = "Officer said the receipt expired last week and insisted on a " . formatNaira($fine) . " on-the-spot levy. Abuja LASTMA no be joke!";
            }
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Invalid negotiation choice. Choose: bribe, argue, or show_receipt.'], 400);
    }

    $deltas      = ['cash' => $cashChange, 'happiness' => $happyChange, 'street_cred' => $credChange];
    $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
    logActivity($char['id'], 'lastma_checkpoint', $msg, $cashChange, 0, $happyChange);

    jsonResponse([
        'success'         => true,
        'event_triggered' => false,
        'has_violation'   => $hasViolation,
        'choice_result'   => $choiceResult,
        'cash_change'     => $cashChange,
        'cred_change'     => $credChange,
        'message'         => $msg,
        'character'       => $updatedChar,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. OKADA_RIDE – Okada Express Fast Travel
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'okada_ride') {
    $fare = mt_rand(500, 1500);

    if ((float)$char['cash'] < $fare) {
        jsonResponse(['success' => false, 'error' => 'Cash no reach for okada fare (' . formatNaira($fare) . '). Find another way!'], 400);
    }

    $roll    = mt_rand(1, 100);
    $outcome = 'safe';
    $incident = null;
    $msg     = '';
    $deltas  = ['cash' => -$fare];

    if ($roll <= 20) {
        // Bad outcome (20%)
        $incidentRoll = mt_rand(1, 2);
        if ($incidentRoll === 1) {
            // Puddle incident
            $outcome  = 'incident';
            $incident = 'puddle';
            $deltas['health']    = -10;
            $deltas['happiness'] = -15;
            $msg = "Rider swerved at full speed and drove you straight into a massive puddle! You reached your destination soaking wet. Fare: " . formatNaira($fare) . ". Health and mood: destroyed.";
        } else {
            // Banned road incident
            $refund  = (int)($fare * 0.5);
            $outcome  = 'incident';
            $incident = 'banned_road';
            $deltas['cash']       = -($fare - $refund); // 50% refund
            $deltas['street_cred'] = -10;
            $deltas['happiness']  = -5;
            $msg = "Rider hopped on the expressway and FRSC officers stopped both of you! Okada seized. Rider refunded half your fare (" . formatNaira($refund) . "). You trekked the rest.";
        }
    } else {
        // Safe, fast arrival (80%)
        $deltas['happiness'] = 5;
        $deltas['energy']    = 5; // saved energy vs walking
        $msg = "Rider weaved through traffic like a ghost! You arrived in record time. Fare: " . formatNaira($fare) . ". +5 energy saved vs walking.";
    }

    $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
    logActivity($char['id'], 'okada_ride', $msg, $deltas['cash'], $deltas['energy'] ?? 0, $deltas['happiness'] ?? 0);

    jsonResponse([
        'success'   => true,
        'outcome'   => $outcome,
        'fare'      => $fare,
        'incident'  => $incident,
        'message'   => $msg,
        'character' => $updatedChar,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. GOSLOW_HAWKER – Go-Slow Street Hawker Purchase
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'goslow_hawker') {
    $item = cleanInput($_POST['item'] ?? '');

    $catalog = [
        'gala'           => ['label' => 'Gala Sausage Roll',    'cost' => 300,  'energy' => 12, 'happiness' => 8],
        'plantain_chips' => ['label' => 'Plantain Chips',       'cost' => 200,  'energy' => 8,  'happiness' => 5],
        'lacasera'       => ['label' => 'La Casera Apple Drink','cost' => 250,  'energy' => 6,  'happiness' => 10],
        'purewater'      => ['label' => 'Pure Water Sachet',    'cost' => 50,   'energy' => 3,  'happiness' => 2],
    ];

    if (!isset($catalog[$item])) {
        jsonResponse(['success' => false, 'error' => 'Invalid item. Choose: gala, plantain_chips, lacasera, or purewater.'], 400);
    }

    $selected = $catalog[$item];

    if ((float)$char['cash'] < $selected['cost']) {
        jsonResponse(['success' => false, 'error' => 'Your pocket empty! You cannot afford ' . $selected['label'] . ' (' . formatNaira($selected['cost']) . ').'], 400);
    }

    $deltas = [
        'cash'      => -$selected['cost'],
        'energy'    => $selected['energy'],
        'happiness' => $selected['happiness'],
    ];

    $msg = "Hawker ran alongside your window! You grabbed a " . $selected['label'] . " for " . formatNaira($selected['cost']) . ". +{$selected['energy']} energy, +{$selected['happiness']} happiness. Go-slow no too bad!";

    $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
    logActivity($char['id'], 'goslow_hawker', $msg, -$selected['cost'], $selected['energy'], $selected['happiness']);

    jsonResponse([
        'success'       => true,
        'item_bought'   => $selected['label'],
        'cost'          => $selected['cost'],
        'energy_gain'   => $selected['energy'],
        'happiness_gain'=> $selected['happiness'],
        'message'       => $msg,
        'character'     => $updatedChar,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. AGBERO_ENCOUNTER – Agbero/NURTW Dues
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'agbero_encounter') {
    // Only for drivers/riders
    $jobTitle = strtolower((string)($char['job_title'] ?? ''));
    $isDriver = str_contains($jobTitle, 'driver')
             || str_contains($jobTitle, 'keke')
             || str_contains($jobTitle, 'rider');

    if (!$isDriver) {
        jsonResponse([
            'success' => false,
            'error'   => 'Agbero no dey disturb you today – this event only affects Drivers, Keke Napep riders, and commercial vehicle operators.',
        ], 403);
    }

    $choice = cleanInput($_POST['choice'] ?? '');

    if ($choice === '') {
        jsonResponse([
            'success'        => true,
            'event'          => 'agbero_encounter',
            'event_triggered' => true,
            'description'    => 'Three agbero men in NURTW vests blocked your vehicle at the park entrance demanding daily union dues.',
            'choices'        => ['pay_dues', 'refuse'],
            'message'        => 'Oga! NURTW men don stop your vehicle. What you wan do?',
        ]);
    }

    $cashChange  = 0;
    $credChange  = 0;
    $happyChange = 0;
    $choiceResult = '';
    $msg = '';

    if ($choice === 'pay_dues') {
        $dues = mt_rand(1500, 3000);
        if ((float)$char['cash'] < $dues) {
            jsonResponse(['success' => false, 'error' => 'No money to settle! Park don seize your key.'], 400);
        }
        $cashChange   = -$dues;
        $credChange   = 3;
        $happyChange  = -5;
        $choiceResult = 'dues_paid';
        $msg = "You settled the agbero men with " . formatNaira($dues) . ". They let you pass and even greeted you as 'chairman'. Street respect earned!";

    } elseif ($choice === 'refuse') {
        $roll = mt_rand(1, 100);
        if ($roll <= 70) {
            // Mirror removed
            $repairCost   = 8000;
            $cashChange   = -$repairCost;
            $credChange   = -10;
            $happyChange  = -20;
            $choiceResult = 'mirror_removed';
            $msg = "You refused and they descended on your vehicle! Side mirror removed and windscreen cracked. Repair bill: " . formatNaira($repairCost) . ". No contest next time!";
        } else {
            // They backed down
            $credChange   = 8;
            $happyChange  = -15; // stress from standoff
            $choiceResult = 'they_backed_down';
            $msg = "You stood firm and they surprisingly backed off! Your reputation for not being bullied spread through the park. +8 street cred but the stress drained you.";
        }
    } else {
        jsonResponse(['success' => false, 'error' => 'Invalid choice. Choose: pay_dues or refuse.'], 400);
    }

    $deltas      = ['cash' => $cashChange, 'happiness' => $happyChange, 'street_cred' => $credChange];
    $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
    logActivity($char['id'], 'agbero_encounter', $msg, $cashChange, 0, $happyChange);

    jsonResponse([
        'success'       => true,
        'event'         => 'agbero_encounter',
        'choice_result' => $choiceResult,
        'cost'          => abs($cashChange),
        'message'       => $msg,
        'character'     => $updatedChar,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. AJO_CONTRIBUTION – Ajo Thrift System
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'ajo_contribution') {
    $action2      = cleanInput($_POST['action2'] ?? '');
    $contribution = 5000;
    $avatarData   = getAvatarData($char);
    $ajoData      = $avatarData['ajo'] ?? ['joined' => false, 'weeks' => 0, 'total' => 0];

    if ($action2 === 'join') {
        if ($ajoData['joined']) {
            jsonResponse(['success' => false, 'error' => 'You are already a member of the Ajo thrift group! Contribute monthly to build your pot.'], 400);
        }
        if ((float)$char['cash'] < $contribution) {
            jsonResponse(['success' => false, 'error' => 'You need at least ' . formatNaira($contribution) . ' to join the Ajo thrift group.'], 400);
        }
        $ajoData = ['joined' => true, 'weeks' => 1, 'total' => $contribution];
        $avatarData['ajo'] = $ajoData;
        saveAvatarData($char, $avatarData, $pdo);

        $deltas      = ['cash' => -$contribution, 'happiness' => 5, 'karma' => 3];
        $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
        logActivity($char['id'], 'ajo_contribution', 'Joined Ajo thrift group. First contribution: ' . formatNaira($contribution), -$contribution, 0, 5);

        jsonResponse([
            'success'      => true,
            'ajo_status'   => $ajoData,
            'payout_amount'=> 0,
            'message'      => "Welcome to the Ajo group! Your first " . formatNaira($contribution) . " contribution is locked in. Keep contributing monthly to reach payout!",
            'character'    => $updatedChar,
        ]);
    }

    if ($action2 === 'contribute') {
        if (!$ajoData['joined']) {
            jsonResponse(['success' => false, 'error' => 'You have not joined an Ajo group yet. Join first!'], 400);
        }
        if ((float)$char['cash'] < $contribution) {
            jsonResponse(['success' => false, 'error' => 'Cash no reach for this month Ajo contribution (' . formatNaira($contribution) . ').'], 400);
        }

        $ajoData['weeks'] += 1;
        $ajoData['total'] += $contribution;
        $avatarData['ajo'] = $ajoData;
        saveAvatarData($char, $avatarData, $pdo);

        $deltas      = ['cash' => -$contribution, 'happiness' => 3];
        $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
        logActivity($char['id'], 'ajo_contribution', 'Monthly Ajo contribution week ' . $ajoData['weeks'], -$contribution, 0, 3);

        $weeksLeft = max(0, 12 - $ajoData['weeks']);
        jsonResponse([
            'success'      => true,
            'ajo_status'   => $ajoData,
            'payout_amount'=> 0,
            'message'      => "Week {$ajoData['weeks']} contribution of " . formatNaira($contribution) . " paid! Total pot: " . formatNaira($ajoData['total']) . ". {$weeksLeft} more week(s) to payout.",
            'character'    => $updatedChar,
        ]);
    }

    if ($action2 === 'check_payout') {
        if (!$ajoData['joined']) {
            jsonResponse(['success' => false, 'error' => 'You have not joined an Ajo group!'], 400);
        }
        if ($ajoData['weeks'] < 12) {
            jsonResponse([
                'success'      => true,
                'ajo_status'   => $ajoData,
                'payout_amount'=> 0,
                'message'      => "Not yet! You have completed {$ajoData['weeks']} of 12 weeks. Keep contributing!",
                'character'    => $char,
            ]);
        }

        // Payout time! 10% community bonus
        $payout = (float)$ajoData['total'] * 1.1;

        // Reset ajo
        $avatarData['ajo'] = ['joined' => false, 'weeks' => 0, 'total' => 0];
        saveAvatarData($char, $avatarData, $pdo);

        $deltas      = ['cash' => $payout, 'happiness' => 25, 'karma' => 5];
        $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
        logActivity($char['id'], 'ajo_contribution', 'Ajo payout received: ' . formatNaira($payout), (int)$payout, 0, 25);

        jsonResponse([
            'success'      => true,
            'ajo_status'   => $avatarData['ajo'],
            'payout_amount'=> $payout,
            'message'      => "PAYOUT TIME! Your Ajo group paid out " . formatNaira($payout) . " (your " . formatNaira($ajoData['total']) . " + 10% community bonus)! Ajo for the win!",
            'character'    => $updatedChar,
        ]);
    }

    jsonResponse(['success' => false, 'error' => 'Invalid action2 for ajo_contribution. Use: join, contribute, or check_payout.'], 400);
}

// ─────────────────────────────────────────────────────────────────────────────
// 7. MARKET_HAGGLE – Market Haggling Mini-Game
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'market_haggle') {
    $market          = cleanInput($_POST['market']           ?? '');
    $itemType        = cleanInput($_POST['item_type']        ?? '');
    $offerPercentage = isset($_POST['offer_percentage']) ? (int)$_POST['offer_percentage'] : null;

    $validMarkets    = ['balogun', 'computer_village', 'wuse_market'];
    $basePrices      = ['phone' => 85000, 'clothes' => 15000, 'food' => 5000, 'electronics' => 45000];
    $marketLabels    = ['balogun' => 'Balogun Market (Lagos)', 'computer_village' => 'Computer Village (Ikeja)', 'wuse_market' => 'Wuse Market (Abuja)'];

    if (!in_array($market, $validMarkets)) {
        jsonResponse(['success' => false, 'error' => 'Invalid market. Choose: balogun, computer_village, or wuse_market.'], 400);
    }
    if (!isset($basePrices[$itemType])) {
        jsonResponse(['success' => false, 'error' => 'Invalid item_type. Choose: phone, clothes, food, or electronics.'], 400);
    }

    $askingPrice = $basePrices[$itemType];
    $marketLabel = $marketLabels[$market];

    // First call – no offer yet
    if ($offerPercentage === null) {
        jsonResponse([
            'success'      => true,
            'haggle_result'=> 'pending',
            'asking_price' => $askingPrice,
            'message'      => "Trader at {$marketLabel} is selling {$itemType} for " . formatNaira($askingPrice) . ". Don't pay asking price – counter-offer! Submit offer_percentage (0-100) to haggle.",
        ]);
    }

    // Validate offer
    $offerPercentage = max(0, min(100, $offerPercentage));
    $offeredPrice    = (int)($askingPrice * $offerPercentage / 100);
    $finalPrice      = $offeredPrice;
    $haggleResult    = '';
    $credChange      = 0;
    $happyChange     = 0;
    $msg             = '';
    $sold            = false;

    if ($offerPercentage > 80) {
        // Overpaid – accepted but called mumu
        $sold         = true;
        $credChange   = -10;
        $haggleResult = 'accepted_overpaid';
        $msg = "Trader accepted immediately – too fast! 'Oga you too generous o! Mumu buyer!' You overpaid at " . formatNaira($offeredPrice) . ". The whole market dey laugh at you. -10 street cred.";
    } elseif ($offerPercentage >= 60) {
        // Good deal
        $sold         = true;
        $credChange   = 3;
        $happyChange  = 5;
        $haggleResult = 'good_deal';
        $msg = "After back-and-forth, trader shook your hand. You got the {$itemType} for " . formatNaira($offeredPrice) . ". Decent deal! +3 street cred as a sensible buyer.";
    } elseif ($offerPercentage >= 40) {
        // 70% chance accepted
        if (mt_rand(1, 100) <= 70) {
            $sold         = true;
            $credChange   = 5;
            $happyChange  = 8;
            $haggleResult = 'great_deal';
            $msg = "Trader hesitated, sucked teeth, then agreed! " . formatNaira($offeredPrice) . " for the {$itemType}. You sharp! +5 street cred – you know how to buy!";
        } else {
            $sold         = false;
            $haggleResult = 'walked_away';
            $msg = "Trader hissed and packed his goods. 'I no dey sell for that price!' Deal collapsed. The " . formatNaira($offeredPrice) . " offer was too low for him today.";
        }
    } else {
        // Below 40% – 90% rejection
        if (mt_rand(1, 100) <= 10) {
            $sold         = true;
            $credChange   = 8;
            $happyChange  = 15;
            $haggleResult = 'legendary_deal';
            $msg = "UNBELIEVABLE! Trader is in a rush to close shop and accepted your low ball offer of " . formatNaira($offeredPrice) . "! Legendary deal achieved! You be money man!";
        } else {
            $sold         = false;
            $credChange   = 5;
            $happyChange  = -5;
            $haggleResult = 'rejected_low';
            $msg = "Trader rejected your " . formatNaira($offeredPrice) . " offer, called you 'monkey'. But people around respected your boldness. +5 cred for trying even if goods no sell.";
        }
    }

    // Deduct cash only if sold
    $deltas = ['street_cred' => $credChange, 'happiness' => $happyChange];
    if ($sold) {
        if ((float)$char['cash'] < $finalPrice) {
            jsonResponse(['success' => false, 'error' => 'Deal accepted but your cash no reach! You need ' . formatNaira($finalPrice) . '.'], 400);
        }
        $deltas['cash'] = -$finalPrice;
    }

    $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
    logActivity($char['id'], 'market_haggle', $msg, $deltas['cash'] ?? 0, 0, $happyChange);

    jsonResponse([
        'success'      => true,
        'haggle_result'=> $haggleResult,
        'sold'         => $sold,
        'asking_price' => $askingPrice,
        'final_price'  => $sold ? $finalPrice : null,
        'cred_change'  => $credChange,
        'message'      => $msg,
        'character'    => $updatedChar,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 8. OWAMBE_PARTY – Owambe Saturday
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'owambe_party') {
    $action2     = cleanInput($_POST['action2'] ?? '');
    $sprayAmount = (int)($_POST['spray_amount'] ?? 0);

    if ($action2 === 'attend') {
        $asoebiFee = 5000;
        if ((float)$char['cash'] < $asoebiFee) {
            jsonResponse(['success' => false, 'error' => 'You need ' . formatNaira($asoebiFee) . ' for aso-ebi to attend the Owambe. No fabric, no entry!'], 400);
        }
        $deltas      = ['cash' => -$asoebiFee, 'happiness' => 20, 'street_cred' => 10];
        $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
        logActivity($char['id'], 'owambe_party', 'Attended Owambe party. Aso-ebi: ' . formatNaira($asoebiFee), -$asoebiFee, 0, 20);

        jsonResponse([
            'success'         => true,
            'outcome'         => 'attending',
            'party_active'    => true,
            'happiness_gain'  => 20,
            'cred_gain'       => 10,
            'connections_made'=> 0,
            'message'         => "You stepped into the Owambe rocking your aso-ebi! DJ is live, jollof rice everywhere, and your presence is felt. +20 happiness, +10 cred!",
            'character'       => $updatedChar,
        ]);
    }

    if ($action2 === 'spray_money') {
        if ($sprayAmount < 5000) {
            jsonResponse(['success' => false, 'error' => 'Minimum spray amount is ' . formatNaira(5000) . '. No be like that you spray for Owambe!'], 400);
        }
        if ((float)$char['cash'] < $sprayAmount) {
            jsonResponse(['success' => false, 'error' => 'You no get enough cash to spray ' . formatNaira($sprayAmount) . '!'], 400);
        }

        // Every 1000 naira sprayed = +2 cred +3 happiness; max 50 cred gain
        $thousandsSpent = (int)floor($sprayAmount / 1000);
        $credGain       = min(50, $thousandsSpent * 2);
        $happyGain      = min(60, $thousandsSpent * 3);

        $deltas      = ['cash' => -$sprayAmount, 'happiness' => $happyGain, 'street_cred' => $credGain];
        $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
        logActivity($char['id'], 'owambe_party', 'Sprayed ' . formatNaira($sprayAmount) . ' at Owambe', -$sprayAmount, 0, $happyGain);

        jsonResponse([
            'success'         => true,
            'outcome'         => 'sprayed',
            'sprayed_amount'  => $sprayAmount,
            'happiness_gain'  => $happyGain,
            'cred_gain'       => $credGain,
            'connections_made'=> 0,
            'message'         => "Rain of money! You sprayed " . formatNaira($sprayAmount) . " on the dance floor and the celebrant. MC called your name on the mic. Crowd went wild! +{$credGain} cred, +{$happyGain} happiness!",
            'character'       => $updatedChar,
        ]);
    }

    if ($action2 === 'network') {
        $connected = (mt_rand(1, 100) <= 40);
        $jobTips   = [
            'A director at FCDA is looking for a procurement consultant – get your CV ready.',
            'Wuse 2 hotel manager needs a front-desk officer with good presentation – apply directly.',
            'A construction firm just got Ministry approval for a Jabi Lake project and needs staff urgently.',
            'A tech startup in Utako is hiring junior developers and UI designers – LinkedIn connect tonight.',
            'FCT Ministry of Transport is hiring logistics officers – closes this Friday.',
        ];
        $jobTip = $connected ? $jobTips[array_rand($jobTips)] : null;

        $deltas      = ['intelligence' => 5, 'street_cred' => 8, 'happiness' => 5];
        // Note: intelligence may not exist in all schemas – map to karma as cultural intelligence proxy
        $deltas      = ['karma' => 3, 'street_cred' => 8, 'happiness' => 5];
        $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
        logActivity($char['id'], 'owambe_party', 'Networked at Owambe. Connection: ' . ($connected ? 'yes' : 'no'), 0, 0, 5);

        jsonResponse([
            'success'           => true,
            'outcome'           => 'networked',
            'happiness_gain'    => 5,
            'cred_gain'         => 8,
            'connections_made'  => $connected ? 1 : 0,
            'job_tip'           => $jobTip,
            'message'           => $connected
                ? "You networked hard at the Owambe and a big man dropped a solid job tip! {$jobTip} +8 cred, +5 happiness!"
                : "You mingled, exchanged numbers, and handed out cards. Good vibes but no concrete connection tonight. +8 cred, +5 happiness.",
            'character'         => $updatedChar,
        ]);
    }

    jsonResponse(['success' => false, 'error' => 'Invalid action2 for owambe_party. Use: attend, spray_money, or network.'], 400);
}

// ─────────────────────────────────────────────────────────────────────────────
// 9. RELIGIOUS_SERVICE – Friday Jumat / Sunday Church
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'religious_service') {
    $type = cleanInput($_POST['type'] ?? '');

    if (!in_array($type, ['jumat', 'church'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid service type. Choose: jumat or church.'], 400);
    }

    $serviceLabel = ($type === 'jumat') ? 'Friday Jumat prayer at the National Mosque' : 'Sunday church service at the Living Faith Worship Centre';

    // Congregation job tips
    $jobTips = [
        'A brother in the congregation works at NNPC and mentioned a vacancy for an IT officer.',
        'Sister Margaret is the HR manager at Zenith Bank — she said to come with your CV next week.',
        'Pastor/Imam announced a free vocational training for members this Saturday in Garki.',
        'A deacon who owns a supermarket in Kubwa said he is looking for a trusted cashier.',
        'Someone in the congregation is looking for a young graduate to train as a real estate agent.',
    ];

    $hasJobTip = (mt_rand(1, 100) <= 30);
    $jobTip    = $hasJobTip ? $jobTips[array_rand($jobTips)] : null;

    $deltas = ['happiness' => 10, 'health' => 5, 'karma' => 8];

    // Save luck bonus in avatar JSON
    $avatarData             = getAvatarData($char);
    $avatarData['luck_bonus'] = ($avatarData['luck_bonus'] ?? 0) + 15;
    saveAvatarData($char, $avatarData, $pdo);

    $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
    logActivity($char['id'], 'religious_service', "Attended {$serviceLabel}. Luck+15, Happiness+10, Health+5, Karma+8.", 0, 0, 10);

    jsonResponse([
        'success'       => true,
        'service_type'  => $type,
        'happiness_gain'=> 10,
        'health_gain'   => 5,
        'karma_gain'    => 8,
        'luck_bonus'    => 15,
        'job_tip'       => $jobTip,
        'message'       => "You attended {$serviceLabel} and left feeling renewed. " .
                           ($hasJobTip ? "Bonus: someone shared a job lead — {$jobTip}" : "No specific job lead today but your spirit is lifted. Come back next week!"),
        'character'     => $updatedChar,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 10. NEPA_ROULETTE – NEPA Power Grid
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'nepa_roulette') {
    $action2    = cleanInput($_POST['action2'] ?? '');
    $avatarData = getAvatarData($char);

    // Buy inverter (special sub-action handled via action2='buy_inverter')
    if ($action2 === 'buy_inverter') {
        $inverterCost = 120000;
        if ($avatarData['inverter_owned'] ?? false) {
            jsonResponse(['success' => false, 'error' => 'You already own an inverter! Enjoy the silent hum of uninterrupted power.'], 400);
        }
        if ((float)$char['cash'] < $inverterCost) {
            jsonResponse(['success' => false, 'error' => 'You need ' . formatNaira($inverterCost) . ' to buy and install a solar inverter system.'], 400);
        }
        $avatarData['inverter_owned'] = true;
        saveAvatarData($char, $avatarData, $pdo);

        $deltas      = ['cash' => -$inverterCost, 'happiness' => 30];
        $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
        logActivity($char['id'], 'nepa_roulette', 'Bought and installed solar inverter system: ' . formatNaira($inverterCost), -$inverterCost, 0, 30);

        jsonResponse([
            'success'          => true,
            'power_status'     => 'inverter_installed',
            'cost'             => $inverterCost,
            'happiness_change' => 30,
            'message'          => "Inverter installed! NEPA can take 12 lifetimes – you have silent, clean solar power now. +30 happiness. No more generator fumes!",
            'character'        => $updatedChar,
        ]);
    }

    if ($action2 === 'buy_fuel') {
        $fuelCost = mt_rand(8000, 15000);
        if ((float)$char['cash'] < $fuelCost) {
            jsonResponse(['success' => false, 'error' => 'No money for fuel! The generator will remain silent today.'], 400);
        }
        $deltas      = ['cash' => -$fuelCost, 'happiness' => 15];
        $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
        logActivity($char['id'], 'nepa_roulette', 'Bought generator fuel: ' . formatNaira($fuelCost), -$fuelCost, 0, 15);

        jsonResponse([
            'success'          => true,
            'power_status'     => 'generator_running',
            'cost'             => $fuelCost,
            'happiness_change' => 15,
            'message'          => "You bought " . formatNaira($fuelCost) . " worth of fuel and the generator roared to life! Light don come (manually). +15 happiness.",
            'character'        => $updatedChar,
        ]);
    }

    if ($action2 === 'use_inverter') {
        $hasInverter = $avatarData['inverter_owned'] ?? false;
        if (!$hasInverter) {
            jsonResponse(['success' => false, 'error' => 'You do not own an inverter! Buy one for ' . formatNaira(120000) . ' using action2=buy_inverter.'], 400);
        }
        $deltas      = ['happiness' => 10];
        $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
        logActivity($char['id'], 'nepa_roulette', 'Used inverter for free power.', 0, 0, 10);

        jsonResponse([
            'success'          => true,
            'power_status'     => 'inverter_active',
            'cost'             => 0,
            'happiness_change' => 10,
            'message'          => "Inverter kicked in silently! While neighbors are sweating and complaining, you're cool with AC and full WiFi. +10 happiness.",
            'character'        => $updatedChar,
        ]);
    }

    if ($action2 === 'pray_light') {
        $lightCame = (mt_rand(1, 100) <= 30); // 30% NEPA brings light
        if ($lightCame) {
            $deltas      = ['happiness' => 8];
            $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
            logActivity($char['id'], 'nepa_roulette', 'Prayed for light – NEPA delivered!', 0, 0, 8);
            $msg = "'UP NEPA!' The transformer finally sparked to life! Your prayers were answered. The whole street rejoiced. +8 happiness.";
        } else {
            $deltas      = ['happiness' => -15, 'energy' => -10];
            $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
            logActivity($char['id'], 'nepa_roulette', 'Prayed for light – NEPA no send.', 0, -10, -15);
            $msg = "Light never came. You sat in darkness sweating and slapping mosquitoes all evening. -15 happiness, -10 energy from heat exhaustion.";
        }

        jsonResponse([
            'success'          => true,
            'power_status'     => $lightCame ? 'light_came' : 'no_power',
            'cost'             => 0,
            'happiness_change' => $lightCame ? 8 : -15,
            'message'          => $msg,
            'character'        => $updatedChar,
        ]);
    }

    jsonResponse(['success' => false, 'error' => 'Invalid action2 for nepa_roulette. Use: buy_fuel, use_inverter, pray_light, or buy_inverter.'], 400);
}

// ─────────────────────────────────────────────────────────────────────────────
// 11. SUYA_SPOT – Late Night Suya (only after 8PM)
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'suya_spot') {
    $hour = (int)date('G'); // 0-23, server local time

    if ($hour < 20) {
        jsonResponse([
            'success' => false,
            'error'   => 'The suya spot is not open yet! Mallam only lights the grill after 8PM. Come back in the evening.',
        ], 400);
    }

    $mealCost = mt_rand(2000, 5000);
    if ((float)$char['cash'] < $mealCost) {
        jsonResponse(['success' => false, 'error' => 'Your pocket no reach for suya tonight (' . formatNaira($mealCost) . '). Save up and come back!'], 400);
    }

    $metContact  = (mt_rand(1, 100) <= 20); // 20% chance meets important NPC
    $npcContacts = [
        'A senior NNPC official who could tip you off on petrol station acquisition deals.',
        'A popular Nollywood producer scouting talent for his next FCT-based production.',
        'A top lawyer from SAN Chambers who handles high-value property transactions.',
        'The operations manager of a fast-growing fintech — they are expanding to Abuja.',
        'A general from the barracks who runs a quiet logistics company on the side.',
    ];
    $contactMet = $metContact ? $npcContacts[array_rand($npcContacts)] : null;

    $deltas      = ['cash' => -$mealCost, 'energy' => 25, 'happiness' => 30, 'street_cred' => 8];
    $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
    logActivity($char['id'], 'suya_spot', "Late-night suya: " . formatNaira($mealCost) . ". Contact met: " . ($metContact ? 'yes' : 'no'), -$mealCost, 25, 30);

    jsonResponse([
        'success'       => true,
        'meal_cost'     => $mealCost,
        'energy_gain'   => 25,
        'happiness_gain'=> 30,
        'cred_gain'     => 8,
        'contact_met'   => $contactMet,
        'message'       => "Mallam sliced the hot suya onto newspaper, added onions and tomatoes — you demolished it! " . formatNaira($mealCost) . " well spent." .
                           ($metContact ? " You bumped into someone interesting: {$contactMet}" : " Good vibes all around the smoky spot."),
        'character'     => $updatedChar,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 12. FLASH_FLOOD – Weather/Flood Event (GET)
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'flash_flood') {
    $month = (int)date('n'); // 1-12

    // Rainy season: April (4) - October (10) in Nigeria
    // Use today's date as seed for consistent day-level result
    $daySeed = (int)date('Ymd');
    mt_srand($daySeed);

    $isRainySeason  = ($month >= 4 && $month <= 10);
    $rainChance     = $isRainySeason ? 65 : 15;
    $isRaining      = (mt_rand(1, 100) <= $rainChance);

    // Flood level is 0-3 depending on rain intensity
    $floodLevel = 0;
    if ($isRaining) {
        $intensityRoll = mt_rand(1, 100);
        if ($intensityRoll <= 50)      $floodLevel = 1; // Light drizzle
        elseif ($intensityRoll <= 80)  $floodLevel = 2; // Heavy rain, streets flooded
        else                           $floodLevel = 3; // Severe flash flood – stay home!
    }

    $descriptions = [
        0 => 'Skies are clear and dry. Perfect Abuja weather for moving around the city.',
        1 => 'Light drizzle falling. Roads slightly wet but manageable. Danfo and okada still running.',
        2 => 'Heavy rain pounding down! Streets are flooding around Kubwa and Nyanya. Danfo fares have doubled.',
        3 => 'SEVERE FLASH FLOOD! Roads completely submerged. Cars stalling. STAY INDOORS if possible. Emergency Alert!',
    ];

    $movementPenalties = [0 => 0, 1 => 5, 2 => 15, 3 => 40]; // % travel cost increase
    $fareMults         = [0 => 1.0, 1 => 1.0, 2 => 2.0, 3 => 3.0];

    // Reset mt_rand seed after done
    mt_srand();

    jsonResponse([
        'success'               => true,
        'is_raining'            => $isRaining,
        'flood_level'           => $floodLevel,
        'description'           => $descriptions[$floodLevel],
        'danfo_fare_multiplier' => $fareMults[$floodLevel],
        'movement_penalty'      => $movementPenalties[$floodLevel],
        'is_rainy_season'       => $isRainySeason,
        'month'                 => $month,
        'message'               => $descriptions[$floodLevel],
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 13. STREET_FIGHT – Street Fights, Agbero Clashes & Grudges
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'street_fight') {
    $combatAction = cleanInput($_POST['combat_action'] ?? 'encounter');
    $rivalName    = cleanInput($_POST['rival'] ?? 'Kubwa Street Agbero');

    if ($combatAction === 'encounter') {
        $opponents = [
            ['name' => 'Berger Underbridge Agbero', 'hp' => 80, 'desc' => 'Demanding illegal bus stop territory levy!'],
            ['name' => 'Kubwa Expressway Tout', 'hp' => 65, 'desc' => 'Tried to pickpocket your wallet in traffic!'],
            ['name' => 'Wuse 2 Nightclub Bouncer Rival', 'hp' => 110, 'desc' => 'Blocked your VIP entry claiming your outfit is invalid!'],
            ['name' => 'Garki Area Boy Leader', 'hp' => 90, 'desc' => 'Challenged your street authority in front of his crew!']
        ];
        $opp = $opponents[array_rand($opponents)];

        jsonResponse([
            'success'       => true,
            'opponent_name' => $opp['name'],
            'opponent_hp'   => $opp['hp'],
            'opponent_desc' => $opp['desc'],
            'player_hp'     => (int)$char['health'],
            'message'       => "Street Clash! {$opp['name']} stepped up: \"{$opp['desc']}\""
        ]);
    }

    $playerHp = (int)($_POST['player_hp'] ?? $char['health']);
    $oppHp    = (int)($_POST['opp_hp'] ?? 80);

    $msg = '';
    $won = false;
    $lootCash = 0;
    $credChange = 0;
    $healthDelta = 0;

    if ($combatAction === 'punch') {
        $damageDealt = mt_rand(22, 38) + round((int)$char['street_cred'] * 0.1);
        $oppHp = max(0, $oppHp - $damageDealt);
        if ($oppHp > 0) {
            $damageTaken = mt_rand(10, 22);
            $playerHp = max(5, $playerHp - $damageTaken);
            $healthDelta = -$damageTaken;
            $msg = "You threw a solid right cross dealing {$damageDealt} dmg! Opponent counter-punched for {$damageTaken} dmg.";
        } else {
            $won = true;
        }
    } elseif ($combatAction === 'dodge_counter') {
        $success = (mt_rand(1, 100) <= 68);
        if ($success) {
            $damageDealt = mt_rand(35, 55);
            $oppHp = max(0, $oppHp - $damageDealt);
            $msg = "Clean slip & counter! You dodged his swing and landed a devastating liver hook for {$damageDealt} critical dmg!";
            if ($oppHp <= 0) $won = true;
        } else {
            $damageTaken = mt_rand(18, 30);
            $playerHp = max(5, $playerHp - $damageTaken);
            $healthDelta = -$damageTaken;
            $msg = "You slipped on the asphalt and got clipped for {$damageTaken} dmg!";
        }
    } elseif ($combatAction === 'call_backup') {
        if ((int)$char['street_cred'] < 25) {
            jsonResponse(['success' => false, 'error' => 'You need at least 25 Street Cred to have loyal area boys on speed dial!'], 400);
        }
        $oppHp = 0;
        $won = true;
        $msg = "You whistled for the local boys! 4 Abuja youth swarmed the street with sticks. Opponent fled in terror!";
    } elseif ($combatAction === 'settle') {
        $settleCost = 2000;
        if ((float)$char['cash'] < $settleCost) {
            jsonResponse(['success' => false, 'error' => 'You do not have ₦2,000 cash to settle! You must fight or dodge.'], 400);
        }
        $updatedChar = applyStatDeltas($char, ['cash' => -$settleCost, 'happiness' => -5], $pdo, $userId);
        logActivity($char['id'], 'street_settle', "Settled street confrontation for " . formatNaira($settleCost) . " to avoid bloodshed.", -$settleCost, 0, -5);

        jsonResponse([
            'success'   => true,
            'resolved'  => true,
            'won'       => false,
            'message'   => "You handed over " . formatNaira($settleCost) . " 'peace money'. Opponent laughed and let you pass safely.",
            'character' => $updatedChar
        ]);
    }

    if ($won) {
        $lootCash = mt_rand(8000, 22000);
        $credChange = 8;
        $deltas = ['cash' => $lootCash, 'health' => $healthDelta, 'street_cred' => $credChange, 'happiness' => 10];
        $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);
        $winMsg = "VICTORY ON THE STREET! Knocked out opponent! Looted " . formatNaira($lootCash) . " and gained +8 Street Cred!";
        logActivity($char['id'], 'street_fight_won', $winMsg, $lootCash, 0, 10);

        jsonResponse([
            'success'     => true,
            'resolved'    => true,
            'won'         => true,
            'loot_cash'   => $lootCash,
            'cred_gain'   => $credChange,
            'message'     => $winMsg,
            'character'   => $updatedChar
        ]);
    } else {
        $deltas = ['health' => $healthDelta];
        $updatedChar = applyStatDeltas($char, $deltas, $pdo, $userId);

        jsonResponse([
            'success'     => true,
            'resolved'    => false,
            'won'         => false,
            'player_hp'   => $playerHp,
            'opp_hp'      => $oppHp,
            'message'     => $msg,
            'character'   => $updatedChar
        ]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 14. NETWORK_TROUBLES – Telco ISP Signal Outages & Network Switching
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'network_troubles') {
    $netAction = cleanInput($_POST['net_action'] ?? 'status');

    if ($netAction === 'switch_sim') {
        $newSim = cleanInput($_POST['sim'] ?? 'Airtel');
        jsonResponse([
            'success' => true,
            'active_sim' => $newSim,
            'signal_strength' => 95,
            'status' => 'CONNECTED_5G',
            'message' => "Switched data traffic to {$newSim} 5G network! Connection stabilized and POS active."
        ]);
    }

    if ($netAction === 'airplane_mode') {
        jsonResponse([
            'success' => true,
            'signal_strength' => 88,
            'status' => 'CONNECTED',
            'message' => "Toggled Airplane Mode! Cellular IP refreshed. Cellular tower handshake completed."
        ]);
    }

    // Default status check (30% chance of temporary glitch)
    $hasGlitch = (mt_rand(1, 100) <= 30);
    $isps = [
        ['name' => 'MTN Nigeria', 'status' => $hasGlitch ? 'FIBER_CUT_DELAY' : '4G_LTE', 'bars' => $hasGlitch ? 1 : 4],
        ['name' => 'Airtel FCT', 'status' => '5G_HIGH_SPEED', 'bars' => 5],
        ['name' => 'Glo Mega', 'status' => 'EDGE_SLOW', 'bars' => 2]
    ];

    jsonResponse([
        'success'    => true,
        'has_glitch' => $hasGlitch,
        'isps'       => $isps,
        'message'    => $hasGlitch 
            ? "Network Warning: Subsea cable glitch slowing down Abuja data! Switch SIM or toggle airplane mode." 
            : "All networks operating at normal bandwidth."
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Fallthrough – unknown action
// ─────────────────────────────────────────────────────────────────────────────
jsonResponse([
    'success' => false,
    'error'   => 'Unknown action. Valid actions: danfo_rush, lastma_checkpoint, okada_ride, goslow_hawker, agbero_encounter, ajo_contribution, market_haggle, owambe_party, religious_service, nepa_roulette, suya_spot, flash_flood, street_fight, network_troubles.',
], 400);

