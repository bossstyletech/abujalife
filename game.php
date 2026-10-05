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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Abuja Life | Urban Life Simulation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-up {
            animation: fadeInUp 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #090d16; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #334155; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col pb-20 sm:pb-8 selection:bg-emerald-600 selection:text-white antialiased">

    <!-- Floating Toast Notifications -->
    <div id="toastContainer" class="fixed top-4 right-4 flex flex-col gap-2 z-50 pointer-events-none"></div>

    <!-- Header Navigation -->
    <header class="border-b border-slate-800 bg-slate-900/95 sticky top-0 z-40 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                    <i class="fa-solid fa-city"></i>
                </div>
                <div>
                    <h1 class="text-base font-bold text-white tracking-tight flex items-center gap-1.5">
                        Abuja Life
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-medium">FCT</span>
                    </h1>
                </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex items-center gap-2">
                <button onclick="GameApp.sleepRest()" title="Sleep and recharge energy" class="h-9 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center gap-1.5 border border-slate-700/80 transition active:scale-95">
                    <i class="fa-solid fa-bed text-indigo-400"></i> <span class="hidden sm:inline">Rest</span>
                </button>
                <button onclick="GameApp.visitHospital()" title="National Hospital Abuja checkup" class="h-9 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center gap-1.5 border border-slate-700/80 transition active:scale-95">
                    <i class="fa-solid fa-hospital text-rose-400"></i> <span class="hidden sm:inline">Hospital</span>
                </button>
                <button onclick="GameApp.advanceDay()" class="h-9 px-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs flex items-center gap-1.5 transition active:scale-95 shadow-sm">
                    <i class="fa-solid fa-forward-step text-[11px]"></i> <span>Next Day</span>
                </button>
                <button onclick="GameApp.logout()" title="Logout" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center border border-slate-700/80 transition active:scale-95">
                    <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Top Dashboard Stats HUD -->
    <section class="bg-slate-900/60 border-b border-slate-800/80 px-4 sm:px-6 py-4">
        <div class="max-w-7xl mx-auto space-y-3">
            
            <!-- Cards Row: Profile & Currency -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                <!-- Citizen -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-2xl flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-800 text-emerald-400 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="min-w-0">
                        <span id="hudName" class="block font-bold text-xs text-white truncate"><?= htmlspecialchars($char['full_name']) ?></span>
                        <span id="hudAge" class="text-[11px] text-slate-400 block truncate"><?= $char['age'] ?> yrs • Day <?= $char['days_lived'] ?></span>
                    </div>
                </div>

                <!-- District -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-2xl flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-800 text-teal-400 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[10px] text-slate-400 uppercase font-medium">District</span>
                        <span id="hudDistrict" class="font-bold text-xs text-white truncate"><?= htmlspecialchars($char['district']) ?></span>
                    </div>
                </div>

                <!-- Cash -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-2xl flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-800 text-emerald-400 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[10px] text-slate-400 uppercase font-medium">Cash</span>
                        <span id="hudCash" class="font-mono font-bold text-xs text-emerald-400 truncate">₦<?= number_format($char['cash'], 2) ?></span>
                    </div>
                </div>

                <!-- Bank -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-2xl flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-800 text-blue-400 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[10px] text-slate-400 uppercase font-medium">Bank</span>
                        <span id="hudBank" class="font-mono font-bold text-xs text-blue-300 truncate">₦<?= number_format($char['bank'], 2) ?></span>
                    </div>
                </div>

                <!-- Net Worth -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-2xl flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-800 text-amber-400 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[10px] text-slate-400 uppercase font-medium">Net Worth</span>
                        <span id="hudNetWorth" class="font-mono font-bold text-xs text-amber-400 truncate">₦0.00</span>
                    </div>
                </div>

                <!-- Job -->
                <div class="bg-slate-900 border border-slate-800 p-3 rounded-2xl flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-800 text-purple-400 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[10px] text-slate-400 uppercase font-medium">Job</span>
                        <span id="hudJob" class="font-bold text-xs text-white truncate"><?= htmlspecialchars($char['job_title'] ?: 'Unemployed') ?></span>
                    </div>
                </div>
            </div>

            <!-- Vitals Progress Row -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
                <!-- Energy -->
                <div class="bg-slate-900 border border-slate-800 px-3 py-2 rounded-2xl">
                    <div class="flex justify-between text-[11px] font-medium mb-1.5">
                        <span class="text-amber-400"><i class="fa-solid fa-bolt mr-1"></i> Energy</span>
                        <span id="valEnergy" class="text-slate-300 font-mono text-[10px]"><?= $char['energy'] ?>%</span>
                    </div>
                    <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden">
                        <div id="barEnergy" class="bg-amber-400 h-full rounded-full transition-all duration-300" style="width: <?= $char['energy'] ?>%"></div>
                    </div>
                </div>

                <!-- Health -->
                <div class="bg-slate-900 border border-slate-800 px-3 py-2 rounded-2xl">
                    <div class="flex justify-between text-[11px] font-medium mb-1.5">
                        <span class="text-rose-400"><i class="fa-solid fa-heart mr-1"></i> Health</span>
                        <span id="valHealth" class="text-slate-300 font-mono text-[10px]"><?= $char['health'] ?>%</span>
                    </div>
                    <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden">
                        <div id="barHealth" class="bg-rose-500 h-full rounded-full transition-all duration-300" style="width: <?= $char['health'] ?>%"></div>
                    </div>
                </div>

                <!-- Happiness -->
                <div class="bg-slate-900 border border-slate-800 px-3 py-2 rounded-2xl">
                    <div class="flex justify-between text-[11px] font-medium mb-1.5">
                        <span class="text-emerald-400"><i class="fa-solid fa-face-smile mr-1"></i> Happiness</span>
                        <span id="valHappiness" class="text-slate-300 font-mono text-[10px]"><?= $char['happiness'] ?>%</span>
                    </div>
                    <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden">
                        <div id="barHappiness" class="bg-emerald-500 h-full rounded-full transition-all duration-300" style="width: <?= $char['happiness'] ?>%"></div>
                    </div>
                </div>

                <!-- Intelligence -->
                <div class="bg-slate-900 border border-slate-800 px-3 py-2 rounded-2xl">
                    <div class="flex justify-between text-[11px] font-medium mb-1.5">
                        <span class="text-sky-400"><i class="fa-solid fa-brain mr-1"></i> IQ</span>
                        <span id="valIntelligence" class="text-slate-300 font-mono text-[10px]"><?= $char['intelligence'] ?></span>
                    </div>
                    <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden">
                        <div id="barIntelligence" class="bg-sky-500 h-full rounded-full transition-all duration-300" style="width: <?= $char['intelligence'] ?>%"></div>
                    </div>
                </div>

                <!-- Street Cred -->
                <div class="col-span-2 sm:col-span-1 bg-slate-900 border border-slate-800 px-3 py-2 rounded-2xl">
                    <div class="flex justify-between text-[11px] font-medium mb-1.5">
                        <span class="text-purple-400"><i class="fa-solid fa-shield mr-1"></i> Cred</span>
                        <span id="valStreetCred" class="text-slate-300 font-mono text-[10px]"><?= $char['street_cred'] ?></span>
                    </div>
                    <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden">
                        <div id="barStreetCred" class="bg-purple-500 h-full rounded-full transition-all duration-300" style="width: <?= min(100, $char['street_cred']) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Desktop Tab Navigation -->
    <nav class="border-b border-slate-800/80 bg-slate-900/40 hidden sm:block">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center gap-1.5 overflow-x-auto py-2.5 text-xs font-semibold whitespace-nowrap">
                <button id="btn-tab-overview" onclick="GameApp.switchTab('overview')" class="tab-btn px-3.5 py-2 rounded-xl border flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-house text-xs"></i> Overview
                </button>
                <button id="btn-tab-jobs" onclick="GameApp.switchTab('jobs')" class="tab-btn px-3.5 py-2 rounded-xl border flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-briefcase text-xs"></i> Careers
                </button>
                <button id="btn-tab-hustles" onclick="GameApp.switchTab('hustles')" class="tab-btn px-3.5 py-2 rounded-xl border flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-bolt text-xs"></i> Hustles
                </button>
                <button id="btn-tab-education" onclick="GameApp.switchTab('education')" class="tab-btn px-3.5 py-2 rounded-xl border flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-graduation-cap text-xs"></i> Education
                </button>
                <button id="btn-tab-realestate" onclick="GameApp.switchTab('realestate')" class="tab-btn px-3.5 py-2 rounded-xl border flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-building text-xs"></i> Real Estate
                </button>
                <button id="btn-tab-vehicles" onclick="GameApp.switchTab('vehicles')" class="tab-btn px-3.5 py-2 rounded-xl border flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-car text-xs"></i> Garage
                </button>
                <button id="btn-tab-lifestyle" onclick="GameApp.switchTab('lifestyle')" class="tab-btn px-3.5 py-2 rounded-xl border flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-mug-hot text-xs"></i> Lifestyle
                </button>
                <button id="btn-tab-bank" onclick="GameApp.switchTab('bank')" class="tab-btn px-3.5 py-2 rounded-xl border flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-landmark text-xs"></i> Bank & Loans
                </button>
                <button id="btn-tab-casino" onclick="GameApp.switchTab('casino')" class="tab-btn px-3.5 py-2 rounded-xl border flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-dice text-xs"></i> Abuja Bet
                </button>
                <button id="btn-tab-districts" onclick="GameApp.switchTab('districts')" class="tab-btn px-3.5 py-2 rounded-xl border flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-map-pin text-xs"></i> Districts
                </button>
                <button id="btn-tab-leaderboard" onclick="GameApp.switchTab('leaderboard')" class="tab-btn px-3.5 py-2 rounded-xl border flex items-center gap-2 transition active:scale-95">
                    <i class="fa-solid fa-trophy text-xs"></i> Leaderboard
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content Workspace -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-6">
        
        <!-- 1. OVERVIEW TAB -->
        <section id="tab-overview" class="game-tab-content">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Profile Summary Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-id-card text-emerald-400"></i> Citizen Profile
                    </h3>

                    <div class="space-y-3.5 text-xs">
                        <div class="flex justify-between py-1.5 border-b border-slate-800">
                            <span class="text-slate-400">Home Residence</span>
                            <span id="hudHouse" class="font-semibold text-white"><?= htmlspecialchars($char['property_name'] ?: 'Renting Self-Con') ?></span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-slate-800">
                            <span class="text-slate-400">Primary Car</span>
                            <span id="hudVehicle" class="font-semibold text-white"><?= htmlspecialchars($char['vehicle_name'] ?: 'Public Transport') ?></span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-slate-800">
                            <span class="text-slate-400">Education Degree</span>
                            <span id="hudEducation" class="font-semibold text-emerald-400"><?= htmlspecialchars($char['education_level']) ?></span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-slate-800">
                            <span class="text-slate-400">Karma Score</span>
                            <span class="font-semibold text-teal-300"><?= $char['karma'] ?>/100</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-slate-800">
                            <span class="text-slate-400">Legal Status</span>
                            <span class="font-semibold <?= $char['jail_days'] > 0 ? 'text-rose-400' : 'text-emerald-400' ?>">
                                <?= $char['jail_days'] > 0 ? "Serving {$char['jail_days']} days detention" : "Clean Citizen" ?>
                            </span>
                        </div>
                    </div>

                    <!-- Quick Shortcuts -->
                    <div class="mt-6 pt-4 border-t border-slate-800 grid grid-cols-2 gap-2">
                        <button onclick="GameApp.switchTab('hustles')" class="py-2.5 px-3 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs rounded-2xl border border-slate-700 transition active:scale-95 text-center">
                            <i class="fa-solid fa-bolt text-amber-400 mb-1 block"></i> Hustle
                        </button>
                        <button onclick="GameApp.switchTab('lifestyle')" class="py-2.5 px-3 bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs rounded-2xl border border-slate-700 transition active:scale-95 text-center">
                            <i class="fa-solid fa-mug-hot text-teal-400 mb-1 block"></i> Leisure
                        </button>
                    </div>
                </div>

                <!-- Recent Activities Stream -->
                <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-3xl p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-400 flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left text-emerald-400"></i> Activity Feed
                            </h3>
                            <button onclick="GameApp.fetchCharacter()" class="text-xs text-slate-400 hover:text-white transition">
                                <i class="fa-solid fa-arrows-rotate mr-1"></i> Refresh
                            </button>
                        </div>
                        <div id="recentLogsList" class="space-y-1 overflow-y-auto max-h-[380px] pr-1">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. CAREERS TAB -->
        <section id="tab-jobs" class="game-tab-content hidden">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-white">Careers & Employment</h2>
                <p class="text-xs text-slate-400">Apply for positions across ministries, startups, and private corporations.</p>
            </div>
            <div id="jobsListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Populated via API -->
            </div>
        </section>

        <!-- 3. HUSTLES TAB -->
        <section id="tab-hustles" class="game-tab-content hidden">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-white">Side Hustles & Gigs</h2>
                <p class="text-xs text-slate-400">Quick ways to generate cash in Abuja. Balance capital requirements with energy.</p>
            </div>
            <div id="hustlesListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Populated via API -->
            </div>
        </section>

        <!-- 4. EDUCATION TAB -->
        <section id="tab-education" class="game-tab-content hidden">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-white">Higher Learning & Training</h2>
                <p class="text-xs text-slate-400">Upgrade your credentials and IQ to unlock higher-paying career tiers.</p>
            </div>
            <div id="educationListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Populated via API -->
            </div>
        </section>

        <!-- 5. REAL ESTATE TAB -->
        <section id="tab-realestate" class="game-tab-content hidden space-y-6">
            <div>
                <h2 class="text-xl font-bold text-white mb-1">Your Property Portfolio</h2>
                <p class="text-xs text-slate-400 mb-4">Properties you own. Rent them out for automatic daily passive Naira.</p>
                <div id="propertiesOwnedContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Populated via API -->
                </div>
            </div>

            <div class="pt-6 border-t border-slate-800">
                <h2 class="text-xl font-bold text-white mb-1">Abuja Real Estate Market</h2>
                <p class="text-xs text-slate-400 mb-4">Prime acquisitions across Kubwa, Lugbe, Gwarinpa, Wuse 2, Maitama, and Asokoro.</p>
                <div id="propertiesMarketContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Populated via API -->
                </div>
            </div>
        </section>

        <!-- 6. VEHICLES / GARAGE TAB -->
        <section id="tab-vehicles" class="game-tab-content hidden space-y-6">
            <div>
                <h2 class="text-xl font-bold text-white mb-1">Your Garage Fleet</h2>
                <p class="text-xs text-slate-400 mb-4">Cruise through the capital to raise your street cred and happiness.</p>
                <div id="vehiclesOwnedContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Populated via API -->
                </div>
            </div>

            <div class="pt-6 border-t border-slate-800">
                <h2 class="text-xl font-bold text-white mb-1">Automobile Showroom</h2>
                <p class="text-xs text-slate-400 mb-4">From everyday Japanese cars to luxury SUVs and siren escorts.</p>
                <div id="vehiclesMarketContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Populated via API -->
                </div>
            </div>
        </section>

        <!-- 7. LIFESTYLE TAB -->
        <section id="tab-lifestyle" class="game-tab-content hidden">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-white">Abuja Leisure & Hotspots</h2>
                <p class="text-xs text-slate-400">Recharge happiness, socialize, and stay healthy across parks, clubs, and gyms.</p>
            </div>
            <div id="lifestyleListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Populated via API -->
            </div>
        </section>

        <!-- 8. BANK & LOANS TAB -->
        <section id="tab-bank" class="game-tab-content hidden">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-white">Banking & Credit Lines</h2>
                <p class="text-xs text-slate-400">Safeguard cash, earn daily interest (+0.05%), or request commercial credit.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                    <span class="text-xs text-slate-400 block font-medium mb-1">Cash Reserves</span>
                    <span id="bankDisplayCash" class="font-mono font-bold text-2xl text-emerald-400">₦0.00</span>
                </div>
                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                    <span class="text-xs text-slate-400 block font-medium mb-1">Bank Savings</span>
                    <span id="bankDisplaySavings" class="font-mono font-bold text-2xl text-blue-400">₦0.00</span>
                </div>
                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                    <span class="text-xs text-slate-400 block font-medium mb-1">Outstanding Debt</span>
                    <span id="bankDisplayLoan" class="font-mono font-bold text-2xl text-rose-400">₦0.00</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Deposit / Withdraw Box -->
                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                    <h3 class="font-bold text-sm text-white mb-4"><i class="fa-solid fa-money-bill-transfer text-emerald-400 mr-2"></i> Savings Operations</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Deposit Cash to Bank</label>
                            <div class="flex gap-2">
                                <input type="number" id="bankDepositInput" placeholder="Amount in ₦" class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-600">
                                <button onclick="GameApp.bankDeposit()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl transition active:scale-95 shadow-sm">
                                    Deposit
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Withdraw Bank to Cash</label>
                            <div class="flex gap-2">
                                <input type="number" id="bankWithdrawInput" placeholder="Amount in ₦" class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-600">
                                <button onclick="GameApp.bankWithdraw()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl border border-slate-700 transition active:scale-95">
                                    Withdraw
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loan Facility Box -->
                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                    <h3 class="font-bold text-sm text-white mb-1"><i class="fa-solid fa-landmark text-amber-400 mr-2"></i> Commercial Bank Loan</h3>
                    <p class="text-xs text-slate-400 mb-4">Credit line tied to Street Cred. Max limit: <strong id="bankMaxLoanLimit" class="text-emerald-400">₦0.00</strong></p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Request Soft Loan</label>
                            <div class="flex gap-2">
                                <input type="number" id="bankLoanInput" placeholder="Loan amount" class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-600">
                                <button onclick="GameApp.takeBankLoan()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-amber-400 font-bold text-xs rounded-xl border border-slate-700 transition active:scale-95">
                                    Apply
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Repay Outstanding Loan</label>
                            <div class="flex gap-2">
                                <input type="number" id="bankRepayInput" placeholder="Repayment amount" class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-600">
                                <button onclick="GameApp.repayBankLoan()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-rose-300 font-bold text-xs rounded-xl border border-slate-700 transition active:scale-95">
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
            <div class="mb-5">
                <h2 class="text-xl font-bold text-white">Abuja Bet & Lucky Games</h2>
                <p class="text-xs text-slate-400">Try your luck on sports accumulator tickets or table dice.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Sports Betting Ticket -->
                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-800 text-emerald-400 flex items-center justify-center text-base">
                            <i class="fa-solid fa-futbol"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-white">Weekend Premier League Accumulator</h3>
                            <span class="text-xs text-slate-400">Pick your risk tier and enter your stake</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-slate-300 mb-1">Stake Amount</label>
                        <input type="number" id="betStakeInput" value="5000" min="500" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-600">
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <button onclick="GameApp.playSportsBet('safe')" class="py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold border border-slate-700 transition active:scale-95 text-center">
                            Safe (1.6x)
                        </button>
                        <button onclick="GameApp.playSportsBet('medium')" class="py-2.5 bg-slate-800 hover:bg-slate-700 text-amber-400 rounded-xl text-xs font-semibold border border-slate-700 transition active:scale-95 text-center">
                            Medium (3.2x)
                        </button>
                        <button onclick="GameApp.playSportsBet('high')" class="py-2.5 bg-slate-800 hover:bg-slate-700 text-rose-400 rounded-xl text-xs font-semibold border border-slate-700 transition active:scale-95 text-center">
                            Jumbo (12.0x)
                        </button>
                    </div>
                </div>

                <!-- Lucky Dice Table -->
                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-800 text-purple-400 flex items-center justify-center text-base">
                            <i class="fa-solid fa-dice"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-white">Wuse 2 Casino Dice Roll</h3>
                            <span class="text-xs text-slate-400">Roll two dice and predict the outcome</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-slate-300 mb-1">Dice Stake</label>
                        <input type="number" id="diceStakeInput" value="5000" min="500" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-600">
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <button onclick="GameApp.rollDiceGame('low')" class="py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold border border-slate-700 transition active:scale-95 text-center">
                            Low (2-6) • 2x
                        </button>
                        <button onclick="GameApp.rollDiceGame('seven')" class="py-2.5 bg-slate-800 hover:bg-slate-700 text-amber-400 rounded-xl text-xs font-semibold border border-slate-700 transition active:scale-95 text-center">
                            Lucky 7 • 5x
                        </button>
                        <button onclick="GameApp.rollDiceGame('high')" class="py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold border border-slate-700 transition active:scale-95 text-center">
                            High (8-12) • 2x
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- 10. DISTRICTS TAB -->
        <section id="tab-districts" class="game-tab-content hidden">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-white">Abuja Districts & Relocation</h2>
                <p class="text-xs text-slate-400">Relocate to higher status enclaves as your street cred grows.</p>
            </div>
            <div id="districtsListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Populated via API -->
            </div>
        </section>

        <!-- 11. LEADERBOARD TAB -->
        <section id="tab-leaderboard" class="game-tab-content hidden">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-white">Abuja Hall of Fame</h2>
                <p class="text-xs text-slate-400">The most influential citizens in the Federal Capital Territory.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                    <h3 class="font-bold text-xs text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-crown text-amber-400"></i> Top Richest Citizens
                    </h3>
                    <div id="leaderboardRichest" class="space-y-1">
                        <!-- Populated via API -->
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                    <h3 class="font-bold text-xs text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-fire text-rose-400"></i> Street Cred Dons
                    </h3>
                    <div id="leaderboardCred" class="space-y-1">
                        <!-- Populated via API -->
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Mobile Bottom Navigation Bar (Sticky, Native App feel) -->
    <nav class="sm:hidden fixed bottom-0 left-0 right-0 bg-slate-900/95 border-t border-slate-800 z-40 backdrop-blur px-2 py-1.5 flex justify-around items-center">
        <button id="mbtn-tab-overview" onclick="GameApp.switchTab('overview')" class="mobile-tab-btn flex flex-col items-center py-1 px-3 text-slate-400 active:scale-95 transition">
            <i class="fa-solid fa-house text-sm mb-1"></i>
            <span class="text-[10px] font-medium">Home</span>
        </button>
        <button id="mbtn-tab-jobs" onclick="GameApp.switchTab('jobs')" class="mobile-tab-btn flex flex-col items-center py-1 px-3 text-slate-400 active:scale-95 transition">
            <i class="fa-solid fa-briefcase text-sm mb-1"></i>
            <span class="text-[10px] font-medium">Careers</span>
        </button>
        <button id="mbtn-tab-hustles" onclick="GameApp.switchTab('hustles')" class="mobile-tab-btn flex flex-col items-center py-1 px-3 text-slate-400 active:scale-95 transition">
            <i class="fa-solid fa-bolt text-sm mb-1"></i>
            <span class="text-[10px] font-medium">Hustle</span>
        </button>
        <button id="mbtn-tab-realestate" onclick="GameApp.switchTab('realestate')" class="mobile-tab-btn flex flex-col items-center py-1 px-3 text-slate-400 active:scale-95 transition">
            <i class="fa-solid fa-building text-sm mb-1"></i>
            <span class="text-[10px] font-medium">Assets</span>
        </button>
        <button onclick="GameApp.toggleMobileMenu()" class="flex flex-col items-center py-1 px-3 text-slate-400 hover:text-white active:scale-95 transition">
            <i class="fa-solid fa-bars text-sm mb-1"></i>
            <span class="text-[10px] font-medium">More</span>
        </button>
    </nav>

    <!-- Mobile Drawer / More Menu Modal -->
    <div id="mobileMenuModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-end sm:items-center justify-center z-50 p-0 sm:p-4 transition-all">
        <div class="bg-slate-900 border-t sm:border border-slate-800 rounded-t-3xl sm:rounded-3xl max-w-sm w-full p-6 shadow-2xl animate-fade-up">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-sm text-white">All Activities</h3>
                <button onclick="GameApp.toggleMobileMenu(false)" class="w-8 h-8 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs font-semibold">
                <button onclick="GameApp.switchTab('education')" class="p-3 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-2xl flex items-center gap-2.5 transition active:scale-95">
                    <i class="fa-solid fa-graduation-cap text-teal-400"></i> Education
                </button>
                <button onclick="GameApp.switchTab('vehicles')" class="p-3 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-2xl flex items-center gap-2.5 transition active:scale-95">
                    <i class="fa-solid fa-car text-purple-400"></i> Garage
                </button>
                <button onclick="GameApp.switchTab('lifestyle')" class="p-3 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-2xl flex items-center gap-2.5 transition active:scale-95">
                    <i class="fa-solid fa-mug-hot text-emerald-400"></i> Lifestyle
                </button>
                <button onclick="GameApp.switchTab('bank')" class="p-3 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-2xl flex items-center gap-2.5 transition active:scale-95">
                    <i class="fa-solid fa-landmark text-blue-400"></i> Bank & Loans
                </button>
                <button onclick="GameApp.switchTab('casino')" class="p-3 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-2xl flex items-center gap-2.5 transition active:scale-95">
                    <i class="fa-solid fa-dice text-amber-400"></i> Abuja Bet
                </button>
                <button onclick="GameApp.switchTab('districts')" class="p-3 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-2xl flex items-center gap-2.5 transition active:scale-95">
                    <i class="fa-solid fa-map-pin text-rose-400"></i> Districts
                </button>
                <button onclick="GameApp.switchTab('leaderboard')" class="col-span-2 p-3 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-2xl flex items-center justify-center gap-2.5 transition active:scale-95">
                    <i class="fa-solid fa-trophy text-amber-400"></i> Abuja Leaderboard
                </button>
            </div>
        </div>
    </div>

    <!-- Random Life Encounter Modal -->
    <div id="randomEventModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl relative animate-fade-up">
            <div class="w-11 h-11 rounded-2xl bg-slate-800 text-amber-400 flex items-center justify-center text-lg mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            
            <h3 id="eventTitle" class="text-base sm:text-lg font-bold text-white mb-2">Life Event</h3>
            <p id="eventDesc" class="text-xs text-slate-300 leading-relaxed mb-6"></p>

            <div class="space-y-2.5">
                <button onclick="GameApp.chooseEventOption('a')" id="eventChoiceA" class="w-full text-left p-3.5 bg-slate-800 hover:bg-slate-700 text-white rounded-2xl text-xs font-semibold border border-slate-700 transition active:scale-95">
                    Option A
                </button>
                <button onclick="GameApp.chooseEventOption('b')" id="eventChoiceB" class="w-full text-left p-3.5 bg-slate-800 hover:bg-slate-700 text-white rounded-2xl text-xs font-semibold border border-slate-700 transition active:scale-95">
                    Option B
                </button>
            </div>
        </div>
    </div>

    <!-- Core Game Engine -->
    <script src="assets/js/game.js"></script>
</body>
</html>
