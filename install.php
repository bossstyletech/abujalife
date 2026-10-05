<?php
require_once __DIR__ . '/config.php';

$message = '';
$status = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `" . DB_NAME . "`;");

        $sql = file_get_contents(__DIR__ . '/db.sql');
        $pdo->exec($sql);

        $status = 'success';
        $message = "Database `" . DB_NAME . "` successfully installed and seeded with Abuja Life data!";
    } catch (Exception $e) {
        $status = 'danger';
        $message = "Installation error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abuja Life - Database Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-slate-900 border border-emerald-500/30 rounded-2xl shadow-2xl p-6 sm:p-8 backdrop-blur">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-500/10 text-emerald-400 rounded-full mb-3 border border-emerald-500/20">
                <i class="fa-solid fa-city text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Abuja Life Setup</h1>
            <p class="text-sm text-slate-400 mt-1">Database Schema & Initial Game Seeding</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-xl text-sm font-medium <?= $status === 'success' ? 'bg-emerald-950/80 text-emerald-300 border border-emerald-500/40' : 'bg-rose-950/80 text-rose-300 border border-rose-500/40' ?>">
                <div class="flex items-center gap-2">
                    <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
                <?php if ($status === 'success'): ?>
                    <div class="mt-4">
                        <a href="index.php" class="inline-flex items-center justify-center w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-semibold transition">
                            Launch Abuja Life <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="bg-slate-800/60 rounded-xl p-4 border border-slate-700 mb-6 text-xs text-slate-300 space-y-2">
            <div class="flex justify-between">
                <span class="text-slate-400">Database Host:</span>
                <span class="font-mono"><?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Target Database:</span>
                <span class="font-mono text-emerald-400"><?= htmlspecialchars(DB_NAME) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Database User:</span>
                <span class="font-mono"><?= htmlspecialchars(DB_USER) ?></span>
            </div>
        </div>

        <form method="POST">
            <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-semibold rounded-xl shadow-lg shadow-emerald-950/40 transition active:scale-[0.98]">
                <i class="fa-solid fa-database"></i> Run Database Migration & Seed
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="index.php" class="text-xs text-slate-400 hover:text-emerald-400 transition">
                Skip to Home Page
            </a>
        </div>
    </div>
</body>
</html>
