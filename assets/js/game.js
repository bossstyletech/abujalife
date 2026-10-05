/**
 * Abuja Life RPG - Core Frontend Game Engine
 * Clean, Human-Designed UI with Solid Colors, 3D Canvas Integration, and Morning Routine
 */

const GameApp = {
    character: null,
    netWorth: 0,
    activeTab: 'overview',
    pendingEvent: null,

    // Web Audio Synthesizer (Zero asset dependencies)
    playSfx(type = 'click') {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);

            if (type === 'money') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
                osc.start();
                osc.stop(ctx.currentTime + 0.25);
            } else if (type === 'win') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(523.25, ctx.currentTime);
                osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.08);
                osc.frequency.setValueAtTime(783.99, ctx.currentTime + 0.16);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
                osc.start();
                osc.stop(ctx.currentTime + 0.35);
            } else if (type === 'loss') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(320, ctx.currentTime);
                osc.frequency.linearRampToValueAtTime(200, ctx.currentTime + 0.2);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                osc.start();
                osc.stop(ctx.currentTime + 0.2);
            } else {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(440, ctx.currentTime);
                gain.gain.setValueAtTime(0.08, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.06);
                osc.start();
                osc.stop(ctx.currentTime + 0.06);
            }
        } catch (e) {}
    },

    formatNaira(amount) {
        return '₦' + Number(amount || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    formatCompactNaira(amount) {
        const val = Number(amount || 0);
        if (val >= 1000000) return '₦' + (val / 1000000).toFixed(1) + 'M';
        if (val >= 1000) return '₦' + (val / 1000).toFixed(0) + 'k';
        return '₦' + val.toFixed(0);
    },

    notify(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl text-xs font-semibold backdrop-blur transform transition-all duration-300 translate-y-2 opacity-0 z-50 border ${
            type === 'success' ? 'bg-white text-emerald-800 border-emerald-300 shadow-emerald-900/10' :
            type === 'error' ? 'bg-white text-rose-800 border-rose-300 shadow-rose-900/10' :
            'bg-white text-slate-800 border-slate-200'
        }`;
        
        const icon = type === 'success' ? 'fa-circle-check text-emerald-600' :
                     type === 'error' ? 'fa-triangle-exclamation text-rose-600' : 'fa-circle-info text-blue-600';
        
        toast.innerHTML = `<i class="fa-solid ${icon} text-sm"></i><span>${message}</span>`;
        
        const container = document.getElementById('toastContainer');
        if (container) {
            container.appendChild(toast);
            setTimeout(() => toast.classList.remove('translate-y-2', 'opacity-0'), 10);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
    },

    async init() {
        await this.fetchCharacter();
        this.switchTab('overview');
        this.checkForRandomEvent();
        this.checkWeather();
        this.calculateAgentFees();

        // Initialize 3D Workplace & House World
        if (window.World3D) {
            World3D.init('world3d-container');
        }
    },

    async fetchCharacter() {
        try {
            const res = await fetch('api/character.php?action=get');
            const data = await res.json();
            if (!data.success) {
                if (data.redirect) window.location.href = data.redirect;
                return;
            }
            this.character = data.character;
            this.netWorth = data.net_worth;
            this.districts = data.districts;
            this.renderHUD();
            this.renderOverviewLogs(data.logs || []);

            if (window.World3D && World3D.isInitialized) {
                World3D.updateScene();
            }
        } catch (err) {
            console.error('Error fetching character:', err);
        }
    },

    renderHUD() {
        if (!this.character) return;
        const c = this.character;

        // Player Info & Top Bar
        const topDist = document.getElementById('hudDistrictTop');
        if (topDist) topDist.textContent = c.district;

        document.getElementById('hudName').textContent = c.full_name;
        document.getElementById('hudAge').textContent = `${c.age} yrs • Day ${c.days_lived}`;
        document.getElementById('hudDistrict').textContent = c.district;

        // Financials
        document.getElementById('hudCash').textContent = this.formatNaira(c.cash);
        document.getElementById('hudBank').textContent = this.formatNaira(c.bank);
        document.getElementById('hudNetWorth').textContent = this.formatNaira(this.netWorth);

        // Bottom-left Minimized Floating Pill Updates
        const pillHealth = document.getElementById('pillHealth');
        if (pillHealth) pillHealth.textContent = `${c.health}%`;
        const pillEnergy = document.getElementById('pillEnergy');
        if (pillEnergy) pillEnergy.textContent = `${c.energy}%`;
        const pillCash = document.getElementById('pillCash');
        if (pillCash) pillCash.textContent = this.formatCompactNaira(c.cash);

        // Drawer Avatar Thumbnail
        const drawerPill = document.getElementById('drawerAvatarPill');
        if (drawerPill && typeof window.getCharacterOutfitImage === 'function') {
            let charId = 'tunde';
            let outfit = c.outfit || 'hoodie';
            if (c.avatar && typeof c.avatar === 'string' && c.avatar.trim().startsWith('{')) {
                try {
                    const cfg = JSON.parse(c.avatar);
                    if (cfg.characterId) charId = cfg.characterId;
                    if (cfg.outfit) outfit = cfg.outfit;
                } catch(e){}
            } else if (c.full_name) {
                const nameLower = c.full_name.toLowerCase();
                const roster = ['tunde', 'emeka', 'chidi', 'farouk', 'ibrahim', 'segun', 'zainab', 'blessing', 'ngozi'];
                for (const r of roster) {
                    if (nameLower.includes(r)) { charId = r; break; }
                }
            }
            const imgPath = window.getCharacterOutfitImage(charId, outfit);
            drawerPill.innerHTML = `<img src="${imgPath}" class="w-full h-full rounded-full object-cover" alt="Avatar">`;
        }

        // 3D HUD Indicators
        const jobLabel = document.getElementById('world3dCurrentJob');
        if (jobLabel) jobLabel.textContent = c.job_title || 'Unemployed Street Grinder';
        const homeLabel = document.getElementById('world3dCurrentHome');
        if (homeLabel) homeLabel.textContent = c.property_name || 'Renting Self-Con';

        // Time of Day Badge
        const timeBadge = document.getElementById('timeBadge');
        if (timeBadge) {
            const time = c.time_of_day || 'Morning';
            if (time === 'Morning') {
                timeBadge.className = "px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200";
                timeBadge.innerHTML = "☀️ Morning (7:00 AM)";
            } else if (time === 'Afternoon') {
                timeBadge.className = "px-3 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200";
                timeBadge.innerHTML = "⛅ Afternoon (1:00 PM)";
            } else if (time === 'Evening') {
                timeBadge.className = "px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-800 border border-indigo-200";
                timeBadge.innerHTML = "🌙 Evening (7:00 PM)";
            } else {
                timeBadge.className = "px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-300";
                timeBadge.innerHTML = "💤 Night (11:00 PM)";
            }
        }

        // Stats sliders
        this.updateStatBar('barEnergy', 'valEnergy', c.energy, 100, '%');
        this.updateStatBar('barHealth', 'valHealth', c.health, 100, '%');
        this.updateStatBar('barHappiness', 'valHappiness', c.happiness, 100, '%');
        this.updateStatBar('barIntelligence', 'valIntelligence', c.intelligence, 100, ' IQ');
        this.updateStatBar('barStreetCred', 'valStreetCred', c.street_cred, 100, ' Cred');

        this.checkSapaStatus();
    },

    updateStatBar(barId, valId, value, max = 100, unit = '%') {
        const bar = document.getElementById(barId);
        const val = document.getElementById(valId);
        const percent = Math.min(100, Math.max(0, (value / max) * 100));
        if (bar) bar.style.width = `${percent}%`;
        if (val) val.textContent = `${value}${unit}`;
    },

    renderOverviewLogs(logs) {
        const container = document.getElementById('recentLogsList');
        if (!container) return;

        if (logs.length === 0) {
            container.innerHTML = `<p class="text-xs text-slate-400 py-6 text-center">No recent activities recorded.</p>`;
            return;
        }

        container.innerHTML = logs.map(l => {
            let cashBadge = '';
            if (parseFloat(l.cash_change) > 0) {
                cashBadge = `<span class="text-emerald-700 font-bold text-xs">+${this.formatNaira(l.cash_change)}</span>`;
            } else if (parseFloat(l.cash_change) < 0) {
                cashBadge = `<span class="text-rose-600 font-bold text-xs">-${this.formatNaira(Math.abs(l.cash_change))}</span>`;
            }

            return `
                <div class="flex items-start justify-between gap-3 py-2.5 border-b border-slate-100 text-xs transition hover:bg-slate-50/80 px-2 rounded-xl">
                    <div class="flex items-start gap-2.5">
                        <div class="w-6 h-6 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] shrink-0 mt-0.5">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <p class="text-slate-700 leading-relaxed">${l.message}</p>
                    </div>
                    <div class="shrink-0 text-right ml-2 font-mono">
                        ${cashBadge}
                    </div>
                </div>
            `;
        }).join('');
    },

    activeHub: 'city',

    switchMenuHub(hubId) {
        this.activeHub = hubId;
        document.querySelectorAll('.hub-btn').forEach(btn => {
            btn.classList.remove('bg-white', 'text-emerald-950', 'font-extrabold', 'shadow-sm');
            btn.classList.add('text-slate-600', 'hover:text-slate-900', 'font-bold');
        });
        const activeHubBtn = document.getElementById(`hubBtn-${hubId}`);
        if (activeHubBtn) {
            activeHubBtn.classList.add('bg-white', 'text-emerald-950', 'font-extrabold', 'shadow-sm');
            activeHubBtn.classList.remove('text-slate-600', 'hover:text-slate-900', 'font-bold');
        }

        document.querySelectorAll('.hub-subnav').forEach(nav => nav.classList.add('hidden'));
        const activeSubnav = document.getElementById(`subnav-${hubId}`);
        if (activeSubnav) activeSubnav.classList.remove('hidden');

        const hubDefaults = {
            city: 'overview',
            hustle: 'jobs',
            wealth: 'realestate',
            social: 'social'
        };
        const defaultTab = hubDefaults[hubId] || 'overview';
        this.switchTab(defaultTab);
    },

    switchTab(tabId) {
        this.activeTab = tabId;

        // Ensure parent hub button and subnav are synchronized
        const tabToHub = {
            overview: 'city', transport: 'city', vehicles: 'city',
            jobs: 'hustle', hustles: 'hustle', economy: 'hustle',
            realestate: 'wealth', bank: 'wealth', leaderboard: 'wealth',
            social: 'social', lifestyle: 'social', casino: 'social'
        };
        const targetHub = tabToHub[tabId];
        if (targetHub) {
            this.activeHub = targetHub;
            document.querySelectorAll('.hub-btn').forEach(btn => {
                btn.classList.remove('bg-white', 'text-emerald-950', 'font-extrabold', 'shadow-sm');
                btn.classList.add('text-slate-600', 'hover:text-slate-900', 'font-bold');
            });
            const hubBtn = document.getElementById(`hubBtn-${targetHub}`);
            if (hubBtn) {
                hubBtn.classList.add('bg-white', 'text-emerald-950', 'font-extrabold', 'shadow-sm');
                hubBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
            }
            document.querySelectorAll('.hub-subnav').forEach(nav => nav.classList.add('hidden'));
            const subnav = document.getElementById(`subnav-${targetHub}`);
            if (subnav) subnav.classList.remove('hidden');
        }
        
        document.querySelectorAll('.game-tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('bg-emerald-600', 'text-white');
            btn.classList.add('text-slate-600', 'hover:text-slate-900');
        });

        const activeContent = document.getElementById(`tab-${tabId}`);
        if (activeContent) {
            activeContent.classList.remove('hidden');
            activeContent.classList.add('animate-fade-up');
        }

        const activeBtn = document.getElementById(`btn-tab-${tabId}`);
        if (activeBtn) {
            activeBtn.classList.add('bg-emerald-600', 'text-white');
            activeBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
        }

        if (tabId === 'jobs') this.loadJobs();
        if (tabId === 'hustles') this.loadHustles();
        if (tabId === 'transport') this.checkWeather();
        if (tabId === 'social') { this.checkConnections(); this.checkDecemberEvent(); }
        if (tabId === 'economy') { this.checkSapaStatus(); this.calculateAgentFees(); }
        if (tabId === 'realestate') this.loadRealEstate();
        if (tabId === 'vehicles') this.loadVehicles();
        if (tabId === 'lifestyle') this.loadLifestyle();
        if (tabId === 'bank') this.loadBank();
        if (tabId === 'leaderboard') this.loadLeaderboard();
    },

    // --- DAY & SCHEDULE ACTIONS ---
    async doMorningRoutine() {
        try {
            const res = await fetch('api/character.php?action=morning_routine', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                this.playSfx('win');
                this.notify(data.message, 'success');
                await this.fetchCharacter();
            } else {
                this.notify(data.error, 'error');
            }
        } catch (e) {
            this.notify("Morning routine error", "error");
        }
    },

    // ====================================================
    // INTERACTIVE WORK SHIFT SIMULATOR ENGINE
    // ====================================================
    shiftState: {
        active: false,
        timer: null,
        totalSeconds: 45, // 45 seconds real-time for full 8-hour workday
        elapsed: 0,
        progress: 0,
        job: null,
        baseSalary: 0,
        tips: 0,
        bonuses: 0,
        penalties: 0,
        powerOn: true,
        isAtDesk: true,
        bladderLevel: 0,
        crisesTriggered: {}
    },

    async goToWork() {
        if (!this.character || !this.character.current_job_id) {
            this.notify("You don't have a job yet! Apply in the Careers tab.", 'error');
            return;
        }

        try {
            const res = await fetch('api/character.php?action=start_shift');
            const data = await res.json();
            if (!data.success) {
                this.playSfx('loss');
                this.notify(data.error || 'Failed to start shift', 'error');
                return;
            }

            this.playSfx('click');
            this.startWorkShift(data.job, data.base_salary);
        } catch (e) {
            this.notify("Error connecting to workplace.", "error");
        }
    },

    startWorkShift(job, baseSalary) {
        this.shiftState = {
            active: true,
            timer: null,
            totalSeconds: 45,
            elapsed: 0,
            progress: 0,
            job: job,
            baseSalary: parseFloat(baseSalary || 0),
            tips: 0,
            bonuses: 0,
            penalties: 0,
            powerOn: true,
            isAtDesk: true,
            bladderLevel: 10,
            crisesTriggered: {}
        };

        // Open modal
        const modal = document.getElementById('workShiftModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        document.getElementById('shiftJobTitle').textContent = job.title || 'Federal Ministry Officer';
        this.updateShiftUI();

        // Start 1-second clock loop
        if (this.shiftState.timer) clearInterval(this.shiftState.timer);
        this.shiftState.timer = setInterval(() => this.tickWorkShift(), 1000);
    },

    tickWorkShift() {
        if (!this.shiftState.active) return;

        // If power is out, progress pauses until resolved!
        if (!this.shiftState.powerOn) {
            document.getElementById('shiftStatusText').textContent = '⚡ BLACKOUT: Office computers dark. Turn on generator to resume work!';
            return;
        }

        this.shiftState.elapsed += 1;
        this.shiftState.progress = Math.min(100, Math.round((this.shiftState.elapsed / this.shiftState.totalSeconds) * 100));

        // Gradual bladder pressure
        this.shiftState.bladderLevel = Math.min(100, this.shiftState.bladderLevel + 2);

        this.updateShiftUI();

        // Check Milestone Crises
        const p = this.shiftState.progress;

        // 1. Client Walk-in Crisis at ~20%
        if (p >= 20 && !this.shiftState.crisesTriggered['client']) {
            this.shiftState.crisesTriggered['client'] = true;
            this.triggerShiftCrisis('client');
        }

        // 2. Nature Calls (Pee / Stomach Rumbling) at ~45%
        if (p >= 45 && !this.shiftState.crisesTriggered['pee']) {
            this.shiftState.crisesTriggered['pee'] = true;
            this.triggerShiftCrisis('pee');
        }

        // 3. NEPA Blackout at ~68%
        if (p >= 68 && !this.shiftState.crisesTriggered['nepa']) {
            this.shiftState.crisesTriggered['nepa'] = true;
            this.triggerShiftCrisis('nepa');
        }

        // 4. Oga Boss Patrol at ~85%
        if (p >= 85 && !this.shiftState.crisesTriggered['boss']) {
            this.shiftState.crisesTriggered['boss'] = true;
            this.triggerShiftCrisis('boss');
        }

        // 5. Shift Complete at 100%
        if (p >= 100) {
            clearInterval(this.shiftState.timer);
            this.completeWorkShift();
        }
    },

    updateShiftUI() {
        const s = this.shiftState;
        const p = s.progress;

        // Virtual Clock Time (09:00 AM to 05:00 PM over 8 hours)
        const totalMinutes = Math.round((p / 100) * (8 * 60)); // 0 to 480 mins
        const hour = 9 + Math.floor(totalMinutes / 60);
        const mins = totalMinutes % 60;
        const hour12 = hour > 12 ? hour - 12 : hour;
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const timeStr = `${String(hour12).padStart(2, '0')}:${String(mins).padStart(2, '0')} ${ampm}`;

        document.getElementById('shiftClockTime').textContent = timeStr;
        document.getElementById('shiftTimeDisplay').textContent = `${s.elapsed}s / ${s.totalSeconds}s`;
        document.getElementById('shiftProgressBar').style.width = `${p}%`;
        document.getElementById('shiftPctText').textContent = `${p}%`;

        // Accumulated Pay
        const currentAccumulated = Math.max(0, Math.round((s.baseSalary * (p / 100)) + s.tips + s.bonuses - s.penalties));
        document.getElementById('shiftAccPay').textContent = this.formatNaira(currentAccumulated);

        // Environmental Indicators
        const envPower = document.getElementById('shiftEnvPower');
        const envPowerText = document.getElementById('shiftEnvPowerText');
        if (s.powerOn) {
            envPower.className = "p-2.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 font-bold flex items-center justify-center gap-1.5";
            envPowerText.textContent = "Power: ON";
        } else {
            envPower.className = "p-2.5 rounded-2xl bg-rose-50 border border-rose-300 text-rose-900 font-bold flex items-center justify-center gap-1.5 animate-pulse";
            envPowerText.textContent = "Power: ⚡ OUT";
        }

        const envBladder = document.getElementById('shiftEnvBladder');
        const envBladderText = document.getElementById('shiftEnvBladderText');
        if (s.bladderLevel >= 75) {
            envBladder.className = "p-2.5 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 font-bold flex items-center justify-center gap-1.5 animate-pulse";
            envBladderText.textContent = "Bladder: 🚨 FULL";
        } else {
            envBladder.className = "p-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 font-bold flex items-center justify-center gap-1.5";
            envBladderText.textContent = "Bladder: OK";
        }

        const envBoss = document.getElementById('shiftEnvBoss');
        const envBossText = document.getElementById('shiftEnvBossText');
        if (s.crisesTriggered['boss'] && !s.bossCrisisResolved) {
            envBoss.className = "p-2.5 rounded-2xl bg-purple-50 border border-purple-300 text-purple-900 font-bold flex items-center justify-center gap-1.5 animate-pulse";
            envBossText.textContent = "Oga: AT DESK 👀";
        } else {
            envBoss.className = "p-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 font-bold flex items-center justify-center gap-1.5";
            envBossText.textContent = "Oga: In Office";
        }
    },

    triggerShiftCrisis(type) {
        const crisisBox = document.getElementById('shiftCrisisCard');
        if (!crisisBox) return;

        this.playSfx('click');
        crisisBox.classList.remove('hidden');

        if (type === 'client') {
            crisisBox.className = "rounded-2xl p-4 border bg-amber-50 border-amber-200 text-amber-950 animate-fade-up space-y-2.5";
            crisisBox.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="text-xl">👤</span>
                    <div>
                        <h4 class="font-extrabold text-xs text-amber-900">VIP Client Walk-in!</h4>
                        <p class="text-[11px] text-amber-800">Alhaji Musa walks up to your desk demanding express tender clearance for his firm.</p>
                    </div>
                </div>
                <div class="space-y-1.5 pt-1">
                    <button onclick="GameApp.resolveShiftCrisis('client', 'polite')" class="w-full py-2 px-3 bg-white hover:bg-amber-100 border border-amber-300 rounded-xl text-left font-bold text-xs text-amber-900 transition flex items-center justify-between">
                        <span>🤝 Attend respectfully & swiftly</span>
                        <span class="text-[10px] text-emerald-700">+₦3,500 tip</span>
                    </button>
                    <button onclick="GameApp.resolveShiftCrisis('client', 'kola')" class="w-full py-2 px-3 bg-white hover:bg-amber-100 border border-amber-300 rounded-xl text-left font-bold text-xs text-amber-900 transition flex items-center justify-between">
                        <span>😏 Request "Kola Nut" facilitation fee</span>
                        <span class="text-[10px] text-amber-700">50/50: ₦5k OR reported!</span>
                    </button>
                    <button onclick="GameApp.resolveShiftCrisis('client', 'delay')" class="w-full py-2 px-3 bg-white hover:bg-amber-100 border border-amber-300 rounded-xl text-left font-bold text-xs text-amber-900 transition flex items-center justify-between">
                        <span>⏳ Tell him system is down (Delay)</span>
                        <span class="text-[10px] text-rose-700">Customer drama</span>
                    </button>
                </div>
            `;
        } else if (type === 'pee') {
            crisisBox.className = "rounded-2xl p-4 border bg-sky-50 border-sky-200 text-sky-950 animate-fade-up space-y-2.5";
            crisisBox.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="text-xl">🚽</span>
                    <div>
                        <h4 class="font-extrabold text-xs text-sky-900">Nature is Calling Loudly!</h4>
                        <p class="text-[11px] text-sky-800">Your stomach is rumbling from spicy breakfast street food! You desperately need the restroom.</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button onclick="GameApp.resolveShiftCrisis('pee', 'go')" class="py-2.5 px-3 bg-sky-600 hover:bg-sky-500 text-white rounded-xl font-bold text-xs transition active:scale-95 text-center">
                        🏃 Rush to Restroom (5s)
                    </button>
                    <button onclick="GameApp.resolveShiftCrisis('pee', 'hold')" class="py-2.5 px-3 bg-white hover:bg-sky-100 border border-sky-300 text-sky-900 rounded-xl font-bold text-xs transition active:scale-95 text-center">
                        😣 Hold It Like a Soldier
                    </button>
                </div>
            `;
        } else if (type === 'nepa') {
            this.shiftState.powerOn = false;
            crisisBox.className = "rounded-2xl p-4 border bg-rose-50 border-rose-300 text-rose-950 animate-fade-up space-y-2.5";
            crisisBox.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="text-xl">⚡</span>
                    <div>
                        <h4 class="font-extrabold text-xs text-rose-900">NEPA Blackout – Work Halted!</h4>
                        <p class="text-[11px] text-rose-800">Power just went out! Desktops are dead and ACs turned off. Productivity frozen.</p>
                    </div>
                </div>
                <div class="space-y-1.5 pt-1">
                    <button onclick="GameApp.resolveShiftCrisis('nepa', 'generator')" class="w-full py-2.5 px-3 bg-amber-500 hover:bg-amber-400 text-white rounded-xl font-bold text-xs transition active:scale-95 text-center flex items-center justify-center gap-2">
                        <i class="fa-solid fa-gears"></i> Pull Mikano Generator Cord (+Boss Praise)
                    </button>
                    <button onclick="GameApp.resolveShiftCrisis('nepa', 'wait')" class="w-full py-2 px-3 bg-white hover:bg-rose-100 border border-rose-200 text-rose-900 rounded-xl font-bold text-xs transition active:scale-95 text-center">
                        🕯️ Wait in Dark with Rechargeable Fan
                    </button>
                </div>
            `;
        } else if (type === 'boss') {
            crisisBox.className = "rounded-2xl p-4 border bg-purple-50 border-purple-200 text-purple-950 animate-fade-up space-y-2.5";
            crisisBox.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="text-xl">👔</span>
                    <div>
                        <h4 class="font-extrabold text-xs text-purple-900">Surprise Inspection by Oga!</h4>
                        <p class="text-[11px] text-purple-800">The Managing Director is pacing down the aisle checking what everyone is doing.</p>
                    </div>
                </div>
                <div class="pt-1">
                    <button onclick="GameApp.resolveShiftCrisis('boss', 'busy')" class="w-full py-2.5 px-3 bg-purple-600 hover:bg-purple-500 text-white rounded-xl font-bold text-xs transition active:scale-95 text-center flex items-center justify-center gap-2">
                        <i class="fa-solid fa-keyboard"></i> Type Spreadsheets Furiously & Greet "Good afternoon Sir!"
                    </button>
                </div>
            `;
        }
    },

    resolveShiftCrisis(type, choice) {
        const crisisBox = document.getElementById('shiftCrisisCard');
        if (crisisBox) crisisBox.classList.add('hidden');

        const s = this.shiftState;

        if (type === 'client') {
            if (choice === 'polite') {
                s.tips += 3500;
                this.playSfx('money');
                this.notify("Alhaji Musa smiled: 'You be good boy!' Handed you ₦3,500 tip.", 'success');
            } else if (choice === 'kola') {
                if (Math.random() > 0.45) {
                    s.tips += 5000;
                    this.playSfx('money');
                    this.notify("Client slipped ₦5,000 kola nut cash into your drawer!", 'success');
                } else {
                    s.penalties += 2000;
                    this.playSfx('loss');
                    this.notify("Client yelled and reported you to Oga! ₦2,000 deducted from shift pay.", 'error');
                }
            } else {
                s.penalties += 1000;
                this.notify("Client grumbled loudly and walked out. Customer feedback score reduced.", 'info');
            }
        } else if (type === 'pee') {
            if (choice === 'go') {
                s.isAtDesk = false;
                s.bladderLevel = 0;
                document.getElementById('shiftStatusText').textContent = '🚽 In the restroom relieving yourself...';
                this.notify("You dashed to the restroom. Huge relief! (+10 happiness).", 'info');
                setTimeout(() => {
                    s.isAtDesk = true;
                    document.getElementById('shiftStatusText').textContent = '💼 Normal Duties: Attending to office files...';
                }, 4000);
            } else {
                this.notify("You held it in painfully. Sweating profusely at your desk!", 'error');
            }
        } else if (type === 'nepa') {
            s.powerOn = true;
            if (choice === 'generator') {
                s.bonuses += 2500;
                this.playSfx('win');
                this.notify("Generator roaring! Light restored. Oga gave you +₦2,500 initiative bonus!", 'success');
            } else {
                this.notify("Switched to rechargeable light. Working at half speed.", 'info');
            }
        } else if (type === 'boss') {
            this.shiftState.bossCrisisResolved = true;
            if (s.isAtDesk) {
                s.bonuses += 3000;
                this.playSfx('win');
                this.notify("Oga nodded approvingly: 'Keep it up!' Performance bonus +₦3,000 unlocked!", 'success');
            } else {
                s.penalties += 2000;
                this.playSfx('loss');
                this.notify("Oga saw your empty desk: 'Where is this staff?!' ₦2,000 docked for absent desk.", 'error');
            }
        }

        this.updateShiftUI();
    },

    shiftDoWorkTask() {
        if (!this.shiftState.active || !this.shiftState.powerOn) return;
        this.shiftState.elapsed += 2; // Speeds up progress
        this.playSfx('click');
        this.notify("Typing vigorously! Shift accelerated by 2 seconds.", 'info');
        this.updateShiftUI();
    },

    shiftGoBathroom() {
        if (!this.shiftState.active) return;
        this.resolveShiftCrisis('pee', 'go');
    },

    abandonShiftPrompt() {
        const confirmed = confirm("⚠️ ARE YOU SURE YOU WANT TO SNEAK OUT EARLY?\n\nOga and the security will catch you at the gate!\nYou will LOSE your daily salary and get issued a formal disciplinary query (-12 Street Cred)!");
        if (!confirmed) return;

        clearInterval(this.shiftState.timer);
        this.shiftState.active = false;

        const modal = document.getElementById('workShiftModal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }

        const formData = new FormData();
        formData.append('progress', this.shiftState.progress);

        fetch('api/character.php?action=abandon_shift', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                this.playSfx('loss');
                this.notify(data.message, 'error');
                this.fetchCharacter();
            })
            .catch(() => this.notify("Left work early.", "error"));
    },

    async completeWorkShift() {
        this.shiftState.active = false;
        clearInterval(this.shiftState.timer);

        const modal = document.getElementById('workShiftModal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }

        const s = this.shiftState;
        const formData = new FormData();
        formData.append('tips', s.tips);
        formData.append('bonuses', s.bonuses);
        formData.append('penalties', s.penalties);

        try {
            const res = await fetch('api/character.php?action=finish_shift', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('money');
                
                // Show summary receipt modal
                const sumModal = document.getElementById('shiftSummaryModal');
                document.getElementById('sumBasePay').textContent = this.formatNaira(s.baseSalary);
                document.getElementById('sumTips').textContent = `+${this.formatNaira(s.tips)}`;
                document.getElementById('sumBonus').textContent = `+${this.formatNaira(s.bonuses)}`;
                document.getElementById('sumPenalties').textContent = `-${this.formatNaira(s.penalties)}`;
                document.getElementById('sumNetPay').textContent = this.formatNaira(data.final_pay);

                if (sumModal) {
                    sumModal.classList.remove('hidden');
                    sumModal.classList.add('flex');
                }

                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Failed to close shift', 'error');
            }
        } catch(e) {
            this.notify("Error closing shift.", "error");
        }
    },

    closeShiftSummary() {
        const sumModal = document.getElementById('shiftSummaryModal');
        if (sumModal) {
            sumModal.classList.add('hidden');
            sumModal.classList.remove('flex');
        }
        this.notify("Shift ended! Heading home for the evening.", 'success');
    },

    async advanceDay() {
        try {
            const res = await fetch('api/character.php?action=advance_day', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                this.playSfx('money');
                this.notify(data.message, 'success');
                await this.fetchCharacter();
                this.checkForRandomEvent();
            } else {
                this.notify(data.error, 'error');
            }
        } catch (err) {
            this.notify('Failed to advance day.', 'error');
        }
    },

    async sleepRest() {
        try {
            const res = await fetch('api/character.php?action=sleep', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                this.playSfx('click');
                this.notify(data.message, 'success');
                await this.fetchCharacter();
            }
        } catch (err) {
            this.notify('Rest error.', 'error');
        }
    },

    // --- JOBS ---
    async loadJobs() {
        const res = await fetch('api/jobs.php?action=list');
        const data = await res.json();
        const container = document.getElementById('jobsListContainer');
        if (!container || !data.success) return;

        container.innerHTML = data.jobs.map(j => {
            const isCurrent = this.character.current_job_id == j.id;
            return `
                <div class="bg-white border ${isCurrent ? 'border-emerald-600 ring-2 ring-emerald-600/20' : 'border-slate-200'} rounded-2xl p-5 flex flex-col justify-between shadow-sm transition hover:shadow-md">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">${j.category}</span>
                            <span class="text-xs font-mono font-bold text-emerald-700">${this.formatNaira(j.daily_salary)}/day</span>
                        </div>
                        <h4 class="font-bold text-base text-slate-900 mb-1">${j.title}</h4>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">${j.description}</p>
                        <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-600 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span><i class="fa-solid fa-graduation-cap text-slate-500 mr-1"></i> ${j.required_education}</span>
                            <span><i class="fa-solid fa-brain text-slate-500 mr-1"></i> IQ ${j.required_intelligence}</span>
                            <span><i class="fa-solid fa-bolt text-amber-500 mr-1"></i> ${j.energy_cost}% Energy</span>
                        </div>
                    </div>
                    <div>
                        ${isCurrent ? `
                            <div class="flex gap-2">
                                <button onclick="GameApp.goToWork()" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs shadow-sm transition active:scale-95">
                                    <i class="fa-solid fa-briefcase mr-1"></i> Work Shift
                                </button>
                                <button onclick="GameApp.resignJob()" class="px-3 py-2.5 bg-slate-100 hover:bg-rose-50 text-rose-700 rounded-xl font-bold text-xs border border-slate-200 transition active:scale-95">
                                    Resign
                                </button>
                            </div>
                        ` : `
                            <button onclick="GameApp.applyJob(${j.id})" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold text-xs shadow-sm transition active:scale-95">
                                Apply for Job
                            </button>
                        `}
                    </div>
                </div>
            `;
        }).join('');
    },

    async applyJob(jobId) {
        const formData = new FormData();
        formData.append('job_id', jobId);
        const res = await fetch('api/jobs.php?action=apply', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.playSfx('win');
            this.notify(data.message, 'success');
            await this.fetchCharacter();
            this.loadJobs();
        } else {
            this.playSfx('loss');
            this.notify(data.error, 'error');
        }
    },

    async resignJob() {
        if (!confirm('Are you sure you want to resign from your position?')) return;
        const res = await fetch('api/jobs.php?action=resign', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            this.notify(data.message, 'info');
            await this.fetchCharacter();
            this.loadJobs();
        }
    },

    // --- HUSTLES ---
    async loadHustles() {
        const res = await fetch('api/hustles.php?action=list');
        const data = await res.json();
        const container = document.getElementById('hustlesListContainer');
        if (!container || !data.success) return;

        container.innerHTML = data.hustles.map(h => `
            <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-sm transition hover:shadow-md">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-lg mb-3">
                        <i class="fa-solid ${h.icon}"></i>
                    </div>
                    <h4 class="font-bold text-base text-slate-900 mb-1">${h.title}</h4>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4">${h.desc}</p>
                    <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-600 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <div>Capital: <strong>${this.formatNaira(h.min_cash)}</strong></div>
                        <div>Req Cred: <strong>${h.min_cred}</strong></div>
                        <div>Energy: <strong>${h.energy}%</strong></div>
                    </div>
                </div>
                <button onclick="GameApp.performHustle('${h.id}')" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95">
                    Run Hustle
                </button>
            </div>
        `).join('');
    },

    async performHustle(hustleId) {
        const formData = new FormData();
        formData.append('hustle_id', hustleId);
        const res = await fetch('api/hustles.php?action=perform', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            if (data.earned > 0) this.playSfx('money');
            else this.playSfx('loss');
            this.notify(data.message, data.earned >= 0 ? 'success' : 'error');
            await this.fetchCharacter();
        } else {
            this.playSfx('loss');
            this.notify(data.error, 'error');
        }
    },

    // --- REAL ESTATE ---
    async loadRealEstate() {
        const resAll = await fetch('api/realestate.php?action=list');
        const dataAll = await resAll.json();
        const resMine = await fetch('api/realestate.php?action=my_properties');
        const dataMine = await resMine.json();

        const containerMarket = document.getElementById('propertiesMarketContainer');
        const containerOwned = document.getElementById('propertiesOwnedContainer');

        if (containerOwned) {
            if (!dataMine.properties || dataMine.properties.length === 0) {
                containerOwned.innerHTML = `<p class="col-span-full text-xs text-slate-400 py-6 text-center">You don't own any real estate properties yet. Buy one below to collect daily rent.</p>`;
            } else {
                containerOwned.innerHTML = dataMine.properties.map(p => `
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">${p.district}</span>
                                <span class="text-xs font-mono font-bold text-emerald-700">${p.is_rented_out ? 'Yield: ' + this.formatNaira(p.daily_rent_yield) + '/day' : 'Occupied'}</span>
                            </div>
                            <h4 class="font-bold text-base text-slate-900 mb-1">${p.name}</h4>
                            <p class="text-xs text-slate-500 mb-3">${p.description}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="GameApp.toggleRentProperty(${p.ownership_id})" class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-800 rounded-xl transition active:scale-95">
                                ${p.is_rented_out ? 'Move In' : 'Rent Out for Income'}
                            </button>
                            <button onclick="GameApp.sellProperty(${p.ownership_id})" class="px-3 py-2 bg-slate-100 hover:bg-rose-50 text-rose-700 text-xs font-bold rounded-xl border border-slate-200 transition active:scale-95">
                                Sell
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        }

        if (containerMarket && dataAll.success) {
            containerMarket.innerHTML = dataAll.properties.map(p => `
                <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-sm transition hover:shadow-md">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">${p.district}</span>
                            <span class="text-xs font-mono font-bold text-emerald-700">${this.formatNaira(p.price)}</span>
                        </div>
                        <h4 class="font-bold text-base text-slate-900 mb-1">${p.name}</h4>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">${p.description}</p>
                        <div class="text-[11px] text-slate-600 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100 flex justify-between">
                            <span>Daily Rent: <strong>${this.formatNaira(p.daily_rent_yield)}</strong></span>
                            <span>Prestige: <strong>+${p.prestige_points}</strong></span>
                        </div>
                    </div>
                    <button onclick="GameApp.buyProperty(${p.id})" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs transition active:scale-95 shadow-sm">
                        Purchase Property
                    </button>
                </div>
            `).join('');
        }
    },

    async buyProperty(id) {
        const formData = new FormData();
        formData.append('property_id', id);
        const res = await fetch('api/realestate.php?action=buy', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.playSfx('win');
            this.notify(data.message, 'success');
            await this.fetchCharacter();
            this.loadRealEstate();
        } else {
            this.playSfx('loss');
            this.notify(data.error, 'error');
        }
    },

    async toggleRentProperty(id) {
        const formData = new FormData();
        formData.append('ownership_id', id);
        const res = await fetch('api/realestate.php?action=toggle_rent', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.notify(data.message, 'info');
            this.loadRealEstate();
        }
    },

    async sellProperty(id) {
        if (!confirm('Are you sure you want to sell this property?')) return;
        const formData = new FormData();
        formData.append('ownership_id', id);
        const res = await fetch('api/realestate.php?action=sell', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.playSfx('money');
            this.notify(data.message, 'success');
            await this.fetchCharacter();
            this.loadRealEstate();
        } else {
            this.notify(data.error, 'error');
        }
    },

    // --- VEHICLES ---
    async loadVehicles() {
        const resAll = await fetch('api/vehicles.php?action=list');
        const dataAll = await resAll.json();
        const resMine = await fetch('api/vehicles.php?action=my_vehicles');
        const dataMine = await resMine.json();

        const containerMarket = document.getElementById('vehiclesMarketContainer');
        const containerOwned = document.getElementById('vehiclesOwnedContainer');

        if (containerOwned) {
            if (!dataMine.vehicles || dataMine.vehicles.length === 0) {
                containerOwned.innerHTML = `<p class="col-span-full text-xs text-slate-400 py-6 text-center">Garage empty. Buy your first vehicle below.</p>`;
            } else {
                containerOwned.innerHTML = dataMine.vehicles.map(v => `
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">${v.brand}</span>
                                <span class="text-xs font-mono font-bold text-amber-700">Upkeep: ${this.formatNaira(v.daily_upkeep)}/day</span>
                            </div>
                            <h4 class="font-bold text-base text-slate-900 mb-1">${v.name}</h4>
                            <p class="text-xs text-slate-500 mb-4">${v.description}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="GameApp.cruiseVehicle()" class="flex-1 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold text-xs transition active:scale-95 shadow-sm">
                                <i class="fa-solid fa-car-side mr-1"></i> Cruise City
                            </button>
                            <button onclick="GameApp.sellVehicle(${v.ownership_id})" class="px-3 py-2 bg-slate-100 hover:bg-rose-50 text-rose-700 text-xs font-bold rounded-xl border border-slate-200 transition active:scale-95">
                                Sell
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        }

        if (containerMarket && dataAll.success) {
            containerMarket.innerHTML = dataAll.vehicles.map(v => `
                <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-sm transition hover:shadow-md">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">${v.brand}</span>
                            <span class="text-xs font-mono font-bold text-emerald-700">${this.formatNaira(v.price)}</span>
                        </div>
                        <h4 class="font-bold text-base text-slate-900 mb-1">${v.name}</h4>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">${v.description}</p>
                        <div class="flex justify-between text-[11px] text-slate-600 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span>Cred Bonus: <strong>+${v.cred_bonus}</strong></span>
                            <span>Daily Upkeep: <strong>${this.formatNaira(v.daily_upkeep)}</strong></span>
                        </div>
                    </div>
                    <button onclick="GameApp.buyVehicle(${v.id})" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs transition active:scale-95 shadow-sm">
                        Purchase Vehicle
                    </button>
                </div>
            `).join('');
        }
    },

    async buyVehicle(id) {
        const formData = new FormData();
        formData.append('vehicle_id', id);
        const res = await fetch('api/vehicles.php?action=buy', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.playSfx('win');
            this.notify(data.message, 'success');
            await this.fetchCharacter();
            this.loadVehicles();
        } else {
            this.playSfx('loss');
            this.notify(data.error, 'error');
        }
    },

    async cruiseVehicle() {
        const res = await fetch('api/vehicles.php?action=cruise', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            this.playSfx('win');
            this.notify(data.message, 'success');
            await this.fetchCharacter();
        } else {
            this.playSfx('loss');
            this.notify(data.error, 'error');
        }
    },

    async sellVehicle(id) {
        if (!confirm('Are you sure you want to sell this car?')) return;
        const formData = new FormData();
        formData.append('ownership_id', id);
        const res = await fetch('api/vehicles.php?action=sell', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.playSfx('money');
            this.notify(data.message, 'success');
            await this.fetchCharacter();
            this.loadVehicles();
        } else {
            this.notify(data.error, 'error');
        }
    },

    // --- LIFESTYLE ---
    async loadLifestyle() {
        const res = await fetch('api/lifestyle.php?action=list');
        const data = await res.json();
        const container = document.getElementById('lifestyleListContainer');
        if (!container || !data.success) return;

        container.innerHTML = data.activities.map(a => `
            <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-sm transition hover:shadow-md">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-lg mb-3">
                        <i class="fa-solid ${a.icon}"></i>
                    </div>
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="font-bold text-base text-slate-900">${a.name}</h4>
                        <span class="text-xs font-mono font-bold text-emerald-700">${this.formatNaira(a.cost)}</span>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4">${a.desc}</p>
                    <div class="flex items-center gap-3 text-[11px] text-slate-600 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span><i class="fa-solid fa-face-smile text-emerald-600 mr-1"></i> +${a.hap_gain} Happiness</span>
                        <span><i class="fa-solid fa-bolt text-amber-500 mr-1"></i> -${a.energy}% Energy</span>
                    </div>
                </div>
                <button onclick="GameApp.performLifestyle('${a.id}')" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold text-xs transition active:scale-95 shadow-sm">
                    Enjoy Activity
                </button>
            </div>
        `).join('');
    },

    async performLifestyle(id) {
        const formData = new FormData();
        formData.append('activity_id', id);
        const res = await fetch('api/lifestyle.php?action=perform', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.playSfx('win');
            this.notify(data.message, 'success');
            await this.fetchCharacter();
        } else {
            this.playSfx('loss');
            this.notify(data.error, 'error');
        }
    },

    // --- BANK ---
    async loadBank() {
        const res = await fetch('api/bank.php?action=status');
        const data = await res.json();
        if (!data.success) return;

        document.getElementById('bankDisplayCash').textContent = this.formatNaira(data.cash);
        document.getElementById('bankDisplaySavings').textContent = this.formatNaira(data.bank);
        document.getElementById('bankDisplayLoan').textContent = this.formatNaira(data.loan_balance);
        document.getElementById('bankMaxLoanLimit').textContent = this.formatNaira(data.max_loan_limit);
    },

    async bankDeposit() {
        const amount = document.getElementById('bankDepositInput').value;
        const formData = new FormData();
        formData.append('amount', amount);
        const res = await fetch('api/bank.php?action=deposit', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.playSfx('money');
            this.notify(data.message, 'success');
            document.getElementById('bankDepositInput').value = '';
            await this.fetchCharacter();
            this.loadBank();
        } else {
            this.notify(data.error, 'error');
        }
    },

    async bankWithdraw() {
        const amount = document.getElementById('bankWithdrawInput').value;
        const formData = new FormData();
        formData.append('amount', amount);
        const res = await fetch('api/bank.php?action=withdraw', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.playSfx('money');
            this.notify(data.message, 'success');
            document.getElementById('bankWithdrawInput').value = '';
            await this.fetchCharacter();
            this.loadBank();
        } else {
            this.notify(data.error, 'error');
        }
    },

    async takeBankLoan() {
        const amount = document.getElementById('bankLoanInput').value;
        const formData = new FormData();
        formData.append('amount', amount);
        const res = await fetch('api/bank.php?action=take_loan', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.playSfx('money');
            this.notify(data.message, 'success');
            document.getElementById('bankLoanInput').value = '';
            await this.fetchCharacter();
            this.loadBank();
        } else {
            this.notify(data.error, 'error');
        }
    },

    async repayBankLoan() {
        const amount = document.getElementById('bankRepayInput').value;
        const formData = new FormData();
        formData.append('amount', amount);
        const res = await fetch('api/bank.php?action=repay_loan', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.playSfx('money');
            this.notify(data.message, 'success');
            document.getElementById('bankRepayInput').value = '';
            await this.fetchCharacter();
            this.loadBank();
        } else {
            this.notify(data.error, 'error');
        }
    },

    // --- CASINO ---
    async playSportsBet(risk) {
        const stake = document.getElementById('betStakeInput').value;
        const formData = new FormData();
        formData.append('stake', stake);
        formData.append('risk', risk);
        const res = await fetch('api/casino.php?action=bet', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            if (data.result === 'win') this.playSfx('win');
            else this.playSfx('loss');
            this.notify(data.message, data.result === 'win' ? 'success' : 'error');
            await this.fetchCharacter();
        } else {
            this.notify(data.error, 'error');
        }
    },

    async rollDiceGame(pred) {
        const stake = document.getElementById('diceStakeInput').value;
        const formData = new FormData();
        formData.append('stake', stake);
        formData.append('prediction', pred);
        const res = await fetch('api/casino.php?action=dice', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            if (data.result === 'win') this.playSfx('win');
            else this.playSfx('loss');
            this.notify(data.message, data.result === 'win' ? 'success' : 'error');
            await this.fetchCharacter();
        } else {
            this.notify(data.error, 'error');
        }
    },

    // --- LEADERBOARD ---
    async loadLeaderboard() {
        const res = await fetch('api/leaderboard.php');
        const data = await res.json();
        const containerRichest = document.getElementById('leaderboardRichest');
        const containerCred = document.getElementById('leaderboardCred');

        if (containerRichest && data.richest) {
            containerRichest.innerHTML = data.richest.map((p, idx) => `
                <div class="flex items-center justify-between py-2 border-b border-slate-100 text-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="w-5 font-bold text-slate-400">#${idx + 1}</span>
                        <div>
                            <span class="font-bold text-slate-900">${p.full_name}</span>
                            <span class="text-slate-400 block text-[10px]">${p.district} • ${p.job_title || 'Citizen'}</span>
                        </div>
                    </div>
                    <span class="font-mono font-bold text-emerald-700">${this.formatNaira(p.calculated_net_worth)}</span>
                </div>
            `).join('');
        }

        if (containerCred && data.street_kings) {
            containerCred.innerHTML = data.street_kings.map((p, idx) => `
                <div class="flex items-center justify-between py-2 border-b border-slate-100 text-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="w-5 font-bold text-slate-400">#${idx + 1}</span>
                        <div>
                            <span class="font-bold text-slate-900">${p.full_name}</span>
                            <span class="text-slate-400 block text-[10px]">${p.district}</span>
                        </div>
                    </div>
                    <span class="font-mono font-bold text-amber-700">${p.street_cred} Cred</span>
                </div>
            `).join('');
        }
    },

    // --- RANDOM LIFE ENCOUNTER POPUP ---
    async checkForRandomEvent() {
        try {
            const res = await fetch('api/events.php?action=random');
            const data = await res.json();
            if (data.success && data.has_event) {
                this.pendingEvent = data.event;
                this.showEventModal(data.event);
            }
        } catch (e) {}
    },

    showEventModal(ev) {
        document.getElementById('eventTitle').textContent = ev.title;
        document.getElementById('eventDesc').textContent = ev.description;
        document.getElementById('eventChoiceA').textContent = ev.option_a_label;
        document.getElementById('eventChoiceB').textContent = ev.option_b_label;

        const modal = document.getElementById('randomEventModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    },

    async chooseEventOption(choice) {
        if (!this.pendingEvent) return;
        const formData = new FormData();
        formData.append('event_id', this.pendingEvent.id);
        formData.append('choice', choice);

        const res = await fetch('api/events.php?action=resolve', { method: 'POST', body: formData });
        const data = await res.json();

        document.getElementById('randomEventModal').classList.add('hidden');
        document.getElementById('randomEventModal').classList.remove('flex');

        if (data.success) {
            this.playSfx(data.outcome.cash >= 0 ? 'win' : 'loss');
            this.notify(data.message, data.outcome.cash >= 0 ? 'success' : 'error');
            await this.fetchCharacter();
        }
        this.pendingEvent = null;
    },

    // --- BITMOJI 3D WARDROBE STUDIO ---
    wardrobeStudio: null,

    openWardrobeModal() {
        const modal = document.getElementById('gameWardrobeModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        setTimeout(() => {
            const container = document.getElementById('gameBitmojiContainer');
            if (!this.wardrobeStudio && container) {
                this.wardrobeStudio = new Avatar3DStudio('gameBitmojiContainer', {
                    width: container.clientWidth || 320,
                    height: 400,
                    showPlatform: true
                });
            } else if (this.wardrobeStudio) {
                this.wardrobeStudio.resize();
            }

            // Sync with current character state
            if (this.wardrobeStudio && this.character) {
                let cfg = null;
                if (this.character.avatar && typeof this.character.avatar === 'string' && this.character.avatar.trim().startsWith('{')) {
                    try { cfg = JSON.parse(this.character.avatar); } catch(e){}
                }
                let activeCharId = 'tunde';
                let activeOutfit = this.character.outfit || 'hoodie';

                if (cfg && cfg.characterId) {
                    activeCharId = cfg.characterId;
                } else if (this.character.full_name) {
                    const nameLower = this.character.full_name.toLowerCase();
                    const roster = ['tunde', 'emeka', 'chidi', 'farouk', 'ibrahim', 'segun', 'zainab', 'blessing', 'ngozi'];
                    for (const r of roster) {
                        if (nameLower.includes(r)) { activeCharId = r; break; }
                    }
                }
                if (cfg && cfg.outfit) activeOutfit = cfg.outfit;

                if (cfg) {
                    this.wardrobeStudio.loadConfig(cfg);
                } else {
                    this.wardrobeStudio.setCharacter(activeCharId);
                    this.wardrobeStudio.setOutfit(activeOutfit);
                }

                // Highlight active character card and outfit button in wardrobe modal
                document.querySelectorAll('.wardrobe-char-card').forEach(btn => {
                    const onclickAttr = btn.getAttribute('onclick') || '';
                    if (onclickAttr.includes(`'${activeCharId}'`)) {
                        btn.className = "wardrobe-char-card p-2 rounded-2xl border-2 border-purple-600 bg-purple-50 text-center transition transform active:scale-95 group";
                    } else {
                        btn.className = "wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group";
                    }
                });

                document.querySelectorAll('.wardrobe-outfit-btn').forEach(btn => {
                    const onclickAttr = btn.getAttribute('onclick') || '';
                    if (onclickAttr.includes(`'${activeOutfit}'`)) {
                        btn.classList.add('border-purple-600', 'bg-purple-50', 'text-purple-950');
                        btn.classList.remove('border-slate-200', 'bg-white');
                    } else {
                        btn.classList.remove('border-purple-600', 'bg-purple-50', 'text-purple-950');
                        btn.classList.add('border-slate-200', 'bg-white');
                    }
                });
            }
        }, 60);
    },

    closeWardrobeModal() {
        const modal = document.getElementById('gameWardrobeModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    },

    turnWardrobeAvatar() {
        if (this.wardrobeStudio) {
            this.wardrobeStudio.turnAround();
        }
    },

    switchWardrobeTab(tabId) {
        ['face', 'outfit', 'kicks'].forEach(t => {
            const panel = document.getElementById(`gameWardrobeTab-${t}`);
            const btn = document.getElementById(`gameTabBtn-${t}`);
            if (t === tabId) {
                if (panel) panel.classList.remove('hidden');
                if (btn) btn.className = "flex-1 py-2 rounded-xl bg-white text-purple-950 font-bold shadow-sm transition flex items-center justify-center gap-1.5 active:scale-95";
            } else {
                if (panel) panel.classList.add('hidden');
                if (btn) btn.className = "flex-1 py-2 rounded-xl text-slate-600 hover:text-slate-900 font-bold transition flex items-center justify-center gap-1.5 active:scale-95";
            }
        });
    },

    setWardrobeCharacter(charId) {
        if (this.wardrobeStudio) {
            this.wardrobeStudio.setCharacter(charId);
        }
        document.querySelectorAll('.wardrobe-char-card').forEach(btn => {
            const onclickAttr = btn.getAttribute('onclick') || '';
            if (onclickAttr.includes(`'${charId}'`)) {
                btn.className = "wardrobe-char-card p-2 rounded-2xl border-2 border-purple-600 bg-purple-50 text-center transition transform active:scale-95 group";
            } else {
                btn.className = "wardrobe-char-card p-2 rounded-2xl border border-slate-200 bg-white text-center transition transform hover:border-slate-300 active:scale-95 group";
            }
        });
    },

    setWardrobeOutfit(outfitKey) {
        if (this.wardrobeStudio) {
            this.wardrobeStudio.setOutfit(outfitKey);
        }
        document.querySelectorAll('.wardrobe-outfit-btn').forEach(btn => {
            const onclickAttr = btn.getAttribute('onclick') || '';
            if (onclickAttr.includes(`'${outfitKey}'`)) {
                btn.classList.add('border-purple-600', 'bg-purple-50', 'text-purple-950');
                btn.classList.remove('border-slate-200', 'bg-white');
            } else {
                btn.classList.remove('border-purple-600', 'bg-purple-50', 'text-purple-950');
                btn.classList.add('border-slate-200', 'bg-white');
            }
        });
    },

    setWardrobeSkin(hex) {
        if (this.wardrobeStudio) {
            this.wardrobeStudio.setSkin(hex);
        }
    },

    setWardrobeHair(style) {
        if (this.wardrobeStudio) {
            this.wardrobeStudio.setHair(style);
        }
    },

    setWardrobeTop(type) {
        if (this.wardrobeStudio) {
            this.wardrobeStudio.setTop(type);
        }
    },

    setWardrobeBottom(type) {
        if (this.wardrobeStudio) {
            this.wardrobeStudio.setBottom(type);
        }
    },

    setWardrobeShoes(type) {
        if (this.wardrobeStudio) {
            this.wardrobeStudio.setShoes(type);
        }
    },

    setWardrobeAccessory(type) {
        if (this.wardrobeStudio) {
            this.wardrobeStudio.setAccessory(type);
        }
    },

    async saveWardrobeLook() {
        if (!this.wardrobeStudio) return;
        const cfg = this.wardrobeStudio.getConfig();

        const formData = new FormData();
        formData.append('avatar_config', JSON.stringify(cfg));
        formData.append('skin_tone', cfg.skinTone || '#704225');
        formData.append('hair_style', cfg.hairStyle || 'fade');
        formData.append('outfit', cfg.outfit || cfg.topType || 'hoodie');

        try {
            const res = await fetch('api/character.php?action=update_looks', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('win');
                this.notify('Wardrobe updated! Your 3D Bitmoji look is refreshed.', 'success');
                this.closeWardrobeModal();
                await this.fetchCharacter();
                if (window.World3D) {
                    World3D.updateScene();
                }
            } else {
                this.notify(data.error || 'Failed to update wardrobe.', 'error');
            }
        } catch (e) {
            this.notify('Connection error updating wardrobe.', 'error');
        }
    },

    async logout() {
        const res = await fetch('api/auth.php?action=logout');
        const data = await res.json();
        window.location.href = data.redirect || 'index.php';
    },

    // ====================================================
    // WEATHER & FLOOD SYSTEM
    // ====================================================
    weatherState: { isRaining: false, floodLevel: 0, fareMultiplier: 1 },

    async checkWeather() {
        try {
            const res = await fetch('api/street.php?action=flash_flood');
            const data = await res.json();
            if (!data.success) return;
            
            this.weatherState = {
                isRaining: data.is_raining,
                floodLevel: data.flood_level,
                fareMultiplier: data.danfo_fare_multiplier || 1
            };
            
            const banner = document.getElementById('weatherBanner');
            const icon = document.getElementById('weatherIcon');
            const statusTitle = document.getElementById('weatherStatusTitle');
            const statusDesc = document.getElementById('weatherStatusDesc');
            const floodBadge = document.getElementById('weatherFloodBadge');
            const fareDisplay = document.getElementById('danfoFareDisplay');
            
            if (data.is_raining) {
                if (banner) { banner.classList.remove('hidden'); }
                if (icon) icon.textContent = data.flood_level >= 2 ? '🌊' : '🌧️';
                if (statusTitle) statusTitle.textContent = data.flood_level >= 2 ? '⚠️ Flood Alert!' : '🌧️ Rain Falling - FCT';
                if (statusDesc) statusDesc.textContent = data.description;
                if (floodBadge) { floodBadge.textContent = `FLOOD LV.${data.flood_level}`; floodBadge.classList.toggle('hidden', data.flood_level === 0); }
                if (fareDisplay) fareDisplay.innerHTML = `Base Fare: <s>₦500</s> | <span class="text-rose-600 font-extrabold">RAIN FARE: ₦${500 * data.danfo_fare_multiplier}</span>`;
            } else {
                if (banner) banner.classList.add('hidden');
                if (icon) icon.textContent = '☀️';
                if (statusTitle) statusTitle.textContent = 'Clear Skies';
                if (statusDesc) statusDesc.textContent = 'Normal fares apply. No flood risk.';
                if (fareDisplay) fareDisplay.innerHTML = 'Base Fare: <strong>₦500</strong> | Rain: ₦1,000';
            }
        } catch(e) { console.error('Weather check failed', e); }
    },

    // ====================================================
    // TRANSPORT MECHANICS
    // ====================================================
    async playDanfoRush() {
        const formData = new FormData();
        formData.append('is_raining', this.weatherState.isRaining ? 1 : 0);
        try {
            const res = await fetch('api/street.php?action=danfo_rush', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx(data.won ? 'win' : 'loss');
                this.notify(data.message, data.won ? 'success' : 'error');
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Action failed', 'error');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    lastmaEventData: null,

    async triggerLastmaCheckpoint() {
        try {
            const res = await fetch('api/street.php?action=lastma_checkpoint', { method: 'POST' });
            const data = await res.json();
            if (!data.success) { this.notify(data.error || 'Error', 'error'); return; }
            
            if (data.event_triggered) {
                this.lastmaEventData = data;
                const modal = document.getElementById('lastmaModal');
                const violation = document.getElementById('lastmaViolation');
                const desc = document.getElementById('lastmaDesc');
                if (violation) violation.textContent = data.violation;
                if (desc) desc.textContent = 'LASTMA officer signals you to pull over. How do you handle this?';
                if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
            } else {
                this.notify('Checkpoint cleared! No violations detected. Safe travels!', 'success');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    async resolveLastma(choice) {
        const modal = document.getElementById('lastmaModal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
        const formData = new FormData();
        formData.append('negotiation_choice', choice);
        try {
            const res = await fetch('api/street.php?action=lastma_checkpoint', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                const isGood = (data.cash_change >= 0 && data.cred_change >= 0) || (data.cred_change > 0);
                this.playSfx(isGood ? 'win' : 'loss');
                this.notify(data.message, isGood ? 'success' : 'error');
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Failed', 'error');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    async takeOkadaRide() {
        try {
            const res = await fetch('api/street.php?action=okada_ride', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                const isBad = data.outcome === 'bad';
                this.playSfx(isBad ? 'loss' : 'win');
                this.notify(data.message, isBad ? 'error' : 'success');
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Error', 'error');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    async buyFromHawker(item) {
        const formData = new FormData();
        formData.append('item', item);
        try {
            const res = await fetch('api/street.php?action=goslow_hawker', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('money');
                this.notify(data.message, 'success');
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Error', 'error');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    async visitSuyaSpot() {
        try {
            const res = await fetch('api/street.php?action=suya_spot', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                this.playSfx('win');
                this.notify(data.message, 'success');
                if (data.contact_met) { setTimeout(() => this.notify('🤝 New Connection Made! Check your network.', 'info'), 1500); }
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Error', 'error');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    async dealWithAgbero(choice) {
        const formData = new FormData();
        formData.append('choice', choice);
        try {
            const res = await fetch('api/street.php?action=agbero_encounter', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx(data.cost > 5000 ? 'loss' : 'click');
                this.notify(data.message, data.cost > 5000 ? 'error' : 'success');
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Error', 'error');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    // ====================================================
    // SOCIAL LIFE MECHANICS
    // ====================================================
    async owambeAction(action2) {
        const formData = new FormData();
        formData.append('action2', action2);
        if (action2 === 'spray_money') {
            const amt = document.getElementById('sprayAmount')?.value || 10000;
            formData.append('spray_amount', amt);
        }
        try {
            const res = await fetch('api/street.php?action=owambe_party', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx(action2 === 'spray_money' ? 'money' : 'win');
                this.notify(data.message, 'success');
                if (data.connections_made) { setTimeout(() => this.notify('🤝 Oga Connection Unlocked! You know somebody now.', 'info'), 1500); }
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Error', 'error');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    async attendReligiousService(type) {
        const formData = new FormData();
        formData.append('type', type);
        try {
            const res = await fetch('api/street.php?action=religious_service', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('win');
                this.notify(data.message, 'success');
                if (data.job_tip) { setTimeout(() => this.notify(`💼 Job Tip from congregation: ${data.job_tip}`, 'info'), 2000); }
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Error', 'error');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    async ajoAction(action2) {
        const formData = new FormData();
        formData.append('action2', action2);
        try {
            const res = await fetch('api/street.php?action=ajo_contribution', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx(data.payout_amount > 0 ? 'money' : 'click');
                this.notify(data.message, 'success');
                const ajoStatus = document.getElementById('ajoStatus');
                if (ajoStatus && data.ajo_status) ajoStatus.textContent = data.ajo_status;
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Error', 'error');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    async nepaAction(action2) {
        const formData = new FormData();
        formData.append('action2', action2);
        try {
            const res = await fetch('api/street.php?action=nepa_roulette', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                const isGood = data.power_status === 'on';
                this.playSfx(isGood ? 'win' : 'loss');
                this.notify(data.message, isGood ? 'success' : 'error');
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Error', 'error');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    // ====================================================
    // STREET ECONOMY MECHANICS
    // ====================================================
    async playHagglingGame() {
        const market = document.getElementById('hagglingMarket')?.value || 'balogun';
        const item = document.getElementById('hagglingItem')?.value || 'phone';
        const pct = parseInt(document.getElementById('hagglingSlider')?.value || 60);
        const formData = new FormData();
        formData.append('market', market);
        formData.append('item_type', item);
        formData.append('offer_percentage', pct);
        try {
            const res = await fetch('api/street.php?action=market_haggle', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                const won = data.haggle_result === 'accepted';
                this.playSfx(won ? 'money' : 'loss');
                this.notify(data.message, won ? 'success' : 'error');
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Error', 'error');
            }
        } catch(e) { console.error('Street action failure:', e); this.notify(e.message || 'Action could not be completed', 'error'); }
    },

    checkSapaStatus() {
        if (!this.character) return;
        const cash = parseFloat(this.character.cash || 0);
        const bank = parseFloat(this.character.bank || 0);
        const total = cash + bank;
        let sapaLevel = 0;
        let sapaText = 'No Sapa';
        let advice = 'You are financially stable. Keep grinding!';
        
        if (total < 5000) { sapaLevel = 100; sapaText = '😩 MAXIMUM SAPA'; advice = 'Omo you don finish! Run POS or collect Ajo payout now!'; }
        else if (total < 20000) { sapaLevel = 85; sapaText = '😰 Deep Sapa'; advice = 'Deep sapa mode. Buy Gala and pure water. Premium spots locked!'; }
        else if (total < 50000) { sapaLevel = 65; sapaText = '😟 Moderate Sapa'; advice = 'Surviving. Avoid big spends. Run side hustle urgently.'; }
        else if (total < 150000) { sapaLevel = 40; sapaText = '😐 Mild Sapa'; advice = 'Manageable but tight. Keep a side gig running.'; }
        else if (total < 500000) { sapaLevel = 15; sapaText = '🙂 Normal'; advice = 'You are comfortable. Keep building!'; }
        else { sapaLevel = 0; sapaText = '💰 No Sapa'; advice = 'Oga! You are doing well. Stay grinding!'; }
        
        // Update displays
        const sapaBar = document.getElementById('sapaBar');
        const sapaLevelText = document.getElementById('sapaLevelText');
        const sapaAdvice = document.getElementById('sapaAdvice');
        const pillSapaVal = document.getElementById('pillSapaVal');
        const barSapa = document.getElementById('barSapa');
        const valSapa = document.getElementById('valSapa');
        
        if (sapaBar) sapaBar.style.width = sapaLevel + '%';
        if (sapaLevelText) sapaLevelText.textContent = sapaText;
        if (sapaAdvice) sapaAdvice.textContent = advice;
        if (pillSapaVal) pillSapaVal.textContent = sapaLevel > 50 ? '😩 SAPA' : 'OK';
        if (barSapa) barSapa.style.width = sapaLevel + '%';
        if (valSapa) valSapa.textContent = sapaText;
    },

    calculateAgentFees() {
        const sel = document.getElementById('rentPropertySelect');
        if (!sel) return;
        const yearlyRent = parseFloat(sel.value);
        const twoYears = yearlyRent * 2;
        const agent = yearlyRent * 0.1;
        const agreement = yearlyRent * 0.05;
        const total = twoYears + agent + agreement;
        
        const fmt = (n) => '₦' + n.toLocaleString('en-NG', {minimumFractionDigits:0});
        const calcRent = document.getElementById('calcRent');
        const calcAgent = document.getElementById('calcAgent');
        const calcAgreement = document.getElementById('calcAgreement');
        const calcTotal = document.getElementById('calcTotal');
        if (calcRent) calcRent.textContent = fmt(twoYears);
        if (calcAgent) calcAgent.textContent = fmt(agent);
        if (calcAgreement) calcAgreement.textContent = fmt(agreement);
        if (calcTotal) calcTotal.textContent = fmt(total);
    },

    checkConnections() {
        if (!this.character) return;
        const cred = parseInt(this.character.street_cred || 0);
        let msg = '';
        if (cred >= 80) msg = '👑 Oga level! You have deep connections. VIP clubs & government contracts open.';
        else if (cred >= 50) msg = '🤝 Good network. Mid-level jobs & lounge access available.';
        else if (cred >= 25) msg = '📞 Some contacts. Attend more parties & church to build stronger network.';
        else msg = '😶 No connections yet. Attend Owambe and religious services to build your network!';
        
        const display = document.getElementById('connectionsDisplay');
        if (display) display.textContent = msg;
        this.notify(msg, 'info');
    },

    checkDecemberEvent() {
        const month = new Date().getMonth() + 1; // 1-12
        const badge = document.getElementById('decemberBadge');
        const statusEl = document.getElementById('decemberStatus');
        if (month === 12) {
            if (badge) badge.classList.remove('hidden');
            if (statusEl) statusEl.textContent = '🎄 DECEMBER IS HERE! Prices are 2x. IJGB crowd everywhere. Concerts, suya joints packed. Go spray money!';
            this.notify('🎄 December IJGB Season Active! Economy inflated. Party hard!', 'info');
        } else {
            if (badge) badge.classList.add('hidden');
            const monthsUntilDec = month < 12 ? 12 - month : 12;
            if (statusEl) statusEl.textContent = `December IJGB season coming in ${monthsUntilDec} month(s). Economy will inflate. Save up for the season!`;
            this.notify(`December season in ${monthsUntilDec} month(s). Save up!`, 'info');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    GameApp.init();
});
