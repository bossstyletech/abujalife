<?php
require_once __DIR__ . '/../config.php';

$pdo = getDbConnection();

$stmt = $pdo->query("
    SELECT c.id, c.full_name, c.gender, c.district, c.street_cred, c.days_lived, c.education_level,
           (c.cash + c.bank - c.loan_balance + 
            COALESCE((SELECT SUM(p.price) FROM character_properties cp JOIN properties p ON cp.property_id = p.id WHERE cp.character_id = c.id), 0) +
            COALESCE((SELECT SUM(v.price) FROM character_vehicles cv JOIN vehicles v ON cv.vehicle_id = v.id WHERE cv.character_id = c.id), 0)
           ) AS calculated_net_worth,
           j.title AS job_title
    FROM characters c
    LEFT JOIN jobs j ON c.current_job_id = j.id
    WHERE c.is_alive = 1
    ORDER BY calculated_net_worth DESC
    LIMIT 20
");
$richest = $stmt->fetchAll();

$stmtCred = $pdo->query("
    SELECT c.id, c.full_name, c.gender, c.district, c.street_cred, c.days_lived,
           j.title AS job_title
    FROM characters c
    LEFT JOIN jobs j ON c.current_job_id = j.id
    WHERE c.is_alive = 1
    ORDER BY c.street_cred DESC
    LIMIT 20
");
$streetKings = $stmtCred->fetchAll();

jsonResponse([
    'success' => true,
    'richest' => $richest,
    'street_kings' => $streetKings
]);
