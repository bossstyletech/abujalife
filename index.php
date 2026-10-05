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
        <div class="bg-white border border-slate-200 rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl relative my-8 animate-fade-up">
            
            <button onclick="closeWizard()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center absolute top-5 right-5 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <!-- Wizard Progress Steps -->
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span id="stepBadge1" class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">1</span>
                    <span class="text-xs font-bold text-slate-800">Origin Quiz</span>
                </div>
                <div class="h-0.5 flex-1 bg-slate-200 mx-3"></div>
                <div class="flex items-center gap-2">
                    <span id="stepBadge2" class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 font-bold text-xs flex items-center justify-center">2</span>
                    <span class="text-xs font-bold text-slate-500">Appearance</span>
                </div>
                <div class="h-0.5 flex-1 bg-slate-200 mx-3"></div>
                <div class="flex items-center gap-2">
                    <span id="stepBadge3" class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 font-bold text-xs flex items-center justify-center">3</span>
                    <span class="text-xs font-bold text-slate-500">Identity</span>
                </div>
            </div>

            <!-- STEP 1: BACKGROUND ORIGIN QUIZ -->
            <div id="wizardStep1" class="space-y-6">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">How does your story in Abuja begin?</h2>
                    <p class="text-xs text-slate-500 mt-1">Answer these 3 questions to calculate your starting family background and wealth.</p>
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
            <div id="wizardStep2" class="space-y-4 hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Snapchat Bitmoji 3D Studio</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Customize skin, hairstyle, top, jeans, and shoes in real time. Drag to turn 360°!</p>
                    </div>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                        Live 3D
                    </span>
                </div>

                <!-- Studio Layout: 3D Viewport on Left, Wardrobe Controls on Right -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
                    
                    <!-- 3D Character Viewport Column -->
                    <div class="md:col-span-5 flex flex-col items-center">
                        <div id="bitmojiStudioContainer" class="w-full h-[360px] sm:h-[400px] rounded-2xl bg-white border border-slate-200/90 relative shadow-inner overflow-hidden flex items-center justify-center cursor-grab active:cursor-grabbing">
                            <div class="text-xs text-slate-400 animate-pulse">Initializing 3D Character Studio...</div>
                        </div>

                        <!-- 3D Interaction Helpers -->
                        <div class="w-full mt-2.5 space-y-1.5">
                            <button type="button" onclick="turnCharacterAround()" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-arrows-rotate text-xs"></i> Turn Around 180°
                            </button>
                            <p class="text-[11px] text-slate-500 text-center font-medium">
                                <i class="fa-solid fa-hand-pointer text-slate-400 mr-1"></i> Drag left/right to spin 360°
                            </p>
                        </div>
                    </div>

                    <!-- Wardrobe & Customization Selectors Column -->
                    <div class="md:col-span-7 space-y-4 max-h-[440px] overflow-y-auto pr-1">
                        
                        <!-- 1. SKIN COMPLEXION -->
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80">
                            <label class="block text-xs font-bold text-slate-800 mb-2">1. Skin Complexion</label>
                            <div class="flex flex-wrap gap-2.5">
                                <button type="button" onclick="applySkin('#2b1d0c')" title="Deep Espresso" class="skin-btn w-9 h-9 rounded-full border-2 border-white shadow-sm transition transform hover:scale-110 active:scale-95" style="background-color: #2b1d0c;"></button>
                                <button type="button" onclick="applySkin('#3d2314')" title="Mahogany" class="skin-btn w-9 h-9 rounded-full border-2 border-white shadow-sm transition transform hover:scale-110 active:scale-95" style="background-color: #3d2314;"></button>
                                <button type="button" onclick="applySkin('#593822')" title="Rich Cocoa" class="skin-btn w-9 h-9 rounded-full border-2 border-white shadow-sm transition transform hover:scale-110 active:scale-95" style="background-color: #593822;"></button>
                                <button type="button" onclick="applySkin('#704225')" title="Warm Almond" class="skin-btn w-9 h-9 rounded-full border-2 border-white ring-2 ring-emerald-600 shadow-sm transition transform hover:scale-110 active:scale-95" style="background-color: #704225;"></button>
                                <button type="button" onclick="applySkin('#8d5524')" title="Bronze" class="skin-btn w-9 h-9 rounded-full border-2 border-white shadow-sm transition transform hover:scale-110 active:scale-95" style="background-color: #8d5524;"></button>
                                <button type="button" onclick="applySkin('#c68642')" title="Caramel" class="skin-btn w-9 h-9 rounded-full border-2 border-white shadow-sm transition transform hover:scale-110 active:scale-95" style="background-color: #c68642;"></button>
                            </div>
                        </div>

                        <!-- 2. HAIRSTYLE -->
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80">
                            <label class="block text-xs font-bold text-slate-800 mb-2">2. Hairstyle</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <button type="button" onclick="applyHair('fade')" class="hair-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    💈 Low Fade Cut
                                </button>
                                <button type="button" onclick="applyHair('afro')" class="hair-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    👑 Afro Crown
                                </button>
                                <button type="button" onclick="applyHair('dreads')" class="hair-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🦁 Dreadlocks
                                </button>
                                <button type="button" onclick="applyHair('cornrows')" class="hair-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    ✨ Cornrows
                                </button>
                                <button type="button" onclick="applyHair('buzz')" class="hair-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    ✂️ Clean Buzz
                                </button>
                            </div>
                        </div>

                        <!-- 3. TOPS / UPPER BODY -->
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80">
                            <label class="block text-xs font-bold text-slate-800 mb-2">3. Tops & Shirts</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" onclick="applyTop('hoodie')" class="top-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🧥 Tech Bro Hoodie
                                </button>
                                <button type="button" onclick="applyTop('tshirt')" class="top-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    👕 Casual T-Shirt
                                </button>
                                <button type="button" onclick="applyTop('agbada')" class="top-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🪡 Royal Agbada
                                </button>
                                <button type="button" onclick="applyTop('suit')" class="top-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    👔 Executive Navy Suit
                                </button>
                            </div>
                        </div>

                        <!-- 4. JEANS & BOTTOMS (REAL-TIME SWAP) -->
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80">
                            <label class="block text-xs font-bold text-slate-800 mb-2">4. Jeans & Trousers</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <button type="button" onclick="applyBottom('jeans_blue')" class="bottom-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    👖 Denim Blue Jeans
                                </button>
                                <button type="button" onclick="applyBottom('jeans_black')" class="bottom-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    👖 Slim Black Jeans
                                </button>
                                <button type="button" onclick="applyBottom('sweatpants')" class="bottom-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🏃 Fleece Joggers
                                </button>
                                <button type="button" onclick="applyBottom('chinos')" class="bottom-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🟤 Khaki Chinos
                                </button>
                                <button type="button" onclick="applyBottom('white_trouser')" class="bottom-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    ⚪ Royal Linen White
                                </button>
                            </div>
                        </div>

                        <!-- 5. SHOES & KICKS (REAL-TIME SWAP) -->
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80">
                            <label class="block text-xs font-bold text-slate-800 mb-2">5. Footwear & Shoes</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" onclick="applyShoes('sneakers')" class="shoe-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    👟 Crisp White AF1s
                                </button>
                                <button type="button" onclick="applyShoes('jordans')" class="shoe-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🏀 High-Top Air Jordans
                                </button>
                                <button type="button" onclick="applyShoes('loafers')" class="shoe-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    👞 Leather Loafers
                                </button>
                                <button type="button" onclick="applyShoes('slides')" class="shoe-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🩴 Casual Slides
                                </button>
                            </div>
                        </div>

                        <!-- 6. ACCESSORIES -->
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80">
                            <label class="block text-xs font-bold text-slate-800 mb-2">6. Accessories & Bling</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" onclick="applyAccessory('none')" class="acc-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🚫 None
                                </button>
                                <button type="button" onclick="applyAccessory('sunglasses')" class="acc-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🕶️ VIP Aviator Shades
                                </button>
                                <button type="button" onclick="applyAccessory('chain')" class="acc-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🪙 Gold Cuban Chain
                                </button>
                                <button type="button" onclick="applyAccessory('fila_cap')" class="acc-btn p-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 hover:border-emerald-500 transition text-left active:scale-95">
                                    🎩 Royal Fila Cap
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
                        Confirm 3D Looks & Proceed <i class="fa-solid fa-arrow-right ml-1"></i>
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
                        if (window.avatarStudio) {
                            window.avatarStudio.setSkin(selectedSkin);
                            highlightActiveOption('hair-btn', 'fade');
                            highlightActiveOption('top-btn', 'hoodie');
                            highlightActiveOption('bottom-btn', 'jeans_blue');
                            highlightActiveOption('shoe-btn', 'sneakers');
                            highlightActiveOption('acc-btn', 'none');
                        }
                    } else if (window.avatarStudio) {
                        window.avatarStudio.resize();
                    }
                }, 60);
            } else if (step === 3) {
                calculateArchetypeSummary();
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
                skinTone: selectedSkin,
                hairStyle: 'fade',
                topType: 'hoodie',
                bottomType: 'jeans_blue',
                shoeType: 'sneakers',
                accessory: 'none'
            };

            data.append('avatar_config', JSON.stringify(cfg));
            data.append('skin_tone', cfg.skinTone || selectedSkin);
            data.append('hair_style', cfg.hairStyle || 'fade');
            data.append('outfit', cfg.topType || 'hoodie');

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
