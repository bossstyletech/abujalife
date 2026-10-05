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
    $stmt = $pdo->query("SELECT * FROM jobs ORDER BY daily_salary ASC");
    $jobs = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'current_job_id' => $char['current_job_id'],
        'jobs' => $jobs
    ]);
}

if ($action === 'apply') {
    $jobId = (int)($_POST['job_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();

    if (!$job) {
        jsonResponse(['success' => false, 'error' => 'Job not found.'], 404);
    }

    // Check intelligence requirement
    if ((int)$char['intelligence'] < (int)$job['required_intelligence']) {
        jsonResponse([
            'success' => false, 
            'error' => "Interview failed! You need at least {$job['required_intelligence']} Intelligence for this role. Your current Intelligence is {$char['intelligence']}."
        ], 400);
    }

    // Check education tier hierarchy
    $educationRank = [
        'SSCE' => 1,
        'OND' => 2,
        'TechCert' => 3,
        'BSc' => 4,
        'MSc' => 5
    ];
    $userRank = $educationRank[$char['education_level']] ?? 1;
    $reqRank = $educationRank[$job['required_education']] ?? 1;

    if ($userRank < $reqRank) {
        jsonResponse([
            'success' => false, 
            'error' => "Qualifications insufficient! Required: {$job['required_education']}. Your current qualification: {$char['education_level']}."
        ], 400);
    }

    // Update job
    $stmtUpdate = $pdo->prepare("UPDATE characters SET current_job_id = ? WHERE id = ?");
    $stmtUpdate->execute([$jobId, $char['id']]);

    logActivity($char['id'], 'job_hired', "Congratulations! You got hired as {$job['title']} in Abuja with daily pay of " . formatNaira($job['daily_salary']) . "!", 0, 0, 15);

    jsonResponse([
        'success' => true,
        'message' => "Congratulations! You are now employed as a {$job['title']}.",
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'work') {
    if (!$char['current_job_id']) {
        jsonResponse(['success' => false, 'error' => 'You currently do not have a job. Apply for an available role first!'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$char['current_job_id']]);
    $job = $stmt->fetch();

    if (!$job) {
        jsonResponse(['success' => false, 'error' => 'Job record not found.'], 404);
    }

    $energyCost = (int)$job['energy_cost'];
    if ((int)$char['energy'] < $energyCost) {
        jsonResponse(['success' => false, 'error' => "You are too exhausted to work this shift! Required energy: {$energyCost}%. Take a nap or rest."], 400);
    }

    $salary = (float)$job['daily_salary'];
    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET cash = cash + ?, energy = energy - ?, happiness = GREATEST(0, happiness - 2), intelligence = intelligence + 1
        WHERE id = ?
    ");
    $stmtUpdate->execute([$salary, $energyCost, $char['id']]);

    logActivity($char['id'], 'work_shift', "Worked shift as {$job['title']}. Earned " . formatNaira($salary) . ".", $salary, -$energyCost, -2);

    jsonResponse([
        'success' => true,
        'message' => "Shift completed as {$job['title']}! Received " . formatNaira($salary) . ".",
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'resign') {
    $stmt = $pdo->prepare("UPDATE characters SET current_job_id = NULL WHERE id = ?");
    $stmt->execute([$char['id']]);

    logActivity($char['id'], 'job_resign', "You resigned from your job.", 0, 0, 0);

    jsonResponse([
        'success' => true,
        'message' => 'You have resigned from your current position.',
        'character' => getUserCharacter($userId)
    ]);
}

jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
