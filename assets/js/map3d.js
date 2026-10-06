/**
 * Abuja Life 3D Map Engine (Three.js WebGL)
 * Ultra-crisp, high-fidelity isometric 3D city replica of Abuja FCT.
 * Matches real low-poly reference: Airport with airplanes & runways,
 * Zuma Rock, Aso Rock, Stadium, 4 Roundabouts, National Mosque,
 * National Church, CBD towers, animated traffic, and interactive pins.
 * Renders dynamically on GPU — NO pixelation, NO blur when zooming in.
 */

const Map3D = {
    container: null,
    scene: null,
    camera: null,
    renderer: null,
    controls: null,
    raycaster: null,
    mouse: null,
    interactables: [],
    animatedCars: [],
    pinSprites: [],
    isInitialized: false,

    init(containerId = 'map3d-container', onPinClick = null, onCitizenClick = null) {
        this.container = document.getElementById(containerId);
        if (!this.container || typeof THREE === 'undefined') return;

        this.onPinClick = onPinClick;
        this.onCitizenClick = onCitizenClick;

        const width = this.container.clientWidth || window.innerWidth;
        const height = this.container.clientHeight || window.innerHeight;

        // Scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0xbddcb8); // Soft outdoor atmospheric tint
        this.scene.fog = new THREE.Fog(0xbddcb8, 160, 320);

        // Isometric Camera (High altitude perspective angle)
        this.camera = new THREE.PerspectiveCamera(38, width / height, 1, 1000);
        this.camera.position.set(0, 110, 140);

        // WebGL Renderer with High Precision & Antialiasing
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        this.container.innerHTML = '';
        this.container.appendChild(this.renderer.domElement);

        // OrbitControls for Smooth Navigation & Deep Zoom
        if (typeof THREE.OrbitControls !== 'undefined') {
            this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.08;
            this.controls.target.set(0, 0, 5);
            this.controls.maxPolarAngle = Math.PI / 2.3; // Prevent dipping beneath ground
            this.controls.minDistance = 18;  // Allow zooming right into street level
            this.controls.maxDistance = 240; // Allow full bird's-eye view
            this.controls.screenSpacePanning = true;
        }

        // Setup Raycaster
        this.raycaster = new THREE.Raycaster();
        this.mouse = new THREE.Vector2();

        // Lighting Architecture
        this.setupLighting();

        // Build City 3D Environment
        this.buildAbujaWorld();

        // Event Listeners
        this.container.addEventListener('click', this.onClick.bind(this));
        this.container.addEventListener('mousemove', this.onMouseMove.bind(this));
        window.addEventListener('resize', this.onResize.bind(this));

        this.isInitialized = true;
        this.animate();
    },

    setupLighting() {
        // Warm Sunlight
        const sun = new THREE.DirectionalLight(0xfffdf0, 1.15);
        sun.position.set(60, 140, 80);
        sun.castShadow = true;
        sun.shadow.mapSize.width = 2048;
        sun.shadow.mapSize.height = 2048;
        sun.shadow.camera.near = 10;
        sun.shadow.camera.far = 350;
        sun.shadow.camera.left = -110;
        sun.shadow.camera.right = 110;
        sun.shadow.camera.top = 110;
        sun.shadow.camera.bottom = -110;
        sun.shadow.bias = -0.0005;
        this.scene.add(sun);

        // Ambient Fill
        const ambient = new THREE.AmbientLight(0xd8e6f0, 0.75);
        this.scene.add(ambient);

        // Soft Hemisphere Ground Bounce
        const hemi = new THREE.HemisphereLight(0xffffff, 0x6e8e5e, 0.45);
        this.scene.add(hemi);
    },

    // ─────────────────────────────────────────────────────────────────────────
    // WORLD GEOMETRY BUILDER
    // ─────────────────────────────────────────────────────────────────────────
    buildAbujaWorld() {
        this.interactables = [];
        this.animatedCars = [];
        this.pinSprites = [];

        const city = new THREE.Group();

        // 1. Base Terrain (Large 220x180 Ground)
        const groundGeo = new THREE.PlaneGeometry(220, 180);
        const groundMat = new THREE.MeshLambertMaterial({ color: 0x98bf82 });
        const ground = new THREE.Mesh(groundGeo, groundMat);
        ground.rotation.x = -Math.PI / 2;
        ground.receiveShadow = true;
        city.add(ground);

        // Perimeter Hills & Lush Green Boundary
        this.buildPerimeterHills(city);

        // 2. Zuma Rock (Left Mountain) & Aso Rock (Right Mountain)
        this.buildMountains(city);

        // 3. Roads, Roundabouts & Highways Network
        this.buildRoadNetwork(city);

        // 4. Nnamdi Azikiwe International Airport (Bottom-Left)
        this.buildAirport(city);

        // 5. Moshood Abiola National Stadium (Bottom-Center)
        this.buildStadium(city);

        // 6. Jabi Lake & Waterfront Resort (Left-Center)
        this.buildJabiLake(city);

        // 7. Iconic Central Landmarks
        this.buildNationalMosque(city);
        this.buildNationalChurch(city);
        this.buildCBDTowers(city);
        this.buildThreeArmsZone(city);
        this.buildHotelsAndMalls(city);
        this.buildHospital(city);

        // 8. Residential & Suburban Districts (Gwarimpa, Garki, Maitama Villas)
        this.buildNeighbourhoods(city);

        // 9. Billboards
        this.buildBillboards(city);

        // 10. Interactive 3D Location Pins
        this.buildLocationPins(city);

        // 11. Roaming Citizens
        this.loadRoamingCitizens(city);

        this.scene.add(city);
    },

    // ── PERIMETER HILLS ─────────────────────────────────────────────────────
    buildPerimeterHills(parent) {
        const hillMat = new THREE.MeshLambertMaterial({ color: 0x6e9058, flatShading: true });
        const hillGeo = new THREE.DodecahedronGeometry(14, 1);

        // Place hills along borders
        const hillCoords = [
            [-110, -85], [-90, -88], [-60, -90], [-30, -92], [0, -92], [30, -90], [60, -88], [90, -85], [110, -85],
            [-112, -50], [-114, -10], [-115, 30], [-114, 70], [-110, 90],
            [112, -50], [114, -10], [115, 30], [114, 70], [110, 90],
            [-80, 92], [-40, 94], [0, 95], [40, 94], [80, 92]
        ];

        hillCoords.forEach(([hx, hz], idx) => {
            const h = new THREE.Mesh(hillGeo, hillMat);
            const scaleY = 1.0 + (idx % 3) * 0.4;
            h.scale.set(1.4, scaleY, 1.4);
            h.position.set(hx, 4, hz);
            h.castShadow = true;
            h.receiveShadow = true;
            parent.add(h);
        });
    },

    // ── ZUMA ROCK & ASO ROCK ────────────────────────────────────────────────
    buildMountains(parent) {
        const rockMat = new THREE.MeshLambertMaterial({ color: 0x6e6357, flatShading: true });

        // Zuma Rock (Tall monolith on left: x: -80, z: 5)
        const zumaGeo = new THREE.CylinderGeometry(11, 16, 28, 9);
        const zuma = new THREE.Mesh(zumaGeo, rockMat);
        zuma.position.set(-80, 14, 5);
        zuma.rotation.y = 0.3;
        zuma.castShadow = true;
        zuma.receiveShadow = true;
        parent.add(zuma);

        // Zuma base bumps
        const bumpGeo = new THREE.DodecahedronGeometry(6, 1);
        [[-88, 3, 2], [-74, 3, 12], [-82, 3, -8]].forEach(([bx, by, bz]) => {
            const b = new THREE.Mesh(bumpGeo, rockMat);
            b.position.set(bx, by, bz);
            b.castShadow = true;
            parent.add(b);
        });

        // Aso Rock (Massive sprawled rock on right: x: 78, z: 0)
        const asoGeo = new THREE.DodecahedronGeometry(18, 1);
        const aso = new THREE.Mesh(asoGeo, rockMat);
        aso.scale.set(1.4, 0.9, 1.3);
        aso.position.set(78, 12, 0);
        aso.castShadow = true;
        aso.receiveShadow = true;
        parent.add(aso);

        const asoChild = new THREE.Mesh(new THREE.DodecahedronGeometry(12, 1), rockMat);
        asoChild.position.set(90, 8, 8);
        asoChild.castShadow = true;
        parent.add(asoChild);
    },

    // ── ROADS & 4 ROUNDABOUTS ───────────────────────────────────────────────
    buildRoadNetwork(parent) {
        const asphaltMat = new THREE.MeshLambertMaterial({ color: 0x3e454f });
        const markingMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
        const islandMat = new THREE.MeshLambertMaterial({ color: 0x68a554 });
        const curbMat = new THREE.MeshLambertMaterial({ color: 0xa8b0b8 });

        // 4 Key Roundabouts (matching the reference layout)
        const roundabouts = [
            { x: -32, z: -8 },
            { x: 28,  z: -8 },
            { x: -16, z: 24 },
            { x: 38,  z: 24 }
        ];

        roundabouts.forEach(rb => {
            // Outer Asphalt Ring
            const ringGeo = new THREE.RingGeometry(4, 9, 28);
            const ring = new THREE.Mesh(ringGeo, asphaltMat);
            ring.rotation.x = -Math.PI / 2;
            ring.position.set(rb.x, 0.05, rb.z);
            ring.receiveShadow = true;
            parent.add(ring);

            // Curb Ring
            const curbGeo = new THREE.CylinderGeometry(4.1, 4.1, 0.4, 24);
            const curb = new THREE.Mesh(curbGeo, curbMat);
            curb.position.set(rb.x, 0.2, rb.z);
            parent.add(curb);

            // Center Green Island
            const islGeo = new THREE.CylinderGeometry(3.9, 3.9, 0.45, 24);
            const isl = new THREE.Mesh(islGeo, islandMat);
            isl.position.set(rb.x, 0.25, rb.z);
            parent.add(isl);

            // Center Palm Tree / Monument
            this.buildTree(parent, rb.x, rb.z, 1.4);
        });

        // Main Highways (Connecting airport, center, Maitama, Asokoro)
        const roadSegments = [
            // Shehu Shagari Way (Center N-S artery)
            { x: -2, z: 0, w: 7, l: 140, rot: 0 },
            // Airport Expressway (Diag connecting bottom-left airport to center)
            { x: -50, z: 40, w: 7, l: 80, rot: -0.65 },
            // Inner E-W Ring 1
            { x: 0, z: -8, w: 120, l: 6.5, rot: 0 },
            // Inner E-W Ring 2 (Southern Boulevard)
            { x: 0, z: 24, w: 120, l: 6.5, rot: 0 },
            // Eastern Avenue to Asokoro
            { x: 55, z: 10, w: 6.5, l: 110, rot: 0 },
            // Western Avenue to Jabi / Gwarimpa
            { x: -55, z: -15, w: 6.5, l: 100, rot: 0 },
            // Cross Highway North
            { x: 0, z: -46, w: 130, l: 6, rot: 0 }
        ];

        roadSegments.forEach(seg => {
            const rGeo = new THREE.PlaneGeometry(seg.w, seg.l);
            const rMesh = new THREE.Mesh(rGeo, asphaltMat);
            rMesh.rotation.x = -Math.PI / 2;
            if (seg.rot) rMesh.rotation.z = seg.rot;
            rMesh.position.set(seg.x, 0.04, seg.z);
            rMesh.receiveShadow = true;
            parent.add(rMesh);
        });

        // Spawn Animated Traffic Cars
        this.spawnTrafficCars(parent);
    },

    spawnTrafficCars(parent) {
        const carColors = [0xef4444, 0x3b82f6, 0xf59e0b, 0xffffff, 0x10b981, 0x8b5cf6, 0x111827];
        const carGeo = new THREE.BoxGeometry(1.4, 0.8, 2.6);
        const roofGeo = new THREE.BoxGeometry(1.2, 0.6, 1.4);

        for (let i = 0; i < 14; i++) {
            const col = carColors[i % carColors.length];
            const carMat = new THREE.MeshLambertMaterial({ color: col });
            const roofMat = new THREE.MeshLambertMaterial({ color: 0x1e293b });

            const carGroup = new THREE.Group();
            const body = new THREE.Mesh(carGeo, carMat);
            body.position.y = 0.5;
            body.castShadow = true;
            carGroup.add(body);

            const roof = new THREE.Mesh(roofGeo, roofMat);
            roof.position.set(0, 1.0, -0.2);
            carGroup.add(roof);

            // Assign routes
            const isHorizontal = i % 2 === 0;
            carGroup.userData = {
                type: isHorizontal ? 'H' : 'V',
                speed: 0.18 + Math.random() * 0.12,
                min: isHorizontal ? -55 : -60,
                max: isHorizontal ? 55 : 60,
                laneY: isHorizontal ? (i < 7 ? -8.8 : 22.8) : (i < 4 ? -2.8 : 53.8)
            };

            if (isHorizontal) {
                carGroup.position.set((Math.random() - 0.5) * 100, 0, carGroup.userData.laneY);
                carGroup.rotation.y = Math.PI / 2;
            } else {
                carGroup.position.set(carGroup.userData.laneY, 0, (Math.random() - 0.5) * 100);
            }

            parent.add(carGroup);
            this.animatedCars.push(carGroup);
        }
    },

    // ── AIRPORT (Bottom Left) ────────────────────────────────────────────────
    buildAirport(parent) {
        const apronMat = new THREE.MeshLambertMaterial({ color: 0xd1d5db });
        const runwayMat = new THREE.MeshLambertMaterial({ color: 0x334155 });
        const termMat = new THREE.MeshLambertMaterial({ color: 0xf8fafc });
        const glassMat = new THREE.MeshLambertMaterial({ color: 0x38bdf8 });

        const airportGroup = new THREE.Group();
        airportGroup.position.set(-58, 0, 52);

        // Apron Ground
        const apron = new THREE.Mesh(new THREE.PlaneGeometry(55, 34), apronMat);
        apron.rotation.x = -Math.PI / 2;
        apron.position.set(0, 0.05, 0);
        apron.receiveShadow = true;
        airportGroup.add(apron);

        // Dual Runways with Runway Markings
        const r1 = new THREE.Mesh(new THREE.PlaneGeometry(52, 4.5), runwayMat);
        r1.rotation.x = -Math.PI / 2;
        r1.position.set(0, 0.07, -11);
        airportGroup.add(r1);

        const r2 = new THREE.Mesh(new THREE.PlaneGeometry(52, 4.5), runwayMat);
        r2.rotation.x = -Math.PI / 2;
        r2.position.set(0, 0.07, -5);
        airportGroup.add(r2);

        // Terminal Hall (Curved glass front)
        const term = new THREE.Mesh(new THREE.BoxGeometry(38, 4.5, 9), termMat);
        term.position.set(0, 2.25, 6);
        term.castShadow = true;
        airportGroup.add(term);

        const termGlass = new THREE.Mesh(new THREE.BoxGeometry(34, 3, 2), glassMat);
        termGlass.position.set(0, 2.5, 1.5);
        airportGroup.add(termGlass);

        // Air Traffic Control Tower
        const towerShaft = new THREE.Mesh(new THREE.CylinderGeometry(1.2, 1.6, 12, 12), termMat);
        towerShaft.position.set(22, 6, 8);
        towerShaft.castShadow = true;
        airportGroup.add(towerShaft);

        const towerCabin = new THREE.Mesh(new THREE.CylinderGeometry(2.4, 1.8, 2.5, 12), glassMat);
        towerCabin.position.set(22, 12.5, 8);
        airportGroup.add(towerCabin);

        // 6 Airplanes on Apron
        const planeOffsets = [-18, -10, -2, 6, 14, 21];
        planeOffsets.forEach(px => {
            const plane = this.createAirplaneMesh();
            plane.position.set(px, 0.5, 0.5);
            plane.rotation.y = Math.PI;
            airportGroup.add(plane);
        });

        // Car Park with Rows of Parked Vehicles
        const parkGeo = new THREE.BoxGeometry(0.8, 0.5, 1.5);
        const pColors = [0xef4444, 0x3b82f6, 0xffffff, 0x10b981, 0x0f172a, 0xf59e0b];
        for (let r = 0; r < 2; r++) {
            for (let c = -14; c <= 14; c += 2.2) {
                const pMesh = new THREE.Mesh(parkGeo, new THREE.MeshLambertMaterial({ color: pColors[Math.abs(Math.floor(c)) % pColors.length] }));
                pMesh.position.set(c, 0.35, 12.5 + r * 2.2);
                airportGroup.add(pMesh);
            }
        }

        parent.add(airportGroup);
    },

    createAirplaneMesh() {
        const plane = new THREE.Group();
        const whiteMat = new THREE.MeshLambertMaterial({ color: 0xffffff });
        const wingMat = new THREE.MeshLambertMaterial({ color: 0x94a3b8 });

        // Fuselage
        const body = new THREE.Mesh(new THREE.CylinderGeometry(0.55, 0.55, 4.5, 10), whiteMat);
        body.rotation.x = Math.PI / 2;
        body.castShadow = true;
        plane.add(body);

        // Nose Cone
        const nose = new THREE.Mesh(new THREE.ConeGeometry(0.55, 1.2, 10), whiteMat);
        nose.rotation.x = -Math.PI / 2;
        nose.position.z = 2.8;
        plane.add(nose);

        // Main Wings
        const wings = new THREE.Mesh(new THREE.BoxGeometry(4.8, 0.12, 1.2), wingMat);
        wings.position.set(0, 0.1, 0.2);
        plane.add(wings);

        // Tail Wings
        const tail = new THREE.Mesh(new THREE.BoxGeometry(1.8, 0.1, 0.6), wingMat);
        tail.position.set(0, 0.2, -2.1);
        plane.add(tail);

        // Tail Fin
        const fin = new THREE.Mesh(new THREE.BoxGeometry(0.12, 1.0, 0.8), new THREE.MeshLambertMaterial({ color: 0x0284c7 }));
        fin.position.set(0, 0.7, -2.0);
        plane.add(fin);

        plane.scale.set(0.9, 0.9, 0.9);
        return plane;
    },

    // ── STADIUM (Bottom Center) ─────────────────────────────────────────────
    buildStadium(parent) {
        const stadGroup = new THREE.Group();
        stadGroup.position.set(-20, 0, 52);

        // Oval Outer Stands
        const bowlMat = new THREE.MeshLambertMaterial({ color: 0xe2e8f0 });
        const bowl = new THREE.Mesh(new THREE.CylinderGeometry(11, 10, 3.5, 28), bowlMat);
        bowl.scale.set(1.4, 1.0, 1.0);
        bowl.position.y = 1.75;
        bowl.castShadow = true;
        stadGroup.add(bowl);

        // Red Running Track
        const trackMat = new THREE.MeshLambertMaterial({ color: 0xe11d48 });
        const track = new THREE.Mesh(new THREE.CylinderGeometry(9.5, 9.5, 0.3, 24), trackMat);
        track.scale.set(1.35, 1.0, 0.95);
        track.position.y = 3.4;
        stadGroup.add(track);

        // Green Pitch
        const pitchMat = new THREE.MeshLambertMaterial({ color: 0x16a34a });
        const pitch = new THREE.Mesh(new THREE.BoxGeometry(12, 0.35, 7), pitchMat);
        pitch.position.y = 3.5;
        stadGroup.add(pitch);

        // Pitch Center Line & Goal Area
        const lineMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
        const cLine = new THREE.Mesh(new THREE.BoxGeometry(0.2, 0.36, 6.8), lineMat);
        cLine.position.y = 3.52;
        stadGroup.add(cLine);

        parent.add(stadGroup);
    },

    // ── JABI LAKE & RESORT ──────────────────────────────────────────────────
    buildJabiLake(parent) {
        // Shimmering Blue Lake
        const lakeMat = new THREE.MeshLambertMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.88 });
        const lake = new THREE.Mesh(new THREE.CylinderGeometry(14, 14, 0.4, 24), lakeMat);
        lake.scale.set(1.6, 1.0, 1.1);
        lake.position.set(-62, 0.15, -8);
        lake.receiveShadow = true;
        parent.add(lake);

        // Jabi Lake Mall / Waterfront Resort
        const resortMat = new THREE.MeshLambertMaterial({ color: 0xffffff });
        const resort = new THREE.Mesh(new THREE.BoxGeometry(10, 5, 8), resortMat);
        resort.position.set(-44, 2.5, -8);
        resort.castShadow = true;
        parent.add(resort);

        // Pier / Jetty
        const pierMat = new THREE.MeshLambertMaterial({ color: 0xa16207 });
        const pier = new THREE.Mesh(new THREE.BoxGeometry(8, 0.3, 2.5), pierMat);
        pier.position.set(-51, 0.3, -8);
        parent.add(pier);
    },

    // ── NATIONAL MOSQUE ─────────────────────────────────────────────────────
    buildNationalMosque(parent) {
        const msqGroup = new THREE.Group();
        msqGroup.position.set(18, 0, -22);

        // Main Plaza Base
        const base = new THREE.Mesh(new THREE.BoxGeometry(18, 1.5, 18), new THREE.MeshLambertMaterial({ color: 0xf1f5f9 }));
        base.position.y = 0.75;
        base.castShadow = true;
        msqGroup.add(base);

        // Main Hall Building
        const hall = new THREE.Mesh(new THREE.BoxGeometry(12, 5, 12), new THREE.MeshLambertMaterial({ color: 0xf8fafc }));
        hall.position.y = 3.5;
        hall.castShadow = true;
        msqGroup.add(hall);

        // Golden Dome
        const domeMat = new THREE.MeshStandardMaterial({ color: 0xf59e0b, roughness: 0.25, metalness: 0.6 });
        const dome = new THREE.Mesh(new THREE.SphereGeometry(4.5, 20, 20, 0, Math.PI * 2, 0, Math.PI / 2), domeMat);
        dome.position.y = 6.0;
        dome.castShadow = true;
        msqGroup.add(dome);

        // 4 Minarets
        const minMat = new THREE.MeshLambertMaterial({ color: 0xffffff });
        const minPositions = [[-7, -7], [7, -7], [-7, 7], [7, 7]];
        minPositions.forEach(([mx, mz]) => {
            const m = new THREE.Mesh(new THREE.CylinderGeometry(0.45, 0.65, 15, 12), minMat);
            m.position.set(mx, 7.5, mz);
            m.castShadow = true;
            msqGroup.add(m);

            const mDome = new THREE.Mesh(new THREE.ConeGeometry(0.65, 1.8, 12), domeMat);
            mDome.position.set(mx, 15.5, mz);
            msqGroup.add(mDome);
        });

        parent.add(msqGroup);
    },

    // ── NATIONAL CHRISTIAN CENTRE ───────────────────────────────────────────
    buildNationalChurch(parent) {
        const chGroup = new THREE.Group();
        chGroup.position.set(18, 0, 8);

        const whiteMat = new THREE.MeshLambertMaterial({ color: 0xf1f5f9 });

        // Nave Body
        const nave = new THREE.Mesh(new THREE.BoxGeometry(10, 6, 14), whiteMat);
        nave.position.y = 3;
        nave.castShadow = true;
        chGroup.add(nave);

        // Steeple / Neo-Gothic Spire
        const spireMat = new THREE.MeshLambertMaterial({ color: 0x94a3b8 });
        const spire = new THREE.Mesh(new THREE.ConeGeometry(4, 18, 4), spireMat);
        spire.position.set(0, 15, -4);
        spire.rotation.y = Math.PI / 4;
        spire.castShadow = true;
        chGroup.add(spire);

        parent.add(chGroup);
    },

    // ── CBD TOWERS (Twin Glass Skyscrapers) ──────────────────────────────────
    buildCBDTowers(parent) {
        const cbdGroup = new THREE.Group();
        cbdGroup.position.set(40, 0, 8);

        const glassMatA = new THREE.MeshStandardMaterial({ color: 0x0284c7, roughness: 0.15, metalness: 0.8 });
        const glassMatB = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.2, metalness: 0.7 });

        // Tower 1
        const t1 = new THREE.Mesh(new THREE.BoxGeometry(8, 32, 8), glassMatA);
        t1.position.set(-5, 16, 0);
        t1.castShadow = true;
        cbdGroup.add(t1);

        // Tower 2 (Slightly taller)
        const t2 = new THREE.Mesh(new THREE.BoxGeometry(8, 38, 8), glassMatB);
        t2.position.set(5, 19, -2);
        t2.castShadow = true;
        cbdGroup.add(t2);

        // Rooftop Communication Spire
        const spire = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.3, 8), new THREE.MeshLambertMaterial({ color: 0xffffff }));
        spire.position.set(5, 42, -2);
        cbdGroup.add(spire);

        parent.add(cbdGroup);
    },

    // ── THREE ARMS ZONE / FEDERAL SECRETARIAT ───────────────────────────────
    buildThreeArmsZone(parent) {
        const secGroup = new THREE.Group();
        secGroup.position.set(65, 0, -20);

        const whiteMat = new THREE.MeshLambertMaterial({ color: 0xf8fafc });
        const greenMat = new THREE.MeshLambertMaterial({ color: 0x15803d });

        // Main Federal Complex Wings
        const wingA = new THREE.Mesh(new THREE.BoxGeometry(22, 6, 8), whiteMat);
        wingA.position.y = 3;
        wingA.castShadow = true;
        secGroup.add(wingA);

        // Central Parliament Dome
        const pDome = new THREE.Mesh(new THREE.SphereGeometry(3.5, 16, 16, 0, Math.PI * 2, 0, Math.PI / 2), greenMat);
        pDome.position.set(0, 6, 0);
        pDome.castShadow = true;
        secGroup.add(pDome);

        // Flagpole
        const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.1, 0.1, 8), new THREE.MeshLambertMaterial({ color: 0x94a3b8 }));
        pole.position.set(0, 10, 6);
        secGroup.add(pole);

        parent.add(secGroup);
    },

    // ── HOTELS & MALLS (Transcorp, Fraser, Novare) ───────────────────────────
    buildHotelsAndMalls(parent) {
        // Transcorp Hilton (Center-right: x: 18, z: -8)
        const transMat = new THREE.MeshLambertMaterial({ color: 0xdbeafe });
        const transcorp = new THREE.Mesh(new THREE.BoxGeometry(16, 12, 10), transMat);
        transcorp.position.set(18, 6, -8);
        transcorp.castShadow = true;
        parent.add(transcorp);

        // Fraser Suites (Executive hotel: x: 8, z: 8)
        const fraserMat = new THREE.MeshLambertMaterial({ color: 0x334155 });
        const fraser = new THREE.Mesh(new THREE.BoxGeometry(10, 16, 8), fraserMat);
        fraser.position.set(8, 8, 8);
        fraser.castShadow = true;
        parent.add(fraser);

        // Novare Gateway Mall (x: -18, z: -24)
        const mallMat = new THREE.MeshLambertMaterial({ color: 0xec4899 });
        const mall = new THREE.Mesh(new THREE.BoxGeometry(14, 6, 10), mallMat);
        mall.position.set(-18, 3, -24);
        mall.castShadow = true;
        parent.add(mall);
    },

    // ── NATIONAL HOSPITAL ───────────────────────────────────────────────────
    buildHospital(parent) {
        const hospGroup = new THREE.Group();
        hospGroup.position.set(-6, 0, -8);

        const hMat = new THREE.MeshLambertMaterial({ color: 0xf8fafc });
        const body = new THREE.Mesh(new THREE.BoxGeometry(12, 7, 8), hMat);
        body.position.y = 3.5;
        body.castShadow = true;
        hospGroup.add(body);

        // Red Cross
        const redMat = new THREE.MeshBasicMaterial({ color: 0xef4444 });
        const r1 = new THREE.Mesh(new THREE.BoxGeometry(2.4, 0.6, 0.2), redMat);
        r1.position.set(0, 5, 4.1);
        hospGroup.add(r1);

        const r2 = new THREE.Mesh(new THREE.BoxGeometry(0.6, 2.4, 0.2), redMat);
        r2.position.set(0, 5, 4.1);
        hospGroup.add(r2);

        parent.add(hospGroup);
    },

    // ── NEIGHBOURHOODS (Maitama villas, Gwarimpa, Garki) ─────────────────────
    buildNeighbourhoods(parent) {
        // Maitama Villas with Swimming Pools & Tennis Courts (x: 40 to 65, z: -35 to -50)
        const villaMat = new THREE.MeshLambertMaterial({ color: 0xffffff });
        const roofMat = new THREE.MeshLambertMaterial({ color: 0x7c2d12 });
        const poolMat = new THREE.MeshLambertMaterial({ color: 0x38bdf8 });
        const tennisMat = new THREE.MeshLambertMaterial({ color: 0x15803d });

        for (let vx = 38; vx <= 62; vx += 12) {
            for (let vz = -48; vz <= -36; vz += 12) {
                const villa = new THREE.Mesh(new THREE.BoxGeometry(6, 4, 6), villaMat);
                villa.position.set(vx, 2, vz);
                villa.castShadow = true;
                parent.add(villa);

                const vRoof = new THREE.Mesh(new THREE.ConeGeometry(5, 2, 4), roofMat);
                vRoof.position.set(vx, 5, vz);
                vRoof.rotation.y = Math.PI / 4;
                parent.add(vRoof);

                // Private Swimming Pool
                const pool = new THREE.Mesh(new THREE.PlaneGeometry(3.5, 2.5), poolMat);
                pool.rotation.x = -Math.PI / 2;
                pool.position.set(vx + 4.5, 0.1, vz);
                parent.add(pool);
            }
        }

        // Tennis Court in Maitama
        const tennis = new THREE.Mesh(new THREE.PlaneGeometry(8, 14), tennisMat);
        tennis.rotation.x = -Math.PI / 2;
        tennis.position.set(48, 0.1, -26);
        parent.add(tennis);

        // Gwarimpa & Garki Dense Housing (x: -50 to -20, z: -40 to -60)
        const houseColors = [0xef4444, 0x3b82f6, 0xf59e0b, 0x10b981, 0x8b5cf6, 0xd97706];
        for (let gx = -55; gx <= -22; gx += 7) {
            for (let gz = -56; gz <= -38; gz += 7) {
                const hCol = houseColors[Math.abs(gx * gz) % houseColors.length];
                const house = new THREE.Mesh(new THREE.BoxGeometry(3.8, 2.5, 3.8), new THREE.MeshLambertMaterial({ color: 0xf1f5f9 }));
                house.position.set(gx, 1.25, gz);
                house.castShadow = true;
                parent.add(house);

                const hRoof = new THREE.Mesh(new THREE.ConeGeometry(3.2, 1.6, 4), new THREE.MeshLambertMaterial({ color: hCol }));
                hRoof.position.set(gx, 3.2, gz);
                hRoof.rotation.y = Math.PI / 4;
                parent.add(hRoof);
            }
        }
    },

    // ── BILLBOARDS ──────────────────────────────────────────────────────────
    buildBillboards(parent) {
        const bbCoords = [
            [-42, 15, 0.8],
            [12, -26, 0],
            [35, 18, -0.4],
            [-22, 38, 0.6]
        ];

        bbCoords.forEach(([bx, bz, rot]) => {
            const bb = new THREE.Group();
            bb.position.set(bx, 0, bz);
            bb.rotation.y = rot;

            // Two Poles
            const poleMat = new THREE.MeshLambertMaterial({ color: 0x64748b });
            const p1 = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.15, 6), poleMat);
            p1.position.set(-2, 3, 0);
            bb.add(p1);

            const p2 = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.15, 6), poleMat);
            p2.position.set(2, 3, 0);
            bb.add(p2);

            // Ad Board
            const board = new THREE.Mesh(new THREE.BoxGeometry(6, 2.5, 0.3), new THREE.MeshLambertMaterial({ color: 0x0284c7 }));
            board.position.set(0, 6, 0);
            board.castShadow = true;
            bb.add(board);

            parent.add(bb);
        });
    },

    // ── TREE HELPER ─────────────────────────────────────────────────────────
    buildTree(parent, x, z, scale = 1) {
        const treeGroup = new THREE.Group();
        treeGroup.position.set(x, 0, z);

        const trunk = new THREE.Mesh(new THREE.CylinderGeometry(0.25, 0.35, 2.5 * scale), new THREE.MeshLambertMaterial({ color: 0x78350f }));
        trunk.position.y = (1.25 * scale);
        trunk.castShadow = true;
        treeGroup.add(trunk);

        const canopy = new THREE.Mesh(new THREE.DodecahedronGeometry(1.6 * scale, 1), new THREE.MeshLambertMaterial({ color: 0x15803d }));
        canopy.position.y = (3.2 * scale);
        canopy.castShadow = true;
        treeGroup.add(canopy);

        parent.add(treeGroup);
    },

    // ─────────────────────────────────────────────────────────────────────────
    // INTERACTIVE 3D LOCATION PINS (Matches user reference image)
    // ─────────────────────────────────────────────────────────────────────────
    buildLocationPins(parent) {
        const pins = [
            { id: 'airport',     name: 'Capital Airport',  icon: '✈️', color: '#8b5cf6', x: -58, z: 52 },
            { id: 'stadium',     name: 'National Stadium', icon: '⚽', color: '#10b981', x: -20, z: 52 },
            { id: 'jabi_lake',   name: 'Jabi Lake',        icon: '⛵', color: '#0ea5e9', x: -62, z: -8 },
            { id: 'novare',      name: 'Novare Mall',      icon: '🛍️', color: '#ec4899', x: -18, z: -24 },
            { id: 'hospital',    name: 'National Hospital',icon: '🏥', color: '#ef4444', x: -6,  z: -8 },
            { id: 'mosque',      name: 'National Mosque',  icon: '🕌', color: '#f59e0b', x: 18,  z: -22 },
            { id: 'transcorp',   name: 'Transcorp Hilton', icon: '🏨', color: '#3b82f6', x: 18,  z: -8 },
            { id: 'church',      name: 'National Church',  icon: '⛪', color: '#6366f1', x: 18,  z: 8 },
            { id: 'fraser',      name: 'Fraser Suites',    icon: '🏨', color: '#f43f5e', x: 8,   z: 8 },
            { id: 'cbd',         name: 'CBD Twin Towers',  icon: '🏦', color: '#0284c7', x: 40,  z: 8 },
            { id: 'secretariat', name: 'Three Arms Zone',  icon: '🏛️', color: '#64748b', x: 65,  z: -20 },
            { id: 'gym',         name: 'Maitama Gym',      icon: '🏋️', color: '#10b981', x: 48,  z: -42 },
            { id: 'wuse',        name: 'Wuse Market',      icon: '🛍️', color: '#f97316', x: -2,  z: -28 }
        ];

        pins.forEach(p => {
            const pinMesh = this.createCircularBadgeSprite(p.name, p.icon, p.color);
            pinMesh.position.set(p.x, 8.5, p.z);
            pinMesh.userData = { id: p.id, name: p.name, type: 'landmark', baseY: 8.5 };
            parent.add(pinMesh);

            // Invisible Hitbox for instant clicking
            const hitGeo = new THREE.SphereGeometry(3.5, 8, 8);
            const hitMat = new THREE.MeshBasicMaterial({ visible: false });
            const hitBox = new THREE.Mesh(hitGeo, hitMat);
            hitBox.position.copy(pinMesh.position);
            hitBox.userData = pinMesh.userData;
            parent.add(hitBox);

            this.interactables.push(hitBox);
            this.pinSprites.push(pinMesh);
        });
    },

    createCircularBadgeSprite(title, icon, ringColor) {
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 128;
        const ctx = canvas.getContext('2d');

        // Drop shadow
        ctx.shadowColor = 'rgba(0,0,0,0.25)';
        ctx.shadowBlur = 12;
        ctx.shadowOffsetY = 4;

        // White circular pill
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.arc(128, 48, 38, 0, Math.PI * 2);
        ctx.fill();

        // Ring border
        ctx.lineWidth = 5;
        ctx.strokeStyle = ringColor;
        ctx.stroke();

        ctx.shadowColor = 'transparent';

        // Icon inside circle
        ctx.font = '36px serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(icon, 128, 48);

        // Subtitle badge pill
        ctx.fillStyle = '#0f172a';
        ctx.beginPath();
        const textWidth = ctx.measureText(title).width + 24;
        ctx.roundRect ? ctx.roundRect(128 - textWidth / 2, 92, textWidth, 26, 13) : ctx.rect(128 - textWidth / 2, 92, textWidth, 26);
        ctx.fill();

        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 15px "Plus Jakarta Sans", sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(title, 128, 105);

        const tex = new THREE.CanvasTexture(canvas);
        const mat = new THREE.SpriteMaterial({ map: tex, sizeAttenuation: true, depthTest: false });
        const sprite = new THREE.Sprite(mat);
        sprite.scale.set(10, 5, 1);
        return sprite;
    },

    // ── ROAMING CITIZENS WITH USERNAME BADGES ───────────────────────────────
    async loadRoamingCitizens(parent) {
        try {
            const res = await fetch('api/citizens.php?action=search');
            const data = await res.json();
            const citizenList = (data.success && data.citizens && data.citizens.length > 0)
                ? data.citizens
                : [
                    { username: 'aminu_fct', full_name: 'Aminu Bello', district: 'Maitama', job_title: 'Special Assistant' },
                    { username: 'dapo_tech', full_name: 'Dapo Kunle', district: 'Wuse 2', job_title: 'Senior Dev' },
                    { username: 'zainab_fintech', full_name: 'Zainab Ahmed', district: 'CBD', job_title: 'Banker' },
                    { username: 'bossman', full_name: 'Chairman Boss', district: 'Asokoro', job_title: 'Tech Lead' }
                ];

            const spawnPoints = [
                [-12, -18], [24, -14], [34, 18], [-36, 12], [8, -32], [44, -36]
            ];

            citizenList.slice(0, 6).forEach((c, idx) => {
                const pt = spawnPoints[idx % spawnPoints.length];
                const citizenGroup = this.createCitizenAvatarMesh(c);
                citizenGroup.position.set(pt[0], 0, pt[1]);
                parent.add(citizenGroup);
            });
        } catch (e) {
            console.error('Citizens 3D map load error', e);
        }
    },

    createCitizenAvatarMesh(citizen) {
        const group = new THREE.Group();
        group.userData = {
            type: 'citizen',
            username: citizen.username.replace(/^@/, ''),
            name: citizen.full_name || citizen.username,
            job: citizen.job_title || 'Abuja Resident',
            district: citizen.district || 'Abuja FCT',
            cred: citizen.street_cred || 50
        };

        // 3D Stylized Pedestrian Figure
        const bodyMat = new THREE.MeshLambertMaterial({ color: 0x10b981 });
        const body = new THREE.Mesh(new THREE.CylinderGeometry(0.4, 0.4, 1.8), bodyMat);
        body.position.y = 0.9;
        body.castShadow = true;
        group.add(body);

        const headMat = new THREE.MeshLambertMaterial({ color: 0x7c4f34 });
        const head = new THREE.Mesh(new THREE.SphereGeometry(0.5, 12, 12), headMat);
        head.position.y = 2.1;
        head.castShadow = true;
        group.add(head);

        // Floating @username tag sprite
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 64;
        const ctx = canvas.getContext('2d');

        ctx.fillStyle = '#0f172a';
        ctx.beginPath();
        ctx.roundRect ? ctx.roundRect(16, 8, 224, 48, 24) : ctx.rect(16, 8, 224, 48);
        ctx.fill();

        ctx.strokeStyle = '#10b981';
        ctx.lineWidth = 4;
        ctx.stroke();

        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 22px monospace';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('@' + group.userData.username, 128, 32);

        const tex = new THREE.CanvasTexture(canvas);
        const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, depthTest: false }));
        sprite.scale.set(6, 1.5, 1);
        sprite.position.set(0, 3.8, 0);
        group.add(sprite);

        // Hitbox
        const hit = new THREE.Mesh(new THREE.SphereGeometry(2.5), new THREE.MeshBasicMaterial({ visible: false }));
        hit.position.y = 2;
        hit.userData = group.userData;
        group.add(hit);
        this.interactables.push(hit);

        return group;
    },

    // ─────────────────────────────────────────────────────────────────────────
    // INTERACTION HANDLING (RAYCASTING)
    // ─────────────────────────────────────────────────────────────────────────
    getIntersects(e) {
        const rect = this.renderer.domElement.getBoundingClientRect();
        const clientX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
        const clientY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;

        this.mouse.x = ((clientX - rect.left) / rect.width) * 2 - 1;
        this.mouse.y = -((clientY - rect.top) / rect.height) * 2 + 1;

        this.raycaster.setFromCamera(this.mouse, this.camera);
        return this.raycaster.intersectObjects(this.interactables);
    },

    onClick(e) {
        if (!this.isInitialized) return;
        const intersects = this.getIntersects(e);

        if (intersects.length > 0) {
            const data = intersects[0].object.userData;

            if (data.type === 'landmark') {
                if (typeof this.onPinClick === 'function') {
                    this.onPinClick(data.id, data.name);
                } else if (window.GameApp && typeof GameApp.openTravelModal === 'function') {
                    GameApp.openTravelModal(data.id);
                } else if (typeof previewLocation === 'function') {
                    previewLocation(data.id);
                }
            } else if (data.type === 'citizen') {
                if (typeof this.onCitizenClick === 'function') {
                    this.onCitizenClick(data);
                } else if (window.GameApp && typeof GameApp.inspectCitizenFromMap === 'function') {
                    GameApp.inspectCitizenFromMap(data.name, data.username, data.job, data.district, data.cred);
                } else if (typeof previewCitizen === 'function') {
                    previewCitizen('@' + data.username, data.job, data.district, '₦5.2M');
                }
            }
        }
    },

    onMouseMove(e) {
        if (!this.isInitialized) return;
        const intersects = this.getIntersects(e);
        this.container.style.cursor = intersects.length > 0 ? 'pointer' : 'default';
    },

    onResize() {
        if (!this.container || !this.renderer || !this.camera) return;
        const w = this.container.clientWidth;
        const h = this.container.clientHeight;
        this.camera.aspect = w / h;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(w, h);
    },

    zoom(factor) {
        if (!this.controls) return;
        this.camera.position.multiplyScalar(1 / factor);
    },

    resetView() {
        if (!this.camera || !this.controls) return;
        this.camera.position.set(0, 110, 140);
        this.controls.target.set(0, 0, 5);
        this.controls.update();
    },

    // ─────────────────────────────────────────────────────────────────────────
    // ANIMATION LOOP (60FPS WebGL)
    // ─────────────────────────────────────────────────────────────────────────
    animate() {
        requestAnimationFrame(() => this.animate());

        if (this.controls) {
            this.controls.update();
        }

        const time = Date.now() * 0.003;

        // Bouncing Pin Animation
        this.pinSprites.forEach((sprite, idx) => {
            sprite.position.y = (sprite.userData.baseY || 8.5) + Math.sin(time + idx * 0.7) * 0.55;
        });

        // Traffic Movement Animation
        this.animatedCars.forEach(car => {
            const u = car.userData;
            if (u.type === 'H') {
                car.position.x += u.speed;
                if (car.position.x > u.max) car.position.x = u.min;
            } else {
                car.position.z += u.speed;
                if (car.position.z > u.max) car.position.z = u.min;
            }
        });

        if (this.renderer && this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }
};

window.Map3D = Map3D;
