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
    <title>Abuja Life | Live, Hustle, Rule</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <!-- Three.js 3D Engine & OrbitControls for Rotatable Workplace/Home & Bitmoji Studio -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
    <script src="assets/js/characters.js"></script>
    <script src="assets/js/avatar3d.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-up {
            animation: fadeInUp 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col pb-24 sm:pb-12 antialiased selection:bg-emerald-600 selection:text-white">

    <!-- Floating Toast Notifications -->
    <div id="toastContainer" class="fixed top-4 right-4 flex flex-col gap-2 z-50 pointer-events-none"></div>

    <!-- Header Navigation (Crisp White Layered) -->
    <header class="bg-white/95 border-b border-slate-200/90 sticky top-0 z-30 backdrop-blur shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                    <i class="fa-solid fa-city"></i>
                </div>
                <div>
                    <h1 class="text-base font-extrabold text-slate-900 tracking-tight flex items-center gap-1.5">
                        Abuja Life
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold border border-slate-200">FCT</span>
                    </h1>
                    <span id="hudDistrictTop" class="text-xs text-slate-500 font-medium"><?= htmlspecialchars($char['district']) ?></span>
                </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex items-center gap-2">
                <button onclick="GameApp.openWardrobeModal()" title="Customize Bitmoji Avatar" class="h-9 px-3.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold flex items-center gap-2 shadow-sm transition active:scale-95">
                    <i class="fa-solid fa-shirt text-xs"></i> <span class="hidden sm:inline">Wardrobe</span>
                </button>
                <button onclick="PhoneApp.toggle()" title="Open Abuja Smartphone" class="h-9 px-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold flex items-center gap-2 shadow-sm transition active:scale-95">
                    <i class="fa-solid fa-mobile-screen-button text-xs"></i> <span class="hidden sm:inline">Phone</span>
                </button>
                <button onclick="GameApp.advanceDay()" class="h-9 px-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition active:scale-95">
                    <i class="fa-solid fa-forward-step text-[11px]"></i> <span>Next Day</span>
                </button>
                <button onclick="GameApp.logout()" title="Logout" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-power-off text-xs"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Workspace (Layered on Clean White Background) -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-6 space-y-6">

        <!-- ========================================================
             1. CENTRAL 3D ROTATABLE WORKPLACE & RESIDENCE SHOWCASE
             ======================================================== -->
        <section class="bg-white border border-slate-200/90 rounded-3xl p-4 sm:p-6 shadow-sm relative overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h2 id="world3dTitle" class="text-base sm:text-lg font-bold text-slate-900">3D Workplace & Persona Showcase</h2>
                    </div>
                    <p class="text-xs text-slate-500">Drag to rotate 360° • Scroll/Pinch to zoom in with infinite vector clarity.</p>
                </div>

                <!-- 3D View Controls -->
                <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-2xl border border-slate-200/60 text-xs">
                    <button id="btnViewWorkplace" onclick="World3D.toggleView('workplace')" class="px-3 py-1.5 rounded-xl font-bold bg-white text-slate-900 shadow-sm transition">
                        <i class="fa-solid fa-briefcase text-xs mr-1 text-emerald-600"></i> Workplace
                    </button>
                    <button id="btnViewHome" onclick="World3D.toggleView('home')" class="px-3 py-1.5 rounded-xl font-bold text-slate-600 hover:text-slate-900 transition">
                        <i class="fa-solid fa-house text-xs mr-1 text-teal-600"></i> Residence
                    </button>
                    <button id="btnAutoRotate" onclick="World3D.toggleAutoRotate()" class="px-2.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 transition" title="Toggle Auto-Spin">
                        <i class="fa-solid fa-play text-xs"></i>
                    </button>
                    <button onclick="World3D.resetCamera()" class="px-2.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 transition" title="Reset View">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Three.js Canvas Container -->
            <div id="world3d-container" class="w-full h-[360px] sm:h-[440px] rounded-2xl bg-slate-50 border border-slate-100 relative cursor-grab active:cursor-grabbing overflow-hidden">
                <!-- Three.js renders in here -->
            </div>

            <!-- Status Indicator below 3D viewport -->
            <div class="mt-3 flex flex-wrap items-center justify-between text-xs text-slate-600 pt-2 border-t border-slate-100">
                <div class="flex items-center gap-3">
                    <span><i class="fa-solid fa-building text-slate-400 mr-1"></i> Current Base: <strong id="world3dCurrentJob" class="text-slate-900"><?= htmlspecialchars($char['job_title'] ?: 'Unemployed Street Grinder') ?></strong></span>
                    <span><i class="fa-solid fa-house-chimney text-slate-400 mr-1"></i> Home: <strong id="world3dCurrentHome" class="text-slate-900"><?= htmlspecialchars($char['property_name'] ?: 'Renting Self-Con') ?></strong></span>
                </div>
                <div class="text-[11px] text-slate-500">
                    <span class="inline-block mr-2"><i class="fa-solid fa-arrows-up-down-left-right text-slate-400 mr-1"></i> Drag to Orbit</span>
                    <span><i class="fa-solid fa-magnifying-glass-plus text-slate-400 mr-1"></i> Pinch/Wheel Zoom</span>
                </div>
            </div>
        </section>

        <!-- ========================================================
             2. DAILY SCHEDULE & MORNING ROUTINE CARD
             ("Wake up in the morning and decide to go to work")
             ======================================================== -->
        <section class="bg-white border border-slate-200/90 rounded-3xl p-5 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span id="timeBadge" class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            ☀️ Morning (7:00 AM)
                        </span>
                        <h3 class="font-extrabold text-base text-slate-900">Today's Schedule & Decisions</h3>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Wake up in your district, plan your hours, commute to work, or run side gigs.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <!-- Action 1: Morning Breakfast & Shower -->
                <button onclick="GameApp.doMorningRoutine()" class="p-4 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 rounded-2xl text-left transition active:scale-95 group">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition">
                        <i class="fa-solid fa-mug-saucer"></i>
                    </div>
                    <h4 class="font-bold text-xs text-slate-900 mb-0.5">Morning Routine</h4>
                    <p class="text-[11px] text-slate-500 leading-snug">Hot shower & Nigerian breakfast. (+15 Energy, +5 Happiness)</p>
                </button>

                <!-- Action 2: Go to Work / Commute -->
                <button onclick="GameApp.goToWork()" class="p-4 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 rounded-2xl text-left transition active:scale-95 group">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <h4 class="font-bold text-xs text-emerald-950 mb-0.5">Commute to Work</h4>
                    <p class="text-[11px] text-emerald-800 leading-snug">Head to your workplace. Complete shift & collect daily pay.</p>
                </button>

                <!-- Action 3: Run Street Hustle -->
                <button onclick="GameApp.switchTab('hustles')" class="p-4 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 rounded-2xl text-left transition active:scale-95 group">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h4 class="font-bold text-xs text-slate-900 mb-0.5">Side Hustles</h4>
                    <p class="text-[11px] text-slate-500 leading-snug">POS business, gadget trade at Banex, or crypto arbitrage.</p>
                </button>

                <!-- Action 4: Evening Sleep / Rest -->
                <button onclick="GameApp.sleepRest()" class="p-4 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 rounded-2xl text-left transition active:scale-95 group">
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition">
                        <i class="fa-solid fa-bed"></i>
                    </div>
                    <h4 class="font-bold text-xs text-slate-900 mb-0.5">Rest & Recharge</h4>
                    <p class="text-[11px] text-slate-500 leading-snug">Sleep in your home. Recharges 100% energy for tomorrow.</p>
                </button>
            </div>
        </section>

        <!-- ========================================================
             3. TAB NAVIGATION (Careers, Hustles, Real Estate, etc.)
             ======================================================== -->
        <div class="flex items-center gap-1.5 overflow-x-auto py-2 text-xs font-bold whitespace-nowrap bg-white p-2 rounded-2xl border border-slate-200/90 shadow-sm">
            <button id="btn-tab-overview" onclick="GameApp.switchTab('overview')" class="tab-btn px-4 py-2 rounded-xl bg-emerald-600 text-white transition active:scale-95">
                Overview
            </button>
            <button id="btn-tab-jobs" onclick="GameApp.switchTab('jobs')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 transition active:scale-95">
                Careers & Ministries
            </button>
            <button id="btn-tab-hustles" onclick="GameApp.switchTab('hustles')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 transition active:scale-95">
                Street Hustles
            </button>
            <button id="btn-tab-realestate" onclick="GameApp.switchTab('realestate')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 transition active:scale-95">
                Real Estate
            </button>
            <button id="btn-tab-vehicles" onclick="GameApp.switchTab('vehicles')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 transition active:scale-95">
                Car Garage
            </button>
            <button id="btn-tab-lifestyle" onclick="GameApp.switchTab('lifestyle')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 transition active:scale-95">
                Abuja Leisure
            </button>
            <button id="btn-tab-bank" onclick="GameApp.switchTab('bank')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 transition active:scale-95">
                Bank & Loans
            </button>
            <button id="btn-tab-casino" onclick="GameApp.switchTab('casino')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 transition active:scale-95">
                Abuja Bet
            </button>
            <button id="btn-tab-leaderboard" onclick="GameApp.switchTab('leaderboard')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 transition active:scale-95">
                Hall of Fame
            </button>
        </div>

        <!-- 3A. OVERVIEW FEED -->
        <section id="tab-overview" class="game-tab-content">
            <div class="bg-white border border-slate-200/90 rounded-3xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-extrabold text-sm uppercase tracking-wider text-slate-600 flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-emerald-600"></i> FCT Activity & Life Feed
                    </h3>
                    <button onclick="GameApp.fetchCharacter()" class="text-xs text-slate-500 hover:text-slate-900 transition">
                        <i class="fa-solid fa-rotate-right mr-1"></i> Refresh
                    </button>
                </div>
                <div id="recentLogsList" class="space-y-1 overflow-y-auto max-h-[360px] pr-2">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
        </section>

        <!-- 3B. JOBS TAB -->
        <section id="tab-jobs" class="game-tab-content hidden">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-slate-900">Career Positions & Federal Civil Service</h3>
                <p class="text-xs text-slate-500">Apply for positions across ministries, software startups, and banking.</p>
            </div>
            <div id="jobsListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
        </section>

        <!-- 3C. HUSTLES TAB -->
        <section id="tab-hustles" class="game-tab-content hidden">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-slate-900">Abuja Street Hustles & Side Gigs</h3>
                <p class="text-xs text-slate-500">Quick ways to generate cash with capital and street cred.</p>
            </div>
            <div id="hustlesListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
        </section>

        <!-- 3D. REAL ESTATE TAB -->
        <section id="tab-realestate" class="game-tab-content hidden space-y-6">
            <div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Your Property Portfolio</h3>
                <p class="text-xs text-slate-500 mb-3">Rent properties out for daily passive Naira yield.</p>
                <div id="propertiesOwnedContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
            </div>
            <div class="pt-4 border-t border-slate-200">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Acquisitions Market</h3>
                <p class="text-xs text-slate-500 mb-3">Prime residential real estate across Abuja districts.</p>
                <div id="propertiesMarketContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
            </div>
        </section>

        <!-- 3E. VEHICLES TAB -->
        <section id="tab-vehicles" class="game-tab-content hidden space-y-6">
            <div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Your Garage Fleet</h3>
                <p class="text-xs text-slate-500 mb-3">Cruise through Abuja to boost street cred and happiness.</p>
                <div id="vehiclesOwnedContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
            </div>
            <div class="pt-4 border-t border-slate-200">
                <h3 class="text-lg font-bold text-slate-900 mb-1">Automobile Showroom</h3>
                <p class="text-xs text-slate-500 mb-3">Japanese workhorses, luxury German saloons, and armored convoys.</p>
                <div id="vehiclesMarketContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
            </div>
        </section>

        <!-- 3F. LIFESTYLE TAB -->
        <section id="tab-lifestyle" class="game-tab-content hidden">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-slate-900">Abuja Leisure & Hotspots</h3>
                <p class="text-xs text-slate-500">Parks, Jabi lake, Wuse 2 clubs, and wellness gyms.</p>
            </div>
            <div id="lifestyleListContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
        </section>

        <!-- 3G. BANK TAB -->
        <section id="tab-bank" class="game-tab-content hidden">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-slate-900">Commercial Banking & Credit Lines</h3>
                <p class="text-xs text-slate-500">Savings account interest (+0.05%/day) and loan facilities.</p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                    <span class="text-xs text-slate-500 block font-semibold mb-1">Cash in Hand</span>
                    <span id="bankDisplayCash" class="font-mono font-bold text-2xl text-emerald-600">₦0.00</span>
                </div>
                <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                    <span class="text-xs text-slate-500 block font-semibold mb-1">Savings Account</span>
                    <span id="bankDisplaySavings" class="font-mono font-bold text-2xl text-blue-600">₦0.00</span>
                </div>
                <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                    <span class="text-xs text-slate-500 block font-semibold mb-1">Outstanding Loan</span>
                    <span id="bankDisplayLoan" class="font-mono font-bold text-2xl text-rose-600">₦0.00</span>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                    <h4 class="font-bold text-sm text-slate-900 mb-3">Deposit & Withdraw</h4>
                    <div class="space-y-3">
                        <div class="flex gap-2">
                            <input type="number" id="bankDepositInput" placeholder="Amount to deposit" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900">
                            <button onclick="GameApp.bankDeposit()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm">Deposit</button>
                        </div>
                        <div class="flex gap-2">
                            <input type="number" id="bankWithdrawInput" placeholder="Amount to withdraw" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900">
                            <button onclick="GameApp.bankWithdraw()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl shadow-sm">Withdraw</button>
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Commercial Bank Loan</h4>
                    <p class="text-xs text-slate-500 mb-3">Max limit: <strong id="bankMaxLoanLimit" class="text-emerald-700">₦0.00</strong></p>
                    <div class="space-y-3">
                        <div class="flex gap-2">
                            <input type="number" id="bankLoanInput" placeholder="Loan request amount" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900">
                            <button onclick="GameApp.takeBankLoan()" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs rounded-xl shadow-sm">Borrow</button>
                        </div>
                        <div class="flex gap-2">
                            <input type="number" id="bankRepayInput" placeholder="Repayment amount" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900">
                            <button onclick="GameApp.repayBankLoan()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl shadow-sm">Repay</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3H. CASINO TAB -->
        <section id="tab-casino" class="game-tab-content hidden">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-slate-900">Abuja Sports Betting & Table Dice</h3>
                <p class="text-xs text-slate-500">Weekend accumulator slips and lucky dice rolls.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                    <h4 class="font-bold text-sm text-slate-900 mb-3">Premier League Accumulator</h4>
                    <input type="number" id="betStakeInput" value="5000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs mb-3">
                    <div class="grid grid-cols-3 gap-2">
                        <button onclick="GameApp.playSportsBet('safe')" class="py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl">Safe 1.6x</button>
                        <button onclick="GameApp.playSportsBet('medium')" class="py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold text-xs rounded-xl">Medium 3.2x</button>
                        <button onclick="GameApp.playSportsBet('high')" class="py-2.5 bg-rose-100 hover:bg-rose-200 text-rose-900 font-bold text-xs rounded-xl">Jumbo 12x</button>
                    </div>
                </div>
                <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                    <h4 class="font-bold text-sm text-slate-900 mb-3">Lucky Dice Roll</h4>
                    <input type="number" id="diceStakeInput" value="5000" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs mb-3">
                    <div class="grid grid-cols-3 gap-2">
                        <button onclick="GameApp.rollDiceGame('low')" class="py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl">Low 2x</button>
                        <button onclick="GameApp.rollDiceGame('seven')" class="py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold text-xs rounded-xl">Lucky 7 (5x)</button>
                        <button onclick="GameApp.rollDiceGame('high')" class="py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl">High 2x</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3I. LEADERBOARD TAB -->
        <section id="tab-leaderboard" class="game-tab-content hidden">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-slate-900">Abuja Hall of Fame</h3>
                <p class="text-xs text-slate-500">Richest citizens and top street cred dons.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 mb-3">Top Net Worth</h4>
                    <div id="leaderboardRichest" class="space-y-1"></div>
                </div>
                <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-sm">
                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 mb-3">Street Cred Dons</h4>
                    <div id="leaderboardCred" class="space-y-1"></div>
                </div>
            </div>
        </section>

    </main>

    <!-- ========================================================
         4. MINIMIZED FLOATING PILL AT BOTTOM LEFT (USER REQUEST)
         ("all those GCE health distinct they should be minimized in one small and rounded pill at the bottom left so I can click it and to expand")
         ======================================================== -->
    <div id="vitalsFloatingPill" onclick="toggleVitalsDrawer()" class="fixed bottom-5 left-5 z-40 bg-white/95 hover:bg-white border border-slate-200/90 text-slate-800 px-4 py-2.5 rounded-full shadow-lg backdrop-blur flex items-center gap-3 cursor-pointer transition transform active:scale-95 group">
        <div class="flex items-center gap-2 text-xs font-bold">
            <span class="flex items-center gap-1 text-rose-600"><i class="fa-solid fa-heart text-[11px]"></i> <span id="pillHealth">100%</span></span>
            <span class="text-slate-300">•</span>
            <span class="flex items-center gap-1 text-amber-600"><i class="fa-solid fa-bolt text-[11px]"></i> <span id="pillEnergy">100%</span></span>
            <span class="text-slate-300">•</span>
            <span class="flex items-center gap-1 font-mono text-emerald-700 font-extrabold"><span id="pillCash">₦0</span></span>
        </div>
        <div class="w-6 h-6 rounded-full bg-slate-100 group-hover:bg-slate-200 flex items-center justify-center text-slate-600 transition">
            <i id="pillIcon" class="fa-solid fa-chevron-up text-[10px]"></i>
        </div>
    </div>

    <!-- EXPANDABLE FULL VITALS SHEET / MODAL -->
    <div id="vitalsDrawerModal" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm hidden items-end sm:items-center justify-center sm:justify-start sm:pl-5 z-50 transition-all">
        <div class="bg-white border border-slate-200 rounded-t-3xl sm:rounded-3xl max-w-sm w-full p-6 shadow-2xl relative animate-fade-up">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <div id="drawerAvatarPill" class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-sm font-bold text-white shadow-sm" style="background-color: <?= htmlspecialchars($char['skin_tone'] ?: '#704225') ?>;">
                        <i class="fa-solid fa-user text-white text-xs"></i>
                    </div>
                    <div>
                        <h4 id="hudName" class="font-extrabold text-sm text-slate-900"><?= htmlspecialchars($char['full_name']) ?></h4>
                        <span id="hudAge" class="text-xs text-slate-500 font-medium"><?= $char['age'] ?> yrs • Day <?= $char['days_lived'] ?></span>
                    </div>
                </div>
                <button onclick="toggleVitalsDrawer(false)" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Vitals Progress Sliders -->
            <div class="space-y-3.5 mb-5 text-xs">
                <div>
                    <div class="flex justify-between font-bold mb-1">
                        <span class="text-amber-700"><i class="fa-solid fa-bolt mr-1"></i> Energy</span>
                        <span id="valEnergy" class="text-slate-600 font-mono"><?= $char['energy'] ?>%</span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div id="barEnergy" class="bg-amber-500 h-full rounded-full transition-all duration-300" style="width: <?= $char['energy'] ?>%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between font-bold mb-1">
                        <span class="text-rose-700"><i class="fa-solid fa-heart mr-1"></i> Health</span>
                        <span id="valHealth" class="text-slate-600 font-mono"><?= $char['health'] ?>%</span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div id="barHealth" class="bg-rose-500 h-full rounded-full transition-all duration-300" style="width: <?= $char['health'] ?>%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between font-bold mb-1">
                        <span class="text-emerald-700"><i class="fa-solid fa-face-smile mr-1"></i> Happiness</span>
                        <span id="valHappiness" class="text-slate-600 font-mono"><?= $char['happiness'] ?>%</span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div id="barHappiness" class="bg-emerald-500 h-full rounded-full transition-all duration-300" style="width: <?= $char['happiness'] ?>%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between font-bold mb-1">
                        <span class="text-sky-700"><i class="fa-solid fa-brain mr-1"></i> Intelligence</span>
                        <span id="valIntelligence" class="text-slate-600 font-mono"><?= $char['intelligence'] ?> IQ</span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div id="barIntelligence" class="bg-sky-500 h-full rounded-full transition-all duration-300" style="width: <?= $char['intelligence'] ?>%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between font-bold mb-1">
                        <span class="text-purple-700"><i class="fa-solid fa-shield mr-1"></i> Street Cred</span>
                        <span id="valStreetCred" class="text-slate-600 font-mono"><?= $char['street_cred'] ?></span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div id="barStreetCred" class="bg-purple-600 h-full rounded-full transition-all duration-300" style="width: <?= min(100, $char['street_cred']) ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Financials Summary Inside Drawer -->
            <div class="bg-slate-50 border border-slate-200 p-3.5 rounded-2xl mb-4 text-xs space-y-1.5 font-medium">
                <div class="flex justify-between">
                    <span class="text-slate-500">Cash Reserves:</span>
                    <strong id="hudCash" class="font-mono text-emerald-700">₦<?= number_format($char['cash'], 2) ?></strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Bank Savings:</span>
                    <strong id="hudBank" class="font-mono text-blue-700">₦<?= number_format($char['bank'], 2) ?></strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Calculated Net Worth:</span>
                    <strong id="hudNetWorth" class="font-mono text-slate-900">₦0.00</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Living Enclave:</span>
                    <strong id="hudDistrict" class="text-slate-800"><?= htmlspecialchars($char['district']) ?></strong>
                </div>
            </div>

            <button onclick="toggleVitalsDrawer(false)" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs transition">
                Minimize to Pill
            </button>
        </div>
    </div>

    <!-- ========================================================
         5. INTERACTIVE IN-GAME SMARTPHONE MODAL
         (AbujaPhone Pro)
         ======================================================== -->
    <div id="phoneWidgetModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <!-- Phone Device Frame -->
        <div class="bg-slate-900 border-4 border-slate-800 rounded-[44px] max-w-[340px] w-full h-[620px] shadow-2xl relative p-3 flex flex-col overflow-hidden animate-fade-up">
            
            <!-- Dynamic Island / Speaker Notch -->
            <div class="absolute top-4 left-1/2 -translate-x-1/2 w-24 h-5 bg-black rounded-full z-20 flex items-center justify-center">
                <div class="w-2.5 h-2.5 rounded-full bg-slate-800"></div>
            </div>

            <!-- Phone Inner Screen -->
            <div class="bg-white rounded-[34px] flex-1 flex flex-col overflow-hidden relative">
                
                <!-- Status Bar -->
                <div class="h-10 px-5 flex items-center justify-between text-[11px] font-bold text-slate-800 z-10 pt-1">
                    <span id="phoneTime">09:41</span>
                    <div class="flex items-center gap-1.5 text-[10px]">
                        <i class="fa-solid fa-signal"></i>
                        <i class="fa-solid fa-wifi"></i>
                        <i class="fa-solid fa-battery-full text-emerald-600"></i>
                    </div>
                </div>

                <!-- Screen Contents Container -->
                <div class="flex-1 overflow-y-auto p-4">
                    
                    <!-- HOME SCREEN (App Grid) -->
                    <div id="phone-app-home" class="phone-screen space-y-4">
                        <div class="text-center pt-2 pb-4">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-widest block">AbujaOS</span>
                            <h3 class="text-xl font-extrabold text-slate-900"><?= htmlspecialchars($char['full_name']) ?></h3>
                        </div>

                        <div class="grid grid-cols-3 gap-3 text-center">
                            <!-- OPay / Bank -->
                            <button onclick="PhoneApp.openApp('bank')" class="flex flex-col items-center group">
                                <div class="w-14 h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl shadow-md group-hover:scale-105 transition">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                                <span class="text-[11px] font-bold text-slate-700 mt-1.5">AbujaPay</span>
                            </button>

                            <!-- Games -->
                            <button onclick="PhoneApp.openApp('games')" class="flex flex-col items-center group">
                                <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shadow-md group-hover:scale-105 transition">
                                    <i class="fa-solid fa-gamepad"></i>
                                </div>
                                <span class="text-[11px] font-bold text-slate-700 mt-1.5">Arcade</span>
                            </button>

                            <!-- WhatsApp Chat -->
                            <button onclick="PhoneApp.openApp('chat')" class="flex flex-col items-center group">
                                <div class="w-14 h-14 rounded-2xl bg-green-500 text-white flex items-center justify-center text-xl shadow-md group-hover:scale-105 transition">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </div>
                                <span class="text-[11px] font-bold text-slate-700 mt-1.5">NaijaChat</span>
                            </button>

                            <!-- Bolt Rides -->
                            <button onclick="PhoneApp.openApp('rides')" class="flex flex-col items-center group">
                                <div class="w-14 h-14 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-xl shadow-md group-hover:scale-105 transition">
                                    <i class="fa-solid fa-car"></i>
                                </div>
                                <span class="text-[11px] font-bold text-slate-700 mt-1.5">Bolt</span>
                            </button>

                            <!-- Wardrobe / Jiji Style -->
                            <button onclick="PhoneApp.openApp('wardrobe')" class="flex flex-col items-center group">
                                <div class="w-14 h-14 rounded-2xl bg-purple-600 text-white flex items-center justify-center text-xl shadow-md group-hover:scale-105 transition">
                                    <i class="fa-solid fa-shirt"></i>
                                </div>
                                <span class="text-[11px] font-bold text-slate-700 mt-1.5">Wardrobe</span>
                            </button>

                            <!-- Close Phone -->
                            <button onclick="PhoneApp.toggle()" class="flex flex-col items-center group">
                                <div class="w-14 h-14 rounded-2xl bg-slate-200 text-slate-600 flex items-center justify-center text-xl shadow-sm group-hover:scale-105 transition">
                                    <i class="fa-solid fa-power-off"></i>
                                </div>
                                <span class="text-[11px] font-bold text-slate-700 mt-1.5">Lock</span>
                            </button>
                        </div>
                    </div>

                    <!-- APP: BANK (AbujaPay) -->
                    <div id="phone-app-bank" class="phone-screen hidden space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <button onclick="PhoneApp.goHome()" class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600"><i class="fa-solid fa-arrow-left"></i></button>
                            <h4 class="font-bold text-sm text-slate-900">AbujaPay Mobile</h4>
                        </div>
                        <div class="bg-emerald-600 text-white p-4 rounded-2xl shadow-md">
                            <span class="text-[10px] text-emerald-100 font-semibold uppercase block">Savings Balance</span>
                            <span id="phoneBankBalance" class="text-xl font-extrabold font-mono block">₦0.00</span>
                            <span class="text-[10px] text-emerald-200 mt-2 block">Cash on Hand: <strong id="phoneCashBalance">₦0.00</strong></span>
                        </div>
                        <div class="space-y-2">
                            <button onclick="PhoneApp.quickTransfer()" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl flex items-center justify-center gap-2">
                                <i class="fa-solid fa-paper-plane text-emerald-600"></i> Quick Transfer to Savings
                            </button>
                            <button onclick="PhoneApp.buyAirtime()" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl flex items-center justify-center gap-2">
                                <i class="fa-solid fa-wifi text-blue-600"></i> Buy Airtime & Data (₦1,000)
                            </button>
                        </div>
                    </div>

                    <!-- APP: GAMES (Arcade) -->
                    <div id="phone-app-games" class="phone-screen hidden space-y-3">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <button onclick="PhoneApp.goHome()" class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600"><i class="fa-solid fa-arrow-left"></i></button>
                            <h4 class="font-bold text-sm text-slate-900">Abuja Mini-Games</h4>
                        </div>
                        <button onclick="PhoneApp.playTrivia()" class="w-full p-3 bg-slate-100 hover:bg-slate-200 text-left rounded-2xl flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-base"><i class="fa-solid fa-brain"></i></div>
                            <div>
                                <h5 class="font-bold text-xs text-slate-900">Abuja Trivia Quiz</h5>
                                <p class="text-[10px] text-slate-500">Answer right to win ₦5,000 bonus cash.</p>
                            </div>
                        </button>
                        <button onclick="PhoneApp.playDiceMinigame()" class="w-full p-3 bg-slate-100 hover:bg-slate-200 text-left rounded-2xl flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center text-base"><i class="fa-solid fa-dice"></i></div>
                            <div>
                                <h5 class="font-bold text-xs text-slate-900">Quick Dice Roll</h5>
                                <p class="text-[10px] text-slate-500">Stake ₦2,000 to win ₦4,000 on high/low.</p>
                            </div>
                        </button>
                    </div>

                    <!-- APP: CHAT (NaijaChat) -->
                    <div id="phone-app-chat" class="phone-screen hidden space-y-3">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <button onclick="PhoneApp.goHome()" class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600"><i class="fa-solid fa-arrow-left"></i></button>
                            <h4 class="font-bold text-sm text-slate-900">NaijaChat (WhatsApp)</h4>
                        </div>
                        <div id="phoneChatList" class="space-y-2"></div>
                    </div>

                    <!-- APP: RIDES (Bolt) -->
                    <div id="phone-app-rides" class="phone-screen hidden space-y-3">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <button onclick="PhoneApp.goHome()" class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600"><i class="fa-solid fa-arrow-left"></i></button>
                            <h4 class="font-bold text-sm text-slate-900">Bolt Ride Hailing</h4>
                        </div>
                        <p class="text-xs text-slate-500">Choose your destination to book an instant cab ride:</p>
                        <div class="space-y-1.5 text-xs">
                            <button onclick="PhoneApp.orderBoltRide('Wuse 2', 3000)" class="w-full p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl flex justify-between font-medium">
                                <span>Wuse 2 (Club & Lounges)</span>
                                <strong class="text-emerald-700">₦3,000</strong>
                            </button>
                            <button onclick="PhoneApp.orderBoltRide('Maitama', 5000)" class="w-full p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl flex justify-between font-medium">
                                <span>Maitama Diplomatic Zone</span>
                                <strong class="text-emerald-700">₦5,000</strong>
                            </button>
                            <button onclick="PhoneApp.orderBoltRide('Central Area', 2500)" class="w-full p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl flex justify-between font-medium">
                                <span>Central Area (Ministries)</span>
                                <strong class="text-emerald-700">₦2,500</strong>
                            </button>
                            <button onclick="PhoneApp.orderBoltRide('Kubwa', 1500)" class="w-full p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl flex justify-between font-medium">
                                <span>Kubwa Satellite</span>
                                <strong class="text-emerald-700">₦1,500</strong>
                            </button>
                        </div>
                    </div>

                    <!-- APP: WARDROBE -->
                    <div id="phone-app-wardrobe" class="phone-screen hidden space-y-3">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <button onclick="PhoneApp.goHome()" class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600"><i class="fa-solid fa-arrow-left"></i></button>
                            <h4 class="font-bold text-sm text-slate-900">Wardrobe & Style</h4>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Outfit</label>
                            <select id="phoneSelectOutfit" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                                <option value="hoodie">🧥 Tech Bro Hoodie</option>
                                <option value="black_hoodie">🖤 Nightclub Black Streetwear</option>
                                <option value="tshirt">👕 Casual White Tee & Denim</option>
                                <option value="agbada">🪡 Royal Agbada & Fila Cap</option>
                                <option value="kaftan">👑 Senator Navy Kaftan</option>
                                <option value="suit">💼 Executive Navy Suit</option>
                                <option value="blazer">👓 Techie Blazer & Chinos</option>
                                <option value="polo">🎾 Country Club Polo & Khakis</option>
                                <option value="joggers">🏃 Fleece Joggers & Slides</option>
                                <option value="sunglasses">🕶️ VIP Aviator Shades</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Hairstyle</label>
                            <select id="phoneSelectHair" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                                <option value="fade">Low Cut / Fade</option>
                                <option value="afro">Afro Crown</option>
                                <option value="dreads">Dreadlocks</option>
                            </select>
                        </div>
                        <button onclick="PhoneApp.toggle(); GameApp.openWardrobeModal();" class="w-full py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-sm flex items-center justify-center gap-2">
                            <i class="fa-solid fa-cube"></i> Open 3D Bitmoji Studio
                        </button>
                        <button onclick="PhoneApp.saveWardrobeStyle()" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl transition">
                            Quick Apply Look
                        </button>
                    </div>

                </div>

                <!-- Bottom Home Bar -->
                <div class="h-6 flex items-center justify-center pb-1">
                    <button onclick="PhoneApp.goHome()" class="w-28 h-1 bg-slate-300 hover:bg-slate-400 rounded-full transition"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Random Life Encounter Modal -->
    <div id="randomEventModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl relative animate-fade-up">
            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            
            <h3 id="eventTitle" class="text-base sm:text-lg font-bold text-slate-900 mb-2">Life Event Encounter</h3>
            <p id="eventDesc" class="text-xs text-slate-600 leading-relaxed mb-6"></p>

            <div class="space-y-2.5">
                <button onclick="GameApp.chooseEventOption('a')" id="eventChoiceA" class="w-full text-left p-4 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-2xl text-xs font-semibold border border-slate-200 transition active:scale-95">
                    Option A
                </button>
                <button onclick="GameApp.chooseEventOption('b')" id="eventChoiceB" class="w-full text-left p-4 bg-slate-50 hover:bg-slate-100 text-slate-800 rounded-2xl text-xs font-semibold border border-slate-200 transition active:scale-95">
                    Option B
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================
         6. IN-GAME BITMOJI 3D WARDROBE STUDIO MODAL
         ======================================================== -->
    <div id="gameWardrobeModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl relative my-8 animate-fade-up">
            <button onclick="GameApp.closeWardrobeModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center absolute top-5 right-5 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Bitmoji 3D Wardrobe Studio</h3>
                    <p class="text-xs text-slate-500">Change tops, jeans, kicks, and hairstyles in real time. Drag to rotate 360°!</p>
                </div>
                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full bg-purple-50 text-purple-800 border border-purple-200">
                    Live 3D Customizer
                </span>
            </div>

            <!-- Studio Grid -->
            <div class="space-y-4">
                
                <!-- 1. BASE PERSONA ARCHETYPE SELECTOR -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-2">1. Character Persona</label>
                    <div class="grid grid-cols-3 sm:grid-cols-9 gap-2">
                        <button type="button" onclick="GameApp.setWardrobeCharacter('tunde')" class="wardrobe-char-card p-2 rounded-2xl border-2 border-purple-600 bg-purple-50 text-center transition transform active:scale-95 group">
                            <img src="assets/img/characters/tunde/Man_standing_in_hoodie_20261005064533.jpg" class="w-10 h-10 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Tunde">
                            <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Tunde</span>
                            <span class="block text-[9px] text-slate-500 truncate">Tech Bro</span>
                        </button>
                        <button type="button" onclick="GameApp.setWardrobeCharacter('emeka')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                            <img src="assets/img/characters/emeka/Man_wearing_green_hoodie_standing_20261005064521.jpg" class="w-10 h-10 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Emeka">
                            <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Emeka</span>
                            <span class="block text-[9px] text-slate-500 truncate">Dealmaker</span>
                        </button>
                        <button type="button" onclick="GameApp.setWardrobeCharacter('farouk')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                            <img src="assets/img/characters/farouk/Man_wearing_green_streetwear_hoodie_20261005064505.jpg" class="w-10 h-10 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Farouk">
                            <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Farouk</span>
                            <span class="block text-[9px] text-slate-500 truncate">Aristocrat</span>
                        </button>
                        <button type="button" onclick="GameApp.setWardrobeCharacter('chidi')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                            <img src="assets/img/characters/chidi/Man_standing_in_hoodie_20261005064449.jpg" class="w-10 h-10 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Chidi">
                            <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Chidi</span>
                            <span class="block text-[9px] text-slate-500 truncate">Creative</span>
                        </button>
                        <button type="button" onclick="GameApp.setWardrobeCharacter('zainab')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                            <img src="assets/img/characters/zainab/Young_woman_standing_wearing_hoodie_20261005064439.jpg" class="w-10 h-10 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Zainab">
                            <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Zainab</span>
                            <span class="block text-[9px] text-slate-500 truncate">FinTech</span>
                        </button>
                        <button type="button" onclick="GameApp.setWardrobeCharacter('blessing')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                            <img src="assets/img/characters/blessing/Young_woman_standing_with_sneakers_20261005064429.jpg" class="w-10 h-10 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Blessing">
                            <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Blessing</span>
                            <span class="block text-[9px] text-slate-500 truncate">Curator</span>
                        </button>
                        <button type="button" onclick="GameApp.setWardrobeCharacter('ibrahim')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                            <img src="assets/img/characters/ibrahim/Man_wearing_streetwear_hoodie_20261005064416.jpg" class="w-10 h-10 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Ibrahim">
                            <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Ibrahim</span>
                            <span class="block text-[9px] text-slate-500 truncate">Oil & Gas</span>
                        </button>
                        <button type="button" onclick="GameApp.setWardrobeCharacter('segun')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                            <img src="assets/img/characters/segun/Man_wearing_green_hoodie_standing_20261005064405.jpg" class="w-10 h-10 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Segun">
                            <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Segun</span>
                            <span class="block text-[9px] text-slate-500 truncate">Hustler</span>
                        </button>
                        <button type="button" onclick="GameApp.setWardrobeCharacter('ngozi')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                            <img src="assets/img/characters/ngozi/Woman_wearing_hoodie_and_sunglasses_20261005064346.jpg" class="w-10 h-10 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Ngozi">
                            <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Ngozi</span>
                            <span class="block text-[9px] text-slate-500 truncate">Attorney</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
                    <!-- 3D Canvas Column -->
                    <div class="md:col-span-5 flex flex-col items-center">
                        <div id="gameBitmojiContainer" class="w-full h-[360px] sm:h-[400px] rounded-2xl bg-white border border-slate-200 relative shadow-inner overflow-hidden flex items-center justify-center cursor-grab active:cursor-grabbing"></div>
                        <div class="w-full mt-2.5 space-y-1.5">
                            <button type="button" onclick="GameApp.turnWardrobeAvatar()" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-arrows-rotate text-xs"></i> Turn Around 180°
                            </button>
                            <p class="text-[11px] text-slate-500 text-center font-medium">
                                <i class="fa-solid fa-hand-pointer text-slate-400 mr-1"></i> Drag left/right to spin 360°
                            </p>
                        </div>
                    </div>

                    <!-- Customization Controls Column -->
                    <div class="md:col-span-7 space-y-3.5 max-h-[440px] overflow-y-auto pr-1 text-xs">
                        
                        <!-- 2. REAL-TIME 3D OUTFITS -->
                        <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/80">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block font-bold text-slate-800">2. Wardrobe & Outfits</label>
                                <span class="text-[10px] text-purple-700 font-bold">12 Variations</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" onclick="GameApp.setWardrobeOutfit('hoodie')" class="wardrobe-outfit-btn p-2 rounded-xl border border-purple-600 bg-purple-50 text-purple-950 font-bold transition text-left active:scale-95">
                                    🧥 Tech Bro Hoodie
                                </button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('black_hoodie')" class="wardrobe-outfit-btn p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                    🖤 Nightclub Black Streetwear
                                </button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('tshirt')" class="wardrobe-outfit-btn p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                    👕 Casual White Tee & Denim
                                </button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('agbada')" class="wardrobe-outfit-btn p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                    🪡 Royal Agbada & Fila Cap
                                </button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('kaftan')" class="wardrobe-outfit-btn p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                    👑 Senator Navy Kaftan
                                </button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('suit')" class="wardrobe-outfit-btn p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                    💼 Executive Navy Suit
                                </button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('blazer')" class="wardrobe-outfit-btn p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                    👓 Techie Blazer & Chinos
                                </button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('polo')" class="wardrobe-outfit-btn p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                    🎾 Country Club Polo & Khakis
                                </button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('joggers')" class="wardrobe-outfit-btn p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                    🏃 Fleece Joggers & Slides
                                </button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('sunglasses')" class="wardrobe-outfit-btn p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                    🕶️ VIP Shades & Streetwear
                                </button>
                            </div>
                        </div>

                        <!-- 3. FOOTWEAR KICKS -->
                        <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/80">
                            <label class="block font-bold text-slate-800 mb-2">3. Footwear Kicks</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" onclick="GameApp.setWardrobeOutfit('hoodie')" class="p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left">👟 White AF1s</button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('black_hoodie')" class="p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left">🏀 Air Jordans</button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('suit')" class="p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left">👞 Leather Loafers</button>
                                <button type="button" onclick="GameApp.setWardrobeOutfit('joggers')" class="p-2 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left">🩴 Casual Slides</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="pt-4 mt-2 border-t border-slate-100 flex gap-3">
                <button onclick="GameApp.closeWardrobeModal()" class="w-1/3 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs transition">Cancel</button>
                <button onclick="GameApp.saveWardrobeLook()" class="flex-1 py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95">Save & Apply Look in 3D</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/world3d.js"></script>
    <script src="assets/js/phone.js"></script>
    <script src="assets/js/game.js"></script>
    <script>
        function toggleVitalsDrawer(forceState = null) {
            const drawer = document.getElementById('vitalsDrawerModal');
            const icon = document.getElementById('pillIcon');
            const isOpen = !drawer.classList.contains('hidden');
            const next = forceState !== null ? forceState : !isOpen;

            if (next) {
                drawer.classList.remove('hidden');
                drawer.classList.add('flex');
                if (icon) icon.className = "fa-solid fa-chevron-down text-[10px]";
            } else {
                drawer.classList.add('hidden');
                drawer.classList.remove('flex');
                if (icon) icon.className = "fa-solid fa-chevron-up text-[10px]";
            }
        }
    </script>
</body>
</html>
