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

    switchTab(tabId) {
        this.activeTab = tabId;
        
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

    async goToWork() {
        try {
            const res = await fetch('api/character.php?action=go_to_work', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                this.playSfx('money');
                this.notify(data.message, 'success');
                await this.fetchCharacter();
            } else {
                this.playSfx('loss');
                this.notify(data.error, 'error');
            }
        } catch (e) {
            this.notify("Work commute error", "error");
        }
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
    }
};

document.addEventListener('DOMContentLoaded', () => {
    GameApp.init();
});
