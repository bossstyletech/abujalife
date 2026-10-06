<?php
require_once __DIR__ . '/config.php';

$userId = getAuthUserId();
$char = $userId ? getUserCharacter($userId) : null;

if (!$userId || !$char) {
    // If server session/cookie was dropped, check localStorage before kicking to index.php
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Restoring Abuja Citizen Session...</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-950 text-white flex items-center justify-center h-screen m-0 select-none">
        <div class="text-center p-6 max-w-sm">
            <div class="w-14 h-14 rounded-3xl bg-emerald-600/20 text-emerald-400 mx-auto flex items-center justify-center text-2xl mb-4 border border-emerald-500/30 animate-pulse">
                👑
            </div>
            <h2 class="text-base font-extrabold text-white mb-1">Abuja Life</h2>
            <p class="text-xs text-slate-400 mb-4" id="restoreStatus">Restoring your citizen session...</p>
            <div class="w-32 h-1.5 bg-slate-800 rounded-full mx-auto overflow-hidden">
                <div class="w-full h-full bg-emerald-500 rounded-full animate-pulse"></div>
            </div>
        </div>
        <script>
        const token = localStorage.getItem('abuja_remember_token') || '';
        const uid = localStorage.getItem('abuja_user_id') || '';
        if (token || uid) {
            fetch('api/auth.php?action=restore_session', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'token=' + encodeURIComponent(token) + '&user_id=' + encodeURIComponent(uid)
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    if (d.token) {
                        localStorage.setItem('abuja_remember_token', d.token);
                        document.cookie = "abuja_remember_token=" + d.token + "; path=/; max-age=315360000; SameSite=Lax";
                    }
                    if (d.user && d.user.id) {
                        localStorage.setItem('abuja_user_id', d.user.id);
                        document.cookie = "abuja_user_id=" + d.user.id + "; path=/; max-age=315360000; SameSite=Lax";
                    }
                    window.location.reload();
                } else {
                    localStorage.removeItem('abuja_remember_token');
                    window.location.href = 'index.php';
                }
            }).catch(() => { window.location.href = 'index.php'; });
        } else {
            window.location.href = 'index.php';
        }
        </script>
    </body>
    </html>
    <?php
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

    <!-- Header Navigation (Matching Image 5 & 6) -->
    <header id="mainGameHeader" class="bg-white/95 border-b border-slate-200/90 sticky top-0 z-30 backdrop-blur shadow-sm">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 h-16 flex items-center justify-between gap-2">
            <!-- Left: Logo & City District -->
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                    <i class="fa-solid fa-city"></i>
                </div>
                <div>
                    <h1 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight flex items-center gap-1.5">
                        Abuja Life
                        <span class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-extrabold">FCT</span>
                    </h1>
                    <span id="hudDistrictTop" class="text-xs text-slate-500 font-bold"><?= htmlspecialchars($char['district']) ?></span>
                </div>
            </div>

            <!-- Center: Time, Mood & Live Status Pill (Image 5 & 6) -->
            <div class="hidden md:flex items-center gap-2 bg-slate-100 border border-slate-200 px-3.5 py-1.5 rounded-full text-xs font-bold text-slate-700 shadow-inner">
                <span id="headerClockPill" class="flex items-center gap-1">☀️ Tue 6 • 7:23 AM</span>
                <span class="text-slate-300">•</span>
                <span id="headerMoodPill" class="text-emerald-700 font-extrabold">😄 Happy</span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-500 flex items-center gap-1">👥 13.8m • <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> 23k online</span>
            </div>

            <!-- Right: Sound Toggle, Cash Counter Pill, Actions -->
            <div class="flex items-center gap-2">
                <!-- Sound Toggle -->
                <button onclick="GameApp.toggleSfx()" id="sfxToggleBtn" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition active:scale-95" title="Mute/Unmute Audio">
                    <i class="fa-solid fa-volume-high text-xs" id="sfxIcon"></i>
                </button>
                <!-- Cash Pill Counter with [+] -->
                <div onclick="GameApp.switchTab('bank')" class="bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded-full flex items-center gap-2 cursor-pointer transition active:scale-95 shadow-sm">
                    <span id="topCashDisplay" class="font-mono font-extrabold text-xs sm:text-sm text-emerald-800">₦<?= number_format($char['cash'], 2) ?></span>
                    <span class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] font-extrabold flex items-center justify-center shadow-xs">+</span>
                </div>
                <!-- Profile Avatar Icon -->
                <button onclick="GameApp.openCitizenFinder()" title="Abuja Citizen Directory & Network" class="w-9 h-9 rounded-xl bg-slate-900 hover:bg-slate-800 text-white flex items-center justify-center transition active:scale-95 shadow-sm">
                    <i class="fa-solid fa-user text-xs"></i>
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
                <span id="weatherDesc" class="text-blue-200">Abuja green taxi fares have doubled. Flooded roads slowing traffic. Buy Pure Water from hawkers.</span>
            </div>
            <span id="weatherFloodBadge" class="px-2 py-1 rounded-full bg-rose-500 text-white text-[10px] font-bold">FLOOD LV.2</span>
        </div>

        <!-- ========================================================
             MAIN VIEW 1: HOME (INTERACTIVE 3D ISOMETRIC HOUSE - Image 5)
             ======================================================== -->
        <div id="mainView-home" class="main-view-section space-y-6">
            
            <section class="bg-white border border-slate-200/90 rounded-3xl p-4 sm:p-6 shadow-sm relative overflow-hidden">
                <!-- Top Status & Switcher -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <h2 class="text-base sm:text-lg font-bold text-slate-900">Abuja Residence & Interior Studio</h2>
                        </div>
                        <p class="text-xs text-slate-500">Tap furniture to interact: sleep on bed, eat from cooler, cook on stove, or code on desk!</p>
                    </div>

                    <!-- 3D House Viewport Container -->
                    <div id="orbit3dContainer" class="w-full h-[440px] sm:h-[540px] rounded-3xl bg-slate-50 border border-slate-200/80 relative cursor-grab active:cursor-grabbing overflow-hidden shadow-inner mb-4">
                        <div id="world3d-container" class="w-full h-full"></div>
                        
                        <!-- Left Floating Quest / Activity Pills -->
                        <div class="absolute top-4 left-4 z-20 flex flex-col gap-2 pointer-events-auto">
                            <div onclick="GameApp.claimDailyGem()" class="bg-white/95 hover:bg-white backdrop-blur-md border border-slate-200/90 rounded-full px-3.5 py-2 shadow-lg flex items-center gap-2 text-xs font-bold text-slate-800 cursor-pointer transition transform active:scale-95">
                                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs">💎</span>
                                <div>
                                    <span class="block text-slate-900">Daily gem hunt</span>
                                    <span class="text-[10px] text-slate-500 font-normal">Tap to collect ₦3,000</span>
                                </div>
                            </div>

                            <button onclick="GameApp.toggleCleanScreen()" class="w-fit bg-white/90 hover:bg-white backdrop-blur-md border border-slate-200/80 rounded-full px-3 py-1 shadow text-[10px] font-bold text-slate-600 transition active:scale-95">
                                <i class="fa-solid fa-chevron-up text-[9px] mr-1"></i> Clean screen
                            </button>
                        </div>

                        <!-- Bottom Center Venue Switcher Pill (Matching reference screenshot) -->
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5 pointer-events-auto">
                            <div class="bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-full px-3 py-1.5 shadow-lg flex items-center gap-2 text-xs font-bold text-slate-800">
                                <span id="world3dVenuePillText" class="flex items-center gap-1">🏠 Abuja Residence</span>
                                <select onchange="if(window.World3D) World3D.switchVenue(this.value)" class="bg-transparent text-[11px] font-extrabold text-slate-700 outline-none cursor-pointer border-l border-slate-200 pl-1.5">
                                    <option value="home">🏠 My Room</option>
                                    <option value="gym">🏋️ i-Fitness Gym</option>
                                    <option value="restaurant">🍲 Jabi Lake Grill</option>
                                    <option value="banex">📱 Banex Tech Hub</option>
                                    <option value="market">🛍️ Wuse Market</option>
                                    <option value="mosque">🕌 National Mosque</option>
                                    <option value="church">⛪ National Church</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Home Interactions UI Grid -->
                    <div class="mb-4">
                        <h4 class="font-bold text-sm text-slate-900 mb-2">Home Actions</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <button onclick="GameApp.interactFurniture('bed')" class="py-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-800 font-bold text-xs rounded-xl transition active:scale-95 flex items-center justify-center gap-1.5">
                                🛏️ Sleep on Bed
                            </button>
                            <button onclick="GameApp.interactFurniture('fridge')" class="py-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-800 font-bold text-xs rounded-xl transition active:scale-95 flex items-center justify-center gap-1.5">
                                🧊 Cooler Chops
                            </button>
                            <button onclick="GameApp.interactFurniture('smart_tv')" class="py-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-800 font-bold text-xs rounded-xl transition active:scale-95 flex items-center justify-center gap-1.5">
                                🛋️ Relax Sofa
                            </button>
                            <button onclick="GameApp.interactFurniture('mac_workstation')" class="py-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-800 font-bold text-xs rounded-xl transition active:scale-95 flex items-center justify-center gap-1.5">
                                💻 Freelance & Trade
                            </button>
                            <button onclick="GameApp.interactFurniture('gas_cooker')" class="py-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-800 font-bold text-xs rounded-xl transition active:scale-95 flex items-center justify-center gap-1.5">
                                🍳 Cook Jollof
                            </button>
                            <button onclick="GameApp.openCommuteModal()" class="py-2.5 bg-emerald-600 hover:bg-emerald-500 border border-emerald-600 text-white font-bold text-xs rounded-xl transition active:scale-95 flex items-center justify-center gap-1.5 md:col-span-3">
                                🚪 Exit & Commute
                            </button>
                        </div>
                    </div>

                <!-- Footer Status -->
                <div class="mt-3 flex flex-wrap items-center justify-between text-xs text-slate-600 pt-2 border-t border-slate-100">
                    <div class="flex items-center gap-3">
                        <span><i class="fa-solid fa-house-chimney text-slate-400 mr-1"></i> Home: <strong id="world3dCurrentHome" class="text-slate-900"><?= htmlspecialchars($char['property_name'] ?: 'Renting Self-Con') ?></strong></span>
                        <span><i class="fa-solid fa-bolt text-slate-400 mr-1"></i> Power: <strong class="text-emerald-700 font-bold">AEDC Grid (Generator Ready)</strong></span>
                    </div>
                    <div class="text-[11px] text-slate-500">
                        <button onclick="GameApp.switchMainView('buy')" class="text-emerald-700 font-bold hover:underline">
                            <i class="fa-solid fa-cart-shopping mr-1"></i> Furnish & Add Items
                        </button>
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

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Action 1: Morning Breakfast & Shower -->
                <button onclick="GameApp.doMorningRoutine()" class="p-4 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 rounded-2xl text-left transition active:scale-95 group">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition">
                        <i class="fa-solid fa-mug-saucer"></i>
                    </div>
                    <h4 class="font-bold text-xs text-slate-900 mb-0.5">Morning Routine</h4>
                    <p class="text-[11px] text-slate-500 leading-snug">Hot shower & breakfast. (+15 Energy, +5 Happiness)</p>
                </button>

                <!-- Action 2: Go to Work / Commute -->
                <button onclick="GameApp.openCommuteModal()" class="p-4 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 rounded-2xl text-left transition active:scale-95 group">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <h4 class="font-bold text-xs text-emerald-950 mb-0.5">Go to Work</h4>
                    <p class="text-[11px] text-emerald-800 leading-snug">Commute through estate gates. Start 8-min workday shift.</p>
                </button>

                <!-- Action 3: Enter Residence / House -->
                <button onclick="GameApp.openResidenceModal()" class="p-4 bg-teal-50 hover:bg-teal-100/80 border border-teal-200 rounded-2xl text-left transition active:scale-95 group">
                    <div class="w-9 h-9 rounded-xl bg-teal-600 text-white flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition">
                        <i class="fa-solid fa-house-chimney-user"></i>
                    </div>
                    <h4 class="font-bold text-xs text-teal-950 mb-0.5">Enter Residence</h4>
                    <p class="text-[11px] text-teal-800 leading-snug">Rest on bed, manage inverter/gen, resolve borehole issues.</p>
                </button>

                <!-- Action 4: Run Street Hustle -->
                <button onclick="GameApp.switchTab('hustles')" class="p-4 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 rounded-2xl text-left transition active:scale-95 group">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h4 class="font-bold text-xs text-slate-900 mb-0.5">Side Hustles</h4>
                    <p class="text-[11px] text-slate-500 leading-snug">POS business, Banex gadget flip, or P2P arbitrage.</p>
                </button>

                <!-- Action 5: Street Fights & Grudges -->
                <button onclick="GameApp.openStreetFight()" class="p-4 bg-rose-50 hover:bg-rose-100/80 border border-rose-200 rounded-2xl text-left transition active:scale-95 group">
                    <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center text-sm mb-2 group-hover:scale-105 transition">
                        <i class="fa-solid fa-hand-fist"></i>
                    </div>
                    <h4 class="font-bold text-xs text-rose-950 mb-0.5">Street Clash</h4>
                    <p class="text-[11px] text-rose-800 leading-snug">Agbero face-off, street defense & box for cash/cred.</p>
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
                    <i class="fa-solid fa-taxi text-xs text-emerald-600"></i> Abuja Expressways & Green Cabs
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
                <h3 class="text-lg font-bold text-slate-900">🚕 Abuja Street Transport & Commuting</h3>
                <p class="text-xs text-slate-500">Navigate Green Cab taxis, VIO & FRSC checkpoints, Keke napeps, and Nyanya expressway traffic like a true FCT citizen.</p>
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
                <!-- Green Cab Rush -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-600 flex items-center justify-center text-white text-lg mb-3">🚕</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">Green Cab Rush Hour</h4>
                    <p class="text-xs text-slate-500 mb-3">Scramble for an authentic Green Cab at Berger Roundabout. Fares double during heavy rainstorms!</p>
                    <div id="danfoFareDisplay" class="text-xs font-bold text-emerald-800 mb-3">Base Fare: ₦500 | Rain: ₦1,000</div>
                    <button onclick="GameApp.playDanfoRush()" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl transition active:scale-95">
                        🏃 Rush to Board Taxi!
                    </button>
                </div>
                
                <!-- VIO / FRSC Checkpoint -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="w-10 h-10 rounded-2xl bg-rose-100 flex items-center justify-center text-rose-700 text-lg mb-3">🚦</div>
                    <h4 class="font-bold text-sm text-slate-900 mb-1">VIO / FRSC Checkpoint</h4>
                    <p class="text-xs text-slate-500 mb-3">Federal traffic officers flag down motorists along Shehu Shagari Way. Inspect papers or negotiate!</p>
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
                <p class="text-xs text-slate-500">Haggle at Wuse Market & Banex Plaza, manage sapa levels, run multiple hustles, and deal with housing agent fees.</p>
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
                                <option value="wuse_market">🥬 Wuse Market (Abuja)</option>
                                <option value="banex_plaza">📱 Banex Plaza (Wuse 2)</option>
                                <option value="garki_market">🛍️ Garki Model Market</option>
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
            </div>
        </div>

        <!-- ========================================================
             MAIN VIEW 2: BUY (FURNITURE & APPLIANCE STORE)
             ======================================================== -->
        <div id="mainView-buy" class="main-view-section hidden space-y-6 animate-fade-up">
            <section class="bg-white border border-slate-200/90 rounded-3xl p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <h2 class="text-base sm:text-lg font-bold text-slate-900">Abuja Home Furnishing & Appliances Store</h2>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Purchase luxury furniture and appliances. Delivered instantly to your Abuja residence!</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500">Wallet:</span>
                        <span class="font-mono font-extrabold text-sm text-emerald-700" id="storeWalletBalance">₦<?= number_format($char['cash'], 2) ?></span>
                    </div>
                </div>

                <!-- Furniture Catalog Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-5">
                    <!-- 1. Orthopedic Bed -->
                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-4 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-bed"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900">Orthopedic Luxury Bed</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">High-density memory foam. Restores energy to 100% and cures fatigue instantly.</p>
                            </div>
                            <div class="text-xs font-mono font-extrabold text-emerald-700">₦120,000.00</div>
                        </div>
                        <div class="pt-4 mt-2 border-t border-slate-200/80">
                            <button onclick="GameApp.buyFurniture('bed_orthopedic')" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-cart-plus text-xs"></i> <span>Buy & Add to House</span>
                            </button>
                        </div>
                    </div>

                    <!-- 2. Refrigerator -->
                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-4 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-800 flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-snowflake"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900">Thermocool Refrigerator</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Double-door frost-free fridge. Stores chilled food and fresh fruit.</p>
                            </div>
                            <div class="text-xs font-mono font-extrabold text-emerald-700">₦180,000.00</div>
                        </div>
                        <div class="pt-4 mt-2 border-t border-slate-200/80">
                            <button onclick="GameApp.buyFurniture('fridge_haier')" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-cart-plus text-xs"></i> <span>Buy & Add to House</span>
                            </button>
                        </div>
                    </div>

                    <!-- 3. Gas Cooker -->
                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-4 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-800 flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-fire-burner"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900">4-Burner Gas Cooker & Oven</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Cook hot smoky Nigerian Party Jollof and fried plantain right in your kitchen.</p>
                            </div>
                            <div class="text-xs font-mono font-extrabold text-emerald-700">₦95,000.00</div>
                        </div>
                        <div class="pt-4 mt-2 border-t border-slate-200/80">
                            <button onclick="GameApp.buyFurniture('gas_cooker')" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-cart-plus text-xs"></i> <span>Buy & Add to House</span>
                            </button>
                        </div>
                    </div>

                    <!-- 4. Solar Inverter System -->
                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-4 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-solar-panel"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900">3.5KVA Solar Inverter & Battery</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">24/7 silent pure sine-wave electricity. Never worry about AEDC blackouts.</p>
                            </div>
                            <div class="text-xs font-mono font-extrabold text-emerald-700">₦450,000.00</div>
                        </div>
                        <div class="pt-4 mt-2 border-t border-slate-200/80">
                            <button onclick="GameApp.buyFurniture('solar_inverter')" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-cart-plus text-xs"></i> <span>Buy & Add to House</span>
                            </button>
                        </div>
                    </div>

                    <!-- 5. Mikano Generator -->
                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-4 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-800 flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-bolt"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900">3.5KVA Mikano Petrol Gen</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">The Nigerian powerhouse. Powers all home appliances and heavy ACs.</p>
                            </div>
                            <div class="text-xs font-mono font-extrabold text-emerald-700">₦150,000.00</div>
                        </div>
                        <div class="pt-4 mt-2 border-t border-slate-200/80">
                            <button onclick="GameApp.buyFurniture('mikano_gen')" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-cart-plus text-xs"></i> <span>Buy & Add to House</span>
                            </button>
                        </div>
                    </div>

                    <!-- 6. Mac Studio Workstation -->
                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-4 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-800 flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-laptop-code"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900">M3 Max Studio Workstation Desk</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Ergonomic desk + dual 4K monitors. Run freelance gigs and P2P crypto arbitrage.</p>
                            </div>
                            <div class="text-xs font-mono font-extrabold text-emerald-700">₦520,000.00</div>
                        </div>
                        <div class="pt-4 mt-2 border-t border-slate-200/80">
                            <button onclick="GameApp.buyFurniture('mac_workstation')" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-cart-plus text-xs"></i> <span>Buy & Add to House</span>
                            </button>
                        </div>
                    </div>

                    <!-- 7. 65" Smart TV -->
                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-4 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-tv"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900">65" OLED 4K Smart TV</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Surround sound. Watch live Premier League matches and movies (+40 Fun).</p>
                            </div>
                            <div class="text-xs font-mono font-extrabold text-emerald-700">₦280,000.00</div>
                        </div>
                        <div class="pt-4 mt-2 border-t border-slate-200/80">
                            <button onclick="GameApp.buyFurniture('smart_tv')" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-cart-plus text-xs"></i> <span>Buy & Add to House</span>
                            </button>
                        </div>
                    </div>

                    <!-- 8. Split AC -->
                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-4 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-cyan-100 text-cyan-800 flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-wind"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900">2HP Inverter Split AC</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Turbo cooling down to 18°C. Beats the intense Abuja afternoon heatwave.</p>
                            </div>
                            <div class="text-xs font-mono font-extrabold text-emerald-700">₦210,000.00</div>
                        </div>
                        <div class="pt-4 mt-2 border-t border-slate-200/80">
                            <button onclick="GameApp.buyFurniture('split_ac')" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-cart-plus text-xs"></i> <span>Buy & Add to House</span>
                            </button>
                        </div>
                    </div>

                    <!-- 9. Italian Leather Sofa -->
                    <div class="bg-slate-50 border border-slate-200 rounded-3xl p-4 flex flex-col justify-between hover:shadow-md transition">
                        <div class="space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-800 flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-couch"></i>
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-900">Italian Leather Sectional Sofa</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Plush executive seating for welcoming guests and relaxing in style.</p>
                            </div>
                            <div class="text-xs font-mono font-extrabold text-emerald-700">₦260,000.00</div>
                        </div>
                        <div class="pt-4 mt-2 border-t border-slate-200/80">
                            <button onclick="GameApp.buyFurniture('leather_sofa')" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-cart-plus text-xs"></i> <span>Buy & Add to House</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ========================================================
             MAIN VIEW 3: MAP (HIGH-RESOLUTION 3D ABUJA CITY MAP - Image 6)
             ======================================================== -->
        <div id="mainView-map" class="main-view-section hidden space-y-4 animate-fade-up">
            
            <!-- Top Filter & City Pills (Image 6) -->
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="bg-white border border-slate-200 px-3 py-1 rounded-full text-xs font-bold text-slate-700 shadow-sm flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span> Serious go-slow
                    </span>
                    <span class="bg-white border border-slate-200 px-3 py-1 rounded-full text-xs font-bold text-slate-700 shadow-sm flex items-center gap-1.5">
                        📊 Billboards
                    </span>
                    <span class="bg-white border border-slate-200 px-3 py-1 rounded-full text-xs font-bold text-slate-700 shadow-sm flex items-center gap-1.5">
                        👨‍👩‍👧‍👦 Neighbours
                    </span>
                    <span class="bg-white border border-slate-200 px-3 py-1 rounded-full text-xs font-bold text-slate-700 shadow-sm flex items-center gap-1.5">
                        🏛️ Gov
                    </span>
                </div>
                <div class="bg-white border border-slate-200 rounded-full p-0.5 text-xs font-bold shadow-sm flex items-center gap-1">
                    <span class="px-2 py-0.5 text-slate-400">Lagos</span>
                    <span class="px-2 py-0.5 text-slate-400">Port Harcourt</span>
                    <span class="px-2.5 py-0.5 rounded-full bg-blue-600 text-white font-extrabold shadow-sm">🏛️ Abuja</span>
                </div>
            </div>

            <!-- 3D City Map Viewport -->
            <section class="bg-slate-900 border border-slate-200/90 rounded-3xl p-0 shadow-lg relative overflow-hidden h-[540px] sm:h-[620px]" id="inGameMapViewport">
                <div id="map3d-container" class="w-full h-full cursor-grab active:cursor-grabbing"></div>
            </section>
        </div>

    </main>

    <!-- ========================================================
         4. BOTTOM LEFT VITALS WIDGET (Image 5 & 6)
         (Avatar portrait + 6 horizontal vital bars)
         ======================================================== -->
    <div id="vitalsFloatingWidget" class="fixed bottom-4 left-4 z-40 bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-3xl p-2 sm:p-2.5 shadow-xl flex items-center gap-3">
        <!-- Avatar Portrait Circle -->
        <div onclick="toggleVitalsDrawer()" class="w-11 h-11 rounded-full overflow-hidden border-2 border-emerald-500 shadow-md bg-amber-500 cursor-pointer active:scale-95 transition">
            <img src="assets/img/characters/tunde/Man_standing_in_hoodie_20261005064533.png" class="w-full h-full object-cover">
        </div>

        <!-- 6 Horizontal Status Bars Grid -->
        <div class="grid grid-cols-3 gap-x-2 gap-y-1 text-[10px] font-bold">
            <!-- Hunger 🍲 -->
            <div class="flex items-center gap-1 w-16">
                <span class="text-[11px]">🍲</span>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div id="barHungerMini" class="bg-emerald-500 h-full rounded-full" style="width: 85%;"></div>
                </div>
            </div>
            <!-- Energy ⚡ -->
            <div class="flex items-center gap-1 w-16">
                <span class="text-[11px]">⚡</span>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div id="barEnergyMini" class="bg-amber-500 h-full rounded-full" style="width: <?= $char['energy'] ?>%;"></div>
                </div>
            </div>
            <!-- Fun 🎉 -->
            <div class="flex items-center gap-1 w-16">
                <span class="text-[11px]">🎉</span>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div id="barFunMini" class="bg-pink-500 h-full rounded-full" style="width: <?= $char['happiness'] ?>%;"></div>
                </div>
            </div>
            <!-- Social 💬 -->
            <div class="flex items-center gap-1 w-16">
                <span class="text-[11px]">💬</span>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div id="barSocialMini" class="bg-blue-500 h-full rounded-full" style="width: <?= min(100, $char['street_cred']) ?>%;"></div>
                </div>
            </div>
            <!-- Hygiene 🧼 -->
            <div class="flex items-center gap-1 w-16">
                <span class="text-[11px]">🧼</span>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div id="barHygieneMini" class="bg-teal-500 h-full rounded-full" style="width: 90%;"></div>
                </div>
            </div>
            <!-- Bladder 🚽 -->
            <div class="flex items-center gap-1 w-16">
                <span class="text-[11px]">🚽</span>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div id="barBladderMini" class="bg-sky-500 h-full rounded-full" style="width: 30%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         5. BOTTOM CENTER FLOATING 4-PILL NAVIGATION (Image 5 & 6)
         (Home | Buy | Map | Phone)
         ======================================================== -->
    <nav id="bottomNavPill" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-40 bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-full shadow-2xl p-1.5 flex items-center gap-1">
        <button id="mainnav-home" onclick="GameApp.switchMainView('home')" class="main-nav-btn px-4 py-2 rounded-full font-extrabold text-xs bg-slate-900 text-white shadow-sm transition active:scale-95 flex items-center gap-1.5">
            <i class="fa-solid fa-house text-xs"></i> <span>Home</span>
        </button>
        <button id="mainnav-buy" onclick="GameApp.switchMainView('buy')" class="main-nav-btn px-4 py-2 rounded-full font-bold text-xs text-slate-600 hover:text-slate-900 transition active:scale-95 flex items-center gap-1.5">
            <i class="fa-solid fa-couch text-xs"></i> <span>Buy</span>
        </button>
        <button id="mainnav-map" onclick="GameApp.switchMainView('map')" class="main-nav-btn px-4 py-2 rounded-full font-bold text-xs text-slate-600 hover:text-slate-900 transition active:scale-95 flex items-center gap-1.5">
            <i class="fa-solid fa-map text-xs"></i> <span>Map</span>
        </button>
        <button id="mainnav-phone" onclick="GameApp.switchMainView('phone')" class="main-nav-btn px-4 py-2 rounded-full font-bold text-xs text-slate-600 hover:text-slate-900 transition active:scale-95 flex items-center gap-1.5">
            <i class="fa-solid fa-mobile-screen text-xs"></i> <span>Phone</span>
        </button>
        <button id="mainnav-citizens" onclick="GameApp.openCitizenFinder()" class="main-nav-btn px-4 py-2 rounded-full font-bold text-xs text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition active:scale-95 flex items-center gap-1.5">
            <i class="fa-solid fa-users text-xs"></i> <span>Citizens</span>
        </button>
    </nav>

    <!-- Bottom Right Quick Keyboard Action Icon (Image 5 & 6) -->
    <button onclick="GameApp.toggleCleanScreen()" class="fixed bottom-4 right-4 z-40 w-11 h-11 rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200 text-slate-700 flex items-center justify-center shadow-lg active:scale-95 transition" title="Toggle Clean Screen">
        <i class="fa-solid fa-keyboard text-sm"></i>
    </button>
        <div class="relative">
            <i class="fa-solid fa-mobile-screen-button text-sm text-emerald-400 group-hover:scale-110 transition"></i>
            <span class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
            <span class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-rose-500"></span>
        </div>
        <span class="hidden sm:inline font-bold">AbujaPhone</span>
    </button>

    <!-- ========================================================
         5. INTERACTIVE IN-GAME SMARTPHONE MODAL
         (AbujaPhone Pro)
         ======================================================== -->
    <div id="phoneWidgetModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm hidden items-center justify-center p-3 sm:p-4 z-50">
        <!-- Phone Device Frame -->
        <div class="bg-slate-900 border-4 border-slate-800 rounded-[44px] max-w-[360px] w-full max-h-[92vh] h-[640px] shadow-2xl relative p-3 flex flex-col overflow-hidden animate-fade-up">
            
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

                            <!-- Chowdeck Food Delivery -->
                            <button onclick="PhoneApp.openApp('chowdeck')" class="flex flex-col items-center group">
                                <div class="w-12 h-12 rounded-2xl bg-orange-500 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-105 transition">
                                    <i class="fa-solid fa-burger"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-700 mt-1">Chowdeck</span>
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
                        </div>

                        <!-- System Widget / Quick Status Bar -->
                        <div class="mt-4 bg-slate-50 border border-slate-200/80 rounded-2xl p-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-slate-800 text-white flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-tower-broadcast"></i>
                                </div>
                                <div>
                                    <span class="text-[9px] uppercase tracking-wider text-slate-400 font-bold block">Abuja Network</span>
                                    <span class="text-xs font-bold text-slate-800">MTN 5G • Ultra</span>
                                </div>
                            </div>
                            <button onclick="PhoneApp.toggle()" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 rounded-lg text-[10px] font-bold text-slate-700 transition">
                                <i class="fa-solid fa-lock mr-1"></i> Lock
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
                                <button onclick="PhoneApp.usePostTemplate('traffic')" class="text-[9px] px-2 py-0.5 bg-yellow-100 text-yellow-800 font-bold rounded-full">🚕 Green Cab Rant</button>
                                <button onclick="PhoneApp.usePostTemplate('wuse2')" class="text-[9px] px-2 py-0.5 bg-purple-100 text-purple-800 font-bold rounded-full">🥂 Wuse 2 Night</button>
                            </div>
                            <button onclick="PhoneApp.publishSocialPost()" class="w-full py-1.5 bg-gradient-to-r from-rose-500 to-purple-600 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-paper-plane text-[10px]"></i> Post Update
                            </button>
                        </div>

                        <!-- Trending Hashtags Pill Bar -->
                        <div class="flex gap-1 overflow-x-auto py-1 text-[10px] font-bold no-scrollbar">
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 whitespace-nowrap cursor-pointer hover:bg-slate-200">#AbujaBigBoys</span>
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 whitespace-nowrap cursor-pointer hover:bg-slate-200">#BergerRoundabout</span>
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 whitespace-nowrap cursor-pointer hover:bg-slate-200">#SapaTears</span>
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 whitespace-nowrap cursor-pointer hover:bg-slate-200">#JabiLakeVibes</span>
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
                        <div class="bg-gradient-to-br from-emerald-600 to-teal-800 text-white p-4 rounded-2xl shadow-md">
                            <span class="text-[10px] text-emerald-100 font-semibold uppercase block">Bank Savings Balance</span>
                            <span id="phoneBankBalance" class="text-xl font-extrabold font-mono block">₦0.00</span>
                            <div class="flex justify-between items-center mt-2 pt-2 border-t border-emerald-500/40 text-[10px]">
                                <span>Cash in Pocket: <strong id="phoneCashBalance">₦0.00</strong></span>
                                <span>Debt: <strong id="phoneLoanBalance" class="text-rose-200">₦0.00</strong></span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <button onclick="PhoneApp.openDirectTransferModal()" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-2 transition active:scale-95 shadow-sm">
                                <i class="fa-solid fa-paper-plane"></i> Send Money to @username
                            </button>
                            <button onclick="PhoneApp.quickTransfer()" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl flex items-center justify-center gap-2 transition active:scale-95">
                                <i class="fa-solid fa-piggy-bank text-emerald-600"></i> Move Cash to Savings
                            </button>
                            <button onclick="PhoneApp.buyAirtime()" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl flex items-center justify-center gap-2 transition active:scale-95">
                                <i class="fa-solid fa-wifi text-blue-600"></i> Buy Airtime & Data VTU (₦1,000)
                            </button>
                            <button onclick="PhoneApp.applyMicroloan()" class="w-full py-2.5 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-900 font-bold text-xs rounded-xl flex items-center justify-center gap-2 transition active:scale-95">
                                <i class="fa-solid fa-hand-holding-dollar text-amber-600"></i> Emergency Sapa Microloan (₦10,000)
                            </button>
                        </div>
                    </div>

                    <!-- APP: CHOWDECK (FOOD DELIVERY) -->
                    <div id="phone-app-chowdeck" class="phone-screen hidden space-y-3">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <button onclick="PhoneApp.goHome()" class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600"><i class="fa-solid fa-arrow-left"></i></button>
                            <div>
                                <h4 class="font-bold text-sm text-slate-900 leading-none">Chowdeck Abuja</h4>
                                <span class="text-[9px] text-orange-500 font-extrabold uppercase">Instant Meal Delivery</span>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-500">Order hot food delivered straight to your location to boost energy and happiness.</p>
                        
                        <div class="space-y-2 max-h-[360px] overflow-y-auto pr-0.5">
                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xl">🥤</span>
                                    <div>
                                        <h5 class="text-xs font-bold text-slate-900">Cold Lacasera & Gala</h5>
                                        <span class="text-[10px] text-emerald-600 font-semibold">+15% Energy • +5% Happy</span>
                                    </div>
                                </div>
                                <button onclick="PhoneApp.orderFood('lacasera_gala')" class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs rounded-xl transition active:scale-95">
                                    ₦600
                                </button>
                            </div>

                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xl">🌯</span>
                                    <div>
                                        <h5 class="text-xs font-bold text-slate-900">Banex Double Shawarma</h5>
                                        <span class="text-[10px] text-emerald-600 font-semibold">+30% Energy • +15% Happy</span>
                                    </div>
                                </div>
                                <button onclick="PhoneApp.orderFood('shawarma')" class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs rounded-xl transition active:scale-95">
                                    ₦2,500
                                </button>
                            </div>

                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xl">🍲</span>
                                    <div>
                                        <h5 class="text-xs font-bold text-slate-900">Buka Amala & Gbegiri</h5>
                                        <span class="text-[10px] text-emerald-600 font-semibold">+45% Energy • +12% Happy</span>
                                    </div>
                                </div>
                                <button onclick="PhoneApp.orderFood('amala')" class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs rounded-xl transition active:scale-95">
                                    ₦2,200
                                </button>
                            </div>

                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xl">🥩</span>
                                    <div>
                                        <h5 class="text-xs font-bold text-slate-900">Maitama VIP Beef Suya</h5>
                                        <span class="text-[10px] text-emerald-600 font-semibold">+35% Energy • +25% Happy</span>
                                    </div>
                                </div>
                                <button onclick="PhoneApp.orderFood('suya_pack')" class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs rounded-xl transition active:scale-95">
                                    ₦4,500
                                </button>
                            </div>

                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xl">🍗</span>
                                    <div>
                                        <h5 class="text-xs font-bold text-slate-900">Smoky Party Jollof</h5>
                                        <span class="text-[10px] text-emerald-600 font-semibold">+50% Energy • +20% Happy</span>
                                    </div>
                                </div>
                                <button onclick="PhoneApp.orderFood('jollof_party')" class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs rounded-xl transition active:scale-95">
                                    ₦3,800
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- APP: CHAT (NaijaChat - WhatsApp & Peer Messaging) -->
                    <div id="phone-app-chat" class="phone-screen hidden space-y-2.5">
                        
                        <!-- CHAT THREADS VIEW (List of chats) -->
                        <div id="phoneChatMainView" class="space-y-2.5">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <button onclick="PhoneApp.goHome()" class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600 hover:text-slate-900 transition"><i class="fa-solid fa-arrow-left"></i></button>
                                    <div>
                                        <h4 class="font-bold text-xs text-slate-900 leading-none">NaijaChat</h4>
                                        <span class="text-[9px] text-green-600 font-extrabold uppercase">Live Citizen Messenger</span>
                                    </div>
                                </div>
                                <!-- Tab Switcher -->
                                <div class="flex bg-slate-100 p-0.5 rounded-lg text-[10px] font-bold">
                                    <button id="chatTabChats" onclick="PhoneApp.switchChatTab('chats')" class="px-2 py-0.5 rounded-md bg-white text-slate-800 shadow-sm">Chats</button>
                                    <button id="chatTabStatus" onclick="PhoneApp.switchChatTab('status')" class="px-2 py-0.5 rounded-md text-slate-500 hover:text-slate-800">Status</button>
                                </div>
                            </div>

                            <!-- Search & Start Chat by @username -->
                            <div class="relative">
                                <div class="flex gap-1.5">
                                    <div class="relative flex-1">
                                        <i class="fa-solid fa-at absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400"></i>
                                        <input 
                                            type="text" 
                                            id="phoneChatSearchInput" 
                                            placeholder="Type @username to chat..." 
                                            onkeydown="if(event.key==='Enter') PhoneApp.startChatFromInput()"
                                            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-6 pr-2.5 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-green-600 focus:bg-white transition"
                                        />
                                    </div>
                                    <button onclick="PhoneApp.startChatFromInput()" class="px-2.5 py-1.5 bg-green-600 hover:bg-green-500 text-white rounded-xl text-xs font-bold transition active:scale-95 shadow-sm">
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Active Citizens Carousel / Quick Picks -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-[10px] font-bold text-slate-500">
                                    <span>Active Citizens in Abuja:</span>
                                    <span class="text-[9px] text-green-600">Tap to text</span>
                                </div>
                                <div id="phoneActiveCitizensBar" class="flex gap-1.5 overflow-x-auto pb-1 no-scrollbar text-xs">
                                    <!-- Populated dynamically -->
                                </div>
                            </div>

                            <!-- Chat Threads List -->
                            <div id="phoneChatList" class="space-y-1.5 max-h-[290px] overflow-y-auto pr-0.5"></div>

                            <!-- Status Updates List -->
                            <div id="phoneStatusList" class="hidden space-y-2 max-h-[340px] overflow-y-auto pr-0.5"></div>
                        </div>

                        <!-- CHAT CONVERSATION VIEW (Inside a specific thread) -->
                        <div id="phoneChatConversationView" class="hidden flex flex-col h-[390px]">
                            <!-- Conversation Header -->
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100 mb-1.5">
                                <div class="flex items-center gap-2">
                                    <button onclick="PhoneApp.closeConversation()" class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-600 hover:text-slate-900 transition">
                                        <i class="fa-solid fa-arrow-left"></i>
                                    </button>
                                    <div class="w-7 h-7 rounded-full bg-slate-200 overflow-hidden flex items-center justify-center text-xs" id="convHeaderAvatar">
                                        👤
                                    </div>
                                    <div class="leading-tight">
                                        <h5 class="font-bold text-xs text-slate-900 truncate max-w-[110px]" id="convHeaderName">Citizen</h5>
                                        <span class="text-[10px] text-green-600 font-semibold block" id="convHeaderUsername">@username</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button onclick="PhoneApp.openTransferToActiveContact()" title="Send Money via AbujaPay" class="px-2 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-[10px] font-extrabold flex items-center gap-1 transition active:scale-95">
                                        <i class="fa-solid fa-money-bill-wave text-emerald-600"></i> Send ₦
                                    </button>
                                </div>
                            </div>

                            <!-- Messages History Box -->
                            <div id="phoneMessagesContainer" class="flex-1 overflow-y-auto space-y-2 p-1 text-xs">
                                <!-- Messages injected dynamically -->
                            </div>

                            <!-- Message Input Form -->
                            <form onsubmit="PhoneApp.handleSendMessage(event)" class="mt-1.5 pt-1.5 border-t border-slate-100 flex items-center gap-1.5">
                                <input 
                                    type="text" 
                                    id="phoneMsgInput" 
                                    placeholder="Type message..." 
                                    autocomplete="off"
                                    required 
                                    class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:border-green-600 focus:bg-white"
                                />
                                <button type="submit" class="w-8 h-8 rounded-xl bg-green-600 hover:bg-green-500 text-white flex items-center justify-center text-xs shadow-sm transition active:scale-95">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </button>
                            </form>
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
         7. VIO / FRSC TRAFFIC CHECKPOINT MODAL
         ======================================================== -->
    <div id="lastmaModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative animate-fade-up">
            <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-xl mb-4">
                🚦
            </div>
            <h3 id="lastmaTitle" class="text-base font-bold text-slate-900 mb-1">VIO / FRSC Traffic Checkpoint!</h3>
            <p id="lastmaDesc" class="text-xs text-slate-500 mb-4">You've been flagged along Shehu Shagari Way for a vehicle check. How do you handle this?</p>
            <div id="lastmaViolation" class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs text-rose-800 font-medium mb-4"></div>
            <div class="space-y-2">
                <button onclick="GameApp.resolveLastma('bribe')" class="w-full p-3.5 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-900 rounded-2xl text-xs font-bold text-left transition active:scale-95">
                    💵 <strong>"Settle" the Officer (₦3,000-₦8,000)</strong> — Quick resolution, no questions asked
                </button>
                <button onclick="GameApp.resolveLastma('argue')" class="w-full p-3.5 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-900 rounded-2xl text-xs font-bold text-left transition active:scale-95">
                    😤 <strong>Argue Your Case</strong> — 50/50: free pass OR ₦5,000 court fine
                </button>
                <button onclick="GameApp.resolveLastma('receipt')" class="w-full p-3.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-900 rounded-2xl text-xs font-bold text-left transition active:scale-95">
                    📄 <strong>Show Valid Vehicle Papers & Tint Permit</strong> — 80% chance they let you go free
                </button>
            </div>
            <button onclick="document.getElementById('lastmaModal').classList.add('hidden'); document.getElementById('lastmaModal').classList.remove('flex')" class="w-full mt-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-2xl transition">Drive Away (Escape)</button>
        </div>
    </div>

    <!-- ========================================================
         8. INTERACTIVE WORK SHIFT SIMULATOR MODAL
         ======================================================== -->
    <div id="workShiftModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <!-- Shift Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div id="shiftJobIconWrapper" class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-lg shadow-sm">
                        <i id="shiftJobIcon" class="fa-solid fa-briefcase"></i>
                    </div>
                    <div>
                        <h3 id="shiftJobTitle" class="text-base font-extrabold text-slate-900 leading-tight">Federal Ministry Officer</h3>
                        <p id="shiftWorkplaceTagline" class="text-[11px] text-emerald-700 font-bold leading-none mt-0.5">Civil Service Secretariat</p>
                        <span class="text-xs text-slate-500 font-mono font-bold flex items-center gap-1.5 mt-1">
                            <span id="shiftPulseDot" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span id="shiftClockTime">09:00 AM</span> • <span id="shiftTimeDisplay">480s shift</span>
                        </span>
                    </div>
                </div>
                <!-- Live Pay Earned Counter -->
                <div class="text-right">
                    <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Accumulated Pay</span>
                    <span id="shiftAccPay" class="font-mono font-bold text-base text-emerald-600">₦0.00</span>
                </div>
            </div>

            <!-- Shift Progress Bar & Workplace Status -->
            <div class="space-y-1.5">
                <div class="flex justify-between text-xs font-bold text-slate-700">
                    <span id="shiftStatusText">💼 Normal Duties: Attending to office files...</span>
                    <span id="shiftPctText" class="font-mono text-emerald-600 font-extrabold">0%</span>
                </div>
                <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden p-0.5 border border-slate-200/60">
                    <div id="shiftProgressBar" class="bg-gradient-to-r from-emerald-500 to-teal-500 h-full rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <div class="flex justify-between text-[10px] text-slate-400 font-mono">
                    <span>09:00 AM (Start Shift)</span>
                    <span>01:00 PM (Midday)</span>
                    <span>05:00 PM (Close Shift)</span>
                </div>
            </div>

            <!-- Shift Environment & Vitals Indicators -->
            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                <div id="shiftEnvPower" class="p-2.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 font-bold flex items-center justify-center gap-1.5">
                    <i id="shiftEnvPowerIcon" class="fa-solid fa-bolt text-xs text-emerald-600"></i> <span id="shiftEnvPowerText">Power: ON</span>
                </div>
                <div id="shiftEnvBladder" class="p-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 font-bold flex items-center justify-center gap-1.5">
                    <i id="shiftEnvBladderIcon" class="fa-solid fa-restroom text-xs text-sky-600"></i> <span id="shiftEnvBladderText">Bladder: OK</span>
                </div>
                <div id="shiftEnvBoss" class="p-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 font-bold flex items-center justify-center gap-1.5">
                    <i id="shiftEnvBossIcon" class="fa-solid fa-user-tie text-xs text-slate-600"></i> <span id="shiftEnvBossText">Oga: In Office</span>
                </div>
            </div>

            <!-- DYNAMIC MID-SHIFT CRISIS CARD (Job-specific) -->
            <div id="shiftCrisisCard" class="hidden rounded-2xl p-4 border animate-fade-up space-y-3">
                <!-- Dynamically populated via GameApp.triggerShiftCrisis() -->
            </div>

            <!-- Routine Job Actions (Active when no blocking crisis) -->
            <div id="shiftRoutineActions" class="bg-slate-50 border border-slate-200 rounded-2xl p-3 space-y-2">
                <span id="shiftRoutineTitle" class="text-[11px] font-bold text-slate-700 block">Workplace Live Quick Actions:</span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button id="shiftBtnWork" onclick="GameApp.shiftDoWorkTask()" class="py-2.5 px-3 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl font-bold text-slate-800 transition active:scale-95 flex items-center justify-center gap-1.5">
                        <i id="shiftBtnWorkIcon" class="fa-solid fa-keyboard text-emerald-600"></i> <span id="shiftBtnWorkText">Process Work (+Speed)</span>
                    </button>
                    <button id="shiftBtnRelief" onclick="GameApp.shiftGoBathroom()" class="py-2.5 px-3 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl font-bold text-slate-800 transition active:scale-95 flex items-center justify-center gap-1.5">
                        <i id="shiftBtnReliefIcon" class="fa-solid fa-person-running text-sky-600"></i> <span id="shiftBtnReliefText">Relieve Urge</span>
                    </button>
                </div>
            </div>

            <!-- Early Departure Button -->
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                <span class="text-[10px] text-slate-400 leading-tight">Leaving early has severe consequences: docked salary & query!</span>
                <button onclick="GameApp.abandonShiftPrompt()" class="py-2 px-3.5 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 font-bold text-xs rounded-xl transition active:scale-95 whitespace-nowrap">
                    <i class="fa-solid fa-door-open mr-1"></i> Sneak Out Early
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================
         9. SHIFT SUMMARY DIALOG MODAL
         ======================================================== -->
    <div id="shiftSummaryModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-6 shadow-2xl relative animate-fade-up text-center space-y-4">
            <div id="shiftSummaryIcon" class="w-16 h-16 rounded-3xl bg-emerald-100 text-emerald-700 mx-auto flex items-center justify-center text-2xl shadow-sm">
                🎉
            </div>
            <div>
                <h3 id="shiftSummaryTitle" class="text-lg font-extrabold text-slate-900">Work Shift Officially Closed!</h3>
                <p id="shiftSummarySubtitle" class="text-xs text-slate-500 mt-1">Full 8-hour shift logged. Here is your daily payroll breakdown:</p>
            </div>

            <!-- Breakdown Receipt Card -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-xs space-y-2 text-left font-medium">
                <div class="flex justify-between">
                    <span class="text-slate-500">Base Daily Salary:</span>
                    <strong id="sumBasePay" class="font-mono text-slate-900">₦0.00</strong>
                </div>
                <div class="flex justify-between text-emerald-700">
                    <span>Client Tips & Gits:</span>
                    <strong id="sumTips" class="font-mono">+₦0.00</strong>
                </div>
                <div class="flex justify-between text-blue-700">
                    <span>Boss Performance Bonus:</span>
                    <strong id="sumBonus" class="font-mono">+₦0.00</strong>
                </div>
                <div class="flex justify-between text-rose-700">
                    <span>Disciplinary Deductions:</span>
                    <strong id="sumPenalties" class="font-mono">-₦0.00</strong>
                </div>
                <div class="border-t border-slate-200 pt-2 flex justify-between font-bold text-sm text-slate-900">
                    <span>Net Paid to Wallet:</span>
                    <strong id="sumNetPay" class="font-mono text-emerald-600 text-base">₦0.00</strong>
                </div>
            </div>

            <button onclick="GameApp.closeShiftSummary()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95">
                Collect Pay & Head Home (Evening)
            </button>
        </div>
    </div>

    <!-- ========================================================
         10. RESIDENCE & HOUSE MANAGEMENT MODAL ("Open Door & Enter")
         ======================================================== -->
    <div id="residenceModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-xl w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <div>
                        <h3 id="residenceTitle" class="text-base font-extrabold text-slate-900 leading-tight">Welcome Home</h3>
                        <span id="residenceSubtitle" class="text-xs text-slate-500 font-medium">Inside your Abuja residence</span>
                    </div>
                </div>
                <button onclick="GameApp.closeResidenceModal()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Residence Stats & Power Strip -->
            <div class="grid grid-cols-3 gap-2.5 text-center text-xs">
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Power Supply</span>
                    <strong id="resPowerMode" class="text-slate-900 text-xs font-mono uppercase">AEDC / NEPA</strong>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Inverter Battery</span>
                    <strong id="resInverterPct" class="text-emerald-700 text-xs font-mono">85% Charged</strong>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Generator Fuel</span>
                    <strong id="resGenFuel" class="text-amber-700 text-xs font-mono">6 Liters</strong>
                </div>
            </div>

            <!-- Residence Rooms & Interactions -->
            <div class="space-y-2.5">
                <span class="text-xs font-extrabold text-slate-700 block">Home Living & Utility Hub:</span>

                <!-- Room 1: Master Bedroom (Rest) -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-bed"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-slate-900">Master Bedroom</h4>
                            <p class="text-[11px] text-slate-500">Rest on your mattress with AC or rechargeable fan (+40 Energy, +15 Health).</p>
                        </div>
                    </div>
                    <button onclick="GameApp.manageResidence('rest')" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition active:scale-95 shadow-sm whitespace-nowrap">
                        Lie Down & Rest
                    </button>
                </div>

                <!-- Room 2: Power & Generator Management -->
                <div class="p-3.5 bg-amber-50/70 border border-amber-200 rounded-2xl space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-bolt text-amber-600 text-xs"></i>
                            <h4 class="font-bold text-xs text-amber-950">Power Grid & Generator Shed</h4>
                        </div>
                        <span class="text-[10px] font-bold text-amber-800">AEDC / Mikano Switcher</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px]">
                        <button onclick="GameApp.manageResidence('manage_power', 'nepa')" class="p-2 bg-white hover:bg-amber-100 border border-amber-200 text-slate-800 rounded-xl font-bold transition">
                            🔌 NEPA Grid
                        </button>
                        <button onclick="GameApp.manageResidence('manage_power', 'inverter')" class="p-2 bg-white hover:bg-amber-100 border border-amber-200 text-slate-800 rounded-xl font-bold transition">
                            🔋 Pure Inverter
                        </button>
                        <button onclick="GameApp.manageResidence('manage_power', 'generator')" class="p-2 bg-white hover:bg-amber-100 border border-amber-200 text-slate-800 rounded-xl font-bold transition">
                            ⚙️ Pull Generator
                        </button>
                        <button onclick="GameApp.manageResidence('manage_power', 'buy_fuel')" class="p-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl font-bold transition">
                            ⛽ Buy 10L Fuel (₦5k)
                        </button>
                    </div>
                </div>

                <!-- Room 3: Compound & Estate Neighbors -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-faucet-drip"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-slate-900">Compound Yard & Borehole</h4>
                            <p class="text-[11px] text-slate-500">Coordinate water pump roster & estate security dues (+5 Cred).</p>
                        </div>
                    </div>
                    <button onclick="GameApp.manageResidence('compound_meet')" class="px-3.5 py-2 bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-bold text-xs rounded-xl transition active:scale-95 whitespace-nowrap">
                        Estate Meeting
                    </button>
                </div>

                <!-- Room 4: Terrace / Balcony / Skyline -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-umbrella-beach"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-slate-900">Veranda & Skyline Balcony</h4>
                            <p class="text-[11px] text-slate-500">Unwind with a cold drink overlooking Abuja hills (+30 Happiness).</p>
                        </div>
                    </div>
                    <button onclick="GameApp.manageResidence('pool_balcony')" class="px-3.5 py-2 bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-bold text-xs rounded-xl transition active:scale-95 whitespace-nowrap">
                        Chill on Balcony
                    </button>
                </div>
            </div>

            <!-- Exit Door -->
            <button onclick="GameApp.closeResidenceModal()" class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95 flex items-center justify-center gap-2">
                <i class="fa-solid fa-person-walking-arrow-right"></i> Open Door & Step Outside to Abuja Streets
            </button>
        </div>
    </div>

    <!-- ========================================================
         11. COMMUTE FROM RESIDENCE TO WORKPLACE MODAL
         ======================================================== -->
    <div id="commuteModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-person-walking"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-tight">Step Out & Go to Work</h3>
                        <span class="text-xs text-slate-500 font-medium">Estate gate security salute & morning transit</span>
                    </div>
                </div>
                <button onclick="document.getElementById('commuteModal').classList.add('hidden'); document.getElementById('commuteModal').classList.remove('flex');" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-3 text-xs text-emerald-950 flex items-center gap-2.5">
                <i class="fa-solid fa-shield-halved text-emerald-600 text-base"></i>
                <div>
                    <span class="font-extrabold block">Security Gate Salute</span>
                    <span class="text-emerald-800 text-[11px]">"Oga good morning sir! Safe journey as you head to Central Area!"</span>
                </div>
            </div>

            <div class="space-y-2">
                <span class="text-xs font-bold text-slate-700 block">Select Morning Transit Mode:</span>
                
                <button onclick="GameApp.commuteToWork('walk')" class="w-full p-3 bg-white hover:bg-slate-50 border border-slate-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">🚶</span>
                        <div>
                            <strong class="text-xs text-slate-900 block">Morning Trek / Walking</strong>
                            <span class="text-[11px] text-slate-500">Free • Heavy sweat (-15 Energy)</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-700">₦0.00</span>
                </button>

                <button onclick="GameApp.commuteToWork('okada')" class="w-full p-3 bg-white hover:bg-slate-50 border border-slate-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">🏍️</span>
                        <div>
                            <strong class="text-xs text-slate-900 block">Okada Express</strong>
                            <span class="text-[11px] text-slate-500">Weaves through go-slow (-5 Energy)</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-900">₦400.00</span>
                </button>

                <button onclick="GameApp.commuteToWork('green_cab')" class="w-full p-3 bg-white hover:bg-slate-50 border border-slate-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">🚕</span>
                        <div>
                            <strong class="text-xs text-slate-900 block">Green Cab Taxi (Shared)</strong>
                            <span class="text-[11px] text-slate-500">Green & White Abuja shared cab (-5 Energy)</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-900">₦800.00</span>
                </button>

                <button onclick="GameApp.commuteToWork('bolt')" class="w-full p-3 bg-white hover:bg-slate-50 border border-slate-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">🚕</span>
                        <div>
                            <strong class="text-xs text-slate-900 block">Bolt AC Cab</strong>
                            <span class="text-[11px] text-slate-500">Chilled AC & relaxed arrival (0 Energy loss)</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-900">₦2,500.00</span>
                </button>

                <button onclick="GameApp.commuteToWork('car')" class="w-full p-3 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">🚗</span>
                        <div>
                            <strong class="text-xs text-emerald-950 block">Drive Personal Garage Car</strong>
                            <span class="text-[11px] text-emerald-800">Your vehicle (-2 Energy)</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-700">Garage</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================
         12. CITIZENS SOCIAL FINDER & DIRECTORY MODAL
         ======================================================== -->
    <div id="citizensFinderModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-tight">Abuja Citizens Directory</h3>
                        <span class="text-xs text-slate-500 font-medium">Find citizens by @username, district, or street reputation</span>
                    </div>
                </div>
                <button onclick="GameApp.closeCitizenFinder()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
                    <input type="text" id="citizenSearchInput" onkeyup="if(event.key==='Enter') GameApp.searchCitizens()" placeholder="Search @username, character name, or district (e.g. @boss, Maitama)..." class="w-full bg-slate-50 border border-slate-200 rounded-2xl pl-9 pr-3 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400">
                </div>
                <button onclick="GameApp.searchCitizens()" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-2xl transition active:scale-95 shadow-sm">
                    Search
                </button>
            </div>

            <!-- Citizen Cards Grid -->
            <div id="citizensResultsList" class="space-y-2 max-h-[380px] overflow-y-auto pr-1">
                <!-- Dynamically populated via GameApp.searchCitizens() -->
            </div>
        </div>
    </div>

    <!-- ========================================================
         13. CITIZEN PROFILE DOSSIER MODAL
         ======================================================== -->
    <div id="citizenProfileModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div id="profAvatarBox" class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-2xl shadow-sm">
                        👤
                    </div>
                    <div>
                        <h3 id="profFullName" class="text-base font-extrabold text-slate-900 leading-tight">Abuja Citizen</h3>
                        <span id="profUsername" class="text-xs font-mono font-bold text-purple-700">@citizen</span>
                    </div>
                </div>
                <button onclick="document.getElementById('citizenProfileModal').classList.add('hidden'); document.getElementById('citizenProfileModal').classList.remove('flex');" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Profile Overview Cards -->
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">District</span>
                    <strong id="profDistrict" class="text-slate-900">Maitama</strong>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Net Worth</span>
                    <strong id="profNetWorth" class="text-emerald-700 font-mono">₦0.00</strong>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Occupation</span>
                    <strong id="profJob" class="text-slate-900">Civil Servant</strong>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Street Cred</span>
                    <strong id="profCred" class="text-amber-700 font-mono">50 ⭐</strong>
                </div>
            </div>

            <!-- Residence & Vehicle -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 text-xs space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-500">Residence:</span>
                    <strong id="profResidence" class="text-slate-800">Serviced Flat</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Primary Ride:</span>
                    <strong id="profVehicle" class="text-slate-800">Mercedes-Benz G-Wagon</strong>
                </div>
            </div>

            <!-- Interactive Actions with Citizen -->
            <div class="space-y-2 pt-1">
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="GameApp.chatWithProfileCitizen()" class="py-2.5 px-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-comment-dots text-xs"></i> Chat on Phone
                    </button>
                    <button onclick="GameApp.openPeerTransfer(GameApp.activeInspectedCitizen)" class="py-2.5 px-3 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-money-bill-transfer text-xs"></i> Send Money
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="GameApp.hangoutWithProfileCitizen('drinks')" class="py-2.5 px-3 bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-utensils text-xs"></i> Buy Suya & Relate
                    </button>
                    <button onclick="GameApp.challengeCitizen(GameApp.activeInspectedCitizen)" class="py-2.5 px-3 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-dice text-xs text-amber-400"></i> Street Challenge
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         14. PEER MONEY TRANSFER & e-RECEIPT MODAL
         ======================================================== -->
    <div id="peerTransferModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-money-bill-transfer"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-tight">AbujaPay Peer Transfer</h3>
                        <span class="text-xs text-slate-500 font-medium">Instant wallet-to-wallet transfer</span>
                    </div>
                </div>
                <button onclick="document.getElementById('peerTransferModal').classList.add('hidden'); document.getElementById('peerTransferModal').classList.remove('flex');" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Form -->
            <div id="transferFormView" class="space-y-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Recipient Citizen (@username or Name):</label>
                    <input type="text" id="transferRecipientName" placeholder="Type @username (e.g. @bossman)..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition">
                    <input type="hidden" id="transferRecipientId">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Transfer Amount (₦):</label>
                    <input type="number" id="transferAmountInput" placeholder="Enter amount (e.g. 10000)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono font-bold text-slate-900">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Transfer Memo / Narration:</label>
                    <input type="text" id="transferMemoInput" value="Abuja life transfer" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800">
                </div>

                <button onclick="GameApp.sendPeerTransfer()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-lock"></i> Authorize & Send Transfer
                </button>
            </div>

            <!-- E-Receipt View -->
            <div id="transferReceiptView" class="hidden space-y-3 text-center">
                <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-700 mx-auto flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-slate-900">Transfer Successful!</h4>
                    <span class="text-[11px] text-slate-500 font-mono">AbujaPay Instant Central Switch</span>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-xs font-mono space-y-2 text-left">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Amount:</span>
                        <strong id="recAmount" class="text-emerald-700 text-sm">₦0.00</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Beneficiary:</span>
                        <strong id="recRecipient" class="text-slate-800">Recipient</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Reference:</span>
                        <strong id="recRef" class="text-slate-600 text-[10px]">ABP-2026-X</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Status:</span>
                        <span class="text-emerald-600 font-bold">COMPLETED</span>
                    </div>
                </div>
                <button onclick="document.getElementById('peerTransferModal').classList.add('hidden'); document.getElementById('peerTransferModal').classList.remove('flex');" class="w-full py-2.5 bg-slate-900 text-white font-bold rounded-2xl text-xs transition">
                    Done
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================
         15. STREET FIGHT & AGBERO CLASH MODAL
         ======================================================== -->
    <div id="streetFightModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-rose-600 text-white flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-hand-fist"></i>
                    </div>
                    <div>
                        <h3 id="fightTitle" class="text-base font-extrabold text-slate-900 leading-tight">Street Clash!</h3>
                        <span id="fightSubtitle" class="text-xs text-rose-700 font-bold">Kubwa / Wuse Street Defense</span>
                    </div>
                </div>
                <button onclick="document.getElementById('streetFightModal').classList.add('hidden'); document.getElementById('streetFightModal').classList.remove('flex');" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Health Bars -->
            <div class="space-y-3 bg-slate-50 border border-slate-200 rounded-2xl p-3.5">
                <!-- Opponent HP -->
                <div>
                    <div class="flex justify-between text-xs font-bold mb-1">
                        <span id="fightOppName" class="text-slate-900">Area Boy Opponent</span>
                        <span id="fightOppHpText" class="text-rose-700 font-mono">80 HP</span>
                    </div>
                    <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
                        <div id="fightOppHpBar" class="bg-rose-600 h-full transition-all duration-300" style="width: 100%"></div>
                    </div>
                </div>
                <!-- Player HP -->
                <div>
                    <div class="flex justify-between text-xs font-bold mb-1">
                        <span class="text-slate-900">Your Health</span>
                        <span id="fightPlayerHpText" class="text-emerald-700 font-mono">100 HP</span>
                    </div>
                    <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
                        <div id="fightPlayerHpBar" class="bg-emerald-600 h-full transition-all duration-300" style="width: 100%"></div>
                    </div>
                </div>
            </div>

            <!-- Fight Action Log -->
            <div id="streetFightLog" class="p-3 bg-slate-900 text-slate-200 rounded-2xl text-xs font-mono min-h-[60px] flex items-center">
                Opponent stepped forward squaring his shoulders! Pick your combat action.
            </div>

            <!-- Combat Buttons -->
            <div class="grid grid-cols-2 gap-2 text-xs">
                <button onclick="GameApp.doStreetFightAction('punch')" class="p-3 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl transition active:scale-95 flex items-center justify-center gap-1.5 shadow-sm">
                    🥊 Heavy Cross Punch
                </button>
                <button onclick="GameApp.doStreetFightAction('dodge_counter')" class="p-3 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl transition active:scale-95 flex items-center justify-center gap-1.5 shadow-sm">
                    🛡️ Slip & Counter
                </button>
                <button onclick="GameApp.doStreetFightAction('call_backup')" class="p-3 bg-amber-500 hover:bg-amber-400 text-white font-bold rounded-xl transition active:scale-95 flex items-center justify-center gap-1.5 shadow-sm">
                    📢 Call Area Boys (25 Cred)
                </button>
                <button onclick="GameApp.doStreetFightAction('settle')" class="p-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-xl transition active:scale-95 flex items-center justify-center gap-1.5 border border-slate-200">
                    💵 Settle (₦2,000)
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================
         16. NETWORK & ISP SIGNAL TROUBLESHOOTING MODAL
         ======================================================== -->
    <div id="networkTroublesModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-tower-cell"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-tight">Telco Network Hub</h3>
                        <span class="text-xs text-slate-500 font-medium">MTN / Airtel / Glo ISP Status in Abuja</span>
                    </div>
                </div>
                <button onclick="document.getElementById('networkTroublesModal').classList.add('hidden'); document.getElementById('networkTroublesModal').classList.remove('flex');" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Signal Indicator -->
            <div id="netStatusBanner" class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-xs">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                <div>
                    <strong id="netActiveProvider" class="text-emerald-950 block">Connected to MTN 4G LTE</strong>
                    <span id="netStatusNote" class="text-emerald-800 text-[11px]">All banking apps & POS transactions smooth.</span>
                </div>
            </div>

            <!-- Switcher & Troubleshooting Actions -->
            <div class="space-y-2">
                <span class="text-xs font-bold text-slate-700 block">Switch Cellular SIM / Reset Tower:</span>
                
                <button onclick="GameApp.switchSim('MTN')" class="w-full p-3 bg-white hover:bg-slate-50 border border-slate-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                        <div>
                            <strong class="text-xs text-slate-900 block">SIM 1: MTN Nigeria</strong>
                            <span class="text-[11px] text-slate-500">4G / 5G Broadband • Wuse / Maitama</span>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-slate-700">Select</span>
                </button>

                <button onclick="GameApp.switchSim('Airtel')" class="w-full p-3 bg-white hover:bg-slate-50 border border-slate-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                        <div>
                            <strong class="text-xs text-slate-900 block">SIM 2: Airtel FCT Express</strong>
                            <span class="text-[11px] text-slate-500">Fast 5G • Reliable for POS float</span>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-slate-700">Select</span>
                </button>

                <button onclick="GameApp.toggleAirplaneMode()" class="w-full p-3 bg-slate-900 hover:bg-slate-800 text-white rounded-2xl font-bold text-xs transition active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plane"></i> Toggle Airplane Mode (Refresh IP Handshake)
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================
         17. START A BRAND NEW LIFE MODAL (REBIRTH WITHOUT LOGOUT)
         ======================================================== -->
    <div id="startNewLifeModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-rotate"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-tight">Start a Brand New Life</h3>
                        <span class="text-xs text-slate-500 font-medium">Rebirth in Abuja without logging out</span>
                    </div>
                </div>
                <button onclick="document.getElementById('startNewLifeModal').classList.add('hidden'); document.getElementById('startNewLifeModal').classList.remove('flex');" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed bg-amber-50 border border-amber-200 rounded-2xl p-3">
                Want to reinvent yourself? Wipe your character's slate clean with a new name, district, and starting archetype. Your user account and login remain safe.
            </p>

            <div class="space-y-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">New Character Name:</label>
                    <input type="text" id="newLifeName" placeholder="Enter new full name (e.g. Tunde Balogun)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Gender:</label>
                        <select id="newLifeGender" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">District:</label>
                        <select id="newLifeDistrict" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                            <option value="Kubwa">Kubwa (Grassroots)</option>
                            <option value="Gwarinpa">Gwarinpa (Middle-class)</option>
                            <option value="Wuse 2">Wuse 2 (Vibrant Hub)</option>
                            <option value="Maitama">Maitama (Elite)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Starting Archetype:</label>
                    <select id="newLifeArchetype" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                        <option value="hustler">Street Hustler (₦15k cash float, hungry grind)</option>
                        <option value="middle">Middle-Class Professional (₦120k cash, Toyota Corolla)</option>
                        <option value="rich">Abuja Aristocrat / Politician (₦1.5M cash, ₦8.5M bank, Mercedes G-Wagon)</option>
                    </select>
                </div>

                <button onclick="GameApp.submitStartNewLife()" class="w-full py-3 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-sparkles"></i> Confirm Rebirth & Begin New Life
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================
         18. INTERACTIVE DESTINATION COMMUTE MODAL
         ======================================================== -->
    <div id="travelModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div id="travelDestIcon" class="w-11 h-11 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div>
                        <h3 id="travelDestTitle" class="text-base font-extrabold text-slate-900 leading-tight">Travel to Destination</h3>
                        <span id="travelDestSubtitle" class="text-xs text-slate-500 font-medium">Choose how to commute across Abuja</span>
                    </div>
                </div>
                <button onclick="GameApp.closeTravelModal()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed bg-slate-50 border border-slate-200 rounded-2xl p-3">
                Select your preferred way to travel. Walk to save money, take a green cab for fast transit, or order a Bolt for chilled AC comfort.
            </p>

            <div class="space-y-2.5">
                <button onclick="GameApp.confirmTravel('walk')" class="w-full p-3.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center text-base">🚶</div>
                        <div>
                            <strong class="text-xs text-slate-900 block group-hover:text-emerald-700">Trek / Walk</strong>
                            <span class="text-[11px] text-slate-500">Free • Burns -15 Energy</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-600">FREE</span>
                </button>

                <button onclick="GameApp.confirmTravel('taxi')" class="w-full p-3.5 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-base">🚕</div>
                        <div>
                            <strong class="text-xs text-emerald-950 block">Green Cab Taxi (Shared)</strong>
                            <span class="text-[11px] text-emerald-700">Fast & authentic Abuja ride • -2 Energy</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-800">₦800.00</span>
                </button>

                <button onclick="GameApp.confirmTravel('bolt')" class="w-full p-3.5 bg-sky-50 hover:bg-sky-100/80 border border-sky-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-600 text-white flex items-center justify-center text-base">🚗</div>
                        <div>
                            <strong class="text-xs text-sky-950 block">Bolt AC Cab (Private)</strong>
                            <span class="text-[11px] text-sky-700">Chilled luxury • 0 Energy loss</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-sky-800">₦2,200.00</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================
         19. INTERACTIVE DESTINATION ACTIVITIES MODAL
         ======================================================== -->
    <div id="destActivityModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div id="activityHeaderIcon" class="w-11 h-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-xl shadow-sm">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div>
                        <h3 id="activityHeaderTitle" class="text-base font-extrabold text-slate-900 leading-tight">Destination Name</h3>
                        <span id="activityHeaderSub" class="text-xs text-slate-500 font-medium">Abuja District Activity</span>
                    </div>
                </div>
                <button onclick="GameApp.closeActivityModal()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- Dynamic Activity Action Cards -->
            <div id="activityCardsContainer" class="space-y-3">
                <!-- Dynamically populated via JS -->
            </div>

            <!-- Citizens Present in this Venue -->
            <div class="pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-extrabold text-xs text-slate-900 flex items-center gap-1.5">
                        <span>👥</span> Citizens Present in this Venue
                    </h4>
                    <span class="text-[10px] text-emerald-600 font-extrabold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Online Now
                    </span>
                </div>
                <div id="activityVenueCitizens" class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    <!-- Dynamically populated with active citizens -->
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         20. CITIZEN MAP INSPECTION & INTERACTION MODAL
         ======================================================== -->
    <div id="citizenMapModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl relative animate-fade-up space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div id="mapCitizenAvatar" class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl shadow-sm overflow-hidden">
                        👤
                    </div>
                    <div>
                        <h3 id="mapCitizenName" class="text-base font-extrabold text-slate-900 leading-tight">Citizen Name</h3>
                        <span id="mapCitizenUsername" class="text-xs text-emerald-600 font-bold">@username</span>
                    </div>
                </div>
                <button onclick="document.getElementById('citizenMapModal').classList.add('hidden'); document.getElementById('citizenMapModal').classList.remove('flex');" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition active:scale-95">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">District</span>
                    <strong id="mapCitizenDistrict" class="text-slate-800 font-bold">Maitama</strong>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Profession / Grind</span>
                    <strong id="mapCitizenJob" class="text-slate-800 font-bold">Tech Founder</strong>
                </div>
            </div>

            <div class="space-y-2 pt-1">
                <button onclick="GameApp.chatWithMapCitizen()" class="w-full p-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-comment-dots"></i> Chat on NaijaChat Phone
                </button>
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="GameApp.transferToMapCitizen()" class="p-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-money-bill-transfer"></i> Send ₦
                    </button>
                    <button onclick="GameApp.hangoutWithMapCitizen()" class="p-3 bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold rounded-2xl text-xs shadow-md transition active:scale-95 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-utensils"></i> Buy Suya & Relate
                    </button>
                </div>
                <button onclick="GameApp.greetMapCitizen()" class="w-full p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs transition active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-hand"></i> Salute ("How Far Chairman!")
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/world3d.js"></script>
    <script src="assets/js/map3d.js"></script>
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
