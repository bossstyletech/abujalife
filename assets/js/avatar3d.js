/**
 * Abuja Life - Real-Time 3D Bitmoji Avatar Engine
 * Snapchat Bitmoji style: Full body, 360° interactive rotation, real-time swapping of
 * hair, skin, tops, jeans/bottoms, shoes/kicks, and accessories.
 */

class Avatar3DStudio {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.options = Object.assign({
            width: 320,
            height: 480,
            allowRotate: true,
            interactive: true,
            showPlatform: true
        }, options);

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.characterGroup = null;

        // Current Avatar Customization State
        this.state = {
            skinTone: '#704225',
            hairStyle: 'fade', // fade, afro, dreads, cornrows, waves, buzz
            hairColor: '#111111',
            topType: 'hoodie', // hoodie, tshirt, agbada, suit
            topColor: '#059669', // emerald default
            bottomType: 'jeans_blue', // jeans_blue, jeans_black, sweatpants, chinos, white_trouser
            bottomColor: '#2563eb', // denim blue
            shoeType: 'sneakers', // sneakers, jordans, loafers, slides
            shoeColor: '#ffffff',
            accessory: 'none' // none, sunglasses, chain, fila_cap
        };

        this.isDragging = false;
        this.prevMouseX = 0;
        this.rotationSpeed = 0.008;

        if (this.container && typeof THREE !== 'undefined') {
            this.init();
        }
    }

    init() {
        const width = (this.container.clientWidth > 0 ? this.container.clientWidth : this.options.width) || 320;
        const height = (this.container.clientHeight > 0 ? this.container.clientHeight : this.options.height) || 480;

        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0xffffff); // Pure crisp white canvas

        this.camera = new THREE.PerspectiveCamera(38, width / height, 0.1, 100);
        this.camera.position.set(0, 1.35, 4.2); // Framed nicely from head to feet

        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        this.container.innerHTML = '';
        this.container.appendChild(this.renderer.domElement);

        // Studio Lights
        const hemiLight = new THREE.HemisphereLight(0xffffff, 0xf1f5f9, 0.85);
        this.scene.add(hemiLight);

        const keyLight = new THREE.DirectionalLight(0xfff7ed, 1.15);
        keyLight.position.set(4, 6, 5);
        keyLight.castShadow = true;
        keyLight.shadow.mapSize.width = 1024;
        keyLight.shadow.mapSize.height = 1024;
        this.scene.add(keyLight);

        const rimLight = new THREE.DirectionalLight(0xe0f2fe, 0.55);
        rimLight.position.set(-4, 3, -3);
        this.scene.add(rimLight);

        if (this.options.showPlatform) {
            // Soft circular pedestal platform for feet
            const pedGeo = new THREE.CylinderGeometry(1.2, 1.3, 0.1, 36);
            const pedMat = new THREE.MeshStandardMaterial({ color: 0xf1f5f9, roughness: 0.8 });
            const ped = new THREE.Mesh(pedGeo, pedMat);
            ped.position.y = -0.05;
            ped.receiveShadow = true;
            this.scene.add(ped);
        }

        // Setup Drag-To-Turn rotation controls
        this.setupDragRotation();

        // Build Initial Character
        this.rebuildCharacter();

        // Responsive resizing listener
        window.addEventListener('resize', () => this.resize());

        // Animation Loop
        this.animate();
    }

    resize() {
        if (!this.container || !this.renderer || !this.camera) return;
        const width = this.container.clientWidth || this.options.width || 320;
        const height = this.container.clientHeight || this.options.height || 480;
        if (width > 0 && height > 0) {
            this.camera.aspect = width / height;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(width, height);
        }
    }

    setupDragRotation() {
        const el = this.renderer.domElement;

        const onStart = (clientX) => {
            this.isDragging = true;
            this.prevMouseX = clientX;
            el.style.cursor = 'grabbing';
        };

        const onMove = (clientX) => {
            if (!this.isDragging || !this.characterGroup) return;
            const delta = clientX - this.prevMouseX;
            this.characterGroup.rotation.y += delta * this.rotationSpeed;
            this.prevMouseX = clientX;
        };

        const onEnd = () => {
            this.isDragging = false;
            el.style.cursor = 'grab';
        };

        el.style.cursor = 'grab';

        // Mouse events
        el.addEventListener('mousedown', (e) => onStart(e.clientX));
        window.addEventListener('mousemove', (e) => onMove(e.clientX));
        window.addEventListener('mouseup', onEnd);

        // Touch events for mobile
        el.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) onStart(e.touches[0].clientX);
        });
        window.addEventListener('touchmove', (e) => {
            if (e.touches.length === 1) onMove(e.touches[0].clientX);
        });
        window.addEventListener('touchend', onEnd);
    }

    turnAround(angle = Math.PI) {
        if (this.characterGroup) {
            this.characterGroup.rotation.y += angle;
        }
    }

    rebuildCharacter() {
        if (this.characterGroup) {
            this.scene.remove(this.characterGroup);
            this.characterGroup = null;
        }

        this.characterGroup = new THREE.Group();
        this.characterGroup.position.set(0, 0, 0);

        const s = this.state;
        const skinColor = new THREE.Color(s.skinTone);
        const skinMat = new THREE.MeshStandardMaterial({ color: skinColor, roughness: 0.45 });

        // 1. FEET & SHOES
        this.buildShoes(this.characterGroup, skinMat);

        // 2. LEGS & JEANS / TROUSERS
        this.buildBottoms(this.characterGroup, skinMat);

        // 3. TORSO & TOPS (HOODIE / TEE / AGBADA / SUIT)
        this.buildTop(this.characterGroup, skinMat);

        // 4. HEAD & FACE
        this.buildHead(this.characterGroup, skinMat);

        // 5. HAIRSTYLE
        this.buildHair(this.characterGroup);

        // 6. ACCESSORIES
        this.buildAccessories(this.characterGroup);

        this.scene.add(this.characterGroup);
    }

    // --- BUILD PARTS ---

    buildShoes(parent, skinMat) {
        const s = this.state;
        const shoeMat = new THREE.MeshStandardMaterial({ color: new THREE.Color(s.shoeColor), roughness: 0.4 });
        const soleMat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.2 });

        const createFoot = (x) => {
            const footGroup = new THREE.Group();
            footGroup.position.set(x, 0, 0);

            if (s.shoeType === 'jordans') {
                // High-top Air Jordans (Bred / Chicago Red & Black)
                const upperGeo = new THREE.BoxGeometry(0.24, 0.24, 0.42);
                const jordanMat = new THREE.MeshStandardMaterial({ color: 0xbe123c, roughness: 0.35 }); // Chicago red
                const upper = new THREE.Mesh(upperGeo, jordanMat);
                upper.position.set(0, 0.13, 0.05);
                upper.castShadow = true;
                footGroup.add(upper);

                // Black high collar
                const collar = new THREE.Mesh(new THREE.BoxGeometry(0.22, 0.12, 0.22), new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.4 }));
                collar.position.set(0, 0.22, -0.04);
                footGroup.add(collar);

                // Thick white sole
                const soleGeo = new THREE.BoxGeometry(0.26, 0.06, 0.44);
                const sole = new THREE.Mesh(soleGeo, soleMat);
                sole.position.set(0, 0.03, 0.05);
                footGroup.add(sole);

            } else if (s.shoeType === 'loafers') {
                // Sleek Italian Leather Loafers
                const loaferGeo = new THREE.BoxGeometry(0.22, 0.12, 0.38);
                const loaferMat = new THREE.MeshStandardMaterial({ color: 0x1e1e1e, roughness: 0.25, metalness: 0.1 });
                const loafer = new THREE.Mesh(loaferGeo, loaferMat);
                loafer.position.set(0, 0.07, 0.06);
                loafer.castShadow = true;
                footGroup.add(loafer);

                // Gold horsebit buckle accent
                const bitGeo = new THREE.BoxGeometry(0.12, 0.02, 0.04);
                const bitMat = new THREE.MeshStandardMaterial({ color: 0xf59e0b, metalness: 0.9, roughness: 0.2 });
                const bit = new THREE.Mesh(bitGeo, bitMat);
                bit.position.set(0, 0.13, 0.1);
                footGroup.add(bit);

            } else if (s.shoeType === 'slides') {
                // Nigerian Casual Slides (Visible feet & toes!)
                const footSkin = new THREE.Mesh(new THREE.BoxGeometry(0.2, 0.1, 0.32), skinMat);
                footSkin.position.set(0, 0.07, 0.04);
                footSkin.castShadow = true;
                footGroup.add(footSkin);

                // Slide base sole
                const slideBase = new THREE.Mesh(new THREE.BoxGeometry(0.23, 0.04, 0.38), new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.5 }));
                slideBase.position.set(0, 0.02, 0.05);
                footGroup.add(slideBase);

                // Slide upper strap
                const strapColor = s.shoeColor === '#ffffff' ? 0x0f172a : new THREE.Color(s.shoeColor);
                const strap = new THREE.Mesh(new THREE.BoxGeometry(0.24, 0.06, 0.14), new THREE.MeshStandardMaterial({ color: strapColor, roughness: 0.4 }));
                strap.position.set(0, 0.12, 0.02);
                footGroup.add(strap);

            } else {
                // Classic Crisp White Sneakers (Air Force 1s)
                const upperGeo = new THREE.BoxGeometry(0.23, 0.16, 0.39);
                const upper = new THREE.Mesh(upperGeo, shoeMat);
                upper.position.set(0, 0.09, 0.06);
                upper.castShadow = true;
                footGroup.add(upper);

                const soleGeo = new THREE.BoxGeometry(0.25, 0.05, 0.41);
                const sole = new THREE.Mesh(soleGeo, soleMat);
                sole.position.set(0, 0.025, 0.06);
                footGroup.add(sole);
            }

            return footGroup;
        };

        parent.add(createFoot(-0.19));
        parent.add(createFoot(0.19));
    }

    buildBottoms(parent, skinMat) {
        const s = this.state;
        let pantsColor = 0x1d4ed8; // Classic denim blue

        if (s.bottomType === 'jeans_black') pantsColor = 0x0f172a; // Slim black denim
        else if (s.bottomType === 'sweatpants') pantsColor = 0x475569; // Comfy grey fleece
        else if (s.bottomType === 'chinos') pantsColor = 0xb45309; // Khaki / camel
        else if (s.bottomType === 'white_trouser') pantsColor = 0xf8fafc; // Pristine white
        else if (s.bottomType === 'jeans_blue') pantsColor = 0x1d4ed8;
        else if (s.bottomColor) pantsColor = new THREE.Color(s.bottomColor);

        const pantsMat = new THREE.MeshStandardMaterial({ color: pantsColor, roughness: 0.65 });

        // Left Leg
        const leftLegGeo = new THREE.CylinderGeometry(0.12, 0.1, 0.85, 14);
        const leftLeg = new THREE.Mesh(leftLegGeo, pantsMat);
        leftLeg.position.set(-0.19, 0.58, 0);
        leftLeg.castShadow = true;
        parent.add(leftLeg);

        // Right Leg
        const rightLegGeo = new THREE.CylinderGeometry(0.12, 0.1, 0.85, 14);
        const rightLeg = new THREE.Mesh(rightLegGeo, pantsMat);
        rightLeg.position.set(0.19, 0.58, 0);
        rightLeg.castShadow = true;
        parent.add(rightLeg);

        // Pelvis / Waist
        const waistGeo = new THREE.CylinderGeometry(0.27, 0.25, 0.28, 14);
        const waist = new THREE.Mesh(waistGeo, pantsMat);
        waist.position.set(0, 1.05, 0);
        waist.castShadow = true;
        parent.add(waist);

        // Belt & Buckle
        const beltGeo = new THREE.CylinderGeometry(0.275, 0.275, 0.05, 16);
        const beltMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.4 });
        const belt = new THREE.Mesh(beltGeo, beltMat);
        belt.position.set(0, 1.15, 0);
        parent.add(belt);

        const buckleGeo = new THREE.BoxGeometry(0.08, 0.05, 0.03);
        const buckleMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0, metalness: 0.85, roughness: 0.2 });
        const buckle = new THREE.Mesh(buckleGeo, buckleMat);
        buckle.position.set(0, 1.15, 0.27);
        parent.add(buckle);
    }

    buildTop(parent, skinMat) {
        const s = this.state;
        const topColor = new THREE.Color(s.topColor);
        const topMat = new THREE.MeshStandardMaterial({ color: topColor, roughness: 0.6 });

        if (s.topType === 'agbada') {
            // Traditional Wide Embroidered Agbada
            const agbadaGeo = new THREE.BoxGeometry(0.85, 0.95, 0.4);
            const agbada = new THREE.Mesh(agbadaGeo, topMat);
            agbada.position.set(0, 1.5, 0);
            agbada.castShadow = true;
            parent.add(agbada);

            // Neck Embroidery Plate
            const embGeo = new THREE.BoxGeometry(0.24, 0.35, 0.42);
            const embMat = new THREE.MeshStandardMaterial({ color: 0xd97706, roughness: 0.3, metalness: 0.6 }); // Gold embroidery
            const emb = new THREE.Mesh(embGeo, embMat);
            emb.position.set(0, 1.7, 0);
            parent.add(emb);

        } else if (s.topType === 'suit') {
            // Corporate Suit & Tie
            const suitGeo = new THREE.BoxGeometry(0.58, 0.85, 0.32);
            const suit = new THREE.Mesh(suitGeo, topMat);
            suit.position.set(0, 1.5, 0);
            suit.castShadow = true;
            parent.add(suit);

            // White Inner Shirt
            const shirtGeo = new THREE.BoxGeometry(0.2, 0.35, 0.33);
            const shirt = new THREE.Mesh(shirtGeo, new THREE.MeshStandardMaterial({ color: 0xffffff }));
            shirt.position.set(0, 1.7, 0);
            parent.add(shirt);

            // Red Corporate Tie
            const tieGeo = new THREE.BoxGeometry(0.06, 0.4, 0.35);
            const tie = new THREE.Mesh(tieGeo, new THREE.MeshStandardMaterial({ color: 0xb91c1c }));
            tie.position.set(0, 1.62, 0);
            parent.add(tie);

        } else if (s.topType === 'tshirt') {
            // Casual T-Shirt (Short Sleeves showing bare skin arms)
            const teeGeo = new THREE.BoxGeometry(0.54, 0.78, 0.3);
            const tee = new THREE.Mesh(teeGeo, topMat);
            tee.position.set(0, 1.48, 0);
            tee.castShadow = true;
            parent.add(tee);

            // Bare Skin Arms
            const leftArm = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.07, 0.7, 8), skinMat);
            leftArm.position.set(-0.35, 1.35, 0);
            leftArm.castShadow = true;
            parent.add(leftArm);

            const rightArm = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.07, 0.7, 8), skinMat);
            rightArm.position.set(0.35, 1.35, 0);
            rightArm.castShadow = true;
            parent.add(rightArm);

        } else {
            // Modern Techie Oversized Hoodie with Pouch
            const hoodieGeo = new THREE.BoxGeometry(0.58, 0.82, 0.34);
            const hoodie = new THREE.Mesh(hoodieGeo, topMat);
            hoodie.position.set(0, 1.5, 0);
            hoodie.castShadow = true;
            parent.add(hoodie);

            // Front Kangaroo Pouch Pocket
            const pocketGeo = new THREE.BoxGeometry(0.36, 0.22, 0.08);
            const pocket = new THREE.Mesh(pocketGeo, topMat);
            pocket.position.set(0, 1.32, 0.16);
            parent.add(pocket);

            // Sleeves
            const leftSleeve = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.08, 0.72, 8), topMat);
            leftSleeve.position.set(-0.36, 1.36, 0);
            parent.add(leftSleeve);

            const rightSleeve = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.08, 0.72, 8), topMat);
            rightSleeve.position.set(0.36, 1.36, 0);
            parent.add(rightSleeve);
        }
    }

    buildHead(parent, skinMat) {
        const headGroup = new THREE.Group();
        headGroup.position.set(0, 2.15, 0);

        // Head Base
        const headGeo = new THREE.SphereGeometry(0.28, 24, 24);
        const head = new THREE.Mesh(headGeo, skinMat);
        head.scale.set(1, 1.1, 1);
        head.castShadow = true;
        headGroup.add(head);

        // Eyes (Bitmoji Style)
        const eyeMat = new THREE.MeshBasicMaterial({ color: 0x111111 });
        const whiteMat = new THREE.MeshBasicMaterial({ color: 0xffffff });

        const leftEyeWhite = new THREE.Mesh(new THREE.SphereGeometry(0.055, 12, 12), whiteMat);
        leftEyeWhite.position.set(-0.09, 0.03, 0.24);
        headGroup.add(leftEyeWhite);

        const leftPupil = new THREE.Mesh(new THREE.SphereGeometry(0.032, 12, 12), eyeMat);
        leftPupil.position.set(-0.09, 0.03, 0.275);
        headGroup.add(leftPupil);

        const rightEyeWhite = new THREE.Mesh(new THREE.SphereGeometry(0.055, 12, 12), whiteMat);
        rightEyeWhite.position.set(0.09, 0.03, 0.24);
        headGroup.add(rightEyeWhite);

        const rightPupil = new THREE.Mesh(new THREE.SphereGeometry(0.032, 12, 12), eyeMat);
        rightPupil.position.set(0.09, 0.03, 0.275);
        headGroup.add(rightPupil);

        // Eyebrows
        const browMat = new THREE.MeshBasicMaterial({ color: 0x111111 });
        const leftBrow = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.02, 0.02), browMat);
        leftBrow.position.set(-0.09, 0.11, 0.26);
        leftBrow.rotation.z = 0.1;
        headGroup.add(leftBrow);

        const rightBrow = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.02, 0.02), browMat);
        rightBrow.position.set(0.09, 0.11, 0.26);
        rightBrow.rotation.z = -0.1;
        headGroup.add(rightBrow);

        // Smile
        const smileGeo = new THREE.TorusGeometry(0.06, 0.012, 8, 12, Math.PI);
        const smileMat = new THREE.MeshBasicMaterial({ color: 0x3d2314 });
        const smile = new THREE.Mesh(smileGeo, smileMat);
        smile.position.set(0, -0.11, 0.26);
        smile.rotation.x = Math.PI;
        headGroup.add(smile);

        // Ears
        const earGeo = new THREE.SphereGeometry(0.06, 12, 12);
        const leftEar = new THREE.Mesh(earGeo, skinMat);
        leftEar.position.set(-0.28, 0.02, 0);
        leftEar.scale.set(0.5, 1, 0.8);
        headGroup.add(leftEar);

        const rightEar = new THREE.Mesh(earGeo, skinMat);
        rightEar.position.set(0.28, 0.02, 0);
        rightEar.scale.set(0.5, 1, 0.8);
        headGroup.add(rightEar);

        parent.add(headGroup);
    }

    buildHair(parent) {
        const s = this.state;
        const hairColor = new THREE.Color(s.hairColor);
        const hairMat = new THREE.MeshStandardMaterial({ color: hairColor, roughness: 0.85 });

        const hairGroup = new THREE.Group();
        hairGroup.position.set(0, 2.15, 0);

        if (s.hairStyle === 'afro') {
            // Volumetric Round Afro Crown
            const afroGeo = new THREE.SphereGeometry(0.36, 18, 18);
            const afro = new THREE.Mesh(afroGeo, hairMat);
            afro.position.set(0, 0.12, -0.02);
            afro.scale.set(1.05, 1.15, 1.05);
            afro.castShadow = true;
            hairGroup.add(afro);

        } else if (s.hairStyle === 'dreads') {
            // Styled Dreadlocks Strands
            const topCap = new THREE.Mesh(new THREE.SphereGeometry(0.3, 16, 16), hairMat);
            topCap.position.set(0, 0.1, -0.02);
            hairGroup.add(topCap);

            // Hanging Dread Strands
            for (let i = 0; i < 14; i++) {
                const angle = (i / 14) * Math.PI * 1.6 - Math.PI * 0.8;
                const strandGeo = new THREE.CylinderGeometry(0.035, 0.03, 0.42, 6);
                const strand = new THREE.Mesh(strandGeo, hairMat);
                strand.position.set(Math.sin(angle) * 0.28, -0.08, Math.cos(angle) * 0.26 - 0.06);
                strand.rotation.z = Math.sin(angle) * 0.3;
                hairGroup.add(strand);
            }

        } else if (s.hairStyle === 'cornrows') {
            // Cornrows / Braided Ridges
            for (let i = -3; i <= 3; i++) {
                const ridgeGeo = new THREE.CylinderGeometry(0.04, 0.035, 0.5, 8);
                const ridge = new THREE.Mesh(ridgeGeo, hairMat);
                ridge.position.set(i * 0.075, 0.14 - Math.abs(i) * 0.02, 0.05);
                ridge.rotation.x = Math.PI / 2.3;
                hairGroup.add(ridge);
            }

        } else if (s.hairStyle === 'buzz') {
            // Very close cropped buzz cut
            const buzzGeo = new THREE.SphereGeometry(0.29, 20, 20);
            const buzz = new THREE.Mesh(buzzGeo, hairMat);
            buzz.position.set(0, 0.02, -0.02);
            buzz.scale.set(1.01, 1.05, 1.02);
            hairGroup.add(buzz);

        } else {
            // Clean Low Taper Fade
            const fadeGeo = new THREE.SphereGeometry(0.3, 20, 20);
            const fade = new THREE.Mesh(fadeGeo, hairMat);
            fade.position.set(0, 0.08, -0.02);
            fade.scale.set(1.02, 1.08, 1.04);
            fade.castShadow = true;
            hairGroup.add(fade);

            // Edge up / hairline band
            const lineGeo = new THREE.TorusGeometry(0.28, 0.025, 8, 24, Math.PI);
            const line = new THREE.Mesh(lineGeo, hairMat);
            line.position.set(0, 0.12, 0.14);
            line.rotation.x = Math.PI / 1.7;
            hairGroup.add(line);
        }

        parent.add(hairGroup);
    }

    buildAccessories(parent) {
        const s = this.state;
        const accGroup = new THREE.Group();

        if (s.accessory === 'sunglasses') {
            // Aviator / VIP Sunglasses
            const glassFrameMat = new THREE.MeshStandardMaterial({ color: 0xd97706, metalness: 0.8 }); // gold frame
            const lensMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.1, metalness: 0.9 }); // dark tint

            const leftLens = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.07, 0.02), lensMat);
            leftLens.position.set(-0.09, 2.18, 0.28);
            accGroup.add(leftLens);

            const rightLens = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.07, 0.02), lensMat);
            rightLens.position.set(0.09, 2.18, 0.28);
            accGroup.add(rightLens);

            const bridge = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.015, 0.02), glassFrameMat);
            bridge.position.set(0, 2.19, 0.28);
            accGroup.add(bridge);

        } else if (s.accessory === 'chain') {
            // Heavy Gold Cuban Link Chain
            const goldMat = new THREE.MeshStandardMaterial({ color: 0xf59e0b, metalness: 0.95, roughness: 0.2 });
            const chainGeo = new THREE.TorusGeometry(0.19, 0.022, 12, 24);
            const chain = new THREE.Mesh(chainGeo, goldMat);
            chain.position.set(0, 1.84, 0.07);
            chain.rotation.x = Math.PI / 2.8;
            accGroup.add(chain);

        } else if (s.accessory === 'fila_cap') {
            // Royal Nigerian Fila Cap
            const capMat = new THREE.MeshStandardMaterial({ color: 0x991b1b, roughness: 0.7 });
            const capGeo = new THREE.CylinderGeometry(0.28, 0.28, 0.24, 16);
            const cap = new THREE.Mesh(capGeo, capMat);
            cap.position.set(0, 2.38, 0);
            cap.rotation.z = -0.15;
            accGroup.add(cap);
        }

        parent.add(accGroup);
    }

    // --- REAL-TIME INTERACTION API ---

    setSkin(hex) {
        this.state.skinTone = hex;
        this.rebuildCharacter();
    }

    setHair(style, colorHex = null) {
        this.state.hairStyle = style;
        if (colorHex) this.state.hairColor = colorHex;
        this.rebuildCharacter();
    }

    setTop(type, colorHex = null) {
        this.state.topType = type;
        if (colorHex) this.state.topColor = colorHex;
        this.rebuildCharacter();
    }

    setBottom(type, colorHex = null) {
        this.state.bottomType = type;
        if (colorHex) this.state.bottomColor = colorHex;
        this.rebuildCharacter();
    }

    setShoes(type, colorHex = null) {
        this.state.shoeType = type;
        if (colorHex) this.state.shoeColor = colorHex;
        this.rebuildCharacter();
    }

    setAccessory(type) {
        this.state.accessory = type;
        this.rebuildCharacter();
    }

    getConfig() {
        return Object.assign({}, this.state);
    }

    loadConfig(cfg) {
        if (!cfg) return;
        Object.assign(this.state, cfg);
        this.rebuildCharacter();
    }

    animate() {
        requestAnimationFrame(() => this.animate());

        // Gentle Bitmoji breathing / idle animation
        if (this.characterGroup) {
            const time = Date.now() * 0.0025;
            this.characterGroup.position.y = Math.sin(time) * 0.015;
        }

        if (this.renderer && this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }
}

window.Avatar3DStudio = Avatar3DStudio;
