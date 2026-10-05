<?php
require_once __DIR__ . '/config.php';

$message = '';
$status = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    try {
        if (!HAS_MYSQL_CONFIG || (defined('DB_DRIVER') && DB_DRIVER === 'sqlite')) {
            $sqliteFile = __DIR__ . '/database.sqlite';
            $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $sql = file_get_contents(__DIR__ . '/db_sqlite.sql');
            $pdo->exec($sql);
            $status = 'success';
            $message = "Local SQLite database successfully installed and seeded with Abuja Life data!";
        } else {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `" . DB_NAME . "`;");
            $sql = file_get_contents(__DIR__ . '/db.sql');
            $pdo->exec($sql);
            $status = 'success';
            $message = "MySQL Database `" . DB_NAME . "` successfully installed and seeded with Abuja Life data!";
        }
    } catch (Exception $e) {
        $status = 'danger';
        $message = "Installation notice: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Abuja Life | Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-4 antialiased">
    <div class="max-w-md w-full bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl mb-3 border border-emerald-100">
                <i class="fa-solid fa-database text-lg"></i>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Abuja Life Setup</h1>
            <p class="text-xs text-slate-500 mt-1">Database Schema & Initial Game Seeding</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-2xl text-xs font-medium <?= $status === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
                <div class="flex items-center gap-2">
                    <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?>"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
                <?php if ($status === 'success'): ?>
                    <div class="mt-3">
                        <a href="index.php" class="inline-flex items-center justify-center w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-semibold transition active:scale-95 shadow-sm">
                            Launch Abuja Life <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 mb-6 text-xs text-slate-700 space-y-2">
            <div class="flex justify-between">
                <span class="text-slate-500">Database Driver:</span>
                <span class="font-bold text-slate-900"><?= defined('DB_DRIVER') ? strtoupper(DB_DRIVER) : 'AUTO (MySQL / SQLite)' ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Database Host:</span>
                <span class="font-mono text-slate-900"><?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Database Name:</span>
                <span class="font-mono text-emerald-700 font-bold"><?= htmlspecialchars(DB_NAME) ?></span>
            </div>
        </div>

        <form method="POST">
            <button type="submit" class="w-full flex items-center justify-center gap-2 py-3.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95">
                <i class="fa-solid fa-play text-xs"></i> Run Database Migration & Seed
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="index.php" class="text-xs text-slate-500 hover:text-slate-900 transition">
                Return to Game Home
            </a>
        </div>
    </div>
</body>
</html>
