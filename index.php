<?php
require_once __DIR__ . '/config.php';

// If user already logged in, redirect straight to game
if (getAuthUserId()) {
    header('Location: game.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abuja Life | The Ultimate Nigerian Urban RPG Simulation</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        abuja: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            gold: '#fbbf24'
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col selection:bg-emerald-500 selection:text-white">

    <!-- Top Navigation -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-slate-950 shadow-lg shadow-emerald-500/20 font-black text-xl">
                    <i class="fa-solid fa-mountain-sun"></i>
                </div>
                <div>
                    <span class="text-xl font-extrabold tracking-tight text-white">ABUJA <span class="text-emerald-400">LIFE</span></span>
                    <span class="hidden sm:inline-block text-[10px] uppercase font-bold tracking-widest text-emerald-500 bg-emerald-950/60 border border-emerald-800/60 px-2 py-0.5 rounded-full ml-2">FCT Simulation</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="openAuthModal('login')" class="text-sm font-semibold px-4 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition">
                    Sign In
                </button>
                <button onclick="openAuthModal('register')" class="text-sm font-semibold px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-900/40 transition">
                    Create Character
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 py-12 relative overflow-hidden">
        <!-- Ambient Glow -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[350px] bg-emerald-500/10 blur-[130px] rounded-full pointer-events-none"></div>

        <div class="max-w-4xl w-full text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900 border border-emerald-500/30 text-emerald-300 text-xs font-semibold mb-6 shadow-inner">
                <span class="flex h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Live in the Federal Capital Territory • Rise from Kubwa to Maitama
            </div>

            <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white mb-6 leading-tight">
                Build Your Empire in <br/>
                <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-amber-300 bg-clip-text text-transparent">The Centre of Unity</span>
            </h1>

            <p class="text-base sm:text-xl text-slate-300 max-w-2xl mx-auto mb-10 leading-relaxed font-light">
                Start with ₦35,000 cash in Kubwa. Choose your path: ride Keke, code for startups, broker federal contracts in Aso Rock, flip real estate in Maitama, and roll with siren escorts.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 max-w-md mx-auto">
                <button onclick="guestPlay()" id="guestBtn" class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-bold text-base shadow-xl shadow-emerald-500/25 transition transform active:scale-95">
                    <i class="fa-solid fa-bolt"></i> Instant Fast Play
                </button>
                <button onclick="openAuthModal('register')" class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white border border-slate-700 font-semibold text-base transition">
                    <i class="fa-solid fa-user-plus"></i> New Account
                </button>
            </div>

            <!-- Highlights Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-16 text-left">
                <div class="bg-slate-900/60 border border-slate-800 p-4 rounded-xl backdrop-blur">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-2">
                        <i class="fa-solid fa-city text-sm"></i>
                    </div>
                    <h3 class="font-bold text-sm text-white">8 Iconic Districts</h3>
                    <p class="text-xs text-slate-400 mt-0.5">From Lugbe & Kubwa to Wuse 2, Maitama & Asokoro.</p>
                </div>
                <div class="bg-slate-900/60 border border-slate-800 p-4 rounded-xl backdrop-blur">
                    <div class="w-8 h-8 rounded-lg bg-teal-500/10 text-teal-400 flex items-center justify-center mb-2">
                        <i class="fa-solid fa-briefcase text-sm"></i>
                    </div>
                    <h3 class="font-bold text-sm text-white">Abuja Careers</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Tech Bro, Civil Servant, Minister SA, Banex trader.</p>
                </div>
                <div class="bg-slate-900/60 border border-slate-800 p-4 rounded-xl backdrop-blur">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center mb-2">
                        <i class="fa-solid fa-building-columns text-sm"></i>
                    </div>
                    <h3 class="font-bold text-sm text-white">Property & Rent</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Buy apartments, duplexes, and mansions for daily passive Naira.</p>
                </div>
                <div class="bg-slate-900/60 border border-slate-800 p-4 rounded-xl backdrop-blur">
                    <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center mb-2">
                        <i class="fa-solid fa-car-side text-sm"></i>
                    </div>
                    <h3 class="font-bold text-sm text-white">Abuja Fleet</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Corolla 'Big Daddy', Lexus RX350, Benz C300, Range Rover.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 py-6 text-center text-xs text-slate-500">
        <p>© <?= date('Y') ?> Abuja Life RPG. Crafted for the love of the Federal Capital Territory, Nigeria.</p>
    </footer>

    <!-- Auth Modal -->
    <div id="authModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md hidden items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 sm:p-8 shadow-2xl relative">
            <button onclick="closeAuthModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <!-- Tabs -->
            <div class="flex border-b border-slate-800 mb-6">
                <button id="tabLogin" onclick="switchAuthTab('login')" class="flex-1 pb-3 text-sm font-bold border-b-2 border-emerald-500 text-emerald-400 transition">
                    Sign In
                </button>
                <button id="tabRegister" onclick="switchAuthTab('register')" class="flex-1 pb-3 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-white transition">
                    Create Character
                </button>
            </div>

            <!-- Error Box -->
            <div id="authError" class="hidden mb-4 p-3 rounded-xl bg-rose-950/80 border border-rose-500/40 text-rose-300 text-xs font-medium"></div>

            <!-- Login Form -->
            <form id="loginForm" onsubmit="handleLogin(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Username or Email</label>
                    <input type="text" name="username" required placeholder="e.g. abuja_boss" class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
                </div>
                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-sm shadow-lg shadow-emerald-950/50 transition">
                    Enter Abuja
                </button>
            </form>

            <!-- Register Form -->
            <form id="registerForm" onsubmit="handleRegister(event)" class="space-y-3.5 hidden">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Character Full Name</label>
                    <input type="text" name="full_name" required placeholder="e.g. Emeka Okafor or Zainab Aliyu" class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Username</label>
                        <input type="text" name="username" required placeholder="Unique username" class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Gender</label>
                        <select name="gender" class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Email Address</label>
                    <input type="email" name="email" required placeholder="you@domain.com" class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="Choose a password" class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-500">
                </div>
                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-sm shadow-lg shadow-emerald-950/50 transition">
                    Start Abuja Journey
                </button>
            </form>
        </div>
    </div>

    <script>
        function openAuthModal(tab = 'login') {
            document.getElementById('authModal').classList.remove('hidden');
            document.getElementById('authModal').classList.add('flex');
            switchAuthTab(tab);
        }

        function closeAuthModal() {
            document.getElementById('authModal').classList.add('hidden');
            document.getElementById('authModal').classList.remove('flex');
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
                tabLogin.className = "flex-1 pb-3 text-sm font-bold border-b-2 border-emerald-500 text-emerald-400 transition";
                tabRegister.className = "flex-1 pb-3 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-white transition";
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                tabLogin.className = "flex-1 pb-3 text-sm font-bold border-b-2 border-transparent text-slate-400 hover:text-white transition";
                tabRegister.className = "flex-1 pb-3 text-sm font-bold border-b-2 border-emerald-500 text-emerald-400 transition";
            }
        }

        async function handleLogin(e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);
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
                showError('Network error. Check database setup.');
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const form = e.target;
            const data = new FormData(form);
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
                showError('Network error. Check database setup.');
            }
        }

        async function guestPlay() {
            const btn = document.getElementById('guestBtn');
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Generating Guest...`;
            btn.disabled = true;

            const data = new FormData();
            data.append('action', 'guest');

            try {
                const res = await fetch('api/auth.php', { method: 'POST', body: data });
                const json = await res.json();
                if (json.success) {
                    window.location.href = json.redirect || 'game.php';
                } else {
                    alert(json.error || 'Guest login failed');
                    btn.innerHTML = `<i class="fa-solid fa-bolt"></i> Instant Fast Play`;
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Connection error. Please run install.php first if database is not ready.');
                btn.innerHTML = `<i class="fa-solid fa-bolt"></i> Instant Fast Play`;
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
