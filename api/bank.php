<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();
$action = cleanInput($_GET['action'] ?? $_POST['action'] ?? 'status');
$pdo = getDbConnection();
$char = getUserCharacter($userId);

if (!$char) {
    jsonResponse(['success' => false, 'error' => 'Character not found.'], 404);
}

if ($action === 'status') {
    $maxLoan = max(50000, (int)$char['street_cred'] * 25000);
    jsonResponse([
        'success' => true,
        'cash' => (float)$char['cash'],
        'bank' => (float)$char['bank'],
        'loan_balance' => (float)$char['loan_balance'],
        'max_loan_limit' => $maxLoan
    ]);
}

if ($action === 'deposit') {
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount <= 0) {
        jsonResponse(['success' => false, 'error' => 'Enter a valid amount to deposit.'], 400);
    }

    if ((float)$char['cash'] < $amount) {
        jsonResponse(['success' => false, 'error' => 'You do not have enough cash in hand to deposit.'], 400);
    }

    $stmt = $pdo->prepare("UPDATE characters SET cash = cash - ?, bank = bank + ? WHERE id = ?");
    $stmt->execute([$amount, $amount, $char['id']]);

    logActivity($char['id'], 'bank_deposit', "Deposited " . formatNaira($amount) . " into your First Bank / GTBank Abuja account.", -$amount, 0, 0);

    jsonResponse([
        'success' => true,
        'message' => "Successfully deposited " . formatNaira($amount) . " into your bank account.",
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'withdraw') {
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount <= 0) {
        jsonResponse(['success' => false, 'error' => 'Enter a valid amount to withdraw.'], 400);
    }

    if ((float)$char['bank'] < $amount) {
        jsonResponse(['success' => false, 'error' => 'Insufficient funds in your bank account.'], 400);
    }

    $stmt = $pdo->prepare("UPDATE characters SET bank = bank - ?, cash = cash + ? WHERE id = ?");
    $stmt->execute([$amount, $amount, $char['id']]);

    logActivity($char['id'], 'bank_withdraw', "Withdrew " . formatNaira($amount) . " in cash from Central Area ATM.", $amount, 0, 0);

    jsonResponse([
        'success' => true,
        'message' => "Withdrew " . formatNaira($amount) . " in cash.",
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'take_loan') {
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount <= 0) {
        jsonResponse(['success' => false, 'error' => 'Enter a valid loan amount.'], 400);
    }

    $maxLoan = max(50000, (int)$char['street_cred'] * 25000);
    $currentLoan = (float)$char['loan_balance'];

    if ($currentLoan + $amount > $maxLoan) {
        jsonResponse([
            'success' => false, 
            'error' => "Loan limit exceeded! Based on your Street Cred ({$char['street_cred']}), your credit ceiling is " . formatNaira($maxLoan) . ". Outstanding balance is " . formatNaira($currentLoan) . "."
        ], 400);
    }

    $stmt = $pdo->prepare("UPDATE characters SET cash = cash + ?, loan_balance = loan_balance + ? WHERE id = ?");
    $stmt->execute([$amount, $amount, $char['id']]);

    logActivity($char['id'], 'loan_taken', "Disbursed " . formatNaira($amount) . " bank loan into your cash reserves.", $amount, 0, 0);

    jsonResponse([
        'success' => true,
        'message' => "Loan approved! " . formatNaira($amount) . " has been credited to your cash.",
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'repay_loan') {
    $amount = (float)($_POST['amount'] ?? 0);
    $currentLoan = (float)$char['loan_balance'];

    if ($currentLoan <= 0) {
        jsonResponse(['success' => false, 'error' => 'You do not have any outstanding loan.'], 400);
    }

    if ($amount <= 0 || $amount > $currentLoan) {
        $amount = $currentLoan; // Repay all if invalid or exceeded
    }

    if ((float)$char['cash'] < $amount) {
        jsonResponse(['success' => false, 'error' => 'You do not have enough cash to repay this loan amount.'], 400);
    }

    $stmt = $pdo->prepare("UPDATE characters SET cash = cash - ?, loan_balance = loan_balance - ? WHERE id = ?");
    $stmt->execute([$amount, $amount, $char['id']]);

    logActivity($char['id'], 'loan_repaid', "Repaid " . formatNaira($amount) . " loan debt.", -$amount, 0, 2);

    jsonResponse([
        'success' => true,
        'message' => "Repaid " . formatNaira($amount) . " towards your debt. Credit score improved!",
        'character' => getUserCharacter($userId)
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
