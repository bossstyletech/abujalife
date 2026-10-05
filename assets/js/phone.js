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
        if (appName === 'chowdeck') this.loadChowdeckApp();
        if (appName === 'wardrobe') this.loadWardrobeApp();
    },

    goHome() {
        this.openApp('home');
        GameApp.playSfx('click');
    },

    // --- 1. ABUJAPAY (BANKING APP) ---
    loadBankApp() {
        const char = GameApp.character || {};
        const bankEl = document.getElementById('phoneBankBalance');
        const cashEl = document.getElementById('phoneCashBalance');
        const loanEl = document.getElementById('phoneLoanBalance');
        if (bankEl) bankEl.textContent = GameApp.formatNaira(char.bank);
        if (cashEl) cashEl.textContent = GameApp.formatNaira(char.cash);
        if (loanEl) loanEl.textContent = GameApp.formatNaira(char.loan_balance || 0);
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
        if (Number(char.cash) < cost) {
            GameApp.notify("Insufficient cash on hand for airtime recharge!", 'error');
            return;
        }

        const formData = new FormData();
        formData.append('amount', cost);
        try {
            await fetch('api/bank.php?action=deposit', { method: 'POST', body: formData });
        } catch(e){}
        if (char.cash !== undefined) {
            char.cash = Math.max(0, Number(char.cash) - cost);
        }
        GameApp.notify("Airtime VTU recharge successful! ₦1,000 + 5GB Data credited to MTN 5G line.", 'success');
        GameApp.playSfx('win');
        await GameApp.fetchCharacter();
        this.loadBankApp();
    },

    async applyMicroloan() {
        const confirmed = confirm("AbujaPay Instant Microloan\nBorrow ₦10,000 emergency cash at 5% interest (Repay ₦10,500)?\nZero collateral required.");
        if (!confirmed) return;

        const formData = new FormData();
        formData.append('amount', 10000);
        try {
            const res = await fetch('api/phone.php?action=microloan', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                GameApp.playSfx('money');
                GameApp.notify(data.message, 'success');
                await GameApp.fetchCharacter();
                this.loadBankApp();
            } else {
                GameApp.notify(data.error || 'Loan application declined.', 'error');
            }
        } catch(e) {
            GameApp.notify('Network error processing microloan', 'error');
        }
    },

    // --- 2. CHOWDECK FOOD DELIVERY APP ---
    loadChowdeckApp() {
        // App is statically rendered in HTML; buttons link to orderFood
    },

    async orderFood(itemKey) {
        const formData = new FormData();
        formData.append('item', itemKey);
        try {
            const res = await fetch('api/phone.php?action=order_food', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                GameApp.playSfx('win');
                GameApp.notify(data.message, 'success');
                await GameApp.fetchCharacter();
            } else {
                GameApp.playSfx('loss');
                GameApp.notify(data.error || 'Failed to complete order.', 'error');
            }
        } catch(e) {
            GameApp.notify('Chowdeck delivery network error', 'error');
        }
    },

    // --- 1.1 DIRECT ABUJAPAY PEER TRANSFER FROM PHONE ---
    openDirectTransferModal(prefilledUsername = '') {
        const modal = document.getElementById('peerTransferModal');
        if (!modal) return;
        const nameInput = document.getElementById('transferRecipientName');
        const idInput = document.getElementById('transferRecipientId');
        const amtInput = document.getElementById('transferAmountInput');
        const formView = document.getElementById('transferFormView');
        const recView = document.getElementById('transferReceiptView');

        if (formView) formView.classList.remove('hidden');
        if (recView) recView.classList.add('hidden');
        if (nameInput) {
            nameInput.value = prefilledUsername ? (prefilledUsername.startsWith('@') ? prefilledUsername : '@' + prefilledUsername) : '';
            setTimeout(() => nameInput.focus(), 50);
        }
        if (idInput) idInput.value = '';
        if (amtInput) amtInput.value = '';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    },

    // --- 3. WHATSAPP CHAT APP (NaijaChat Live Messaging & Status) ---
    currentChatTab: 'chats',
    activeChatTarget: null,

    switchChatTab(tab) {
        this.currentChatTab = tab;
        const chatsList = document.getElementById('phoneChatList');
        const statusList = document.getElementById('phoneStatusList');
        const searchBox = document.getElementById('phoneChatSearchInput')?.parentElement?.parentElement;
        const activeBar = document.getElementById('phoneActiveCitizensBar')?.parentElement;
        const btnChats = document.getElementById('chatTabChats');
        const btnStatus = document.getElementById('chatTabStatus');

        if (tab === 'chats') {
            if (chatsList) chatsList.classList.remove('hidden');
            if (statusList) statusList.classList.add('hidden');
            if (searchBox) searchBox.classList.remove('hidden');
            if (activeBar) activeBar.classList.remove('hidden');
            if (btnChats) { btnChats.className = 'px-2 py-0.5 rounded-md bg-white text-slate-800 shadow-sm'; }
            if (btnStatus) { btnStatus.className = 'px-2 py-0.5 rounded-md text-slate-500 hover:text-slate-800'; }
            this.loadChatApp();
        } else {
            if (chatsList) chatsList.classList.add('hidden');
            if (statusList) statusList.classList.remove('hidden');
            if (searchBox) searchBox.classList.add('hidden');
            if (activeBar) activeBar.classList.add('hidden');
            if (btnStatus) { btnStatus.className = 'px-2 py-0.5 rounded-md bg-white text-slate-800 shadow-sm'; }
            if (btnChats) { btnChats.className = 'px-2 py-0.5 rounded-md text-slate-500 hover:text-slate-800'; }
            this.loadStatusApp();
        }
    },

    async loadChatApp() {
        const list = document.getElementById('phoneChatList');
        const citizensBar = document.getElementById('phoneActiveCitizensBar');
        if (!list) return;

        list.innerHTML = `<div class="p-4 text-center text-xs text-slate-400 animate-pulse"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Syncing Abuja NaijaChat...</div>`;

        try {
            const res = await fetch('api/phone.php?action=get_chat_threads');
            const data = await res.json();
            if (!data.success) {
                list.innerHTML = `<p class="p-3 text-xs text-rose-500 text-center">Failed to load chats</p>`;
                return;
            }

            // Render Active Citizens Horizontal Bar
            if (citizensBar) {
                const citizens = data.available_citizens || [];
                if (citizens.length === 0) {
                    citizensBar.innerHTML = `<span class="text-[10px] text-slate-400">No other citizens registered yet</span>`;
                } else {
                    citizensBar.innerHTML = citizens.map(c => `
                        <button onclick="PhoneApp.openConversationWithUser('${c.raw_username}', '${(c.name || c.username).replace(/'/g, "\\'")}', '${c.avatar}')" class="flex-shrink-0 flex items-center gap-1.5 px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 rounded-full shadow-sm text-slate-800 transition active:scale-95 group">
                            <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-800 text-[10px] flex items-center justify-center font-bold">@</span>
                            <span class="font-bold text-[10px] text-slate-900 group-hover:text-emerald-700">${c.username}</span>
                        </button>
                    `).join('');
                }
            }

            // Render Threads List
            const userThreads = data.user_threads || [];
            const npcThreads = data.npc_threads || [];

            let html = '';

            // Real User Threads
            if (userThreads.length > 0) {
                html += `<div class="text-[10px] font-bold text-slate-500 uppercase px-1 pt-1 tracking-wider">Citizen Conversations</div>`;
                html += userThreads.map(t => `
                    <div onclick="PhoneApp.openConversationWithUser('${t.raw_username}', '${(t.name || t.username).replace(/'/g, "\\'")}', '${t.avatar}')" class="bg-white border border-slate-200/90 hover:border-emerald-500 hover:bg-slate-50/80 p-2.5 rounded-2xl cursor-pointer transition active:scale-[0.99] shadow-sm flex items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="relative flex-shrink-0">
                                <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-sm font-bold text-slate-700 overflow-hidden">
                                    ${t.avatar && t.avatar.includes('/') ? `<img src="${t.avatar}" class="w-full h-full object-cover">` : (t.avatar || '👤')}
                                </div>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 border-2 border-white absolute bottom-0 right-0"></span>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1">
                                    <span class="font-extrabold text-xs text-slate-900 truncate">${t.name}</span>
                                    <span class="text-[10px] text-emerald-600 font-bold">${t.username}</span>
                                </div>
                                <p class="text-[11px] text-slate-500 truncate leading-tight">${t.last_message}</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 flex flex-col items-end">
                            <span class="text-[9px] text-slate-400 font-medium">${t.time}</span>
                            ${t.unread > 0 ? `<span class="mt-1 px-1.5 py-0.2 bg-emerald-600 text-white font-extrabold text-[9px] rounded-full">${t.unread}</span>` : ''}
                        </div>
                    </div>
                `).join('');
            }

            // Quest / NPC Contacts
            if (npcThreads.length > 0) {
                html += `<div class="text-[10px] font-bold text-slate-500 uppercase px-1 pt-2 tracking-wider">Abuja Contacts & Quests</div>`;
                html += npcThreads.map(n => `
                    <div class="bg-slate-50 border border-slate-200 p-2.5 rounded-2xl transition space-y-1.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-base">${n.avatar}</span>
                                <div>
                                    <h5 class="font-bold text-xs text-slate-900 leading-none">${n.name}</h5>
                                    <span class="text-[9px] text-slate-400 font-mono">${n.district}</span>
                                </div>
                            </div>
                            <span class="text-[9px] text-slate-400">${n.time}</span>
                        </div>
                        <p class="text-[11px] text-slate-600 leading-snug">${n.last_message}</p>
                        <div class="pt-0.5">
                            ${PhoneApp.renderNpcActions(n.id)}
                        </div>
                    </div>
                `).join('');
            }

            list.innerHTML = html || `<p class="p-4 text-center text-xs text-slate-400">No chats yet. Start one by typing a @username above!</p>`;

        } catch(err) {
            list.innerHTML = `<p class="p-3 text-xs text-rose-500 text-center">Connection error syncing messages.</p>`;
        }
    },

    renderNpcActions(id) {
        if (id === 'landlord') {
            return `
                <div class="flex gap-1.5">
                    <button onclick="PhoneApp.handleChatAction('landlord', 'pay')" class="flex-1 py-1 px-2 bg-green-600 hover:bg-green-500 text-white rounded-xl text-[10px] font-bold shadow-sm transition active:scale-95 text-center">
                        Transfer ₦5,000 Levy
                    </button>
                    <button onclick="PhoneApp.handleChatAction('landlord', 'ignore')" class="py-1 px-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-[10px] font-bold transition active:scale-95">
                        Leave on Read
                    </button>
                </div>
            `;
        }
        if (id === 'kunle_gig') {
            return `
                <div class="flex gap-1.5">
                    <button onclick="PhoneApp.handleChatAction('kunle_gig', 'accept_gig')" class="flex-1 py-1 px-2 bg-green-600 hover:bg-green-500 text-white rounded-xl text-[10px] font-bold shadow-sm transition active:scale-95 text-center">
                        Accept Gig (+₦35k)
                    </button>
                    <button onclick="PhoneApp.handleChatAction('kunle_gig', 'decline')" class="py-1 px-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-[10px] font-bold transition active:scale-95">
                        Decline
                    </button>
                </div>
            `;
        }
        if (id === 'femi') {
            return `
                <div class="flex gap-1.5">
                    <button onclick="PhoneApp.handleChatAction('femi', 'send_2k')" class="flex-1 py-1 px-2 bg-green-600 hover:bg-green-500 text-white rounded-xl text-[10px] font-bold shadow-sm transition active:scale-95 text-center">
                        Send ₦2,000 (+Karma)
                    </button>
                    <button onclick="PhoneApp.handleChatAction('femi', 'decline')" class="py-1 px-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-[10px] font-bold transition active:scale-95">
                        'Sapa Hold Me'
                    </button>
                </div>
            `;
        }
        if (id === 'shawarma') {
            return `
                <div class="flex gap-1.5">
                    <button onclick="PhoneApp.handleChatAction('shawarma', 'accept')" class="flex-1 py-1 px-2 bg-green-600 hover:bg-green-500 text-white rounded-xl text-[10px] font-bold shadow-sm transition active:scale-95 text-center">
                        Pay ₦2,500 & Collect
                    </button>
                    <button onclick="PhoneApp.handleChatAction('shawarma', 'ignore')" class="py-1 px-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-[10px] font-bold transition active:scale-95">
                        Cancel
                    </button>
                </div>
            `;
        }
        return '';
    },

    startChatFromInput() {
        const input = document.getElementById('phoneChatSearchInput');
        if (!input) return;
        let val = input.value.trim();
        if (!val) return;
        val = val.replace(/^@/, '');
        this.openConversationWithUser(val, '@' + val, '👤');
        input.value = '';
    },

    async openConversationWithUser(username, name, avatar) {
        const cleanUser = username.replace(/^@/, '');
        this.activeChatTarget = { username: cleanUser, name: name || cleanUser, avatar: avatar || '👤' };

        const mainView = document.getElementById('phoneChatMainView');
        const convView = document.getElementById('phoneChatConversationView');
        if (mainView) mainView.classList.add('hidden');
        if (convView) convView.classList.remove('hidden');

        const headerName = document.getElementById('convHeaderName');
        const headerUser = document.getElementById('convHeaderUsername');
        const headerAvatar = document.getElementById('convHeaderAvatar');

        if (headerName) headerName.textContent = name || cleanUser;
        if (headerUser) headerUser.textContent = '@' + cleanUser;
        if (headerAvatar) {
            headerAvatar.innerHTML = (avatar && avatar.includes('/'))
                ? `<img src="${avatar}" class="w-full h-full object-cover">`
                : (avatar || '👤');
        }

        const container = document.getElementById('phoneMessagesContainer');
        if (container) {
            container.innerHTML = `<div class="p-4 text-center text-xs text-slate-400 animate-pulse"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Loading conversation...</div>`;
        }

        try {
            const res = await fetch(`api/phone.php?action=get_messages&username=${encodeURIComponent(cleanUser)}`);
            const data = await res.json();
            if (data.success) {
                if (data.contact) {
                    this.activeChatTarget = {
                        username: data.contact.raw_username || cleanUser,
                        name: data.contact.full_name || cleanUser,
                        avatar: data.contact.avatar || avatar
                    };
                    if (headerName) headerName.textContent = this.activeChatTarget.name;
                    if (headerUser) headerUser.textContent = '@' + this.activeChatTarget.username;
                }
                this.renderMessages(data.messages || []);
            } else {
                if (container) container.innerHTML = `<p class="p-3 text-xs text-rose-500 text-center">${data.error || 'Failed to load messages'}</p>`;
            }
        } catch(e) {
            if (container) container.innerHTML = `<p class="p-3 text-xs text-slate-400 text-center">Start a new conversation with @${cleanUser}!</p>`;
        }
    },

    closeConversation() {
        this.activeChatTarget = null;
        const mainView = document.getElementById('phoneChatMainView');
        const convView = document.getElementById('phoneChatConversationView');
        if (convView) convView.classList.add('hidden');
        if (mainView) mainView.classList.remove('hidden');
        this.loadChatApp();
    },

    renderMessages(messages) {
        const container = document.getElementById('phoneMessagesContainer');
        if (!container) return;

        if (messages.length === 0) {
            container.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-center p-4 text-slate-400">
                    <span class="text-2xl mb-1">💬</span>
                    <p class="text-xs font-semibold">No messages yet with @${this.activeChatTarget?.username}</p>
                    <span class="text-[10px]">Say hello or send funds!</span>
                </div>
            `;
            return;
        }

        container.innerHTML = messages.map(m => `
            <div class="flex flex-col ${m.is_me ? 'items-end' : 'items-start'}">
                <div class="max-w-[78%] px-3 py-2 rounded-2xl text-xs leading-snug shadow-sm ${
                    m.is_me 
                        ? 'bg-emerald-600 text-white rounded-br-none' 
                        : 'bg-white border border-slate-200 text-slate-900 rounded-bl-none'
                }">
                    ${m.message}
                </div>
                <span class="text-[9px] text-slate-400 mt-0.5 px-1">${m.time || ''}</span>
            </div>
        `).join('');

        container.scrollTop = container.scrollHeight;
    },

    async handleSendMessage(e) {
        e.preventDefault();
        const input = document.getElementById('phoneMsgInput');
        if (!input || !this.activeChatTarget) return;

        const text = input.value.trim();
        if (!text) return;

        input.value = '';

        const container = document.getElementById('phoneMessagesContainer');
        if (container) {
            const timeNow = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            container.insertAdjacentHTML('beforeend', `
                <div class="flex flex-col items-end animate-fade-in">
                    <div class="max-w-[78%] px-3 py-2 rounded-2xl rounded-br-none text-xs leading-snug shadow-sm bg-emerald-600 text-white">
                        ${text}
                    </div>
                    <span class="text-[9px] text-slate-400 mt-0.5 px-1">${timeNow}</span>
                </div>
            `);
            container.scrollTop = container.scrollHeight;
        }

        GameApp.playSfx('click');

        const formData = new FormData();
        formData.append('recipient_username', this.activeChatTarget.username);
        formData.append('message', text);

        try {
            const res = await fetch('api/phone.php?action=send_message', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                if (data.auto_reply && container) {
                    setTimeout(() => {
                        GameApp.playSfx('win');
                        container.insertAdjacentHTML('beforeend', `
                            <div class="flex flex-col items-start animate-fade-in">
                                <div class="max-w-[78%] px-3 py-2 rounded-2xl rounded-bl-none text-xs leading-snug shadow-sm bg-white border border-slate-200 text-slate-900">
                                    ${data.auto_reply.message}
                                </div>
                                <span class="text-[9px] text-slate-400 mt-0.5 px-1">${data.auto_reply.time || 'Just now'}</span>
                            </div>
                        `);
                        container.scrollTop = container.scrollHeight;
                    }, 650);
                }
            } else {
                GameApp.notify(data.error || 'Failed to deliver message', 'error');
            }
        } catch(err) {
            GameApp.notify('Message delivery error', 'error');
        }
    },

    openTransferToActiveContact() {
        if (!this.activeChatTarget) return;
        this.openDirectTransferModal('@' + this.activeChatTarget.username);
    },

    // --- 4. GAMES APP ---
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
            fetch('api/bank.php?action=withdraw', { method: 'POST' });
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

    // --- 5. BOLT RIDE HAILING APP ---
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

    // --- 6. WARDROBE / JIJI STYLE APP ---
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

    async publishSocialPost() {
        const input = document.getElementById('socialPostInput');
        if (!input || !input.value.trim()) {
            GameApp.notify("Type something to post on NaijaGram!", "error");
            return;
        }

        const text = input.value.trim();
        const char = GameApp.character || {};
        const formData = new FormData();
        formData.append('content', text);

        try {
            const res = await fetch('api/phone.php?action=post_social', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                const commentList = (data.comments || []).map(c => `${c.author}: ${c.text}`);
                if (commentList.length === 0) {
                    commentList.push("Senior man! Street cred on point 🙌");
                }
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
                    comments: commentList
                };

                this.socialPosts.unshift(newPost);
                this.socialClout += data.clout_gain || 180;
                input.value = '';

                GameApp.playSfx('win');
                GameApp.notify(data.message, "success");
                await GameApp.fetchCharacter();
                this.loadNaijaGramApp();
            } else {
                GameApp.notify(data.error || 'Failed to post on NaijaGram', 'error');
            }
        } catch(e) {
            GameApp.notify('Network error publishing to NaijaGram', 'error');
        }
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
