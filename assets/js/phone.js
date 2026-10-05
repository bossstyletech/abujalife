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

        const formData = new FormData();
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
    }
};

window.PhoneApp = PhoneApp;
