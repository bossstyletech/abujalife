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

if ($action === 'start_shift') {
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

    $shiftDuration = 480; // 8 real-time minutes (480 seconds)
    $now = time();
    $shiftData = [
        'job_id' => (int)$job['id'],
        'started_at' => $now,
        'duration' => $shiftDuration,
        'energy_cost' => $energyCost,
        'base_salary' => (float)$job['daily_salary']
    ];

    $stmtSaveShift = $pdo->prepare("UPDATE characters SET active_shift = ? WHERE id = ?");
    $stmtSaveShift->execute([json_encode($shiftData), $char['id']]);

    jsonResponse([
        'success' => true,
        'job' => $job,
        'shift_duration_seconds' => $shiftDuration,
        'started_at' => $now,
        'energy_cost' => $energyCost,
        'base_salary' => (float)$job['daily_salary']
    ]);
}

if ($action === 'get_active_shift') {
    if (!empty($char['active_shift'])) {
        $s = json_decode($char['active_shift'], true);
        if (is_array($s) && isset($s['started_at'])) {
            $now = time();
            $elapsed = max(0, $now - (int)$s['started_at']);
            $duration = (int)($s['duration'] ?? 480);
            $remaining = max(0, $duration - $elapsed);
            $jobId = (int)($s['job_id'] ?? $char['current_job_id']);

            $stmtJob = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
            $stmtJob->execute([$jobId]);
            $job = $stmtJob->fetch();

            jsonResponse([
                'success' => true,
                'has_active_shift' => true,
                'job' => $job,
                'shift' => $s,
                'started_at' => (int)$s['started_at'],
                'elapsed_seconds' => $elapsed,
                'remaining_seconds' => $remaining,
                'duration' => $duration,
                'is_completed' => ($elapsed >= $duration)
            ]);
        }
    }
    jsonResponse(['success' => true, 'has_active_shift' => false]);
}

if ($action === 'finish_shift') {
    if (!$char['current_job_id']) {
        jsonResponse(['success' => false, 'error' => 'No active job.'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$char['current_job_id']]);
    $job = $stmt->fetch();

    if (!$job) {
        jsonResponse(['success' => false, 'error' => 'Job record not found.'], 404);
    }

    $energyCost = (int)$job['energy_cost'];
    $tips = max(0, (float)($_POST['tips'] ?? 0));
    $bonuses = max(0, (float)($_POST['bonuses'] ?? 0));
    $penalties = max(0, (float)($_POST['penalties'] ?? 0));

    $baseSalary = (float)$job['daily_salary'];
    $finalPay = max(0, $baseSalary + $tips + $bonuses - $penalties);

    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET cash = cash + ?, 
            energy = CASE WHEN energy - ? < 0 THEN 0 ELSE energy - ? END, 
            time_of_day = 'Evening', 
            intelligence = intelligence + 1,
            street_cred = street_cred + 2,
            happiness = CASE WHEN happiness + 5 > 100 THEN 100 ELSE happiness + 5 END,
            active_shift = NULL
        WHERE id = ?
    ");
    $stmtUpdate->execute([$finalPay, $energyCost, $energyCost, $char['id']]);

    $logMsg = "Completed full 8-minute workday as {$job['title']}. Base Salary: " . formatNaira($baseSalary);
    if ($tips > 0) $logMsg .= " + Tips: " . formatNaira($tips);
    if ($bonuses > 0) $logMsg .= " + Performance: " . formatNaira($bonuses);
    if ($penalties > 0) $logMsg .= " - Deductions: " . formatNaira($penalties);

    logActivity($char['id'], 'work_shift', $logMsg, $finalPay, -$energyCost, 5);

    jsonResponse([
        'success' => true,
        'message' => "Shift officially closed! Net take-home pay of " . formatNaira($finalPay) . " credited.",
        'final_pay' => $finalPay,
        'character' => getUserCharacter($userId)
    ]);
}

if ($action === 'abandon_shift') {
    if (!$char['current_job_id']) {
        jsonResponse(['success' => false, 'error' => 'No active job.'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$char['current_job_id']]);
    $job = $stmt->fetch();

    $progress = min(100, max(0, (float)($_POST['progress'] ?? 20)));
    $partialSalary = ($progress >= 50) ? round(((float)$job['daily_salary'] * ($progress / 100)) * 0.4, 2) : 0;
    $energyCost = round((int)$job['energy_cost'] * ($progress / 100));

    $stmtUpdate = $pdo->prepare("
        UPDATE characters 
        SET cash = cash + ?, 
            energy = CASE WHEN energy - ? < 0 THEN 0 ELSE energy - ? END, 
            street_cred = CASE WHEN street_cred - 12 < 0 THEN 0 ELSE street_cred - 12 END,
            happiness = CASE WHEN happiness - 15 < 0 THEN 0 ELSE happiness - 15 END,
            time_of_day = 'Evening',
            active_shift = NULL
        WHERE id = ?
    ");
    $stmtUpdate->execute([$partialSalary, $energyCost, $energyCost, $char['id']]);

    $msg = "You sneaked out of work at {$progress}% of shift! Oga issued a formal query! -12 Street Cred.";
    logActivity($char['id'], 'work_abandon', $msg, $partialSalary, -$energyCost, -15);

    jsonResponse([
        'success' => true,
        'message' => $msg,
        'partial_pay' => $partialSalary,
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
        SET cash = cash + ?, energy = energy - ?, happiness = CASE WHEN happiness - 2 < 0 THEN 0 ELSE happiness - 2 END, intelligence = intelligence + 1
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
