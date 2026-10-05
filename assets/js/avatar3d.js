/**
 * Abuja Life - High-Fidelity Animated Bitmoji & Character Sprite Engine
 * Beautiful modular vector character sprites with smooth breathing, eye blinking,
 * natural fabric folds, designer sneakers, authentic Nigerian hairstyles, and 360° interactive rotation.
 */

class Avatar3DStudio {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
        this.options = Object.assign({
            width: 320,
            height: 440,
            showPlatform: true,
            interactive: true,
            animated: true
        }, options);

        // Character Styling State
        this.state = {
            skinTone: '#704225',
            hairStyle: 'fade', // fade, afro, dreads, cornrows, buzz
            hairColor: '#111111',
            topType: 'hoodie', // hoodie, tshirt, agbada, suit
            topColor: '#059669', // emerald default
            bottomType: 'jeans_blue', // jeans_blue, jeans_black, sweatpants, chinos, white_trouser
            bottomColor: '#1d4ed8', // denim blue
            shoeType: 'sneakers', // sneakers, jordans, loafers, slides
            shoeColor: '#ffffff',
            accessory: 'none', // none, sunglasses, chain, fila_cap
            rotationAngle: 0 // 0 = front, 45 = 3/4 front, 90 = side, 180 = back, etc.
        };

        this.isDragging = false;
        this.prevMouseX = 0;
        this.dragDistance = 0;

        if (this.container) {
            this.init();
        }
    }

    init() {
        this.container.innerHTML = '';
        this.container.classList.add('select-none');

        // Create main wrapper
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'w-full h-full flex flex-col items-center justify-center relative overflow-hidden';
        this.wrapper.style.cursor = 'grab';

        // SVG Render Canvas
        this.svgContainer = document.createElement('div');
        this.svgContainer.className = 'w-full h-full flex items-center justify-center relative transition-transform duration-300';
        this.wrapper.appendChild(this.svgContainer);

        this.container.appendChild(this.wrapper);

        // Inject Stylesheet for smooth organic animations once
        Avatar3DStudio.injectGlobalStyles();

        // Setup Drag Rotation
        this.setupDragRotation();

        // Render Initial Character
        this.render();

        window.addEventListener('resize', () => this.resize());
    }

    static injectGlobalStyles() {
        if (document.getElementById('avatar-engine-styles')) return;
        const style = document.createElement('style');
        style.id = 'avatar-engine-styles';
        style.textContent = `
            @keyframes avatarBreathe {
                0%, 100% { transform: translateY(0px) scale(1, 1); }
                50% { transform: translateY(-3px) scale(1.006, 1.012); }
            }
            @keyframes avatarBlink {
                0%, 94%, 98%, 100% { transform: scaleY(1); }
                96% { transform: scaleY(0.08); }
            }
            @keyframes avatarHeadSway {
                0%, 100% { transform: rotate(0deg); }
                50% { transform: rotate(0.8deg); }
            }
            @keyframes shadowPulse {
                0%, 100% { transform: scale(1); opacity: 0.28; }
                50% { transform: scale(0.97); opacity: 0.22; }
            }
            .avatar-animated-body {
                animation: avatarBreathe 3.6s ease-in-out infinite;
                transform-origin: center 88%;
            }
            .avatar-animated-eyes {
                animation: avatarBlink 4.2s infinite;
                transform-origin: 200px 145px;
            }
            .avatar-animated-head {
                animation: avatarHeadSway 4.8s ease-in-out infinite;
                transform-origin: 200px 185px;
            }
            .avatar-shadow {
                animation: shadowPulse 3.6s ease-in-out infinite;
                transform-origin: center center;
            }
        `;
        document.head.appendChild(style);
    }

    setupDragRotation() {
        const el = this.wrapper;

        const onStart = (clientX) => {
            this.isDragging = true;
            this.prevMouseX = clientX;
            this.dragDistance = 0;
            el.style.cursor = 'grabbing';
        };

        const onMove = (clientX) => {
            if (!this.isDragging) return;
            const delta = clientX - this.prevMouseX;
            this.prevMouseX = clientX;
            this.dragDistance += delta;

            // Rotate angle based on drag
            this.state.rotationAngle = (this.state.rotationAngle - delta * 0.85 + 360) % 360;
            this.render();
        };

        const onEnd = () => {
            if (!this.isDragging) return;
            this.isDragging = false;
            el.style.cursor = 'grab';

            // Snap gently to nearest cardinal angle (0 front, 45 3/4, 90 side, 180 back, 270 side, 315 3/4)
            const snapPoints = [0, 45, 90, 180, 270, 315, 360];
            let closest = snapPoints[0];
            let minDiff = 360;
            snapPoints.forEach(p => {
                const diff = Math.abs(this.state.rotationAngle - p);
                if (diff < minDiff) {
                    minDiff = diff;
                    closest = p;
                }
            });
            this.state.rotationAngle = closest % 360;
            this.render();
        };

        el.addEventListener('mousedown', (e) => onStart(e.clientX));
        window.addEventListener('mousemove', (e) => onMove(e.clientX));
        window.addEventListener('mouseup', onEnd);

        el.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) onStart(e.touches[0].clientX);
        });
        window.addEventListener('touchmove', (e) => {
            if (e.touches.length === 1) onMove(e.touches[0].clientX);
        });
        window.addEventListener('touchend', onEnd);
    }

    turnAround() {
        // Toggle cleanly between 0 (Front) and 180 (Back)
        this.state.rotationAngle = (this.state.rotationAngle >= 90 && this.state.rotationAngle <= 270) ? 0 : 180;
        this.render();
    }

    // --- COLOR PALETTE HELPERS ---
    getSkinColors(hex) {
        const palettes = {
            '#2b1d0c': { base: '#2b1d0c', shadow: '#1a1106', highlight: '#422c15', blush: '#3d2511' },
            '#3d2314': { base: '#3d2314', shadow: '#28160b', highlight: '#55321d', blush: '#512a14' },
            '#593822': { base: '#593822', shadow: '#3c2415', highlight: '#774d31', blush: '#6c4025' },
            '#704225': { base: '#704225', shadow: '#4e2d18', highlight: '#8f5632', blush: '#864c29' },
            '#8d5524': { base: '#8d5524', shadow: '#653b16', highlight: '#b16d31', blush: '#a35f26' },
            '#c68642': { base: '#c68642', shadow: '#96612b', highlight: '#dba25f', blush: '#cb8b47' }
        };
        return palettes[hex] || palettes['#704225'];
    }

    getBottomColors(type) {
        const p = {
            'jeans_blue': { base: '#1d4ed8', dark: '#173da8', light: '#3b82f6', stitch: '#f59e0b', belt: '#1e293b' },
            'jeans_black': { base: '#0f172a', dark: '#020617', light: '#1e293b', stitch: '#334155', belt: '#334155' },
            'sweatpants': { base: '#64748b', dark: '#475569', light: '#94a3b8', stitch: '#ffffff', belt: '#475569' },
            'chinos': { base: '#b45309', dark: '#92400e', light: '#d97706', stitch: '#78350f', belt: '#78350f' },
            'white_trouser': { base: '#f8fafc', dark: '#e2e8f0', light: '#ffffff', stitch: '#cbd5e1', belt: '#94a3b8' }
        };
        return p[type] || p['jeans_blue'];
    }

    getTopColors(type) {
        const p = {
            'hoodie': { base: '#059669', dark: '#047857', light: '#10b981', trim: '#065f46' },
            'tshirt': { base: '#2563eb', dark: '#1d4ed8', light: '#3b82f6', trim: '#1e40af' },
            'agbada': { base: '#ffffff', dark: '#f1f5f9', light: '#ffffff', trim: '#f59e0b', gold: '#fbbf24' },
            'suit': { base: '#0f172a', dark: '#020617', light: '#1e293b', tie: '#be123c', shirt: '#ffffff' }
        };
        return p[type] || p['hoodie'];
    }

    // --- MAIN RENDER PIPELINE ---
    render() {
        if (!this.svgContainer) return;
        const svgContent = this.generateSVG();
        this.svgContainer.innerHTML = svgContent;
    }

    generateSVG() {
        const s = this.state;
        const skin = this.getSkinColors(s.skinTone);
        const pants = this.getBottomColors(s.bottomType);
        const top = this.getTopColors(s.topType);

        // Normalize view angle: is it back or front?
        const isBack = (s.rotationAngle > 90 && s.rotationAngle < 270);
        const isProfile = (s.rotationAngle >= 75 && s.rotationAngle <= 105) || (s.rotationAngle >= 255 && s.rotationAngle <= 285);
        const isThreeQuarter = (s.rotationAngle > 20 && s.rotationAngle < 75) || (s.rotationAngle > 285 && s.rotationAngle < 340);

        let transformFlip = (s.rotationAngle > 180) ? 'scale(-1, 1) translate(-400, 0)' : '';

        return `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 520" class="w-full h-full max-h-[440px] drop-shadow-sm transition-all duration-300">
            <defs>
                <!-- Soft Glow & Gradient Shaders -->
                <radialGradient id="pedestalGlow" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#e2e8f0" stop-opacity="0.9"/>
                    <stop offset="70%" stop-color="#f1f5f9" stop-opacity="0.4"/>
                    <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
                </radialGradient>
                <linearGradient id="goldChainGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#fde047"/>
                    <stop offset="50%" stop-color="#d97706"/>
                    <stop offset="100%" stop-color="#f59e0b"/>
                </linearGradient>
                <linearGradient id="shadesGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#0f172a" stop-opacity="0.95"/>
                    <stop offset="100%" stop-color="#334155" stop-opacity="0.8"/>
                </linearGradient>
                <linearGradient id="denimWash" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="${pants.light}"/>
                    <stop offset="50%" stop-color="${pants.base}"/>
                    <stop offset="100%" stop-color="${pants.dark}"/>
                </linearGradient>
            </defs>

            <!-- 1. PEDESTAL & AMBIENT CONTACT SHADOW -->
            <ellipse cx="200" cy="495" rx="110" ry="18" fill="url(#pedestalGlow)"/>
            <ellipse class="avatar-shadow" cx="200" cy="492" rx="75" ry="11" fill="#0f172a"/>

            <g transform="${transformFlip}">
                ${isBack ? this.renderBackView(s, skin, pants, top) : this.renderFrontView(s, skin, pants, top, isThreeQuarter, isProfile)}
            </g>
        </svg>
        `;
    }

    // ==========================================
    // FRONT & 3/4 VIEW RENDERING
    // ==========================================
    renderFrontView(s, skin, pants, top, isThreeQuarter, isProfile) {
        return `
        <!-- CHARACTER BODY CONTAINER (Breathing Animation) -->
        <g class="avatar-animated-body">

            <!-- 2. FEET & SNEAKERS / KICKS -->
            <g id="avatarShoes">
                ${this.renderFrontShoes(s, skin)}
            </g>

            <!-- 3. LEGS & JEANS / TROUSERS -->
            <g id="avatarBottoms">
                ${this.renderFrontBottoms(s, pants)}
            </g>

            <!-- 4. TORSO & TOPS (HOODIE / TEE / AGBADA / SUIT) -->
            <g id="avatarTop">
                ${this.renderFrontTop(s, skin, top)}
            </g>

            <!-- 5. BLING & ACCESSORIES: CHAIN -->
            ${s.accessory === 'chain' ? this.renderChain() : ''}

            <!-- 6. HEAD, FACE & EXPRESSION -->
            <g class="avatar-animated-head">
                <!-- Neck -->
                <path d="M 186 195 L 214 195 L 216 230 L 184 230 Z" fill="${skin.shadow}"/>

                <!-- Head Contour & Ears -->
                <ellipse cx="145" cy="148" rx="10" ry="16" fill="${skin.base}"/>
                <ellipse cx="145" cy="148" rx="6" ry="10" fill="${skin.shadow}"/>
                <ellipse cx="255" cy="148" rx="10" ry="16" fill="${skin.base}"/>
                <ellipse cx="255" cy="148" rx="6" ry="10" fill="${skin.shadow}"/>

                <!-- Main Face Shape -->
                <path d="M 152 140 C 152 85, 248 85, 248 140 C 248 185, 230 205, 200 208 C 170 205, 152 185, 152 140 Z" fill="${skin.base}"/>
                <!-- Soft jawline contour -->
                <path d="M 170 200 Q 200 210 230 200" stroke="${skin.shadow}" stroke-width="2.5" fill="none" stroke-linecap="round"/>

                <!-- EYES & BLINK ANIMATION -->
                <g class="avatar-animated-eyes">
                    <!-- Left Eye -->
                    <ellipse cx="178" cy="144" rx="13" ry="11" fill="#ffffff"/>
                    <circle cx="179" cy="144" r="7.5" fill="#1c1917"/>
                    <circle cx="180" cy="143" r="5" fill="#44281d"/>
                    <circle cx="181.5" cy="141.5" r="2.2" fill="#ffffff"/> <!-- Specular shine -->

                    <!-- Right Eye -->
                    <ellipse cx="222" cy="144" rx="13" ry="11" fill="#ffffff"/>
                    <circle cx="221" cy="144" r="7.5" fill="#1c1917"/>
                    <circle cx="220" cy="143" r="5" fill="#44281d"/>
                    <circle cx="222.5" cy="141.5" r="2.2" fill="#ffffff"/>
                </g>

                <!-- Eyebrows -->
                <path d="M 166 128 Q 180 123 192 128" stroke="#111111" stroke-width="4.5" stroke-linecap="round" fill="none"/>
                <path d="M 208 128 Q 220 123 234 128" stroke="#111111" stroke-width="4.5" stroke-linecap="round" fill="none"/>

                <!-- Nose -->
                <path d="M 197 152 Q 200 162 195 167 Q 200 170 205 167" stroke="${skin.shadow}" stroke-width="2.5" stroke-linecap="round" fill="none"/>

                <!-- Smile / Confident Smirk -->
                <path d="M 183 182 Q 200 195 217 182" stroke="#2b1407" stroke-width="3.5" stroke-linecap="round" fill="none"/>
                <path d="M 189 184 Q 200 191 211 184" fill="#ffffff" opacity="0.9"/> <!-- Teeth glint -->

                <!-- 7. HAIRSTYLE -->
                ${this.renderHairstyle(s)}

                <!-- 8. ACCESSORIES: SUNGLASSES & FILA CAP -->
                ${s.accessory === 'sunglasses' ? this.renderSunglasses() : ''}
                ${s.accessory === 'fila_cap' ? this.renderFilaCap() : ''}
            </g>
        </g>
        `;
    }

    // ==========================================
    // BACK VIEW RENDERING (180° REVERSE INSPECTION)
    // ==========================================
    renderBackView(s, skin, pants, top) {
        return `
        <g class="avatar-animated-body">
            <!-- Back of Shoes -->
            <g id="avatarShoesBack">
                <!-- Left Shoe Back -->
                <rect x="146" y="468" width="34" height="20" rx="6" fill="${s.shoeType === 'jordans' ? '#be123c' : '#ffffff'}"/>
                <rect x="144" y="484" width="38" height="6" rx="2" fill="#e2e8f0"/>
                <!-- Right Shoe Back -->
                <rect x="220" y="468" width="34" height="20" rx="6" fill="${s.shoeType === 'jordans' ? '#be123c' : '#ffffff'}"/>
                <rect x="218" y="484" width="38" height="6" rx="2" fill="#e2e8f0"/>
            </g>

            <!-- Back of Jeans (Pockets & Yoke Seam) -->
            <g id="avatarBottomsBack">
                <path d="M 152 300 L 248 300 L 254 468 L 222 470 L 202 360 L 178 470 L 146 468 Z" fill="url(#denimWash)"/>
                <!-- Belt loop & waistband -->
                <rect x="150" y="296" width="100" height="12" fill="${pants.dark}"/>
                <rect x="198" y="294" width="6" height="16" fill="${pants.base}"/>
                <!-- Back Pockets -->
                <path d="M 160 318 L 184 318 L 180 344 L 172 349 L 164 344 Z" fill="${pants.dark}" stroke="${pants.stitch}" stroke-width="1.8"/>
                <path d="M 216 318 L 240 318 L 236 344 L 228 349 L 220 344 Z" fill="${pants.dark}" stroke="${pants.stitch}" stroke-width="1.8"/>
            </g>

            <!-- Back of Top (Hood hanging down / Agbada broad back) -->
            <g id="avatarTopBack">
                ${s.topType === 'agbada' ? `
                    <path d="M 108 220 L 292 220 L 285 385 L 115 385 Z" fill="${top.base}"/>
                    <path d="M 180 220 L 220 220 L 215 310 L 185 310 Z" fill="${top.dark}" stroke="${top.trim}" stroke-width="2"/>
                ` : s.topType === 'suit' ? `
                    <path d="M 138 215 L 262 215 L 256 325 L 144 325 Z" fill="${top.base}"/>
                    <!-- Suit center vent line -->
                    <line x1="200" y1="260" x2="200" y2="325" stroke="${top.dark}" stroke-width="2.5"/>
                ` : `
                    <!-- Hoodie / Tee Back -->
                    <path d="M 138 215 L 262 215 L 254 315 L 146 315 Z" fill="${top.base}"/>
                    <!-- Dropped Hood on Back -->
                    <path d="M 170 215 C 170 248, 230 248, 230 215 Z" fill="${top.dark}" stroke="${top.trim}" stroke-width="2"/>
                `}
            </g>

            <!-- Back of Head & Neck Taper Fade -->
            <g class="avatar-animated-head">
                <rect x="186" y="190" width="28" height="35" fill="${skin.shadow}"/>
                <!-- Clean Barber Hairline Taper / Edge-up at Nape -->
                <path d="M 186 194 Q 200 198 214 194" stroke="#111111" stroke-width="3" fill="none"/>

                <!-- Back of Head Base -->
                <ellipse cx="200" cy="144" rx="46" ry="54" fill="${s.hairColor || '#111111'}"/>

                <!-- Hair Back Silhouette -->
                ${s.hairStyle === 'afro' ? `
                    <circle cx="200" cy="136" r="62" fill="${s.hairColor || '#111111'}"/>
                ` : s.hairStyle === 'dreads' ? `
                    <g fill="${s.hairColor || '#111111'}">
                        <path d="M 160 140 Q 150 190 156 225 L 166 225 Q 164 185 172 140 Z"/>
                        <path d="M 175 140 Q 170 200 176 235 L 186 235 Q 182 190 187 140 Z"/>
                        <path d="M 215 140 Q 220 200 214 235 L 224 235 Q 226 190 223 140 Z"/>
                        <path d="M 228 140 Q 238 190 234 225 L 244 225 Q 242 185 236 140 Z"/>
                    </g>
                ` : ''}
            </g>
        </g>
        `;
    }

    // ==========================================
    // DETAIL PART GENERATORS
    // ==========================================

    renderFrontShoes(s, skin) {
        if (s.shoeType === 'jordans') {
            // High-Top Air Jordans (Chicago Red, Black & White)
            return `
            <!-- Left Jordan -->
            <g id="leftJordan">
                <path d="M 144 460 L 168 454 L 175 464 L 180 482 L 138 482 L 138 472 Z" fill="#be123c"/> <!-- Red collar -->
                <path d="M 152 466 L 172 466 L 178 482 L 150 482 Z" fill="#ffffff"/> <!-- White quarter -->
                <path d="M 155 474 Q 172 470 180 475" stroke="#111111" stroke-width="4.5" stroke-linecap="round"/> <!-- Nike Swoosh -->
                <rect x="134" y="482" width="48" height="8" rx="2" fill="#ffffff"/> <!-- White Midsole -->
                <rect x="134" y="488" width="48" height="3" rx="1.5" fill="#be123c"/> <!-- Red Outsole -->
            </g>
            <!-- Right Jordan -->
            <g id="rightJordan">
                <path d="M 256 460 L 232 454 L 225 464 L 220 482 L 262 482 L 262 472 Z" fill="#be123c"/>
                <path d="M 248 466 L 228 466 L 222 482 L 250 482 Z" fill="#ffffff"/>
                <path d="M 245 474 Q 228 470 220 475" stroke="#111111" stroke-width="4.5" stroke-linecap="round"/>
                <rect x="218" y="482" width="48" height="8" rx="2" fill="#ffffff"/>
                <rect x="218" y="488" width="48" height="3" rx="1.5" fill="#be123c"/>
            </g>
            `;
        } else if (s.shoeType === 'loafers') {
            // Italian Black Leather Loafers with Gold Horsebit
            return `
            <!-- Left Loafer -->
            <path d="M 142 468 C 142 462, 174 462, 178 476 L 180 486 L 138 486 Z" fill="#18181b"/>
            <rect x="150" y="472" width="16" height="3.5" rx="1.5" fill="#f59e0b"/> <!-- Gold Horsebit -->
            <rect x="136" y="485" width="46" height="5" rx="1.5" fill="#09090b"/>
            <!-- Right Loafer -->
            <path d="M 258 468 C 258 462, 226 462, 222 476 L 220 486 L 262 486 Z" fill="#18181b"/>
            <rect x="234" y="472" width="16" height="3.5" rx="1.5" fill="#f59e0b"/>
            <rect x="218" y="485" width="46" height="5" rx="1.5" fill="#09090b"/>
            `;
        } else if (s.shoeType === 'slides') {
            // Nigerian Casual Slides with Bare Feet & Toes!
            return `
            <!-- Left Bare Foot & Toes -->
            <path d="M 148 464 L 170 464 L 176 484 L 144 484 Z" fill="${skin.base}"/>
            <!-- Toes -->
            <ellipse cx="150" cy="483" rx="4" ry="3" fill="${skin.highlight}"/>
            <ellipse cx="158" cy="483" rx="3.5" ry="3" fill="${skin.highlight}"/>
            <ellipse cx="165" cy="483" rx="3" ry="2.8" fill="${skin.highlight}"/>
            <ellipse cx="172" cy="483" rx="2.8" ry="2.5" fill="${skin.highlight}"/>
            <!-- Slide Sole -->
            <rect x="140" y="485" width="40" height="6" rx="3" fill="#0f172a"/>
            <!-- Slide Upper Strap -->
            <rect x="146" y="468" width="28" height="10" rx="3" fill="#1e293b"/>
            <rect x="154" y="471" width="12" height="4" fill="#ffffff" rx="1"/> <!-- Logo bar -->

            <!-- Right Bare Foot & Toes -->
            <path d="M 252 464 L 230 464 L 224 484 L 256 484 Z" fill="${skin.base}"/>
            <!-- Toes -->
            <ellipse cx="250" cy="483" rx="4" ry="3" fill="${skin.highlight}"/>
            <ellipse cx="242" cy="483" rx="3.5" ry="3" fill="${skin.highlight}"/>
            <ellipse cx="235" cy="483" rx="3" ry="2.8" fill="${skin.highlight}"/>
            <ellipse cx="228" cy="483" rx="2.8" ry="2.5" fill="${skin.highlight}"/>
            <!-- Slide Sole -->
            <rect x="220" y="485" width="40" height="6" rx="3" fill="#0f172a"/>
            <!-- Slide Upper Strap -->
            <rect x="226" y="468" width="28" height="10" rx="3" fill="#1e293b"/>
            <rect x="234" y="471" width="12" height="4" fill="#ffffff" rx="1"/>
            `;
        } else {
            // Crisp White Air Force 1s (Chunky Sole, Laces & Toe Perforations)
            return `
            <!-- Left AF1 -->
            <path d="M 144 464 L 168 460 L 176 472 L 182 483 L 136 483 Z" fill="#ffffff"/>
            <path d="M 152 466 L 164 466" stroke="#cbd5e1" stroke-width="2"/> <!-- Laces -->
            <path d="M 154 470 L 166 470" stroke="#cbd5e1" stroke-width="2"/>
            <rect x="134" y="482" width="50" height="9" rx="3" fill="#ffffff"/>
            <line x1="138" y1="486" x2="180" y2="486" stroke="#e2e8f0" stroke-width="1.5"/> <!-- Air Line -->
            <!-- Right AF1 -->
            <path d="M 256 464 L 232 460 L 224 472 L 218 483 L 264 483 Z" fill="#ffffff"/>
            <path d="M 248 466 L 236 466" stroke="#cbd5e1" stroke-width="2"/>
            <path d="M 246 470 L 234 470" stroke="#cbd5e1" stroke-width="2"/>
            <rect x="216" y="482" width="50" height="9" rx="3" fill="#ffffff"/>
            <line x1="220" y1="486" x2="262" y2="486" stroke="#e2e8f0" stroke-width="1.5"/>
            `;
        }
    }

    renderFrontBottoms(s, pants) {
        return `
        <!-- Jeans Silhouette with Inseam & Natural Stacking Creases -->
        <path d="M 152 295 L 248 295 L 254 464 L 224 466 L 200 345 L 176 466 L 146 464 Z" fill="url(#denimWash)"/>

        <!-- Inseam Center Stitching -->
        <path d="M 200 305 L 200 345" stroke="${pants.stitch}" stroke-width="2" stroke-linecap="round"/>

        <!-- Front Denim Pockets -->
        <path d="M 154 308 Q 172 312 178 300" stroke="${pants.stitch}" stroke-width="2" fill="none"/>
        <path d="M 246 308 Q 228 312 222 300" stroke="${pants.stitch}" stroke-width="2" fill="none"/>

        <!-- Belt Loops & Belt with Silver Buckle -->
        <rect x="150" y="292" width="100" height="12" fill="${pants.belt}" rx="1"/>
        <rect x="194" y="290" width="12" height="16" fill="#e2e8f0" rx="2" stroke="#64748b" stroke-width="1.5"/>
        <line x1="200" y1="292" x2="200" y2="304" stroke="#0f172a" stroke-width="2"/>

        <!-- Natural Denim Stacking Creases at Ankles -->
        <path d="M 148 448 Q 160 452 172 449" stroke="${pants.dark}" stroke-width="2" fill="none"/>
        <path d="M 150 456 Q 160 460 170 457" stroke="${pants.dark}" stroke-width="2" fill="none"/>
        <path d="M 228 448 Q 240 452 252 449" stroke="${pants.dark}" stroke-width="2" fill="none"/>
        <path d="M 230 456 Q 240 460 250 457" stroke="${pants.dark}" stroke-width="2" fill="none"/>
        `;
    }

    renderFrontTop(s, skin, top) {
        if (s.topType === 'agbada') {
            // Royal Nigerian Ceremonial Agbada (Flowing Cape & Golden Embroidery)
            return `
            <!-- Flowing Broad Shoulder Cape -->
            <path d="M 100 215 C 100 215, 140 210, 200 210 C 260 210, 300 215, 300 215 L 292 390 L 108 390 Z" fill="${top.base}"/>

            <!-- Rich Golden Neckline Embroidery Plate -->
            <path d="M 175 210 L 225 210 L 220 310 L 200 325 L 180 310 Z" fill="${top.gold}" stroke="#b45309" stroke-width="2"/>
            <!-- Intricate Geometric Motifs -->
            <circle cx="200" cy="245" r="10" fill="none" stroke="#78350f" stroke-width="2.5"/>
            <polygon points="200,265 210,285 190,285" fill="none" stroke="#78350f" stroke-width="2.5"/>
            <line x1="200" y1="220" x2="200" y2="305" stroke="#78350f" stroke-width="2" stroke-dasharray="3,3"/>
            `;
        } else if (s.topType === 'suit') {
            // Executive Corporate Navy Suit & Red Tie
            return `
            <!-- Suit Body -->
            <path d="M 134 210 L 266 210 L 258 320 L 142 320 Z" fill="${top.base}"/>

            <!-- White Inner Shirt & Collar -->
            <polygon points="186,210 214,210 200,260" fill="#ffffff"/>
            <polygon points="186,210 196,225 188,230" fill="#ffffff" stroke="#e2e8f0"/>
            <polygon points="214,210 204,225 212,230" fill="#ffffff" stroke="#e2e8f0"/>

            <!-- Crimson Red Necktie -->
            <polygon points="196,218 204,218 205,226 195,226" fill="#be123c"/>
            <polygon points="195,226 205,226 207,290 200,298 193,290" fill="#be123c"/>
            <line x1="196" y1="250" x2="204" y2="250" stroke="#f59e0b" stroke-width="2"/> <!-- Gold Tie Clip -->

            <!-- Suit Lapels -->
            <path d="M 144 210 L 185 275 L 175 275 L 140 235 Z" fill="${top.light}"/>
            <path d="M 256 210 L 215 275 L 225 275 L 260 235 Z" fill="${top.light}"/>

            <!-- Pocket Square -->
            <line x1="150" y1="260" x2="168" y2="260" stroke="#ffffff" stroke-width="3"/>

            <!-- Arms / Sleeves -->
            <path d="M 134 210 L 120 280 L 140 285 L 146 225 Z" fill="${top.base}"/>
            <path d="M 266 210 L 280 280 L 260 285 L 254 225 Z" fill="${top.base}"/>
            `;
        } else if (s.topType === 'tshirt') {
            // Casual Streetwear T-Shirt (Short Sleeves Showing Bare Toned Arms!)
            return `
            <!-- Toned Bare Arms -->
            <path d="M 135 220 L 118 310 L 138 314 L 148 235 Z" fill="${skin.base}"/>
            <path d="M 265 220 L 282 310 L 262 314 L 252 235 Z" fill="${skin.base}"/>

            <!-- T-Shirt Torso -->
            <path d="M 138 210 L 262 210 L 254 315 L 146 315 Z" fill="${top.base}"/>
            <!-- Ribbed Crewneck Collar -->
            <path d="M 182 210 C 182 225, 218 225, 218 210 Z" fill="none" stroke="${top.dark}" stroke-width="4.5"/>
            <!-- Short Sleeves -->
            <path d="M 138 210 L 124 250 L 144 254 L 148 220 Z" fill="${top.base}"/>
            <path d="M 262 210 L 276 250 L 256 254 L 252 220 Z" fill="${top.base}"/>
            `;
        } else {
            // Modern Tech Bro Oversized Hoodie with Pouch Pocket & Drawstrings
            return `
            <!-- Sleeves -->
            <path d="M 135 210 L 115 315 L 138 320 L 148 230 Z" fill="${top.dark}"/>
            <rect x="115" y="312" width="23" height="10" rx="3" fill="${top.trim}"/> <!-- Cuffs -->

            <path d="M 265 210 L 285 315 L 262 320 L 252 230 Z" fill="${top.dark}"/>
            <rect x="262" y="312" width="23" height="10" rx="3" fill="${top.trim}"/>

            <!-- Hoodie Torso -->
            <path d="M 136 210 L 264 210 L 255 318 L 145 318 Z" fill="${top.base}"/>
            <rect x="145" y="310" width="110" height="12" rx="4" fill="${top.trim}"/> <!-- Waistband -->

            <!-- Kangaroo Pouch Pocket -->
            <path d="M 165 272 L 235 272 L 244 308 L 156 308 Z" fill="${top.dark}" stroke="${top.trim}" stroke-width="2"/>

            <!-- Hood Collar & Hanging Drawstrings -->
            <path d="M 174 210 C 174 230, 226 230, 226 210 Z" fill="${top.dark}"/>
            <!-- Drawstrings with Metal Aglets -->
            <path d="M 190 220 L 188 260" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>
            <rect x="186.5" y="258" width="3" height="6" fill="#cbd5e1" rx="1"/>

            <path d="M 210 220 L 212 256" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>
            <rect x="210.5" y="254" width="3" height="6" fill="#cbd5e1" rx="1"/>
            `;
        }
    }

    renderHairstyle(s) {
        const color = s.hairColor || '#111111';

        if (s.hairStyle === 'afro') {
            // Volumetric Natural Afro Crown with Textured Curls
            return `
            <g id="afroCrown" fill="${color}">
                <circle cx="200" cy="115" r="54"/>
                <circle cx="160" cy="120" r="38"/>
                <circle cx="240" cy="120" r="38"/>
                <circle cx="175" cy="85" r="36"/>
                <circle cx="225" cy="85" r="36"/>
                <circle cx="200" cy="72" r="34"/>
                <!-- Hairline texture edge -->
                <path d="M 154 135 Q 200 120 246 135" stroke="${color}" stroke-width="4"/>
            </g>
            `;
        } else if (s.hairStyle === 'dreads') {
            // Styled Hanging Dreadlocks with Gold Rings
            return `
            <g id="dreadlocks" fill="${color}">
                <!-- Base Cap -->
                <ellipse cx="200" cy="112" rx="48" ry="24"/>
                <!-- Flowing Strands -->
                <path d="M 152 120 Q 140 160 148 190 Q 155 190 158 120 Z"/>
                <path d="M 165 115 Q 156 168 164 205 Q 172 205 173 115 Z"/>
                <path d="M 180 112 Q 178 175 184 212 Q 192 212 189 112 Z"/>
                <path d="M 220 112 Q 222 175 216 212 Q 208 212 211 112 Z"/>
                <path d="M 235 115 Q 244 168 236 205 Q 228 205 227 115 Z"/>
                <path d="M 248 120 Q 260 160 252 190 Q 245 190 242 120 Z"/>
                <!-- Gold Dread Cuffs -->
                <rect x="144" y="160" width="8" height="5" fill="#f59e0b" rx="1"/>
                <rect x="248" y="168" width="8" height="5" fill="#f59e0b" rx="1"/>
                <rect x="160" y="180" width="8" height="5" fill="#f59e0b" rx="1"/>
            </g>
            `;
        } else if (s.hairStyle === 'cornrows') {
            // Precision Cornrows Tracks
            return `
            <g id="cornrows" stroke="${color}" fill="none">
                <ellipse cx="200" cy="115" rx="46" ry="28" fill="${color}"/>
                <!-- Precision Braided Ridges -->
                <path d="M 165 130 C 165 100, 185 85, 200 85" stroke-width="6" stroke-linecap="round"/>
                <path d="M 180 130 C 180 95, 195 85, 200 85" stroke-width="6" stroke-linecap="round"/>
                <path d="M 200 130 L 200 85" stroke-width="6" stroke-linecap="round"/>
                <path d="M 220 130 C 220 95, 205 85, 200 85" stroke-width="6" stroke-linecap="round"/>
                <path d="M 235 130 C 235 100, 215 85, 200 85" stroke-width="6" stroke-linecap="round"/>
            </g>
            `;
        } else if (s.hairStyle === 'buzz') {
            // Clean Shaven Buzz
            return `
            <g id="buzzCut">
                <path d="M 152 135 C 152 82, 248 82, 248 135 Z" fill="${color}" opacity="0.95"/>
                <!-- Sharp Barber Lineup -->
                <path d="M 156 135 L 244 135" stroke="${color}" stroke-width="3"/>
            </g>
            `;
        } else {
            // Razor Sharp Low Taper Fade with Clean Temple Gradient
            return `
            <g id="lowFade">
                <!-- Hair Top Volume -->
                <path d="M 152 135 C 150 78, 250 78, 248 135 Q 200 115 152 135 Z" fill="${color}"/>
                <!-- Barber Box Lineup across forehead -->
                <path d="M 154 128 L 246 128" stroke="${color}" stroke-width="5" stroke-linecap="square"/>
                <!-- Temple Fade Taper (gradient shading above ears) -->
                <path d="M 150 142 L 156 128" stroke="${color}" stroke-width="4"/>
                <path d="M 250 142 L 244 128" stroke="${color}" stroke-width="4"/>
            </g>
            `;
        }
    }

    renderSunglasses() {
        return `
        <!-- VIP Gold Aviator Sunglasses -->
        <g id="aviatorShades">
            <!-- Top Brow Bar & Bridge -->
            <path d="M 160 132 L 240 132" stroke="#d97706" stroke-width="3" stroke-linecap="round"/>
            <path d="M 194 140 L 206 140" stroke="#d97706" stroke-width="3" stroke-linecap="round"/>

            <!-- Left Teardrop Lens -->
            <path d="M 164 134 C 164 134, 192 134, 192 144 C 192 158, 175 162, 168 156 C 162 150, 164 134, 164 134 Z" fill="url(#shadesGrad)" stroke="#f59e0b" stroke-width="2.5"/>
            <!-- Lens Glass Sheen Reflection -->
            <line x1="168" y1="138" x2="178" y2="154" stroke="#ffffff" stroke-width="2" opacity="0.45" stroke-linecap="round"/>

            <!-- Right Teardrop Lens -->
            <path d="M 236 134 C 236 134, 208 134, 208 144 C 208 158, 225 162, 232 156 C 238 150, 236 134, 236 134 Z" fill="url(#shadesGrad)" stroke="#f59e0b" stroke-width="2.5"/>
            <!-- Lens Glass Sheen Reflection -->
            <line x1="222" y1="138" x2="232" y2="154" stroke="#ffffff" stroke-width="2" opacity="0.45" stroke-linecap="round"/>
        </g>
        `;
    }

    renderChain() {
        return `
        <!-- Heavy Metallic Gold Cuban Link Chain -->
        <path d="M 172 215 C 172 258, 228 258, 228 215" fill="none" stroke="url(#goldChainGrad)" stroke-width="7" stroke-linecap="round" stroke-dasharray="7,3" filter="drop-shadow(0px 2px 2px rgba(0,0,0,0.3))"/>
        `;
    }

    renderFilaCap() {
        return `
        <!-- Traditional Nigerian Royal Fila Cap -->
        <g id="filaCap">
            <path d="M 152 110 L 248 100 L 240 70 L 160 82 Z" fill="#991b1b" stroke="#7f1d1d" stroke-width="2"/>
            <!-- Fold / Crease -->
            <path d="M 160 82 Q 200 95 240 70" stroke="#f59e0b" stroke-width="2.5" fill="none"/>
            <!-- Gold Embroidery Motif -->
            <circle cx="198" cy="94" r="5" fill="#f59e0b"/>
        </g>
        `;
    }

    // --- INTERACTION API (100% Backward Compatible) ---
    setSkin(hex) {
        this.state.skinTone = hex;
        this.render();
    }

    setHair(style, colorHex = null) {
        this.state.hairStyle = style;
        if (colorHex) this.state.hairColor = colorHex;
        this.render();
    }

    setTop(type, colorHex = null) {
        this.state.topType = type;
        if (colorHex) this.state.topColor = colorHex;
        this.render();
    }

    setBottom(type, colorHex = null) {
        this.state.bottomType = type;
        if (colorHex) this.state.bottomColor = colorHex;
        this.render();
    }

    setShoes(type, colorHex = null) {
        this.state.shoeType = type;
        if (colorHex) this.state.shoeColor = colorHex;
        this.render();
    }

    setAccessory(type) {
        this.state.accessory = type;
        this.render();
    }

    getConfig() {
        return Object.assign({}, this.state);
    }

    loadConfig(cfg) {
        if (!cfg) return;
        Object.assign(this.state, cfg);
        this.render();
    }

    resize() {
        // SVG scales automatically with viewBox="0 0 400 520"
        this.render();
    }

    // Helper: Export SVG String
    getSVGString() {
        return this.generateSVG();
    }

    // Helper: Export as Data URL for billboard or profile pictures
    getDataURL(callback) {
        const svgStr = this.generateSVG();
        const blob = new Blob([svgStr], { type: 'image/svg+xml;charset=utf-8' });
        const URL = window.URL || window.webkitURL || window;
        const blobURL = URL.createObjectURL(blob);
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = 400;
            canvas.height = 520;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            URL.revokeObjectURL(blobURL);
            if (callback) callback(canvas.toDataURL('image/png'));
        };
        img.src = blobURL;
    }
}

window.Avatar3DStudio = Avatar3DStudio;
