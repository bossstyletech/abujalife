<?php
require_once __DIR__ . '/config.php';

if (getAuthUserId()) {
    header('Location: game.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full overflow-hidden select-none">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Abuja Life | Urban RPG Simulation</title>
    <script>
        // Keep a real account alive across browser/session-cookie loss. This runs
        // before the login UI so a saved citizen returns straight to the city.
        (async () => {
            const token = localStorage.getItem('abuja_remember_token');
            if (!token) return;
            try {
                const body = new FormData();
                body.append('token', token);
                const response = await fetch('api/auth.php?action=restore_session', { method: 'POST', body, credentials: 'same-origin' });
                const data = await response.json();
                if (data.success) {
                    if (data.token) localStorage.setItem('abuja_remember_token', data.token);
                    window.location.replace('game.php');
                } else if (response.status === 401) {
                    localStorage.removeItem('abuja_remember_token');
                }
            } catch (_) { /* The regular sign-in screen remains available offline. */ }
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <!-- Three.js 3D Engine & OrbitControls for Realtime 3D City & Avatars -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
    <script src="assets/js/characters.js"></script>
    <script src="assets/js/avatar3d.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        /* Map container drag and pan styling */
        #mapViewport {
            touch-action: none;
            cursor: grab;
        }
        #mapViewport.grabbing {
            cursor: grabbing;
        }
        
        /* Pulse animations for pins */
        @keyframes pinBounce {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-4px) scale(1.05); }
        }
        .pin-animated {
            animation: pinBounce 2.5s infinite ease-in-out;
        }
        
        /* Subtle float for citizen badges */
        @keyframes citizenWalk {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(8px, -6px); }
        }
        .citizen-floating {
            animation: citizenWalk 4s infinite ease-in-out;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-900 h-full w-full overflow-hidden flex flex-col antialiased">

    <!-- ========================================================
         1. TOP FLOATING NAVIGATION & STATS BAR (Image 1 & 6)
         ======================================================== -->
    <div class="fixed top-3 left-0 right-0 z-40 px-3 sm:px-6 pointer-events-none">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
            
            <!-- Main Stats Pill -->
            <div class="pointer-events-auto bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-full px-4 py-2 shadow-lg flex items-center gap-3 text-xs font-bold text-slate-800">
                <div class="flex items-center gap-2 pr-2 border-r border-slate-200">
                    <span class="text-base">👑</span>
                    <span class="font-extrabold text-sm tracking-tight text-slate-900">Abuja Life</span>
                </div>
                <div class="flex items-center gap-1.5 text-emerald-600 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>46k online</span>
                </div>
                <span class="text-slate-300">•</span>
                <div class="hidden sm:flex items-center gap-1.5 text-slate-600">
                    <i class="fa-solid fa-users text-slate-400"></i>
                    <span>13762k visits</span>
                </div>
                <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-[11px] text-slate-700">
                    <span>🏛️ Gov @Farouk</span>
                </div>
                <div class="hidden lg:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-sky-50 text-[11px] text-sky-800 border border-sky-100">
                    <span>🏞️ Jabi Lake Plots</span>
                </div>
                <div class="hidden lg:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-[11px] text-emerald-800 border border-emerald-100">
                    <span>🏡 400 homes</span>
                </div>
            </div>

            <!-- Top Action Buttons (Sign up & Log in) -->
            <div class="pointer-events-auto flex items-center gap-2">
                <button onclick="openSignUpModal()" class="px-5 py-2 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition transform active:scale-95">
                    Sign up
                </button>
                <button onclick="openLoginModal()" class="px-4 py-2 rounded-full bg-white hover:bg-slate-50 text-slate-800 font-bold text-xs border border-slate-200 shadow-sm transition transform active:scale-95">
                    Log in
                </button>
            </div>
        </div>

        <!-- Floating Announcement Banner (Image 1) -->
        <div class="max-w-md mx-auto mt-2 pointer-events-auto">
            <div class="bg-emerald-800/90 text-emerald-100 backdrop-blur-md rounded-full px-4 py-1.5 text-[11px] font-bold text-center shadow-md flex items-center justify-center gap-2">
                <span>🎉 Diplomatic gala season is on this week in Maitama</span>
            </div>
        </div>

        <!-- Sub-Filter Pills Bar (Image 6) -->
        <div class="max-w-2xl mx-auto mt-2 pointer-events-auto flex items-center justify-center gap-1.5 flex-wrap">
            <div class="bg-white/90 backdrop-blur-md border border-slate-200/80 rounded-full px-3 py-1 text-[11px] font-bold text-slate-700 shadow-sm flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Serious go-slow
            </div>
            <div class="bg-white/90 backdrop-blur-md border border-slate-200/80 rounded-full px-3 py-1 text-[11px] font-bold text-slate-700 shadow-sm flex items-center gap-1.5">
                📊 Billboards
            </div>
            <div class="bg-white/90 backdrop-blur-md border border-slate-200/80 rounded-full px-3 py-1 text-[11px] font-bold text-slate-700 shadow-sm flex items-center gap-1.5">
                👨‍👩‍👧‍👦 Neighbours
            </div>
            <div class="bg-white/90 backdrop-blur-md border border-slate-200/80 rounded-full px-3 py-1 text-[11px] font-bold text-slate-700 shadow-sm flex items-center gap-1.5">
                🏛️ Gov
            </div>
            <div class="bg-white/90 backdrop-blur-md border border-slate-200/80 rounded-full p-0.5 text-[11px] font-bold shadow-sm flex items-center gap-1">
                <span class="px-2 py-0.5 text-slate-500">Lagos</span>
                <span class="px-2 py-0.5 text-slate-500">Port Harcourt</span>
                <span class="px-2.5 py-0.5 rounded-full bg-blue-600 text-white font-extrabold shadow-sm">🏛️ Abuja</span>
            </div>
        </div>
    </div>

    <!-- =====================================================
         2. FULL-SCREEN INTERACTIVE 3D ISOMETRIC ABUJA CITY MAP
         ===================================================== -->
    <main id="mapViewport" class="flex-1 w-full h-full relative overflow-hidden select-none bg-slate-900">

        <!-- Realtime 3D WebGL City Container — Zero quality loss on zoom -->
        <div id="map3d-container" class="absolute inset-0 w-full h-full cursor-grab active:cursor-grabbing"></div>

        <!-- 3D Camera Controls (Zoom In / Out / Reset) -->
        <div class="absolute right-4 bottom-24 z-30 flex flex-col gap-2 pointer-events-auto">
            <button onclick="if(window.Map3D) Map3D.zoom(1.25)" class="w-10 h-10 rounded-2xl bg-white/95 text-slate-800 border border-slate-200/90 shadow-lg flex items-center justify-center hover:bg-white active:scale-90 transition" title="Zoom In">
                <i class="fa-solid fa-plus text-xs"></i>
            </button>
            <button onclick="if(window.Map3D) Map3D.zoom(0.8)" class="w-10 h-10 rounded-2xl bg-white/95 text-slate-800 border border-slate-200/90 shadow-lg flex items-center justify-center hover:bg-white active:scale-90 transition" title="Zoom Out">
                <i class="fa-solid fa-minus text-xs"></i>
            </button>
            <button onclick="if(window.Map3D) Map3D.resetView()" class="w-10 h-10 rounded-2xl bg-white/95 text-slate-800 border border-slate-200/90 shadow-lg flex items-center justify-center hover:bg-white active:scale-90 transition" title="Reset View">
                <i class="fa-solid fa-crosshairs text-xs"></i>
            </button>
        </div>

        <!-- Location preview card — shown when pin clicked -->
        <div id="locationPreviewCard" class="absolute bottom-20 left-1/2 -translate-x-1/2 z-40 pointer-events-auto hidden"></div>

        <!-- Citizen preview card — shown when citizen clicked -->
        <div id="citizenPreviewCard" class="absolute bottom-20 left-1/2 -translate-x-1/2 z-40 pointer-events-auto hidden"></div>

    </main>
    <!-- ========================================================
         3. FLOATING COOKIE CONSENT BAR (Image 1)
         ======================================================== -->
    <div id="cookieBar" class="fixed bottom-4 left-4 z-40 max-w-sm bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-3xl p-4 shadow-2xl flex items-start gap-3 text-xs animate-fade-up">
        <span class="text-2xl">🍪</span>
        <div class="flex-1">
            <p class="text-slate-700 leading-snug">
                We use a cookie to keep you signed in, and another to count visits. No ad trackers, ever. 
                <a href="#" class="text-emerald-700 font-bold underline">Privacy</a>
            </p>
            <div class="flex items-center gap-2 mt-3">
                <button onclick="dismissCookie()" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition active:scale-95">
                    Essential only
                </button>
                <button onclick="dismissCookie()" class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm transition active:scale-95">
                    Accept
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================
         4. FLOATING BOTTOM SIGN UP CARD (Image 1)
         ======================================================== -->
    <div class="fixed bottom-4 left-1/2 -translate-x-1/2 z-40 pointer-events-auto">
        <div class="bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-3xl p-2.5 sm:p-3 shadow-2xl flex items-center gap-3 animate-fade-up">
            <!-- Avatars overlap -->
            <div class="flex -space-x-2 pl-1">
                <img class="w-8 h-8 rounded-full border-2 border-white object-cover" src="assets/img/characters/tunde/Man_standing_in_hoodie_20261005064533.png" alt="Avatar">
                <img class="w-8 h-8 rounded-full border-2 border-white object-cover" src="assets/img/characters/zainab/Young_woman_standing_wearing_hoodie_20261005064439.png" alt="Avatar">
                <img class="w-8 h-8 rounded-full border-2 border-white object-cover" src="assets/img/characters/farouk/Man_wearing_green_streetwear_hoodie_20261005064505.png" alt="Avatar">
            </div>
            
            <div class="hidden sm:block text-xs font-semibold text-slate-700 pr-2">
                <strong>46k Abujans</strong> playing right now • free
            </div>

            <button onclick="openSignUpModal()" class="px-5 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs shadow-md transition transform active:scale-95">
                Sign up free
            </button>
            <button onclick="openLoginModal()" class="px-4 py-2.5 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-800 font-bold text-xs border border-slate-200 shadow-sm transition transform active:scale-95">
                Log in
            </button>
        </div>
    </div>

    <!-- ========================================================
         5. SIGN UP MODAL (Image 2)
         ======================================================== -->
    <div id="signUpModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-sm w-full p-6 sm:p-7 shadow-2xl relative animate-fade-up my-auto">
            
            <!-- Close Button -->
            <button onclick="closeSignUpModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center absolute top-4 right-4 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <!-- Pill Tabs: Create account | Log in -->
            <div class="flex bg-slate-100 p-1 rounded-2xl mb-5 text-xs font-bold text-slate-600">
                <button type="button" class="flex-1 py-2 rounded-xl bg-white text-slate-900 font-extrabold shadow-sm transition">
                    Create account
                </button>
                <button type="button" onclick="switchToLoginModal()" class="flex-1 py-2 rounded-xl text-slate-600 hover:text-slate-900 transition">
                    Log in
                </button>
            </div>

            <form onsubmit="handleAccountCreation(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Your name</label>
                    <input 
                        type="text" 
                        id="regFullName" 
                        required 
                        placeholder="e.g. Aminu Bello" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Username</label>
                    <input 
                        type="text" 
                        id="regUsername" 
                        required 
                        placeholder="@aminu_fct" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition"
                    />
                    <p class="text-[11px] text-slate-400 mt-1">This is your Sim's name in Abuja Life.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="regPassword" 
                            required 
                            minlength="6"
                            placeholder="••••••••" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition pr-10"
                        />
                        <button type="button" onclick="togglePasswordVisibility('regPassword')" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-700 text-xs">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">At least 6 characters.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email (optional)</label>
                    <input 
                        type="email" 
                        id="regEmail" 
                        placeholder="you@email.com" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition"
                    />
                    <p class="text-[11px] text-slate-400 mt-1 leading-tight">Only used to reset your password if you ever forget it. Without one, a lost password can't be recovered.</p>
                </div>

                <div class="flex items-start gap-2 pt-1">
                    <input type="checkbox" id="regTerms" required class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500">
                    <label for="regTerms" class="text-[11px] text-slate-600 leading-tight">
                        I'm <strong>18 or older</strong> and I agree to the <a href="#" class="text-blue-600 underline">Terms</a> and <a href="#" class="text-blue-600 underline">Privacy Policy</a>.
                    </label>
                </div>

                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-sm rounded-2xl shadow-md transition transform active:scale-95">
                    Sign up • it's free
                </button>

                <p class="text-[11px] text-slate-400 text-center leading-tight">
                    Your Sim will live in the same Abuja as everyone else who signs up.
                </p>
            </form>
        </div>
    </div>

    <!-- ========================================================
         6. CHARACTER CUSTOMIZER WIZARD (Image 3: Look & Image 4: Traits)
         ======================================================== -->
    <div id="characterWizardModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-md hidden items-center justify-center p-3 sm:p-6 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-4xl w-full p-4 sm:p-6 shadow-2xl relative my-auto animate-fade-up max-h-[95vh] overflow-y-auto">
            
            <!-- Wizard Header Bar (Image 3) -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <button type="button" onclick="wizardGoBack()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center text-xs transition">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <div class="text-center">
                    <h3 id="wizardStepTitle" class="text-base font-extrabold text-slate-900">Look</h3>
                    <div class="flex items-center justify-center gap-1.5 mt-1">
                        <span id="wizardPill1" class="w-6 h-1 rounded-full bg-emerald-600"></span>
                        <span id="wizardPill2" class="w-6 h-1 rounded-full bg-slate-200"></span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="randomizeCharacter()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center text-xs transition" title="Randomize Look">
                        <i class="fa-solid fa-shuffle"></i>
                    </button>
                    <button id="wizardNextBtn" type="button" onclick="wizardGoNext()" class="px-3.5 py-1.5 rounded-full bg-emerald-600 text-white font-bold text-xs shadow-sm transition active:scale-95">
                        Next
                    </button>
                </div>
            </div>

            <!-- STEP 1: LOOK (Image 3) -->
            <div id="stepLookSection" class="grid grid-cols-1 md:grid-cols-12 gap-5 items-center">
                
                <!-- 3D Avatar Viewport with Circular Floor Shadow -->
                <div class="md:col-span-6 flex flex-col items-center">
                    <div id="wizard3dContainer" class="w-full h-[360px] sm:h-[440px] rounded-3xl bg-gradient-to-b from-sky-50 to-slate-100 border border-slate-200/80 relative overflow-hidden flex items-center justify-center cursor-grab active:cursor-grabbing shadow-inner">
                        <div class="text-xs text-slate-400 font-bold animate-pulse">Rendering 3D Sim...</div>
                    </div>
                    <span class="text-[11px] text-slate-400 font-bold mt-2">
                        <i class="fa-solid fa-hand-pointer text-slate-400 mr-1"></i> Drag to spin
                    </span>
                </div>

                <!-- Customizer Controls Card (Image 3) -->
                <div class="md:col-span-6 bg-slate-50 border border-slate-200/80 rounded-3xl p-5 space-y-4">
                    <!-- User Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                        <h4 id="wizardSimUsername" class="text-base font-extrabold text-slate-900">@aminu_fct</h4>
                        <span class="text-[11px] text-slate-400">your Sim's name</span>
                    </div>

                    <!-- Body Toggle: Woman | Man -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Body</label>
                        <div class="flex bg-slate-200/80 p-1 rounded-2xl text-xs font-bold">
                            <button type="button" id="btnGenderWoman" onclick="setSimGender('Woman')" class="flex-1 py-2 rounded-xl transition font-bold text-slate-600">
                                Woman
                            </button>
                            <button type="button" id="btnGenderMan" onclick="setSimGender('Man')" class="flex-1 py-2 rounded-xl bg-slate-900 text-white font-bold shadow-sm transition">
                                Man
                            </button>
                        </div>
                    </div>

                    <!-- Hairstyle Pills -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Hairstyle</label>
                        <div class="flex flex-wrap gap-1.5" id="hairstyleContainer">
                            <button type="button" onclick="setSimHair('braids')" class="hair-pill px-3 py-1.5 rounded-full bg-slate-900 text-white text-xs font-bold transition active:scale-95">Braids</button>
                            <button type="button" onclick="setSimHair('afro')" class="hair-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Afro</button>
                            <button type="button" onclick="setSimHair('bun')" class="hair-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Bun</button>
                            <button type="button" onclick="setSimHair('ponytail')" class="hair-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Ponytail</button>
                            <button type="button" onclick="setSimHair('long')" class="hair-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Long</button>
                            <button type="button" onclick="setSimHair('locs')" class="hair-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Locs</button>
                            <button type="button" onclick="setSimHair('lowcut')" class="hair-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Low cut</button>
                            <button type="button" onclick="setSimHair('gele')" class="hair-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Gele</button>
                            <button type="button" onclick="setSimHair('classic')" class="hair-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Classic</button>
                        </div>
                    </div>

                    <!-- Outfit Pills -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Outfit</label>
                        <div class="flex flex-wrap gap-1.5" id="outfitContainer">
                            <button type="button" onclick="setSimOutfit('casual')" class="outfit-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Casual</button>
                            <button type="button" onclick="setSimOutfit('office')" class="outfit-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Office</button>
                            <button type="button" onclick="setSimOutfit('kaftan')" class="outfit-pill px-3 py-1.5 rounded-full bg-slate-900 text-white text-xs font-bold transition active:scale-95">Senator / Kaftan</button>
                            <button type="button" onclick="setSimOutfit('agbada')" class="outfit-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Royal Agbada</button>
                            <button type="button" onclick="setSimOutfit('streetwear')" class="outfit-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Streetwear</button>
                            <button type="button" onclick="setSimOutfit('sitework')" class="outfit-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95">Site work</button>
                        </div>
                    </div>

                    <button type="button" onclick="wizardGoNext()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-2xl shadow-md transition transform active:scale-95">
                        Continue
                    </button>
                </div>

            </div>

            <!-- STEP 2: TRAITS (Image 4) -->
            <div id="stepTraitsSection" class="hidden space-y-4">
                <div>
                    <h3 id="traitsHeading" class="text-base font-extrabold text-slate-900">Choose 2 traits for @aminu_fct.</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Traits shape your personality, boost earnings, and unlock unique Abuja opportunities.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[380px] overflow-y-auto pr-1">
                    
                    <!-- Trait 1: Hustler -->
                    <div onclick="toggleTrait('hustler', this)" class="trait-card p-4 rounded-3xl bg-slate-50 hover:bg-slate-100 border-2 border-slate-200 cursor-pointer transition transform active:scale-95 flex items-start gap-3">
                        <span class="text-2xl">💼</span>
                        <div>
                            <h4 class="font-extrabold text-xs text-slate-900">Hustler</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">Sees money everywhere. Hustle skill grows faster and bosses notice.</p>
                        </div>
                    </div>

                    <!-- Trait 2: Foodie -->
                    <div onclick="toggleTrait('foodie', this)" class="trait-card p-4 rounded-3xl bg-slate-50 hover:bg-slate-100 border-2 border-slate-200 cursor-pointer transition transform active:scale-95 flex items-start gap-3">
                        <span class="text-2xl">🍲</span>
                        <div>
                            <h4 class="font-extrabold text-xs text-slate-900">Foodie</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">Lives for jollof & suya. Learns cooking fast and food is extra enjoyable.</p>
                        </div>
                    </div>

                    <!-- Trait 3: Party Spirit -->
                    <div onclick="toggleTrait('party', this)" class="trait-card p-4 rounded-3xl bg-slate-50 hover:bg-slate-100 border-2 border-slate-200 cursor-pointer transition transform active:scale-95 flex items-start gap-3">
                        <span class="text-2xl">🎉</span>
                        <div>
                            <h4 class="font-extrabold text-xs text-slate-900">Party Spirit</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">Always ready to spray money. Parties and dancing hit different, but gets bored quicker.</p>
                        </div>
                    </div>

                    <!-- Trait 4: Gym Rat -->
                    <div onclick="toggleTrait('gym', this)" class="trait-card p-4 rounded-3xl bg-slate-50 hover:bg-slate-100 border-2 border-slate-200 cursor-pointer transition transform active:scale-95 flex items-start gap-3">
                        <span class="text-2xl">💪</span>
                        <div>
                            <h4 class="font-extrabold text-xs text-slate-900">Gym Rat</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">Leg day every day. Fitness grows fast and workouts feel good.</p>
                        </div>
                    </div>

                    <!-- Trait 5: Smooth Talker -->
                    <div onclick="toggleTrait('smooth', this)" class="trait-card p-4 rounded-3xl bg-slate-50 hover:bg-slate-100 border-2 border-slate-200 cursor-pointer transition transform active:scale-95 flex items-start gap-3">
                        <span class="text-2xl">💬</span>
                        <div>
                            <h4 class="font-extrabold text-xs text-slate-900">Smooth Talker</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">Mouth sweet like honey. Charisma grows fast and socials land better.</p>
                        </div>
                    </div>

                    <!-- Trait 6: Lazy Bone -->
                    <div onclick="toggleTrait('lazy', this)" class="trait-card p-4 rounded-3xl bg-slate-50 hover:bg-slate-100 border-2 border-slate-200 cursor-pointer transition transform active:scale-95 flex items-start gap-3">
                        <span class="text-2xl">😴</span>
                        <div>
                            <h4 class="font-extrabold text-xs text-slate-900">Lazy Bone</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">Why stand when you can sit? Energy lasts longer, work suffers a bit.</p>
                        </div>
                    </div>

                    <!-- Trait 7: Tech Bro -->
                    <div onclick="toggleTrait('tech_bro', this)" class="trait-card p-4 rounded-3xl bg-slate-50 hover:bg-slate-100 border-2 border-slate-200 cursor-pointer transition transform active:scale-95 flex items-start gap-3">
                        <span class="text-2xl">💻</span>
                        <div>
                            <h4 class="font-extrabold text-xs text-slate-900">Tech Bro</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">Remote dollar hustler. Codes microservices and trades crypto arbitrage.</p>
                        </div>
                    </div>

                    <!-- Trait 8: Politico (Oga at Top) -->
                    <div onclick="toggleTrait('politico', this)" class="trait-card p-4 rounded-3xl bg-slate-50 hover:bg-slate-100 border-2 border-slate-200 cursor-pointer transition transform active:scale-95 flex items-start gap-3">
                        <span class="text-2xl">🏛️</span>
                        <div>
                            <h4 class="font-extrabold text-xs text-slate-900">Oga at the Top</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">Federal connection unlocked. Fast track promotion and ministerial contract bids.</p>
                        </div>
                    </div>

                </div>

                <div class="pt-2">
                    <button id="btnFinishWizard" type="button" onclick="submitCompleteSimRegistration()" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-sm rounded-2xl shadow-md transition transform active:scale-95">
                        Launch Abuja Life
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================================
         7. STANDARD LOGIN MODAL
         ======================================================== -->
    <div id="loginModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-sm w-full p-6 sm:p-7 shadow-2xl relative animate-fade-up">
            <button onclick="closeLoginModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center absolute top-4 right-4 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <!-- Pill Tabs -->
            <div class="flex bg-slate-100 p-1 rounded-2xl mb-5 text-xs font-bold text-slate-600">
                <button type="button" onclick="switchToSignUpModal()" class="flex-1 py-2 rounded-xl text-slate-600 hover:text-slate-900 transition">
                    Create account
                </button>
                <button type="button" class="flex-1 py-2 rounded-xl bg-white text-slate-900 font-extrabold shadow-sm transition">
                    Log in
                </button>
            </div>

            <div id="loginError" class="hidden mb-3 p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold"></div>

            <form onsubmit="handleStandardLogin(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Username or Email</label>
                    <input type="text" name="username" required placeholder="e.g. aminu_fct" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600">
                </div>
                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-sm rounded-2xl shadow-md transition transform active:scale-95">
                    Log in
                </button>
            </form>
        </div>
    </div>

    <!-- ========================================================
         8. LOCATION & CITIZEN PREVIEW MODAL (When clicking pins)
         ======================================================== -->
    <div id="locationPreviewModal" class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-sm w-full p-6 shadow-2xl relative animate-fade-up text-center space-y-3">
            <button onclick="closeLocationPreview()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center absolute top-4 right-4 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
            <div id="previewIcon" class="w-14 h-14 rounded-3xl bg-emerald-100 text-emerald-700 mx-auto flex items-center justify-center text-2xl shadow-sm">
                🏛️
            </div>
            <h3 id="previewTitle" class="text-base font-extrabold text-slate-900">Location Preview</h3>
            <p id="previewDesc" class="text-xs text-slate-600 leading-relaxed">Experience high-stakes activities, gym sessions, lakeside parties, and lucrative contracts in Abuja Life.</p>
            <button onclick="openSignUpModal()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-2xl shadow-md transition transform active:scale-95">
                Join Now to Enter & Play
            </button>
        </div>
    </div>

    <!-- Realtime Three.js 3D Map Engine -->
    <script src="assets/js/map3d.js"></script>

    <script>
        // ─────────────────────────────────────────────────────────
        // 1. Boot the Realtime Three.js 3D Abuja City Map
        // ─────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            if (window.Map3D) {
                Map3D.init(
                    'map3d-container',
                    // Landmark Pin clicked
                    (id, name) => { previewLocation(id); },
                    // Citizen clicked
                    (citizen) => {
                        previewCitizen(
                            '@' + citizen.username,
                            citizen.job || 'Abuja Resident',
                            citizen.district || 'Abuja FCT',
                            '₦5.8M'
                        );
                    }
                );
            }
        });



        function dismissCookie() {
            document.getElementById('cookieBar').classList.add('hidden');
        }

        // ----------------------------------------------------
        // 2. Modals Control & Location Previews
        // ----------------------------------------------------
        function openSignUpModal() {
            closeAllModals();
            document.getElementById('signUpModal').classList.remove('hidden');
            document.getElementById('signUpModal').classList.add('flex');
        }

        function closeSignUpModal() {
            document.getElementById('signUpModal').classList.add('hidden');
            document.getElementById('signUpModal').classList.remove('flex');
        }

        function openLoginModal() {
            closeAllModals();
            document.getElementById('loginModal').classList.remove('hidden');
            document.getElementById('loginModal').classList.add('flex');
        }

        function closeLoginModal() {
            document.getElementById('loginModal').classList.add('hidden');
            document.getElementById('loginModal').classList.remove('flex');
        }

        function switchToLoginModal() {
            closeSignUpModal();
            openLoginModal();
        }

        function switchToSignUpModal() {
            closeLoginModal();
            openSignUpModal();
        }

        function closeAllModals() {
            closeSignUpModal();
            closeLoginModal();
            closeLocationPreview();
        }

        function togglePasswordVisibility(fieldId) {
            const input = document.getElementById(fieldId);
            if (input) {
                input.type = input.type === 'password' ? 'text' : 'password';
            }
        }

        const LOCATIONS_DATA = {
            gym: { icon: '🏋️', title: 'Maitama Executive Gym', desc: 'State-of-the-art weights, cardio deck, and boxing ring. Build high health and street cred!' },
            restaurant: { icon: '🍲', title: 'Jabi Lake Grill & Suya Restaurant', desc: 'Lakeside dining serving smoky Nigerian party jollof, grilled tilapia, and chilled Chapman.' },
            banex: { icon: '📱', title: 'Banex Plaza Tech Hub (Wuse 2)', desc: 'The bustling trade center for gadget flipping, smartphone screen repairs, and quick cash profits!' },
            jabi_lake: { icon: '⛵', title: 'Jabi Lake Waterfront & Boat Club', desc: 'Chilling, jet ski cruises, and high-value networking with top citizens overlooking Jabi Lake.' },
            market: { icon: '🛍️', title: 'Wuse Modern Market', desc: 'The premier market for fabrics, perfumes, groceries, and intense price haggling.' },
            hospital: { icon: '🏥', title: 'National Hospital Abuja', desc: 'Premier healthcare facility for rapid treatment, checkups, and vitality restoration.' },
            mosque: { icon: '🕌', title: 'National Mosque', desc: 'Golden dome sanctuary along Central Area. Boost hope, spirituality, and inner calm.' },
            church: { icon: '⛪', title: 'National Christian Centre', desc: 'Iconic neo-gothic spire. Sunday fellowship, moral boosts, and social networking.' },
            fraser: { icon: '🏨', title: 'Fraser Suites Luxury Hotel', desc: 'Executive suites, diplomatic cocktails, and presidential ballroom gala events.' },
            cbd: { icon: '🏦', title: 'Central Business District & Twin Towers', desc: 'Corporate high-rises, commercial banking, stock and FX trading suites.' },
            secretariat: { icon: '🏛️', title: 'Three Arms Zone & Federal Secretariat', desc: 'Federal ministries, clerical careers, docket stamping, and high-stakes procurement tenders!' },
            stadium: { icon: '⚽', title: 'Moshood Abiola National Stadium', desc: 'Sprint tracks and athletic stamina training in the heart of Abuja.' },
            airport: { icon: '✈️', title: 'Capital Airport Terminal', desc: 'International flight arrivals, VIP pickups, and travel corridors along Airport Road.' }
        };

        function previewLocation(id) {
            const data = LOCATIONS_DATA[id] || { icon: '📍', title: 'Abuja Landmark', desc: 'Explore this district in Abuja Life.' };
            document.getElementById('previewIcon').textContent = data.icon;
            document.getElementById('previewTitle').textContent = data.title;
            document.getElementById('previewDesc').textContent = data.desc;
            document.getElementById('locationPreviewModal').classList.remove('hidden');
            document.getElementById('locationPreviewModal').classList.add('flex');
        }

        function previewCitizen(username, job, district, networth) {
            document.getElementById('previewIcon').textContent = '👤';
            document.getElementById('previewTitle').textContent = `${username} • ${district}`;
            document.getElementById('previewDesc').textContent = `${job} • Net worth: ${networth}. Text them, send money, or challenge them in Abuja Life!`;
            document.getElementById('locationPreviewModal').classList.remove('hidden');
            document.getElementById('locationPreviewModal').classList.add('flex');
        }

        function closeLocationPreview() {
            document.getElementById('locationPreviewModal').classList.add('hidden');
            document.getElementById('locationPreviewModal').classList.remove('flex');
        }

        // ----------------------------------------------------
        // 3. Wizard State & Character Customization (Image 3 & 4)
        // ----------------------------------------------------
        let simState = {
            fullName: '',
            username: '',
            password: '',
            email: '',
            gender: 'Man',
            hairStyle: 'braids',
            outfit: 'kaftan',
            traits: ['hustler', 'tech_bro'],
            characterId: 'tunde'
        };

        function handleAccountCreation(e) {
            e.preventDefault();
            simState.fullName = document.getElementById('regFullName').value.trim();
            let rawUser = document.getElementById('regUsername').value.trim();
            if (rawUser.startsWith('@')) rawUser = rawUser.substring(1);
            simState.username = rawUser;
            simState.password = document.getElementById('regPassword').value;
            simState.email = document.getElementById('regEmail').value.trim();

            closeSignUpModal();
            openCharacterWizard();
        }

        function openCharacterWizard() {
            document.getElementById('characterWizardModal').classList.remove('hidden');
            document.getElementById('characterWizardModal').classList.add('flex');
            document.getElementById('wizardSimUsername').textContent = `@${simState.username}`;
            document.getElementById('traitsHeading').textContent = `Choose 2 traits for @${simState.username}.`;
            wizardShowStep(1);

            // Initialize 3D Viewport in Look Studio
            setTimeout(() => {
                if (window.AvatarStudio3D) {
                    window.wizardAvatar = new AvatarStudio3D('wizard3dContainer', {
                        skinTone: '#704225',
                        outfit: simState.outfit,
                        hairStyle: simState.hairStyle
                    });
                }
            }, 100);
        }

        function wizardShowStep(step) {
            if (step === 1) {
                document.getElementById('stepLookSection').classList.remove('hidden');
                document.getElementById('stepTraitsSection').classList.add('hidden');
                document.getElementById('wizardStepTitle').textContent = 'Look';
                document.getElementById('wizardPill1').className = 'w-6 h-1 rounded-full bg-emerald-600';
                document.getElementById('wizardPill2').className = 'w-6 h-1 rounded-full bg-slate-200';
                document.getElementById('wizardNextBtn').textContent = 'Next';
            } else {
                document.getElementById('stepLookSection').classList.add('hidden');
                document.getElementById('stepTraitsSection').classList.remove('hidden');
                document.getElementById('wizardStepTitle').textContent = 'Traits';
                document.getElementById('wizardPill1').className = 'w-6 h-1 rounded-full bg-slate-200';
                document.getElementById('wizardPill2').className = 'w-6 h-1 rounded-full bg-emerald-600';
                document.getElementById('wizardNextBtn').textContent = 'Finish';
            }
        }

        function wizardGoNext() {
            const isStep1 = !document.getElementById('stepLookSection').classList.contains('hidden');
            if (isStep1) {
                wizardShowStep(2);
            } else {
                submitCompleteSimRegistration();
            }
        }

        function wizardGoBack() {
            const isStep2 = !document.getElementById('stepTraitsSection').classList.contains('hidden');
            if (isStep2) {
                wizardShowStep(1);
            } else {
                document.getElementById('characterWizardModal').classList.add('hidden');
                document.getElementById('characterWizardModal').classList.remove('flex');
                openSignUpModal();
            }
        }

        function setSimGender(gender) {
            simState.gender = gender;
            const btnWoman = document.getElementById('btnGenderWoman');
            const btnMan = document.getElementById('btnGenderMan');
            if (gender === 'Woman') {
                btnWoman.className = 'flex-1 py-2 rounded-xl bg-slate-900 text-white font-bold shadow-sm transition';
                btnMan.className = 'flex-1 py-2 rounded-xl text-slate-600 font-bold transition';
                simState.characterId = 'zainab';
            } else {
                btnMan.className = 'flex-1 py-2 rounded-xl bg-slate-900 text-white font-bold shadow-sm transition';
                btnWoman.className = 'flex-1 py-2 rounded-xl text-slate-600 font-bold transition';
                simState.characterId = 'tunde';
            }
            if (window.wizardAvatar) window.wizardAvatar.updateOutfit(simState.outfit);
        }

        function setSimHair(style) {
            simState.hairStyle = style;
            document.querySelectorAll('.hair-pill').forEach(btn => {
                btn.className = 'hair-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95';
            });
            event.target.className = 'hair-pill px-3 py-1.5 rounded-full bg-slate-900 text-white text-xs font-bold transition active:scale-95';
            if (window.wizardAvatar) window.wizardAvatar.updateHair(style);
        }

        function setSimOutfit(outfit) {
            simState.outfit = outfit;
            document.querySelectorAll('.outfit-pill').forEach(btn => {
                btn.className = 'outfit-pill px-3 py-1.5 rounded-full bg-white text-slate-700 border border-slate-200 text-xs font-bold transition active:scale-95';
            });
            event.target.className = 'outfit-pill px-3 py-1.5 rounded-full bg-slate-900 text-white text-xs font-bold transition active:scale-95';
            if (window.wizardAvatar) window.wizardAvatar.updateOutfit(outfit);
        }

        function randomizeCharacter() {
            const hairs = ['braids', 'afro', 'bun', 'ponytail', 'locs', 'lowcut', 'classic'];
            const outfits = ['casual', 'office', 'kaftan', 'agbada', 'streetwear', 'sitework'];
            const randomHair = hairs[Math.floor(Math.random() * hairs.length)];
            const randomOutfit = outfits[Math.floor(Math.random() * outfits.length)];
            simState.hairStyle = randomHair;
            simState.outfit = randomOutfit;
            if (window.wizardAvatar) {
                window.wizardAvatar.updateHair(randomHair);
                window.wizardAvatar.updateOutfit(randomOutfit);
            }
        }

        function toggleTrait(traitName, cardEl) {
            const idx = simState.traits.indexOf(traitName);
            if (idx > -1) {
                simState.traits.splice(idx, 1);
                cardEl.classList.remove('border-emerald-600', 'bg-emerald-50');
                cardEl.classList.add('border-slate-200', 'bg-slate-50');
            } else {
                if (simState.traits.length >= 2) {
                    // Remove first
                    const firstTrait = simState.traits.shift();
                    document.querySelectorAll('.trait-card').forEach(c => {
                        if (c.getAttribute('onclick').includes(firstTrait)) {
                            c.classList.remove('border-emerald-600', 'bg-emerald-50');
                            c.classList.add('border-slate-200', 'bg-slate-50');
                        }
                    });
                }
                simState.traits.push(traitName);
                cardEl.classList.add('border-emerald-600', 'bg-emerald-50');
                cardEl.classList.remove('border-slate-200', 'bg-slate-50');
            }
        }

        // ----------------------------------------------------
        // 4. API Registration Submission
        // ----------------------------------------------------
        async function submitCompleteSimRegistration() {
            const finishBtn = document.getElementById('btnFinishWizard');
            finishBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Registering Citizen ID...`;
            finishBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('username', simState.username);
            formData.append('full_name', simState.fullName);
            formData.append('password', simState.password);
            formData.append('email', simState.email);
            formData.append('gender', simState.gender);
            formData.append('hair_style', simState.hairStyle);
            formData.append('outfit', simState.outfit);
            formData.append('traits', JSON.stringify(simState.traits));
            formData.append('archetype', simState.traits.includes('tech_bro') ? 'strivers' : (simState.traits.includes('politico') ? 'rich' : 'hustler'));

            const avatarConfig = {
                characterId: simState.characterId,
                gender: simState.gender,
                outfit: simState.outfit,
                hairStyle: simState.hairStyle,
                skinTone: '#704225',
                traits: simState.traits
            };
            formData.append('avatar_config', JSON.stringify(avatarConfig));

            try {
                const res = await fetch('api/auth.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.success) {
                    if (json.token) {
                        localStorage.setItem('abuja_remember_token', json.token);
                        document.cookie = "abuja_remember_token=" + json.token + "; path=/; max-age=315360000; SameSite=Lax";
                    }
                    if (json.user_id) {
                        localStorage.setItem('abuja_user_id', json.user_id);
                        document.cookie = "abuja_user_id=" + json.user_id + "; path=/; max-age=315360000; SameSite=Lax";
                    }
                    window.location.href = json.redirect || 'game.php';
                } else {
                    alert(json.error || 'Registration error. Please choose a different username.');
                    finishBtn.innerHTML = `Launch Abuja Life`;
                    finishBtn.disabled = false;
                }
            } catch(e) {
                alert('Connection error. Initializing database schema.');
                finishBtn.innerHTML = `Launch Abuja Life`;
                finishBtn.disabled = false;
            }
        }

        async function handleStandardLogin(e) {
            e.preventDefault();
            const data = new FormData(e.target);
            data.append('action', 'login');

            try {
                const res = await fetch('api/auth.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) {
                    if (json.token) {
                        localStorage.setItem('abuja_remember_token', json.token);
                        document.cookie = "abuja_remember_token=" + json.token + "; path=/; max-age=315360000; SameSite=Lax";
                    }
                    if (json.user_id) {
                        localStorage.setItem('abuja_user_id', json.user_id);
                        document.cookie = "abuja_user_id=" + json.user_id + "; path=/; max-age=315360000; SameSite=Lax";
                    }
                    window.location.href = json.redirect || 'game.php';
                } else {
                    const errEl = document.getElementById('loginError');
                    errEl.textContent = json.error || 'Login failed.';
                    errEl.classList.remove('hidden');
                }
            } catch(err) {
                alert('Connection error.');
            }
        }
    </script>
</body>
</html>
