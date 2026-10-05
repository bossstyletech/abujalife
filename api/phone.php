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

// ---------------------------------------------------------
// 6. NAIJACHAT MESSAGING ENGINE (REAL CITIZENS & CONTACTS)
// ---------------------------------------------------------
if ($action === 'get_chat_threads') {
    // 1. Fetch conversations with real registered users from user_messages
    $stmt = $pdo->prepare("
        SELECT 
            CASE WHEN m.sender_id = :uid THEN m.recipient_id ELSE m.sender_id END AS other_user_id,
            MAX(m.id) AS latest_msg_id,
            SUM(CASE WHEN m.recipient_id = :uid AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
        FROM user_messages m
        WHERE m.sender_id = :uid OR m.recipient_id = :uid
        GROUP BY other_user_id
        ORDER BY latest_msg_id DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $threadRows = $stmt->fetchAll();

    $userThreads = [];
    foreach ($threadRows as $t) {
        $otherUid = (int)$t['other_user_id'];
        $uStmt = $pdo->prepare("
            SELECT u.id AS user_id, u.username, c.id AS character_id, c.full_name, c.district, c.avatar, c.outfit
            FROM users u
            LEFT JOIN characters c ON c.user_id = u.id
            WHERE u.id = ?
        ");
        $uStmt->execute([$otherUid]);
        $targetUser = $uStmt->fetch();
        if (!$targetUser) continue;

        // Fetch latest message details
        $msgStmt = $pdo->prepare("SELECT message, created_at, sender_id FROM user_messages WHERE id = ?");
        $msgStmt->execute([(int)$t['latest_msg_id']]);
        $latestMsg = $msgStmt->fetch();

        $timeStr = $latestMsg ? date('H:i', strtotime($latestMsg['created_at'])) : '';

        $userThreads[] = [
            'type'        => 'user',
            'user_id'     => (int)$targetUser['user_id'],
            'character_id'=> (int)($targetUser['character_id'] ?? 0),
            'username'    => '@' . $targetUser['username'],
            'raw_username'=> $targetUser['username'],
            'name'        => $targetUser['full_name'] ?: $targetUser['username'],
            'district'    => $targetUser['district'] ?? 'Abuja FCT',
            'avatar'      => $targetUser['avatar'] ?? 'assets/img/characters/tunde/face.png',
            'last_message'=> $latestMsg ? ($latestMsg['sender_id'] == $userId ? 'You: ' : '') . $latestMsg['message'] : 'Started conversation',
            'time'        => $timeStr,
            'unread'      => (int)$t['unread_count'],
            'online'      => true
        ];
    }

    // 2. Fetch available Abuja citizens to start new chats with
    $citStmt = $pdo->prepare("
        SELECT u.id AS user_id, u.username, c.id AS character_id, c.full_name, c.district, c.avatar, c.street_cred
        FROM users u
        JOIN characters c ON c.user_id = u.id
        WHERE u.id != ? AND c.is_alive = 1
        ORDER BY c.street_cred DESC, c.id DESC
        LIMIT 10
    ");
    $citStmt->execute([$userId]);
    $availableCitizens = array_map(function($c) {
        return [
            'user_id'     => (int)$c['user_id'],
            'character_id'=> (int)$c['character_id'],
            'username'    => '@' . $c['username'],
            'raw_username'=> $c['username'],
            'name'        => $c['full_name'],
            'district'    => $c['district'],
            'avatar'      => $c['avatar'] ?: 'assets/img/characters/tunde/face.png',
            'street_cred' => (int)$c['street_cred']
        ];
    }, $citStmt->fetchAll());

    // 3. Built-in interactive quest/NPC threads
    $npcThreads = [
        [
            'type'        => 'npc',
            'id'          => 'landlord',
            'name'        => 'Alhaji Landlord',
            'username'    => '@alhaji_landlord',
            'raw_username'=> 'alhaji_landlord',
            'avatar'      => '🏢',
            'district'    => 'Compound Owner',
            'last_message'=> 'Borehole maintenance levy ₦5,000 due.',
            'time'        => 'Today',
            'unread'      => 1,
            'online'      => true
        ],
        [
            'type'        => 'npc',
            'id'          => 'kunle_gig',
            'name'        => 'Kunle (Tech Bro)',
            'username'    => '@kunle_tech',
            'raw_username'=> 'kunle_tech',
            'avatar'      => '💻',
            'district'    => 'Wuse 2 Tech Hub',
            'last_message'=> 'Emergency foreign client bugfix available. Payout: ₦35k.',
            'time'        => '10m ago',
            'unread'      => 1,
            'online'      => true
        ],
        [
            'type'        => 'npc',
            'id'          => 'femi',
            'name'        => 'Cousin Femi',
            'username'    => '@femi_uniabuja',
            'raw_username'=> 'femi_uniabuja',
            'avatar'      => '🎒',
            'district'    => 'UniAbuja Campus',
            'last_message'=> 'Egbon urgent 2k abeg! Sapa dey catch me.',
            'time'        => '1h ago',
            'unread'      => 0,
            'online'      => true
        ],
        [
            'type'        => 'npc',
            'id'          => 'shawarma',
            'name'        => 'Banex Dispatch Rider',
            'username'    => '@banex_rider',
            'raw_username'=> 'banex_rider',
            'avatar'      => '🛵',
            'district'    => 'Banex Express Logistics',
            'last_message'=> 'Oga I don reach your estate security gate.',
            'time'        => '2h ago',
            'unread'      => 0,
            'online'      => false
        ]
    ];

    jsonResponse([
        'success'            => true,
        'user_threads'       => $userThreads,
        'npc_threads'        => $npcThreads,
        'available_citizens' => $availableCitizens,
        'my_username'        => '@' . ($char['username'] ?? '')
    ]);
}

// ---------------------------------------------------------
// 7. GET MESSAGES IN CONVERSATION THREAD
// ---------------------------------------------------------
if ($action === 'get_messages') {
    $targetUsername = trim(cleanInput($_GET['username'] ?? $_POST['username'] ?? ''));
    $targetUserId   = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

    if (!empty($targetUsername)) {
        $targetUsername = ltrim($targetUsername, '@');
        $uStmt = $pdo->prepare("
            SELECT u.id AS user_id, u.username, c.id AS character_id, c.full_name, c.district, c.avatar, c.cash, c.bank
            FROM users u
            LEFT JOIN characters c ON c.user_id = u.id
            WHERE LOWER(u.username) = LOWER(?)
            LIMIT 1
        ");
        $uStmt->execute([$targetUsername]);
        $targetUser = $uStmt->fetch();
        if ($targetUser) {
            $targetUserId = (int)$targetUser['user_id'];
        }
    } elseif ($targetUserId > 0) {
        $uStmt = $pdo->prepare("
            SELECT u.id AS user_id, u.username, c.id AS character_id, c.full_name, c.district, c.avatar, c.cash, c.bank
            FROM users u
            LEFT JOIN characters c ON c.user_id = u.id
            WHERE u.id = ?
            LIMIT 1
        ");
        $uStmt->execute([$targetUserId]);
        $targetUser = $uStmt->fetch();
    }

    if (!$targetUser || $targetUserId <= 0) {
        jsonResponse(['success' => false, 'error' => 'Citizen user not found.'], 404);
    }

    // Mark incoming messages as read
    $markStmt = $pdo->prepare("UPDATE user_messages SET is_read = 1 WHERE sender_id = ? AND recipient_id = ?");
    $markStmt->execute([$targetUserId, $userId]);

    // Fetch conversation history
    $msgStmt = $pdo->prepare("
        SELECT id, sender_id, recipient_id, message, is_read, created_at
        FROM user_messages
        WHERE (sender_id = :uid AND recipient_id = :target)
           OR (sender_id = :target AND recipient_id = :uid)
        ORDER BY id ASC
        LIMIT 60
    ");
    $msgStmt->execute([':uid' => $userId, ':target' => $targetUserId]);
    $messages = array_map(function($m) use ($userId) {
        return [
            'id'         => (int)$m['id'],
            'is_me'      => ((int)$m['sender_id'] === (int)$userId),
            'message'    => $m['message'],
            'time'       => date('h:i A', strtotime($m['created_at'])),
            'date'       => date('M d', strtotime($m['created_at'])),
            'is_read'    => (bool)$m['is_read']
        ];
    }, $msgStmt->fetchAll());

    jsonResponse([
        'success'      => true,
        'contact'      => [
            'user_id'      => (int)$targetUser['user_id'],
            'character_id' => (int)($targetUser['character_id'] ?? 0),
            'username'     => '@' . $targetUser['username'],
            'raw_username' => $targetUser['username'],
            'full_name'    => $targetUser['full_name'] ?: $targetUser['username'],
            'district'     => $targetUser['district'] ?? 'Abuja FCT',
            'avatar'       => $targetUser['avatar'] ?: 'assets/img/characters/tunde/face.png'
        ],
        'messages'     => $messages
    ]);
}

// ---------------------------------------------------------
// 8. SEND MESSAGE TO CITIZEN BY @USERNAME
// ---------------------------------------------------------
if ($action === 'send_message') {
    $targetUsername = trim(cleanInput($_POST['recipient_username'] ?? ''));
    $targetUserId   = (int)($_POST['recipient_id'] ?? 0);
    $text           = trim(cleanInput($_POST['message'] ?? ''));

    if (empty($text)) {
        jsonResponse(['success' => false, 'error' => 'Message text cannot be empty!'], 400);
    }

    if (!empty($targetUsername)) {
        $targetUsername = ltrim($targetUsername, '@');
        $uStmt = $pdo->prepare("
            SELECT u.id AS user_id, u.username, c.full_name
            FROM users u
            LEFT JOIN characters c ON c.user_id = u.id
            WHERE LOWER(u.username) = LOWER(?)
            LIMIT 1
        ");
        $uStmt->execute([$targetUsername]);
        $targetUser = $uStmt->fetch();
        if ($targetUser) {
            $targetUserId = (int)$targetUser['user_id'];
        }
    } elseif ($targetUserId > 0) {
        $uStmt = $pdo->prepare("
            SELECT u.id AS user_id, u.username, c.full_name
            FROM users u
            LEFT JOIN characters c ON c.user_id = u.id
            WHERE u.id = ?
            LIMIT 1
        ");
        $uStmt->execute([$targetUserId]);
        $targetUser = $uStmt->fetch();
    }

    if (!$targetUser || $targetUserId <= 0) {
        jsonResponse(['success' => false, 'error' => 'Recipient citizen @' . ($targetUsername ?: $targetUserId) . ' not found.'], 404);
    }

    if ($targetUserId === (int)$userId) {
        jsonResponse(['success' => false, 'error' => 'You cannot text yourself!'], 400);
    }

    // Insert user message
    $insStmt = $pdo->prepare("
        INSERT INTO user_messages (sender_id, recipient_id, message, is_read, created_at)
        VALUES (?, ?, ?, 0, datetime('now'))
    ");
    $insStmt->execute([$userId, $targetUserId, $text]);
    $newMsgId = (int)$pdo->lastInsertId();

    // Check if recipient is a system / automated NPC persona to provide dynamic replies
    $reply = null;
    $botReplies = [
        'Safe! How Abuja dey treat you?',
        'Omo hustle is real today o! Catch you around Wuse.',
        'Abuja no be beans my brother! Stay focused.',
        'Well received senior man! More blessings to your account.',
        'Haha valid! Let us connect properly over cold Lacasera soon.',
        'Nice one! Make sure you grind hard before NEPA take light.'
    ];

    // If it's a simulated peer citizen or demo account, send an authentic reply
    $isDemoTarget = in_array(strtolower($targetUser['username']), ['bossman', 'chidi', 'zainab', 'emeka', 'farouk', 'blessing', 'ibrahim', 'segun', 'ngozi']);
    if ($isDemoTarget) {
        $replyText = $botReplies[array_rand($botReplies)];
        $botIns = $pdo->prepare("
            INSERT INTO user_messages (sender_id, recipient_id, message, is_read, created_at)
            VALUES (?, ?, ?, 0, datetime('now', '+1 second'))
        ");
        $botIns->execute([$targetUserId, $userId, $replyText]);
        $reply = [
            'id'      => (int)$pdo->lastInsertId(),
            'is_me'   => false,
            'message' => $replyText,
            'time'    => date('h:i A'),
            'date'    => date('M d')
        ];
    }

    jsonResponse([
        'success' => true,
        'message_sent' => [
            'id'      => $newMsgId,
            'is_me'   => true,
            'message' => $text,
            'time'    => date('h:i A'),
            'date'    => date('M d')
        ],
        'auto_reply' => $reply
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid phone action.'], 400);

