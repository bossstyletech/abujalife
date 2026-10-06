/**
 * Abuja Life 3D Interactive Room & Multi-Venue Engine (Three.js)
 * Fully responsive isometric room diorama supporting:
 * 1. 'home': Player's Residence (bed with red duvet, blue mat, 4-tier bookshelf, radio, desk, cooler, water drum, jerry cans, red chair, buckets, standing avatar)
 * 2. 'gym': i-FITNESS Maitama Gym (smoothies bar, glass shower, bench press, dumbbell rack, treadmills, yoga mats, exercise ball, plant)
 * 3. 'mosque': National Mosque Sanctuary (emerald green carpet, arched mihrab, calligraphy, ablution fountain, Quran stands)
 * 4. 'church': National Christian Centre (polished pews, altar with cross, choir instruments, stained glass)
 * 5. 'market': Wuse Modern Market (ankara fabric stalls, produce, electronics kiosk, POS umbrellas)
 * 6. 'restaurant': Jabi Lake Grill (dining tables, smoky party jollof, tilapia, Chapman glasses, lake view)
 * 7. 'banex': Banex Plaza Tech Hub (iPhone/MacBook display counters, gadget repair workbench, neon signs)
 * 
 * In EVERY venue, multiple 3D citizens are present with floating @username badges!
 * Clicking ANY citizen allows:
 * - 💬 Chat on NaijaChat
 * - 💸 Send Money via AbujaPay
 * - 🍢 Relate & Connect (Suya & Drinks)
 * - 🎲 Street Dice Challenge
 */

const World3D = {
    container: null,
    scene: null,
    camera: null,
    renderer: null,
    controls: null,
    raycaster: null,
    mouse: null,
    interactables: [],
    roomGroup: null,
    characterGroup: null,
    venueCitizensGroup: null,
    currentVenue: 'home', // 'home', 'gym', 'mosque', 'church', 'market', 'restaurant', 'banex'
    tooltipEl: null,
    currentHoverObject: null,
    isInitialized: false,

    init(containerId = 'world3d-container') {
        this.container = document.getElementById(containerId);
        if (!this.container || typeof THREE === 'undefined') return;

        const width = this.container.clientWidth || 800;
        const height = this.container.clientHeight || 540;

        // Scene
        this.scene = new THREE.Scene();
        this.updateSkyForVenue(this.currentVenue);

        // Camera - Isometric Elevated Angle
        this.camera = new THREE.PerspectiveCamera(36, width / height, 0.1, 500);
        this.camera.position.set(13.5, 10.5, 13.5);

        // Renderer
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        this.container.innerHTML = '';
        this.container.appendChild(this.renderer.domElement);

        // OrbitControls
        if (typeof THREE.OrbitControls !== 'undefined') {
            this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.08;
            this.controls.target.set(0, 2.5, 0);
            this.controls.maxPolarAngle = Math.PI / 2.1;
            this.controls.minDistance = 6;
            this.controls.maxDistance = 32;
        }

        // Raycasting
        this.raycaster = new THREE.Raycaster();
        this.mouse = new THREE.Vector2();

        // Tooltip
        this.setupTooltip();

        // Lighting Architecture
        this.setupLighting();

        // Build Active Venue Room
        this.buildVenueScene(this.currentVenue);

        // Events
        this.renderer.domElement.addEventListener('click', this.onClick.bind(this));
        this.renderer.domElement.addEventListener('mousemove', this.onMouseMove.bind(this));
        this.renderer.domElement.addEventListener('mouseleave', this.onMouseLeave.bind(this));
        window.addEventListener('resize', this.onResize.bind(this));

        this.isInitialized = true;
        this.animate();
    },

    updateSkyForVenue(venue) {
        if (!this.scene) return;
        if (venue === 'gym') {
            this.scene.background = new THREE.Color(0xdce5ea);
            this.scene.fog = new THREE.Fog(0xdce5ea, 35, 90);
        } else if (venue === 'mosque') {
            this.scene.background = new THREE.Color(0xd7ebe0);
            this.scene.fog = new THREE.Fog(0xd7ebe0, 35, 90);
        } else if (venue === 'church') {
            this.scene.background = new THREE.Color(0xe6e1d8);
            this.scene.fog = new THREE.Fog(0xe6e1d8, 35, 90);
        } else {
            // Warm sunset/twilight sky matching screenshot
            this.scene.background = new THREE.Color(0xd9bfa9);
            this.scene.fog = new THREE.Fog(0xd9bfa9, 35, 85);
        }
    },

    setupTooltip() {
        if (document.getElementById('world3d-tooltip')) {
            this.tooltipEl = document.getElementById('world3d-tooltip');
            return;
        }
        this.tooltipEl = document.createElement('div');
        this.tooltipEl.id = 'world3d-tooltip';
        this.tooltipEl.style.position = 'absolute';
        this.tooltipEl.style.pointerEvents = 'none';
        this.tooltipEl.style.zIndex = '50';
        this.tooltipEl.style.padding = '6px 14px';
        this.tooltipEl.style.background = 'rgba(15, 23, 42, 0.92)';
        this.tooltipEl.style.color = '#ffffff';
        this.tooltipEl.style.fontSize = '12px';
        this.tooltipEl.style.fontWeight = 'bold';
        this.tooltipEl.style.borderRadius = '9999px';
        this.tooltipEl.style.border = '1px solid rgba(255, 255, 255, 0.25)';
        this.tooltipEl.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.35)';
        this.tooltipEl.style.transform = 'translate(-50%, -130%)';
        this.tooltipEl.style.transition = 'opacity 0.15s ease, transform 0.15s ease';
        this.tooltipEl.style.opacity = '0';
        this.container.style.position = 'relative';
        this.container.appendChild(this.tooltipEl);
    },

    setupLighting() {
        // Main Sun Key Light
        const sun = new THREE.DirectionalLight(0xfffaed, 1.25);
        sun.position.set(16, 24, 15);
        sun.castShadow = true;
        sun.shadow.mapSize.width = 2048;
        sun.shadow.mapSize.height = 2048;
        sun.shadow.bias = -0.0003;
        sun.shadow.camera.left = -12;
        sun.shadow.camera.right = 12;
        sun.shadow.camera.top = 12;
        sun.shadow.camera.bottom = -12;
        this.scene.add(sun);

        // Ambient Fill
        const ambient = new THREE.AmbientLight(0x8295a8, 0.75);
        this.scene.add(ambient);

        // Left Accent Point Light
        this.pointLightL = new THREE.PointLight(0xffedd5, 1.2, 10);
        this.pointLightL.position.set(-3.2, 5.0, -4.6);
        this.scene.add(this.pointLightL);

        // Right Accent Point Light
        this.pointLightR = new THREE.PointLight(0xffedd5, 1.2, 10);
        this.pointLightR.position.set(4.6, 5.0, -1.8);
        this.scene.add(this.pointLightR);
    },

    // ─────────────────────────────────────────────────────────────────────────
    // VENUE SWITCHER
    // ─────────────────────────────────────────────────────────────────────────
    switchVenue(venueId) {
        this.currentVenue = venueId;
        this.updateSkyForVenue(venueId);
        this.buildVenueScene(venueId);

        // Update UI pill text
        const pillText = document.getElementById('world3dVenuePillText');
        if (pillText) {
            const names = {
                home: '🏠 Abuja Residence',
                gym: '🏋️ i-Fitness Gym',
                mosque: '🕌 National Mosque',
                church: '⛪ National Church',
                market: '🛍️ Wuse Market',
                restaurant: '🍲 Jabi Lake Grill',
                banex: '📱 Banex Tech Hub'
            };
            pillText.textContent = names[venueId] || 'Abuja Room';
        }
    },

    buildVenueScene(venueId) {
        if (this.roomGroup) {
            this.scene.remove(this.roomGroup);
        }
        if (this.venueCitizensGroup) {
            this.scene.remove(this.venueCitizensGroup);
        }

        this.interactables = [];
        this.roomGroup = new THREE.Group();
        this.venueCitizensGroup = new THREE.Group();

        if (venueId === 'gym') {
            this.buildGymRoom(this.roomGroup);
        } else if (venueId === 'mosque') {
            this.buildMosqueRoom(this.roomGroup);
        } else if (venueId === 'church') {
            this.buildChurchRoom(this.roomGroup);
        } else if (venueId === 'market') {
            this.buildMarketRoom(this.roomGroup);
        } else if (venueId === 'restaurant') {
            this.buildRestaurantRoom(this.roomGroup);
        } else if (venueId === 'banex') {
            this.buildBanexRoom(this.roomGroup);
        } else {
            this.buildHomeRoom(this.roomGroup);
        }

        // Add Room Group
        this.scene.add(this.roomGroup);

        // Spawn citizens inside this room (other users with @username)
        this.spawnVenueCitizens(venueId, this.venueCitizensGroup);
        this.scene.add(this.venueCitizensGroup);
    },

    // ─────────────────────────────────────────────────────────────────────────
    // 1. HOME RESIDENCE DIORAMA (Image 1 - media_1791305930819.png)
    // ─────────────────────────────────────────────────────────────────────────
    buildHomeRoom(parent) {
        // Base Grass Podium
        const terrain = new THREE.Mesh(new THREE.CylinderGeometry(13, 13.5, 1.2, 48), new THREE.MeshLambertMaterial({ color: 0x93ab63 }));
        terrain.position.y = -0.6;
        terrain.receiveShadow = true;
        parent.add(terrain);

        // Parquet Checkerboard Floor
        const floorW = 10, floorD = 10, tileSize = 2.0;
        const tileMatA = new THREE.MeshStandardMaterial({ color: 0xc49e6f, roughness: 0.45 });
        const tileMatB = new THREE.MeshStandardMaterial({ color: 0xa98154, roughness: 0.5 });

        for (let x = -floorW / 2; x < floorW / 2; x += tileSize) {
            for (let z = -floorD / 2; z < floorD / 2; z += tileSize) {
                const isEven = (Math.round((x + floorW / 2) / tileSize) + Math.round((z + floorD / 2) / tileSize)) % 2 === 0;
                const tile = new THREE.Mesh(new THREE.BoxGeometry(tileSize, 0.2, tileSize), isEven ? tileMatA : tileMatB);
                tile.position.set(x + tileSize / 2, 0.1, z + tileSize / 2);
                tile.receiveShadow = true;
                parent.add(tile);
            }
        }

        // Cutaway Walls (Back-left and back-right, smooth grey)
        const wallMat = new THREE.MeshStandardMaterial({ color: 0x62757e, roughness: 0.7 });
        const wallLeft = new THREE.Mesh(new THREE.BoxGeometry(10.4, 6.2, 0.4), wallMat);
        wallLeft.position.set(0, 3.3, -5.2);
        wallLeft.castShadow = true;
        wallLeft.receiveShadow = true;
        parent.add(wallLeft);

        const wallRight = new THREE.Mesh(new THREE.BoxGeometry(0.4, 6.2, 10.4), wallMat);
        wallRight.position.set(5.2, 3.3, 0);
        wallRight.castShadow = true;
        wallRight.receiveShadow = true;
        parent.add(wallRight);

        // Skirting Boards
        const skirtMat = new THREE.MeshStandardMaterial({ color: 0x8a633c });
        const skirtL = new THREE.Mesh(new THREE.BoxGeometry(10, 0.35, 0.08), skirtMat);
        skirtL.position.set(0, 0.35, -4.96);
        parent.add(skirtL);

        const skirtR = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.35, 10), skirtMat);
        skirtR.position.set(4.96, 0.35, 0);
        parent.add(skirtR);

        // Door on Left Wall
        this.createDoor(parent, -3.6, 2.4, -4.9);

        // Wall Sconce Lamps with Glowing Spherical Bulbs
        this.createWallSconce(parent, -3.2, 5.0, -4.9, 0);
        this.createWallSconce(parent, 4.9, 5.0, -1.8, Math.PI / 2);

        // Wall Calendar
        this.createWallCalendar(parent, -1.8, 4.6, -4.95);

        // Bed with Red Duvet & White Pillow
        this.createBed(parent, -1.8, 0.7, -3.4);

        // Blue Foam Floor Mattress Mat
        this.createFloorMat(parent, 0.6, 0.25, -3.6);

        // 4-Tier Bookshelf with Colorful Books
        this.createBookshelf(parent, 2.4, 2.4, -4.6);

        // Stool with Retro Radio
        this.createRadioStool(parent, 3.8, 0.8, -4.5);

        // Study Desk with Mug & Window with Blinds
        this.createStudyDesk(parent, 4.6, 1.1, -3.2);
        this.createWindowBlinds(parent, 4.95, 4.4, -0.6);

        // Food Cooler (Ice Chest)
        this.createCooler(parent, 4.4, 0.45, 0.8);

        // Water Drum (100L Blue) & 2 Yellow Jerry Cans
        this.createWaterSupply(parent, 4.4, 0.9, 2.4);

        // Red Center Chair
        this.createCenterChair(parent, 0.1, 0.65, -0.4);

        // Red and Blue Buckets in Foreground
        this.createBuckets(parent, 0.8, 1.9, 4.4);

        // Player's Avatar standing with Golden Crown
        this.createPlayerAvatarInRoom(parent, 0, 0.15, 2.0);
    },

    // ─────────────────────────────────────────────────────────────────────────
    // 2. "i-FITNESS" GYM DIORAMA (Image 2 - media_1791306855591.png)
    // ─────────────────────────────────────────────────────────────────────────
    buildGymRoom(parent) {
        // Base Podium
        const terrain = new THREE.Mesh(new THREE.CylinderGeometry(13.5, 14, 1.2, 48), new THREE.MeshLambertMaterial({ color: 0x829a73 }));
        terrain.position.y = -0.6;
        parent.add(terrain);

        // Dark Charcoal / Black Rubber Gym Flooring
        const floorMat = new THREE.MeshStandardMaterial({ color: 0x22262e, roughness: 0.8 });
        const floor = new THREE.Mesh(new THREE.BoxGeometry(10.2, 0.2, 10.2), floorMat);
        floor.position.set(0, 0.1, 0);
        floor.receiveShadow = true;
        parent.add(floor);

        // Crisp Off-White Gym Walls
        const wallMat = new THREE.MeshStandardMaterial({ color: 0xdedede, roughness: 0.5 });
        const wallLeft = new THREE.Mesh(new THREE.BoxGeometry(10.4, 6.2, 0.4), wallMat);
        wallLeft.position.set(0, 3.3, -5.2);
        parent.add(wallLeft);

        const wallRight = new THREE.Mesh(new THREE.BoxGeometry(0.4, 6.2, 10.4), wallMat);
        wallRight.position.set(5.2, 3.3, 0);
        parent.add(wallRight);

        // Red "i-FITNESS" Wall Banner on Right Wall
        const bannerGroup = new THREE.Group();
        bannerGroup.position.set(4.95, 4.8, 0.5);
        bannerGroup.rotation.y = -Math.PI / 2;

        const bannerBg = new THREE.Mesh(new THREE.BoxGeometry(3.6, 0.85, 0.08), new THREE.MeshStandardMaterial({ color: 0xc81e2b }));
        bannerGroup.add(bannerBg);

        const bannerCanvas = document.createElement('canvas');
        bannerCanvas.width = 512; bannerCanvas.height = 128;
        const bctx = bannerCanvas.getContext('2d');
        bctx.fillStyle = '#c81e2b'; bctx.fillRect(0,0,512,128);
        bctx.fillStyle = '#ffffff'; bctx.font = 'bold 64px "Plus Jakarta Sans", sans-serif';
        bctx.textAlign = 'center'; bctx.textBaseline = 'middle';
        bctx.fillText('i-FITNESS', 256, 64);
        const btex = new THREE.CanvasTexture(bannerCanvas);
        const bannerSign = new THREE.Mesh(new THREE.PlaneGeometry(3.5, 0.8), new THREE.MeshBasicMaterial({ map: btex }));
        bannerSign.position.z = 0.05;
        bannerGroup.add(bannerSign);
        parent.add(bannerGroup);

        // "SMOOTHIES" Bar Counter on Back-Left Wall
        this.createSmoothieBar(parent, -2.8, -4.5);

        // Glass Shower / Changing Cubicle on Far Left
        this.createShowerCubicle(parent, -4.2, 0.8);

        // Barbell Bench Press & Weight Rack in Center-Back
        this.createBenchPressStation(parent, 0.8, -3.8);

        // Treadmills Station on Right
        this.createTreadmills(parent, 3.4, -2.4);

        // Workout Yoga Mats (Pink & Cyan) on the Floor
        this.createYogaMats(parent, 1.8, 2.6);

        // Exercise Gym Ball (Blue sphere)
        const ballMat = new THREE.MeshStandardMaterial({ color: 0x0284c7, roughness: 0.3 });
        const ball = new THREE.Mesh(new THREE.SphereGeometry(0.42, 16, 16), ballMat);
        ball.position.set(3.8, 0.5, 3.6);
        ball.castShadow = true;
        this.registerInteractable(ball, 'gym_ball', '🔵 Tap Exercise Ball', () => {
            if (window.GameApp) GameApp.notify('🤸 Stretched core muscles on the exercise ball (+5 Health)', 'success');
        });
        parent.add(ball);

        // Potted Indoor Plant
        this.createPottedPlant(parent, 4.2, 1.4);
    },

    createSmoothieBar(parent, x, z) {
        const barGroup = new THREE.Group();
        barGroup.position.set(x, 0, z);

        // White base with turquoise top counter
        const base = new THREE.Mesh(new THREE.BoxGeometry(2.8, 1.4, 1.1), new THREE.MeshStandardMaterial({ color: 0xffffff }));
        base.position.y = 0.8;
        base.castShadow = true;
        barGroup.add(base);

        const counterTop = new THREE.Mesh(new THREE.BoxGeometry(3.0, 0.14, 1.25), new THREE.MeshStandardMaterial({ color: 0x10b981 }));
        counterTop.position.y = 1.55;
        barGroup.add(counterTop);

        // "SMOOTHIES" Sign above shelf
        const signCanvas = document.createElement('canvas');
        signCanvas.width = 256; signCanvas.height = 64;
        const sctx = signCanvas.getContext('2d');
        sctx.fillStyle = '#0f172a'; sctx.fillRect(0,0,256,64);
        sctx.fillStyle = '#ffffff'; sctx.font = 'bold 30px sans-serif';
        sctx.textAlign = 'center'; sctx.textBaseline = 'middle';
        sctx.fillText('SMOOTHIES', 128, 32);
        const stex = new THREE.CanvasTexture(signCanvas);
        const sign = new THREE.Mesh(new THREE.PlaneGeometry(1.6, 0.4), new THREE.MeshBasicMaterial({ map: stex }));
        sign.position.set(0, 3.2, -0.6);
        barGroup.add(sign);

        // Shelves with Colorful Smoothie Bottles
        const shelf = new THREE.Mesh(new THREE.BoxGeometry(2.4, 0.08, 0.35), new THREE.MeshStandardMaterial({ color: 0xd97706 }));
        shelf.position.set(0, 2.7, -0.55);
        barGroup.add(shelf);

        const bottleColors = [0xef4444, 0x10b981, 0x06b6d4, 0xf59e0b, 0x8b5cf6];
        for (let bx = -0.9; bx <= 0.9; bx += 0.35) {
            const bMat = new THREE.MeshStandardMaterial({ color: bottleColors[Math.abs(Math.floor(bx * 10)) % bottleColors.length] });
            const bot = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.08, 0.32, 10), bMat);
            bot.position.set(bx, 2.9, -0.55);
            barGroup.add(bot);
        }

        // Bar Stools
        for (let sx = -0.8; sx <= 0.8; sx += 0.8) {
            const stool = new THREE.Mesh(new THREE.CylinderGeometry(0.24, 0.24, 0.8, 12), new THREE.MeshStandardMaterial({ color: 0x1e293b }));
            stool.position.set(sx, 0.5, 0.85);
            barGroup.add(stool);
        }

        // Bartender NPC behind counter
        const tender = this.createNpcFigure({ full_name: 'Habib Smoothie Bar', outfit: 'street', gender: 'Male' });
        tender.position.set(0, 0.1, -0.5);
        barGroup.add(tender);

        this.registerInteractable(barGroup, 'smoothie_bar', '🥤 Tap Bar: Buy Protein Smoothie (₦1,500)', () => {
            if (window.GameApp) {
                GameApp.playSfx('win');
                GameApp.notify('🥤 Chugged a chilled Banana Whey Protein Shake! (+25 Energy, +15 Health)', 'success');
                GameApp.fetchCharacter();
            }
        });

        parent.add(barGroup);
    },

    createShowerCubicle(parent, x, z) {
        const cubGroup = new THREE.Group();
        cubGroup.position.set(x, 1.8, z);

        // Glass frame
        const glassMat = new THREE.MeshStandardMaterial({ color: 0xbae6fd, transparent: true, opacity: 0.45, roughness: 0.1 });
        const box = new THREE.Mesh(new THREE.BoxGeometry(1.6, 3.4, 1.6), glassMat);
        cubGroup.add(box);

        // Showerhead
        const head = new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.18, 0.06, 12), new THREE.MeshStandardMaterial({ color: 0xd4d4d8, metalness: 0.9 }));
        head.position.set(0, 1.4, 0);
        cubGroup.add(head);

        this.registerInteractable(cubGroup, 'gym_shower', '🚿 Tap Shower: Freshen Up', () => {
            if (window.GameApp) {
                GameApp.playSfx('win');
                GameApp.notify('🚿 Took a warm shower in the gym cubicle! (+100% Hygiene, +10 Happiness)', 'success');
            }
        });

        parent.add(cubGroup);
    },

    createBenchPressStation(parent, x, z) {
        const bpGroup = new THREE.Group();
        bpGroup.position.set(x, 0.5, z);

        // Bench
        const bench = new THREE.Mesh(new THREE.BoxGeometry(1.2, 0.35, 2.4), new THREE.MeshStandardMaterial({ color: 0x0f172a }));
        bench.position.y = 0.2;
        bpGroup.add(bench);

        // Metal Rack Posts
        const rackMat = new THREE.MeshStandardMaterial({ color: 0x475569, metalness: 0.8 });
        const postL = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.06, 2.0), rackMat);
        postL.position.set(-0.7, 0.9, -0.6);
        bpGroup.add(postL);

        const postR = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.06, 2.0), rackMat);
        postR.position.set(0.7, 0.9, -0.6);
        bpGroup.add(postR);

        // Barbell with Heavy Weights
        const bar = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 2.4), rackMat);
        bar.rotation.z = Math.PI / 2;
        bar.position.set(0, 1.7, -0.6);
        bpGroup.add(bar);

        const plateMat = new THREE.MeshStandardMaterial({ color: 0x1e293b });
        const plateL = new THREE.Mesh(new THREE.CylinderGeometry(0.35, 0.35, 0.12, 16), plateMat);
        plateL.rotation.z = Math.PI / 2;
        plateL.position.set(-1.0, 1.7, -0.6);
        bpGroup.add(plateL);

        const plateR = new THREE.Mesh(new THREE.CylinderGeometry(0.35, 0.35, 0.12, 16), plateMat);
        plateR.rotation.z = Math.PI / 2;
        plateR.position.set(1.0, 1.7, -0.6);
        bpGroup.add(plateR);

        // Dumbbell Rack
        const rack = new THREE.Mesh(new THREE.BoxGeometry(1.6, 0.8, 0.6), new THREE.MeshStandardMaterial({ color: 0x334155 }));
        rack.position.set(2.2, 0.2, 0);
        bpGroup.add(rack);

        this.registerInteractable(bpGroup, 'bench_press', '🏋️ Tap Bench Press: Lift Heavy Weights', () => {
            if (window.GameApp) {
                GameApp.doDestinationActivity('gym', 'workout');
            }
        });

        parent.add(bpGroup);
    },

    createTreadmills(parent, x, z) {
        for (let tz of [z - 1.2, z + 1.2]) {
            const tmGroup = new THREE.Group();
            tmGroup.position.set(x, 0.2, tz);

            // Base ramp
            const base = new THREE.Mesh(new THREE.BoxGeometry(1.2, 0.25, 2.2), new THREE.MeshStandardMaterial({ color: 0x1e293b }));
            tmGroup.add(base);

            // Screen & handles
            const handleMat = new THREE.MeshStandardMaterial({ color: 0x0284c7 });
            const handle = new THREE.Mesh(new THREE.BoxGeometry(0.9, 0.1, 0.15), handleMat);
            handle.position.set(0, 1.2, -0.9);
            tmGroup.add(handle);

            this.registerInteractable(tmGroup, 'treadmill', '🏃 Tap Treadmill: 5km Cardio Run', () => {
                if (window.GameApp) {
                    GameApp.playSfx('win');
                    GameApp.notify('🏃 Completed high-intensity cardio sprint! (+15 Health, +5 Stamina)', 'success');
                }
            });

            parent.add(tmGroup);
        }
    },

    createYogaMats(parent, x, z) {
        // Cyan mat
        const matA = new THREE.Mesh(new THREE.BoxGeometry(1.2, 0.04, 2.2), new THREE.MeshStandardMaterial({ color: 0x06b6d4 }));
        matA.position.set(x - 0.9, 0.12, z);
        parent.add(matA);

        // Pink mat
        const matB = new THREE.Mesh(new THREE.BoxGeometry(1.2, 0.04, 2.2), new THREE.MeshStandardMaterial({ color: 0xec4899 }));
        matB.position.set(x + 0.9, 0.12, z);
        parent.add(matB);
    },

    createPottedPlant(parent, x, z) {
        const pot = new THREE.Mesh(new THREE.CylinderGeometry(0.35, 0.25, 0.8, 12), new THREE.MeshStandardMaterial({ color: 0xfef08a }));
        pot.position.set(x, 0.5, z);
        parent.add(pot);

        const leafMat = new THREE.MeshStandardMaterial({ color: 0x15803d });
        for (let i = 0; i < 5; i++) {
            const leaf = new THREE.Mesh(new THREE.ConeGeometry(0.16, 0.9, 4), leafMat);
            leaf.position.set(x, 1.1, z);
            leaf.rotation.z = (i - 2) * 0.25;
            parent.add(leaf);
        }
    },

    // ─────────────────────────────────────────────────────────────────────────
    // 3. OTHER VENUES (Mosque, Church, Market, Restaurant, Banex)
    // ─────────────────────────────────────────────────────────────────────────
    buildMosqueRoom(parent) {
        // Emerald Green Ornate Carpet Floor
        const floor = new THREE.Mesh(new THREE.BoxGeometry(10.2, 0.2, 10.2), new THREE.MeshStandardMaterial({ color: 0x14532d, roughness: 0.6 }));
        floor.position.y = 0.1;
        parent.add(floor);

        // White Walls with Arched Mihrab
        const wallMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.4 });
        const wallLeft = new THREE.Mesh(new THREE.BoxGeometry(10.4, 6.2, 0.4), wallMat);
        wallLeft.position.set(0, 3.3, -5.2);
        parent.add(wallLeft);

        const wallRight = new THREE.Mesh(new THREE.BoxGeometry(0.4, 6.2, 10.4), wallMat);
        wallRight.position.set(5.2, 3.3, 0);
        parent.add(wallRight);

        // Mihrab Arch Alcove
        const mihrab = new THREE.Mesh(new THREE.BoxGeometry(2.4, 4.2, 0.3), new THREE.MeshStandardMaterial({ color: 0x15803d }));
        mihrab.position.set(0, 2.3, -5.0);
        parent.add(mihrab);

        // Quran Stands (Rihal)
        for (let qx of [-2.4, 2.4]) {
            const stand = new THREE.Mesh(new THREE.BoxGeometry(0.6, 0.45, 0.5), new THREE.MeshStandardMaterial({ color: 0x78350f }));
            stand.position.set(qx, 0.35, -2.5);
            parent.add(stand);

            this.registerInteractable(stand, 'quran_stand', '📖 Recite Holy Quran & Boost Inner Peace', () => {
                if (window.GameApp) {
                    GameApp.playSfx('win');
                    GameApp.notify('🕊️ Recited Surah Al-Fatiha in the sanctuary. (+30 Happiness, +10 Spiritual Peace)', 'success');
                }
            });
        }
    },

    buildChurchRoom(parent) {
        // Polished Hardwood Pews Floor
        const floor = new THREE.Mesh(new THREE.BoxGeometry(10.2, 0.2, 10.2), new THREE.MeshStandardMaterial({ color: 0x543310, roughness: 0.3 }));
        floor.position.y = 0.1;
        parent.add(floor);

        // Cream Gothic Walls
        const wallMat = new THREE.MeshStandardMaterial({ color: 0xfef3c7, roughness: 0.5 });
        const wallLeft = new THREE.Mesh(new THREE.BoxGeometry(10.4, 6.2, 0.4), wallMat);
        wallLeft.position.set(0, 3.3, -5.2);
        parent.add(wallLeft);

        const wallRight = new THREE.Mesh(new THREE.BoxGeometry(0.4, 6.2, 10.4), wallMat);
        wallRight.position.set(5.2, 3.3, 0);
        parent.add(wallRight);

        // Golden Cross Altar
        const crossV = new THREE.Mesh(new THREE.BoxGeometry(0.2, 2.4, 0.1), new THREE.MeshStandardMaterial({ color: 0xf59e0b, metalness: 0.9 }));
        crossV.position.set(0, 4.0, -4.95);
        parent.add(crossV);

        const crossH = new THREE.Mesh(new THREE.BoxGeometry(1.4, 0.2, 0.1), new THREE.MeshStandardMaterial({ color: 0xf59e0b, metalness: 0.9 }));
        crossH.position.set(0, 4.4, -4.95);
        parent.add(crossH);

        // Wooden Pews
        for (let pz of [-2.2, 0.2, 2.6]) {
            const pew = new THREE.Mesh(new THREE.BoxGeometry(5.4, 0.7, 0.6), new THREE.MeshStandardMaterial({ color: 0x78350f }));
            pew.position.set(0, 0.45, pz);
            parent.add(pew);
        }
    },

    buildMarketRoom(parent) {
        // Concrete Market Floor
        const floor = new THREE.Mesh(new THREE.BoxGeometry(10.2, 0.2, 10.2), new THREE.MeshStandardMaterial({ color: 0x64748b, roughness: 0.9 }));
        floor.position.y = 0.1;
        parent.add(floor);

        // Market Walls with Stalls
        const wallMat = new THREE.MeshStandardMaterial({ color: 0xcfd8dc, roughness: 0.7 });
        const wallLeft = new THREE.Mesh(new THREE.BoxGeometry(10.4, 6.2, 0.4), wallMat);
        wallLeft.position.set(0, 3.3, -5.2);
        parent.add(wallLeft);

        const wallRight = new THREE.Mesh(new THREE.BoxGeometry(0.4, 6.2, 10.4), wallMat);
        wallRight.position.set(5.2, 3.3, 0);
        parent.add(wallRight);

        // Fabric Stalls (Ankara & Lace)
        const fMatA = new THREE.MeshStandardMaterial({ color: 0xdc2626 });
        const fMatB = new THREE.MeshStandardMaterial({ color: 0x0284c7 });
        const stallA = new THREE.Mesh(new THREE.BoxGeometry(3.2, 1.8, 1.2), fMatA);
        stallA.position.set(-2.8, 1.0, -3.8);
        parent.add(stallA);

        const stallB = new THREE.Mesh(new THREE.BoxGeometry(3.2, 1.8, 1.2), fMatB);
        stallB.position.set(2.8, 1.0, -3.8);
        parent.add(stallB);

        this.registerInteractable(stallA, 'market_stall', '🛍️ Shop Designer Fabrics & Perfumes', () => {
            if (window.GameApp) GameApp.doDestinationActivity('market');
        });
    },

    buildRestaurantRoom(parent) {
        // Terracotta Floor
        const floor = new THREE.Mesh(new THREE.BoxGeometry(10.2, 0.2, 10.2), new THREE.MeshStandardMaterial({ color: 0xb45309, roughness: 0.5 }));
        floor.position.y = 0.1;
        parent.add(floor);

        // Warm Walls
        const wallMat = new THREE.MeshStandardMaterial({ color: 0xfef3c7 });
        const wallLeft = new THREE.Mesh(new THREE.BoxGeometry(10.4, 6.2, 0.4), wallMat);
        wallLeft.position.set(0, 3.3, -5.2);
        parent.add(wallLeft);

        const wallRight = new THREE.Mesh(new THREE.BoxGeometry(0.4, 6.2, 10.4), wallMat);
        wallRight.position.set(5.2, 3.3, 0);
        parent.add(wallRight);

        // Dining Tables with Food Plates
        for (let tx of [-2.4, 2.4]) {
            const table = new THREE.Mesh(new THREE.CylinderGeometry(1.2, 1.2, 0.15, 20), new THREE.MeshStandardMaterial({ color: 0x78350f }));
            table.position.set(tx, 1.1, 0);
            parent.add(table);

            // Jollof & Tilapia Plates
            const plate = new THREE.Mesh(new THREE.CylinderGeometry(0.35, 0.35, 0.05, 12), new THREE.MeshStandardMaterial({ color: 0xffffff }));
            plate.position.set(tx, 1.2, 0);
            parent.add(plate);

            this.registerInteractable(table, 'dining_table', '🍲 Tap Table: Order Smoky Jollof & Grilled Tilapia', () => {
                if (window.GameApp) GameApp.doDestinationActivity('restaurant', 'tilapia');
            });
        }
    },

    buildBanexRoom(parent) {
        // Sleek Tech Store Flooring
        const floor = new THREE.Mesh(new THREE.BoxGeometry(10.2, 0.2, 10.2), new THREE.MeshStandardMaterial({ color: 0x0f172a, metalness: 0.6, roughness: 0.2 }));
        floor.position.y = 0.1;
        parent.add(floor);

        const wallMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.4 });
        const wallLeft = new THREE.Mesh(new THREE.BoxGeometry(10.4, 6.2, 0.4), wallMat);
        wallLeft.position.set(0, 3.3, -5.2);
        parent.add(wallLeft);

        const wallRight = new THREE.Mesh(new THREE.BoxGeometry(0.4, 6.2, 10.4), wallMat);
        wallRight.position.set(5.2, 3.3, 0);
        parent.add(wallRight);

        // Glass Device Display Counters
        const counter = new THREE.Mesh(new THREE.BoxGeometry(4.8, 1.2, 1.2), new THREE.MeshStandardMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.65 }));
        counter.position.set(0, 0.8, -2.5);
        parent.add(counter);

        this.registerInteractable(counter, 'banex_counter', '📱 Tap Counter: Flip Refurbished iPhones (+Cash Profit)', () => {
            if (window.GameApp) GameApp.doDestinationActivity('banex_flip');
        });
    },

    // ─────────────────────────────────────────────────────────────────────────
    // SPAWN CITIZENS IN VENUE (Other users present with @username tags)
    // ─────────────────────────────────────────────────────────────────────────
    async spawnVenueCitizens(venueId, parentGroup) {
        let citizens = [];
        try {
            const res = await fetch('api/citizens.php?action=search');
            const data = await res.json();
            if (data.success && data.citizens && data.citizens.length > 0) {
                citizens = data.citizens;
            }
        } catch(e) {}

        // Fallback default lively citizens matching user screenshot
        if (citizens.length < 8) {
            citizens = [
                { username: 'priscil_mco', full_name: 'Priscilla M.', district: 'Maitama', job_title: 'Fitness Coach', gender: 'Female' },
                { username: 'kizbojstt', full_name: 'Kizito B.', district: 'Wuse 2', job_title: 'Tech Founder', gender: 'Male' },
                { username: 'greenfoot', full_name: 'David O.', district: 'Jabi', job_title: 'Runner', gender: 'Male' },
                { username: 'valteo', full_name: 'Valerie T.', district: 'CBD', job_title: 'Banker', gender: 'Female' },
                { username: 'Bisola', full_name: 'Bisola Ade', district: 'Asokoro', job_title: 'Diplomat', gender: 'Female' },
                { username: 'gmkaskayy', full_name: 'Gideon K.', district: 'Gwarinpa', job_title: 'Engineer', gender: 'Male' },
                { username: 'phynatna', full_name: 'Phyna T.', district: 'Wuse 2', job_title: 'Stylist', gender: 'Female' },
                { username: 'aminu_fct', full_name: 'Aminu Bello', district: 'Three Arms Zone', job_title: 'Special Assistant', gender: 'Male' },
                { username: 'zainab_fct', full_name: 'Zainab Ahmed', district: 'CBD Towers', job_title: 'FX Dealer', gender: 'Female' },
                { username: 'dapo_tech', full_name: 'Dapo Kunle', district: 'Banex Hub', job_title: 'Fullstack Dev', gender: 'Male' }
            ];
        }

        // Realistic spread positions matching reference screenshot
        const venueCoords = {
            gym: [
                [-4.2, 0.8], [-2.8, -1.8], [-2.4, 1.4], [-0.4, 0.4], [0.8, -2.4],
                [1.6, 2.2], [2.2, 2.6], [3.2, 1.2], [3.4, -1.2], [0.1, 3.2]
            ],
            mosque: [
                [-3.2, -2.4], [-1.2, -2.4], [1.2, -2.4], [3.2, -2.4],
                [-2.2, 1.2], [0.0, 1.2], [2.2, 1.2], [0.0, 3.2]
            ],
            home: [
                [0.0, 2.0] // In home, player stands in room
            ]
        };

        const coords = venueCoords[venueId] || [
            [-3.0, -1.5], [-1.5, -2.0], [1.5, -2.0], [3.0, -1.5],
            [-2.0, 1.5], [0.0, 1.5], [2.0, 1.5], [0.0, 3.0]
        ];

        citizens.slice(0, coords.length).forEach((c, idx) => {
            const pt = coords[idx];
            const hasCrown = (idx === 0 || c.street_cred > 60);
            const npc = this.createNpcFigure(c, hasCrown);
            npc.position.set(pt[0], 0.15, pt[1]);
            parentGroup.add(npc);
        });
    },

    createNpcFigure(citizen, hasCrown = false) {
        const group = new THREE.Group();
        group.userData = {
            type: 'citizen',
            username: (citizen.username || 'citizen').replace(/^@/, ''),
            name: citizen.full_name || citizen.username,
            job: citizen.job_title || 'Abuja Resident',
            district: citizen.district || 'Abuja FCT',
            cred: citizen.street_cred || 50
        };

        const isFemale = (citizen.gender === 'Female' || citizen.gender === 'Woman');
        const skinMat = new THREE.MeshStandardMaterial({ color: 0x5a3825, roughness: 0.6 });

        // Diverse outfit colors matching screenshot (green tops, pink pants, yellow shirts)
        const topColors = [0x10b981, 0xf59e0b, 0xef4444, 0x3b82f6, 0xec4899, 0xffffff];
        const botColors = [0x0f172a, 0xec4899, 0x3b82f6, 0xd97706, 0xffffff];
        const topCol = topColors[Math.abs(citizen.username.charCodeAt(0)) % topColors.length];
        const botCol = botColors[Math.abs(citizen.username.charCodeAt(1) || 0) % botColors.length];

        // Torso
        const torso = new THREE.Mesh(new THREE.CylinderGeometry(0.3, 0.36, 1.05, 12), new THREE.MeshStandardMaterial({ color: topCol }));
        torso.position.y = 1.4;
        torso.castShadow = true;
        group.add(torso);

        // Head
        const head = new THREE.Mesh(new THREE.SphereGeometry(0.32, 14, 14), skinMat);
        head.position.y = 2.15;
        head.castShadow = true;
        group.add(head);

        // Hair
        const hair = new THREE.Mesh(new THREE.SphereGeometry(0.34, 14, 14), new THREE.MeshStandardMaterial({ color: 0x111111 }));
        hair.position.set(0, 2.25, -0.04);
        group.add(hair);

        if (isFemale) {
            const skirt = new THREE.Mesh(new THREE.ConeGeometry(0.5, 0.8, 14), new THREE.MeshStandardMaterial({ color: botCol }));
            skirt.position.y = 0.8;
            skirt.castShadow = true;
            group.add(skirt);
        } else {
            const legGeo = new THREE.CylinderGeometry(0.11, 0.11, 0.85, 8);
            const legL = new THREE.Mesh(legGeo, new THREE.MeshStandardMaterial({ color: botCol }));
            legL.position.set(-0.15, 0.5, 0);
            group.add(legL);

            const legR = new THREE.Mesh(legGeo, new THREE.MeshStandardMaterial({ color: botCol }));
            legR.position.set(0.15, 0.5, 0);
            group.add(legR);
        }

        // Green Online Status Dot
        const dotMat = new THREE.MeshBasicMaterial({ color: 0x22c55e });
        const dot = new THREE.Mesh(new THREE.SphereGeometry(0.08, 8, 8), dotMat);
        dot.position.set(0, 2.7, 0);
        group.add(dot);

        // Floating @username Canvas Badge Sprite
        const badgeCanvas = document.createElement('canvas');
        badgeCanvas.width = 256; badgeCanvas.height = 64;
        const bctx = badgeCanvas.getContext('2d');

        // Rounded Pill background
        bctx.fillStyle = '#0284c7'; // Blue pill from screenshot
        bctx.beginPath();
        bctx.roundRect ? bctx.roundRect(16, 8, 224, 48, 24) : bctx.rect(16, 8, 224, 48);
        bctx.fill();

        bctx.strokeStyle = '#ffffff';
        bctx.lineWidth = 3;
        bctx.stroke();

        bctx.fillStyle = '#ffffff';
        bctx.font = 'bold 22px monospace';
        bctx.textAlign = 'center';
        bctx.textBaseline = 'middle';
        bctx.fillText('@' + group.userData.username, 128, 32);

        const tex = new THREE.CanvasTexture(badgeCanvas);
        const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, depthTest: false }));
        sprite.scale.set(3.2, 0.8, 1);
        sprite.position.set(0, 3.2, 0);
        group.add(sprite);

        // Golden Crown if VIP
        if (hasCrown) {
            const crown = new THREE.Mesh(new THREE.CylinderGeometry(0.24, 0.2, 0.14, 10), new THREE.MeshStandardMaterial({
                color: 0xf59e0b, metalness: 0.9, roughness: 0.1
            }));
            crown.position.set(0, 2.65, 0);
            group.add(crown);
        }

        // Clickable Hitbox
        const hit = new THREE.Mesh(new THREE.CylinderGeometry(0.8, 0.8, 3.2, 8), new THREE.MeshBasicMaterial({ visible: false }));
        hit.position.y = 1.6;
        hit.userData = group.userData;
        group.add(hit);

        this.registerInteractable(group, 'citizen_' + group.userData.username, '👤 @' + group.userData.username + ' (Tap to Chat & Connect)', () => {
            if (window.GameApp && typeof GameApp.inspectCitizenFromMap === 'function') {
                GameApp.inspectCitizenFromMap(
                    group.userData.name,
                    group.userData.username,
                    group.userData.job,
                    group.userData.district,
                    group.userData.cred
                );
            }
        });

        return group;
    },

    createPlayerAvatarInRoom(parent, x, y, z) {
        if (this.characterGroup) {
            parent.remove(this.characterGroup);
        }

        const char = (window.GameApp && GameApp.character) ? GameApp.character : {};
        const isFemale = (char.gender === 'Female' || char.gender === 'Woman');

        this.characterGroup = new THREE.Group();
        this.characterGroup.position.set(x, y, z);

        const skinMat = new THREE.MeshStandardMaterial({ color: 0x613d29, roughness: 0.6 });
        const topMat = new THREE.MeshStandardMaterial({ color: 0xf59e0b, roughness: 0.5 }); // Orange/Yellow top
        const botMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.5 }); // Black skirt

        // Torso
        const torso = new THREE.Mesh(new THREE.CylinderGeometry(0.32, 0.38, 1.1, 14), topMat);
        torso.position.y = 1.45;
        torso.castShadow = true;
        this.characterGroup.add(torso);

        // Head
        const head = new THREE.Mesh(new THREE.SphereGeometry(0.34, 16, 16), skinMat);
        head.position.y = 2.25;
        head.castShadow = true;
        this.characterGroup.add(head);

        // Hair
        const hair = new THREE.Mesh(new THREE.SphereGeometry(0.36, 16, 16), new THREE.MeshStandardMaterial({ color: 0x111111 }));
        hair.position.set(0, 2.35, -0.05);
        this.characterGroup.add(hair);

        if (isFemale) {
            const bun = new THREE.Mesh(new THREE.SphereGeometry(0.2, 12, 12), new THREE.MeshStandardMaterial({ color: 0x111111 }));
            bun.position.set(0, 2.65, -0.15);
            this.characterGroup.add(bun);

            const skirt = new THREE.Mesh(new THREE.ConeGeometry(0.55, 0.85, 16), botMat);
            skirt.position.y = 0.85;
            skirt.castShadow = true;
            this.characterGroup.add(skirt);
        } else {
            const legGeo = new THREE.CylinderGeometry(0.12, 0.12, 0.9, 10);
            const legL = new THREE.Mesh(legGeo, botMat);
            legL.position.set(-0.16, 0.55, 0);
            this.characterGroup.add(legL);

            const legR = new THREE.Mesh(legGeo, botMat);
            legR.position.set(0.16, 0.55, 0);
            this.characterGroup.add(legR);
        }

        // Golden Crown
        const crownGroup = new THREE.Group();
        crownGroup.position.set(0, 2.85, 0);
        const crownMat = new THREE.MeshStandardMaterial({ color: 0xf59e0b, metalness: 0.9, roughness: 0.2 });
        crownGroup.add(new THREE.Mesh(new THREE.CylinderGeometry(0.28, 0.24, 0.18, 12, 1, true), crownMat));
        this.crownMesh = crownGroup;
        this.characterGroup.add(crownGroup);

        this.registerInteractable(this.characterGroup, 'player_character', '👑 Tap Character: Wardrobe & Outfits', () => {
            if (window.GameApp && typeof GameApp.openAvatarCustomizerModal === 'function') {
                GameApp.openAvatarCustomizerModal();
            }
        });

        parent.add(this.characterGroup);
    },

    // ── PROPS BUILDERS HELPERS ───────────────────────────────────────────────
    createDoor(parent, x, y, z) {
        const doorGroup = new THREE.Group();
        doorGroup.position.set(x, y, z);
        doorGroup.add(new THREE.Mesh(new THREE.BoxGeometry(2.1, 4.4, 0.16), new THREE.MeshStandardMaterial({ color: 0x2e1809 })));
        doorGroup.add(new THREE.Mesh(new THREE.BoxGeometry(1.85, 4.15, 0.18), new THREE.MeshStandardMaterial({ color: 0x45230c })));
        const knob = new THREE.Mesh(new THREE.SphereGeometry(0.08, 12, 12), new THREE.MeshStandardMaterial({ color: 0xd97706, metalness: 0.9 }));
        knob.position.set(0.65, -0.2, 0.15);
        doorGroup.add(knob);

        this.registerInteractable(doorGroup, 'door', '🚪 Tap Door to Go Out', () => {
            if (window.GameApp) {
                GameApp.switchMainView('map');
                GameApp.notify('Stepping out into the streets of Abuja...', 'info');
            }
        });
        parent.add(doorGroup);
    },

    createWallSconce(parent, x, y, z, rotY) {
        const sconce = new THREE.Group();
        sconce.position.set(x, y, z);
        sconce.rotation.y = rotY;
        sconce.add(new THREE.Mesh(new THREE.BoxGeometry(0.25, 0.4, 0.2), new THREE.MeshStandardMaterial({ color: 0x8a633c })));
        const bulb = new THREE.Mesh(new THREE.SphereGeometry(0.18, 16, 16), new THREE.MeshStandardMaterial({
            color: 0xffedd5, emissive: 0xfef08a, emissiveIntensity: 1.2
        }));
        bulb.position.set(0, -0.15, 0.22);
        sconce.add(bulb);
        parent.add(sconce);
    },

    createWallCalendar(parent, x, y, z) {
        const cal = new THREE.Mesh(new THREE.PlaneGeometry(0.9, 1.4), new THREE.MeshStandardMaterial({ color: 0xf8fafc }));
        cal.position.set(x, y, z);
        parent.add(cal);
    },

    createBed(parent, x, y, z) {
        const bedGroup = new THREE.Group();
        bedGroup.position.set(x, y, z);
        const woodMat = new THREE.MeshStandardMaterial({ color: 0x936639 });
        bedGroup.add(new THREE.Mesh(new THREE.BoxGeometry(2.4, 0.4, 3.2), woodMat));

        const head = new THREE.Mesh(new THREE.BoxGeometry(2.4, 1.6, 0.2), woodMat);
        head.position.set(0, 0.6, -1.5);
        bedGroup.add(head);

        const redDuvet = new THREE.Mesh(new THREE.BoxGeometry(2.2, 0.45, 2.9), new THREE.MeshStandardMaterial({ color: 0xdc2626 }));
        redDuvet.position.set(0, 0.35, 0.05);
        bedGroup.add(redDuvet);

        const pillow = new THREE.Mesh(new THREE.BoxGeometry(1.4, 0.22, 0.7), new THREE.MeshStandardMaterial({ color: 0xf8fafc }));
        pillow.position.set(0, 0.6, -0.9);
        bedGroup.add(pillow);

        this.registerInteractable(bedGroup, 'bed', '🛏️ Tap Bed to Sleep & Rest', () => {
            if (window.GameApp) GameApp.interactFurniture('bed');
        });
        parent.add(bedGroup);
    },

    createFloorMat(parent, x, y, z) {
        const matGroup = new THREE.Group();
        matGroup.position.set(x, y, z);
        matGroup.add(new THREE.Mesh(new THREE.BoxGeometry(1.7, 0.18, 2.6), new THREE.MeshStandardMaterial({ color: 0x0284c7 })));
        const pil = new THREE.Mesh(new THREE.BoxGeometry(1.1, 0.16, 0.55), new THREE.MeshStandardMaterial({ color: 0xffffff }));
        pil.position.set(0, 0.16, -0.85);
        matGroup.add(pil);

        this.registerInteractable(matGroup, 'floor_mat', '🛏️ Rest on Foam Mat', () => {
            if (window.GameApp) GameApp.interactFurniture('bed');
        });
        parent.add(matGroup);
    },

    createBookshelf(parent, x, y, z) {
        const shelfGroup = new THREE.Group();
        shelfGroup.position.set(x, y, z);
        shelfGroup.add(new THREE.Mesh(new THREE.BoxGeometry(1.8, 4.4, 0.8), new THREE.MeshStandardMaterial({ color: 0x936639 })));

        const bColors = [0xef4444, 0x3b82f6, 0x10b981, 0xf59e0b, 0x8b5cf6];
        for (let tier = -1.2; tier <= 1.2; tier += 0.9) {
            for (let bx = -0.65; bx <= 0.65; bx += 0.22) {
                const book = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.6, 0.5), new THREE.MeshLambertMaterial({
                    color: bColors[Math.abs(Math.floor(bx * 10)) % bColors.length]
                }));
                book.position.set(bx, tier, 0.1);
                shelfGroup.add(book);
            }
        }

        this.registerInteractable(shelfGroup, 'books', '📚 Tap Books to Read & Study', () => {
            if (window.GameApp) GameApp.interactFurniture('books');
        });
        parent.add(shelfGroup);
    },

    createRadioStool(parent, x, y, z) {
        const stoolGroup = new THREE.Group();
        stoolGroup.position.set(x, y, z);
        stoolGroup.add(new THREE.Mesh(new THREE.BoxGeometry(0.9, 1.4, 0.7), new THREE.MeshStandardMaterial({ color: 0xb08050 })));

        const radio = new THREE.Mesh(new THREE.BoxGeometry(0.65, 0.45, 0.35), new THREE.MeshStandardMaterial({ color: 0x334155, metalness: 0.6 }));
        radio.position.y = 0.9;
        stoolGroup.add(radio);

        this.registerInteractable(stoolGroup, 'radio', '📻 Tap Radio to Play Afrobeats', () => {
            if (window.GameApp) GameApp.interactFurniture('radio');
        });
        parent.add(stoolGroup);
    },

    createStudyDesk(parent, x, y, z) {
        const deskGroup = new THREE.Group();
        deskGroup.position.set(x, y, z);
        deskGroup.add(new THREE.Mesh(new THREE.BoxGeometry(0.8, 1.8, 1.8), new THREE.MeshStandardMaterial({ color: 0x936639 })));
        const mug = new THREE.Mesh(new THREE.CylinderGeometry(0.12, 0.12, 0.28, 12), new THREE.MeshStandardMaterial({ color: 0x15803d }));
        mug.position.set(-0.1, 1.05, 0);
        deskGroup.add(mug);

        this.registerInteractable(deskGroup, 'desk', '💻 Tap Desk to Code & Freelance', () => {
            if (window.GameApp) GameApp.interactFurniture('mac_workstation');
        });
        parent.add(deskGroup);
    },

    createWindowBlinds(parent, x, y, z) {
        const winGroup = new THREE.Group();
        winGroup.position.set(x, y, z);
        winGroup.rotation.y = -Math.PI / 2;
        winGroup.add(new THREE.Mesh(new THREE.BoxGeometry(2.8, 2.2, 0.1), new THREE.MeshStandardMaterial({ color: 0xf1f5f9 })));
        winGroup.add(new THREE.Mesh(new THREE.PlaneGeometry(2.5, 1.9), new THREE.MeshBasicMaterial({ color: 0xbae6fd })));
        for (let by = -0.8; by <= 0.8; by += 0.2) {
            const slat = new THREE.Mesh(new THREE.BoxGeometry(2.5, 0.06, 0.08), new THREE.MeshBasicMaterial({ color: 0xffffff }));
            slat.position.set(0, by, 0.05);
            winGroup.add(slat);
        }
        parent.add(winGroup);
    },

    createCooler(parent, x, y, z) {
        const coolerGroup = new THREE.Group();
        coolerGroup.position.set(x, y, z);
        coolerGroup.add(new THREE.Mesh(new THREE.BoxGeometry(0.9, 0.6, 1.3), new THREE.MeshStandardMaterial({ color: 0x1d4ed8 })));
        const lid = new THREE.Mesh(new THREE.BoxGeometry(0.96, 0.16, 1.36), new THREE.MeshStandardMaterial({ color: 0xffffff }));
        lid.position.y = 0.35;
        coolerGroup.add(lid);

        this.registerInteractable(coolerGroup, 'cooler', '🧊 Tap Cooler to Eat Food', () => {
            if (window.GameApp) GameApp.interactFurniture('cooler');
        });
        parent.add(coolerGroup);
    },

    createWaterSupply(parent, x, y, z) {
        const drumGroup = new THREE.Group();
        drumGroup.position.set(x, y, z);
        drumGroup.add(new THREE.Mesh(new THREE.CylinderGeometry(0.65, 0.65, 1.8, 20), new THREE.MeshStandardMaterial({ color: 0x1e3a8a })));
        this.registerInteractable(drumGroup, 'water_drum', '💧 Water Drum: Clean Water Reservoir', () => {
            if (window.GameApp) GameApp.notify('💧 Checked 100L drum: Borehole water is clean & full!', 'info');
        });
        parent.add(drumGroup);

        const canMat = new THREE.MeshStandardMaterial({ color: 0xeab308 });
        for (let cz of [z + 1.1, z + 1.6]) {
            const can = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.75, 0.55), canMat);
            can.position.set(x, 0.45, cz);
            parent.add(can);
        }
    },

    createCenterChair(parent, x, y, z) {
        const chairGroup = new THREE.Group();
        chairGroup.position.set(x, y, z);
        const redMat = new THREE.MeshStandardMaterial({ color: 0xb91c1c });
        chairGroup.add(new THREE.Mesh(new THREE.BoxGeometry(0.85, 0.1, 0.85), redMat));

        const back = new THREE.Mesh(new THREE.BoxGeometry(0.85, 0.9, 0.08), redMat);
        back.position.set(0, 0.45, -0.38);
        chairGroup.add(back);

        this.registerInteractable(chairGroup, 'chair', '🪑 Tap Chair to Sit & Relax', () => {
            if (window.GameApp) GameApp.interactFurniture('chair');
        });
        parent.add(chairGroup);
    },

    createBuckets(parent, x1, x2, z) {
        const rBucket = new THREE.Mesh(new THREE.CylinderGeometry(0.42, 0.32, 0.65, 18), new THREE.MeshStandardMaterial({ color: 0xdc2626 }));
        rBucket.position.set(x1, 0.4, z);
        this.registerInteractable(rBucket, 'bucket_red', '🪣 Tap Bucket to Bathe & Freshen Up', () => {
            if (window.GameApp) GameApp.interactFurniture('bucket');
        });
        parent.add(rBucket);

        const bBucket = new THREE.Mesh(new THREE.CylinderGeometry(0.42, 0.32, 0.65, 18), new THREE.MeshStandardMaterial({ color: 0x0284c7 }));
        bBucket.position.set(x2, 0.4, z);
        this.registerInteractable(bBucket, 'bucket_blue', '🪣 Tap Bucket to Bathe & Freshen Up', () => {
            if (window.GameApp) GameApp.interactFurniture('bucket');
        });
        parent.add(bBucket);
    },

    // ─────────────────────────────────────────────────────────────────────────
    // RAYCASTING & INTERACTION
    // ─────────────────────────────────────────────────────────────────────────
    registerInteractable(rootObject, id, label, actionCallback) {
        rootObject.userData = { id, label, action: actionCallback };

        rootObject.traverse(child => {
            if (child.isMesh) {
                child.userData = rootObject.userData;
                this.interactables.push(child);
            }
        });
    },

    getIntersection(e) {
        if (!this.renderer || !this.camera) return null;
        const rect = this.renderer.domElement.getBoundingClientRect();
        this.mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
        this.mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;

        this.raycaster.setFromCamera(this.mouse, this.camera);
        const hits = this.raycaster.intersectObjects(this.interactables, false);
        return hits.length > 0 ? hits[0] : null;
    },

    onMouseMove(e) {
        if (!this.isInitialized) return;
        const hit = this.getIntersection(e);

        if (hit && hit.object.userData && hit.object.userData.label) {
            this.container.style.cursor = 'pointer';
            this.currentHoverObject = hit.object;

            if (this.tooltipEl) {
                const rect = this.container.getBoundingClientRect();
                this.tooltipEl.style.left = (e.clientX - rect.left) + 'px';
                this.tooltipEl.style.top = (e.clientY - rect.top) + 'px';
                this.tooltipEl.textContent = hit.object.userData.label;
                this.tooltipEl.style.opacity = '1';
                this.tooltipEl.style.transform = 'translate(-50%, -130%) scale(1)';
            }
        } else {
            this.container.style.cursor = 'default';
            this.currentHoverObject = null;
            if (this.tooltipEl) {
                this.tooltipEl.style.opacity = '0';
                this.tooltipEl.style.transform = 'translate(-50%, -130%) scale(0.9)';
            }
        }
    },

    onMouseLeave() {
        if (this.tooltipEl) this.tooltipEl.style.opacity = '0';
        this.currentHoverObject = null;
    },

    onClick(e) {
        if (!this.isInitialized) return;
        const hit = this.getIntersection(e);

        if (hit && hit.object.userData && typeof hit.object.userData.action === 'function') {
            if (window.GameApp && typeof GameApp.playSfx === 'function') {
                GameApp.playSfx('click');
            }
            hit.object.userData.action();
        }
    },

    onResize() {
        if (!this.container || !this.renderer || !this.camera) return;
        const w = this.container.clientWidth;
        const h = this.container.clientHeight;
        this.camera.aspect = w / h;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(w, h);
    },

    updateScene() {
        this.buildVenueScene(this.currentVenue);
    },

    animate() {
        requestAnimationFrame(() => this.animate());

        if (this.controls) {
            this.controls.update();
        }

        const time = Date.now() * 0.003;

        // Idle crown floating
        if (this.crownMesh) {
            this.crownMesh.position.y = 2.85 + Math.sin(time * 2) * 0.06;
            this.crownMesh.rotation.y += 0.015;
        }

        if (this.renderer && this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }
};

window.World3D = World3D;
