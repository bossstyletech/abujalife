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

        <!-- Weather Banner (Rain & Flood) -->
        <div id="weatherBanner" class="hidden bg-gradient-to-r from-slate-700 to-blue-900 text-white rounded-2xl p-3 flex items-center gap-3 text-xs font-semibold">
            <i class="fa-solid fa-cloud-rain text-blue-300 text-lg animate-pulse"></i>
            <div class="flex-1">
                <span class="font-extrabold text-sm block" id="weatherTitle">🌧️ Heavy Rain Alert – FCT!</span>
                <span id="weatherDesc" class="text-blue-200">Danfo fares have doubled. Flooded roads slowing traffic. Buy Pure Water from hawkers.</span>
            </div>
            <span id="weatherFloodBadge" class="px-2 py-1 rounded-full bg-rose-500 text-white text-[10px] font-bold">FLOOD LV.2</span>
        </div>

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
                    <button onclick="World3D.cycleAtmosphere()" class="px-2.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 transition" title="Toggle Lighting (Day/Sunset/Night)">
                        <i class="fa-solid fa-sun text-xs text-amber-500"></i>
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
            <div id="world3d-container" class="w-full h-[420px] sm:h-[480px] rounded-3xl bg-slate-50 border border-slate-200/80 relative cursor-grab active:cursor-grabbing overflow-hidden shadow-inner">
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
        <!-- ========================================================
             3. ARRANGE MASTER MENUS & CATEGORIES (ORGANIZED NAVIGATION)
             ======================================================== -->
        <div class="bg-white p-3 rounded-3xl border border-slate-200/90 shadow-sm space-y-2.5">
            <!-- Tier 1: Master Category Hubs -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-slate-100 p-1.5 rounded-2xl text-xs font-bold text-slate-600">
                <button type="button" onclick="GameApp.switchMenuHub('city')" id="hubBtn-city" class="hub-btn py-2 px-3 rounded-xl bg-white text-emerald-950 font-extrabold shadow-sm transition flex items-center justify-center gap-1.5 active:scale-95">
                    <i class="fa-solid fa-city text-emerald-600 text-xs"></i> <span>City & Commute</span>
                </button>
                <button type="button" onclick="GameApp.switchMenuHub('hustle')" id="hubBtn-hustle" class="hub-btn py-2 px-3 rounded-xl text-slate-600 hover:text-slate-900 font-bold transition flex items-center justify-center gap-1.5 active:scale-95">
                    <i class="fa-solid fa-briefcase text-blue-600 text-xs"></i> <span>Career & Hustle</span>
                </button>
                <button type="button" onclick="GameApp.switchMenuHub('wealth')" id="hubBtn-wealth" class="hub-btn py-2 px-3 rounded-xl text-slate-600 hover:text-slate-900 font-bold transition flex items-center justify-center gap-1.5 active:scale-95">
                    <i class="fa-solid fa-building-columns text-amber-600 text-xs"></i> <span>Assets & Bank</span>
                </button>
                <button type="button" onclick="GameApp.switchMenuHub('social')" id="hubBtn-social" class="hub-btn py-2 px-3 rounded-xl text-slate-600 hover:text-slate-900 font-bold transition flex items-center justify-center gap-1.5 active:scale-95">
                    <i class="fa-solid fa-champagne-glasses text-purple-600 text-xs"></i> <span>Social & Leisure</span>
                </button>
            </div>

            <!-- Tier 2: Arranged Sub-Tabs for Active Hub -->
            <!-- Hub: City & Commute -->
            <div id="subnav-city" class="hub-subnav flex items-center gap-2 overflow-x-auto text-xs font-bold">
                <button id="btn-tab-overview" onclick="GameApp.switchTab('overview')" class="tab-btn px-4 py-2 rounded-xl bg-emerald-600 text-white transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-newspaper text-xs"></i> Life Feed
                </button>
                <button id="btn-tab-transport" onclick="GameApp.switchTab('transport')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200 transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-bus text-xs text-yellow-600"></i> Lagos/FCT Streets & Danfo
                </button>
                <button id="btn-tab-vehicles" onclick="GameApp.switchTab('vehicles')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200 transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-car text-xs text-slate-700"></i> Car Garage
                </button>
            </div>

            <!-- Hub: Career & Hustle -->
            <div id="subnav-hustle" class="hub-subnav hidden flex items-center gap-2 overflow-x-auto text-xs font-bold">
                <button id="btn-tab-jobs" onclick="GameApp.switchTab('jobs')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200 transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-landmark text-xs text-emerald-600"></i> Careers & Ministries
                </button>
                <button id="btn-tab-hustles" onclick="GameApp.switchTab('hustles')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200 transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-bolt text-xs text-amber-500"></i> Street Hustles
                </button>
                <button id="btn-tab-economy" onclick="GameApp.switchTab('economy')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200 transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-scale-balanced text-xs text-orange-600"></i> Market Haggling & Sapa
                </button>
            </div>

            <!-- Hub: Assets & Bank -->
            <div id="subnav-wealth" class="hub-subnav hidden flex items-center gap-2 overflow-x-auto text-xs font-bold">
                <button id="btn-tab-realestate" onclick="GameApp.switchTab('realestate')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200 transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-house-chimney text-xs text-teal-600"></i> Real Estate
                </button>
                <button id="btn-tab-bank" onclick="GameApp.switchTab('bank')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200 transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-vault text-xs text-blue-600"></i> Bank & Loans
                </button>
                <button id="btn-tab-leaderboard" onclick="GameApp.switchTab('leaderboard')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200 transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-trophy text-xs text-amber-500"></i> Hall of Fame
                </button>
            </div>

            <!-- Hub: Social & Leisure -->
            <div id="subnav-social" class="hub-subnav hidden flex items-center gap-2 overflow-x-auto text-xs font-bold">
                <button id="btn-tab-social" onclick="GameApp.switchTab('social')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200 transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-champagne-glasses text-xs text-purple-600"></i> Owambe & Community
                </button>
                <button id="btn-tab-lifestyle" onclick="GameApp.switchTab('lifestyle')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200 transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-tree text-xs text-emerald-600"></i> Abuja Leisure Hotspots
                </button>
                <button id="btn-tab-casino" onclick="GameApp.switchTab('casino')" class="tab-btn px-4 py-2 rounded-xl text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200 transition active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-dice text-xs text-rose-600"></i> Abuja Bet & Dice
                </button>
            </div>
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

        <!-- TRANSPORT TAB -->
        <section id="tab-transport" class="game-tab-content hidden">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-slate-900">🚌 Abuja Street Transport & Commuting</h3>
                <p class="text-xs text-slate-500">Navigate Danfo buses, LASTMA checkpoints, Okadas, and Go-Slow traffic like a true FCT citizen.</p>
            </div>
            
            <!-- Weather Status Card -->
            <div id="transportWeatherCard" class="bg-slate-50 border border-slate-200 rounded-2xl p-4 mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-xl">
                        <span id="weatherIcon">☀️</span>
                    </div>
                    <div>
                        <h4 id="weatherStatusTitle" class="font-bold text-sm text-slate-900">Clear Skies</h4>
                        <p id="weatherStatusDesc" class="text-xs text-slate-500">Normal fares apply across FCT.</p>
                    </div>
                </div>
                <button onclick="GameApp.checkWeather()" class="text-xs text-slate-500 hover:text-slate-900 transition">
                    <i class="fa-solid fa-rotate-right mr-1"></i> Refresh
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Danfo Rush -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-yellow-400 flex items-center justify-center text-white text-lg mb-3">🚐</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Danfo Rush Hour</h4>
                    <p class="text-xs text-slate-500 mb-3">Race to grab a seat in a yellow Danfo during rush hour. Fares double when it rains!</p>
                    <div id="danfoFareDisplay" class="text-xs font-bold text-amber-700 mb-3">Base Fare: ₦500 | Rain: ₦1,000</div>
                    <button onclick="GameApp.playDanfoRush()" class="w-full py-2.5 bg-yellow-500 hover:bg-yellow-400 text-white font-bold text-xs rounded-xl transition active:scale-95">
                        🏃 Rush to Board!
                    </button>
                </div>
                
                <!-- LASTMA Checkpoint -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-rose-100 flex items-center justify-center text-rose-700 text-lg mb-3">🚦</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">LASTMA Checkpoint</h4>
                    <p class="text-xs text-slate-500 mb-3">Private car drivers face random checkpoints. Minor violation? Negotiate or pay fine.</p>
                    <button onclick="GameApp.triggerLastmaCheckpoint()" class="w-full py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl transition active:scale-95">
                        🚗 Drive Through Checkpoint
                    </button>
                </div>
                
                <!-- Okada Ride -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-slate-800 flex items-center justify-center text-white text-lg mb-3">🏍️</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Okada Express</h4>
                    <p class="text-xs text-slate-500 mb-3">Fast travel via motorcycle. Risk getting dropped in a puddle or stopped on banned highways!</p>
                    <button onclick="GameApp.takeOkadaRide()" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition active:scale-95">
                        🏍️ Hop On Okada!
                    </button>
                </div>
                
                <!-- Go-Slow Hawkers -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-orange-100 flex items-center justify-center text-orange-700 text-lg mb-3">🌯</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Go-Slow Hawkers</h4>
                    <p class="text-xs text-slate-500 mb-2">Stuck in traffic? Buy snacks from fast-moving hawkers through your window!</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="GameApp.buyFromHawker('gala')" class="py-2 bg-orange-50 hover:bg-orange-100 border border-orange-200 text-orange-900 font-bold text-[11px] rounded-xl transition active:scale-95">🌭 Gala ₦300</button>
                        <button onclick="GameApp.buyFromHawker('plantain_chips')" class="py-2 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 text-yellow-900 font-bold text-[11px] rounded-xl transition active:scale-95">🍟 Plantain ₦200</button>
                        <button onclick="GameApp.buyFromHawker('lacasera')" class="py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-900 font-bold text-[11px] rounded-xl transition active:scale-95">🥤 Lacasera ₦250</button>
                        <button onclick="GameApp.buyFromHawker('purewater')" class="py-2 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-900 font-bold text-[11px] rounded-xl transition active:scale-95">💧 Pure Water ₦50</button>
                    </div>
                </div>
                
                <!-- Late Night Suya -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-red-100 flex items-center justify-center text-red-700 text-lg mb-3">🍢</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Late-Night Suya Spot</h4>
                    <p class="text-xs text-slate-500 mb-3">Open after 8PM only. Best stat boost in the game. A social hub for networking in Abuja!</p>
                    <button onclick="GameApp.visitSuyaSpot()" class="w-full py-2.5 bg-red-600 hover:bg-red-500 text-white font-bold text-xs rounded-xl transition active:scale-95">
                        🔥 Order Suya & Relax
                    </button>
                </div>
                
                <!-- Agbero Encounter -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-slate-200 flex items-center justify-center text-slate-700 text-lg mb-3">😤</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Agbero Encounter</h4>
                    <p class="text-xs text-slate-500 mb-3">Drivers & Keke riders face daily agbero dues at bus stops. Pay or risk your mirrors!</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="GameApp.dealWithAgbero('pay_dues')" class="py-2 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-900 font-bold text-[11px] rounded-xl transition active:scale-95">💵 Pay Dues</button>
                        <button onclick="GameApp.dealWithAgbero('refuse')" class="py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-900 font-bold text-[11px] rounded-xl transition active:scale-95">🛡️ Refuse & Risk</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- SOCIAL LIFE TAB -->
        <section id="tab-social" class="game-tab-content hidden">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-slate-900">🎉 Abuja Social Life & Community</h3>
                <p class="text-xs text-slate-500">Owambe parties, religious services, networking, and money spraying. "Who You Know" unlocks everything.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Owambe Party -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-purple-100 flex items-center justify-center text-purple-700 text-lg mb-3">👗</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Owambe Saturday</h4>
                    <p class="text-xs text-slate-500 mb-3">Aso-Ebi invite! Attend, spray money on the dance floor, and network with Abuja's elite.</p>
                    <div class="space-y-2">
                        <button onclick="GameApp.owambeAction('attend')" class="w-full py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl transition active:scale-95">👗 Attend (₦5,000 Aso-Ebi)</button>
                        <div class="flex gap-2">
                            <input type="number" id="sprayAmount" placeholder="₦ to spray" value="10000" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                            <button onclick="GameApp.owambeAction('spray_money')" class="px-3 py-2 bg-amber-500 hover:bg-amber-400 text-white font-bold text-xs rounded-xl transition active:scale-95">💸 Spray!</button>
                        </div>
                        <button onclick="GameApp.owambeAction('network')" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl transition active:scale-95">🤝 Network & Mingle</button>
                    </div>
                </div>
                
                <!-- Religious Services -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-700 text-lg mb-3">🕌</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Religious Services</h4>
                    <p class="text-xs text-slate-500 mb-3">Attend Friday Juma'at or Sunday service. Massive luck boost, health recovery & job tips from congregation.</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="GameApp.attendReligiousService('jumat')" class="py-2.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-900 font-bold text-xs rounded-xl transition active:scale-95">🕌 Juma'at</button>
                        <button onclick="GameApp.attendReligiousService('church')" class="py-2.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-900 font-bold text-xs rounded-xl transition active:scale-95">⛪ Church</button>
                    </div>
                </div>
                
                <!-- Ajo Thrift System -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-teal-100 flex items-center justify-center text-teal-700 text-lg mb-3">🏦</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Ajo Thrift Contribution</h4>
                    <p class="text-xs text-slate-500 mb-3">Community banking. Contribute ₦5,000/week. After 12 weeks, collect your lump sum + 10% bonus!</p>
                    <div id="ajoStatus" class="text-xs font-bold text-teal-700 mb-3">Not in any Ajo group yet.</div>
                    <div class="space-y-2">
                        <button onclick="GameApp.ajoAction('join')" class="w-full py-2 bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs rounded-xl transition active:scale-95">🤝 Join Ajo Group</button>
                        <button onclick="GameApp.ajoAction('contribute')" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl transition active:scale-95">💵 Contribute This Week (₦5,000)</button>
                        <button onclick="GameApp.ajoAction('check_payout')" class="w-full py-2 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-900 font-bold text-xs rounded-xl transition active:scale-95">💰 Check Payout Status</button>
                    </div>
                </div>
                
                <!-- NEPA Power Roulette -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-yellow-100 flex items-center justify-center text-yellow-700 text-xl mb-3">⚡</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">NEPA / Power Grid Roulette</h4>
                    <p class="text-xs text-slate-500 mb-3">Electricity is never guaranteed. Buy generator fuel, use inverter, or just pray NEPA brings light.</p>
                    <div class="space-y-2">
                        <button onclick="GameApp.nepaAction('buy_fuel')" class="w-full py-2 bg-amber-500 hover:bg-amber-400 text-white font-bold text-xs rounded-xl transition active:scale-95">⛽ Buy Generator Fuel (₦8-15k)</button>
                        <button onclick="GameApp.nepaAction('use_inverter')" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl transition active:scale-95">🔋 Use Inverter</button>
                        <button onclick="GameApp.nepaAction('pray_light')" class="w-full py-2 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-900 font-bold text-xs rounded-xl transition active:scale-95">🙏 Pray for Light (Risky!)</button>
                        <button onclick="GameApp.nepaAction('buy_inverter')" class="w-full py-2 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-900 font-bold text-xs rounded-xl transition active:scale-95">🏪 Buy Inverter System (₦120k)</button>
                    </div>
                </div>
                
                <!-- Who You Know -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-slate-900 flex items-center justify-center text-white text-lg mb-3">👔</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">"Who You Know" Network</h4>
                    <p class="text-xs text-slate-500 mb-3">Certain VIP clubs and high-paying jobs need connections. Network your way to the top.</p>
                    <div id="connectionsDisplay" class="text-xs text-slate-600 mb-3">No Oga connections yet. Attend parties & church!</div>
                    <button onclick="GameApp.checkConnections()" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition active:scale-95">🔐 Check My Network Access</button>
                </div>
                
                <!-- December IJGB Inflation -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-red-600/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="w-10 h-10 rounded-2xl bg-red-100 flex items-center justify-center text-red-700 text-lg mb-3">🎄</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">December IJGB Season</h4>
                    <p class="text-xs text-slate-500 mb-3" id="decemberStatus">"I Just Got Back" season. Economy inflates massively. Elite parties & expensive concerts arrive!</p>
                    <div id="decemberBadge" class="hidden px-3 py-1.5 bg-red-600 text-white text-xs font-bold rounded-full text-center mb-3">🔥 DECEMBER INFLATION ACTIVE</div>
                    <button onclick="GameApp.checkDecemberEvent()" class="w-full py-2.5 bg-red-600 hover:bg-red-500 text-white font-bold text-xs rounded-xl transition active:scale-95">🎉 Check December Events</button>
                </div>
            </div>
        </section>

        <!-- STREET ECONOMY TAB -->
        <section id="tab-economy" class="game-tab-content hidden">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-slate-900">💰 Street Economy & Market Life</h3>
                <p class="text-xs text-slate-500">Haggle at Balogun Market, manage sapa levels, run multiple hustles, and deal with housing agent fees.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Market Haggling -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm md:col-span-2">
                    <div class="w-10 h-10 rounded-2xl bg-orange-100 flex items-center justify-center text-orange-700 text-lg mb-3">🏪</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Market Haggling Mini-Game</h4>
                    <p class="text-xs text-slate-500 mb-3">Accepting the first price marks you as a "mugu" and kills your street cred. Haggle hard!</p>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="text-xs font-bold text-slate-700 block mb-1">Market</label>
                            <select id="hagglingMarket" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                                <option value="balogun">🛍️ Balogun Market</option>
                                <option value="computer_village">💻 Computer Village</option>
                                <option value="wuse_market">🥬 Wuse Market</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-700 block mb-1">Item Type</label>
                            <select id="hagglingItem" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                                <option value="phone">📱 Phone (₦85k base)</option>
                                <option value="clothes">👕 Clothes (₦15k base)</option>
                                <option value="electronics">💻 Electronics (₦45k base)</option>
                                <option value="food">🥬 Food Stuff (₦5k base)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-xs font-bold text-slate-700 block mb-1">Your Counter-Offer (% of asking price)</label>
                        <div class="flex gap-2">
                            <input type="range" id="hagglingSlider" min="10" max="100" value="60" class="flex-1" oninput="document.getElementById('hagglingPct').textContent = this.value + '%'">
                            <span id="hagglingPct" class="text-xs font-bold text-emerald-700 w-10 text-right">60%</span>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                            <span>🔥 Sharpe Bargain</span><span>😅 Fair Offer</span><span>🤦 Mugu Price</span>
                        </div>
                    </div>
                    <button onclick="GameApp.playHagglingGame()" class="w-full py-2.5 bg-orange-500 hover:bg-orange-400 text-white font-bold text-xs rounded-xl transition active:scale-95">🗣️ Start Haggling!</button>
                </div>
                
                <!-- Sapa Meter -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-violet-100 flex items-center justify-center text-violet-700 text-lg mb-3">😩</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Sapa (Financial Stress) Meter</h4>
                    <p class="text-xs text-slate-500 mb-3">Your broke-o-meter. High sapa blocks premium locations. Eat street food to survive the month.</p>
                    <div id="sapaDetailDisplay" class="space-y-2 mb-3">
                        <div class="bg-violet-50 border border-violet-200 rounded-xl p-3">
                            <div class="flex justify-between text-xs font-bold">
                                <span class="text-violet-800">Current Sapa Level:</span>
                                <span id="sapaLevelText" class="text-violet-900">Loading...</span>
                            </div>
                            <div class="w-full bg-violet-100 h-2 rounded-full overflow-hidden mt-2">
                                <div id="sapaBar" class="bg-violet-600 h-full rounded-full transition-all" style="width:0%"></div>
                            </div>
                            <p id="sapaAdvice" class="text-[10px] text-violet-700 mt-2">You're financially stable!</p>
                        </div>
                    </div>
                    <button onclick="GameApp.checkSapaStatus()" class="w-full py-2 bg-violet-600 hover:bg-violet-500 text-white font-bold text-xs rounded-xl transition active:scale-95">📊 Check Sapa Status</button>
                </div>
                
                <!-- Housing Agent Fees -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-slate-700 flex items-center justify-center text-white text-lg mb-3">🏠</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Agent Fee Reality Check</h4>
                    <p class="text-xs text-slate-500 mb-3">Renting in Abuja means paying: 2 years rent upfront + Agent Fee (10%) + Agreement Fee (5%). Brutal!</p>
                    <div id="agentFeeCalculator" class="space-y-2 text-xs">
                        <select id="rentPropertySelect" onchange="GameApp.calculateAgentFees()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs">
                            <option value="450000">Self-Contain, Lugbe (₦450k/yr)</option>
                            <option value="1200000">2-Bed Flat, Kubwa (₦1.2M/yr)</option>
                            <option value="3500000">3-Bed Apartment, Gwarinpa (₦3.5M/yr)</option>
                            <option value="15000000">4-Bed Duplex, Wuse 2 (₦15M/yr)</option>
                        </select>
                        <div id="agentFeeBreakdown" class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-1">
                            <div class="flex justify-between"><span>2 Years Rent:</span> <strong id="calcRent">₦900,000</strong></div>
                            <div class="flex justify-between text-amber-700"><span>Agent Fee (10%):</span> <strong id="calcAgent">₦90,000</strong></div>
                            <div class="flex justify-between text-rose-700"><span>Agreement Fee (5%):</span> <strong id="calcAgreement">₦45,000</strong></div>
                            <div class="flex justify-between font-bold text-slate-900 border-t border-slate-200 pt-1 mt-1"><span>Total Upfront:</span> <strong id="calcTotal">₦1,035,000</strong></div>
                        </div>
                    </div>
                </div>
                
                <!-- Face-Me-I-Face-You Compound -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-amber-100 flex items-center justify-center text-amber-700 text-lg mb-3">🏘️</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Face-Me-I-Face-You Life</h4>
                    <p class="text-xs text-slate-500 mb-3">Starter compound life. Share bathrooms with nosey neighbors. Manage gossip and water-pumping disputes.</p>
                    <div id="compoundEvents" class="space-y-2 text-xs text-slate-600">
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-2.5">👀 Mama Ngozi is spreading gist about your rent arrears...</div>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-2.5">💧 It's your turn to pump water from the borehole today.</div>
                    </div>
                </div>
                
                <!-- Mainland vs Island Analysis -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm md:col-span-2">
                    <div class="w-10 h-10 rounded-2xl bg-blue-100 flex items-center justify-center text-blue-700 text-lg mb-3">🌉</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Abuja District Divide: Maitama/Asokoro vs Kubwa/Lugbe</h4>
                    <p class="text-xs text-slate-500 mb-3">High-status districts give social prestige but cost triple. Mass districts mean long commutes but affordable hustle life.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                            <h5 class="font-bold text-xs text-amber-900 mb-2">💎 Maitama / Asokoro / Wuse 2</h5>
                            <ul class="text-[11px] text-amber-800 space-y-1">
                                <li>✅ +60 Social Status Boost</li>
                                <li>✅ VIP Clubs & Embassies Access</li>
                                <li>❌ 3x Higher Rent</li>
                                <li>❌ Higher Lifestyle Maintenance</li>
                            </ul>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                            <h5 class="font-bold text-xs text-slate-900 mb-2">🏘️ Kubwa / Lugbe / Nyanya</h5>
                            <ul class="text-[11px] text-slate-700 space-y-1">
                                <li>✅ Affordable Rent & Street Food</li>
                                <li>✅ Best Hustle Opportunities</li>
                                <li>❌ Long Commutes to CBD</li>
                                <li>❌ -20 Social Status</li>
                            </ul>
                        </div>
                    </div>
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
            <span class="text-slate-300">•</span>
            <span class="flex items-center gap-1 text-violet-700" id="pillSapa"><i class="fa-solid fa-face-tired text-[11px]"></i> <span id="pillSapaVal">OK</span></span>
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

                <div>
                    <div class="flex justify-between font-bold mb-1">
                        <span class="text-violet-700"><i class="fa-solid fa-face-tired mr-1"></i> Financial Stress (Sapa)</span>
                        <span id="valSapa" class="text-slate-600 font-mono">OK</span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        <div id="barSapa" class="bg-violet-500 h-full rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p class="text-[10px] text-slate-500 mt-0.5">High sapa blocks premium spots. Eat street food to survive.</p>
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

                        <div class="grid grid-cols-4 gap-2.5 text-center">
                            <!-- NaijaGram (Social Media) -->
                            <button onclick="PhoneApp.openApp('naijagram')" class="flex flex-col items-center group">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-105 transition">
                                    <i class="fa-solid fa-hashtag"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-700 mt-1">NaijaGram</span>
                            </button>

                            <!-- NaijaConnect (VIP Contacts) -->
                            <button onclick="PhoneApp.openApp('contacts')" class="flex flex-col items-center group">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-105 transition">
                                    <i class="fa-solid fa-address-book"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-700 mt-1">Connect</span>
                            </button>

                            <!-- OPay / Bank -->
                            <button onclick="PhoneApp.openApp('bank')" class="flex flex-col items-center group">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-105 transition">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-700 mt-1">AbujaPay</span>
                            </button>

                            <!-- WhatsApp Chat -->
                            <button onclick="PhoneApp.openApp('chat')" class="flex flex-col items-center group">
                                <div class="w-12 h-12 rounded-2xl bg-green-500 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-105 transition">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-700 mt-1">NaijaChat</span>
                            </button>

                            <!-- Bolt Rides -->
                            <button onclick="PhoneApp.openApp('rides')" class="flex flex-col items-center group">
                                <div class="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-105 transition">
                                    <i class="fa-solid fa-car"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-700 mt-1">Bolt</span>
                            </button>

                            <!-- Wardrobe / Jiji Style -->
                            <button onclick="PhoneApp.openApp('wardrobe')" class="flex flex-col items-center group">
                                <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-105 transition">
                                    <i class="fa-solid fa-shirt"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-700 mt-1">Wardrobe</span>
                            </button>

                            <!-- Games -->
                            <button onclick="PhoneApp.openApp('games')" class="flex flex-col items-center group">
                                <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-105 transition">
                                    <i class="fa-solid fa-gamepad"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-700 mt-1">Arcade</span>
                            </button>

                            <!-- Close Phone -->
                            <button onclick="PhoneApp.toggle()" class="flex flex-col items-center group">
                                <div class="w-12 h-12 rounded-2xl bg-slate-200 text-slate-600 flex items-center justify-center text-lg shadow-sm group-hover:scale-105 transition">
                                    <i class="fa-solid fa-power-off"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-700 mt-1">Lock</span>
                            </button>
                        </div>
                    </div>

                    <!-- APP: NAIJAGRAM (SOCIAL MEDIA) -->
                    <div id="phone-app-naijagram" class="phone-screen hidden space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <button onclick="PhoneApp.goHome()" class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600"><i class="fa-solid fa-arrow-left"></i></button>
                                <div>
                                    <h4 class="font-bold text-xs text-slate-900 leading-none">NaijaGram</h4>
                                    <span class="text-[9px] text-rose-500 font-extrabold uppercase">FCT Trending</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 bg-rose-50 text-rose-700 px-2 py-0.5 rounded-full text-[10px] font-bold border border-rose-200">
                                <i class="fa-solid fa-users text-[9px]"></i> <span id="socialFollowersCount">2.4k</span> Clout
                            </div>
                        </div>

                        <!-- Compose Post Box -->
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-2.5 space-y-2">
                            <div class="flex gap-2 items-center">
                                <div class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs font-bold">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <input type="text" id="socialPostInput" placeholder="Drop hot gist, flaunt wealth, or lament traffic..." class="flex-1 bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-rose-500">
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <button onclick="PhoneApp.usePostTemplate('flaunt')" class="text-[9px] px-2 py-0.5 bg-amber-100 text-amber-800 font-bold rounded-full">💸 Flaunt Wealth</button>
                                <button onclick="PhoneApp.usePostTemplate('traffic')" class="text-[9px] px-2 py-0.5 bg-yellow-100 text-yellow-800 font-bold rounded-full">🚗 Danfo Rant</button>
                                <button onclick="PhoneApp.usePostTemplate('wuse2')" class="text-[9px] px-2 py-0.5 bg-purple-100 text-purple-800 font-bold rounded-full">🥂 Wuse 2 Night</button>
                            </div>
                            <button onclick="PhoneApp.publishSocialPost()" class="w-full py-1.5 bg-gradient-to-r from-rose-500 to-purple-600 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-paper-plane text-[10px]"></i> Post Update
                            </button>
                        </div>

                        <!-- Trending Hashtags Pill Bar -->
                        <div class="flex gap-1 overflow-x-auto py-1 text-[10px] font-bold no-scrollbar">
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 whitespace-nowrap cursor-pointer hover:bg-slate-200">#AbujaBigBoys</span>
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 whitespace-nowrap cursor-pointer hover:bg-slate-200">#DanfoRushHour</span>
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 whitespace-nowrap cursor-pointer hover:bg-slate-200">#SapaTears</span>
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 whitespace-nowrap cursor-pointer hover:bg-slate-200">#OwambeSaturday</span>
                        </div>

                        <!-- Live Social Media Feed -->
                        <div id="socialFeedList" class="space-y-2.5 max-h-[300px] overflow-y-auto pr-0.5">
                            <!-- Populated dynamically via phone.js -->
                        </div>
                    </div>

                    <!-- APP: NAIJACONNECT (ACTUAL CONNECTIONS & CONTACTS) -->
                    <div id="phone-app-contacts" class="phone-screen hidden space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <button onclick="PhoneApp.goHome()" class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600"><i class="fa-solid fa-arrow-left"></i></button>
                                <div>
                                    <h4 class="font-bold text-xs text-slate-900 leading-none">NaijaConnect</h4>
                                    <span class="text-[9px] text-indigo-600 font-extrabold uppercase">VIP Network & Hookups</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200" id="networkTierPill">VIP Tier</span>
                        </div>

                        <p class="text-[10px] text-slate-500 leading-snug">Call your network for favors, send gifts to boost rapport, or get hooked up with high-paying gigs.</p>

                        <!-- Contact List Cards -->
                        <div id="contactsListContainer" class="space-y-2 max-h-[360px] overflow-y-auto pr-0.5">
                            <!-- Populated dynamically via phone.js -->
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
        <div class="bg-white border border-slate-200 rounded-3xl max-w-4xl w-full p-4 sm:p-6 md:p-8 shadow-2xl relative my-4 sm:my-8 animate-fade-up max-h-[92vh] overflow-y-auto">
            <button onclick="GameApp.closeWardrobeModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center absolute top-4 sm:top-5 right-4 sm:right-5 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <div class="flex items-center justify-between mb-3 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900">Bitmoji 3D Wardrobe Studio</h3>
                    <p class="text-xs text-slate-500">Customize your face, outfit, and kicks in real time. Drag to rotate 360°!</p>
                </div>
                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full bg-purple-50 text-purple-800 border border-purple-200">
                    Live 3D Customizer
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-5 items-start">
                <!-- 3D Canvas Column (Mobile responsive height) -->
                <div class="md:col-span-5 flex flex-col items-center w-full">
                    <div id="gameBitmojiContainer" class="w-full h-[340px] sm:h-[400px] md:h-[480px] rounded-3xl bg-slate-50 border border-slate-200 relative shadow-inner overflow-hidden flex items-center justify-center cursor-grab active:cursor-grabbing"></div>
                    <div class="w-full mt-2 space-y-1">
                        <button type="button" onclick="GameApp.turnWardrobeAvatar()" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-arrows-rotate text-xs"></i> Turn Around 180°
                        </button>
                        <p class="text-[10px] sm:text-[11px] text-slate-500 text-center font-medium">
                            <i class="fa-solid fa-hand-pointer text-slate-400 mr-1"></i> Drag left/right to spin 360°
                        </p>
                    </div>
                </div>

                <!-- Customization Controls Column -->
                <div class="md:col-span-7 flex flex-col w-full space-y-3">
                    
                    <!-- CATEGORY TABS -->
                    <div class="flex bg-slate-100 p-1 rounded-2xl text-xs font-bold text-slate-600">
                        <button type="button" onclick="GameApp.switchWardrobeTab('face')" id="gameTabBtn-face" class="flex-1 py-2 rounded-xl bg-white text-purple-950 font-bold shadow-sm transition flex items-center justify-center gap-1.5 active:scale-95">
                            <i class="fa-solid fa-user text-xs"></i> <span>1. Face</span>
                        </button>
                        <button type="button" onclick="GameApp.switchWardrobeTab('outfit')" id="gameTabBtn-outfit" class="flex-1 py-2 rounded-xl text-slate-600 hover:text-slate-900 font-bold transition flex items-center justify-center gap-1.5 active:scale-95">
                            <i class="fa-solid fa-shirt text-xs"></i> <span>2. The Fit</span>
                        </button>
                        <button type="button" onclick="GameApp.switchWardrobeTab('kicks')" id="gameTabBtn-kicks" class="flex-1 py-2 rounded-xl text-slate-600 hover:text-slate-900 font-bold transition flex items-center justify-center gap-1.5 active:scale-95">
                            <i class="fa-solid fa-shoe-prints text-xs"></i> <span>3. Kicks</span>
                        </button>
                    </div>

                    <!-- TAB 1: FACE SELECTION -->
                    <div id="gameWardrobeTab-face" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-700">Choose Face & Persona:</span>
                            <span class="text-[10px] text-slate-400">9 Archetypes</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 max-h-[290px] sm:max-h-[320px] overflow-y-auto pr-1">
                            <button type="button" onclick="GameApp.setWardrobeCharacter('tunde')" class="wardrobe-char-card p-2 rounded-2xl border-2 border-purple-600 bg-purple-50 text-center transition transform active:scale-95 group">
                                <img src="assets/img/characters/tunde/Man_standing_in_hoodie_20261005064533.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Tunde">
                                <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Tunde</span>
                                <span class="block text-[9px] text-slate-500 truncate">Tech Bro</span>
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeCharacter('emeka')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                <img src="assets/img/characters/emeka/Man_wearing_green_hoodie_standing_20261005064521.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Emeka">
                                <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Emeka</span>
                                <span class="block text-[9px] text-slate-500 truncate">Dealmaker</span>
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeCharacter('farouk')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                <img src="assets/img/characters/farouk/Man_wearing_green_streetwear_hoodie_20261005064505.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Farouk">
                                <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Farouk</span>
                                <span class="block text-[9px] text-slate-500 truncate">Aristocrat</span>
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeCharacter('chidi')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                <img src="assets/img/characters/chidi/Man_standing_in_hoodie_20261005064449.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Chidi">
                                <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Chidi</span>
                                <span class="block text-[9px] text-slate-500 truncate">Creative</span>
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeCharacter('zainab')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                <img src="assets/img/characters/zainab/Young_woman_standing_wearing_hoodie_20261005064439.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Zainab">
                                <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Zainab</span>
                                <span class="block text-[9px] text-slate-500 truncate">FinTech</span>
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeCharacter('blessing')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                <img src="assets/img/characters/blessing/Young_woman_standing_with_sneakers_20261005064429.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Blessing">
                                <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Blessing</span>
                                <span class="block text-[9px] text-slate-500 truncate">Curator</span>
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeCharacter('ibrahim')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                <img src="assets/img/characters/ibrahim/Man_wearing_streetwear_hoodie_20261005064416.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Ibrahim">
                                <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Ibrahim</span>
                                <span class="block text-[9px] text-slate-500 truncate">Oil & Gas</span>
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeCharacter('segun')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                <img src="assets/img/characters/segun/Man_wearing_green_hoodie_standing_20261005064405.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Segun">
                                <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Segun</span>
                                <span class="block text-[9px] text-slate-500 truncate">Hustler</span>
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeCharacter('ngozi')" class="wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                <img src="assets/img/characters/ngozi/Woman_wearing_hoodie_and_sunglasses_20261005064346.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Ngozi">
                                <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Ngozi</span>
                                <span class="block text-[9px] text-slate-500 truncate">Attorney</span>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: THE FIT (OUTFITS) -->
                    <div id="gameWardrobeTab-outfit" class="space-y-2 hidden">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-700">Choose Outfit Variation:</span>
                            <span class="text-[10px] text-purple-700 font-bold">12 Variations</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 max-h-[290px] sm:max-h-[320px] overflow-y-auto pr-1 text-xs">
                            <button type="button" onclick="GameApp.setWardrobeOutfit('hoodie')" class="wardrobe-outfit-btn p-2.5 rounded-xl border border-purple-600 bg-purple-50 text-purple-950 font-bold transition text-left active:scale-95">
                                🧥 Tech Bro Hoodie
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('black_hoodie')" class="wardrobe-outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                🖤 Nightclub Black Streetwear
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('tshirt')" class="wardrobe-outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                👕 Casual White Tee & Denim
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('agbada')" class="wardrobe-outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                🪡 Royal Agbada & Fila Cap
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('kaftan')" class="wardrobe-outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                👑 Senator Navy Kaftan
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('suit')" class="wardrobe-outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                💼 Executive Navy Suit
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('blazer')" class="wardrobe-outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                👓 Techie Blazer & Chinos
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('polo')" class="wardrobe-outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                🎾 Country Club Polo & Khakis
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('joggers')" class="wardrobe-outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                🏃 Fleece Joggers & Slides
                            </button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('sunglasses')" class="wardrobe-outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">
                                🕶️ VIP Shades & Streetwear
                            </button>
                        </div>
                    </div>

                    <!-- TAB 3: FOOTWEAR KICKS -->
                    <div id="gameWardrobeTab-kicks" class="space-y-2 hidden">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-700">Choose Footwear Kicks:</span>
                            <span class="text-[10px] text-slate-400">Kicks & Loafers</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button type="button" onclick="GameApp.setWardrobeOutfit('hoodie')" class="wardrobe-shoe-btn p-2.5 rounded-xl border border-purple-600 bg-purple-50 font-bold text-purple-950 transition text-left active:scale-95">👟 Crisp White AF1s</button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('black_hoodie')" class="wardrobe-shoe-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">🏀 High-Top Jordans</button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('suit')" class="wardrobe-shoe-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">👞 Handcrafted Loafers</button>
                            <button type="button" onclick="GameApp.setWardrobeOutfit('joggers')" class="wardrobe-shoe-btn p-2.5 rounded-xl border border-slate-200 bg-white font-bold hover:border-purple-500 transition text-left active:scale-95">🩴 Designer Casual Slides</button>
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

    <!-- ========================================================
         7. LASTMA CHECKPOINT NEGOTIATION MODAL
         ======================================================== -->
    <div id="lastmaModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative animate-fade-up">
            <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-xl mb-4">
                🚦
            </div>
            <h3 id="lastmaTitle" class="text-base font-bold text-slate-900 mb-1">LASTMA Traffic Checkpoint!</h3>
            <p id="lastmaDesc" class="text-xs text-slate-500 mb-4">You've been flagged for a minor traffic violation. How do you handle this?</p>
            <div id="lastmaViolation" class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs text-rose-800 font-medium mb-4"></div>
            <div class="space-y-2">
                <button onclick="GameApp.resolveLastma('bribe')" class="w-full p-3.5 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-900 rounded-2xl text-xs font-bold text-left transition active:scale-95">
                    💵 <strong>"Settle" the Officer (₦3,000-₦8,000)</strong> — Quick resolution, no questions asked
                </button>
                <button onclick="GameApp.resolveLastma('argue')" class="w-full p-3.5 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-900 rounded-2xl text-xs font-bold text-left transition active:scale-95">
                    😤 <strong>Argue Your Case</strong> — 50/50: free pass OR ₦5,000 court fine
                </button>
                <button onclick="GameApp.resolveLastma('receipt')" class="w-full p-3.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-900 rounded-2xl text-xs font-bold text-left transition active:scale-95">
                    📄 <strong>Show Road Worthiness Receipt</strong> — 80% chance they let you go free
                </button>
            </div>
            <button onclick="document.getElementById('lastmaModal').classList.add('hidden'); document.getElementById('lastmaModal').classList.remove('flex')" class="w-full mt-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-2xl transition">Drive Away (Escape)</button>
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
