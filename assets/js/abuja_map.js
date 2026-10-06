/**
 * Abuja Life – Full Interactive Isometric Canvas Map
 * Pure HTML5 Canvas 2D renderer. Zero images, zero HTML overlays.
 * Everything is drawn, clickable, and animated directly on canvas.
 */
const AbujaMap = (() => {
    let canvas, ctx;
    let W, H;
    let camX = 0, camY = 0;
    let scale = 1;
    let isDragging = false, dragStartX, dragStartY, dragCamX, dragCamY;
    let frame = 0;
    let onClickLocation = null;   // callback(locId)
    let onClickCitizen  = null;   // callback(citizen)

    // ── Palette ──────────────────────────────────────────────────────────────
    const C = {
        sky:        '#c8e6fa',
        ground:     '#d4e9a8',
        grass:      '#a8d060',
        road:       '#5a6473',
        roadLine:   '#f5c518',
        pavement:   '#b8c4cc',
        water:      '#3ab8ff',
        waterDeep:  '#1a90d8',
        sand:       '#e8d08a',
        roof1:      '#e05a38',
        roof2:      '#c04080',
        roof3:      '#5060c8',
        roof4:      '#e09030',
        wall1:      '#f0e0c0',
        wall2:      '#d8cbb0',
        wall3:      '#e0d4c0',
        wallGov:    '#dde8f0',
        glass:      '#80c8f0',
        glass2:     '#60a8e0',
        park:       '#68b840',
        parkDark:   '#4a9830',
        tree1:      '#3a9030',
        tree2:      '#2a7820',
        pinBg:      '#1a1f2e',
        pinText:    '#ffffff',
        pinGreen:   '#10b981',
        pinBlue:    '#3b82f6',
        pinYellow:  '#f59e0b',
        pinRed:     '#ef4444',
        pinPurple:  '#8b5cf6',
    };

    // ── Isometric helpers ────────────────────────────────────────────────────
    // Tile size in world units
    const TW = 72, TH = 36;  // tile width, tile half-height

    function isoToScreen(gx, gy) {
        // gx = column, gy = row
        const sx = (gx - gy) * (TW / 2);
        const sy = (gx + gy) * (TH / 2);
        return { x: sx, y: sy };
    }

    function drawIsoTile(gx, gy, topColor, leftColor, rightColor) {
        const { x, y } = isoToScreen(gx, gy);
        // Top face
        ctx.beginPath();
        ctx.moveTo(x, y - TH / 2);
        ctx.lineTo(x + TW / 2, y);
        ctx.lineTo(x, y + TH / 2);
        ctx.lineTo(x - TW / 2, y);
        ctx.closePath();
        ctx.fillStyle = topColor;
        ctx.fill();
        ctx.strokeStyle = 'rgba(0,0,0,0.08)';
        ctx.lineWidth = 0.5;
        ctx.stroke();
    }

    function drawIsoBox(gx, gy, gw, gd, gh, wallFront, wallSide, roofColor) {
        // gx,gy = grid origin (top-left column), gw=width in tiles, gd=depth, gh=height in px
        const s00 = isoToScreen(gx, gy);
        const s10 = isoToScreen(gx + gw, gy);
        const s01 = isoToScreen(gx, gy + gd);
        const s11 = isoToScreen(gx + gw, gy + gd);

        // Roof
        ctx.beginPath();
        ctx.moveTo(s00.x, s00.y - gh);
        ctx.lineTo(s10.x, s10.y - gh);
        ctx.lineTo(s11.x, s11.y - gh);
        ctx.lineTo(s01.x, s01.y - gh);
        ctx.closePath();
        ctx.fillStyle = roofColor;
        ctx.fill();
        ctx.strokeStyle = 'rgba(0,0,0,0.15)';
        ctx.lineWidth = 0.8;
        ctx.stroke();

        // Right face (gy+d side)
        ctx.beginPath();
        ctx.moveTo(s11.x, s11.y - gh);
        ctx.lineTo(s01.x, s01.y - gh);
        ctx.lineTo(s01.x, s01.y);
        ctx.lineTo(s11.x, s11.y);
        ctx.closePath();
        ctx.fillStyle = wallSide;
        ctx.fill();
        ctx.strokeStyle = 'rgba(0,0,0,0.1)';
        ctx.stroke();

        // Front face (gx+w side)
        ctx.beginPath();
        ctx.moveTo(s10.x, s10.y - gh);
        ctx.lineTo(s11.x, s11.y - gh);
        ctx.lineTo(s11.x, s11.y);
        ctx.lineTo(s10.x, s10.y);
        ctx.closePath();
        ctx.fillStyle = wallFront;
        ctx.fill();
        ctx.strokeStyle = 'rgba(0,0,0,0.1)';
        ctx.stroke();
    }

    function drawWindows(gx, gy, gw, gd, gh, rows, cols) {
        // draw windows on the front (right) face
        const s10 = isoToScreen(gx + gw, gy);
        const s11 = isoToScreen(gx + gw, gy + gd);
        const faceW = Math.abs(s11.x - s10.x);
        const faceH = gh;
        const wW = faceW / (cols * 2 + 1);
        const wH = faceH / (rows * 2 + 1);

        // Skew transform to match iso face
        ctx.save();
        const angle = Math.atan2(s11.y - s10.y, s11.x - s10.x);
        ctx.translate(s10.x, s10.y - gh);
        ctx.transform(1, Math.tan(angle), 0, 1, 0, 0);
        ctx.fillStyle = C.glass;
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                const wx = wW * (2 * c + 1);
                const wy = wH * (2 * r + 1);
                ctx.fillRect(wx, wy, wW * 0.8, wH * 0.8);
            }
        }
        ctx.restore();
    }

    function drawTree(gx, gy) {
        const { x, y } = isoToScreen(gx, gy);
        const bob = Math.sin(frame * 0.03 + gx * 1.3 + gy * 0.7) * 1.5;

        // Trunk
        ctx.fillStyle = '#8B5E3C';
        ctx.fillRect(x - 3, y - 14 + bob, 6, 14);

        // Canopy
        ctx.fillStyle = C.tree2;
        ctx.beginPath();
        ctx.arc(x, y - 22 + bob, 12, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = C.tree1;
        ctx.beginPath();
        ctx.arc(x - 3, y - 26 + bob, 9, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = '#5ab040';
        ctx.beginPath();
        ctx.arc(x + 3, y - 24 + bob, 7, 0, Math.PI * 2);
        ctx.fill();
    }

    function drawRoadH(gx, gy, len) {
        for (let i = 0; i < len; i++) {
            const { x, y } = isoToScreen(gx + i, gy);
            // Tile
            ctx.beginPath();
            ctx.moveTo(x, y - TH / 2);
            ctx.lineTo(x + TW / 2, y);
            ctx.lineTo(x, y + TH / 2);
            ctx.lineTo(x - TW / 2, y);
            ctx.closePath();
            ctx.fillStyle = C.road;
            ctx.fill();
            // Center dashes
            if (i % 2 === 0) {
                ctx.fillStyle = C.roadLine;
                ctx.fillRect(x - 6, y - 1, 12, 2);
            }
        }
    }

    function drawRoadV(gx, gy, len) {
        for (let i = 0; i < len; i++) {
            const { x, y } = isoToScreen(gx, gy + i);
            ctx.beginPath();
            ctx.moveTo(x, y - TH / 2);
            ctx.lineTo(x + TW / 2, y);
            ctx.lineTo(x, y + TH / 2);
            ctx.lineTo(x - TW / 2, y);
            ctx.closePath();
            ctx.fillStyle = C.road;
            ctx.fill();
            if (i % 2 === 0) {
                ctx.save();
                ctx.translate(x, y);
                ctx.rotate(Math.PI / 2);
                ctx.fillStyle = C.roadLine;
                ctx.fillRect(-6, -1, 12, 2);
                ctx.restore();
            }
        }
    }

    function fillIsoRegion(tiles, color) {
        for (const [gx, gy] of tiles) {
            const { x, y } = isoToScreen(gx, gy);
            ctx.beginPath();
            ctx.moveTo(x, y - TH / 2);
            ctx.lineTo(x + TW / 2, y);
            ctx.lineTo(x, y + TH / 2);
            ctx.lineTo(x - TW / 2, y);
            ctx.closePath();
            ctx.fillStyle = color;
            ctx.fill();
        }
    }

    // ── Clickable zones ──────────────────────────────────────────────────────
    const hitZones = [];   // { x, y, r, id, label, color }
    const citizens  = [];  // { x, y, username, label, emoji }

    function registerHit(sx, sy, r, id, label, color) {
        hitZones.push({ x: sx, y: sy, r, id, label, color });
    }

    function drawPin(sx, sy, label, icon, color, bounce) {
        const bY = Math.sin(frame * 0.04 + sx * 0.01 + sy * 0.01) * 4 * bounce;
        const py = sy + bY;

        // Shadow
        ctx.save();
        ctx.globalAlpha = 0.18;
        ctx.fillStyle = '#000';
        ctx.beginPath();
        ctx.ellipse(sx, py + 6, 10, 4, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();

        // Stem
        ctx.strokeStyle = color;
        ctx.lineWidth = 2.5;
        ctx.beginPath();
        ctx.moveTo(sx, py);
        ctx.lineTo(sx, py - 10);
        ctx.stroke();

        // Pin circle
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.arc(sx, py - 22, 14, 0, Math.PI * 2);
        ctx.fill();

        // White inner
        ctx.fillStyle = 'rgba(255,255,255,0.25)';
        ctx.beginPath();
        ctx.arc(sx, py - 22, 11, 0, Math.PI * 2);
        ctx.fill();

        // Icon
        ctx.font = '13px serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(icon, sx, py - 22);

        // Label pill
        const tw = ctx.measureText(label).width + 18;
        const tx = sx - tw / 2;
        const ty = py - 42;

        ctx.fillStyle = C.pinBg;
        roundRect(ctx, tx, ty, tw, 18, 9);
        ctx.fill();

        ctx.fillStyle = '#fff';
        ctx.font = 'bold 9px "Plus Jakarta Sans", sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(label, sx, ty + 9);
    }

    function drawCitizenBadge(c) {
        const bob = Math.sin(frame * 0.03 + c.x * 0.1) * 5;
        const py = c.y + bob;

        // Shadow
        ctx.save();
        ctx.globalAlpha = 0.15;
        ctx.fillStyle = '#000';
        ctx.beginPath();
        ctx.ellipse(c.x, py + 12, 8, 3, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();

        // Avatar circle
        ctx.fillStyle = c.color || '#6366f1';
        ctx.beginPath();
        ctx.arc(c.x, py, 12, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = '#fff';
        ctx.font = '12px serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(c.emoji || '🧑', c.x, py);

        // Username tag
        const label = c.username;
        const tw = ctx.measureText(label).width + 16;
        const tx = c.x - tw / 2;
        const ty = py - 24;

        ctx.fillStyle = '#0f172a';
        roundRect(ctx, tx, ty, tw, 16, 8);
        ctx.fill();

        ctx.strokeStyle = C.pinGreen;
        ctx.lineWidth = 1.5;
        roundRect(ctx, tx, ty, tw, 16, 8);
        ctx.stroke();

        ctx.fillStyle = '#fff';
        ctx.font = 'bold 8.5px monospace';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(label, c.x, ty + 8);
    }

    function roundRect(c2d, x, y, w, h, r) {
        c2d.beginPath();
        c2d.moveTo(x + r, y);
        c2d.lineTo(x + w - r, y);
        c2d.quadraticCurveTo(x + w, y, x + w, y + r);
        c2d.lineTo(x + w, y + h - r);
        c2d.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        c2d.lineTo(x + r, y + h);
        c2d.quadraticCurveTo(x, y + h, x, y + h - r);
        c2d.lineTo(x, y + r);
        c2d.quadraticCurveTo(x, y, x + r, y);
        c2d.closePath();
    }

    // ── Draw the full Abuja city ────────────────────────────────────────────
    function drawCity() {
        hitZones.length = 0;

        // ── Ground base ──
        for (let gx = -2; gx < 28; gx++) {
            for (let gy = -2; gy < 28; gy++) {
                drawIsoTile(gx, gy, C.ground, C.grass, C.grass);
            }
        }

        // ── Jabi Lake ──────────────────────────────────────────────────────
        for (let gx = 0; gx < 5; gx++) {
            for (let gy = 1; gy < 5; gy++) {
                const wave = Math.sin(frame * 0.02 + gx + gy) * 5;
                const blue = `hsl(200, 75%, ${44 + wave}%)`;
                drawIsoTile(gx, gy, blue, C.waterDeep, C.waterDeep);
            }
        }
        // Lake label
        const lakeS = isoToScreen(2, 3);
        ctx.save();
        ctx.globalAlpha = 0.7;
        ctx.fillStyle = '#005fa3';
        ctx.font = 'bold italic 11px "Plus Jakarta Sans",sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('Jabi Lake', lakeS.x, lakeS.y + 4);
        ctx.restore();

        // Sandy lakeside
        for (let gx = 0; gx < 5; gx++) {
            drawIsoTile(gx, 5, C.sand, '#c8b870', '#c8b870');
        }
        for (let gy = 1; gy < 5; gy++) {
            drawIsoTile(5, gy, C.sand, '#c8b870', '#c8b870');
        }

        // ── Roads (Shehu Shagari Way, Airport Road, etc.) ──
        drawRoadH(0, 6, 26);  // Main horizontal highway
        drawRoadH(0, 13, 26); // Second horizontal
        drawRoadH(0, 20, 26); // Third
        drawRoadV(7, 0, 26);  // Main vertical
        drawRoadV(14, 0, 26); // CBD axis
        drawRoadV(21, 0, 26); // Airport road
        // Connector
        drawRoadH(0, 0, 26);

        // ── Parks ──────────────────────────────────────────────────────────
        // Millennium Park
        for (let gx = 15; gx < 20; gx++) {
            for (let gy = 7; gy < 12; gy++) {
                drawIsoTile(gx, gy, C.park, C.parkDark, C.parkDark);
            }
        }
        // Trees in park
        for (let gx = 15; gx < 20; gx += 2) {
            for (let gy = 7; gy < 12; gy += 2) {
                drawTree(gx, gy);
            }
        }
        const mpS = isoToScreen(17, 9);
        ctx.fillStyle = '#1a5c1a';
        ctx.font = 'bold 10px "Plus Jakarta Sans",sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('Millennium Park', mpS.x, mpS.y - 8);

        // ── Government zone (Three Arms) ──────────────────────────────────
        // NNPC / Aso Rock area
        drawIsoBox(22, 7, 3, 3, 70, '#d8e8f8', '#b0c8e8', '#e8f0f8');
        // Government wings
        drawIsoBox(19, 7, 2, 2, 50, '#c8d8e8', '#a0b8d0', '#d8e8f0');
        drawIsoBox(22, 11, 4, 2, 45, '#c0d8e8', '#98b8d0', '#d0e4f0');

        // ── CBD Twin Towers ────────────────────────────────────────────────
        drawIsoBox(15, 14, 2, 2, 110, '#bcc8d8', '#98a8b8', '#d8e4f0'); // Tower A
        drawIsoBox(18, 14, 2, 2, 100, '#b8c4d4', '#94a4b4', '#d4e0ec'); // Tower B
        // Glass reflections
        drawIsoBox(15, 14, 1, 1, 80, C.glass2, '#5090c0', C.glass);
        drawIsoBox(18, 14, 1, 1, 80, C.glass2, '#5090c0', C.glass);

        // ── Wuse Market ────────────────────────────────────────────────────
        drawIsoBox(8, 7, 3, 3, 28, '#e8c070', '#c8a040', C.roof4);
        drawIsoBox(9, 8, 1, 1, 22, '#f0c860', '#d0a030', '#f8d040');

        // ── Banex Plaza / Wuse 2 ───────────────────────────────────────────
        drawIsoBox(8, 1, 3, 2, 45, '#d8d0e8', '#b8a8d0', '#e0d8f0');
        drawIsoBox(10, 2, 2, 1, 30, '#e0d8f8', '#b8b0e0', '#e8e0f8');

        // ── Maitama District (residential cluster) ─────────────────────────
        drawIsoBox(22, 1, 2, 2, 38, C.wall1, C.wall2, '#e06840');
        drawIsoBox(19, 1, 2, 2, 32, C.wall1, C.wall2, '#d04060');
        drawIsoBox(22, 4, 2, 2, 35, C.wall1, C.wall2, '#5068c8');
        drawIsoBox(19, 4, 2, 2, 30, C.wall1, C.wall2, '#e08030');

        // ── National Mosque ────────────────────────────────────────────────
        const mosqueS = isoToScreen(14, 7);
        ctx.fillStyle = '#38809a';
        ctx.beginPath();
        ctx.arc(mosqueS.x, mosqueS.y - 35, 22, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#50b0d0';
        ctx.beginPath();
        ctx.arc(mosqueS.x, mosqueS.y - 35, 18, 0, Math.PI * 2);
        ctx.fill();
        // Dome shine
        ctx.fillStyle = 'rgba(255,255,255,0.25)';
        ctx.beginPath();
        ctx.arc(mosqueS.x - 5, mosqueS.y - 42, 8, 0, Math.PI * 2);
        ctx.fill();
        // Minarets
        ctx.fillStyle = '#38809a';
        ctx.fillRect(mosqueS.x - 30, mosqueS.y - 60, 7, 60);
        ctx.fillRect(mosqueS.x + 23, mosqueS.y - 60, 7, 60);
        ctx.fillStyle = '#e8d080';
        ctx.beginPath();
        ctx.arc(mosqueS.x - 26, mosqueS.y - 62, 5, 0, Math.PI * 2);
        ctx.fill();
        ctx.beginPath();
        ctx.arc(mosqueS.x + 26, mosqueS.y - 62, 5, 0, Math.PI * 2);
        ctx.fill();

        // ── National Hospital ──────────────────────────────────────────────
        drawIsoBox(8, 14, 3, 3, 40, '#f0f0f8', '#d8d8e8', '#e8505a');
        // Red cross on roof
        const hospS = isoToScreen(9, 15);
        ctx.fillStyle = '#e82030';
        ctx.fillRect(hospS.x - 2, hospS.y - 45, 4, 14);
        ctx.fillRect(hospS.x - 7, hospS.y - 39, 14, 4);

        // ── Residential blocks scattered ──────────────────────────────────
        const resBlocks = [
            [1, 7, 2, 2, 24, '#e8c090', '#c8a060', '#d04040'],
            [1, 10, 2, 2, 22, '#e0c890', '#c0a060', '#c85030'],
            [4, 7, 2, 2, 26, '#e8d0a0', '#c8b070', '#4060c0'],
            [4, 10, 2, 2, 24, '#f0e0a8', '#d0c080', '#e08040'],
            [8, 21, 2, 2, 28, '#e8c898', '#c8a868', '#c03050'],
            [12, 21, 2, 2, 26, '#e0c890', '#c0a860', '#5060b8'],
            [16, 21, 2, 2, 30, '#e8d0a8', '#c8b078', '#d06030'],
            [1, 14, 2, 2, 25, '#f0d8b0', '#d0b880', '#40a040'],
            [1, 17, 2, 2, 22, '#e8d0a8', '#c8b078', '#b04040'],
            [4, 17, 2, 2, 24, '#f0d8b0', '#d0b880', '#304090'],
        ];
        for (const b of resBlocks) {
            drawIsoBox(...b);
        }

        // ── Streetlights along road ───────────────────────────────────────
        for (let gx = 0; gx < 26; gx += 4) {
            const s = isoToScreen(gx, 6);
            ctx.fillStyle = '#607080';
            ctx.fillRect(s.x - 1, s.y - 30, 2, 30);
            ctx.fillStyle = '#ffe090';
            ctx.beginPath();
            ctx.arc(s.x, s.y - 30, 4, 0, Math.PI * 2);
            ctx.fill();
            // Glow
            ctx.save();
            ctx.globalAlpha = 0.15;
            const grd = ctx.createRadialGradient(s.x, s.y - 30, 0, s.x, s.y - 30, 20);
            grd.addColorStop(0, '#ffe090');
            grd.addColorStop(1, 'transparent');
            ctx.fillStyle = grd;
            ctx.beginPath();
            ctx.arc(s.x, s.y - 30, 20, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }

        // ── Scattered trees ───────────────────────────────────────────────
        const treePlots = [
            [6,1],[6,2],[6,3],[6,4],[6,5],
            [11,1],[12,1],[12,3],[13,2],[13,4],
            [1,0],[2,0],[3,0],[4,0],[5,0],
            [1,21],[2,21],[3,21],[4,21],[5,21],
            [20,3],[21,3],[20,6],[21,6],
            [9,9],[10,9],[11,9],[10,10],[11,10],
        ];
        for (const [gx, gy] of treePlots) {
            drawTree(gx, gy);
        }

        // ── Stadium ────────────────────────────────────────────────────────
        const stadS = isoToScreen(3, 22);
        ctx.fillStyle = '#3a9030';
        ctx.beginPath();
        ctx.ellipse(stadS.x, stadS.y, 52, 30, -0.2, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = 'rgba(255,255,255,0.6)';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.ellipse(stadS.x, stadS.y, 38, 22, -0.2, 0, Math.PI * 2);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(stadS.x - 38, stadS.y);
        ctx.lineTo(stadS.x + 38, stadS.y);
        ctx.stroke();
        // Stadium stands
        ctx.fillStyle = '#c04050';
        ctx.beginPath();
        ctx.ellipse(stadS.x, stadS.y, 58, 35, -0.2, 0, Math.PI * 2);
        ctx.arc(stadS.x, stadS.y, 50, 0, Math.PI * 2, true);
        ctx.fill();

        // ── INTERACTIVE PIN LANDMARKS ─────────────────────────────────────
        const lakePos   = isoToScreen(2, 3);
        const banexPos  = isoToScreen(9, 1);
        const marketPos = isoToScreen(9, 8);
        const gymPos    = isoToScreen(22, 2);
        const restPos   = isoToScreen(2, 7);
        const hospPos   = isoToScreen(9, 15);
        const mosquePos2 = isoToScreen(14, 7);
        const cbdPos    = isoToScreen(17, 14);
        const fraserPos = isoToScreen(12, 14);
        const stadPos   = isoToScreen(3, 22);
        const secPos    = isoToScreen(21, 8);
        const airportPos= isoToScreen(21, 22);

        const pins = [
            { sx: lakePos.x,    sy: lakePos.y,    id: 'jabi_lake',  label: 'Jabi Lake', icon: '⛵', color: C.pinBlue  },
            { sx: banexPos.x,   sy: banexPos.y,   id: 'banex',      label: 'Banex Plaza', icon: '📱', color: C.pinPurple },
            { sx: marketPos.x,  sy: marketPos.y,  id: 'market',     label: 'Wuse Market', icon: '🛍️', color: C.pinRed  },
            { sx: gymPos.x,     sy: gymPos.y,     id: 'gym',        label: 'Maitama Gym', icon: '🏋️', color: C.pinGreen},
            { sx: restPos.x,    sy: restPos.y,    id: 'restaurant', label: 'Jabi Grill', icon: '🍲', color: C.pinYellow},
            { sx: hospPos.x,    sy: hospPos.y,    id: 'hospital',   label: 'Hospital', icon: '🏥', color: '#f43f5e' },
            { sx: mosquePos2.x, sy: mosquePos2.y, id: 'mosque',     label: 'Nat. Mosque', icon: '🕌', color: '#14b8a6' },
            { sx: cbdPos.x,     sy: cbdPos.y,     id: 'cbd',        label: 'CBD Towers', icon: '🏦', color: C.pinBlue },
            { sx: fraserPos.x,  sy: fraserPos.y,  id: 'fraser',     label: 'Fraser Suites', icon: '🏨', color: '#f43f5e' },
            { sx: stadPos.x,    sy: stadPos.y,    id: 'stadium',    label: 'Nat. Stadium', icon: '⚽', color: '#10b981' },
            { sx: secPos.x,     sy: secPos.y,     id: 'secretariat',label: 'Three Arms Zone', icon: '🏛️', color: '#64748b' },
            { sx: airportPos.x, sy: airportPos.y, id: 'airport',    label: 'Capital Airport', icon: '✈️', color: '#8b5cf6' },
        ];

        for (const p of pins) {
            drawPin(p.sx, p.sy, p.label, p.icon, p.color, 1);
            registerHit(p.sx, p.sy - 22, 20, p.id, p.label, p.color);
        }

        // ── CITIZEN BADGES ────────────────────────────────────────────────
        for (const c of citizens) {
            drawCitizenBadge(c);
        }
    }

    // ── Main loop ────────────────────────────────────────────────────────────
    function loop() {
        frame++;
        ctx.clearRect(0, 0, W, H);

        // Sky gradient
        const skyGrd = ctx.createLinearGradient(0, 0, 0, H);
        skyGrd.addColorStop(0, '#c8e6fa');
        skyGrd.addColorStop(1, '#e8f4fb');
        ctx.fillStyle = skyGrd;
        ctx.fillRect(0, 0, W, H);

        ctx.save();
        // Centering offset + pan + zoom
        ctx.translate(W / 2 + camX, H / 4 + camY);
        ctx.scale(scale, scale);

        drawCity();

        ctx.restore();
        requestAnimationFrame(loop);
    }

    // ── World → screen ───────────────────────────────────────────────────────
    function worldToCanvas(wx, wy) {
        return {
            x: (wx * scale) + W / 2 + camX,
            y: (wy * scale) + H / 4 + camY,
        };
    }
    function canvasToWorld(cx, cy) {
        return {
            x: (cx - W / 2 - camX) / scale,
            y: (cy - H / 4  - camY) / scale,
        };
    }

    // ── Hit testing ──────────────────────────────────────────────────────────
    function testHit(ex, ey) {
        const { x: wx, y: wy } = canvasToWorld(ex, ey);

        // Check citizens first
        for (const c of citizens) {
            const dx = c.x - wx, dy = c.y - wy;
            if (dx * dx + dy * dy < 300) {
                return { type: 'citizen', citizen: c };
            }
        }
        // Then pins
        for (const z of hitZones) {
            const dx = z.x - wx, dy = z.y - wy;
            if (dx * dx + dy * dy < z.r * z.r) {
                return { type: 'location', id: z.id, label: z.label };
            }
        }
        return null;
    }

    // ── Public API ───────────────────────────────────────────────────────────
    function init(canvasEl, clickLocCb, clickCitCb) {
        canvas = canvasEl;
        ctx = canvas.getContext('2d');
        onClickLocation = clickLocCb;
        onClickCitizen  = clickCitCb;

        function resize() {
            W = canvas.width  = canvas.parentElement.clientWidth;
            H = canvas.height = canvas.parentElement.clientHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        // Pan
        canvas.addEventListener('mousedown', e => {
            isDragging = true;
            dragStartX = e.clientX; dragStartY = e.clientY;
            dragCamX = camX; dragCamY = camY;
            canvas.style.cursor = 'grabbing';
        });
        canvas.addEventListener('mousemove', e => {
            if (isDragging) {
                camX = dragCamX + (e.clientX - dragStartX);
                camY = dragCamY + (e.clientY - dragStartY);
            } else {
                const hit = testHit(e.clientX - canvas.getBoundingClientRect().left,
                                    e.clientY - canvas.getBoundingClientRect().top);
                canvas.style.cursor = hit ? 'pointer' : 'grab';
            }
        });
        canvas.addEventListener('mouseup', e => {
            const moved = Math.abs(e.clientX - dragStartX) + Math.abs(e.clientY - dragStartY);
            isDragging = false;
            canvas.style.cursor = 'grab';
            if (moved < 5) {
                const rect = canvas.getBoundingClientRect();
                const hit = testHit(e.clientX - rect.left, e.clientY - rect.top);
                if (hit) {
                    if (hit.type === 'location' && onClickLocation) onClickLocation(hit.id, hit.label);
                    if (hit.type === 'citizen'  && onClickCitizen)  onClickCitizen(hit.citizen);
                }
            }
        });

        // Zoom
        canvas.addEventListener('wheel', e => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            scale = Math.min(2.0, Math.max(0.4, scale * delta));
        }, { passive: false });

        // Touch
        let lastTouchDist = null, lastTouchX, lastTouchY;
        canvas.addEventListener('touchstart', e => {
            if (e.touches.length === 1) {
                isDragging = true;
                dragStartX = e.touches[0].clientX; dragStartY = e.touches[0].clientY;
                dragCamX = camX; dragCamY = camY;
            } else if (e.touches.length === 2) {
                isDragging = false;
                lastTouchDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY);
            }
        }, { passive: true });
        canvas.addEventListener('touchmove', e => {
            e.preventDefault();
            if (e.touches.length === 1 && isDragging) {
                camX = dragCamX + (e.touches[0].clientX - dragStartX);
                camY = dragCamY + (e.touches[0].clientY - dragStartY);
            } else if (e.touches.length === 2) {
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY);
                scale = Math.min(2.0, Math.max(0.4, scale * (dist / lastTouchDist)));
                lastTouchDist = dist;
            }
        }, { passive: false });
        canvas.addEventListener('touchend', e => {
            if (e.changedTouches.length === 1 && !isDragging) {
                const t = e.changedTouches[0];
                const rect = canvas.getBoundingClientRect();
                const hit = testHit(t.clientX - rect.left, t.clientY - rect.top);
                if (hit) {
                    if (hit.type === 'location' && onClickLocation) onClickLocation(hit.id, hit.label);
                    if (hit.type === 'citizen'  && onClickCitizen)  onClickCitizen(hit.citizen);
                }
            }
            isDragging = false;
        }, { passive: true });

        loop();
    }

    function zoom(factor) {
        scale = Math.min(2.0, Math.max(0.4, scale * factor));
    }
    function resetView() {
        camX = 0; camY = 0; scale = 1;
    }

    function loadCitizens(citizenList) {
        citizens.length = 0;
        const emojis = ['🧑','👨','👩','🧔','👸','🤴','💂','🧑‍💼','👨‍💼','👩‍💼'];
        const colors  = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6','#14b8a6'];
        citizenList.forEach((c, i) => {
            const angle = (i / citizenList.length) * Math.PI * 2;
            const radius = 80 + (i % 5) * 60;
            // Scatter them across a wide area of the map
            const wx = Math.cos(angle) * radius;
            const wy = Math.sin(angle) * radius * 0.5;
            citizens.push({
                x: wx, y: wy,
                username: c.username || '@citizen',
                emoji: emojis[i % emojis.length],
                color: colors[i % colors.length],
                raw: c
            });
        });
    }

    return { init, zoom, resetView, loadCitizens };
})();
