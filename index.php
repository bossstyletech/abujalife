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
    <title>Abuja Life | Urban Life Simulation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-up {
            animation: fadeInUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col antialiased selection:bg-emerald-600 selection:text-white">

    <!-- Top Navigation -->
    <header class="border-b border-slate-800/80 bg-slate-900/95 sticky top-0 z-40 backdrop-blur">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-sm transition group-hover:bg-emerald-500">
                    <i class="fa-solid fa-city text-base"></i>
                </div>
                <div>
                    <span class="text-base font-bold text-white tracking-tight">Abuja Life</span>
                    <span class="block text-[11px] text-slate-400 font-medium">Life Simulator</span>
                </div>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <button onclick="openAuthModal('login')" class="text-xs sm:text-sm font-semibold px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition active:scale-95">
                    Log In
                </button>
                <button onclick="openAuthModal('register')" class="text-xs sm:text-sm font-semibold px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white transition active:scale-95 shadow-sm">
                    Register
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 py-12 sm:py-20">
        <div class="max-w-3xl w-full text-center animate-fade-up">
            
            <!-- City Tag -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-emerald-400 text-xs font-medium mb-6">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Federal Capital Territory, Nigeria
            </div>

            <!-- Main Heading: Natural, Solid Typography (No AI Text Gradients) -->
            <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight mb-5">
                Live, hustle, and build your life in Abuja.
            </h1>

            <p class="text-sm sm:text-lg text-slate-300 max-w-xl mx-auto mb-8 font-normal leading-relaxed">
                Start from Kubwa with ₦35,000. Land civil service jobs, run side hustles, acquire rental properties, cruise Wuse 2, and grow your net worth.
            </p>

            <!-- Action Buttons: Solid Colors, Well Rounded Corners -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 max-w-sm mx-auto mb-16">
                <button onclick="guestPlay()" id="guestBtn" class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm transition shadow-sm active:scale-95">
                    <i class="fa-solid fa-play text-xs"></i> Instant Demo Play
                </button>
                <button onclick="openAuthModal('register')" class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 font-semibold text-sm transition active:scale-95">
                    <i class="fa-solid fa-user-plus text-xs"></i> Create Account
                </button>
            </div>

            <!-- Feature Pillars: Clean Solid Cards with Well Rounded Corners -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left">
                <div class="bg-slate-900 border border-slate-800/80 p-5 rounded-2xl transition hover:border-slate-700">
                    <div class="w-10 h-10 rounded-xl bg-slate-800 text-emerald-400 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-briefcase text-base"></i>
                    </div>
                    <h3 class="font-bold text-sm text-white mb-1">Careers & Gigs</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Keke rider, Banex technician, software dev, bank manager, or special assistant.</p>
                </div>

                <div class="bg-slate-900 border border-slate-800/80 p-5 rounded-2xl transition hover:border-slate-700">
                    <div class="w-10 h-10 rounded-xl bg-slate-800 text-emerald-400 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-building text-base"></i>
                    </div>
                    <h3 class="font-bold text-sm text-white mb-1">Properties & Rent</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Acquire apartments in Gwarinpa or mansions in Maitama and earn daily rental income.</p>
                </div>

                <div class="bg-slate-900 border border-slate-800/80 p-5 rounded-2xl transition hover:border-slate-700">
                    <div class="w-10 h-10 rounded-xl bg-slate-800 text-emerald-400 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-car text-base"></i>
                    </div>
                    <h3 class="font-bold text-sm text-white mb-1">Vehicles & Lifestyle</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Drive Corollas to Benz C300s, relax at Millennium Park, and enjoy Abuja nightlife.</p>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 py-6 text-center text-xs text-slate-500">
        <div class="max-w-6xl mx-auto px-4">
            <p>© <?= date('Y') ?> Abuja Life. Designed for all screen sizes.</p>
        </div>
    </footer>

    <!-- Auth Modal -->
    <div id="authModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-200">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-xl relative animate-fade-up">
            <button onclick="closeAuthModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center absolute top-4 right-4 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <!-- Modal Tabs -->
            <div class="flex bg-slate-950 p-1 rounded-2xl mb-6 border border-slate-800/80">
                <button id="tabLogin" onclick="switchAuthTab('login')" class="flex-1 py-2 text-xs font-bold rounded-xl bg-slate-800 text-white transition">
                    Sign In
                </button>
                <button id="tabRegister" onclick="switchAuthTab('register')" class="flex-1 py-2 text-xs font-bold rounded-xl text-slate-400 hover:text-white transition">
                    New Account
                </button>
            </div>

            <!-- Error Notification -->
            <div id="authError" class="hidden mb-4 p-3 rounded-2xl bg-rose-950/70 border border-rose-800 text-rose-300 text-xs font-medium"></div>

            <!-- Login Form -->
            <form id="loginForm" onsubmit="handleLogin(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Username or Email</label>
                    <input type="text" name="username" required placeholder="e.g. bossman" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-600 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-600 transition">
                </div>
                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-2xl text-sm transition active:scale-95 shadow-sm">
                    Enter Game
                </button>
            </form>

            <!-- Register Form -->
            <form id="registerForm" onsubmit="handleRegister(event)" class="space-y-3 hidden">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Character Name</label>
                    <input type="text" name="full_name" required placeholder="e.g. Tunde Balogun" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-600 transition">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Username</label>
                        <input type="text" name="username" required placeholder="username" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Gender</label>
                        <select name="gender" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-600 transition">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Email</label>
                    <input type="email" name="email" required placeholder="name@email.com" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-600 transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="Choose password" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-600 transition">
                </div>
                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-2xl text-sm transition active:scale-95 shadow-sm mt-2">
                    Create Character
                </button>
            </form>
        </div>
    </div>

    <script>
        function openAuthModal(tab = 'login') {
            const modal = document.getElementById('authModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            switchAuthTab(tab);
        }

        function closeAuthModal() {
            const modal = document.getElementById('authModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('authError').classList.add('hidden');
        }

        function switchAuthTab(tab) {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const tabLogin = document.getElementById('tabLogin');
            const tabRegister = document.getElementById('tabRegister');
            const authError = document.getElementById('authError');
            authError.classList.add('hidden');

            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                tabLogin.className = "flex-1 py-2 text-xs font-bold rounded-xl bg-slate-800 text-white transition";
                tabRegister.className = "flex-1 py-2 text-xs font-bold rounded-xl text-slate-400 hover:text-white transition";
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                tabLogin.className = "flex-1 py-2 text-xs font-bold rounded-xl text-slate-400 hover:text-white transition";
                tabRegister.className = "flex-1 py-2 text-xs font-bold rounded-xl bg-slate-800 text-white transition";
            }
        }

        async function handleLogin(e) {
            e.preventDefault();
            const data = new FormData(e.target);
            data.append('action', 'login');

            try {
                const res = await fetch('api/auth.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) {
                    window.location.href = json.redirect || 'game.php';
                } else {
                    showError(json.error || 'Login failed.');
                }
            } catch (err) {
                showError('Network error. Check database connection.');
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const data = new FormData(e.target);
            data.append('action', 'register');

            try {
                const res = await fetch('api/auth.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) {
                    window.location.href = json.redirect || 'game.php';
                } else {
                    showError(json.error || 'Registration failed.');
                }
            } catch (err) {
                showError('Network error. Check database connection.');
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
                    alert(json.error || 'Guest play failed.');
                    btn.innerHTML = `<i class="fa-solid fa-play text-xs"></i> Instant Demo Play`;
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Connection error. Visit install.php if the database is not set up.');
                btn.innerHTML = `<i class="fa-solid fa-play text-xs"></i> Instant Demo Play`;
                btn.disabled = false;
            }
        }

        function showError(msg) {
            const err = document.getElementById('authError');
            err.innerText = msg;
            err.classList.remove('hidden');
        }
    </script>
</body>
</html>
