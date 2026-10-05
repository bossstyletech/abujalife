<?php
require_once __DIR__ . '/config.php';

$userId = getAuthUserId();
if (!$userId) {
    header('Location: index.php');
    exit;
}
$char = getUserCharacter($userId);
if (!$char) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abuja Life | Live, Hustle, Rule</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        /* Custom scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #090d16; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #334155; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col selection:bg-emerald-500 selection:text-white">

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="fixed top-5 right-5 flex flex-col gap-2 z-50 pointer-events-none"></div>

    <!-- Header Navigation -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/20">
                    <i class="fa-solid fa-mountain-sun"></i>
                </div>
                <div>
                    <h1 class="text-lg font-black tracking-tight text-white flex items-center gap-2">
                        ABUJA <span class="text-emerald-400">LIFE</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-400 border border-emerald-800/80 uppercase tracking-widest font-bold">FCT</span>
                    </h1>
                </div>
            </div>

            <!-- Quick Life Day Controls -->
            <div class="flex items-center gap-2 sm:gap-3">
                <button onclick="GameApp.sleepRest()" title="Sleep and restore 100% Energy" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center gap-1.5 border border-slate-700 transition">
                    <i class="fa-solid fa-bed text-indigo-400"></i> <span class="hidden sm:inline">Rest</span>
                </button>
                <button onclick="GameApp.visitHospital()" title="Check in to National Hospital Abuja" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center gap-1.5 border border-slate-700 transition">
                    <i class="fa-solid fa-hospital text-rose-400"></i> <span class="hidden sm:inline">Hospital</span>
                </button>
                <button onclick="GameApp.advanceDay()" class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs shadow-lg shadow-emerald-950/40 flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-forward-step"></i> Advance Day
                </button>
                <button onclick="GameApp.logout()" title="Logout" class="w-9 h-9 rounded-xl bg-slate-800/80 hover:bg-rose-950 hover:text-rose-400 text-slate-400 flex items-center justify-center border border-slate-700 transition">
                    <i class="fa-solid fa-power-off text-xs"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Master HUD (Stats & Vitals) -->
    <section class="bg-slate-900/60 border-b border-slate-800/80 px-4 sm:px-6 lg:px-8 py-4 backdrop-blur">
        <div class="max-w-7xl mx-auto">
            <!-- Top Line: Identity & Financials -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 mb-4">
                <!-- Character & Age -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div class="overflow-hidden">
                        <span id="hudName" class="block font-bold text-xs text-white truncate"><?= htmlspecialchars($char['full_name']) ?></span>
                        <span id="hudAge" class="text-[11px] text-slate-400 block"><?= $char['age'] ?> yrs (Day <?= $char['days_lived'] ?>)</span>
                    </div>
                </div>

                <!-- District Location -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-400 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-map-pin"></i>
                    </div>
                    <div class="overflow-hidden">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">District</span>
                        <span id="hudDistrict" class="font-bold text-xs text-teal-300 truncate"><?= htmlspecialchars($char['district']) ?></span>
                    </div>
                </div>

                <!-- Cash in Hand -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="overflow-hidden">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Cash</span>
                        <span id="hudCash" class="font-mono font-bold text-xs text-emerald-400 truncate">₦<?= number_format($char['cash'], 2) ?></span>
                    </div>
                </div>

                <!-- Bank Savings -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div class="overflow-hidden">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Bank</span>
                        <span id="hudBank" class="font-mono font-bold text-xs text-blue-300 truncate">₦<?= number_format($char['bank'], 2) ?></span>
                    </div>
                </div>

                <!-- Estimated Net Worth -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div class="overflow-hidden">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Net Worth</span>
                        <span id="hudNetWorth" class="font-mono font-bold text-xs text-amber-300 truncate">Loading...</span>
                    </div>
                </div>

                <!-- Current Job -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <div class="overflow-hidden">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Career</span>
                        <span id="hudJob" class="font-bold text-xs text-purple-300 truncate"><?= htmlspecialchars($char['job_title'] ?: 'Unemployed') ?></span>
                    </div>
                </div>
            </div>

            <!-- Vitals Progress Bars -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <!-- Energy -->
                <div class="bg-slate-900/80 border border-slate-800 px-3 py-2 rounded-xl">
                    <div class="flex justify-between text-[11px] font-semibold mb-1">
                        <span class="text-amber-400"><i class="fa-solid fa-bolt mr-1"></i> Energy</span>
                        <span id="valEnergy" class="text-slate-300 font-mono"><?= $char['energy'] ?>%</span>
                    </div>
                    <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden">
                        <div id="barEnergy" class="bg-amber-400 h-full rounded-full transition-all duration-300" style="width: <?= $char['energy'] ?>%"></div>
                    </div>
                </div>

                <!-- Health -->
                <div class="bg-slate-900/80 border border-slate-800 px-3 py-2 rounded-xl">
                    <div class="flex justify-between text-[11px] font-semibold mb-1">
                        <span class="text-rose-400"><i class="fa-solid fa-heart mr-1"></i> Health</span>
                        <span id="valHealth" class="text-slate-300 font-mono"><?= $char['health'] ?>%</span>
                    </div>
                    <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden">
                        <div id="barHealth" class="bg-rose-500 h-full rounded-full transition-all duration-300" style="width: <?= $char['health'] ?>%"></div>
                    </div>
                </div>

                <!-- Happiness -->
                <div class="bg-slate-900/80 border border-slate-800 px-3 py-2 rounded-xl">
                    <div class="flex justify-between text-[11px] font-semibold mb-1">
                        <span class="text-emerald-400"><i class="fa-solid fa-face-smile mr-1"></i> Happiness</span>
                        <span id="valHappiness" class="text-slate-300 font-mono"><?= $char['happiness'] ?>%</span>
                    </div>
                    <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden">
                        <div id="barHappiness" class="bg-emerald-400 h-full rounded-full transition-all duration-300" style="width: <?= $char['happiness'] ?>%"></div>
                    </div>
                </div>

                <!-- Intelligence -->
                <div class="bg-slate-900/80 border border-slate-800 px-3 py-2 rounded-xl">
                    <div class="flex justify-between text-[11px] font-semibold mb-1">
                        <span class="text-blue-400"><i class="fa-solid fa-brain mr-1"></i> Intelligence</span>
                        <span id="valIntelligence" class="text-slate-300 font-mono"><?= $char['intelligence'] ?> IQ</span>
                    </div>
                    <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden">
                        <div id="barIntelligence" class="bg-blue-400 h-full rounded-full transition-all duration-300" style="width: <?= $char['intelligence'] ?>%"></div>
                    </div>
                </div>

                <!-- Street Cred -->
                <div class="col-span-2 sm:col-span-1 bg-slate-900/80 border border-slate-800 px-3 py-2 rounded-xl">
                    <div class="flex justify-between text-[11px] font-semibold mb-1">
                        <span class="text-purple-400"><i class="fa-solid fa-shield-halved mr-1"></i> Street Cred</span>
                        <span id="valStreetCred" class="text-slate-300 font-mono"><?= $char['street_cred'] ?></span>
                    </div>
                    <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden">
                        <div id="barStreetCred" class="bg-purple-500 h-full rounded-full transition-all duration-300" style="width: <?= min(100, $char['street_cred']) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Navigation Bar -->
    <nav class="border-b border-slate-800 bg-slate-900/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-1 overflow-x-auto py-2.5 text-xs font-bold whitespace-nowrap">
                <button id="btn-tab-overview" onclick="GameApp.switchTab('overview')" class="tab-btn px-3 py-2 rounded-xl border flex items-center gap-2 transition">
                    <i class="fa-solid fa-house"></i> Overview
                </button>
                <button id="btn-tab-jobs" onclick="GameApp.switchTab('jobs')" class="tab-btn px-3 py-2 rounded-xl border flex items-center gap-2 transition">
                    <i class="fa-solid fa-briefcase"></i> Careers
                </button>
                <button id="btn-tab-hustles" onclick="GameApp.switchTab('hustles')" class="tab-btn px-3 py-2 rounded-xl border flex items-center gap-2 transition">
                    <i class="fa-solid fa-bolt"></i> Hustles
                </button>
                <button id="btn-tab-education" onclick="GameApp.switchTab('education')" class="tab-btn px-3 py-2 rounded-xl border flex items-center gap-2 transition">
                    <i class="fa-solid fa-graduation-cap"></i> Education
                </button>
                <button id="btn-tab-realestate" onclick="GameApp.switchTab('realestate')" class="tab-btn px-3 py-2 rounded-xl border flex items-center gap-2 transition">
                    <i class="fa-solid fa-building"></i> Real Estate
                </button>
                <button id="btn-tab-vehicles" onclick="GameApp.switchTab('vehicles')" class="tab-btn px-3 py-2 rounded-xl border flex items-center gap-2 transition">
                    <i class="fa-solid fa-car-side"></i> Garage
                </button>
                <button id="btn-tab-lifestyle" onclick="GameApp.switchTab('lifestyle')" class="tab-btn px-3 py-2 rounded-xl border flex items-center gap-2 transition">
                    <i class="fa-solid fa-champagne-glasses"></i> Lifestyle
                </button>
                <button id="btn-tab-bank" onclick="GameApp.switchTab('bank')" class="tab-btn px-3 py-2 rounded-xl border flex items-center gap-2 transition">
                    <i class="fa-solid fa-landmark"></i> Bank & Loans
                </button>
                <button id="btn-tab-casino" onclick="GameApp.switchTab('casino')" class="tab-btn px-3 py-2 rounded-xl border flex items-center gap-2 transition">
                    <i class="fa-solid fa-dice"></i> Abuja Bet
                </button>
                <button id="btn-tab-districts" onclick="GameApp.switchTab('districts')" class="tab-btn px-3 py-2 rounded-xl border flex items-center gap-2 transition">
                    <i class="fa-solid fa-map-location-dot"></i> Districts
                </button>
                <button id="btn-tab-leaderboard" onclick="GameApp.switchTab('leaderboard')" class="tab-btn px-3 py-2 rounded-xl border flex items-center gap-2 transition">
                    <i class="fa-solid fa-trophy"></i> Leaderboard
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Workspace / Active Tab -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- 1. OVERVIEW TAB -->
        <section id="tab-overview" class="game-tab-content">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Assets & Profile Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                    <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-id-card text-emerald-400"></i> Abuja Citizen Dossier
                    </h3>

                    <div class="space-y-4 text-xs">
                        <div class="flex justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Current Residence</span>
                            <span id="hudHouse" class="font-bold text-white"><?= htmlspecialchars($char['property_name'] ?: 'Renting Self-Con') ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Active Vehicle</span>
                            <span id="hudVehicle" class="font-bold text-white"><?= htmlspecialchars($char['vehicle_name'] ?: 'Public Transport') ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Education Degree</span>
                            <span id="hudEducation" class="font-bold text-emerald-400"><?= htmlspecialchars($char['education_level']) ?></span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Moral Karma</span>
                            <span class="font-bold text-teal-300"><?= $char['karma'] ?>/100</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-slate-800">
                            <span class="text-slate-400">Legal Record</span>
                            <span class="font-bold <?= $char['jail_days'] > 0 ? 'text-rose-400' : 'text-emerald-400' ?>">
                                <?= $char['jail_days'] > 0 ? "Serving {$char['jail_days']} days detention" : "Clean Citizen" ?>
                            </span>
                        </div>
                    </div>

                    <!-- Quick Action Shortcuts -->
                    <div class="mt-6 pt-4 border-t border-slate-800 grid grid-cols-2 gap-2">
                        <button onclick="GameApp.switchTab('hustles')" class="py-2.5 px-3 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 font-bold text-xs rounded-xl border border-amber-500/30 transition text-center">
                            <i class="fa-solid fa-bolt mb-1 block"></i> Fast Hustle
                        </button>
                        <button onclick="GameApp.switchTab('lifestyle')" class="py-2.5 px-3 bg-teal-500/10 hover:bg-teal-500/20 text-teal-300 font-bold text-xs rounded-xl border border-teal-500/30 transition text-center">
                            <i class="fa-solid fa-fire-burner mb-1 block"></i> Chill at Park
                        </button>
                    </div>
                </div>

                <!-- Recent Activities Stream -->
                <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-400 flex items-center gap-2">
                                <i class="fa-solid fa-newspaper text-emerald-400"></i> FCT Activity & Event Feed
                            </h3>
                            <button onclick="GameApp.fetchCharacter()" class="text-xs text-slate-400 hover:text-emerald-400 transition">
                                <i class="fa-solid fa-rotate-right mr-1"></i> Refresh
                            </button>
                        </div>
                        <div id="recentLogsList" class="space-y-1 overflow-y-auto max-h-[380px] pr-2">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. CAREERS TAB -->
        <section id="tab-jobs" class="game-tab-content hidden">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-xl font-extrabold text-white">Employment & Civil Service</h2>
                    <p class="text-xs text-slate-400">Apply for positions across ministries, tech hubs, corporate banking, and executive boards.</p>
                </div>
            </div>
            <div id="jobsListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- Populated via API -->
            </div>
        </section>

        <!-- 3. HUSTLES TAB -->
        <section id="tab-hustles" class="game-tab-content hidden">
            <div class="mb-6">
                <h2 class="text-xl font-extrabold text-white">Street Hustles & Side Gigs</h2>
                <p class="text-xs text-slate-400">Quick ways to generate cash in Abuja. Balance capital requirements with risk.</p>
            </div>
            <div id="hustlesListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- Populated via API -->
            </div>
        </section>

        <!-- 4. EDUCATION TAB -->
        <section id="tab-education" class="game-tab-content hidden">
            <div class="mb-6">
                <h2 class="text-xl font-extrabold text-white">Higher Learning & Academies</h2>
                <p class="text-xs text-slate-400">Upgrade your credentials and IQ to unlock higher-paying executive careers.</p>
            </div>
            <div id="educationListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- Populated via API -->
            </div>
        </section>

        <!-- 5. REAL ESTATE TAB -->
        <section id="tab-realestate" class="game-tab-content hidden space-y-8">
            <div>
                <h2 class="text-xl font-extrabold text-white mb-1">Your Property Portfolio</h2>
                <p class="text-xs text-slate-400 mb-4">Properties you own in Abuja. Toggle rent out to generate automatic daily passive income.</p>
                <div id="propertiesOwnedContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <!-- Populated via API -->
                </div>
            </div>

            <div class="pt-6 border-t border-slate-800">
                <h2 class="text-xl font-extrabold text-white mb-1">Abuja Real Estate Market</h2>
                <p class="text-xs text-slate-400 mb-4">Prime acquisitions across Kubwa, Lugbe, Gwarinpa, Wuse 2, Maitama, and Asokoro.</p>
                <div id="propertiesMarketContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <!-- Populated via API -->
                </div>
            </div>
        </section>

        <!-- 6. VEHICLES / GARAGE TAB -->
        <section id="tab-vehicles" class="game-tab-content hidden space-y-8">
            <div>
                <h2 class="text-xl font-extrabold text-white mb-1">Your Garage Fleet</h2>
                <p class="text-xs text-slate-400 mb-4">Take your cars out for cruises to elevate your street cred and happiness across the capital.</p>
                <div id="vehiclesOwnedContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <!-- Populated via API -->
                </div>
            </div>

            <div class="pt-6 border-t border-slate-800">
                <h2 class="text-xl font-extrabold text-white mb-1">Abuja Automobile Showroom</h2>
                <p class="text-xs text-slate-400 mb-4">From everyday Japanese workhorses to armored luxury convoys.</p>
                <div id="vehiclesMarketContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <!-- Populated via API -->
                </div>
            </div>
        </section>

        <!-- 7. LIFESTYLE TAB -->
        <section id="tab-lifestyle" class="game-tab-content hidden">
            <div class="mb-6">
                <h2 class="text-xl font-extrabold text-white">Abuja Hotspots & Leisure</h2>
                <p class="text-xs text-slate-400">Recharge happiness, boost social status, and stay fit across the city's finest parks, clubs, and lounges.</p>
            </div>
            <div id="lifestyleListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- Populated via API -->
            </div>
        </section>

        <!-- 8. BANK & LOANS TAB -->
        <section id="tab-bank" class="game-tab-content hidden">
            <div class="mb-6">
                <h2 class="text-xl font-extrabold text-white">Central Banking & Credit</h2>
                <p class="text-xs text-slate-400">Safeguard cash, earn daily savings interest, or apply for credit facilities.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                    <span class="text-xs text-slate-400 block font-semibold mb-1">Cash in Hand</span>
                    <span id="bankDisplayCash" class="font-mono font-bold text-2xl text-emerald-400">₦0.00</span>
                </div>
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                    <span class="text-xs text-slate-400 block font-semibold mb-1">Savings Account (Yields +0.05%/day)</span>
                    <span id="bankDisplaySavings" class="font-mono font-bold text-2xl text-blue-400">₦0.00</span>
                </div>
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                    <span class="text-xs text-slate-400 block font-semibold mb-1">Outstanding Bank Debt</span>
                    <span id="bankDisplayLoan" class="font-mono font-bold text-2xl text-rose-400">₦0.00</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Deposit / Withdraw Box -->
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                    <h3 class="font-bold text-sm text-white mb-4"><i class="fa-solid fa-money-bill-transfer text-emerald-400 mr-2"></i> Savings Operations</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Amount to Deposit (Cash -> Bank)</label>
                            <div class="flex gap-2">
                                <input type="number" id="bankDepositInput" placeholder="Amount in ₦" class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                                <button onclick="GameApp.bankDeposit()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl transition">
                                    Deposit
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Amount to Withdraw (Bank -> Cash)</label>
                            <div class="flex gap-2">
                                <input type="number" id="bankWithdrawInput" placeholder="Amount in ₦" class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                                <button onclick="GameApp.bankWithdraw()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl border border-slate-700 transition">
                                    Withdraw
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loan Facility Box -->
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                    <h3 class="font-bold text-sm text-white mb-2"><i class="fa-solid fa-landmark text-amber-400 mr-2"></i> Commercial Bank Loan</h3>
                    <p class="text-xs text-slate-400 mb-4">Credit line is tied to Street Cred. Max eligible: <strong id="bankMaxLoanLimit" class="text-emerald-400">₦0.00</strong></p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Request Soft Loan</label>
                            <div class="flex gap-2">
                                <input type="number" id="bankLoanInput" placeholder="Loan amount" class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                                <button onclick="GameApp.takeBankLoan()" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-slate-950 font-extrabold text-xs rounded-xl transition">
                                    Apply
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Repay Outstanding Loan</label>
                            <div class="flex gap-2">
                                <input type="number" id="bankRepayInput" placeholder="Repayment amount" class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                                <button onclick="GameApp.repayBankLoan()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-rose-300 font-bold text-xs rounded-xl border border-slate-700 transition">
                                    Repay
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 9. CASINO & BETTING TAB -->
        <section id="tab-casino" class="game-tab-content hidden">
            <div class="mb-6">
                <h2 class="text-xl font-extrabold text-white">Abuja Bet & Lucky Games</h2>
                <p class="text-xs text-slate-400">Try your luck on sports accumulator tickets or table dice.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sports Betting Ticket -->
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-futbol"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-white">Weekend Premier League Accumulator</h3>
                            <span class="text-xs text-slate-400">Pick your risk appetite and place your stake</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Stake Amount</label>
                        <input type="number" id="betStakeInput" value="5000" min="500" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2.5 text-xs text-white">
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <button onclick="GameApp.playSportsBet('safe')" class="py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold border border-slate-700 transition text-center">
                            Safe (1.6x)
                        </button>
                        <button onclick="GameApp.playSportsBet('medium')" class="py-2.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 rounded-xl text-xs font-bold border border-amber-500/40 transition text-center">
                            Medium (3.2x)
                        </button>
                        <button onclick="GameApp.playSportsBet('high')" class="py-2.5 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 rounded-xl text-xs font-bold border border-rose-500/40 transition text-center">
                            Jumbo (12.0x)
                        </button>
                    </div>
                </div>

                <!-- Lucky Dice Table -->
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-dice"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-white">Wuse 2 Casino Dice Roll</h3>
                            <span class="text-xs text-slate-400">Two dice roll. Predict sum range!</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Dice Stake</label>
                        <input type="number" id="diceStakeInput" value="5000" min="500" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2.5 text-xs text-white">
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <button onclick="GameApp.rollDiceGame('low')" class="py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold border border-slate-700 transition text-center">
                            Low (2-6) • 2x
                        </button>
                        <button onclick="GameApp.rollDiceGame('seven')" class="py-2.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 rounded-xl text-xs font-bold border border-amber-500/40 transition text-center">
                            Lucky 7 • 5x
                        </button>
                        <button onclick="GameApp.rollDiceGame('high')" class="py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold border border-slate-700 transition text-center">
                            High (8-12) • 2x
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- 10. DISTRICTS TAB -->
        <section id="tab-districts" class="game-tab-content hidden">
            <div class="mb-6">
                <h2 class="text-xl font-extrabold text-white">Abuja Districts & Relocation</h2>
                <p class="text-xs text-slate-400">Move your base of operation to higher status enclaves as your street cred grows.</p>
            </div>
            <div id="districtsListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Populated via API -->
            </div>
        </section>

        <!-- 11. LEADERBOARD TAB -->
        <section id="tab-leaderboard" class="game-tab-content hidden">
            <div class="mb-6">
                <h2 class="text-xl font-extrabold text-white">Abuja Hall of Fame</h2>
                <p class="text-xs text-slate-400">The most influential and wealthiest citizens in the Federal Capital Territory.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                    <h3 class="font-extrabold text-sm text-emerald-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-crown text-amber-400"></i> Top 20 Richest Citizens
                    </h3>
                    <div id="leaderboardRichest" class="space-y-1">
                        <!-- Populated via API -->
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
                    <h3 class="font-extrabold text-sm text-amber-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-fire text-rose-400"></i> Street Cred Dons
                    </h3>
                    <div id="leaderboardCred" class="space-y-1">
                        <!-- Populated via API -->
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Random Life Encounter Modal -->
    <div id="randomEventModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md hidden items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-amber-500/40 rounded-2xl max-w-lg w-full p-6 sm:p-8 shadow-2xl relative">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl mb-4 border border-amber-500/20">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            
            <h3 id="eventTitle" class="text-lg font-bold text-white mb-2">Life Event</h3>
            <p id="eventDesc" class="text-xs text-slate-300 leading-relaxed mb-6"></p>

            <div class="space-y-3">
                <button onclick="GameApp.chooseEventOption('a')" id="eventChoiceA" class="w-full text-left p-3.5 bg-slate-800/90 hover:bg-slate-700/90 text-white rounded-xl text-xs font-semibold border border-slate-700 transition">
                    Option A
                </button>
                <button onclick="GameApp.chooseEventOption('b')" id="eventChoiceB" class="w-full text-left p-3.5 bg-slate-800/90 hover:bg-slate-700/90 text-white rounded-xl text-xs font-semibold border border-slate-700 transition">
                    Option B
                </button>
            </div>
        </div>
    </div>

    <!-- Core Game Engine -->
    <script src="assets/js/game.js"></script>
</body>
</html>
