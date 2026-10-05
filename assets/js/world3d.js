/**
 * Abuja Life 3D Interactive World Engine (Three.js)
 * Clean, high-fidelity 3D rotatable & zoomable showcase of Workplace & Residence with Character Persona
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
    autoRotate: true,
    isInitialized: false,

    init(containerId = 'world3d-container') {
        this.container = document.getElementById(containerId);
        if (!this.container || typeof THREE === 'undefined') return;

        const width = this.container.clientWidth || 600;
        const height = this.container.clientHeight || 450;

        // Scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0xf8fafc); // Crisp white/slate soft backdrop

        // Camera
        this.camera = new THREE.PerspectiveCamera(40, width / height, 0.1, 1000);
        this.camera.position.set(12, 10, 15);

        // Renderer
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        this.container.innerHTML = '';
        this.container.appendChild(this.renderer.domElement);

        // Orbit Controls (Infinite zoom & rotation with zero degradation)
        if (typeof THREE.OrbitControls !== 'undefined') {
            this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.05;
            this.controls.maxPolarAngle = Math.PI / 2.05; // Don't go below ground
            this.controls.minDistance = 4;
            this.controls.maxDistance = 35;
            this.controls.autoRotate = this.autoRotate;
            this.controls.autoRotateSpeed = 1.0;
        }

        // Lighting (Clean, soft architectural studio lighting)
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.75);
        this.scene.add(ambientLight);

        const sunLight = new THREE.DirectionalLight(0xfff7ed, 1.2);
        sunLight.position.set(15, 25, 12);
        sunLight.castShadow = true;
        sunLight.shadow.mapSize.width = 2048;
        sunLight.shadow.mapSize.height = 2048;
        sunLight.shadow.bias = -0.0005;
        this.scene.add(sunLight);

        const fillLight = new THREE.DirectionalLight(0xe0f2fe, 0.4);
        fillLight.position.set(-15, 10, -10);
        this.scene.add(fillLight);

        // Ground Platform
        this.createGround();

        // Initial Build
        this.updateScene();

        // Responsive Resizing
        window.addEventListener('resize', () => this.onResize());

        this.isInitialized = true;
        this.animate();
    },

    createGround() {
        // Base ground slab
        const groundGeo = new THREE.CylinderGeometry(8, 8.2, 0.4, 48);
        const groundMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 0.8 });
        const ground = new THREE.Mesh(groundGeo, groundMat);
        ground.position.y = -0.2;
        ground.receiveShadow = true;
        this.scene.add(ground);

        // Pavement disc
        const paveGeo = new THREE.CylinderGeometry(7.2, 7.2, 0.05, 48);
        const paveMat = new THREE.MeshStandardMaterial({ color: 0xf1f5f9, roughness: 0.6 });
        const pave = new THREE.Mesh(paveGeo, paveMat);
        pave.position.y = 0.03;
        pave.receiveShadow = true;
        this.scene.add(pave);

        // Decorative palm trees
        this.addPalmTree(5.5, -3.5);
        this.addPalmTree(-5.2, -3.8);
        this.addPalmTree(5.2, 3.8);
    },

    addPalmTree(x, z) {
        const group = new THREE.Group();
        group.position.set(x, 0, z);

        // Trunk
        const trunkGeo = new THREE.CylinderGeometry(0.12, 0.18, 2.8, 8);
        const trunkMat = new THREE.MeshStandardMaterial({ color: 0x78553d, roughness: 0.9 });
        const trunk = new THREE.Mesh(trunkGeo, trunkMat);
        trunk.position.y = 1.4;
        trunk.rotation.z = (Math.random() - 0.5) * 0.15;
        trunk.castShadow = true;
        group.add(trunk);

        // Leaves canopy
        const leafMat = new THREE.MeshStandardMaterial({ color: 0x16a34a, roughness: 0.6, flatShading: true });
        for (let i = 0; i < 5; i++) {
            const leafGeo = new THREE.ConeGeometry(0.8, 1.4, 4);
            const leaf = new THREE.Mesh(leafGeo, leafMat);
            leaf.position.set(0, 2.7, 0);
            leaf.rotation.x = Math.PI / 3;
            leaf.rotation.y = (i * Math.PI * 2) / 5;
            leaf.castShadow = true;
            group.add(leaf);
        }

        this.scene.add(group);
    },

    updateScene(buildingType = null) {
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
        const char = GameApp.character || {};
        const jobTitle = (char.job_title || '').toLowerCase();

        const building = new THREE.Group();

        if (jobTitle.includes('minister') || jobTitle.includes('ministry') || jobTitle.includes('clerical')) {
            // Federal Secretariat / Government Ministry (Classic stone pillars & Nigerian Flag)
            const mainGeo = new THREE.BoxGeometry(6, 4.2, 4.5);
            const mainMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.5 });
            const mainBody = new THREE.Mesh(mainGeo, mainMat);
            mainBody.position.y = 2.1;
            mainBody.castShadow = true;
            mainBody.receiveShadow = true;
            building.add(mainBody);

            // Columns
            const colMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 0.3 });
            for (let i = -2.2; i <= 2.2; i += 1.4) {
                const colGeo = new THREE.CylinderGeometry(0.18, 0.18, 4.2, 16);
                const col = new THREE.Mesh(colGeo, colMat);
                col.position.set(i, 2.1, 2.45);
                col.castShadow = true;
                building.add(col);
            }

            // Roof Pediment
            const roofGeo = new THREE.ConeGeometry(4.8, 1.2, 4);
            const roofMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.4 });
            const roof = new THREE.Mesh(roofGeo, roofMat);
            roof.position.set(0, 4.8, 0);
            roof.rotation.y = Math.PI / 4;
            roof.castShadow = true;
            building.add(roof);

            // Nigerian Flag Pole
            const poleGeo = new THREE.CylinderGeometry(0.04, 0.04, 3, 8);
            const poleMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.8 });
            const pole = new THREE.Mesh(poleGeo, poleMat);
            pole.position.set(2.4, 1.5, 3.2);
            building.add(pole);

            // Green White Green Flag
            const flagGeo = new THREE.BoxGeometry(0.8, 0.45, 0.02);
            const flagMat = new THREE.MeshStandardMaterial({ color: 0x16a34a });
            const flag = new THREE.Mesh(flagGeo, flagMat);
            flag.position.set(2.8, 2.7, 3.2);
            building.add(flag);

        } else if (jobTitle.includes('developer') || jobTitle.includes('tech') || jobTitle.includes('consultant')) {
            // Modern Tech Innovation Hub in Jabi / Wuse 2 (Glass cube, antennas, signage)
            const mainGeo = new THREE.BoxGeometry(5.2, 5, 4.5);
            const mainMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.2 });
            const mainBody = new THREE.Mesh(mainGeo, mainMat);
            mainBody.position.y = 2.5;
            mainBody.castShadow = true;
            mainBody.receiveShadow = true;
            building.add(mainBody);

            // Glass curtain panels
            const glassGeo = new THREE.PlaneGeometry(4.4, 4);
            const glassMat = new THREE.MeshStandardMaterial({ color: 0x38bdf8, roughness: 0.1, metalness: 0.7, transparent: true, opacity: 0.85 });
            const glass = new THREE.Mesh(glassGeo, glassMat);
            glass.position.set(0, 2.5, 2.27);
            building.add(glass);

            // Roof Terrace & Solar Panels
            const solarGeo = new THREE.BoxGeometry(2.5, 0.1, 1.8);
            const solarMat = new THREE.MeshStandardMaterial({ color: 0x0284c7, metalness: 0.9, roughness: 0.2 });
            const solar = new THREE.Mesh(solarGeo, solarMat);
            solar.position.set(0, 5.1, 0);
            solar.rotation.x = 0.2;
            building.add(solar);

        } else {
            // Standard Abuja Commercial Building / Plaza / Bank
            const mainGeo = new THREE.BoxGeometry(5, 4, 4);
            const mainMat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.4 });
            const mainBody = new THREE.Mesh(mainGeo, mainMat);
            mainBody.position.y = 2;
            mainBody.castShadow = true;
            building.add(mainBody);

            // Entrance Door Canopy
            const canopyGeo = new THREE.BoxGeometry(2.2, 0.2, 1.2);
            const canopyMat = new THREE.MeshStandardMaterial({ color: 0x059669 });
            const canopy = new THREE.Mesh(canopyGeo, canopyMat);
            canopy.position.set(0, 2.2, 2.6);
            building.add(canopy);

            // Entrance Glass Door
            const doorGeo = new THREE.PlaneGeometry(1.6, 2.0);
            const doorMat = new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.2 });
            const door = new THREE.Mesh(doorGeo, doorMat);
            door.position.set(0, 1.0, 2.02);
            building.add(door);
        }

        this.currentBuildingGroup.add(building);
    },

    buildHomeModel() {
        const char = GameApp.character || {};
        const houseName = (char.property_name || '').toLowerCase();

        const home = new THREE.Group();

        if (houseName.includes('mansion') || houseName.includes('villa') || houseName.includes('duplex')) {
            // Luxury Maitama / Asokoro Smart Villa with Pool & Balcony
            const mainGeo = new THREE.BoxGeometry(5.6, 4.4, 4.2);
            const mainMat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.3 });
            const mainBody = new THREE.Mesh(mainGeo, mainMat);
            mainBody.position.y = 2.2;
            mainBody.castShadow = true;
            home.add(mainBody);

            // 2nd Floor Modern Balcony
            const balcGeo = new THREE.BoxGeometry(3.5, 0.15, 1.4);
            const balcMat = new THREE.MeshStandardMaterial({ color: 0x0f172a });
            const balc = new THREE.Mesh(balcGeo, balcMat);
            balc.position.set(0, 2.8, 2.7);
            home.add(balc);

            // Luxury Swimming Pool at front
            const poolGeo = new THREE.BoxGeometry(3.0, 0.1, 1.6);
            const poolMat = new THREE.MeshStandardMaterial({ color: 0x0ea5e9, roughness: 0.1, metalness: 0.3 });
            const pool = new THREE.Mesh(poolGeo, poolMat);
            pool.position.set(-2.2, 0.05, 3.2);
            home.add(pool);

        } else {
            // Self-Contain or Gwarinpa Flat with Water Tank (GP Tank)
            const mainGeo = new THREE.BoxGeometry(4.5, 3.2, 3.8);
            const mainMat = new THREE.MeshStandardMaterial({ color: 0xf1f5f9, roughness: 0.6 });
            const mainBody = new THREE.Mesh(mainGeo, mainMat);
            mainBody.position.y = 1.6;
            mainBody.castShadow = true;
            home.add(mainBody);

            // Pitched Roof
            const roofGeo = new THREE.ConeGeometry(3.8, 1.2, 4);
            const roofMat = new THREE.MeshStandardMaterial({ color: 0x991b1b, roughness: 0.6 });
            const roof = new THREE.Mesh(roofGeo, roofMat);
            roof.position.set(0, 3.8, 0);
            roof.rotation.y = Math.PI / 4;
            roof.castShadow = true;
            home.add(roof);

            // Overhead Water Tank (Black GP Tank on stand)
            const standGeo = new THREE.BoxGeometry(0.8, 1.8, 0.8);
            const standMat = new THREE.MeshStandardMaterial({ color: 0x475569, metalness: 0.7 });
            const stand = new THREE.Mesh(standGeo, standMat);
            stand.position.set(3.2, 0.9, -1.2);
            home.add(stand);

            const tankGeo = new THREE.CylinderGeometry(0.55, 0.55, 1.1, 16);
            const tankMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.4 });
            const tank = new THREE.Mesh(tankGeo, tankMat);
            tank.position.set(3.2, 2.3, -1.2);
            tank.castShadow = true;
            home.add(tank);
        }

        this.currentBuildingGroup.add(home);
    },

    buildPersonaModel() {
        const char = GameApp.character || {};
        let cfg = {};
        if (char.avatar && typeof char.avatar === 'string' && char.avatar.trim().startsWith('{')) {
            try { cfg = JSON.parse(char.avatar); } catch(e){}
        }

        const personaGroup = new THREE.Group();
        personaGroup.position.set(1.4, 0, 3.3); // Standing near the entrance
        personaGroup.rotation.y = -Math.PI / 10;

        // Ground shadow for character
        const shadowGeo = new THREE.CylinderGeometry(0.55, 0.55, 0.02, 24);
        const shadowMat = new THREE.MeshBasicMaterial({ color: 0x0f172a, transparent: true, opacity: 0.25 });
        const shadowMesh = new THREE.Mesh(shadowGeo, shadowMat);
        shadowMesh.position.y = 0.01;
        personaGroup.add(shadowMesh);

        // Render photorealistic 3D Character Sprite
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
            : `assets/img/characters/${charId}/Man_standing_in_hoodie_20261005064533.jpg`;

        const loader = new THREE.TextureLoader();
        loader.load(imgUrl, (texture) => {
            texture.minFilter = THREE.LinearFilter;
            texture.magFilter = THREE.LinearFilter;

            const spriteMat = new THREE.SpriteMaterial({
                map: texture,
                transparent: true,
                alphaTest: 0.02
            });

            const sprite = new THREE.Sprite(spriteMat);
            sprite.scale.set(2.4, 3.12, 1);
            sprite.position.set(0, 1.56, 0);
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

    toggleAutoRotate() {
        this.autoRotate = !this.autoRotate;
        if (this.controls) {
            this.controls.autoRotate = this.autoRotate;
        }
        const btn = document.getElementById('btnAutoRotate');
        if (btn) {
            btn.innerHTML = this.autoRotate ? `<i class="fa-solid fa-pause text-xs"></i> Pause Spin` : `<i class="fa-solid fa-play text-xs"></i> 360 Spin`;
        }
    },

    resetCamera() {
        if (!this.camera || !this.controls) return;
        this.camera.position.set(12, 10, 15);
        this.controls.target.set(0, 1.5, 0);
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
