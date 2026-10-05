# 🇳🇬 Abuja Life - Urban Nigerian RPG Simulation

**Abuja Life** is an immersive browser-based life simulation RPG set in Nigeria's Federal Capital Territory (FCT), Abuja. Start with ₦35,000 cash in the satellite town of Kubwa, navigate careers, run street hustles, acquire real estate, collect fleets of vehicles, experience Abuja nightlife, and rise to power in Maitama and Asokoro.

---

## 🌟 Key Features

- **8 Iconic FCT Districts:** Progress through Kubwa, Lugbe, Gwarinpa, Garki, Central Area, Wuse 2, Maitama, and Asokoro.
- **Careers & Civil Service:** From Keke Napep rider and Banex phone technician, to Tech Lead, Commercial Bank Manager, and Special Assistant to the Minister.
- **Street Hustles & Side Gigs:** Run POS terminal cash points, flip UK-used gadgets at Banex Plaza, trade crypto P2P arbitrage, hype crowds as an Abuja MC, and broker ministry procurement contracts.
- **Higher Education:** Study at UniAbuja, Nile University, Baze University, or modern tech bootcamps to raise your intelligence and qualify for elite executive appointments.
- **Real Estate Portfolio:** Buy self-contains, flats, duplexes, and luxury mansions; reside in them for happiness bonuses or rent them out for daily passive Naira yield.
- **Garage & Fleet:** Own everything from Bajaj motorcycles and Toyota Corolla ("Big Daddy") to Mercedes-Benz C300, Lexus RX350, Range Rover Autobiography, and Siren Convoy escorts.
- **Abuja Lifestyle & Leisure:** Eat hot suya at Millennium Park, cruise Jabi Lake, party in Wuse 2 VIP clubs, workout at Maitama gyms, and attend weekend Owambe parties.
- **Financial System:** Bank savings with compound daily interest, credit lines, and sports betting (accumulator tickets & lucky dice).
- **Random Nigerian Life Encounters:** Police checkpoints, urgent 2k family billing, power grid surges, wedding spraying, and crypto market dips.
- **Hall of Fame:** Compete on the leaderboard for Abuja's Richest Citizen and Top Street Cred Don.

---

## 🛠️ Tech Stack

- **Backend:** PHP (PHP 7.4+ / 8.x), PDO MySQL, RESTful API architecture.
- **Database:** MySQL / MariaDB (`db.sql`).
- **Frontend:** HTML5, Modern Tailwind CSS CDN, FontAwesome 6, Vanilla ES6 JavaScript game engine (`assets/js/game.js`).
- **Audio:** Native Web Audio API audio synthesis for instant sound effects without external MP3 dependencies.

---

## 🚀 Quick Setup & Installation

### Option 1: Using XAMPP / Laragon / WAMP
1. Clone or place the `abuja life` folder into your web root directory (e.g. `htdocs/abujalife` or `www/abujalife`).
2. Start Apache and MySQL in your control panel.
3. Open your browser and visit:
   ```
   http://localhost/abujalife/install.php
   ```
4. Click **Run Database Migration & Seed** to automatically create the database and seed initial items, jobs, vehicles, and properties.
5. Visit `http://localhost/abujalife/index.php` and click **Instant Fast Play** or register a new character.

### Option 2: Using Built-in PHP CLI Server
1. Open PowerShell / Command Prompt inside this folder:
   ```powershell
   cd "abuja life"
   php -S localhost:8000
   ```
2. Navigate to `http://localhost:8000/install.php` in your browser.
3. Once completed, head to `http://localhost:8000` to start playing!

---

## 📂 Project Structure

```text
├── api/
│   ├── auth.php          # Registration, login, guest instant-play, logout
│   ├── bank.php          # Deposits, withdrawals, and bank loans
│   ├── casino.php        # Sports accumulator betting & lucky dice
│   ├── character.php     # Character stats, day advancement, resting, health
│   ├── education.php     # Degree courses and bootcamps
│   ├── events.php        # Random life encounters and choice resolution
│   ├── hustles.php       # Side hustles (POS, Banex, Crypto, MC, Ministry tenders)
│   ├── jobs.php          # Career listings, applications, and work shifts
│   ├── leaderboard.php   # Top net worth & street cred rankings
│   ├── lifestyle.php     # Leisure, Millennium park, clubs, gym, Owambe
│   ├── realestate.php    # Buying, renting out, and selling properties
│   └── vehicles.php      # Showroom, purchases, cruising, and car sales
├── assets/
│   └── js/
│       └── game.js       # Core game engine, Web Audio SFX, HUD updates
├── config.php            # PDO connection, game constants, auth helpers
├── db.sql                # Complete schema & seed data
├── game.php              # Main game dashboard & single-page application
├── index.php             # Landing page & authentication portal
├── install.php           # One-click web database installer
├── .gitignore            # Git exclusion rules
└── README.md             # Project documentation
```

---

## 📜 License

This project is open-source and free to play and customize.
