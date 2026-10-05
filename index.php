<?php
require_once __DIR__ . '/config.php';

if (getAuthUserId()) {
    header('Location: game.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Abuja Life | Urban RPG Simulation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <!-- Three.js 3D Engine for Bitmoji Real-Time Character Creator -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="assets/js/characters.js"></script>
    <script src="assets/js/avatar3d.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-up {
            animation: fadeInUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col antialiased selection:bg-emerald-600 selection:text-white">

    <!-- Top Navigation (Crisp White Layered) -->
    <header class="bg-white/95 border-b border-slate-200/80 sticky top-0 z-40 backdrop-blur shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-sm">
                    <i class="fa-solid fa-city text-base"></i>
                </div>
                <div>
                    <span class="text-base font-extrabold text-slate-900 tracking-tight">Abuja Life</span>
                    <span class="block text-[11px] text-slate-600 font-medium">Urban RPG Simulator</span>
                </div>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <button onclick="openAuthModal('login')" class="text-xs sm:text-sm font-semibold px-4 py-2 rounded-xl text-slate-700 hover:text-slate-900 hover:bg-slate-100 transition active:scale-95">
                    Sign In
                </button>
                <button onclick="startCharacterCreationWizard()" class="text-xs sm:text-sm font-bold px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition active:scale-95">
                    Build Character
                </button>
            </div>
        </div>
    </header>

    <!-- Main Hero on Pure White Canvas -->
    <main class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 py-12 sm:py-20">
        <div class="max-w-4xl w-full text-center animate-fade-up">
            
            <!-- City Tag -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-slate-200 text-emerald-700 text-xs font-semibold mb-6 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                The Centre of Unity • Federal Capital Territory
            </div>

            <h1 class="text-4xl sm:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight mb-5">
                Live, hustle, and rule <br/>
                <span class="text-emerald-700">the capital city.</span>
            </h1>

            <p class="text-base sm:text-xl text-slate-700 max-w-2xl mx-auto mb-10 font-normal leading-relaxed">
                Will you be born with a gold spoon in Maitama, or start from the grassroots in Kubwa? Customize your looks, wake up to real morning choices, commute to 3D rotatable workplaces, and build an empire.
            </p>

            <!-- Action Buttons: Solid Colors, Well Rounded -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 max-w-md mx-auto mb-16">
                <button onclick="startCharacterCreationWizard()" class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-md transition active:scale-95">
                    <i class="fa-solid fa-wand-magic-sparkles text-xs"></i> Create Character & Origin
                </button>
                <button onclick="guestPlay()" id="guestBtn" class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 font-bold text-sm shadow-sm transition active:scale-95">
                    <i class="fa-solid fa-play text-xs"></i> Instant Demo
                </button>
            </div>

            <!-- Elevated Layered Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 text-left">
                <div class="bg-white border border-slate-200/90 p-6 rounded-3xl shadow-sm hover:shadow-md transition">
                    <div class="w-11 h-11 rounded-2xl bg-slate-100 text-emerald-600 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-cube text-lg"></i>
                    </div>
                    <h3 class="font-bold text-base text-slate-900 mb-1.5">3D Rotatable World</h3>
                    <p class="text-xs text-slate-700 leading-relaxed">View your workplace and homes in full 360° rotatable 3D with zero quality loss.</p>
                </div>

                <div class="bg-white border border-slate-200/90 p-6 rounded-3xl shadow-sm hover:shadow-md transition">
                    <div class="w-11 h-11 rounded-2xl bg-slate-100 text-emerald-600 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-sun text-lg"></i>
                    </div>
                    <h3 class="font-bold text-base text-slate-900 mb-1.5">Morning to Night Routine</h3>
                    <p class="text-xs text-slate-700 leading-relaxed">Wake up in the morning, shower, have breakfast, commute to your shift, and manage time.</p>
                </div>

                <div class="bg-white border border-slate-200/90 p-6 rounded-3xl shadow-sm hover:shadow-md transition">
                    <div class="w-11 h-11 rounded-2xl bg-slate-100 text-emerald-600 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-mobile-screen text-lg"></i>
                    </div>
                    <h3 class="font-bold text-base text-slate-900 mb-1.5">Abuja Smartphone</h3>
                    <p class="text-xs text-slate-700 leading-relaxed">Send money on OPay/Kuda, play mini-games, answer WhatsApp chats, and call Bolt rides.</p>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-600">
        <p>© <?= date('Y') ?> Abuja Life. Designed for the Federal Capital Territory, Nigeria.</p>
    </footer>

    <!-- ============================================================
         CHARACTER CREATION WIZARD (ORIGIN QUIZ + FACE BUILDER + SIGNUP)
         ============================================================ -->
    <div id="wizardModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-4xl w-full p-4 sm:p-6 md:p-8 shadow-2xl relative my-4 sm:my-8 animate-fade-up max-h-[92vh] overflow-y-auto">
            
            <button onclick="closeWizard()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center absolute top-4 sm:top-5 right-4 sm:right-5 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <!-- Wizard Progress Steps -->
            <div class="flex items-center justify-between mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span id="stepBadge1" class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">1</span>
                    <span class="text-xs font-bold text-slate-800">Origin Quiz</span>
                </div>
                <div class="h-0.5 flex-1 bg-slate-200 mx-2 sm:mx-3"></div>
                <div class="flex items-center gap-2">
                    <span id="stepBadge2" class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 font-bold text-xs flex items-center justify-center">2</span>
                    <span class="text-xs font-bold text-slate-500">Appearance</span>
                </div>
                <div class="h-0.5 flex-1 bg-slate-200 mx-2 sm:mx-3"></div>
                <div class="flex items-center gap-2">
                    <span id="stepBadge3" class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 font-bold text-xs flex items-center justify-center">3</span>
                    <span class="text-xs font-bold text-slate-500">Identity</span>
                </div>
            </div>

            <!-- STEP 1: BACKGROUND ORIGIN QUIZ -->
            <div id="wizardStep1" class="space-y-6">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">How does your story in Abuja begin?</h2>
                    <p class="text-xs text-slate-500 mt-1">Answer these questions to calculate your starting family background and wealth.</p>
                </div>

                <!-- Question 1 -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">1. Where were you raised?</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-3 rounded-2xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                            <input type="radio" name="quiz_q1" value="rich" class="text-emerald-600 focus:ring-0 mr-3">
                            <span class="text-xs font-medium text-slate-800">Maitama luxury mansion with backup generators & diplomatic neighbors.</span>
                        </label>
                        <label class="flex items-center p-3 rounded-2xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                            <input type="radio" name="quiz_q1" value="middle" checked class="text-emerald-600 focus:ring-0 mr-3">
                            <span class="text-xs font-medium text-slate-800">Gwarinpa Estate civil service family flat with steady salary life.</span>
                        </label>
                        <label class="flex items-center p-3 rounded-2xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                            <input type="radio" name="quiz_q1" value="hustler" class="text-emerald-600 focus:ring-0 mr-3">
                            <span class="text-xs font-medium text-slate-800">Satellite town Kubwa face-me-I-face-you, grinding from scratch.</span>
                        </label>
                    </div>
                </div>

                <!-- Question 2 -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">2. What did your parents hand you on your 18th birthday?</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-3 rounded-2xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                            <input type="radio" name="quiz_q2" value="rich" class="text-emerald-600 focus:ring-0 mr-3">
                            <span class="text-xs font-medium text-slate-800">Lexus SUV keys and a loaded bank account.</span>
                        </label>
                        <label class="flex items-center p-3 rounded-2xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                            <input type="radio" name="quiz_q2" value="middle" checked class="text-emerald-600 focus:ring-0 mr-3">
                            <span class="text-xs font-medium text-slate-800">UniAbuja admission letter and a laptop for studies.</span>
                        </label>
                        <label class="flex items-center p-3 rounded-2xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                            <input type="radio" name="quiz_q2" value="hustler" class="text-emerald-600 focus:ring-0 mr-3">
                            <span class="text-xs font-medium text-slate-800">A pair of shoes, transport fare, and "God go help you".</span>
                        </label>
                    </div>
                </div>

                <button onclick="goToWizardStep(2)" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95">
                    Continue to Appearance & Face Customizer <i class="fa-solid fa-arrow-right ml-1"></i>
                </button>
            </div>

            <!-- STEP 2: BITMOJI 3D CHARACTER STUDIO (HEAD TO FEET IN REAL TIME) -->
            <!-- STEP 2: BITMOJI 3D CHARACTER STUDIO (CUSTOM NAME, FACE, THE FIT, KICKS) -->
            <div id="wizardStep2" class="space-y-3.5 hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold text-slate-900">Abuja 3D Character Studio</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Pick your face, dress your fit, and choose kicks in real time. Drag to rotate 360°!</p>
                    </div>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                        Live 3D Customizer
                    </span>
                </div>

                <!-- Studio Layout: 3D Viewport on Left (Desktop) / Top (Mobile), Controls on Right (Desktop) / Bottom (Mobile) -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-5 items-start">
                    
                    <!-- 3D Character Viewport Column (Mobile responsive height) -->
                    <div class="md:col-span-5 flex flex-col items-center w-full">
                        <div id="bitmojiStudioContainer" class="w-full h-[340px] sm:h-[400px] md:h-[480px] rounded-3xl bg-slate-50 border border-slate-200/90 relative shadow-inner overflow-hidden flex items-center justify-center cursor-grab active:cursor-grabbing">
                            <div class="text-xs text-slate-400 animate-pulse">Initializing 3D Character Studio...</div>
                        </div>

                        <!-- 3D Interaction Helpers -->
                        <div class="w-full mt-2 space-y-1">
                            <button type="button" onclick="turnCharacterAround()" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-arrows-rotate text-xs"></i> Turn Around 180°
                            </button>
                            <p class="text-[10px] sm:text-[11px] text-slate-500 text-center font-medium">
                                <i class="fa-solid fa-hand-pointer text-slate-400 mr-1"></i> Drag left/right to spin 360°
                            </p>
                        </div>
                    </div>

                    <!-- Character Customization Controls Column -->
                    <div class="md:col-span-7 flex flex-col w-full space-y-3">
                        
                        <!-- 1. CUSTOM NAME INPUT (Allows typing any name they want!) -->
                        <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/80">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-800">
                                    <i class="fa-solid fa-signature text-emerald-600 mr-1"></i> Character Name
                                </label>
                                <span class="text-[10px] text-slate-400">Type any name you want</span>
                            </div>
                            <input 
                                type="text" 
                                id="charStudioNameInput" 
                                oninput="onStudioNameChanged(this.value)" 
                                placeholder="Type your character name (e.g. Dapo Adeleke)..." 
                                class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-500 shadow-sm transition"
                            />
                        </div>

                        <!-- 2. CUSTOMIZER TABS (Face, The Fit, Kicks) -->
                        <div class="flex bg-slate-100 p-1 rounded-2xl text-xs font-bold text-slate-600">
                            <button type="button" onclick="switchStudioTab('face')" id="tabBtn-face" class="flex-1 py-2 rounded-xl bg-white text-emerald-950 font-bold shadow-sm transition flex items-center justify-center gap-1.5 active:scale-95">
                                <i class="fa-solid fa-user text-xs"></i> <span>1. Face</span>
                            </button>
                            <button type="button" onclick="switchStudioTab('outfit')" id="tabBtn-outfit" class="flex-1 py-2 rounded-xl text-slate-600 hover:text-slate-900 font-bold transition flex items-center justify-center gap-1.5 active:scale-95">
                                <i class="fa-solid fa-shirt text-xs"></i> <span>2. The Fit</span>
                            </button>
                            <button type="button" onclick="switchStudioTab('kicks')" id="tabBtn-kicks" class="flex-1 py-2 rounded-xl text-slate-600 hover:text-slate-900 font-bold transition flex items-center justify-center gap-1.5 active:scale-95">
                                <i class="fa-solid fa-shoe-prints text-xs"></i> <span>3. Kicks</span>
                            </button>
                        </div>

                        <!-- TAB 1: FACE & PERSONA SELECTION -->
                        <div id="studioTab-face" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-700">Choose Face & Persona:</span>
                                <span class="text-[10px] text-slate-400">9 Nigerian Archetypes</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2 max-h-[290px] sm:max-h-[320px] overflow-y-auto pr-1">
                                <button type="button" onclick="selectCharacter('tunde')" class="char-card p-2 rounded-2xl border-2 border-emerald-600 bg-emerald-50 text-center transition transform active:scale-95 group">
                                    <img src="assets/img/characters/tunde/Man_standing_in_hoodie_20261005064533.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Tunde">
                                    <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Tunde</span>
                                    <span class="block text-[9px] text-slate-500 truncate">Tech Bro</span>
                                </button>
                                <button type="button" onclick="selectCharacter('emeka')" class="char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                    <img src="assets/img/characters/emeka/Man_wearing_green_hoodie_standing_20261005064521.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Emeka">
                                    <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Emeka</span>
                                    <span class="block text-[9px] text-slate-500 truncate">Dealmaker</span>
                                </button>
                                <button type="button" onclick="selectCharacter('farouk')" class="char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                    <img src="assets/img/characters/farouk/Man_wearing_green_streetwear_hoodie_20261005064505.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Farouk">
                                    <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Farouk</span>
                                    <span class="block text-[9px] text-slate-500 truncate">Aristocrat</span>
                                </button>
                                <button type="button" onclick="selectCharacter('chidi')" class="char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                    <img src="assets/img/characters/chidi/Man_standing_in_hoodie_20261005064449.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Chidi">
                                    <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Chidi</span>
                                    <span class="block text-[9px] text-slate-500 truncate">Creative</span>
                                </button>
                                <button type="button" onclick="selectCharacter('zainab')" class="char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                    <img src="assets/img/characters/zainab/Young_woman_standing_wearing_hoodie_20261005064439.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Zainab">
                                    <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Zainab</span>
                                    <span class="block text-[9px] text-slate-500 truncate">FinTech</span>
                                </button>
                                <button type="button" onclick="selectCharacter('blessing')" class="char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                    <img src="assets/img/characters/blessing/Young_woman_standing_with_sneakers_20261005064429.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Blessing">
                                    <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Blessing</span>
                                    <span class="block text-[9px] text-slate-500 truncate">Curator</span>
                                </button>
                                <button type="button" onclick="selectCharacter('ibrahim')" class="char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                    <img src="assets/img/characters/ibrahim/Man_wearing_streetwear_hoodie_20261005064416.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Ibrahim">
                                    <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Ibrahim</span>
                                    <span class="block text-[9px] text-slate-500 truncate">Oil & Gas</span>
                                </button>
                                <button type="button" onclick="selectCharacter('segun')" class="char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                    <img src="assets/img/characters/segun/Man_wearing_green_hoodie_standing_20261005064405.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Segun">
                                    <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Segun</span>
                                    <span class="block text-[9px] text-slate-500 truncate">Hustler</span>
                                </button>
                                <button type="button" onclick="selectCharacter('ngozi')" class="char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group">
                                    <img src="assets/img/characters/ngozi/Woman_wearing_hoodie_and_sunglasses_20261005064346.png" class="w-11 h-11 rounded-full mx-auto object-cover border border-slate-200 group-hover:scale-105 transition" alt="Ngozi">
                                    <span class="block text-[11px] font-bold text-slate-900 mt-1 truncate">Ngozi</span>
                                    <span class="block text-[9px] text-slate-500 truncate">Attorney</span>
                                </button>
                            </div>
                        </div>

                        <!-- TAB 2: THE FIT (OUTFITS & WARDROBE) -->
                        <div id="studioTab-outfit" class="space-y-2 hidden">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-700">Choose Outfit Variation:</span>
                                <span class="text-[10px] text-emerald-700 font-bold">12 Variations</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 max-h-[290px] sm:max-h-[320px] overflow-y-auto pr-1">
                                <button type="button" onclick="applyOutfit('hoodie')" class="outfit-btn p-2.5 rounded-xl border border-emerald-600 bg-emerald-50 text-xs font-bold text-emerald-950 transition text-left active:scale-95">
                                    🧥 Tech Bro Hoodie
                                </button>
                                <button type="button" onclick="applyOutfit('black_hoodie')" class="outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🖤 Nightclub Black Streetwear
                                </button>
                                <button type="button" onclick="applyOutfit('tshirt')" class="outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    👕 Casual White Tee & Denim
                                </button>
                                <button type="button" onclick="applyOutfit('agbada')" class="outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🪡 Royal Agbada & Fila Cap
                                </button>
                                <button type="button" onclick="applyOutfit('kaftan')" class="outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    👑 Senator Navy Kaftan
                                </button>
                                <button type="button" onclick="applyOutfit('suit')" class="outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    💼 Executive Navy Suit
                                </button>
                                <button type="button" onclick="applyOutfit('blazer')" class="outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    👓 Techie Blazer & Chinos
                                </button>
                                <button type="button" onclick="applyOutfit('polo')" class="outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🎾 Country Club Polo & Khakis
                                </button>
                                <button type="button" onclick="applyOutfit('joggers')" class="outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🏃 Fleece Joggers & Slides
                                </button>
                                <button type="button" onclick="applyOutfit('sunglasses')" class="outfit-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🕶️ VIP Shades & Streetwear
                                </button>
                            </div>
                        </div>

                        <!-- TAB 3: KICKS (FOOTWEAR) -->
                        <div id="studioTab-kicks" class="space-y-2 hidden">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-700">Choose Footwear Kicks:</span>
                                <span class="text-[10px] text-slate-400">Sneakers & Shoes</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" onclick="applyOutfit('hoodie')" class="shoe-btn p-2.5 rounded-xl border border-emerald-600 bg-emerald-50 text-xs font-bold text-emerald-950 transition text-left active:scale-95">
                                    👟 Crisp White AF1s
                                </button>
                                <button type="button" onclick="applyOutfit('black_hoodie')" class="shoe-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🏀 High-Top Air Jordans
                                </button>
                                <button type="button" onclick="applyOutfit('suit')" class="shoe-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    👞 Handcrafted Leather Loafers
                                </button>
                                <button type="button" onclick="applyOutfit('joggers')" class="shoe-btn p-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🩴 Designer Casual Slides
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Navigation between steps -->
                <div class="flex gap-3 pt-3 border-t border-slate-100">
                    <button onclick="goToWizardStep(1)" class="w-1/3 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs transition active:scale-95">
                        Back
                    </button>
                    <button onclick="goToWizardStep(3)" class="flex-1 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95">
                        Next: Email & Account Setup <i class="fa-solid fa-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 3: ACCOUNT & NAME SETUP -->
            <div id="wizardStep3" class="space-y-4 hidden">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Name Your Character</h2>
                    <p class="text-xs text-slate-500 mt-1">Final step: Enter your credentials to enter Abuja.</p>
                </div>

                <div id="originSummaryBox" class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-900 font-medium">
                    Calculating your background...
                </div>

                <form id="wizardForm" onsubmit="submitCharacterRegistration(event)" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Character Full Name</label>
                        <input type="text" id="wizFullName" required placeholder="e.g. Tunde Balogun or Zainab Aliyu" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Username</label>
                            <input type="text" id="wizUsername" required placeholder="username" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Gender</label>
                            <select id="wizGender" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" id="wizEmail" required placeholder="you@domain.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                        <input type="password" id="wizPassword" required placeholder="Choose a password" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600">
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="goToWizardStep(2)" class="w-1/3 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs transition active:scale-95">
                            Back
                        </button>
                        <button type="submit" class="flex-1 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95">
                            Launch My Abuja Life
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- STANDARD SIGN IN MODAL -->
    <div id="authModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-white border border-slate-200 rounded-3xl max-w-sm w-full p-6 sm:p-8 shadow-2xl relative animate-fade-up">
            <button onclick="closeAuthModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center absolute top-4 right-4 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <h3 class="text-xl font-bold text-slate-900 mb-1">Welcome Back</h3>
            <p class="text-xs text-slate-500 mb-5">Sign in to your existing Abuja character.</p>

            <div id="loginError" class="hidden mb-4 p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium"></div>

            <form onsubmit="handleStandardLogin(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Username or Email</label>
                    <input type="text" name="username" required placeholder="username" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600">
                </div>
                <button type="submit" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-2xl text-xs shadow-md transition active:scale-95">
                    Sign In & Play
                </button>
            </form>
        </div>
    </div>

    <!-- Wizard & Auth Logic -->
    <script>
        let selectedSkin = '#704225';
        let calculatedArchetype = 'middle';
        let selectedCharacter = 'tunde';
        let selectedOutfit = 'hoodie';
        let userCustomName = '';

        function onStudioNameChanged(val) {
            userCustomName = (val || '').trim();
            if (window.avatarStudio) {
                window.avatarStudio.setName(userCustomName);
            }
            const wizInput = document.getElementById('wizFullName');
            if (wizInput) {
                wizInput.value = userCustomName;
            }
        }

        function switchStudioTab(tabId) {
            ['face', 'outfit', 'kicks'].forEach(t => {
                const panel = document.getElementById(`studioTab-${t}`);
                const btn = document.getElementById(`tabBtn-${t}`);
                if (t === tabId) {
                    if (panel) panel.classList.remove('hidden');
                    if (btn) {
                        btn.className = "flex-1 py-2 rounded-xl bg-white text-emerald-950 font-bold shadow-sm transition flex items-center justify-center gap-1.5 active:scale-95";
                    }
                } else {
                    if (panel) panel.classList.add('hidden');
                    if (btn) {
                        btn.className = "flex-1 py-2 rounded-xl text-slate-600 hover:text-slate-900 font-bold transition flex items-center justify-center gap-1.5 active:scale-95";
                    }
                }
            });
        }

        function selectCharacter(charId) {
            selectedCharacter = charId;
            if (window.avatarStudio) {
                window.avatarStudio.setCharacter(charId);
                if (userCustomName) {
                    window.avatarStudio.setName(userCustomName);
                }
            }
            document.querySelectorAll('.char-card').forEach(btn => {
                const onclickAttr = btn.getAttribute('onclick') || '';
                if (onclickAttr.includes(`'${charId}'`)) {
                    btn.className = "char-card p-2 rounded-2xl border-2 border-emerald-600 bg-emerald-50 text-center transition transform active:scale-95 group";
                } else {
                    btn.className = "char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group";
                }
            });

            // Set character name suggestion if user hasn't typed custom name
            if (typeof window.getCharacterById === 'function') {
                const char = window.getCharacterById(charId);
                if (char) {
                    const studioInput = document.getElementById('charStudioNameInput');
                    const nameInput = document.getElementById('wizFullName');
                    if (!userCustomName) {
                        if (studioInput) studioInput.value = char.name;
                        if (nameInput) nameInput.value = char.name;
                    }
                    const genderSelect = document.getElementById('wizGender');
                    if (genderSelect && char.gender) {
                        genderSelect.value = char.gender;
                    }
                }
            }
        }

        function applyOutfit(outfitKey) {
            selectedOutfit = outfitKey;
            if (window.avatarStudio) {
                window.avatarStudio.setOutfit(outfitKey);
            }
            highlightActiveOption('outfit-btn', outfitKey);
            highlightActiveOption('shoe-btn', outfitKey);
        }

        function startCharacterCreationWizard() {
            document.getElementById('wizardModal').classList.remove('hidden');
            document.getElementById('wizardModal').classList.add('flex');
            goToWizardStep(1);
        }

        function closeWizard() {
            document.getElementById('wizardModal').classList.add('hidden');
            document.getElementById('wizardModal').classList.remove('flex');
        }

        function openAuthModal(tab = 'login') {
            document.getElementById('authModal').classList.remove('hidden');
            document.getElementById('authModal').classList.add('flex');
        }

        function closeAuthModal() {
            document.getElementById('authModal').classList.add('hidden');
            document.getElementById('authModal').classList.remove('flex');
        }

        function goToWizardStep(step) {
            document.getElementById('wizardStep1').classList.add('hidden');
            document.getElementById('wizardStep2').classList.add('hidden');
            document.getElementById('wizardStep3').classList.add('hidden');

            document.getElementById('stepBadge1').className = "w-6 h-6 rounded-full font-bold text-xs flex items-center justify-center " + (step >= 1 ? "bg-emerald-600 text-white" : "bg-slate-200 text-slate-600");
            document.getElementById('stepBadge2').className = "w-6 h-6 rounded-full font-bold text-xs flex items-center justify-center " + (step >= 2 ? "bg-emerald-600 text-white" : "bg-slate-200 text-slate-600");
            document.getElementById('stepBadge3').className = "w-6 h-6 rounded-full font-bold text-xs flex items-center justify-center " + (step >= 3 ? "bg-emerald-600 text-white" : "bg-slate-200 text-slate-600");

            document.getElementById(`wizardStep${step}`).classList.remove('hidden');

            if (step === 2) {
                // Initialize or resize 3D Bitmoji Studio
                setTimeout(() => {
                    const studioContainer = document.getElementById('bitmojiStudioContainer');
                    if (!window.avatarStudio && studioContainer) {
                        window.avatarStudio = new Avatar3DStudio('bitmojiStudioContainer', {
                            width: studioContainer.clientWidth || 320,
                            height: 400,
                            showPlatform: true
                        });
                        const studioInput = document.getElementById('charStudioNameInput');
                        if (userCustomName) {
                            window.avatarStudio.setName(userCustomName);
                            if (studioInput) studioInput.value = userCustomName;
                        } else if (studioInput && studioInput.value) {
                            window.avatarStudio.setName(studioInput.value);
                        }
                    } else if (window.avatarStudio) {
                        window.avatarStudio.resize();
                    }
                }, 60);
            } else if (step === 3) {
                calculateArchetypeSummary();
                const studioInput = document.getElementById('charStudioNameInput');
                const wizName = document.getElementById('wizFullName');
                if (studioInput && studioInput.value && wizName) {
                    wizName.value = studioInput.value;
                }
            }
        }

        function turnCharacterAround() {
            if (window.avatarStudio) {
                window.avatarStudio.turnAround();
            }
        }

        function applySkin(hex) {
            selectedSkin = hex;
            if (window.avatarStudio) {
                window.avatarStudio.setSkin(hex);
            }
            document.querySelectorAll('.skin-btn').forEach(btn => {
                const style = btn.getAttribute('style') || '';
                if (style.includes(hex)) {
                    btn.classList.add('ring-2', 'ring-emerald-600');
                } else {
                    btn.classList.remove('ring-2', 'ring-emerald-600');
                }
            });
        }

        function applyHair(style) {
            if (window.avatarStudio) {
                window.avatarStudio.setHair(style);
            }
            highlightActiveOption('hair-btn', style);
        }

        function applyTop(type) {
            if (window.avatarStudio) {
                window.avatarStudio.setTop(type);
            }
            highlightActiveOption('top-btn', type);
        }

        function applyBottom(type) {
            if (window.avatarStudio) {
                window.avatarStudio.setBottom(type);
            }
            highlightActiveOption('bottom-btn', type);
        }

        function applyShoes(type) {
            if (window.avatarStudio) {
                window.avatarStudio.setShoes(type);
            }
            highlightActiveOption('shoe-btn', type);
        }

        function applyAccessory(type) {
            if (window.avatarStudio) {
                window.avatarStudio.setAccessory(type);
            }
            highlightActiveOption('acc-btn', type);
        }

        function highlightActiveOption(className, value) {
            document.querySelectorAll('.' + className).forEach(btn => {
                const onclickAttr = btn.getAttribute('onclick') || '';
                if (onclickAttr.includes(`'${value}'`)) {
                    btn.classList.add('border-emerald-600', 'bg-emerald-50', 'text-emerald-950');
                    btn.classList.remove('border-slate-200', 'bg-white');
                } else {
                    btn.classList.remove('border-emerald-600', 'bg-emerald-50', 'text-emerald-950');
                    btn.classList.add('border-slate-200', 'bg-white');
                }
            });
        }

        function calculateArchetypeSummary() {
            const q1 = document.querySelector('input[name="quiz_q1"]:checked')?.value || 'middle';
            const q2 = document.querySelector('input[name="quiz_q2"]:checked')?.value || 'middle';

            let richCount = 0;
            let hustlerCount = 0;
            if (q1 === 'rich') richCount++;
            if (q2 === 'rich') richCount++;
            if (q1 === 'hustler') hustlerCount++;
            if (q2 === 'hustler') hustlerCount++;

            if (richCount >= 2) {
                calculatedArchetype = 'rich';
                document.getElementById('originSummaryBox').innerHTML = `
                    <div class="font-bold text-emerald-800 text-xs mb-1"><i class="fa-solid fa-crown text-amber-500 mr-1"></i> Starting Class: Maitama Silver Spoon</div>
                    <p class="text-[11px] text-emerald-700 leading-snug">You start in a luxury villa in Maitama with ₦10,000,000 net worth and a Lexus RX350 SUV!</p>
                `;
            } else if (hustlerCount >= 2) {
                calculatedArchetype = 'hustler';
                document.getElementById('originSummaryBox').innerHTML = `
                    <div class="font-bold text-emerald-800 text-xs mb-1"><i class="fa-solid fa-bolt text-amber-500 mr-1"></i> Starting Class: Kubwa Grassroots Hustler</div>
                    <p class="text-[11px] text-emerald-700 leading-snug">You start from the bottom in Kubwa with ₦20,000 cash and pure Nigerian determination to rise!</p>
                `;
            } else {
                calculatedArchetype = 'middle';
                document.getElementById('originSummaryBox').innerHTML = `
                    <div class="font-bold text-emerald-800 text-xs mb-1"><i class="fa-solid fa-briefcase text-emerald-600 mr-1"></i> Starting Class: Gwarinpa Civil Service Strivers</div>
                    <p class="text-[11px] text-emerald-700 leading-snug">You start in Gwarinpa Estate with a UniAbuja BSc degree, ₦470,000 net worth, and a Toyota Corolla Big Daddy.</p>
                `;
            }
        }

        async function submitCharacterRegistration(e) {
            e.preventDefault();
            const data = new FormData();
            data.append('action', 'register');
            data.append('full_name', document.getElementById('wizFullName').value);
            data.append('username', document.getElementById('wizUsername').value);
            data.append('email', document.getElementById('wizEmail').value);
            data.append('password', document.getElementById('wizPassword').value);
            data.append('gender', document.getElementById('wizGender').value);
            data.append('archetype', calculatedArchetype);

            const cfg = window.avatarStudio ? window.avatarStudio.getConfig() : {
                characterId: selectedCharacter,
                outfit: selectedOutfit,
                skinTone: selectedSkin,
                hairStyle: 'fade',
                topType: selectedOutfit,
                bottomType: 'jeans_blue',
                shoeType: 'sneakers',
                accessory: 'none'
            };
            cfg.characterId = selectedCharacter;
            cfg.outfit = selectedOutfit;

            data.append('avatar_config', JSON.stringify(cfg));
            data.append('skin_tone', cfg.skinTone || selectedSkin);
            data.append('hair_style', cfg.hairStyle || 'fade');
            data.append('outfit', selectedOutfit);

            try {
                const res = await fetch('api/auth.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) {
                    window.location.href = json.redirect || 'game.php';
                } else {
                    alert(json.error || 'Registration failed.');
                }
            } catch (err) {
                alert('Connection error. Database initializing.');
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
                    window.location.href = json.redirect || 'game.php';
                } else {
                    document.getElementById('loginError').textContent = json.error || 'Login failed.';
                    document.getElementById('loginError').classList.remove('hidden');
                }
            } catch (err) {
                alert('Connection error.');
            }
        }

        async function guestPlay() {
            const btn = document.getElementById('guestBtn');
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-xs"></i> Loading...`;
            btn.disabled = true;

            const data = new FormData();
            data.append('action', 'guest');

            try {
                const res = await fetch('api/auth.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) {
                    window.location.href = json.redirect || 'game.php';
                } else {
                    alert(json.error || 'Guest failed');
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Connection error.');
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
