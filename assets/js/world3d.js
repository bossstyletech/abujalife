/**
 * Abuja Life 3D Interactive World Engine (Three.js)
 * High-fidelity architectural diorama of Abuja Workplace & Residence
 * with Nigerian Royal Palms, luxury vehicles, glass facades, and prominent character persona.
 */

const World3D = {
    container: null,
    scene: null,
    camera: null,
    renderer: null,
    controls: null,
    currentBuildingGroup: null,
    characterMesh: null,
    viewMode: 'workplace', // 'workplace' or 'home'
    timeOfDay: 'day',      // 'day', 'sunset', 'night'
    autoRotate: true,
    isInitialized: false,

    init(containerId = 'world3d-container') {
        this.container = document.getElementById(containerId);
        if (!this.container || typeof THREE === 'undefined') return;

        const width = this.container.clientWidth || 600;
        const height = this.container.clientHeight || 450;

        // Scene Setup with soft atmospheric background
        this.scene = new THREE.Scene();
        this.updateAtmosphere('day');

        // Camera Setup
        this.camera = new THREE.PerspectiveCamera(38, width / height, 0.1, 1000);
        this.camera.position.set(11, 7.5, 14);

        // WebGL Renderer
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        this.container.innerHTML = '';
        this.container.appendChild(this.renderer.domElement);

        // Orbit Controls
        if (typeof THREE.OrbitControls !== 'undefined') {
            this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.05;
            this.controls.target.set(0, 2.0, 0);
            this.controls.maxPolarAngle = Math.PI / 2.05;
            this.controls.minDistance = 5;
            this.controls.maxDistance = 32;
            this.controls.autoRotate = this.autoRotate;
            this.controls.autoRotateSpeed = 0.9;
        }

        // Lighting Architecture
        this.setupLighting();

        // Architectural Diorama Ground Podium
        this.createGround();

        // Build Initial Scene
        this.updateScene();

        // Responsive Resize
        window.addEventListener('resize', () => this.onResize());

        this.isInitialized = true;
        this.animate();
    },

    updateAtmosphere(mode = 'day') {
        this.timeOfDay = mode;
        if (mode === 'night') {
            this.scene.background = new THREE.Color(0x090d16);
            this.scene.fog = new THREE.FogExp2(0x090d16, 0.025);
        } else if (mode === 'sunset') {
            this.scene.background = new THREE.Color(0xffedd5); // Warm Nigerian twilight
            this.scene.fog = new THREE.FogExp2(0xffedd5, 0.02);
        } else {
            this.scene.background = new THREE.Color(0xf1f5f9); // Crisp slate/sky daylight
            this.scene.fog = new THREE.FogExp2(0xf1f5f9, 0.015);
        }
    },

    setupLighting() {
        // Main Sun Key Light
        this.sunLight = new THREE.DirectionalLight(0xfffaed, 0.95);
        this.sunLight.position.set(16, 24, 14);
        this.sunLight.castShadow = true;
        this.sunLight.shadow.mapSize.width = 2048;
        this.sunLight.shadow.mapSize.height = 2048;
        this.sunLight.shadow.bias = -0.0004;
        this.scene.add(this.sunLight);

        // Sky Fill Light (Cool sky tones to contrast warm sun)
        this.fillLight = new THREE.DirectionalLight(0x38bdf8, 0.45);
        this.fillLight.position.set(-14, 12, -10);
        this.scene.add(this.fillLight);

        // Ambient Fill
        this.ambientLight = new THREE.AmbientLight(0x64748b, 0.55);
        this.scene.add(this.ambientLight);

        // Hemisphere Sky/Ground
        this.hemiLight = new THREE.HemisphereLight(0xbae6fd, 0x1e293b, 0.45);
        this.scene.add(this.hemiLight);

        // Entrance Warm Spotlight (Welcoming glow on lobby and character)
        this.entryLight = new THREE.PointLight(0xfef08a, 1.2, 14);
        this.entryLight.position.set(0, 3.2, 3.8);
        this.scene.add(this.entryLight);
    },

    createGround() {
        const group = new THREE.Group();

        // 1. Foundation Podium Slab (Dark Obsidian / Metallic Rim)
        const baseGeo = new THREE.CylinderGeometry(8.8, 9.1, 0.5, 48);
        const baseMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.7, metalness: 0.3 });
        const baseMesh = new THREE.Mesh(baseGeo, baseMat);
        baseMesh.position.y = -0.25;
        baseMesh.receiveShadow = true;
        group.add(baseMesh);

        // 2. Beveled Metallic Trim Accent Ring
        const ringGeo = new THREE.TorusGeometry(8.9, 0.08, 16, 48);
        const ringMat = new THREE.MeshStandardMaterial({ color: 0x10b981, metalness: 0.8, roughness: 0.2 });
        const ringMesh = new THREE.Mesh(ringGeo, ringMat);
        ringMesh.position.y = 0.01;
        ringMesh.rotation.x = Math.PI / 2;
        group.add(ringMesh);

        // 3. Manicured Emerald Grass Lawn
        const lawnGeo = new THREE.CylinderGeometry(8.2, 8.2, 0.06, 48);
        const lawnMat = new THREE.MeshStandardMaterial({ color: 0x15803d, roughness: 0.85 });
        const lawn = new THREE.Mesh(lawnGeo, lawnMat);
        lawn.position.y = 0.03;
        lawn.receiveShadow = true;
        group.add(lawn);

        // 4. Driveway / Stone Paver Plaza
        const driveGeo = new THREE.BoxGeometry(4.2, 0.08, 7.8);
        const driveMat = new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.6 });
        const driveway = new THREE.Mesh(driveGeo, driveMat);
        driveway.position.set(0, 0.04, 3.2);
        driveway.receiveShadow = true;
        group.add(driveway);

        // Curb Paver Strips (Granite Curbs)
        const curbMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, roughness: 0.4 });
        const curbLeft = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.12, 7.8), curbMat);
        curbLeft.position.set(-2.15, 0.07, 3.2);
        group.add(curbLeft);

        const curbRight = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.12, 7.8), curbMat);
        curbRight.position.set(2.15, 0.07, 3.2);
        group.add(curbRight);

        // 5. Landscaping: Nigerian Royal Palms
        this.addRoyalPalm(group, 5.8, -3.2, 1.1);
        this.addRoyalPalm(group, -5.8, -3.4, 0.95);
        this.addRoyalPalm(group, 5.6, 2.8, 1.0);
        this.addRoyalPalm(group, -5.4, 2.6, 0.85);

        // 6. Modern Bollard Pathway Lights
        this.addBollardLight(group, -2.4, 2.5);
        this.addBollardLight(group, -2.4, 5.2);
        this.addBollardLight(group, 2.4, 5.2);

        // 7. Parked Luxury SUV (Lexus RX / G-Wagon in Driveway)
        this.addLuxurySUV(group, -2.8, 3.6);

        this.scene.add(group);
    },

    addRoyalPalm(parentGroup, x, z, scale = 1.0) {
        const palm = new THREE.Group();
        palm.position.set(x, 0, z);
        palm.scale.set(scale, scale, scale);

        // Ribbed textured trunk
        const trunkMat = new THREE.MeshStandardMaterial({ color: 0x78350f, roughness: 0.9 });
        for (let i = 0; i < 7; i++) {
            const segGeo = new THREE.CylinderGeometry(0.14 - i * 0.01, 0.17 - i * 0.01, 0.55, 8);
            const seg = new THREE.Mesh(segGeo, trunkMat);
            seg.position.y = 0.28 + i * 0.5;
            seg.rotation.z = (Math.sin(i * 0.8) * 0.04);
            seg.castShadow = true;
            palm.add(seg);
        }

        // Crownshaft Green Collar
        const collarGeo = new THREE.CylinderGeometry(0.14, 0.12, 0.6, 8);
        const collarMat = new THREE.MeshStandardMaterial({ color: 0x22c55e, roughness: 0.5 });
        const collar = new THREE.Mesh(collarGeo, collarMat);
        collar.position.y = 4.0;
        palm.add(collar);

        // Palm Fronds Canopy (Realistic 8-way fan)
        const frondMat = new THREE.MeshStandardMaterial({ color: 0x166534, roughness: 0.6, flatShading: true });
        for (let i = 0; i < 8; i++) {
            const angle = (i * Math.PI * 2) / 8;
            const frondGeo = new THREE.ConeGeometry(0.7, 2.2, 4);
            const frond = new THREE.Mesh(frondGeo, frondMat);
            frond.position.set(Math.cos(angle) * 0.6, 4.3, Math.sin(angle) * 0.6);
            frond.rotation.y = angle;
            frond.rotation.x = Math.PI / 3.2;
            frond.rotation.z = Math.PI / 10;
            frond.castShadow = true;
            palm.add(frond);
        }

        parentGroup.add(palm);
    },

    addBollardLight(parentGroup, x, z) {
        const bollard = new THREE.Group();
        bollard.position.set(x, 0, z);

        const postGeo = new THREE.CylinderGeometry(0.06, 0.06, 0.9, 12);
        const postMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, metalness: 0.8 });
        const post = new THREE.Mesh(postGeo, postMat);
        post.position.y = 0.45;
        bollard.add(post);

        // Glowing Lantern
        const glowGeo = new THREE.CylinderGeometry(0.065, 0.065, 0.16, 12);
        const glowMat = new THREE.MeshStandardMaterial({ color: 0xfef08a, emissive: 0xfde047, emissiveIntensity: 0.8 });
        const glow = new THREE.Mesh(glowGeo, glowMat);
        glow.position.y = 0.82;
        bollard.add(glow);

        parentGroup.add(bollard);
    },

    addLuxurySUV(parentGroup, x, z) {
        const car = new THREE.Group();
        car.position.set(x, 0.12, z);
        car.rotation.y = Math.PI / 12;

        // SUV Body (Deep Metallic Obsidian Black)
        const bodyGeo = new THREE.BoxGeometry(1.6, 0.85, 3.2);
        const bodyMat = new THREE.MeshStandardMaterial({ color: 0x09090b, roughness: 0.2, metalness: 0.85 });
        const body = new THREE.Mesh(bodyGeo, bodyMat);
        body.position.y = 0.55;
        body.castShadow = true;
        car.add(body);

        // Cabin Roof & Dark Tinted Windows
        const cabinGeo = new THREE.BoxGeometry(1.4, 0.65, 1.8);
        const cabinMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.1, metalness: 0.9 });
        const cabin = new THREE.Mesh(cabinGeo, cabinMat);
        cabin.position.set(0, 1.2, -0.15);
        cabin.castShadow = true;
        car.add(cabin);

        // Headlights LED Strip
        const headLightMat = new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0xe0f2fe, emissiveIntensity: 1.0 });
        const headLeft = new THREE.Mesh(new THREE.BoxGeometry(0.25, 0.1, 0.05), headLightMat);
        headLeft.position.set(-0.55, 0.6, 1.62);
        car.add(headLeft);

        const headRight = new THREE.Mesh(new THREE.BoxGeometry(0.25, 0.1, 0.05), headLightMat);
        headRight.position.set(0.55, 0.6, 1.62);
        car.add(headRight);

        // Wheels
        const wheelGeo = new THREE.CylinderGeometry(0.26, 0.26, 0.2, 16);
        const wheelMat = new THREE.MeshStandardMaterial({ color: 0x18181b, roughness: 0.8 });
        const wheelPositions = [
            [-0.85, 0.26, 0.95],
            [0.85, 0.26, 0.95],
            [-0.85, 0.26, -0.95],
            [0.85, 0.26, -0.95]
        ];
        wheelPositions.forEach(pos => {
            const w = new THREE.Mesh(wheelGeo, wheelMat);
            w.rotation.z = Math.PI / 2;
            w.position.set(...pos);
            w.castShadow = true;
            car.add(w);
        });

        parentGroup.add(car);
    },

    updateScene() {
        if (this.currentBuildingGroup) {
            this.scene.remove(this.currentBuildingGroup);
            this.currentBuildingGroup = null;
        }

        this.currentBuildingGroup = new THREE.Group();

        if (this.viewMode === 'workplace') {
            this.buildWorkplaceModel();
        } else {
            this.buildHomeModel();
        }

        this.buildPersonaModel();

        this.scene.add(this.currentBuildingGroup);
    },

    buildWorkplaceModel() {
        const b = new THREE.Group();

        // 1. Main Corporate Tower Body (3-Story Dark Titanium Facade)
        const mainGeo = new THREE.BoxGeometry(5.6, 6.2, 4.6);
        const mainMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.35, metalness: 0.4 });
        const mainBody = new THREE.Mesh(mainGeo, mainMat);
        mainBody.position.y = 3.1;
        mainBody.castShadow = true;
        mainBody.receiveShadow = true;
        b.add(mainBody);

        // 2. Reflective Blue Glass Curtain Wall Facade
        const glassGeo = new THREE.PlaneGeometry(4.8, 5.2);
        const glassMat = new THREE.MeshStandardMaterial({
            color: 0x0284c7,
            roughness: 0.1,
            metalness: 0.85,
            transparent: true,
            opacity: 0.88
        });
        const glassFront = new THREE.Mesh(glassGeo, glassMat);
        glassFront.position.set(0, 3.1, 2.32);
        b.add(glassFront);

        // Glass Mullions / Floor Dividing Beams
        const beamMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, metalness: 0.8 });
        for (let y = 1.6; y <= 4.8; y += 1.6) {
            const beam = new THREE.Mesh(new THREE.BoxGeometry(4.9, 0.12, 0.1), beamMat);
            beam.position.set(0, y, 2.33);
            b.add(beam);
        }

        // Vertical Mullions
        for (let x = -1.6; x <= 1.6; x += 1.6) {
            const vBeam = new THREE.Mesh(new THREE.BoxGeometry(0.1, 5.2, 0.1), beamMat);
            vBeam.position.set(x, 3.1, 2.33);
            b.add(vBeam);
        }

        // 3. Grand Ground Floor Entrance Lobby & Canopy
        const canopyGeo = new THREE.BoxGeometry(3.6, 0.22, 1.8);
        const canopyMat = new THREE.MeshStandardMaterial({ color: 0x059669, metalness: 0.6, roughness: 0.3 });
        const canopy = new THREE.Mesh(canopyGeo, canopyMat);
        canopy.position.set(0, 2.3, 3.0);
        canopy.castShadow = true;
        b.add(canopy);

        // Glowing Warm Entrance Glass Revolving Doors
        const doorGeo = new THREE.PlaneGeometry(2.4, 1.9);
        const doorMat = new THREE.MeshStandardMaterial({
            color: 0xfef08a,
            emissive: 0xfef08a,
            emissiveIntensity: 0.35,
            roughness: 0.1
        });
        const door = new THREE.Mesh(doorGeo, doorMat);
        door.position.set(0, 0.95, 2.34);
        b.add(door);

        // Red Carpet Entrance Runner
        const carpetGeo = new THREE.BoxGeometry(1.6, 0.02, 2.4);
        const carpetMat = new THREE.MeshStandardMaterial({ color: 0x991b1b, roughness: 0.9 });
        const carpet = new THREE.Mesh(carpetGeo, carpetMat);
        carpet.position.set(0, 0.05, 3.5);
        b.add(carpet);

        // 4. 3D Corporate Illuminated Rooftop Billboard Sign
        const signBack = new THREE.Mesh(
            new THREE.BoxGeometry(4.2, 0.9, 0.15),
            new THREE.MeshStandardMaterial({ color: 0x0f172a, metalness: 0.8 })
        );
        signBack.position.set(0, 6.7, 1.8);
        b.add(signBack);

        // Golden Glowing Letter Block on Billboard
        const signGlow = new THREE.Mesh(
            new THREE.BoxGeometry(3.8, 0.5, 0.1),
            new THREE.MeshStandardMaterial({ color: 0xf59e0b, emissive: 0xd97706, emissiveIntensity: 0.7 })
        );
        signGlow.position.set(0, 6.7, 1.9);
        b.add(signGlow);

        // 5. Rooftop Infrastructure: Antenna Mast & AC Chillers
        const mastGeo = new THREE.CylinderGeometry(0.04, 0.06, 2.8, 8);
        const mastMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.9 });
        const mast = new THREE.Mesh(mastGeo, mastMat);
        mast.position.set(-1.8, 7.6, -1.2);
        b.add(mast);

        // Red Flashing Aircraft Warning Beacon
        const beacon = new THREE.Mesh(
            new THREE.SphereGeometry(0.1, 8, 8),
            new THREE.MeshStandardMaterial({ color: 0xef4444, emissive: 0xef4444, emissiveIntensity: 1.2 })
        );
        beacon.position.set(-1.8, 9.0, -1.2);
        b.add(beacon);

        // AC Chillers
        const chMat = new THREE.MeshStandardMaterial({ color: 0x475569 });
        const ch1 = new THREE.Mesh(new THREE.BoxGeometry(1.4, 0.8, 1.2), chMat);
        ch1.position.set(1.4, 6.6, -0.8);
        b.add(ch1);

        this.currentBuildingGroup.add(b);
    },

    buildHomeModel() {
        const h = new THREE.Group();

        // 1. Luxury Contemporary Maitama Villa (White Stucco + Teak Wood Accents)
        const villaMain = new THREE.Mesh(
            new THREE.BoxGeometry(5.8, 4.4, 4.2),
            new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.25 })
        );
        villaMain.position.y = 2.2;
        villaMain.castShadow = true;
        villaMain.receiveShadow = true;
        h.add(villaMain);

        // Teak Wood Cladding Accent Wing
        const woodGeo = new THREE.BoxGeometry(2.4, 4.42, 4.22);
        const woodMat = new THREE.MeshStandardMaterial({ color: 0x78350f, roughness: 0.7 });
        const woodWing = new THREE.Mesh(woodGeo, woodMat);
        woodWing.position.set(2.0, 2.2, 0);
        woodWing.castShadow = true;
        h.add(woodWing);

        // 2. Second Floor Cantilever Master Balcony
        const balcGeo = new THREE.BoxGeometry(3.6, 0.2, 1.6);
        const balcMat = new THREE.MeshStandardMaterial({ color: 0x0f172a });
        const balc = new THREE.Mesh(balcGeo, balcMat);
        balc.position.set(-0.8, 2.8, 2.7);
        balc.castShadow = true;
        h.add(balc);

        // Balcony Glass Railing
        const railGeo = new THREE.PlaneGeometry(3.5, 0.8);
        const railMat = new THREE.MeshStandardMaterial({
            color: 0x38bdf8,
            transparent: true,
            opacity: 0.6,
            roughness: 0.1
        });
        const railing = new THREE.Mesh(railGeo, railMat);
        railing.position.set(-0.8, 3.3, 3.48);
        h.add(railing);

        // 3. Sparkling Turquoise Infinity Swimming Pool
        const poolBorder = new THREE.Mesh(
            new THREE.BoxGeometry(3.2, 0.14, 2.0),
            new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 0.4 })
        );
        poolBorder.position.set(-2.0, 0.08, 3.3);
        poolBorder.receiveShadow = true;
        h.add(poolBorder);

        const poolWater = new THREE.Mesh(
            new THREE.PlaneGeometry(2.9, 1.7),
            new THREE.MeshStandardMaterial({
                color: 0x06b6d4,
                emissive: 0x0891b2,
                emissiveIntensity: 0.4,
                roughness: 0.05,
                metalness: 0.5
            })
        );
        poolWater.rotation.x = -Math.PI / 2;
        poolWater.position.set(-2.0, 0.15, 3.3);
        h.add(poolWater);

        // 4. Overhead Black GP Water Tank (Nigerian Household Staple)
        const standMat = new THREE.MeshStandardMaterial({ color: 0x475569, metalness: 0.8 });
        const stand = new THREE.Mesh(new THREE.BoxGeometry(0.8, 2.0, 0.8), standMat);
        stand.position.set(3.4, 1.0, -1.2);
        h.add(stand);

        const tankMat = new THREE.MeshStandardMaterial({ color: 0x09090b, roughness: 0.4 });
        const tank = new THREE.Mesh(new THREE.CylinderGeometry(0.55, 0.55, 1.1, 16), tankMat);
        tank.position.set(3.4, 2.5, -1.2);
        tank.castShadow = true;
        h.add(tank);

        // 5. Soundproof Mikano Generator House
        const genMat = new THREE.MeshStandardMaterial({ color: 0x334155 });
        const genHouse = new THREE.Mesh(new THREE.BoxGeometry(1.2, 0.9, 1.2), genMat);
        genHouse.position.set(3.4, 0.45, 0.8);
        genHouse.castShadow = true;
        h.add(genHouse);

        this.currentBuildingGroup.add(h);
    },

    buildPersonaModel() {
        const char = (typeof GameApp !== 'undefined' && GameApp.character) ? GameApp.character : {};
        let cfg = {};
        if (char.avatar && typeof char.avatar === 'string' && char.avatar.trim().startsWith('{')) {
            try { cfg = JSON.parse(char.avatar); } catch(e){}
        }

        const personaGroup = new THREE.Group();
        personaGroup.position.set(0.6, 0, 3.8); // Prominent front & center on red carpet
        personaGroup.rotation.y = -Math.PI / 16;

        // Executive Platform & Soft Contact Shadow
        const shadowGeo = new THREE.CylinderGeometry(0.75, 0.75, 0.02, 24);
        const shadowMat = new THREE.MeshBasicMaterial({ color: 0x09090b, transparent: true, opacity: 0.35 });
        const shadowMesh = new THREE.Mesh(shadowGeo, shadowMat);
        shadowMesh.position.y = 0.01;
        personaGroup.add(shadowMesh);

        // Pedestal Disc
        const pedGeo = new THREE.CylinderGeometry(0.85, 0.85, 0.04, 24);
        const pedMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, metalness: 0.8, roughness: 0.2 });
        const pedMesh = new THREE.Mesh(pedGeo, pedMat);
        pedMesh.position.y = 0.02;
        personaGroup.add(pedMesh);

        // Determine Character Sprite Image
        let charId = cfg.characterId || 'tunde';
        if (!cfg.characterId && char.full_name) {
            const nameLower = char.full_name.toLowerCase();
            const roster = ['tunde', 'emeka', 'chidi', 'farouk', 'ibrahim', 'segun', 'zainab', 'blessing', 'ngozi'];
            for (const r of roster) {
                if (nameLower.includes(r)) { charId = r; break; }
            }
        }
        const outfit = cfg.outfit || cfg.topType || char.outfit || 'hoodie';
        const imgUrl = (typeof window.getCharacterOutfitImage === 'function')
            ? window.getCharacterOutfitImage(charId, outfit)
            : `assets/img/characters/${charId}/Man_standing_in_hoodie_20261005064533.png`;

        // Render Crisp Big 3D Character Sprite Billboard (Transparent PNG)
        const loader = new THREE.TextureLoader();
        loader.load(imgUrl, (texture) => {
            texture.minFilter = THREE.LinearFilter;
            texture.magFilter = THREE.LinearFilter;

            const spriteMat = new THREE.SpriteMaterial({
                map: texture,
                transparent: true,
                alphaTest: 0.08
            });

            const sprite = new THREE.Sprite(spriteMat);
            // Proportional portrait aspect ratio (380x768 -> 1:2)
            sprite.scale.set(1.85, 3.7, 1.0);
            sprite.position.set(0, 1.85, 0);
            personaGroup.add(sprite);

            if (this.renderer && this.scene && this.camera) {
                this.renderer.render(this.scene, this.camera);
            }
        });

        this.characterMesh = personaGroup;
        this.currentBuildingGroup.add(personaGroup);
    },

    toggleView(mode) {
        this.viewMode = mode;
        this.updateScene();

        // Update UI pill buttons
        const btnWork = document.getElementById('btnViewWorkplace');
        const btnHome = document.getElementById('btnViewHome');
        if (btnWork && btnHome) {
            if (mode === 'workplace') {
                btnWork.className = "px-3 py-1.5 rounded-full text-xs font-bold bg-slate-900 text-white shadow-sm transition";
                btnHome.className = "px-3 py-1.5 rounded-full text-xs font-bold text-slate-600 hover:text-slate-900 transition";
            } else {
                btnWork.className = "px-3 py-1.5 rounded-full text-xs font-bold text-slate-600 hover:text-slate-900 transition";
                btnHome.className = "px-3 py-1.5 rounded-full text-xs font-bold bg-slate-900 text-white shadow-sm transition";
            }
        }
    },

    cycleAtmosphere() {
        const modes = ['day', 'sunset', 'night'];
        const nextIdx = (modes.indexOf(this.timeOfDay) + 1) % modes.length;
        this.updateAtmosphere(modes[nextIdx]);
    },

    toggleAutoRotate() {
        this.autoRotate = !this.autoRotate;
        if (this.controls) {
            this.controls.autoRotate = this.autoRotate;
        }
        const btn = document.getElementById('btnAutoRotate');
        if (btn) {
            btn.innerHTML = this.autoRotate ? `<i class="fa-solid fa-pause text-xs"></i>` : `<i class="fa-solid fa-play text-xs"></i>`;
        }
    },

    resetCamera() {
        if (!this.camera || !this.controls) return;
        this.camera.position.set(11, 7.5, 14);
        this.controls.target.set(0, 2.0, 0);
        this.controls.update();
    },

    onResize() {
        if (!this.container || !this.renderer || !this.camera) return;
        const width = this.container.clientWidth;
        const height = this.container.clientHeight;
        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
    },

    animate() {
        requestAnimationFrame(() => this.animate());

        if (this.controls) {
            this.controls.update();
        }

        // Persona gentle idle breathing
        if (this.characterMesh) {
            const time = Date.now() * 0.0025;
            this.characterMesh.position.y = Math.sin(time) * 0.03;
        }

        if (this.renderer && this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }
};

window.World3D = World3D;
