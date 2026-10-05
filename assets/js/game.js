/**
 * Abuja Life RPG - Core Frontend Game Engine
 */

const GameApp = {
    character: null,
    netWorth: 0,
    activeTab: 'overview',
    pendingEvent: null,

    // Audio SFX using Web Audio API (Zero external assets needed!)
    playSfx(type = 'click') {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);

            if (type === 'money') {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08); // A5
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                osc.start();
                osc.stop(ctx.currentTime + 0.3);
            } else if (type === 'win') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(523.25, ctx.currentTime); // C5
                osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.09); // E5
                osc.frequency.setValueAtTime(783.99, ctx.currentTime + 0.18); // G5
                gain.gain.setValueAtTime(0.25, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                osc.start();
                osc.stop(ctx.currentTime + 0.4);
            } else if (type === 'loss') {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(300, ctx.currentTime);
                osc.frequency.linearRampToValueAtTime(150, ctx.currentTime + 0.25);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
                osc.start();
                osc.stop(ctx.currentTime + 0.25);
            } else {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(440, ctx.currentTime);
                gain.gain.setValueAtTime(0.1, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.08);
                osc.start();
                osc.stop(ctx.currentTime + 0.08);
            }
        } catch (e) {
            // Ignore audio context errors if browser autoplay blocked
        }
    },

    formatNaira(amount) {
        return '₦' + Number(amount || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    notify(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl text-xs font-semibold backdrop-blur transform transition-all duration-300 translate-y-2 opacity-0 z-50 border ${
            type === 'success' ? 'bg-emerald-950/90 text-emerald-300 border-emerald-500/50' :
            type === 'error' ? 'bg-rose-950/90 text-rose-300 border-rose-500/50' :
            'bg-slate-900/90 text-slate-200 border-slate-700'
        }`;
        
        const icon = type === 'success' ? 'fa-circle-check text-emerald-400' :
                     type === 'error' ? 'fa-triangle-exclamation text-rose-400' : 'fa-circle-info text-teal-400';
        
        toast.innerHTML = `<i class="fa-solid ${icon} text-base"></i><span>${message}</span>`;
        
        const container = document.getElementById('toastContainer');
        if (container) {
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 4500);
        }
    },

    async init() {
        await this.fetchCharacter();
        this.switchTab('overview');
        this.checkForRandomEvent();
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
        } catch (err) {
            console.error('Error fetching character:', err);
        }
    },

    renderHUD() {
        if (!this.character) return;
        const c = this.character;

        // Player Profile info
        document.getElementById('hudName').textContent = c.full_name;
        document.getElementById('hudAge').textContent = `${c.age} yrs (Day ${c.days_lived})`;
        document.getElementById('hudDistrict').textContent = c.district;
        document.getElementById('hudJob').textContent = c.job_title || 'Unemployed';
        document.getElementById('hudEducation').textContent = c.education_level;

        // Financials
        document.getElementById('hudCash').textContent = this.formatNaira(c.cash);
        document.getElementById('hudBank').textContent = this.formatNaira(c.bank);
        document.getElementById('hudNetWorth').textContent = this.formatNaira(this.netWorth);

        // Stats bars
        this.updateStatBar('barEnergy', 'valEnergy', c.energy, 100, '%');
        this.updateStatBar('barHealth', 'valHealth', c.health, 100, '%');
        this.updateStatBar('barHappiness', 'valHappiness', c.happiness, 100, '%');
        this.updateStatBar('barIntelligence', 'valIntelligence', c.intelligence, 100, ' IQ');
        this.updateStatBar('barStreetCred', 'valStreetCred', c.street_cred, 100, ' Cred');

        // Car & Property
        const carEl = document.getElementById('hudVehicle');
        if (carEl) carEl.textContent = c.vehicle_name ? `${c.vehicle_name}` : 'Public Transport';
        const houseEl = document.getElementById('hudHouse');
        if (houseEl) houseEl.textContent = c.property_name ? `${c.property_name}` : 'Renting Self-Con';
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
            container.innerHTML = `<p class="text-xs text-slate-500 py-4 text-center">No recent activities recorded.</p>`;
            return;
        }

        container.innerHTML = logs.map(l => {
            let cashBadge = '';
            if (parseFloat(l.cash_change) > 0) {
                cashBadge = `<span class="text-emerald-400 font-semibold">+${this.formatNaira(l.cash_change)}</span>`;
            } else if (parseFloat(l.cash_change) < 0) {
                cashBadge = `<span class="text-rose-400 font-semibold">-${this.formatNaira(Math.abs(l.cash_change))}</span>`;
            }

            return `
                <div class="flex items-start justify-between gap-3 py-2.5 border-b border-slate-800/80 text-xs">
                    <div class="flex items-start gap-2.5">
                        <div class="w-6 h-6 rounded-md bg-slate-800 text-slate-300 flex items-center justify-center text-[10px] shrink-0 mt-0.5">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <p class="text-slate-300 leading-snug">${l.message}</p>
                    </div>
                    <div class="shrink-0 text-right">
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
            btn.classList.remove('bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500');
            btn.classList.add('text-slate-400', 'border-transparent');
        });

        const activeContent = document.getElementById(`tab-${tabId}`);
        if (activeContent) activeContent.classList.remove('hidden');

        const activeBtn = document.getElementById(`btn-tab-${tabId}`);
        if (activeBtn) {
            activeBtn.classList.add('bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500');
            activeBtn.classList.remove('text-slate-400', 'border-transparent');
        }

        // Lazy load tab specific contents
        if (tabId === 'jobs') this.loadJobs();
        if (tabId === 'education') this.loadEducation();
        if (tabId === 'hustles') this.loadHustles();
        if (tabId === 'realestate') this.loadRealEstate();
        if (tabId === 'vehicles') this.loadVehicles();
        if (tabId === 'lifestyle') this.loadLifestyle();
        if (tabId === 'bank') this.loadBank();
        if (tabId === 'leaderboard') this.loadLeaderboard();
        if (tabId === 'districts') this.loadDistricts();
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

    async visitHospital() {
        try {
            const res = await fetch('api/character.php?action=hospital', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                this.playSfx('win');
                this.notify(data.message, 'success');
                await this.fetchCharacter();
            } else {
                this.playSfx('loss');
                this.notify(data.error, 'error');
            }
        } catch (err) {
            this.notify('Hospital error.', 'error');
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
                <div class="bg-slate-900 border ${isCurrent ? 'border-emerald-500 ring-1 ring-emerald-500/30' : 'border-slate-800'} rounded-2xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-800 text-emerald-400 border border-slate-700">${j.category}</span>
                            <span class="text-xs font-mono font-bold text-amber-300">${this.formatNaira(j.daily_salary)}/day</span>
                        </div>
                        <h4 class="font-bold text-base text-white mb-1">${j.title}</h4>
                        <p class="text-xs text-slate-400 leading-relaxed mb-4">${j.description}</p>
                        <div class="flex items-center gap-4 text-[11px] text-slate-400 mb-4 bg-slate-950/50 p-2.5 rounded-xl border border-slate-800/80">
                            <span><i class="fa-solid fa-graduation-cap text-teal-400 mr-1"></i> Req: <strong>${j.required_education}</strong></span>
                            <span><i class="fa-solid fa-brain text-purple-400 mr-1"></i> Min IQ: <strong>${j.required_intelligence}</strong></span>
                            <span><i class="fa-solid fa-bolt text-amber-400 mr-1"></i> Energy: <strong>${j.energy_cost}%</strong></span>
                        </div>
                    </div>
                    <div>
                        ${isCurrent ? `
                            <div class="flex gap-2">
                                <button onclick="GameApp.workShift()" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-emerald-950/40 transition">
                                    <i class="fa-solid fa-briefcase mr-1"></i> Work Shift
                                </button>
                                <button onclick="GameApp.resignJob()" class="px-3 py-2.5 bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-800 rounded-xl font-semibold text-xs transition">
                                    Resign
                                </button>
                            </div>
                        ` : `
                            <button onclick="GameApp.applyJob(${j.id})" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-semibold text-xs border border-slate-700 transition">
                                Apply for Position
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

    async workShift() {
        const res = await fetch('api/jobs.php?action=work', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            this.playSfx('money');
            this.notify(data.message, 'success');
            await this.fetchCharacter();
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

    // --- EDUCATION ---
    async loadEducation() {
        const res = await fetch('api/education.php?action=list');
        const data = await res.json();
        const container = document.getElementById('educationListContainer');
        if (!container || !data.success) return;

        container.innerHTML = data.courses.map(c => `
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-teal-950 text-teal-400 border border-teal-800/60">${c.institution}</span>
                        <span class="text-xs font-mono font-bold text-emerald-400">${this.formatNaira(c.cost)}</span>
                    </div>
                    <h4 class="font-bold text-base text-white mb-1">${c.name}</h4>
                    <p class="text-xs text-slate-400 leading-relaxed mb-4">${c.description}</p>
                    <div class="flex items-center gap-4 text-[11px] text-slate-300 mb-4 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800">
                        <span><i class="fa-solid fa-brain text-purple-400 mr-1"></i> IQ Boost: <strong>+${c.intelligence_gain}</strong></span>
                        <span><i class="fa-solid fa-stamp text-amber-400 mr-1"></i> Awards: <strong>${c.qualification}</strong></span>
                    </div>
                </div>
                <button onclick="GameApp.enrollCourse(${c.id})" class="w-full py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-teal-950/30 transition">
                    Enroll & Study
                </button>
            </div>
        `).join('');
    },

    async enrollCourse(courseId) {
        const formData = new FormData();
        formData.append('course_id', courseId);
        const res = await fetch('api/education.php?action=enroll', { method: 'POST', body: formData });
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

    // --- HUSTLES ---
    async loadHustles() {
        const res = await fetch('api/hustles.php?action=list');
        const data = await res.json();
        const container = document.getElementById('hustlesListContainer');
        if (!container || !data.success) return;

        container.innerHTML = data.hustles.map(h => `
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-lg mb-3 border border-amber-500/20">
                        <i class="fa-solid ${h.icon}"></i>
                    </div>
                    <h4 class="font-bold text-base text-white mb-1">${h.title}</h4>
                    <p class="text-xs text-slate-400 leading-relaxed mb-4">${h.desc}</p>
                    <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-300 mb-4 bg-slate-950/50 p-2.5 rounded-xl border border-slate-800">
                        <div>Req Cash: <strong>${this.formatNaira(h.min_cash)}</strong></div>
                        <div>Req Cred: <strong>${h.min_cred}</strong></div>
                        <div>Energy: <strong>${h.energy}%</strong></div>
                    </div>
                </div>
                <button onclick="GameApp.performHustle('${h.id}')" class="w-full py-2.5 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-950 font-extrabold text-xs rounded-xl shadow-lg shadow-amber-950/40 transition">
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
                containerOwned.innerHTML = `<p class="col-span-full text-xs text-slate-500 py-6 text-center">You don't own any real estate properties yet. Buy one below to collect daily rent!</p>`;
            } else {
                containerOwned.innerHTML = dataMine.properties.map(p => `
                    <div class="bg-slate-900 border border-emerald-500/40 rounded-2xl p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-400 border border-emerald-800">${p.district}</span>
                                <span class="text-xs font-mono font-bold text-emerald-300">${p.is_rented_out ? 'Yield: ' + this.formatNaira(p.daily_rent_yield) + '/day' : 'Owner Occupied'}</span>
                            </div>
                            <h4 class="font-bold text-base text-white mb-1">${p.name}</h4>
                            <p class="text-xs text-slate-400 mb-3">${p.description}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="GameApp.toggleRentProperty(${p.ownership_id})" class="flex-1 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold rounded-xl transition">
                                ${p.is_rented_out ? 'Evict / Move In' : 'Rent Out for Income'}
                            </button>
                            <button onclick="GameApp.sellProperty(${p.ownership_id})" class="px-3 py-2 bg-rose-950 hover:bg-rose-900 text-rose-300 text-xs font-bold rounded-xl border border-rose-800 transition">
                                Sell
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        }

        if (containerMarket && dataAll.success) {
            containerMarket.innerHTML = dataAll.properties.map(p => `
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-800 text-teal-400 border border-slate-700">${p.district}</span>
                            <span class="text-xs font-mono font-bold text-emerald-400">${this.formatNaira(p.price)}</span>
                        </div>
                        <h4 class="font-bold text-base text-white mb-1">${p.name}</h4>
                        <p class="text-xs text-slate-400 leading-relaxed mb-4">${p.description}</p>
                        <div class="text-[11px] text-slate-300 mb-4 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800 flex justify-between">
                            <span>Daily Rent: <strong>${this.formatNaira(p.daily_rent_yield)}</strong></span>
                            <span>Prestige: <strong>+${p.prestige_points}</strong></span>
                        </div>
                    </div>
                    <button onclick="GameApp.buyProperty(${p.id})" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-emerald-950/40 transition">
                        Buy Property
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
        if (!confirm('Are you sure you want to liquidate this property?')) return;
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
                containerOwned.innerHTML = `<p class="col-span-full text-xs text-slate-500 py-6 text-center">Garage empty. Buy your first car below to hit Abuja roads!</p>`;
            } else {
                containerOwned.innerHTML = dataMine.vehicles.map(v => `
                    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-800 text-purple-400 border border-slate-700">${v.brand}</span>
                                <span class="text-xs font-mono font-bold text-amber-300">Upkeep: ${this.formatNaira(v.daily_upkeep)}/day</span>
                            </div>
                            <h4 class="font-bold text-base text-white mb-1">${v.name}</h4>
                            <p class="text-xs text-slate-400 mb-4">${v.description}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="GameApp.cruiseVehicle()" class="flex-1 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white rounded-xl font-bold text-xs shadow-lg transition">
                                <i class="fa-solid fa-car-side mr-1"></i> Cruise Abuja
                            </button>
                            <button onclick="GameApp.sellVehicle(${v.ownership_id})" class="px-3 py-2 bg-rose-950 hover:bg-rose-900 text-rose-300 text-xs font-bold rounded-xl border border-rose-800 transition">
                                Sell
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        }

        if (containerMarket && dataAll.success) {
            containerMarket.innerHTML = dataAll.vehicles.map(v => `
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-800 text-purple-400 border border-slate-700">${v.brand}</span>
                            <span class="text-xs font-mono font-bold text-emerald-400">${this.formatNaira(v.price)}</span>
                        </div>
                        <h4 class="font-bold text-base text-white mb-1">${v.name}</h4>
                        <p class="text-xs text-slate-400 leading-relaxed mb-4">${v.description}</p>
                        <div class="flex justify-between text-[11px] text-slate-300 mb-4 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800">
                            <span>Road Cred: <strong>+${v.cred_bonus}</strong></span>
                            <span>Daily Fuel/Upkeep: <strong>${this.formatNaira(v.daily_upkeep)}</strong></span>
                        </div>
                    </div>
                    <button onclick="GameApp.buyVehicle(${v.id})" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-bold text-xs border border-slate-700 transition">
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
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-400 flex items-center justify-center text-lg mb-3 border border-teal-500/20">
                        <i class="fa-solid ${a.icon}"></i>
                    </div>
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="font-bold text-base text-white">${a.name}</h4>
                        <span class="text-xs font-mono font-bold text-emerald-400">${this.formatNaira(a.cost)}</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed mb-4">${a.desc}</p>
                    <div class="flex items-center gap-3 text-[11px] text-slate-300 mb-4 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800">
                        <span><i class="fa-solid fa-face-smile text-emerald-400 mr-1"></i> +${a.hap_gain} Happiness</span>
                        <span><i class="fa-solid fa-bolt text-amber-400 mr-1"></i> -${a.energy}% Energy</span>
                    </div>
                </div>
                <button onclick="GameApp.performLifestyle('${a.id}')" class="w-full py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white rounded-xl font-bold text-xs shadow-lg transition">
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

    // --- BANK & FINANCES ---
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

    // --- CASINO & BETTING ---
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

    // --- DISTRICTS ---
    loadDistricts() {
        const container = document.getElementById('districtsListContainer');
        if (!container || !this.districts) return;

        container.innerHTML = Object.entries(this.districts).map(([name, info]) => {
            const isCurrent = this.character.district === name;
            return `
                <div class="bg-slate-900 border ${isCurrent ? 'border-emerald-500 ring-1 ring-emerald-500/30' : 'border-slate-800'} rounded-2xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-800 text-teal-300 border border-slate-700">Tier ${info.tier}</span>
                            <span class="text-xs font-mono font-bold text-amber-300">Fare: ${this.formatNaira(info.travel_cost)}</span>
                        </div>
                        <h4 class="font-bold text-base text-white mb-1">${name}</h4>
                        <p class="text-xs text-slate-400 mb-4">${info.desc}</p>
                        <p class="text-[11px] text-slate-300 mb-4 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800">
                            Min Cred: <strong>${info.min_cred}</strong>
                        </p>
                    </div>
                    ${isCurrent ? `
                        <div class="text-center py-2.5 bg-emerald-950/60 text-emerald-300 border border-emerald-800 rounded-xl font-bold text-xs">
                            <i class="fa-solid fa-location-dot mr-1"></i> Current Location
                        </div>
                    ` : `
                        <button onclick="GameApp.relocateDistrict('${name}')" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-bold text-xs border border-slate-700 transition">
                            Travel / Relocate
                        </button>
                    `}
                </div>
            `;
        }).join('');
    },

    async relocateDistrict(districtName) {
        const formData = new FormData();
        formData.append('district', districtName);
        const res = await fetch('api/character.php?action=relocate', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            this.playSfx('win');
            this.notify(data.message, 'success');
            await this.fetchCharacter();
            this.loadDistricts();
        } else {
            this.playSfx('loss');
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
                <div class="flex items-center justify-between py-2.5 border-b border-slate-800 text-xs">
                    <div class="flex items-center gap-3">
                        <span class="w-5 text-center font-bold text-slate-400">#${idx + 1}</span>
                        <div>
                            <span class="font-bold text-white">${p.full_name}</span>
                            <span class="text-slate-500 block text-[10px]">${p.district} • ${p.job_title || 'Citizen'}</span>
                        </div>
                    </div>
                    <span class="font-mono font-bold text-emerald-400">${this.formatNaira(p.calculated_net_worth)}</span>
                </div>
            `).join('');
        }

        if (containerCred && data.street_kings) {
            containerCred.innerHTML = data.street_kings.map((p, idx) => `
                <div class="flex items-center justify-between py-2.5 border-b border-slate-800 text-xs">
                    <div class="flex items-center gap-3">
                        <span class="w-5 text-center font-bold text-slate-400">#${idx + 1}</span>
                        <div>
                            <span class="font-bold text-white">${p.full_name}</span>
                            <span class="text-slate-500 block text-[10px]">${p.district}</span>
                        </div>
                    </div>
                    <span class="font-mono font-bold text-amber-400">${p.street_cred} Cred</span>
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
        } catch (e) {
            // Ignore background check failure
        }
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

    async logout() {
        const res = await fetch('api/auth.php?action=logout');
        const data = await res.json();
        window.location.href = data.redirect || 'index.php';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    GameApp.init();
});
