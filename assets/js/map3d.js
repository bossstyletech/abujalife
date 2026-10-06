/**
 * Abuja Life 3D Map Engine (Three.js)
 * High-fidelity isometric city map of Abuja
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
    isInitialized: false,

    init(containerId = 'map3d-container') {
        this.container = document.getElementById(containerId);
        if (!this.container || typeof THREE === 'undefined') return;

        const width = this.container.clientWidth || 800;
        const height = this.container.clientHeight || 600;

        // Scene Setup
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0xf1f5f9);
        this.scene.fog = new THREE.FogExp2(0xf1f5f9, 0.015);

        // Camera Setup - Isometric Top-Down
        this.camera = new THREE.PerspectiveCamera(40, width / height, 0.1, 1000);
        this.camera.position.set(0, 35, 45);

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
            this.controls.target.set(0, 0, 0);
            this.controls.maxPolarAngle = Math.PI / 3;
            this.controls.minDistance = 10;
            this.controls.maxDistance = 80;
            this.controls.enableRotate = false; // Keep isometric perspective fixed for map
        }

        // Setup Interaction Raycaster
        this.raycaster = new THREE.Raycaster();
        this.mouse = new THREE.Vector2();

        // Lighting Architecture
        this.setupLighting();

        // Build City Scene
        this.buildCityMap();

        // Interaction Events
        this.container.addEventListener('click', this.onClick.bind(this));
        this.container.addEventListener('mousemove', this.onMouseMove.bind(this));
        this.container.addEventListener('touchstart', this.onTouch.bind(this), {passive: true});

        // Responsive Resize
        window.addEventListener('resize', () => this.onResize());

        this.isInitialized = true;
        this.animate();
    },

    setupLighting() {
        // Main Sun Key Light
        this.sunLight = new THREE.DirectionalLight(0xfffaed, 0.95);
        this.sunLight.position.set(16, 40, 14);
        this.sunLight.castShadow = true;
        this.sunLight.shadow.mapSize.width = 2048;
        this.sunLight.shadow.mapSize.height = 2048;
        this.sunLight.shadow.bias = -0.0004;
        
        // Large shadow camera for city scale
        this.sunLight.shadow.camera.left = -30;
        this.sunLight.shadow.camera.right = 30;
        this.sunLight.shadow.camera.top = 30;
        this.sunLight.shadow.camera.bottom = -30;
        
        this.scene.add(this.sunLight);

        // Ambient Fill
        this.ambientLight = new THREE.AmbientLight(0x64748b, 0.65);
        this.scene.add(this.ambientLight);
    },

    buildCityMap() {
        const cityGroup = new THREE.Group();

        // Ground Plane (Abuja Map Base)
        const groundGeo = new THREE.PlaneGeometry(60, 60);
        const groundMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 1.0 });
        const ground = new THREE.Mesh(groundGeo, groundMat);
        ground.rotation.x = -Math.PI / 2;
        ground.receiveShadow = true;
        cityGroup.add(ground);
        
        // Main Highways (Shehu Shagari Way, etc.)
        const roadMat = new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.9 });
        
        const road1 = new THREE.Mesh(new THREE.PlaneGeometry(60, 4), roadMat);
        road1.rotation.x = -Math.PI / 2;
        road1.position.set(0, 0.01, 2);
        cityGroup.add(road1);
        
        const road2 = new THREE.Mesh(new THREE.PlaneGeometry(4, 60), roadMat);
        road2.rotation.x = -Math.PI / 2;
        road2.position.set(-5, 0.01, 0);
        cityGroup.add(road2);

        // Jabi Lake Waterbody
        const lakeGeo = new THREE.PlaneGeometry(15, 12);
        const lakeMat = new THREE.MeshStandardMaterial({ color: 0x0284c7, roughness: 0.1, metalness: 0.8 });
        const lake = new THREE.Mesh(lakeGeo, lakeMat);
        lake.rotation.x = -Math.PI / 2;
        lake.position.set(-18, 0.02, -10);
        cityGroup.add(lake);

        // Add Interactive Landmarks (Pins)
        this.addLandmark(cityGroup, 'gym', 'Maitama Gym', -12, -4, 0x10b981, '🏋️');
        this.addLandmark(cityGroup, 'restaurant', 'Jabi Grill', -15, -12, 0xf59e0b, '🍲');
        this.addLandmark(cityGroup, 'jabi_lake', 'Lake Resort', -20, -8, 0x0ea5e9, '🛥️');
        this.addLandmark(cityGroup, 'banex', 'Banex Plaza', -8, -1, 0x8b5cf6, '📱');
        this.addLandmark(cityGroup, 'market', 'Wuse Market', 2, -2, 0xec4899, '🛍️');
        this.addLandmark(cityGroup, 'fraser', 'Fraser Suites', 4, 8, 0xf43f5e, '🏨');
        this.addLandmark(cityGroup, 'secretariat', 'Secretariat', 14, 5, 0x64748b, '🏛️');
        this.addLandmark(cityGroup, 'cbd_bank', 'CBD Towers', 12, 12, 0x0f172a, '🏦');

        // Decorative Buildings
        this.addDecorBuilding(cityGroup, -20, 10, 3, 8, 4);
        this.addDecorBuilding(cityGroup, -15, 15, 5, 12, 5);
        this.addDecorBuilding(cityGroup, 18, -10, 4, 15, 4);
        this.addDecorBuilding(cityGroup, 22, -5, 6, 10, 6);

        this.scene.add(cityGroup);
    },
    
    addDecorBuilding(parentGroup, x, z, w, h, d) {
        const bMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, roughness: 0.5 });
        const b = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), bMat);
        b.position.set(x, h/2, z);
        b.castShadow = true;
        b.receiveShadow = true;
        parentGroup.add(b);
    },

    addLandmark(parentGroup, id, name, x, z, colorHex, iconChar) {
        const group = new THREE.Group();
        group.position.set(x, 0, z);
        group.userData = { id, type: 'landmark', name };

        // Pin Base
        const pinBaseMat = new THREE.MeshStandardMaterial({ color: colorHex, roughness: 0.2, metalness: 0.5 });
        
        // Build 3D Location Pin Marker
        const pinGeo = new THREE.ConeGeometry(0.8, 2, 16);
        const pin = new THREE.Mesh(pinGeo, pinBaseMat);
        pin.position.y = 2.5;
        pin.rotation.x = Math.PI;
        pin.castShadow = true;
        
        const sphereGeo = new THREE.SphereGeometry(1.2, 16, 16);
        const sphere = new THREE.Mesh(sphereGeo, pinBaseMat);
        sphere.position.y = 3.5;
        sphere.castShadow = true;

        group.add(pin);
        group.add(sphere);

        // Invisible hit box for easier clicking
        const hitGeo = new THREE.BoxGeometry(4, 6, 4);
        const hitMat = new THREE.MeshBasicMaterial({ visible: false });
        const hitBox = new THREE.Mesh(hitGeo, hitMat);
        hitBox.position.y = 3;
        hitBox.userData = group.userData; // Pass data to hitbox
        group.add(hitBox);

        this.interactables.push(hitBox);
        
        // Floating Text Label
        if (typeof document !== 'undefined') {
            const canvas = document.createElement('canvas');
            canvas.width = 256;
            canvas.height = 64;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.roundRect(0, 0, 256, 64, 32);
            ctx.fill();
            
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 4;
            ctx.stroke();

            ctx.font = 'bold 24px Arial, sans-serif';
            ctx.fillStyle = '#0f172a';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(`${iconChar} ${name}`, 128, 32);

            const tex = new THREE.CanvasTexture(canvas);
            const spriteMat = new THREE.SpriteMaterial({ map: tex, sizeAttenuation: true });
            const sprite = new THREE.Sprite(spriteMat);
            sprite.scale.set(6, 1.5, 1);
            sprite.position.set(0, 5.5, 0);
            group.add(sprite);
        }
        
        // Gentle hover animation setup
        group.userData.baseY = 0;
        group.userData.timeOffset = Math.random() * Math.PI * 2;
        
        parentGroup.add(group);
    },
    
    getIntersects(e) {
        const rect = this.renderer.domElement.getBoundingClientRect();
        let clientX, clientY;

        if (e.changedTouches) {
            clientX = e.changedTouches[0].clientX;
            clientY = e.changedTouches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
        }

        this.mouse.x = ((clientX - rect.left) / rect.width) * 2 - 1;
        this.mouse.y = -((clientY - rect.top) / rect.height) * 2 + 1;

        this.raycaster.setFromCamera(this.mouse, this.camera);
        return this.raycaster.intersectObjects(this.interactables);
    },

    onClick(e) {
        if (!this.isInitialized) return;
        
        const intersects = this.getIntersects(e);
        if (intersects.length > 0) {
            const destId = intersects[0].object.userData.id;
            if (destId && window.GameApp) {
                // Flash the object white briefly
                const mat = intersects[0].object.parent.children[1].material;
                const oldColor = mat.color.getHex();
                mat.color.setHex(0xffffff);
                setTimeout(() => { if (mat) mat.color.setHex(oldColor); }, 150);
                
                GameApp.openTravelModal(destId);
            }
        }
    },
    
    onMouseMove(e) {
        if (!this.isInitialized) return;
        const intersects = this.getIntersects(e);
        if (intersects.length > 0) {
            this.container.style.cursor = 'pointer';
        } else {
            this.container.style.cursor = 'default';
        }
    },
    
    onTouch(e) {
        // Just for raycaster consistency if needed
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

        // Animate pins bouncing
        const time = Date.now() * 0.002;
        this.interactables.forEach(hitBox => {
            const group = hitBox.parent;
            group.position.y = Math.sin(time + group.userData.timeOffset) * 0.3;
        });

        if (this.renderer && this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }
};

window.Map3D = Map3D;
