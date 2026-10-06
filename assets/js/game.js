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
        const savedTab = localStorage.getItem('abuja_active_tab') || 'overview';
        this.switchTab(savedTab);
        this.checkForRandomEvent();
        this.checkWeather();
        this.calculateAgentFees();
        this.checkActiveShift();

        // Initialize 3D Workplace & House World
        if (window.World3D) {
            World3D.init('world3d-container');
        }

        const savedView = localStorage.getItem('abuja_main_view') || 'home';
        this.switchMainView(savedView);
    },

    async fetchCharacter() {
        try {
            const token = localStorage.getItem('abuja_remember_token');
            const headers = {};
            if (token) headers['Authorization'] = `Bearer ${token}`;

            let res = await fetch('api/character.php?action=get', { headers });
            let data = await res.json();
            
            if (!data.success && data.error === 'Unauthorized' && token) {
                const restoreData = new FormData();
                restoreData.append('token', token);
                const restoreRes = await fetch('api/auth.php?action=restore_session', { method: 'POST', body: restoreData });
                const restoreJson = await restoreRes.json();
                
                if (restoreJson.success) {
                    res = await fetch('api/character.php?action=get', { headers });
                    data = await res.json();
                } else {
                    localStorage.removeItem('abuja_remember_token');
                }
            }

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

        // Top Floating Cash Pill
        const topCash = document.getElementById('topHudCash');
        if (topCash) topCash.textContent = this.formatNaira(c.cash);

        // 6 Mini Vitals Bars (Bottom Left Floating Widget)
        const bHunger = document.getElementById('barHungerMini');
        if (bHunger) bHunger.style.width = `${Math.min(100, Math.max(10, c.hunger || 85))}%`;
        const bEnergy = document.getElementById('barEnergyMini');
        if (bEnergy) bEnergy.style.width = `${Math.min(100, Math.max(5, c.energy || 50))}%`;
        const bFun = document.getElementById('barFunMini');
        if (bFun) bFun.style.width = `${Math.min(100, Math.max(5, c.happiness || 50))}%`;
        const bSocial = document.getElementById('barSocialMini');
        if (bSocial) bSocial.style.width = `${Math.min(100, Math.max(5, c.street_cred || 20))}%`;
        const bHygiene = document.getElementById('barHygieneMini');
        if (bHygiene) bHygiene.style.width = `${Math.min(100, Math.max(10, c.health || 90))}%`;
        const bBladder = document.getElementById('barBladderMini');
        if (bBladder) bBladder.style.width = `${Math.min(100, Math.max(5, c.bladder || 35))}%`;

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
        try { localStorage.setItem('abuja_active_tab', tabId); } catch(e){}

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
    // INTERACTIVE WORK SHIFT SIMULATOR ENGINE (8-10 REAL-TIME MINUTES)
    // ====================================================
    shiftState: {
        active: false,
        timer: null,
        totalSeconds: 480, // 8 real-time minutes (480 seconds)
        startedAtMs: 0,
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

    async checkActiveShift() {
        try {
            const res = await fetch('api/jobs.php?action=get_active_shift');
            const data = await res.json();
            if (data.success && data.has_active_shift && data.job) {
                if (data.is_completed) {
                    this.notify("Your 8-hour workday shift has completed! Collect your pay breakdown.", "success");
                    this.startWorkShift(data.job, data.job.daily_salary, data.duration, data.duration, data.started_at);
                    this.completeWorkShift();
                } else {
                    this.notify(`Resuming active shift (${Math.floor(data.remaining_seconds / 60)}m ${data.remaining_seconds % 60}s remaining)...`, "info");
                    this.startWorkShift(data.job, data.job.daily_salary, data.elapsed_seconds, data.duration, data.started_at);
                }
            }
        } catch(e) {}
    },

    async goToWork() {
        if (!this.character || !this.character.current_job_id) {
            this.notify("You don't have a job yet! Apply in the Careers tab.", 'error');
            this.switchTab('jobs');
            return;
        }

        // If shift already in progress, bring up modal
        if (this.shiftState.active) {
            const modal = document.getElementById('workShiftModal');
            if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
            return;
        }

        try {
            const res = await fetch('api/jobs.php?action=start_shift', { method: 'POST' });
            const data = await res.json();
            if (!data.success) {
                this.playSfx('loss');
                this.notify(data.error || 'Failed to start shift', 'error');
                return;
            }

            this.playSfx('click');
            this.startWorkShift(data.job, data.base_salary, 0, data.shift_duration_seconds || 480, data.started_at);
        } catch (e) {
            this.notify("Error connecting to workplace.", "error");
        }
    },

    getJobProfile(job) {
        const title = (job && job.title) ? job.title.toLowerCase() : '';
        const cat = (job && job.category) ? job.category.toLowerCase() : '';

        // 1. DRIVERS: Uber / Bolt Driver / Transport
        if (title.includes('bolt') || title.includes('uber') || title.includes('driver') || cat.includes('transport')) {
            return {
                id: 'bolt',
                title: job.title || 'Uber / Bolt Driver',
                workplaceTagline: 'Airport Road ⇄ Maitama ⇄ Central Business District',
                iconClass: 'fa-solid fa-car-side',
                iconBg: 'bg-emerald-600',
                normalStatus: '🚗 Driving on Abuja Expressways: Passenger on board...',
                workActionText: 'Accelerate Route (+Speed)',
                workActionIcon: 'fa-solid fa-gauge-high',
                reliefActionText: 'Pitstop at Filling Station',
                reliefActionIcon: 'fa-solid fa-gas-pump',
                reliefStatusText: '⛽ Pulled over at Conoil Airport Road filling station for quick relief...',
                workActionFeedback: 'Smooth acceleration along Airport Road! Shaved 10s off trip.',
                envLabels: {
                    powerOn: 'Road: Clear Highway 🟢',
                    powerOff: 'Traffic: Go-Slow 🛑',
                    bladderOk: 'Pitstop: OK',
                    bladderFull: 'Bladder: 🚨 URGENT',
                    bossNormal: 'Patrol: Road Clear 🛣️',
                    bossAlert: 'VIO: Checkpoint Ahead 👮'
                },
                crises: {
                    client: {
                        icon: '🧳',
                        title: 'Luggage Overload & VIP Passenger!',
                        desc: 'A wealthy diplomat at Transcorp Hilton has four heavy suitcases and wants a rush trip to Nnamdi Azikiwe Airport.',
                        options: [
                            { label: '🤝 Help load trunk briskly with VIP courtesy', tip: '+₦4,500 tip', action: 'polite', tipAmt: 4500, msg: "Passenger beamed: 'Thank you my brother!' Handed you ₦4,500 cash tip." },
                            { label: '⚡ Blast AC and take airport expressway toll', tip: '+₦3,000 tip', action: 'ac', tipAmt: 3000, msg: "Passenger enjoyed the chilled ride and left a 5-star ₦3,000 tip!" },
                            { label: '🙄 Grumble about trunk weight and ask for cash', tip: 'Risk bad rating', action: 'grumble', penaltyAmt: 1500, msg: "Passenger gave a 1-star rating! Platform deducted ₦1,500 dispute fee." }
                        ]
                    },
                    pee: {
                        icon: '⛽',
                        title: 'Urgent Pitstop along Umaru Musa Yar\'Adua Way!',
                        desc: 'Your bladder is bursting from cold morning water! Ahead is an NNPC Mega Station.',
                        options: [
                            { label: '⛽ Pull over at NNPC Station restroom (5s)', action: 'go' },
                            { label: '😣 Step on pedal and hold it to destination', action: 'hold' }
                        ]
                    },
                    nepa: {
                        icon: '🛑',
                        title: 'Abuja Go-Slow & Traffic Gridlock!',
                        desc: 'A broken-down cement truck has blocked the Garki Area 1 flyover. Trip progress is halted!',
                        options: [
                            { label: '🗺️ Take alternate inner-district service lane (+Skill bonus)', tip: '+₦2,500 surge', action: 'detour', bonusAmt: 2500, msg: "Expert navigation through backstreets! Traffic bypassed (+₦2,500 fare surge)." },
                            { label: '⏳ Sit patiently in traffic and wait it out', tip: 'Slow crawl', action: 'wait', msg: "Waited in the slow crawl until lane cleared. Resuming trip." }
                        ]
                    },
                    boss: {
                        icon: '👮',
                        title: 'VIO & Road Safety Checkpoint Ahead!',
                        desc: 'VIO officers in black and white uniforms are waving cars to the curb with inspection batons.',
                        options: [
                            { label: '📄 Present valid driver licence & vehicle papers', tip: '+₦2,000 bonus', action: 'papers', bonusAmt: 2000, msg: "Officer saluted: 'Safe journey!' Clear passage without delay (+₦2,000 road safety bonus)." },
                            { label: '😬 Argue that you are carrying a diplomat in a hurry', tip: 'Delay fine', action: 'argue', penaltyAmt: 2000, msg: "VIO delayed you with intensive vehicle check! ₦2,000 delay penalty incurred." }
                        ]
                    }
                }
            };
        }

        // 2. KEKE: Keke Napep Rider / Informal
        if (title.includes('keke') || title.includes('napep') || title.includes('rider')) {
            return {
                id: 'keke',
                title: job.title || 'Keke Napep Rider',
                workplaceTagline: 'Kubwa ⇄ Dutse Alhaji ⇄ Berger Junction Shuttle',
                iconClass: 'fa-solid fa-motorcycle',
                iconBg: 'bg-amber-600',
                normalStatus: '🛺 Commuting passengers between Kubwa and Berger Junction...',
                workActionText: 'Manoeuvre Potholes (+Speed)',
                workActionIcon: 'fa-solid fa-bolt',
                reliefActionText: 'Stop at Motor Park Restroom',
                reliefActionIcon: 'fa-solid fa-restroom',
                reliefStatusText: '🚽 Relieving yourself behind the Kubwa motor park facilities...',
                workActionFeedback: 'Dodged Kubwa expressway potholes with agility! Shaved 10s.',
                envLabels: {
                    powerOn: 'Engine: Humming ⚡',
                    powerOff: 'Engine: Stalled ⚠️',
                    bladderOk: 'Bladder: OK',
                    bladderFull: 'Bladder: 🚨 BURSTING',
                    bossNormal: 'Park Taskforce: Clear 🛵',
                    bossAlert: 'Agbero: Demanding Ticket 🎫'
                },
                crises: {
                    client: {
                        icon: '👥',
                        title: 'Market Rush at Dutse Junction!',
                        desc: 'Five market women with heavy yam baskets fight to board your Keke at the roadside.',
                        options: [
                            { label: '🤝 Pack 3 passengers neatly and tie baskets securely', tip: '+₦3,000 fares', action: 'polite', tipAmt: 3000, msg: "Safe trip with grateful passengers! Collected full ₦3,000 fare & tip." },
                            { label: '🏃 Squeeze 5 people inside (Overload hustle)', tip: '50/50: ₦5k OR fine', action: 'overload', tipAmt: 5000, penaltyAmt: 2000, msgSuccess: "Made record speed run! Collected ₦5,000 overload cash.", msgFail: "Taskforce caught you overloading! Fined ₦2,000." },
                            { label: '🙅 Decline and wait for light single passengers', tip: 'Slow turnover', action: 'decline', penaltyAmt: 800, msg: "Lost passenger crowd. Delayed shift turnaround." }
                        ]
                    },
                    pee: {
                        icon: '🚽',
                        title: 'Urgent Nature Call at Berger Underbridge!',
                        desc: 'Bwari road dust and cold pure water has filled your bladder to the brim.',
                        options: [
                            { label: '🏃 Rush to motor park public restroom (5s)', action: 'go' },
                            { label: '😣 Tighten belt and endure until end of route', action: 'hold' }
                        ]
                    },
                    nepa: {
                        icon: '🔧',
                        title: 'Keke Tyre Puncture & Flat Tire!',
                        desc: 'A sharp nail near Dutse market punctured your front tyre! Keke cannot move.',
                        options: [
                            { label: '🛞 Swap spare tyre with roadside vulcaniser (+Agility)', tip: '+₦1,500 bonus', action: 'fix', bonusAmt: 1500, msg: "Swift tyre swap! Back on the road under 2 minutes (+₦1,500 agility bonus)." },
                            { label: '⏳ Push Keke to filling station manually', tip: 'Exhausting wait', action: 'wait', msg: "Exhausting push to the station. Resuming route slowly." }
                        ]
                    },
                    boss: {
                        icon: '🎫',
                        title: 'NURTW Union Taskforce Encounter!',
                        desc: 'Fierce union boys block your handlebars at Berger junction demanding today\'s motor park dues.',
                        options: [
                            { label: '🎫 Pay union daily ticket peacefully with respect', tip: '+₦1,200 peace bonus', action: 'pay', bonusAmt: 1200, msg: "Union boys issued green ticket: 'Oya chairman, pass!' Smooth operations (+₦1,200 peace bonus)." },
                            { label: '🤬 Argue that you paid at Kubwa park earlier', tip: 'Risk mirror damage', action: 'argue', penaltyAmt: 1800, msg: "Union boys stripped your side mirror! Lost ₦1,800 resolving dispute." }
                        ]
                    }
                }
            };
        }

        // 3. TECHNICIAN: Banex Plaza Phone Technician / Trade
        if (title.includes('banex') || title.includes('technician') || title.includes('phone') || cat.includes('trade')) {
            return {
                id: 'technician',
                title: job.title || 'Banex Plaza Phone Technician',
                workplaceTagline: 'Banex Plaza, Block B, Wuse 2 – Hardware & Flashing Desk',
                iconClass: 'fa-solid fa-screwdriver-wrench',
                iconBg: 'bg-blue-600',
                normalStatus: '📱 At repair desk: Replacing OLED glass and diagnosing motherboard...',
                workActionText: 'Solder Chip & Screen (+Speed)',
                workActionIcon: 'fa-solid fa-microchip',
                reliefActionText: 'Plaza Restroom Break',
                reliefActionIcon: 'fa-solid fa-restroom',
                reliefStatusText: '🚽 Visiting Banex Plaza Block B restroom...',
                workActionFeedback: 'Clean soldering and heat gun work! Shaved 10s.',
                envLabels: {
                    powerOn: 'Plaza Power: ON ⚡',
                    powerOff: 'Plaza Gen: OFF 🕯️',
                    bladderOk: 'Bladder: OK',
                    bladderFull: 'Bladder: 🚨 FULL',
                    bossNormal: 'Plaza Shop: Steady 📱',
                    bossAlert: 'Customer: Checking Work 👀'
                },
                crises: {
                    client: {
                        icon: '📱',
                        title: 'Shattered iPhone 15 Pro Max Screen!',
                        desc: 'A frantic customer in Wuse 2 needs an original OLED screen replacement within 30 minutes for an urgent flight.',
                        options: [
                            { label: '🔬 Install Grade-A original OLED screen with precision', tip: '+₦5,500 tip', action: 'polite', tipAmt: 5500, msg: "Screen tested 120Hz TrueTone perfectly! Customer tipped you ₦5,500 cash." },
                            { label: '⚡ Offer quick glass lamination polish only', tip: '+₦3,000 tip', action: 'glass', tipAmt: 3000, msg: "Cost-effective repair completed! Earned ₦3,000 workmanship fee." },
                            { label: '⚠️ Rush the screws and tear the face-ID flex cable', tip: 'Costly error', action: 'damage', penaltyAmt: 2500, msg: "Flex cable damaged! Had to pay ₦2,500 to replace delicate sensor." }
                        ]
                    },
                    pee: {
                        icon: '🚽',
                        title: 'Banex Junction Suya Emergency!',
                        desc: 'The spicy suya you ate during morning break is violently churning in your stomach.',
                        options: [
                            { label: '🏃 Lock repair counter and sprint to plaza restroom (5s)', action: 'go' },
                            { label: '😣 Grip precision tweezers tightly and hold it', action: 'hold' }
                        ]
                    },
                    nepa: {
                        icon: '⚡',
                        title: 'Banex Plaza Generator Cut!',
                        desc: 'The central plaza generator tripped! Soldering iron and microscope turned pitch black.',
                        options: [
                            { label: '🔋 Switch to lithium backup inverter station (+Bonus)', tip: '+₦2,500 bonus', action: 'inverter', bonusAmt: 2500, msg: "Work never paused! Customer impressed by uninterrupted setup (+₦2,500 bonus)." },
                            { label: '⏳ Step outside and wait with other shop technicians', tip: 'Wait in heat', action: 'wait', msg: "Waited outside until plaza engineer reset generator breaker." }
                        ]
                    },
                    boss: {
                        icon: '🧐',
                        title: 'Pick-Up Inspection by Strict Customer!',
                        desc: 'The customer returns with a magnifying glass inspecting the phone frame for any glue marks or scratches.',
                        options: [
                            { label: '✨ Clean frame with isopropyl alcohol & show TrueTone', tip: '+₦3,000 bonus', action: 'inspect', bonusAmt: 3000, msg: "Flawless finish! Customer rated 5-stars and added +₦3,000 bonus." },
                            { label: '😬 Argue that old scratches were there before', tip: 'Discount given', action: 'argue', penaltyAmt: 1500, msg: "Customer demanded discount for cosmetic smudges (-₦1,500)." }
                        ]
                    }
                }
            };
        }

        // 4. TECH: Software Developer / Tech Lead / Tech
        if (title.includes('developer') || title.includes('tech lead') || title.includes('software') || cat.includes('tech')) {
            return {
                id: 'tech',
                title: job.title || 'Software Developer',
                workplaceTagline: 'Abuja Tech Hub & Remote Workspace, Jabi Lake',
                iconClass: 'fa-solid fa-laptop-code',
                iconBg: 'bg-purple-600',
                normalStatus: '💻 At developer station: Writing backend microservices and reviewing PRs...',
                workActionText: 'Ship Code & Fix Bugs (+Speed)',
                workActionIcon: 'fa-solid fa-code-commit',
                reliefActionText: 'Hub Restroom & Coffee Break',
                reliefActionIcon: 'fa-solid fa-mug-hot',
                reliefStatusText: '☕ Grabbing espresso and using the tech hub washroom...',
                workActionFeedback: 'Merged clean async pull request! Shaved 10s.',
                envLabels: {
                    powerOn: '5G Fiber: 450Mbps 📶',
                    powerOff: 'ISP Line: Down 🔌',
                    bladderOk: 'Bladder: OK',
                    bladderFull: 'Bladder: 🚨 BURSTING',
                    bossNormal: 'CI/CD: Passing 🟢',
                    bossAlert: 'CTO: Code Review 👀'
                },
                crises: {
                    client: {
                        icon: '🚨',
                        title: 'Production Outage – 502 Bad Gateway!',
                        desc: 'Fintech checkout API is throwing 502 errors during high volume payday transactions.',
                        options: [
                            { label: '🛠️ Hotfix database connection leak and redeploy', tip: '+₦6,500 bounty', action: 'polite', tipAmt: 6500, msg: "Critical incident resolved in minutes! Leadership awarded ₦6,500 bug bounty." },
                            { label: '🔄 Roll back Kubernetes pod to last stable build', tip: '+₦3,500 tip', action: 'rollback', tipAmt: 3500, msg: "System restored to green status. Safe execution (+₦3,500 bonus)." },
                            { label: '🤷 Blame telecom network provider in Slack channel', tip: 'Query logged', action: 'blame', penaltyAmt: 2000, msg: "CTO discovered internal memory leak! Docked ₦2,000 for misdirection." }
                        ]
                    },
                    pee: {
                        icon: '🚽',
                        title: 'Triple Espresso & Energy Drink Overload!',
                        desc: 'Your third can of energy drink is taking its toll. You urgently need to visit the tech hub washroom.',
                        options: [
                            { label: '🏃 Step away from mechanical keyboard for quick bio break (5s)', action: 'go' },
                            { label: '😣 Keep hacking through the sprint while wriggling', action: 'hold' }
                        ]
                    },
                    nepa: {
                        icon: '📶',
                        title: 'Subsea Fiber Cut – Main ISP Offline!',
                        desc: 'Office fiber connection dropped to zero KB/s. Git pushes and cloud deploys frozen.',
                        options: [
                            { label: '🛰️ Instantly failover to backup Starlink satellite dish (+Bonus)', tip: '+₦3,500 bonus', action: 'starlink', bonusAmt: 3500, msg: "Zero downtime achieved! Team applauded your proactive setup (+₦3,500 bonus)." },
                            { label: '⏳ Sit back and wait for local ISP fiber splicing', tip: 'Idling', action: 'wait', msg: "Waited idly for ISP team to repair roadside junction box." }
                        ]
                    },
                    boss: {
                        icon: '💻',
                        title: 'Surprise PR Architecture Review by CTO!',
                        desc: 'The Chief Technology Officer hops on a screen share to scrutinize your system architecture.',
                        options: [
                            { label: '🚀 Present clean modular microservices with 95% test coverage', tip: '+₦4,500 bonus', action: 'show', bonusAmt: 4500, msg: "CTO impressed: 'Top-tier engineering!' Performance bonus +₦4,500 awarded." },
                            { label: '😅 Explain hacky temporary patch with TODO comments', tip: 'Tech debt fine', action: 'excuse', penaltyAmt: 2000, msg: "Flagged technical debt. Tech lead logged ₦2,000 code review penalty." }
                        ]
                    }
                }
            };
        }

        // 5. CORPORATE: Bank Manager / Managing Director / Executive
        if (title.includes('bank') || title.includes('director') || title.includes('executive') || cat.includes('corporate') || cat.includes('executive')) {
            return {
                id: 'corporate',
                title: job.title || 'Corporate Executive',
                workplaceTagline: 'Central Business District – Towers / Boardroom Suite',
                iconClass: 'fa-solid fa-building-columns',
                iconBg: 'bg-indigo-600',
                normalStatus: '💼 In executive suite: Reviewing high-yield institutional portfolios...',
                workActionText: 'Approve Deal & Wire Transfer (+Speed)',
                workActionIcon: 'fa-solid fa-file-signature',
                reliefActionText: 'Executive Washroom Break',
                reliefActionIcon: 'fa-solid fa-restroom',
                reliefStatusText: '🚽 Visiting the marble executive washroom suite...',
                workActionFeedback: 'Cleared multi-million Naira syndicated financing memo! Shaved 10s.',
                envLabels: {
                    powerOn: 'Executive HVAC: 18°C ❄️',
                    powerOff: 'HVAC: Generator Lag ⚠️',
                    bladderOk: 'Bladder: OK',
                    bladderFull: 'Bladder: 🚨 FULL',
                    bossNormal: 'Portfolio: Healthy 💼',
                    bossAlert: 'Board Chairman: Inspecting 👔'
                },
                crises: {
                    client: {
                        icon: '💎',
                        title: 'High Net Worth HNI Investor Arrives!',
                        desc: 'A prominent oil mogul arrives with private security, looking to place ₦100M in short-term treasury assets.',
                        options: [
                            { label: '🥂 Welcome to private lounge with bespoke portfolio strategy', tip: '+₦10,000 bonus', action: 'polite', tipAmt: 10000, msg: "Client executed ₦100M investment! Earned ₦10,000 executive commission." },
                            { label: '📈 Structure corporate bond placement with 18% yield', tip: '+₦6,000 bonus', action: 'bond', tipAmt: 6000, msg: "Bond subscription approved. Client awarded ₦6,000 structuring fee." },
                            { label: '⏳ Leave client waiting 30 minutes in conference room', tip: 'Lost deal', action: 'delay', penaltyAmt: 3000, msg: "Client left furious for a competitor bank! Penalised ₦3,000." }
                        ]
                    },
                    pee: {
                        icon: '🚽',
                        title: 'Boardroom Coffee Marathon!',
                        desc: 'Three hours of continuous executive meetings and double espresso cups. Nature calls.',
                        options: [
                            { label: '🏃 Excuse yourself politely to executive washroom (5s)', action: 'go' },
                            { label: '😣 Maintain straight poker face during shareholder address', action: 'hold' }
                        ]
                    },
                    nepa: {
                        icon: '⚡',
                        title: 'CBD Power Grid Fluctuation!',
                        desc: 'Substation power flicker caused trading screens to flicker.',
                        options: [
                            { label: '⚡ Engage dual Caterpillar diesel generators instantly', tip: '+₦4,000 bonus', action: 'gen', bonusAmt: 4000, msg: "Flawless switchover! Trading floor remained 100% active (+₦4,000 bonus)." },
                            { label: '⏳ Wait for automated building transfer switch', tip: 'Slow switch', action: 'wait', msg: "Brief 1-minute downtime until building systems stabilized." }
                        ]
                    },
                    boss: {
                        icon: '👔',
                        title: 'Board Chairman Walk-in Audit!',
                        desc: 'The Board Chairman arrives for unannounced quarterly revenue verification.',
                        options: [
                            { label: '📊 Present audited quarterly revenue achieving 140% of target', tip: '+₦8,000 bonus', action: 'present', bonusAmt: 8000, msg: "Chairman stood and shook hands: 'Exemplary leadership!' +₦8,000 bonus." },
                            { label: '😬 Scramble for missing variance reconciliation sheets', tip: 'Audit query', action: 'excuse', penaltyAmt: 3500, msg: "Audit noted discrepancies. Docked ₦3,500 performance pay." }
                        ]
                    }
                }
            };
        }

        // 6. DEFAULT: Federal Secretariat / Ministry Officer / Politics
        return {
            id: 'civil_service',
            title: job.title || 'Federal Ministry Officer',
            workplaceTagline: 'Federal Secretariat Complex, Shehu Shagari Way',
            iconClass: 'fa-solid fa-landmark',
            iconBg: 'bg-emerald-600',
            normalStatus: '🏛️ At registry desk: Minuting official dockets and gazette files...',
            workActionText: 'Minute Memos & Stamp Files (+Speed)',
            workActionIcon: 'fa-solid fa-stamp',
            reliefActionText: 'Secretariat Restroom Break',
            reliefActionIcon: 'fa-solid fa-restroom',
            reliefStatusText: '🚽 Visiting the 3rd floor Secretariat restroom...',
            workActionFeedback: 'Minuted official docket and stamped files! Shaved 10s.',
            envLabels: {
                powerOn: 'Secretariat Power: ON ⚡',
                powerOff: 'Secretariat: Blackout 🕯️',
                bladderOk: 'Bladder: OK',
                bladderFull: 'Bladder: 🚨 FULL',
                bossNormal: 'Permanent Sec: In Office 🏛️',
                bossAlert: 'Director: At Your Desk 👀'
            },
            crises: {
                client: {
                    icon: '👤',
                    title: 'VIP Procurement Contractor Walk-in!',
                    desc: 'Alhaji Musa walks up to your desk demanding express tender clearance for his infrastructure company.',
                    options: [
                        { label: '🤝 Attend swiftly with civil service protocol', tip: '+₦3,500 tip', action: 'polite', tipAmt: 3500, msg: "Alhaji Musa smiled: 'You be good boy!' Handed you ₦3,500 tip." },
                        { label: '😏 Request \'Kola Nut\' facilitation fee', tip: '50/50: ₦5k OR query', action: 'kola', tipAmt: 5000, penaltyAmt: 2000, msgSuccess: "Client slipped ₦5,000 kola nut cash into your drawer!", msgFail: "Client yelled and reported you to Director! ₦2,000 deducted." },
                        { label: '⏳ Tell him the database server is currently slow', tip: 'Client grumbles', action: 'delay', penaltyAmt: 1000, msg: "Client grumbled and left. Customer feedback reduced." }
                    ]
                },
                pee: {
                    icon: '🚽',
                    title: 'Secretariat Canteen Emergency!',
                    desc: 'The spicy goat meat and jollof from the ministry canteen has your stomach churning intensely.',
                    options: [
                        { label: '🏃 Walk down corridor to 3rd floor restroom (5s)', action: 'go' },
                        { label: '😣 Hold it like a patriot and keep stamping files', action: 'hold' }
                    ]
                },
                nepa: {
                    icon: '⚡',
                    title: 'Secretariat Transformer Tripped!',
                    desc: 'Power cut off! Computers went dark and ceiling fans halted across the ministry block.',
                    options: [
                        { label: '🔌 Engage backup Mikano generator with facilities officer', tip: '+₦2,500 bonus', action: 'generator', bonusAmt: 2500, msg: "Generator roaring! Power restored. Director gave +₦2,500 initiative bonus!" },
                        { label: '🕯️ Sit quietly in the dim registry corridor', tip: 'Wait in dark', action: 'wait', msg: "Waited in the heat until central power returned." }
                    ]
                },
                boss: {
                    icon: '👔',
                    title: 'Surprise Registry Inspection by Director!',
                    desc: 'The Departmental Director is pacing down the aisle checking whose pending tray has backlogs.',
                    options: [
                        { label: '📂 Stand at attention with sorted dockets: \'Good afternoon, Sir!\'', tip: '+₦3,000 bonus', action: 'busy', bonusAmt: 3000, msg: "Director nodded: 'Very organized officer!' +₦3,000 performance bonus!" },
                        { label: '📱 Caught scrolling WhatsApp behind open files', tip: 'Reprimand fine', action: 'caught', penaltyAmt: 2000, msg: "Director gave you a stern reprimand! ₦2,000 disciplinary deduction." }
                    ]
                }
            }
        };
    },

    startWorkShift(job, baseSalary, initialElapsed = 0, duration = 480, startedAtUnix = null) {
        const startedMs = startedAtUnix ? (startedAtUnix * 1000) : (Date.now() - (initialElapsed * 1000));
        const totalSecs = duration || 480;
        const profile = this.getJobProfile(job);

        this.shiftState = {
            active: true,
            timer: null,
            totalSeconds: totalSecs,
            startedAtMs: startedMs,
            elapsed: initialElapsed,
            progress: Math.min(100, Math.round((initialElapsed / totalSecs) * 100)),
            job: job,
            profile: profile,
            baseSalary: parseFloat(baseSalary || 0),
            tips: 0,
            bonuses: 0,
            penalties: 0,
            powerOn: true,
            isAtDesk: true,
            bladderLevel: Math.min(100, Math.round((initialElapsed / totalSecs) * 60)),
            crisesTriggered: {}
        };

        // Open modal
        const modal = document.getElementById('workShiftModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Apply Job Profile to Header, Tagline, and Icon
        const titleEl = document.getElementById('shiftJobTitle');
        if (titleEl) titleEl.textContent = profile.title;
        const taglineEl = document.getElementById('shiftWorkplaceTagline');
        if (taglineEl) taglineEl.textContent = profile.workplaceTagline;
        const iconEl = document.getElementById('shiftJobIcon');
        if (iconEl) iconEl.className = profile.iconClass;
        const iconWrapperEl = document.getElementById('shiftJobIconWrapper');
        if (iconWrapperEl) iconWrapperEl.className = `w-10 h-10 rounded-2xl ${profile.iconBg} text-white flex items-center justify-center text-lg shadow-sm`;

        // Apply Job Profile to Quick Action Buttons
        const btnWorkText = document.getElementById('shiftBtnWorkText');
        if (btnWorkText) btnWorkText.textContent = profile.workActionText;
        const btnWorkIcon = document.getElementById('shiftBtnWorkIcon');
        if (btnWorkIcon) btnWorkIcon.className = profile.workActionIcon;

        const btnReliefText = document.getElementById('shiftBtnReliefText');
        if (btnReliefText) btnReliefText.textContent = profile.reliefActionText;
        const btnReliefIcon = document.getElementById('shiftBtnReliefIcon');
        if (btnReliefIcon) btnReliefIcon.className = profile.reliefActionIcon;

        this.updateShiftUI();

        // Start 1-second clock loop
        if (this.shiftState.timer) clearInterval(this.shiftState.timer);
        this.shiftState.timer = setInterval(() => this.tickWorkShift(), 1000);
    },

    tickWorkShift() {
        if (!this.shiftState.active) return;

        const profile = this.shiftState.profile || this.getJobProfile(this.shiftState.job);

        // If power is out / route blocked, progress pauses until resolved!
        if (!this.shiftState.powerOn) {
            const statusEl = document.getElementById('shiftStatusText');
            if (statusEl) {
                statusEl.textContent = (profile.id === 'bolt' || profile.id === 'keke')
                    ? '🛑 GRIDLOCK: Vehicle stuck in traffic gridlock. Clear roadblock to resume trip!'
                    : '⚡ BLACKOUT: Workplace systems halted. Restore power to resume!';
            }
            return;
        }

        // Exact real-time sync against startedAtMs
        const now = Date.now();
        const calculatedElapsed = Math.floor((now - this.shiftState.startedAtMs) / 1000);
        this.shiftState.elapsed = Math.max(this.shiftState.elapsed + 1, calculatedElapsed);
        this.shiftState.progress = Math.min(100, Math.round((this.shiftState.elapsed / this.shiftState.totalSeconds) * 100));

        // Gradual bladder pressure over the 8 minutes
        this.shiftState.bladderLevel = Math.min(100, this.shiftState.bladderLevel + 0.25);

        this.updateShiftUI();

        // Check Milestone Crises
        const p = this.shiftState.progress;

        // 1. Client Walk-in / Passenger at ~20%
        if (p >= 20 && !this.shiftState.crisesTriggered['client']) {
            this.shiftState.crisesTriggered['client'] = true;
            this.triggerShiftCrisis('client');
        }

        // 2. Nature Calls (Pee / Pitstop) at ~45%
        if (p >= 45 && !this.shiftState.crisesTriggered['pee']) {
            this.shiftState.crisesTriggered['pee'] = true;
            this.triggerShiftCrisis('pee');
        }

        // 3. NEPA Blackout / Gridlock at ~68%
        if (p >= 68 && !this.shiftState.crisesTriggered['nepa']) {
            this.shiftState.crisesTriggered['nepa'] = true;
            this.triggerShiftCrisis('nepa');
        }

        // 4. Oga Boss / VIO Inspection at ~85%
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
        const profile = s.profile || this.getJobProfile(s.job);

        // Virtual Clock Time (09:00 AM to 05:00 PM over 8 hours)
        const totalMinutes = Math.round((p / 100) * (8 * 60)); // 0 to 480 mins
        const hour = 9 + Math.floor(totalMinutes / 60);
        const mins = totalMinutes % 60;
        const hour12 = hour > 12 ? hour - 12 : hour;
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const timeStr = `${String(hour12).padStart(2, '0')}:${String(mins).padStart(2, '0')} ${ampm}`;

        const clockEl = document.getElementById('shiftClockTime');
        if (clockEl) clockEl.textContent = timeStr;
        const timeDispEl = document.getElementById('shiftTimeDisplay');
        if (timeDispEl) timeDispEl.textContent = `${s.elapsed}s / ${s.totalSeconds}s`;
        const barEl = document.getElementById('shiftProgressBar');
        if (barEl) barEl.style.width = `${p}%`;
        const pctEl = document.getElementById('shiftPctText');
        if (pctEl) pctEl.textContent = `${p}%`;

        // If normal status and not in custom relief or crisis, display profile normalStatus
        const statusEl = document.getElementById('shiftStatusText');
        if (statusEl && s.isAtDesk && s.powerOn) {
            statusEl.textContent = profile.normalStatus;
        }

        // Accumulated Pay
        const currentAccumulated = Math.max(0, Math.round((s.baseSalary * (p / 100)) + s.tips + s.bonuses - s.penalties));
        const accPayEl = document.getElementById('shiftAccPay');
        if (accPayEl) accPayEl.textContent = this.formatNaira(currentAccumulated);

        // Environmental Indicators based on profile.envLabels
        const envLabels = profile.envLabels || {};
        const envPower = document.getElementById('shiftEnvPower');
        const envPowerText = document.getElementById('shiftEnvPowerText');
        if (envPower && envPowerText) {
            if (s.powerOn) {
                envPower.className = "p-2.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 font-bold flex items-center justify-center gap-1.5";
                envPowerText.textContent = envLabels.powerOn || "Power: ON";
            } else {
                envPower.className = "p-2.5 rounded-2xl bg-rose-50 border border-rose-300 text-rose-900 font-bold flex items-center justify-center gap-1.5 animate-pulse";
                envPowerText.textContent = envLabels.powerOff || "Power: ⚡ OUT";
            }
        }

        const envBladder = document.getElementById('shiftEnvBladder');
        const envBladderText = document.getElementById('shiftEnvBladderText');
        if (envBladder && envBladderText) {
            if (s.bladderLevel >= 75) {
                envBladder.className = "p-2.5 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 font-bold flex items-center justify-center gap-1.5 animate-pulse";
                envBladderText.textContent = envLabels.bladderFull || "Bladder: 🚨 FULL";
            } else {
                envBladder.className = "p-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 font-bold flex items-center justify-center gap-1.5";
                envBladderText.textContent = envLabels.bladderOk || "Bladder: OK";
            }
        }

        const envBoss = document.getElementById('shiftEnvBoss');
        const envBossText = document.getElementById('shiftEnvBossText');
        if (envBoss && envBossText) {
            if (s.crisesTriggered['boss'] && !s.bossCrisisResolved) {
                envBoss.className = "p-2.5 rounded-2xl bg-purple-50 border border-purple-300 text-purple-900 font-bold flex items-center justify-center gap-1.5 animate-pulse";
                envBossText.textContent = envLabels.bossAlert || "Inspection: ACTIVE 👀";
            } else {
                envBoss.className = "p-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 font-bold flex items-center justify-center gap-1.5";
                envBossText.textContent = envLabels.bossNormal || "Supervision: Normal";
            }
        }
    },

    triggerShiftCrisis(type) {
        const crisisBox = document.getElementById('shiftCrisisCard');
        if (!crisisBox) return;

        this.playSfx('click');
        crisisBox.classList.remove('hidden');

        const profile = this.shiftState.profile || this.getJobProfile(this.shiftState.job);
        const crisis = profile.crises && profile.crises[type];
        if (!crisis) return;

        if (type === 'nepa') {
            this.shiftState.powerOn = false;
        }

        let bgClass = "bg-amber-50 border-amber-200 text-amber-950";
        if (type === 'pee') bgClass = "bg-sky-50 border-sky-200 text-sky-950";
        if (type === 'nepa') bgClass = "bg-rose-50 border-rose-300 text-rose-950";
        if (type === 'boss') bgClass = "bg-purple-50 border-purple-200 text-purple-950";

        crisisBox.className = `rounded-2xl p-4 border ${bgClass} animate-fade-up space-y-2.5`;

        let optionsHtml = '';
        if (type === 'pee') {
            optionsHtml = `
                <div class="grid grid-cols-2 gap-2 pt-1">
                    ${crisis.options.map((opt, idx) => `
                        <button onclick="GameApp.resolveShiftCrisis('${type}', ${idx})" class="py-2.5 px-3 ${idx === 0 ? 'bg-sky-600 hover:bg-sky-500 text-white' : 'bg-white hover:bg-sky-100 border border-sky-300 text-sky-900'} rounded-xl font-bold text-xs transition active:scale-95 text-center">
                            ${opt.label}
                        </button>
                    `).join('')}
                </div>
            `;
        } else {
            optionsHtml = `
                <div class="space-y-1.5 pt-1">
                    ${crisis.options.map((opt, idx) => `
                        <button onclick="GameApp.resolveShiftCrisis('${type}', ${idx})" class="w-full py-2 px-3 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl text-left font-bold text-xs text-slate-800 transition flex items-center justify-between">
                            <span>${opt.label}</span>
                            ${opt.tip ? `<span class="text-[10px] text-emerald-700 font-bold">${opt.tip}</span>` : ''}
                        </button>
                    `).join('')}
                </div>
            `;
        }

        crisisBox.innerHTML = `
            <div class="flex items-center gap-2">
                <span class="text-xl">${crisis.icon}</span>
                <div>
                    <h4 class="font-extrabold text-xs text-slate-900">${crisis.title}</h4>
                    <p class="text-[11px] text-slate-700">${crisis.desc}</p>
                </div>
            </div>
            ${optionsHtml}
        `;
    },

    resolveShiftCrisis(type, optionIdx) {
        const crisisBox = document.getElementById('shiftCrisisCard');
        if (crisisBox) crisisBox.classList.add('hidden');

        const s = this.shiftState;
        const profile = s.profile || this.getJobProfile(s.job);
        const crisis = profile.crises && profile.crises[type];
        const opt = (crisis && crisis.options) ? crisis.options[optionIdx] : null;

        if (type === 'pee') {
            if (optionIdx === 0) { // Go to restroom / pitstop
                s.isAtDesk = false;
                s.bladderLevel = 0;
                const statusEl = document.getElementById('shiftStatusText');
                if (statusEl) statusEl.textContent = profile.reliefStatusText || '🚽 Relieving yourself...';
                this.notify("Ah, huge relief! You're refreshed and ready to continue (+10 happiness).", 'info');
                setTimeout(() => {
                    s.isAtDesk = true;
                    const statusEl = document.getElementById('shiftStatusText');
                    if (statusEl) statusEl.textContent = profile.normalStatus;
                }, 4000);
            } else {
                this.notify("You gritted your teeth and held it in painfully. Sweating profusely!", 'error');
            }
        } else if (type === 'nepa') {
            s.powerOn = true;
            if (opt) {
                if (opt.bonusAmt) {
                    s.bonuses += opt.bonusAmt;
                    this.playSfx('win');
                    this.notify(opt.msg || `Crisis handled! Bonus +₦${opt.bonusAmt.toLocaleString()}`, 'success');
                } else {
                    this.playSfx('click');
                    this.notify(opt.msg || "Resumed work.", 'info');
                }
            }
        } else if (type === 'boss') {
            s.bossCrisisResolved = true;
            if (opt) {
                if (opt.bonusAmt) {
                    if (s.isAtDesk) {
                        s.bonuses += opt.bonusAmt;
                        this.playSfx('win');
                        this.notify(opt.msg || `Inspection passed! Bonus +₦${opt.bonusAmt.toLocaleString()}`, 'success');
                    } else {
                        s.penalties += 2000;
                        this.playSfx('loss');
                        this.notify("Inspector arrived while you were away from your station! ₦2,000 docked.", 'error');
                    }
                } else if (opt.penaltyAmt) {
                    s.penalties += opt.penaltyAmt;
                    this.playSfx('loss');
                    this.notify(opt.msg || "Inspection query issued.", 'error');
                }
            }
        } else if (type === 'client') {
            if (opt) {
                if (opt.tipAmt && !opt.penaltyAmt) {
                    s.tips += opt.tipAmt;
                    this.playSfx('money');
                    this.notify(opt.msg || `Received tip of ₦${opt.tipAmt.toLocaleString()}!`, 'success');
                } else if (opt.tipAmt && opt.penaltyAmt) {
                    // 50/50 gamble
                    if (Math.random() > 0.45) {
                        s.tips += opt.tipAmt;
                        this.playSfx('money');
                        this.notify(opt.msgSuccess || `Success! Collected ₦${opt.tipAmt.toLocaleString()}`, 'success');
                    } else {
                        s.penalties += opt.penaltyAmt;
                        this.playSfx('loss');
                        this.notify(opt.msgFail || `Caught! Fined ₦${opt.penaltyAmt.toLocaleString()}`, 'error');
                    }
                } else if (opt.penaltyAmt) {
                    s.penalties += opt.penaltyAmt;
                    this.playSfx('loss');
                    this.notify(opt.msg || "Lost revenue from dissatisfied customer.", 'error');
                } else {
                    this.notify(opt.msg || "Handled customer.", 'info');
                }
            }
        }

        this.updateShiftUI();
    },

    shiftDoWorkTask() {
        if (!this.shiftState.active || !this.shiftState.powerOn) return;
        this.shiftState.startedAtMs -= 10000; // Fast forwards by 10 real seconds of hard work
        this.shiftState.elapsed += 10;
        this.shiftState.progress = Math.min(100, Math.round((this.shiftState.elapsed / this.shiftState.totalSeconds) * 100));
        this.playSfx('click');
        const msg = (this.shiftState.profile && this.shiftState.profile.workActionFeedback) 
            ? this.shiftState.profile.workActionFeedback 
            : "Working hard! Shaved 10s off the shift.";
        this.notify(msg, 'info');
        this.updateShiftUI();
    },

    shiftGoBathroom() {
        if (!this.shiftState.active) return;
        this.resolveShiftCrisis('pee', 0);
    },

    abandonShiftPrompt() {
        const confirmed = confirm("⚠️ ARE YOU SURE YOU WANT TO SNEAK OUT EARLY?\n\nLeaving your post early means you will LOSE your daily salary and get issued a formal disciplinary query (-12 Street Cred)!");
        if (!confirmed) return;

        clearInterval(this.shiftState.timer);
        this.shiftState.active = false;

        const modal = document.getElementById('workShiftModal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }

        const formData = new FormData();
        formData.append('progress', this.shiftState.progress);

        fetch('api/jobs.php?action=abandon_shift', { method: 'POST', body: formData })
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
            const res = await fetch('api/jobs.php?action=finish_shift', { method: 'POST', body: formData });
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
                if (desc) desc.textContent = 'VIO / FRSC officer signals you to pull over along Shehu Shagari Way. How do you handle this?';
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
        const market = document.getElementById('hagglingMarket')?.value || 'wuse_market';
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
    },

    // ====================================================
    // RESIDENCE & HOUSE MANAGEMENT
    // ====================================================
    async openResidenceModal() {
        try {
            const res = await fetch('api/character.php?action=enter_residence');
            const data = await res.json();
            if (!data.success) {
                this.notify(data.error || 'Could not enter residence.', 'error');
                return;
            }

            const titleEl = document.getElementById('residenceTitle');
            const subEl = document.getElementById('residenceSubtitle');
            if (titleEl) titleEl.textContent = data.home_name || 'Your Residence';
            if (subEl) subEl.textContent = `${data.district} • ${data.tier}`;
            
            const hs = data.home_state || {};
            const pMode = (hs.power_mode || 'nepa').toUpperCase();
            const pModeEl = document.getElementById('resPowerMode');
            const invEl = document.getElementById('resInverterPct');
            const genEl = document.getElementById('resGenFuel');
            if (pModeEl) pModeEl.textContent = pMode === 'GENERATOR' ? '⚙️ MIKANO' : (pMode === 'INVERTER' ? '🔋 INVERTER' : '🔌 NEPA');
            if (invEl) invEl.textContent = `${hs.inverter_battery || 85}% Charged`;
            if (genEl) genEl.textContent = `${hs.gen_fuel_liters || 6} Liters`;

            const modal = document.getElementById('residenceModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
            this.playSfx('click');
        } catch(e) {
            this.notify('Error accessing residence.', 'error');
        }
    },

    closeResidenceModal() {
        const modal = document.getElementById('residenceModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    },

    async manageResidence(subAction, extra = null) {
        const formData = new FormData();
        formData.append('sub_action', subAction);
        if (extra) formData.append('power_mode', extra);

        try {
            const res = await fetch('api/character.php?action=manage_residence', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('win');
                this.notify(data.message, 'success');
                const hs = data.home_state || {};
                const pMode = (hs.power_mode || 'nepa').toUpperCase();
                const pModeEl = document.getElementById('resPowerMode');
                const invEl = document.getElementById('resInverterPct');
                const genEl = document.getElementById('resGenFuel');
                if (pModeEl) pModeEl.textContent = pMode === 'GENERATOR' ? '⚙️ MIKANO' : (pMode === 'INVERTER' ? '🔋 INVERTER' : '🔌 NEPA');
                if (invEl) invEl.textContent = `${hs.inverter_battery || 85}% Charged`;
                if (genEl) genEl.textContent = `${hs.gen_fuel_liters || 6} Liters`;
                await this.fetchCharacter();
            } else {
                this.playSfx('loss');
                this.notify(data.error || 'Action failed', 'error');
            }
        } catch(e) {
            this.notify('Connection error', 'error');
        }
    },

    // ====================================================
    // COMMUTE TO WORK TRANSITION
    // ====================================================
    openCommuteModal() {
        if (!this.character || !this.character.current_job_id) {
            this.notify("You don't have a job yet! Apply in Careers first.", 'error');
            this.switchTab('jobs');
            return;
        }
        if (this.shiftState.active) {
            const modal = document.getElementById('workShiftModal');
            if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
            return;
        }
        const modal = document.getElementById('commuteModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    },

    async commuteToWork(mode) {
        const modal = document.getElementById('commuteModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        const formData = new FormData();
        formData.append('mode', mode);

        try {
            const res = await fetch('api/character.php?action=commute_to_work', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('click');
                this.notify(data.message, 'info');
                await this.fetchCharacter();
                // Clock into work shift
                this.goToWork();
            } else {
                this.playSfx('loss');
                this.notify(data.error || 'Commute failed', 'error');
            }
        } catch(e) {
            this.notify('Commute connection error', 'error');
        }
    },

    // ====================================================
    // CITIZENS SOCIAL DIRECTORY & FINDER
    // ====================================================
    activeInspectedCitizen: null,

    openCitizenFinder() {
        const modal = document.getElementById('citizensFinderModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        this.searchCitizens('');
    },

    closeCitizenFinder() {
        const modal = document.getElementById('citizensFinderModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    },

    async searchCitizens(query = null) {
        if (query === null) {
            query = document.getElementById('citizenSearchInput')?.value || '';
        }
        try {
            const res = await fetch(`api/citizens.php?action=search&q=${encodeURIComponent(query)}`);
            const data = await res.json();
            const listEl = document.getElementById('citizensResultsList');
            if (!listEl || !data.success) return;

            if (!data.citizens || data.citizens.length === 0) {
                listEl.innerHTML = `<div class="p-8 text-center text-xs text-slate-400">No Abuja citizens found matching "${query}".</div>`;
                return;
            }

            listEl.innerHTML = data.citizens.map(c => `
                <div class="p-3.5 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">
                            ${c.full_name ? c.full_name.charAt(0) : 'A'}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <strong class="text-xs text-slate-900">${c.full_name}</strong>
                                <span class="text-[11px] font-mono font-bold text-purple-700">${c.username}</span>
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                <span><i class="fa-solid fa-location-dot text-slate-400 mr-0.5"></i> ${c.district}</span>
                                <span>•</span>
                                <span><i class="fa-solid fa-briefcase text-slate-400 mr-0.5"></i> ${c.job_title}</span>
                                <span>•</span>
                                <span class="text-emerald-700 font-mono font-bold">${this.formatCompactNaira(c.net_worth)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 self-end sm:self-center">
                        <button onclick="GameApp.viewCitizenProfile(${c.id})" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-[11px] rounded-xl transition">
                            Profile
                        </button>
                        <button onclick="GameApp.openPeerTransfer({id: ${c.id}, full_name: '${c.full_name.replace(/'/g, "\\'")}', username: '${c.username}'})" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] rounded-xl transition">
                            Send ₦
                        </button>
                        <button onclick="GameApp.challengeCitizen({id: ${c.id}, username: '${c.username}'})" class="px-2.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-[11px] rounded-xl transition">
                            🎲 Dice
                        </button>
                    </div>
                </div>
            `).join('');
        } catch(e) {
            console.error('Citizens search failed', e);
        }
    },

    async viewCitizenProfile(citizenId) {
        try {
            const res = await fetch(`api/citizens.php?action=profile&id=${citizenId}`);
            const data = await res.json();
            if (!data.success) {
                this.notify(data.error || 'Failed to load profile', 'error');
                return;
            }
            const p = data.profile;
            this.activeInspectedCitizen = p;

            const nameEl = document.getElementById('profFullName');
            const userEl = document.getElementById('profUsername');
            const distEl = document.getElementById('profDistrict');
            const netEl = document.getElementById('profNetWorth');
            const jobEl = document.getElementById('profJob');
            const credEl = document.getElementById('profCred');
            const resEl = document.getElementById('profResidence');
            const vehEl = document.getElementById('profVehicle');

            if (nameEl) nameEl.textContent = p.full_name;
            if (userEl) userEl.textContent = p.username;
            if (distEl) distEl.textContent = p.district;
            if (netEl) netEl.textContent = this.formatNaira(p.net_worth);
            if (jobEl) jobEl.textContent = p.job_title;
            if (credEl) credEl.textContent = `${p.street_cred} ⭐`;
            if (resEl) resEl.textContent = p.residence;
            if (vehEl) vehEl.textContent = p.vehicle;

            const modal = document.getElementById('citizenProfileModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        } catch(e) {
            this.notify('Profile load error', 'error');
        }
    },

    openPeerTransfer(citizen) {
        if (!citizen) return;
        this.activeInspectedCitizen = citizen;
        const recNameEl = document.getElementById('transferRecipientName');
        const recIdEl = document.getElementById('transferRecipientId');
        const amtEl = document.getElementById('transferAmountInput');

        if (recNameEl) recNameEl.value = `${citizen.full_name || 'Citizen'} (${citizen.username})`;
        if (recIdEl) recIdEl.value = citizen.id;
        if (amtEl) amtEl.value = '';

        const formView = document.getElementById('transferFormView');
        const recView = document.getElementById('transferReceiptView');
        if (formView) formView.classList.remove('hidden');
        if (recView) recView.classList.add('hidden');

        const modal = document.getElementById('peerTransferModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    },

    async sendPeerTransfer() {
        const id = document.getElementById('transferRecipientId')?.value;
        const nameInput = document.getElementById('transferRecipientName')?.value || '';
        const amount = document.getElementById('transferAmountInput')?.value;
        const memo = document.getElementById('transferMemoInput')?.value || 'Transfer';

        if (!amount || parseFloat(amount) <= 0) {
            this.notify('Please enter a valid transfer amount.', 'error');
            return;
        }

        const formData = new FormData();
        if (id) {
            formData.append('recipient_id', id);
        }
        const usernameMatch = nameInput.match(/@([a-zA-Z0-9_]+)/);
        if (usernameMatch) {
            formData.append('recipient_username', usernameMatch[1]);
        } else if (nameInput.trim() && !id) {
            formData.append('recipient_username', nameInput.trim().replace(/^@/, ''));
        }
        formData.append('amount', amount);
        formData.append('memo', memo);

        try {
            const res = await fetch('api/citizens.php?action=transfer', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('money');
                const recAmt = document.getElementById('recAmount');
                const recTo = document.getElementById('recRecipient');
                const recRef = document.getElementById('recRef');

                if (recAmt) recAmt.textContent = this.formatNaira(amount);
                if (recTo) recTo.textContent = data.receipt?.recipient || 'Citizen';
                if (recRef) recRef.textContent = data.receipt?.reference || 'ABP-2026-X';

                const formView = document.getElementById('transferFormView');
                const recView = document.getElementById('transferReceiptView');
                if (formView) formView.classList.add('hidden');
                if (recView) recView.classList.remove('hidden');

                this.notify(data.message, 'success');
                await this.fetchCharacter();
                if (window.PhoneApp && typeof window.PhoneApp.loadBankApp === 'function') {
                    window.PhoneApp.loadBankApp();
                }
            } else {
                this.playSfx('loss');
                this.notify(data.error || 'Transfer failed', 'error');
            }
        } catch(e) {
            this.notify('Transfer network error', 'error');
        }
    },

    async challengeCitizen(citizen) {
        if (!citizen) return;
        const formData = new FormData();
        formData.append('citizen_id', citizen.id);

        try {
            const res = await fetch('api/citizens.php?action=challenge', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx(data.won ? 'win' : 'loss');
                alert(`🎲 STREET DICE CHALLENGE vs ${citizen.username}\n\nYour Roll: 🎲 ${data.player_roll}\nOpponent Roll: 🎲 ${data.opponent_roll}\n\nOutcome: ${data.message}`);
                this.notify(data.message, data.won ? 'success' : 'error');
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Challenge could not take place', 'error');
            }
        } catch(e) {
            this.notify('Challenge connection error', 'error');
        }
    },

    // ====================================================
    // STREET FIGHT & AGBERO CLASH ENGINE
    // ====================================================
    streetFightState: {
        oppHp: 80,
        maxOppHp: 80,
        playerHp: 100,
        maxPlayerHp: 100,
        opponentName: 'Area Boy'
    },

    async openStreetFight() {
        const formData = new FormData();
        formData.append('combat_action', 'encounter');

        try {
            const res = await fetch('api/street.php?action=street_fight', { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.success) {
                this.notify(data.error || 'No street clashes right now', 'info');
                return;
            }

            const pHealth = this.character ? parseInt(this.character.health) : 100;
            this.streetFightState = {
                oppHp: data.opponent_hp,
                maxOppHp: data.opponent_hp,
                playerHp: pHealth,
                maxPlayerHp: 100,
                opponentName: data.opponent_name
            };

            const titleEl = document.getElementById('fightTitle');
            const oppNameEl = document.getElementById('fightOppName');
            const oppHpText = document.getElementById('fightOppHpText');
            const oppHpBar = document.getElementById('fightOppHpBar');
            const playerHpText = document.getElementById('fightPlayerHpText');
            const playerHpBar = document.getElementById('fightPlayerHpBar');
            const logEl = document.getElementById('streetFightLog');

            if (titleEl) titleEl.textContent = `Street Clash vs ${data.opponent_name}`;
            if (oppNameEl) oppNameEl.textContent = data.opponent_name;
            if (oppHpText) oppHpText.textContent = `${data.opponent_hp} HP`;
            if (oppHpBar) oppHpBar.style.width = '100%';
            if (playerHpText) playerHpText.textContent = `${pHealth} HP`;
            if (playerHpBar) playerHpBar.style.width = `${pHealth}%`;
            if (logEl) logEl.textContent = data.message;

            const modal = document.getElementById('streetFightModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
            this.playSfx('click');
        } catch(e) {
            this.notify('Street confrontation failed to load', 'error');
        }
    },

    async doStreetFightAction(action) {
        const formData = new FormData();
        formData.append('combat_action', action);
        formData.append('player_hp', this.streetFightState.playerHp);
        formData.append('opp_hp', this.streetFightState.oppHp);

        try {
            const res = await fetch('api/street.php?action=street_fight', { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.success) {
                this.notify(data.error || 'Action failed', 'error');
                return;
            }

            const logEl = document.getElementById('streetFightLog');
            if (logEl) logEl.textContent = data.message;

            if (data.resolved) {
                if (data.won) {
                    this.playSfx('win');
                    const oppHpBar = document.getElementById('fightOppHpBar');
                    const oppHpText = document.getElementById('fightOppHpText');
                    if (oppHpBar) oppHpBar.style.width = '0%';
                    if (oppHpText) oppHpText.textContent = '0 HP (K.O.)';
                    this.notify(data.message, 'success');
                } else {
                    this.playSfx('loss');
                    this.notify(data.message, 'info');
                }
                await this.fetchCharacter();
                setTimeout(() => {
                    const modal = document.getElementById('streetFightModal');
                    if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
                }, 2000);
            } else {
                this.streetFightState.playerHp = data.player_hp;
                this.streetFightState.oppHp = data.opp_hp;

                const oppPct = Math.round((data.opp_hp / this.streetFightState.maxOppHp) * 100);
                const oppHpBar = document.getElementById('fightOppHpBar');
                const oppHpText = document.getElementById('fightOppHpText');
                if (oppHpBar) oppHpBar.style.width = `${Math.max(0, oppPct)}%`;
                if (oppHpText) oppHpText.textContent = `${data.opp_hp} HP`;

                const playerPct = Math.min(100, Math.max(0, data.player_hp));
                const playerHpBar = document.getElementById('fightPlayerHpBar');
                const playerHpText = document.getElementById('fightPlayerHpText');
                if (playerHpBar) playerHpBar.style.width = `${playerPct}%`;
                if (playerHpText) playerHpText.textContent = `${data.player_hp} HP`;

                this.playSfx('loss');
                await this.fetchCharacter();
            }
        } catch(e) {
            this.notify('Fight connection dropped', 'error');
        }
    },

    // ====================================================
    // NETWORK CONNECTION & ISP SIGNAL TROUBLESHOOTING
    // ====================================================
    async openNetworkTroublesModal() {
        try {
            const res = await fetch('api/street.php?action=network_troubles');
            const data = await res.json();
            if (data.success) {
                const banner = document.getElementById('netStatusBanner');
                const provEl = document.getElementById('netActiveProvider');
                const noteEl = document.getElementById('netStatusNote');

                if (data.has_glitch) {
                    if (banner) banner.className = "p-3.5 bg-rose-50 border border-rose-200 rounded-2xl flex items-center gap-3 text-xs";
                    if (provEl) provEl.textContent = "⚠️ Cellular Signal Outage (FCT)";
                    if (noteEl) noteEl.textContent = data.message;
                } else {
                    if (banner) banner.className = "p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-xs";
                    if (provEl) provEl.textContent = "Connected to MTN 4G LTE";
                    if (noteEl) noteEl.textContent = "All banking apps & POS transactions running smooth.";
                }
            }
            const modal = document.getElementById('networkTroublesModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        } catch(e) {
            this.notify('Network diagnostics error', 'error');
        }
    },

    async switchSim(simName) {
        const formData = new FormData();
        formData.append('net_action', 'switch_sim');
        formData.append('sim', simName);

        try {
            const res = await fetch('api/street.php?action=network_troubles', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('win');
                const txt = document.getElementById('headerNetworkText');
                const icn = document.getElementById('headerNetworkIcon');
                const provEl = document.getElementById('netActiveProvider');
                const noteEl = document.getElementById('netStatusNote');
                const banner = document.getElementById('netStatusBanner');

                if (txt) txt.textContent = `${simName} 5G`;
                if (icn) icn.className = "fa-solid fa-signal text-emerald-600 text-[11px]";
                if (banner) banner.className = "p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-xs";
                if (provEl) provEl.textContent = `Connected to ${simName} 5G High Speed`;
                if (noteEl) noteEl.textContent = data.message;
                this.notify(data.message, 'success');
            }
        } catch(e) {
            this.notify('SIM switch error', 'error');
        }
    },

    async toggleAirplaneMode() {
        const formData = new FormData();
        formData.append('net_action', 'airplane_mode');

        this.notify('✈️ Airplane mode toggled... cellular handshake refreshed.', 'info');
        try {
            const res = await fetch('api/street.php?action=network_troubles', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('win');
                const icn = document.getElementById('headerNetworkIcon');
                if (icn) icn.className = "fa-solid fa-signal text-emerald-600 text-[11px]";
                this.notify(data.message, 'success');
            }
        } catch(e) {
            this.notify('Airplane toggle failed', 'error');
        }
    },

    // ====================================================
    // START A BRAND NEW LIFE (REBIRTH WITHOUT LOGOUT)
    // ====================================================
    openStartNewLifeModal() {
        const modal = document.getElementById('startNewLifeModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    },

    async submitStartNewLife() {
        const name = document.getElementById('newLifeName')?.value?.trim();
        const gender = document.getElementById('newLifeGender')?.value || 'Male';
        const district = document.getElementById('newLifeDistrict')?.value || 'Kubwa';
        const archetype = document.getElementById('newLifeArchetype')?.value || 'hustler';

        if (!name) {
            this.notify('Please choose a name for your new life.', 'error');
            return;
        }

        const confirmed = confirm(`Are you sure you want to begin a NEW LIFE as ${name} in ${district}?\n\nThis will wipe your current character's slate clean with fresh funds for the new archetype. Your user login will stay active!`);
        if (!confirmed) return;

        const formData = new FormData();
        formData.append('new_name', name);
        formData.append('gender', gender);
        formData.append('district', district);
        formData.append('archetype', archetype);

        try {
            const res = await fetch('api/character.php?action=start_new_life', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('win');
                const modal = document.getElementById('startNewLifeModal');
                if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
                this.notify(data.message, 'success');
                await this.fetchCharacter();
                if (window.World3D) {
                    World3D.updateScene();
                }
            } else {
                this.playSfx('loss');
                this.notify(data.error || 'Rebirth failed', 'error');
            }
        } catch(e) {
            this.notify('Connection error during rebirth', 'error');
        }
    },

    // ====================================================
    // MAIN 4-PILL VIEW CONTROLLER (HOME | BUY | MAP | PHONE)
    // ====================================================
    switchMainView(viewName) {
        if (viewName === 'phone') {
            if (window.PhoneApp) {
                PhoneApp.toggle();
            }
            return;
        }

        localStorage.setItem('abuja_main_view', viewName);

        const views = {
            home: document.getElementById('mainView-home'),
            buy: document.getElementById('mainView-buy'),
            map: document.getElementById('mainView-map')
        };

        Object.keys(views).forEach(k => {
            if (views[k]) {
                if (k === viewName) {
                    views[k].classList.remove('hidden');
                } else {
                    views[k].classList.add('hidden');
                }
            }
        });

        // Update nav pill styling
        const navBtns = {
            home: document.getElementById('mainnav-home'),
            buy: document.getElementById('mainnav-buy'),
            map: document.getElementById('mainnav-map'),
            phone: document.getElementById('mainnav-phone')
        };

        Object.keys(navBtns).forEach(k => {
            const btn = navBtns[k];
            if (!btn) return;
            if (k === viewName) {
                btn.className = "main-nav-btn px-4 py-2 rounded-full font-extrabold text-xs bg-slate-900 text-white shadow-sm transition active:scale-95 flex items-center gap-1.5";
            } else {
                btn.className = "main-nav-btn px-4 py-2 rounded-full font-bold text-xs text-slate-600 hover:text-slate-900 transition active:scale-95 flex items-center gap-1.5";
            }
        });

        this.playSfx('click');

        if (viewName === 'map') {
            this.initInGameMap();
        }
    },

    cleanScreenMode: false,
    toggleCleanScreen() {
        this.cleanScreenMode = !this.cleanScreenMode;
        const quests = document.getElementById('homeQuestPills');
        const nav = document.getElementById('bottomNavPill');
        const vitals = document.getElementById('vitalsFloatingWidget');

        const elements = [quests, nav, vitals];
        elements.forEach(el => {
            if (!el) return;
            if (this.cleanScreenMode) {
                el.classList.add('opacity-0', 'pointer-events-none', 'transition-opacity');
            } else {
                el.classList.remove('opacity-0', 'pointer-events-none');
            }
        });
        this.notify(this.cleanScreenMode ? 'Clean screen enabled' : 'Clean screen disabled', 'info');
    },

    // ====================================================
    // HOME & FURNITURE INTERACTION
    // ====================================================
    async interactFurniture(itemId, subAction = 'interact') {
        const formData = new FormData();
        formData.append('item_id', itemId);
        formData.append('sub_action', subAction);

        try {
            const res = await fetch('api/character.php?action=interact_furniture', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx(itemId.includes('workstation') || itemId === 'laptop' ? 'money' : 'win');
                this.notify(data.message, 'success');
                await this.fetchCharacter();
            } else {
                this.playSfx('loss');
                this.notify(data.error || 'Action failed', 'error');
            }
        } catch(e) {
            this.notify('Connection error with house appliance', 'error');
        }
    },

    async buyFurniture(itemId) {
        const formData = new FormData();
        formData.append('item_id', itemId);

        try {
            const res = await fetch('api/character.php?action=buy_furniture', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('money');
                this.notify(data.message, 'success');
                await this.fetchCharacter();
                setTimeout(() => this.switchMainView('home'), 1000);
            } else {
                this.playSfx('loss');
                this.notify(data.error || 'Purchase failed', 'error');
            }
        } catch(e) {
            this.notify('Furniture store connection error', 'error');
        }
    },

    async claimDailyGem() {
        try {
            const res = await fetch('api/character.php?action=claim_gem', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                this.playSfx('money');
                this.notify(data.message, 'success');
                await this.fetchCharacter();
            } else {
                this.notify(data.error || 'Gem already claimed today!', 'info');
            }
        } catch(e) {
            this.notify('Gem network connection error', 'error');
        }
    },

    // ====================================================
    // DESTINATION TRAVEL & ACTIVITIES
    // ====================================================
    selectedDestId: null,
    destinationsMeta: {
        gym: { name: 'Maitama Executive Gym', sub: 'Maitama Highbrow', icon: '🏋️' },
        restaurant: { name: 'Jabi Lake Suya & Grill', sub: 'Jabi Waterfront', icon: '🍲' },
        banex: { name: 'Banex Plaza Tech Hub', sub: 'Wuse 2 Commercial', icon: '📱' },
        jabi_lake: { name: 'Jabi Lake Waterfront & Boat Club', sub: 'Jabi Lake Resort', icon: '🛥️' },
        secretariat: { name: 'Federal Secretariat Complex', sub: 'Central Area Ministries', icon: '🏛️' },
        market: { name: 'Wuse Modern Market', sub: 'Wuse Market Zone', icon: '🥬' },
        fraser: { name: 'Fraser Suites Presidential Hotel', sub: 'Central Business District', icon: '🏨' },
        cbd_bank: { name: 'CBD Financial Towers', sub: 'Banking & Arbitrage', icon: '💼' }
    },

    openTravelModal(destId) {
        this.selectedDestId = destId;
        const meta = this.destinationsMeta[destId] || { name: 'Abuja Destination', sub: 'Federal Capital Territory', icon: '📍' };
        
        const titleEl = document.getElementById('travelDestTitle');
        const subEl = document.getElementById('travelDestSubtitle');
        const iconEl = document.getElementById('travelDestIcon');

        if (titleEl) titleEl.textContent = meta.name;
        if (subEl) subEl.textContent = meta.sub;
        if (iconEl) iconEl.innerHTML = `<span class="text-xl">${meta.icon}</span>`;

        const modal = document.getElementById('travelModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            this.playSfx('click');
        }
    },

    closeTravelModal() {
        const modal = document.getElementById('travelModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    },

    async confirmTravel(mode) {
        const destId = this.selectedDestId || 'jabi_lake';
        this.closeTravelModal();

        const formData = new FormData();
        formData.append('destination_id', destId);
        formData.append('travel_mode', mode);

        try {
            const res = await fetch('api/character.php?action=travel_destination', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('click');
                this.notify(data.message, 'success');
                await this.fetchCharacter();
                setTimeout(() => {
                    this.openActivityModal(destId);
                }, 500);
            } else {
                this.playSfx('loss');
                this.notify(data.error || 'Travel failed', 'error');
            }
        } catch(e) {
            this.notify('Travel network error', 'error');
        }
    },

    openActivityModal(destId) {
        this.selectedDestId = destId;
        const meta = this.destinationsMeta[destId] || { name: 'Abuja Destination', sub: 'FCT', icon: '📍' };

        const titleEl = document.getElementById('activityHeaderTitle');
        const subEl = document.getElementById('activityHeaderSub');
        const iconEl = document.getElementById('activityHeaderIcon');
        const container = document.getElementById('activityCardsContainer');

        if (titleEl) titleEl.textContent = meta.name;
        if (subEl) subEl.textContent = meta.sub;
        if (iconEl) iconEl.innerHTML = `<span class="text-xl">${meta.icon}</span>`;

        if (!container) return;

        let html = '';
        if (destId === 'gym') {
            html = `
                <button onclick="GameApp.doDestinationActivity('gym', 'workout')" class="w-full p-4 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-lg">🏋️</div>
                        <div>
                            <strong class="text-xs text-emerald-950 block">Cardio & Heavy Bench Press</strong>
                            <span class="text-[11px] text-emerald-700">+20 Health, +8 Cred • Burns -20 Energy</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-800">₦2,500.00</span>
                </button>
            `;
        } else if (destId === 'restaurant') {
            html = `
                <button onclick="GameApp.doDestinationActivity('restaurant', 'jollof')" class="w-full p-3.5 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center text-lg">🍛</div>
                        <div>
                            <strong class="text-xs text-amber-950 block">Smoky Party Jollof & Asun</strong>
                            <span class="text-[11px] text-amber-700">+25 Energy, +25 Happiness</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-amber-800">₦3,500.00</span>
                </button>
                <button onclick="GameApp.doDestinationActivity('restaurant', 'tilapia')" class="w-full p-3.5 bg-teal-50 hover:bg-teal-100 border border-teal-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center text-lg">🐟</div>
                        <div>
                            <strong class="text-xs text-teal-950 block">Grilled Jabi Lake Tilapia & Plantain</strong>
                            <span class="text-[11px] text-teal-700">+40 Energy, +35 Happiness</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-teal-800">₦6,000.00</span>
                </button>
                <button onclick="GameApp.doDestinationActivity('restaurant', 'suya')" class="w-full p-3.5 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-orange-600 text-white flex items-center justify-center text-lg">🍢</div>
                        <div>
                            <strong class="text-xs text-orange-950 block">Abuja Special Beef Suya & Masa</strong>
                            <span class="text-[11px] text-orange-700">+20 Energy, +20 Happiness</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-orange-800">₦2,500.00</span>
                </button>
            `;
        } else if (destId === 'banex') {
            html = `
                <button onclick="GameApp.doDestinationActivity('banex_flip')" class="w-full p-4 bg-purple-50 hover:bg-purple-100/80 border border-purple-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center text-lg">📱</div>
                        <div>
                            <strong class="text-xs text-purple-950 block">Wholesale iPhone & Gadget Lot Flip</strong>
                            <span class="text-[11px] text-purple-700">Flipping electronics for ₦80k-₦115k payout!</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-purple-800">₦50,000.00 Cost</span>
                </button>
            `;
        } else if (destId === 'jabi_lake') {
            html = `
                <button onclick="GameApp.doDestinationActivity('lake_cruise')" class="w-full p-4 bg-sky-50 hover:bg-sky-100/80 border border-sky-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-600 text-white flex items-center justify-center text-lg">🛥️</div>
                        <div>
                            <strong class="text-xs text-sky-950 block">Sunset Speedboat Cruise & Drinks</strong>
                            <span class="text-[11px] text-sky-700">+40 Happiness, +10 Street Cred</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-sky-800">₦6,500.00</span>
                </button>
            `;
        } else if (destId === 'secretariat') {
            html = `
                <button onclick="GameApp.doDestinationActivity('tender_bid')" class="w-full p-4 bg-amber-50 hover:bg-amber-100/80 border border-amber-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center text-lg">🏛️</div>
                        <div>
                            <strong class="text-xs text-amber-950 block">Lobby for Ministry Procurement Tender</strong>
                            <span class="text-[11px] text-amber-700">65% chance of ₦350k - ₦850k contract payout!</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-amber-800">₦20,000 Form</span>
                </button>
            `;
        } else if (destId === 'cbd_bank' || destId === 'fraser') {
            html = `
                <button onclick="GameApp.doDestinationActivity('crypto_p2p')" class="w-full p-4 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-lg">🪙</div>
                        <div>
                            <strong class="text-xs text-emerald-950 block">OTC Dollar & P2P Crypto Arbitrage</strong>
                            <span class="text-[11px] text-emerald-700">+₦35,000 to ₦75,000 instant arbitrage profit</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-800">HIGH YIELD</span>
                </button>
            `;
        } else {
            html = `
                <button onclick="GameApp.doDestinationActivity('pos_kiosk')" class="w-full p-4 bg-blue-50 hover:bg-blue-100/80 border border-blue-200 rounded-2xl text-left transition active:scale-95 flex items-center justify-between group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-lg">🏪</div>
                        <div>
                            <strong class="text-xs text-blue-950 block">Run Market POS Cashout Kiosk</strong>
                            <span class="text-[11px] text-blue-700">+₦15k - ₦28k commissions, +6 Cred</span>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-blue-800">START BIZ</span>
                </button>
            `;
        }

        container.innerHTML = html;

        const modal = document.getElementById('destActivityModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            this.playSfx('click');
        }
    },

    closeActivityModal() {
        const modal = document.getElementById('destActivityModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    },

    async doDestinationActivity(act, sub = '') {
        const formData = new FormData();
        formData.append('activity', act);
        if (sub) formData.append('sub_activity', sub);

        try {
            const res = await fetch('api/character.php?action=do_destination_activity', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                this.playSfx('money');
                this.notify(data.message, 'success');
                await this.fetchCharacter();
                this.closeActivityModal();
            } else {
                this.playSfx('loss');
                this.notify(data.error || 'Activity failed', 'error');
            }
        } catch(e) {
            this.notify('Activity connection error', 'error');
        }
    },

    // ====================================================
    // CITIZEN MAP INTERACTIONS
    // ====================================================
    activeMapCitizen: null,
    inspectCitizenFromMap(name, username, job, district, cred = 50) {
        this.activeMapCitizen = { name, username: username.replace(/^@/, ''), job, district, cred };

        const nameEl = document.getElementById('mapCitizenName');
        const userEl = document.getElementById('mapCitizenUsername');
        const distEl = document.getElementById('mapCitizenDistrict');
        const jobEl = document.getElementById('mapCitizenJob');

        if (nameEl) nameEl.textContent = name;
        if (userEl) userEl.textContent = '@' + this.activeMapCitizen.username;
        if (distEl) distEl.textContent = district;
        if (jobEl) jobEl.textContent = job;

        const modal = document.getElementById('citizenMapModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            this.playSfx('click');
        }
    },

    chatWithMapCitizen() {
        if (!this.activeMapCitizen) return;
        const modal = document.getElementById('citizenMapModal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }

        if (window.PhoneApp) {
            if (!PhoneApp.isOpen) PhoneApp.toggle();
            PhoneApp.openConversationWithUser(this.activeMapCitizen.username, this.activeMapCitizen.name, '👤');
        }
    },

    transferToMapCitizen() {
        if (!this.activeMapCitizen) return;
        const modal = document.getElementById('citizenMapModal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }

        if (window.PhoneApp) {
            if (!PhoneApp.isOpen) PhoneApp.toggle();
            PhoneApp.openDirectTransferModal('@' + this.activeMapCitizen.username);
        }
    },

    chatWithProfileCitizen() {
        if (!this.activeInspectedCitizen) return;
        const modal = document.getElementById('citizenProfileModal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }

        const rawUser = (this.activeInspectedCitizen.username || '').replace(/^@/, '');
        if (window.PhoneApp) {
            if (!PhoneApp.isOpen) PhoneApp.toggle();
            PhoneApp.openConversationWithUser(rawUser, this.activeInspectedCitizen.full_name || rawUser, '👤');
        }
    },

    async hangoutWithProfileCitizen(type = 'drinks') {
        const c = this.activeInspectedCitizen;
        if (!c) return;

        const cost = 3500;
        if ((this.character?.cash || 0) < cost) {
            this.notify("You need at least ₦3,500 cash on hand to buy suya & chilled drinks!", "error");
            return;
        }

        const modal = document.getElementById('citizenProfileModal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }

        // Deduct cost and give bonuses
        const formData = new FormData();
        formData.append('item', 'suya_pack');
        try {
            await fetch('api/phone.php?action=order_food', { method: 'POST', body: formData });
        } catch(e) {}

        this.playSfx('win');
        this.notify(`🍢 Bought chilled drinks & hot Abuja suya with @${c.username.replace(/^@/, '')}! Relationship built (+3 Cred, +15 Happiness)`, 'success');
        await this.fetchCharacter();
    },

    async hangoutWithMapCitizen() {
        const c = this.activeMapCitizen;
        if (!c) return;

        const cost = 3500;
        if ((this.character?.cash || 0) < cost) {
            this.notify("You need at least ₦3,500 cash on hand to hangout!", "error");
            return;
        }

        const modal = document.getElementById('citizenMapModal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }

        const formData = new FormData();
        formData.append('item', 'suya_pack');
        try {
            await fetch('api/phone.php?action=order_food', { method: 'POST', body: formData });
        } catch(e) {}

        this.playSfx('win');
        this.notify(`🍢 Chilling at Jabi Waterfront with @${c.username}! Relationship & street connection strengthened (+3 Cred, +15 Happiness)`, 'success');
        await this.fetchCharacter();
    },

    greetMapCitizen() {
        if (!this.activeMapCitizen) return;
        const modal = document.getElementById('citizenMapModal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }

        this.playSfx('win');
        this.notify(`👋 You greeted @${this.activeMapCitizen.username}: "How far Chairman!" (+2 Social, +1 Cred)`, 'success');
    },

    // ====================================================
    // IN-GAME 3D MAP DRAGGING & ZOOMING
    // ====================================================
    initInGameMap() {
        if (window.Map3D && !window.Map3D.isInitialized) {
            window.Map3D.init('map3d-container');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    GameApp.init();
});
