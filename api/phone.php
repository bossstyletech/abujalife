<?php
/**
 * Abuja Life - Smartphone Backend API (AbujaPhone Pro)
 * Handles NaijaGram (Social Feed), NaijaConnect (VIP Network), NaijaChat (WhatsApp),
 * Chowdeck Food Delivery, and AbujaPay Micro-banking.
 */

require_once __DIR__ . '/../config.php';

$userId = requireAuth();
$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? 'get_state');
$pdo    = getDbConnection();
$char   = getUserCharacter($userId);

if (!$char) {
    jsonResponse(['success' => false, 'error' => 'Character not found.'], 404);
}

// ---------------------------------------------------------
// Default Social Posts Feed
// ---------------------------------------------------------
$defaultPosts = [
    [
        'id' => 1,
        'author' => 'Senator Adeleke',
        'handle' => '@adeleke_fct',
        'avatar' => '👑',
        'verified' => true,
        'time' => '12m ago',
        'content' => 'Just inspected the ongoing infrastructural modernization in Maitama and Asokoro. Abuja is rising! 🇳🇬✨',
        'likes' => 1240,
        'userLiked' => false,
        'comments' => ['Good job sir!', 'Please look into Airport Road traffic o', 'Senior man! 🫡']
    ],
    [
        'id' => 2,
        'author' => 'Tech Bro Kunle',
        'handle' => '@kunle_builds',
        'avatar' => '💻',
        'verified' => true,
        'time' => '34m ago',
        'content' => 'Building fintech APIs from Wuse 2 with strong generator power. Lagos tech bros cannot relate to this peace of mind 🚀 #AbujaTech',
        'likes' => 856,
        'userLiked' => false,
        'comments' => ['Bro drop referral link!', 'Generator fuel is ₦12k though 😂', 'Wuse 2 cafes hit different']
    ],
    [
        'id' => 3,
        'author' => 'Abuja Gist Central',
        'handle' => '@abuja_gist',
        'avatar' => '🔥',
        'verified' => false,
        'time' => '1h ago',
        'content' => 'WHO SPRAYED ₦500k CRISP NOTES AT THE ICC OWAMBE LAST NIGHT?! The praise singers are still dancing! Drop your identity! 👀 #OwambeSaturday',
        'likes' => 2100,
        'userLiked' => false,
        'comments' => ['It was Chairman!', 'Money speaks in Abuja!', 'Aso-Ebi was looking top tier!']
    ],
    [
        'id' => 4,
        'author' => 'Danfo Chronicles',
        'handle' => '@danfo_life',
        'avatar' => '🚐',
        'verified' => false,
        'time' => '2h ago',
        'content' => 'Small rain drop like this, danfo fare from Berger to Lugbe jump from ₦500 to ₦1,000! Conductor say rain na luxury tax 😭 #DanfoRushHour',
        'likes' => 3410,
        'userLiked' => false,
        'comments' => ['True talk!', 'Omo I had to enter okada', 'Berger roundabout was pure water pool']
    ]
];

// ---------------------------------------------------------
// 1. GET FULL PHONE STATE
// ---------------------------------------------------------
if ($action === 'get_state') {
    $clout = 1500 + ((int)$char['street_cred'] * 45) + ((int)$char['days_lived'] * 15);
    
    jsonResponse([
        'success'    => true,
        'character'  => $char,
        'clout'      => $clout,
        'posts'      => $defaultPosts,
        'phone_time' => date('H:i'),
        'bank_balance' => (float)$char['bank'],
        'cash_balance' => (float)$char['cash'],
        'loan_balance' => (float)$char['loan_balance']
    ]);
}

// ---------------------------------------------------------
// 2. PUBLISH SOCIAL MEDIA POST (NaijaGram)
// ---------------------------------------------------------
if ($action === 'post_social') {
    $text = trim(cleanInput($_POST['content'] ?? ''));
    if (empty($text)) {
        jsonResponse(['success' => false, 'error' => 'Post cannot be empty! Type your hot gist.'], 400);
    }

    $credBonus  = mt_rand(2, 4);
    $happyBonus = mt_rand(4, 8);
    $cloutGain  = mt_rand(120, 320) + ((int)$char['street_cred'] * 3);

    $newCash = (float)$char['cash'];
    $newCred = min(200, (int)$char['street_cred'] + $credBonus);
    $newHappy = min(100, (int)$char['happiness'] + $happyBonus);

    $stmt = $pdo->prepare("UPDATE characters SET street_cred = ?, happiness = ? WHERE id = ?");
    $stmt->execute([$newCred, $newHappy, $char['id']]);

    $responsesPool = [
        ["author" => "Wuse2_Baddie", "text" => "Chai! You dey pressure us o! 🔥🔥"],
        ["author" => "Alhaji Musa", "text" => "Barkah da rana! Big boy movement! 👍"],
        ["author" => "Kunle Tech", "text" => "Valid point brother. Abuja hustle is real."],
        ["author" => "AreaBoy_Garki", "text" => "Chairman drop urgent 2k for boys na! 🙌"],
        ["author" => "AsoRock_Insider", "text" => "Approved! Street Cred on the rise."]
    ];
    shuffle($responsesPool);
    $generatedComments = array_slice($responsesPool, 0, mt_rand(2, 3));

    logActivity($char['id'], 'naijagram_post', "Posted hot update on NaijaGram: \"$text\". Clout +$cloutGain!", 0, 0, $happyBonus);

    jsonResponse([
        'success'    => true,
        'message'    => "Update posted on NaijaGram! Clout +$cloutGain, Street Cred +$credBonus.",
        'clout_gain' => $cloutGain,
        'comments'   => $generatedComments,
        'character'  => getUserCharacter($userId)
    ]);
}

// ---------------------------------------------------------
// 3. WHATSAPP CHAT ACTIONS & BILLINGS
// ---------------------------------------------------------
if ($action === 'chat_action') {
    $chatType = cleanInput($_POST['chat_type'] ?? '');
    $choice   = cleanInput($_POST['choice'] ?? '');

    $msg = '';
    $deltas = ['cash' => 0, 'happiness' => 0, 'karma' => 0, 'energy' => 0, 'street_cred' => 0];

    if ($chatType === 'landlord') {
        if ($choice === 'pay') {
            $cost = 5000;
            if ((float)$char['cash'] < $cost) {
                jsonResponse(['success' => false, 'error' => 'You do not have ₦5,000 cash for landlord levy!'], 400);
            }
            $deltas['cash'] = -$cost;
            $deltas['happiness'] = 5;
            $deltas['karma'] = 5;
            $msg = "Alhaji Landlord replied: 'Well received tenant! Water pump technician has been scheduled.' Compound peace maintained!";
        } else {
            $deltas['happiness'] = -8;
            $msg = "You left the landlord on read. He banged your gate 30 minutes later complaining about generator noise!";
        }
    } elseif ($chatType === 'femi') {
        if ($choice === 'send_2k') {
            $cost = 2000;
            if ((float)$char['cash'] < $cost) {
                jsonResponse(['success' => false, 'error' => 'No reach! You don\'t even have ₦2k cash!'], 400);
            }
            $deltas['cash'] = -$cost;
            $deltas['happiness'] = 10;
            $deltas['karma'] = 10;
            $msg = "Cousin Femi sent audio voice note: 'EGBON BLESS YOU! May your pocket never dry!' Good karma unlocked!";
        } else {
            $deltas['happiness'] = -3;
            $msg = "You replied 'Sapa hold me too bro'. Femi sent a crying sticker and forwarded your chat to your auntie!";
        }
    } elseif ($chatType === 'shawarma') {
        if ($choice === 'accept') {
            $cost = 2500;
            if ((float)$char['cash'] < $cost) {
                jsonResponse(['success' => false, 'error' => 'You need ₦2,500 cash for the shawarma delivery!'], 400);
            }
            $deltas['cash'] = -$cost;
            $deltas['energy'] = 25;
            $deltas['happiness'] = 15;
            $msg = "Rider delivered extra-spicy Banex Shawarma with chilled Lacasera! Energy +25%, Happiness +15%!";
        }
    } elseif ($chatType === 'kunle_gig') {
        if ($choice === 'accept_gig') {
            $payout = 35000;
            $deltas['cash'] = $payout;
            $deltas['energy'] = -15;
            $deltas['happiness'] = 15;
            $deltas['street_cred'] = 5;
            $msg = "You submitted the freelance project for Kunle! Foreign client paid out " . formatNaira($payout) . " straight!";
        }
    }

    $newCash  = max(0, (float)$char['cash'] + $deltas['cash']);
    $newEnergy = max(0, min(100, (int)$char['energy'] + $deltas['energy']));
    $newHappy  = max(0, min(100, (int)$char['happiness'] + $deltas['happiness']));
    $newCred   = max(0, min(200, (int)$char['street_cred'] + $deltas['street_cred']));
    $newKarma  = max(0, min(100, (int)($char['karma'] ?? 50) + $deltas['karma']));

    $stmt = $pdo->prepare("UPDATE characters SET cash = ?, energy = ?, happiness = ?, street_cred = ?, karma = ? WHERE id = ?");
    $stmt->execute([$newCash, $newEnergy, $newHappy, $newCred, $newKarma, $char['id']]);

    logActivity($char['id'], 'naijachat_action', $msg, $deltas['cash'], $deltas['energy'], $deltas['happiness']);

    jsonResponse([
        'success'   => true,
        'message'   => $msg,
        'character' => getUserCharacter($userId)
    ]);
}

// ---------------------------------------------------------
// 4. CHOWDECK / FOOD DELIVERY
// ---------------------------------------------------------
if ($action === 'order_food') {
    $menuItem = cleanInput($_POST['item'] ?? '');

    $catalog = [
        'lacasera_gala' => ['name' => 'Cold Lacasera & Gala', 'cost' => 600, 'energy' => 15, 'happy' => 5],
        'shawarma'      => ['name' => 'Banex Double Sausage Shawarma', 'cost' => 2500, 'energy' => 30, 'happy' => 15],
        'amala'         => ['name' => 'Buka Amala & Gbegiri Special', 'cost' => 2200, 'energy' => 45, 'happy' => 12],
        'suya_pack'     => ['name' => 'Maitama VIP Beef Suya & Masa', 'cost' => 4500, 'energy' => 35, 'happy' => 25],
        'jollof_party'  => ['name' => 'Smoky Party Jollof & Fried Chicken', 'cost' => 3800, 'energy' => 50, 'happy' => 20]
    ];

    if (!isset($catalog[$menuItem])) {
        jsonResponse(['success' => false, 'error' => 'Item not on Chowdeck menu!'], 400);
    }

    $dish = $catalog[$menuItem];
    if ((float)$char['cash'] < $dish['cost']) {
        jsonResponse(['success' => false, 'error' => 'Insufficient cash on hand for ' . $dish['name'] . ' (' . formatNaira($dish['cost']) . ')!'], 400);
    }

    $newCash = (float)$char['cash'] - $dish['cost'];
    $newEnergy = min(100, (int)$char['energy'] + $dish['energy']);
    $newHappy  = min(100, (int)$char['happiness'] + $dish['happy']);

    $stmt = $pdo->prepare("UPDATE characters SET cash = ?, energy = ?, happiness = ? WHERE id = ?");
    $stmt->execute([$newCash, $newEnergy, $newHappy, $char['id']]);

    $msg = "Chowdeck dispatch rider arrived! Enjoyed hot " . $dish['name'] . ". Energy +" . $dish['energy'] . "%, Happiness +" . $dish['happy'] . "%!";
    logActivity($char['id'], 'chowdeck_order', $msg, -$dish['cost'], $dish['energy'], $dish['happy']);

    jsonResponse([
        'success'   => true,
        'message'   => $msg,
        'character' => getUserCharacter($userId)
    ]);
}

// ---------------------------------------------------------
// 5. ABUJAPAY EMERGENCY MICROLOAN
// ---------------------------------------------------------
if ($action === 'microloan') {
    $amount = (float)($_POST['amount'] ?? 10000);
    if ($amount <= 0 || $amount > 30000) {
        jsonResponse(['success' => false, 'error' => 'Microloans are limited to ₦30,000 max.'], 400);
    }

    if ((float)$char['loan_balance'] > 100000) {
        jsonResponse(['success' => false, 'error' => 'AbujaPay loan rejected! Sapa risk too high. Clear existing debt first.'], 400);
    }

    $interest = $amount * 0.05; // 5% instant charge
    $totalDebt = $amount + $interest;

    $stmt = $pdo->prepare("UPDATE characters SET cash = cash + ?, loan_balance = loan_balance + ? WHERE id = ?");
    $stmt->execute([$amount, $totalDebt, $char['id']]);

    $msg = "Instant AbujaPay microloan approved! Disbursed " . formatNaira($amount) . " to cash wallet. (Repayment: " . formatNaira($totalDebt) . ").";
    logActivity($char['id'], 'abujapay_loan', $msg, $amount, 0, 0);

    jsonResponse([
        'success'   => true,
        'message'   => $msg,
        'character' => getUserCharacter($userId)
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid phone action.'], 400);
