/**
 * Abuja Life - Interactive In-Game Smartphone (AbujaPhone Pro)
 * Play mini-games, send money on OPay/Kuda, chat on WhatsApp, order Bolt rides, and style your wardrobe.
 */

const PhoneApp = {
    isOpen: false,
    currentApp: 'home', // 'home', 'bank', 'games', 'chat', 'rides', 'wardrobe'

    toggle() {
        this.isOpen = !this.isOpen;
        const phoneModal = document.getElementById('phoneWidgetModal');
        if (!phoneModal) return;

        if (this.isOpen) {
            phoneModal.classList.remove('hidden');
            phoneModal.classList.add('flex');
            GameApp.playSfx('click');
            this.openApp('home');
            this.updateStatusBar();
        } else {
            phoneModal.classList.add('hidden');
            phoneModal.classList.remove('flex');
        }
    },

    updateStatusBar() {
        const timeEl = document.getElementById('phoneTime');
        if (timeEl) {
            const now = new Date();
            timeEl.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    },

    openApp(appName) {
        this.currentApp = appName;
        document.querySelectorAll('.phone-screen').forEach(el => el.classList.add('hidden'));

        const target = document.getElementById(`phone-app-${appName}`);
        if (target) {
            target.classList.remove('hidden');
        }

        // Lazy load specific app views
        if (appName === 'naijagram') this.loadNaijaGramApp();
        if (appName === 'contacts') this.loadContactsApp();
        if (appName === 'bank') this.loadBankApp();
        if (appName === 'chat') this.loadChatApp();
        if (appName === 'wardrobe') this.loadWardrobeApp();
    },

    goHome() {
        this.openApp('home');
        GameApp.playSfx('click');
    },

    // --- 1. ABUJAPAY (BANKING APP) ---
    loadBankApp() {
        const char = GameApp.character || {};
        document.getElementById('phoneBankBalance').textContent = GameApp.formatNaira(char.bank);
        document.getElementById('phoneCashBalance').textContent = GameApp.formatNaira(char.cash);
    },

    async quickTransfer() {
        const amount = prompt("Enter amount to transfer to savings (₦):", "5000");
        if (!amount || isNaN(amount) || Number(amount) <= 0) return;

        const formData = new FormData();
        formData.append('amount', amount);
        const res = await fetch('api/bank.php?action=deposit', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            GameApp.playSfx('money');
            GameApp.notify(data.message, 'success');
            await GameApp.fetchCharacter();
            this.loadBankApp();
        } else {
            GameApp.notify(data.error, 'error');
        }
    },

    async buyAirtime() {
        const cost = 1000;
        const char = GameApp.character || {};
        if ((float = Number(char.cash)) < cost) {
            alert("Insufficient cash for airtime recharge!");
            return;
        }

        const formData = new FormData();
        formData.append('amount', cost);
        // Deduct via lifestyle / bank
        GameApp.notify("Airtime VTU recharge successful! ₦1,000 + 5GB Data credited.", 'success');
        GameApp.playSfx('win');
    },

    // --- 2. GAMES APP ---
    playTrivia() {
        const questions = [
            { q: "What is the official slogan of Abuja?", options: ["Centre of Excellence", "Centre of Unity", "Seat of Power"], ans: 1 },
            { q: "Which mountain overlooks the Federal Capital City?", options: ["Olumo Rock", "Zuma Rock", "Aso Rock"], ans: 2 },
            { q: "Where can you buy the hottest evening suya in Abuja?", options: ["Millennium Park", "Airport Tarmac", "Berger Roundabout"], ans: 0 },
            { q: "Which district houses the Presidential Villa and Diplomatic Zones?", options: ["Kubwa", "Asokoro & Maitama", "Lugbe"], ans: 1 }
        ];

        const item = questions[Math.floor(Math.random() * questions.length)];
        const choice = prompt(`${item.q}\n1: ${item.options[0]}\n2: ${item.options[1]}\n3: ${item.options[2]}\n(Enter 1, 2, or 3):`);

        if (choice === null) return;
        const selectedIdx = parseInt(choice) - 1;

        if (selectedIdx === item.ans) {
            GameApp.playSfx('win');
            alert("Correct! You won ₦5,000 trivia bonus!");
            // Award cash bonus
            fetch('api/bank.php?action=withdraw', { method: 'POST' }); // or direct notify
            GameApp.notify("Trivia Champion! +₦5,000 added.", 'success');
        } else {
            GameApp.playSfx('loss');
            alert(`Incorrect! The right answer was: ${item.options[item.ans]}`);
        }
    },

    async playDiceMinigame() {
        const bet = 2000;
        const char = GameApp.character || {};
        if (Number(char.cash) < bet) {
            alert("You need at least ₦2,000 cash to roll!");
            return;
        }

        const guess = confirm("Dice Roll Mini-Game!\nClick OK for HIGH (8-12) or Cancel for LOW (2-6)");
        const pred = guess ? 'high' : 'low';

        const formData = new FormData();
        formData.append('stake', bet);
        formData.append('prediction', pred);

        const res = await fetch('api/casino.php?action=dice', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            if (data.result === 'win') {
                GameApp.playSfx('win');
                alert(`WINNER! Rolled ${data.dice[0]} + ${data.dice[1]} = ${data.dice[2]}. Won ${GameApp.formatNaira(data.payout)}!`);
            } else {
                GameApp.playSfx('loss');
                alert(`Lost! Rolled ${data.dice[0]} + ${data.dice[1]} = ${data.dice[2]}. Better luck next time.`);
            }
            await GameApp.fetchCharacter();
        }
    },

    // --- 3. WHATSAPP CHAT APP ---
    loadChatApp() {
        const list = document.getElementById('phoneChatList');
        if (!list) return;

        const chats = [
            {
                name: "Landlord (Alhaji Garki)",
                msg: "Good day tenant. Please renew your water maintenance levy.",
                actionText: "Transfer ₦3,000",
                replyAction: () => this.handleChatBill(3000, "Paid water maintenance levy.")
            },
            {
                name: "Cousin Femi",
                msg: "Egbon! Billing hold me for school abeg send urgent 2k.",
                actionText: "Send ₦2,000",
                replyAction: () => this.handleChatBill(2000, "Sent ₦2,000 to cousin Femi. Good karma unlocked!")
            },
            {
                name: "Banex Dispatch Rider",
                msg: "Oga I don reach your gate with the hot shawarma.",
                actionText: "Accept Delivery",
                replyAction: () => {
                    GameApp.playSfx('win');
                    GameApp.notify("Received hot shawarma! Happiness +10%.", 'success');
                }
            }
        ];

        list.innerHTML = chats.map((c, i) => `
            <div class="bg-slate-100 hover:bg-slate-200/80 p-3 rounded-2xl mb-2 transition">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-bold text-xs text-slate-900">${c.name}</span>
                    <span class="text-[10px] text-emerald-600 font-semibold">Online</span>
                </div>
                <p class="text-xs text-slate-600 mb-2 leading-snug">${c.msg}</p>
                <button onclick="PhoneApp.chats[${i}].replyAction()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold shadow-sm transition active:scale-95">
                    ${c.actionText}
                </button>
            </div>
        `).join('');

        this.chats = chats;
    },

    async handleChatBill(amount, successMsg) {
        const char = GameApp.character || {};
        if (Number(char.cash) < amount) {
            alert(`You do not have ₦${amount} cash right now!`);
            return;
        }

        const formData = new FormData();
        formData.append('amount', amount);
        GameApp.playSfx('money');
        GameApp.notify(successMsg, 'success');
        await GameApp.fetchCharacter();
    },

    // --- 4. BOLT RIDE HAILING APP ---
    async orderBoltRide(districtName, cost) {
        const char = GameApp.character || {};
        if (Number(char.cash) < cost) {
            alert(`Insufficient cash! Bolt ride to ${districtName} costs ${GameApp.formatNaira(cost)}.`);
            return;
        }

        const formData = new FormData();
        formData.append('district', districtName);
        const res = await fetch('api/character.php?action=relocate', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            GameApp.playSfx('win');
            GameApp.notify(`Bolt arrived! Dropped you off safely in ${districtName}.`, 'success');
            await GameApp.fetchCharacter();
            this.goHome();
        } else {
            GameApp.notify(data.error, 'error');
        }
    },

    // --- 5. WARDROBE / JIJI STYLE APP ---
    loadWardrobeApp() {
        const char = GameApp.character || {};
        const outfitEl = document.getElementById('phoneSelectOutfit');
        if (outfitEl && char.outfit) {
            outfitEl.value = char.outfit;
        }
    },

    async saveWardrobeStyle() {
        const outfit = document.getElementById('phoneSelectOutfit').value;
        const hair = document.getElementById('phoneSelectHair').value;
        const char = GameApp.character || {};

        let cfg = { characterId: 'tunde', outfit: outfit, topType: outfit, hairStyle: hair };
        if (char.avatar && typeof char.avatar === 'string' && char.avatar.trim().startsWith('{')) {
            try { cfg = JSON.parse(char.avatar); } catch(e){}
        }
        cfg.outfit = outfit;
        cfg.topType = outfit;
        cfg.hairStyle = hair;

        const formData = new FormData();
        formData.append('avatar_config', JSON.stringify(cfg));
        formData.append('outfit', outfit);
        formData.append('hair_style', hair);

        const res = await fetch('api/character.php?action=update_looks', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            GameApp.playSfx('win');
            GameApp.notify("New outfit applied! Looking fresh on Abuja streets.", 'success');
            await GameApp.fetchCharacter();
            if (window.World3D) {
                World3D.updateScene();
            }
        }
    },

    // ====================================================
    // NAIJAGRAM (IN-GAME SOCIAL MEDIA FEED & CLOUT)
    // ====================================================
    socialClout: 2450,
    socialPosts: [
        {
            id: 1,
            author: "Senator Adeleke",
            handle: "@adeleke_fct",
            avatar: "👑",
            verified: true,
            time: "12m ago",
            content: "Just inspected the ongoing infrastructural modernization in Maitama and Asokoro. Abuja is rising! 🇳🇬✨",
            likes: 1240,
            userLiked: false,
            comments: ["Good job sir!", "Please look into Airport Road traffic o"]
        },
        {
            id: 2,
            author: "Tech Bro Kunle",
            handle: "@kunle_builds",
            avatar: "💻",
            verified: true,
            time: "34m ago",
            content: "Building fintech APIs from Wuse 2 with strong generator power. Lagos tech bros cannot relate to this peace of mind 🚀 #AbujaTech",
            likes: 856,
            userLiked: false,
            comments: ["Bro drop referral link!", "Generator fuel is ₦12k though 😂"]
        },
        {
            id: 3,
            author: "Abuja Gist Central",
            handle: "@abuja_gist",
            avatar: "🔥",
            verified: false,
            time: "1h ago",
            content: "WHO SPRAYED ₦500k CRISP NOTES AT THE ICC OWAMBE LAST NIGHT?! The praise singers are still dancing! Drop your identity! 👀 #OwambeSaturday",
            likes: 2100,
            userLiked: false,
            comments: ["It was Chairman!", "Money speaks in Abuja!"]
        },
        {
            id: 4,
            author: "Danfo Chronicles",
            handle: "@danfo_life",
            avatar: "🚐",
            verified: false,
            time: "2h ago",
            content: "Small rain drop like this, danfo fare from Berger to Lugbe jump from ₦500 to ₦1,000! Conductor say rain na luxury tax 😭 #DanfoRushHour",
            likes: 3410,
            userLiked: false,
            comments: ["True talk!", "Omo I had to enter okada"]
        }
    ],

    loadNaijaGramApp() {
        const feedContainer = document.getElementById('socialFeedList');
        const cloutEl = document.getElementById('socialFollowersCount');
        if (cloutEl) cloutEl.textContent = this.socialClout.toLocaleString();
        if (!feedContainer) return;

        feedContainer.innerHTML = this.socialPosts.map(p => `
            <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-3 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-slate-200 flex items-center justify-center text-xs">
                            ${p.avatar}
                        </div>
                        <div>
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-xs text-slate-900">${p.author}</span>
                                ${p.verified ? '<i class="fa-solid fa-circle-check text-blue-500 text-[9px]"></i>' : ''}
                            </div>
                            <span class="text-[10px] text-slate-400">${p.handle} • ${p.time}</span>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-slate-700 leading-snug">${p.content}</p>
                <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-[11px] text-slate-500">
                    <button onclick="PhoneApp.toggleLikePost(${p.id})" class="flex items-center gap-1 hover:text-rose-600 transition ${p.userLiked ? 'text-rose-600 font-bold' : ''}">
                        <i class="${p.userLiked ? 'fa-solid' : 'fa-regular'} fa-heart text-xs"></i> <span>${p.likes}</span>
                    </button>
                    <span class="text-[10px]"><i class="fa-regular fa-comment mr-1"></i> ${p.comments.length} comments</span>
                    <button onclick="GameApp.notify('Post shared to NaijaChat!', 'info')" class="hover:text-slate-900"><i class="fa-solid fa-share-nodes"></i></button>
                </div>
                ${p.comments.length > 0 ? `
                    <div class="bg-white/80 p-2 rounded-xl text-[10px] text-slate-600 border border-slate-100 space-y-1">
                        ${p.comments.slice(0, 2).map(c => `<div class="truncate">💬 <strong class="text-slate-700">Abuja Resident:</strong> ${c}</div>`).join('')}
                    </div>
                ` : ''}
            </div>
        `).join('');
    },

    toggleLikePost(postId) {
        const post = this.socialPosts.find(p => p.id === postId);
        if (!post) return;
        post.userLiked = !post.userLiked;
        post.likes += post.userLiked ? 1 : -1;
        GameApp.playSfx('click');
        this.loadNaijaGramApp();
    },

    usePostTemplate(type) {
        const input = document.getElementById('socialPostInput');
        if (!input) return;
        const char = GameApp.character || {};
        if (type === 'flaunt') {
            input.value = `Just counting ${GameApp.formatCompactNaira(char.cash)} cash reserves in my ${char.district || 'Abuja'} crib. Hard work pays! 🙏 #AbujaBigBoys`;
        } else if (type === 'traffic') {
            input.value = `This Berger to Airport road go-slow is crazy today! Rain or shine, we grind. #DanfoWahala 🚗`;
        } else if (type === 'wuse2') {
            input.value = `Live at Wuse 2 VIP lounge! Chilled drinks and hot suya on deck. Soft life only! 🥂✨ #AbujaNights`;
        }
    },

    publishSocialPost() {
        const input = document.getElementById('socialPostInput');
        if (!input || !input.value.trim()) {
            GameApp.notify("Type something to post on NaijaGram!", "error");
            return;
        }

        const text = input.value.trim();
        const char = GameApp.character || {};
        const newPost = {
            id: Date.now(),
            author: char.full_name || 'Abuja Hustler',
            handle: `@${(char.full_name || 'citizen').toLowerCase().replace(/\s+/g, '_')}`,
            avatar: "👤",
            verified: ((char.street_cred || 0) >= 50),
            time: "Just now",
            content: text,
            likes: Math.floor(Math.random() * 80) + 25,
            userLiked: true,
            comments: [
                "Oga show us the way! 🔥",
                "Senior man! Looking sharp as always 🙌",
                "Billing don land for this your post o 😂"
            ]
        };

        this.socialPosts.unshift(newPost);
        this.socialClout += Math.floor(Math.random() * 250) + 120;
        input.value = '';

        GameApp.playSfx('win');
        GameApp.notify("Update posted on NaijaGram! Clout +180, Street Cred +3.", "success");

        if (char.street_cred !== undefined) {
            char.street_cred = Math.min(100, parseInt(char.street_cred) + 3);
            char.happiness = Math.min(100, parseInt(char.happiness) + 5);
        }

        this.loadNaijaGramApp();
    },

    // ====================================================
    // NAIJACONNECT (VIP CONTACTS & ACTUAL RELATIONSHIPS)
    // ====================================================
    contacts: [
        {
            id: 'senator',
            name: "Senator Bello",
            title: "Aso Rock Power Broker",
            icon: "🏛️",
            tier: "Elite VIP",
            status: "In Senate Committee Session",
            relation: 45,
            favorDesc: "Aso Rock Federal Ministerial Recommendation (+₦150k / High Job Chance)",
            favorMinRelation: 60
        },
        {
            id: 'kunle',
            name: "Kunle (Tech Bro)",
            title: "Fintech Co-Founder (Jabi Hub)",
            icon: "💻",
            tier: "Tech Elite",
            status: "Coding from Wuse 2 Café",
            relation: 65,
            favorDesc: "Hookup with foreign remote freelance contract (+₦85,000)",
            favorMinRelation: 55
        },
        {
            id: 'alhajimusa',
            name: "Alhaji Musa Garki",
            title: "Real Estate & FX Dealer",
            icon: "💱",
            tier: "Commercial Don",
            status: "At Friday Juma'at Central Mosque",
            relation: 50,
            favorDesc: "Waive Agent & Agreement legal fees on your next rent",
            favorMinRelation: 65
        },
        {
            id: 'tailor',
            name: "Chioma Fashion",
            title: "Celebrity Tailor & Stylist",
            icon: "👗",
            tier: "Society Hub",
            status: "Fitting VIP Senator Kaftans",
            relation: 75,
            favorDesc: "Free VIP Aso-Ebi & Designer Outfit for Saturday Owambe",
            favorMinRelation: 70
        },
        {
            id: 'dpo',
            name: "Oga DPO Frank",
            title: "Airport Road Police Chief",
            icon: "👮",
            tier: "Security Chief",
            status: "On Patrol Highway",
            relation: 35,
            favorDesc: "VIP Police Road Escort Pass & cancel any LASTMA violation",
            favorMinRelation: 50
        },
        {
            id: 'mamangozi',
            name: "Mama Ngozi",
            title: "Compound Landlady & Gossip Queen",
            icon: "🏘️",
            tier: "Grassroots",
            status: "Supervising borehole water pump",
            relation: 80,
            favorDesc: "Compound peace guarantee & priority borehole water access",
            favorMinRelation: 60
        },
        {
            id: 'zainab',
            name: "Banker Zainab",
            title: "CBD Commercial Bank Manager",
            icon: "🏦",
            tier: "Financial VIP",
            status: "Reviewing loan portfolios in CBD",
            relation: 60,
            favorDesc: "Boost your commercial bank borrowing ceiling by ₦500,000",
            favorMinRelation: 70
        }
    ],

    loadContactsApp() {
        const container = document.getElementById('contactsListContainer');
        if (!container) return;

        container.innerHTML = this.contacts.map((c, idx) => `
            <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-3 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-sm shadow-sm">
                            ${c.icon}
                        </div>
                        <div>
                            <h5 class="font-extrabold text-xs text-slate-900 leading-tight">${c.name}</h5>
                            <span class="text-[10px] text-slate-500">${c.title}</span>
                        </div>
                    </div>
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">${c.tier}</span>
                </div>

                <div class="flex items-center justify-between text-[10px]">
                    <span class="text-slate-400">Status: <strong class="text-slate-600">${c.status}</strong></span>
                    <span class="font-mono font-bold text-indigo-600">${c.relation}% Trust</span>
                </div>

                <!-- Relationship Progress Bar -->
                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-indigo-600 h-full rounded-full transition-all" style="width: ${c.relation}%"></div>
                </div>

                <!-- Action Buttons Grid -->
                <div class="grid grid-cols-3 gap-1.5 pt-1 text-[10px]">
                    <button onclick="PhoneApp.callContact(${idx})" class="py-1 px-2 bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-bold rounded-xl transition active:scale-95 flex items-center justify-center gap-1">
                        <i class="fa-solid fa-phone text-emerald-600"></i> Call
                    </button>
                    <button onclick="PhoneApp.askFavor(${idx})" class="py-1 px-2 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-900 font-bold rounded-xl transition active:scale-95 flex items-center justify-center gap-1">
                        <i class="fa-solid fa-handshake text-indigo-600"></i> Favor
                    </button>
                    <button onclick="PhoneApp.sendGift(${idx})" class="py-1 px-2 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-900 font-bold rounded-xl transition active:scale-95 flex items-center justify-center gap-1">
                        <i class="fa-solid fa-gift text-amber-600"></i> ₦5k Gift
                    </button>
                </div>
            </div>
        `).join('');
    },

    callContact(idx) {
        const c = this.contacts[idx];
        if (!c) return;
        GameApp.playSfx('click');
        const dialogues = [
            `"${c.name} on the line: 'Hello chairman! Good to hear from you. The hustle in Abuja no dey sleep o. Stay focused!' (+5% rapport unlocked)"`,
            `"${c.name} answered: 'My guy! I dey meeting with some delegates right now, but I got your back anytime. Link up this weekend!' (+5% rapport unlocked)"`,
            `"${c.name}: 'Good timing! Was just thinking about that project we discussed. Keep doing good work!' (+5% rapport unlocked)"`
        ];
        const msg = dialogues[Math.floor(Math.random() * dialogues.length)];
        c.relation = Math.min(100, c.relation + 5);
        GameApp.notify(msg, 'success');
        this.loadContactsApp();
    },

    async askFavor(idx) {
        const c = this.contacts[idx];
        if (!c) return;

        if (c.relation < c.favorMinRelation) {
            GameApp.playSfx('loss');
            GameApp.notify(`${c.name} smiled: "Chairman, we never reach that level yet! You need at least ${c.favorMinRelation}% trust. Send urgent gift or call more often!"`, 'error');
            return;
        }

        GameApp.playSfx('win');
        if (c.id === 'senator') {
            GameApp.notify(`Senator Bello made a single phone call! You received a federal contract allowance grant (+₦150,000)!`, 'success');
            const res = await fetch('api/bank.php?action=deposit', {
                method: 'POST',
                body: new URLSearchParams({ amount: 150000 })
            });
            await GameApp.fetchCharacter();
        } else if (c.id === 'kunle') {
            GameApp.notify(`Kunle linked you directly with a London tech founder! Earned ₦85,000 facilitation fee.`, 'success');
            const res = await fetch('api/bank.php?action=deposit', {
                method: 'POST',
                body: new URLSearchParams({ amount: 85000 })
            });
            await GameApp.fetchCharacter();
        } else if (c.id === 'tailor') {
            GameApp.notify(`Chioma packed you a bespoke royal designer Senator outfit free of charge! Street cred +15.`, 'success');
            if (GameApp.character) GameApp.character.street_cred = Math.min(100, parseInt(GameApp.character.street_cred) + 15);
        } else if (c.id === 'dpo') {
            GameApp.notify(`Oga DPO Frank signed an official immunity VIP badge for your dashboard. No LASTMA extortion today!`, 'success');
        } else if (c.id === 'mamangozi') {
            GameApp.notify(`Mama Ngozi quelled all neighborhood gossip and gave you first turn at the compound borehole!`, 'success');
        } else if (c.id === 'alhajimusa') {
            GameApp.notify(`Alhaji Musa approved a 25% rent rebate voucher on your properties portfolio!`, 'success');
        } else {
            GameApp.notify(`Banker Zainab fast-tracked your credit application! Commercial limit increased.`, 'success');
        }

        c.relation = Math.max(20, c.relation - 15);
        this.loadContactsApp();
    },

    async sendGift(idx) {
        const c = this.contacts[idx];
        if (!c) return;
        const char = GameApp.character || {};
        const giftCost = 5000;

        if (parseFloat(char.cash || 0) < giftCost) {
            GameApp.notify(`Insufficient cash! You need ₦5,000 in cash to send an appreciation gift.`, 'error');
            return;
        }

        const formData = new FormData();
        formData.append('amount', giftCost);
        try {
            await fetch('api/bank.php?action=deposit', { method: 'POST', body: formData });
        } catch(e){}

        if (char.cash !== undefined) {
            char.cash = Math.max(0, parseFloat(char.cash) - giftCost);
        }

        c.relation = Math.min(100, c.relation + 15);
        GameApp.playSfx('money');
        GameApp.notify(`Sent ₦5,000 appreciation gift to ${c.name}! Relationship increased to ${c.relation}%.`, 'success');
        await GameApp.fetchCharacter();
        this.loadContactsApp();
    }
};

window.PhoneApp = PhoneApp;
