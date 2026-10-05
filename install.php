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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Abuja Life | Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-slate-800 text-emerald-400 rounded-2xl mb-3">
                <i class="fa-solid fa-database text-lg"></i>
            </div>
            <h1 class="text-xl font-bold text-white tracking-tight">Abuja Life Setup</h1>
            <p class="text-xs text-slate-400 mt-1">Database Schema & Initial Game Seeding</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-2xl text-xs font-medium <?= $status === 'success' ? 'bg-slate-950 text-emerald-300 border border-emerald-600/50' : 'bg-slate-950 text-rose-300 border border-rose-600/50' ?>">
                <div class="flex items-center gap-2">
                    <i class="fa-solid <?= $status === 'success' ? 'fa-circle-check text-emerald-400' : 'fa-circle-exclamation text-rose-400' ?>"></i>
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

        <div class="bg-slate-950 rounded-2xl p-4 border border-slate-800/80 mb-6 text-xs text-slate-300 space-y-2">
            <div class="flex justify-between">
                <span class="text-slate-400">Database Host:</span>
                <span class="font-mono text-slate-200"><?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Target Database:</span>
                <span class="font-mono text-emerald-400"><?= htmlspecialchars(DB_NAME) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Database User:</span>
                <span class="font-mono text-slate-200"><?= htmlspecialchars(DB_USER) ?></span>
            </div>
        </div>

        <form method="POST">
            <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-2xl text-xs shadow-sm transition active:scale-95">
                <i class="fa-solid fa-play text-xs"></i> Run Database Migration & Seed
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="index.php" class="text-xs text-slate-400 hover:text-white transition">
                Return to Home
            </a>
        </div>
    </div>
</body>
</html>
