/**
 * Abuja Life – Premium Isometric Canvas City Map v3
 * Hand-crafted HTML5 Canvas 2D renderer — no external deps, no images needed.
 * Large, detailed, animated, fully interactive Abuja FCT map.
 */
const AbujaMap = (() => {
    // ─── State ─────────────────────────────────────────────────────────────
    let canvas, ctx, W, H;
    let camX = 0, camY = 0, scale = 0.85;
    let isDragging = false, dragStartX, dragStartY, dragCamX, dragCamY;
    let frame = 0;
    let onClickLocation = null;
    let onClickCitizen  = null;

    const hitZones  = [];  // { x, y, r, id, label }
    const citizens   = [];  // { x, y, username, color, emoji }
    const cars       = [];  // { tx, ty, progress, route, color }

    // ─── Tile geometry ─────────────────────────────────────────────────────
    const TW = 96, TH = 48;   // tile width & height (larger for crispness)

    function iso(col, row) {
        return {
            x: (col - row) * (TW / 2),
            y: (col + row) * (TH / 2)
        };
    }

    // ─── Colour palette ────────────────────────────────────────────────────
    const P = {
        // Ground variants
        grass:      '#b8d978', grassDark: '#8fc050', grassMid: '#a8cc60',
        dirt:       '#c8b070', sand:      '#e8d898',
        // Road
        road:       '#3d4550', roadEdge: '#4a5260', roadLine: '#e8d840',
        roadWalk:   '#f0ece0', curb:     '#8090a0',
        // Water
        waterShallow:'#48c8f8', waterDeep:'#1880c8', waterFoam:'#a0e0ff',
        // Buildings – residential
        rWallA: '#f0dfc0', rWallB: '#e8d4a8', rWallC: '#f4e8cc',
        rRoofA: '#d04040', rRoofB: '#c03050', rRoofC: '#b84030',
        rRoofD: '#4060b8', rRoofE: '#306090', rRoofF: '#a03020',
        // Buildings – commercial/glass
        cWall:  '#c8d8e8', cGlassA:'#70c0f0', cGlassB:'#50a8e0', cGlassC:'#90d0ff',
        cRoofA: '#2050a0', cRoofB: '#184080',
        // Buildings – government
        gWall:  '#e8f0f4', gTrim:  '#b0c8d8', gRoof:  '#6080a0', gDome:  '#b0c8e0',
        // Parks & nature
        park:   '#78c840', parkD:  '#58a028', parkBright:'#90e050',
        tree:   '#2a8820', treeMid:'#3aaa30', treeLite:'#50cc40', treeTrunk:'#7a5030',
        // UI pins
        pinDark: '#0f1520', pinWhite:'#ffffff',
        pinGreen:'#10b981', pinBlue:'#3b82f6', pinAmber:'#f59e0b',
        pinRed:  '#ef4444', pinPurple:'#8b5cf6', pinCyan:'#06b6d4',
        pinPink: '#ec4899',
    };

    // ─── Utility: rounded rect ──────────────────────────────────────────────
    function rr(x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x+r,y); ctx.lineTo(x+w-r,y);
        ctx.arcTo(x+w,y,x+w,y+r,r); ctx.lineTo(x+w,y+h-r);
        ctx.arcTo(x+w,y+h,x+w-r,y+h,r); ctx.lineTo(x+r,y+h);
        ctx.arcTo(x,y+h,x,y+h-r,r); ctx.lineTo(x,y+r);
        ctx.arcTo(x,y,x+r,y,r); ctx.closePath();
    }

    // ─── Iso tile (flat top face) ───────────────────────────────────────────
    function tile(col, row, fill, stroke='rgba(0,0,0,0.04)') {
        const {x,y} = iso(col, row);
        ctx.beginPath();
        ctx.moveTo(x,       y - TH/2);
        ctx.lineTo(x+TW/2,  y);
        ctx.lineTo(x,       y + TH/2);
        ctx.lineTo(x-TW/2,  y);
        ctx.closePath();
        ctx.fillStyle = fill; ctx.fill();
        if (stroke) { ctx.strokeStyle=stroke; ctx.lineWidth=0.6; ctx.stroke(); }
    }

    // ─── Isometric box ──────────────────────────────────────────────────────
    // col,row = top-left grid corner; w,d = tile footprint; h = pixel height
    function box(col, row, w, d, h, roofC, wallFrontC, wallSideC, opts={}) {
        const tl = iso(col,   row);
        const tr = iso(col+w, row);
        const bl = iso(col,   row+d);
        const br = iso(col+w, row+d);

        // Side face (right side, row+d)
        ctx.beginPath();
        ctx.moveTo(br.x, br.y-h); ctx.lineTo(bl.x, bl.y-h);
        ctx.lineTo(bl.x, bl.y);   ctx.lineTo(br.x, br.y);
        ctx.closePath();
        ctx.fillStyle = wallSideC; ctx.fill();
        ctx.strokeStyle='rgba(0,0,0,0.12)'; ctx.lineWidth=0.8; ctx.stroke();

        // Front face (right side, col+w)
        ctx.beginPath();
        ctx.moveTo(tr.x, tr.y-h); ctx.lineTo(br.x, br.y-h);
        ctx.lineTo(br.x, br.y);   ctx.lineTo(tr.x, tr.y);
        ctx.closePath();
        ctx.fillStyle = wallFrontC; ctx.fill();
        ctx.strokeStyle='rgba(0,0,0,0.1)'; ctx.lineWidth=0.8; ctx.stroke();

        // Roof
        ctx.beginPath();
        ctx.moveTo(tl.x, tl.y-h); ctx.lineTo(tr.x, tr.y-h);
        ctx.lineTo(br.x, br.y-h); ctx.lineTo(bl.x, bl.y-h);
        ctx.closePath();
        ctx.fillStyle = roofC; ctx.fill();
        ctx.strokeStyle='rgba(0,0,0,0.15)'; ctx.lineWidth=0.8; ctx.stroke();

        // Windows on front face
        if (opts.windows && h > 25) {
            const rows = Math.max(1, Math.floor(h / 20) - 1);
            const cols = Math.max(1, w * 2);
            const dx   = (br.x - tr.x) / (cols + 1);
            const ax   = (br.y - tr.y) / (cols + 1);
            const wy   = h / (rows + 1);
            ctx.fillStyle = opts.winC || P.cGlassA;
            for (let r2=1; r2<=rows; r2++) {
                for (let c2=1; c2<=cols; c2++) {
                    const wx = tr.x + dx*c2 - 3;
                    const wy2= tr.y - h + wy*r2 + ax*c2 - 3;
                    ctx.fillRect(wx, wy2, 6, 8);
                }
            }
        }
        if (opts.windowsSide && h > 25) {
            const rows = Math.max(1, Math.floor(h / 20) - 1);
            const cols = Math.max(1, d * 2);
            const dx   = (bl.x - br.x) / (cols + 1);
            const ax   = (bl.y - br.y) / (cols + 1);
            const wy   = h / (rows + 1);
            ctx.fillStyle = opts.winC || P.cGlassB;
            for (let r2=1; r2<=rows; r2++) {
                for (let c2=1; c2<=cols; c2++) {
                    const wx = br.x + dx*c2 - 2;
                    const wy2= br.y - h + wy*r2 + ax*c2 - 2;
                    ctx.fillRect(wx, wy2, 5, 7);
                }
            }
        }
    }

    // ─── Road tile ──────────────────────────────────────────────────────────
    function roadTile(col, row, dir='H') {
        tile(col, row, P.road, null);
        const {x,y} = iso(col, row);
        // Curb edges
        ctx.strokeStyle = P.roadEdge; ctx.lineWidth = 1.5;
        // Centre dash
        ctx.setLineDash([8,6]);
        ctx.strokeStyle = P.roadLine; ctx.lineWidth = 1.2;
        ctx.beginPath();
        if (dir === 'H') { ctx.moveTo(x-TW/2+5,y); ctx.lineTo(x+TW/2-5,y); }
        else             { ctx.moveTo(x,y-TH/2+4); ctx.lineTo(x,y+TH/2-4); }
        ctx.stroke();
        ctx.setLineDash([]);
    }
    function roadH(col, row, len) { for(let i=0;i<len;i++) roadTile(col+i, row, 'H'); }
    function roadV(col, row, len) { for(let i=0;i<len;i++) roadTile(col, row+i, 'V'); }
    function intersection(col, row) {
        tile(col, row, P.road, null);
        // White pedestrian stripes on all 4 edges
        const {x,y} = iso(col, row);
        ctx.fillStyle='rgba(255,255,255,0.35)';
        for(let i=-2;i<=2;i+=2) { ctx.fillRect(x+i*4-1, y-TH/2+2, 2, 8); }
        for(let i=-2;i<=2;i+=2) { ctx.fillRect(x+i*4-1, y+TH/2-10, 2, 8); }
    }

    // ─── Ground region fill ─────────────────────────────────────────────────
    function fillRegion(cols, rows, colors) {
        for(const [c,r] of cols.flatMap((c,i)=>rows.map(row=>[c,row])))
            tile(c, r, colors[0]);
    }

    // ─── Tree ───────────────────────────────────────────────────────────────
    function tree(col, row, size=1) {
        const {x,y} = iso(col, row);
        const bob = Math.sin(frame*0.025 + col*1.7 + row*0.9) * 2;
        const h = 18 * size, cr = 13 * size;
        // Trunk
        ctx.fillStyle = P.treeTrunk;
        ctx.fillRect(x-2, y-h+bob, 4, h);
        // Shadow
        ctx.save(); ctx.globalAlpha=0.18;
        ctx.fillStyle='#000';
        ctx.beginPath(); ctx.ellipse(x,y+3,cr*0.7,cr*0.3,0,0,Math.PI*2);
        ctx.fill(); ctx.restore();
        // Canopy layers
        ctx.fillStyle = P.tree;    ctx.beginPath(); ctx.arc(x,y-h+bob,cr,0,Math.PI*2); ctx.fill();
        ctx.fillStyle = P.treeMid; ctx.beginPath(); ctx.arc(x-cr*0.3,y-h-cr*0.4+bob,cr*0.7,0,Math.PI*2); ctx.fill();
        ctx.fillStyle = P.treeLite;ctx.beginPath(); ctx.arc(x+cr*0.2,y-h-cr*0.3+bob,cr*0.5,0,Math.PI*2); ctx.fill();
    }

    // ─── Streetlight ────────────────────────────────────────────────────────
    function streetlight(col, row) {
        const {x,y}=iso(col,row);
        ctx.fillStyle='#607080';
        ctx.fillRect(x-1,y-28,2,28);
        ctx.fillStyle='#ffe880';
        ctx.beginPath(); ctx.arc(x,y-28,4,0,Math.PI*2); ctx.fill();
        ctx.save(); ctx.globalAlpha=0.12;
        const g=ctx.createRadialGradient(x,y-28,0,x,y-28,22);
        g.addColorStop(0,'#ffe880'); g.addColorStop(1,'transparent');
        ctx.fillStyle=g; ctx.beginPath(); ctx.arc(x,y-28,22,0,Math.PI*2); ctx.fill();
        ctx.restore();
    }

    // ─── Pin / label ────────────────────────────────────────────────────────
    function pin(sx, sy, label, icon, color) {
        const bob = Math.sin(frame*0.035 + sx*0.008)*5;
        const py = sy + bob;
        // Shadow
        ctx.save(); ctx.globalAlpha=0.2; ctx.fillStyle='#000';
        ctx.beginPath(); ctx.ellipse(sx,py+5,9,3,0,0,Math.PI*2); ctx.fill(); ctx.restore();
        // Stem
        ctx.strokeStyle=color; ctx.lineWidth=2.5;
        ctx.beginPath(); ctx.moveTo(sx,py); ctx.lineTo(sx,py-12); ctx.stroke();
        // Circle
        ctx.fillStyle=color; ctx.beginPath(); ctx.arc(sx,py-26,16,0,Math.PI*2); ctx.fill();
        ctx.fillStyle='rgba(255,255,255,0.2)'; ctx.beginPath(); ctx.arc(sx,py-26,13,0,Math.PI*2); ctx.fill();
        // Icon
        ctx.font='14px serif'; ctx.textAlign='center'; ctx.textBaseline='middle';
        ctx.fillText(icon, sx, py-26);
        // Label pill
        ctx.font='bold 9px "Plus Jakarta Sans",sans-serif';
        const tw = ctx.measureText(label).width + 20;
        ctx.fillStyle=P.pinDark;
        rr(sx-tw/2, py-50, tw, 18, 9); ctx.fill();
        ctx.fillStyle='#fff'; ctx.textAlign='center'; ctx.textBaseline='middle';
        ctx.fillText(label, sx, py-41);
        hitZones.push({x:sx, y:py-26, r:18, id:icon, label});
    }

    // ─── Citizen badge ──────────────────────────────────────────────────────
    function citizenBadge(c) {
        const bob = Math.sin(frame*0.03 + c.x*0.08)*6;
        const py = c.y + bob;
        // Walk shimmy
        const shimmy = Math.sin(frame*0.08 + c.x)*1.5;
        const cx = c.x + shimmy;
        // Shadow
        ctx.save(); ctx.globalAlpha=0.15; ctx.fillStyle='#000';
        ctx.beginPath(); ctx.ellipse(cx,py+5,9,3,0,0,Math.PI*2); ctx.fill(); ctx.restore();
        // Body circle
        ctx.fillStyle = c.color||'#6366f1';
        ctx.beginPath(); ctx.arc(cx,py,14,0,Math.PI*2); ctx.fill();
        ctx.fillStyle='rgba(255,255,255,0.25)';
        ctx.beginPath(); ctx.arc(cx-3,py-3,6,0,Math.PI*2); ctx.fill();
        // Avatar emoji
        ctx.font='14px serif'; ctx.textAlign='center'; ctx.textBaseline='middle';
        ctx.fillText(c.emoji||'🧑', cx, py);
        // Username tag
        ctx.font='bold 8.5px monospace';
        const tw=ctx.measureText(c.username).width+16;
        ctx.fillStyle='#0f172a';
        rr(cx-tw/2, py-32, tw, 16, 8); ctx.fill();
        ctx.strokeStyle=P.pinGreen; ctx.lineWidth=1.5;
        rr(cx-tw/2, py-32, tw, 16, 8); ctx.stroke();
        ctx.fillStyle='#fff'; ctx.textAlign='center'; ctx.textBaseline='middle';
        ctx.fillText(c.username, cx, py-24);
    }

    // ─── Zuma Rock (background scenery) ─────────────────────────────────────
    function drawZumaRock(ox, oy) {
        // Massive rock in the far background
        ctx.save();
        // Base rock
        ctx.fillStyle='#6a7a60'; ctx.beginPath();
        ctx.moveTo(ox-140,oy);
        ctx.quadraticCurveTo(ox-170,oy-200,ox-30,oy-280);
        ctx.quadraticCurveTo(ox+80,oy-320,ox+150,oy-200);
        ctx.quadraticCurveTo(ox+180,oy-120,ox+130,oy);
        ctx.closePath(); ctx.fill();
        // Lighter face
        ctx.fillStyle='#7a8a70'; ctx.beginPath();
        ctx.moveTo(ox-30,oy-280);
        ctx.quadraticCurveTo(ox+20,oy-300,ox+80,oy-290);
        ctx.quadraticCurveTo(ox+120,oy-230,ox+100,oy);
        ctx.lineTo(ox-30,oy); ctx.closePath(); ctx.fill();
        // Highlight
        ctx.fillStyle='rgba(255,255,255,0.08)'; ctx.beginPath();
        ctx.ellipse(ox+10,oy-250,50,70,-0.3,0,Math.PI*2); ctx.fill();
        // Shadow base
        ctx.fillStyle='#3d4a32'; ctx.beginPath();
        ctx.moveTo(ox-140,oy);
        ctx.quadraticCurveTo(ox-30,oy+18,ox+130,oy);
        ctx.closePath(); ctx.fill();
        ctx.restore();
    }

    // ─── Draw full city ─────────────────────────────────────────────────────
    function drawCity() {
        hitZones.length=0;

        // ── Sky gradient (drawn before ctx.save/translate) handled in loop

        // ─── GROUND BASE ───────────────────────────────────────────────────
        for(let c=-4;c<60;c++) for(let r=-4;r<60;r++) {
            const g = ((c+r)%2===0) ? P.grass : P.grassMid;
            tile(c,r,g,null);
        }

        // ─── JABI LAKE ─────────────────────────────────────────────────────
        for(let c=0;c<8;c++) for(let r=0;r<7;r++) {
            const wave = Math.sin(frame*0.018+c*0.8+r*0.5)*4;
            const t = (c+r)/(8+7);
            const blue = `hsl(${200+wave}, ${76+t*4}%, ${44+wave*0.8}%)`;
            tile(c,r,blue,null);
        }
        // Foam edge
        for(let c=0;c<8;c++) {
            tile(c,7,P.sand,'rgba(200,180,100,0.4)');
            tile(c,-1,P.waterFoam,'rgba(160,220,255,0.4)');
        }
        for(let r=0;r<7;r++) {
            tile(-1,r,P.waterFoam,'rgba(160,220,255,0.4)');
            tile(8,r,P.sand,'rgba(200,180,100,0.4)');
        }
        // Lake label
        const lakePt=iso(3,3);
        ctx.save(); ctx.globalAlpha=0.75;
        ctx.fillStyle='#003f7a'; ctx.font='bold italic 13px "Plus Jakarta Sans",sans-serif';
        ctx.textAlign='center'; ctx.textBaseline='middle';
        ctx.fillText('Jabi Lake', lakePt.x, lakePt.y+6);
        ctx.restore();

        // ─── ROADS ─────────────────────────────────────────────────────────
        // Main roads (Shehu Shagari Way, Airport Rd, etc)
        roadH(0, 8, 55);   // Main E-W highway
        roadH(0,18, 55);   // Southern highway
        roadH(0,30, 55);   // Airport road
        roadH(0,42, 55);   // Southern outskirt
        roadV(10, 0, 55);  // Main N-S artery
        roadV(20, 0, 55);  // CBD axis
        roadV(32, 0, 55);  // Eastern artery
        roadV(44, 0, 55);  // Far eastern
        // Secondary roads
        roadH(0,13,55); roadH(0,24,55); roadH(0,36,55); roadH(0,48,55);
        roadV(15,0,55); roadV(26,0,55); roadV(38,0,55); roadV(50,0,55);
        // Intersections
        for(const col of [10,15,20,26,32,38,44,50]) {
            for(const row of [8,13,18,24,30,36,42,48]) {
                intersection(col,row);
            }
        }

        // ─── PAVEMENTS / SIDEWALKS beside major roads ──────────────────────
        for(let c=0;c<55;c++) {
            tile(c,7,P.roadWalk,null); tile(c,9,P.roadWalk,null);
            tile(c,17,P.roadWalk,null); tile(c,19,P.roadWalk,null);
        }

        // ─── PARKS ─────────────────────────────────────────────────────────
        // Millennium Park (main large park)
        for(let c=21;c<30;c++) for(let r=9;r<17;r++) tile(c,r,P.park,null);
        // Inner grass
        for(let c=22;c<29;c++) for(let r=10;r<16;r++) tile(c,r,P.parkBright,null);
        for(let c=23;c<29;c+=2) for(let r=10;r<16;r+=2) tree(c,r,1.1);
        // Park paths
        roadH(21,12,9); roadV(25,9,8);
        // Fountain center
        const ftn=iso(25,12); ctx.fillStyle='#48b8f0';
        ctx.beginPath(); ctx.arc(ftn.x,ftn.y-3,12,0,Math.PI*2); ctx.fill();
        ctx.fillStyle='rgba(255,255,255,0.4)'; ctx.beginPath(); ctx.arc(ftn.x-2,ftn.y-6,5,0,Math.PI*2); ctx.fill();

        // Gwarimpa Park
        for(let c=33;c<40;c++) for(let r=1;r<7;r++) tile(c,r,P.park,null);
        for(let c=34;c<39;c+=2) for(let r=1;r<7;r+=2) tree(c,r,0.9);

        // Area 1 Gardens
        for(let c=11;c<15;c++) for(let r=9;r<13;r++) tile(c,r,P.park,null);
        for(let c=11;c<15;c+=2) for(let r=9;r<13;r+=2) tree(c,r,0.8);

        // ─── JABI DISTRICT (Blocks around the lake) ─────────────────────────
        // Residential cluster
        box(0,9,2,2,30, P.rRoofA,P.rWallA,P.rWallB,{windows:true});
        box(3,9,2,2,28, P.rRoofB,P.rWallB,P.rWallC,{windows:true});
        box(6,9,2,2,32, P.rRoofD,P.rWallA,P.rWallB,{windows:true});
        box(0,12,2,2,26, P.rRoofC,P.rWallC,P.rWallA,{windows:true});
        box(3,12,2,2,30, P.rRoofF,P.rWallB,P.rWallC,{windows:true});
        box(6,12,2,2,28, P.rRoofA,P.rWallA,P.rWallB,{windows:true});
        // Jabi mall / Banex-style plaza
        box(0,14,4,3,50, P.cRoofA,P.cWall,P.cGlassB,{windows:true,windowsSide:true,winC:P.cGlassA});

        // ─── WUSE / BANEX PLAZA DISTRICT ───────────────────────────────────
        box(11,1,4,3,55, P.cRoofA, P.cWall, P.cGlassB,{windows:true,windowsSide:true,winC:P.cGlassA});
        box(11,5,2,2,38, P.rRoofD, P.rWallA,P.rWallB,{windows:true});
        box(14,5,2,2,34, P.rRoofA, P.rWallB,P.rWallC,{windows:true});
        box(16,1,3,3,45, P.cRoofB, P.cGlassA,P.cGlassC,{windows:true,windowsSide:true,winC:P.cGlassB});
        box(16,5,2,2,36, P.rRoofB, P.rWallC,P.rWallA,{windows:true});
        // Wuse Market buildings
        box(11,9, 3,3,42, P.rRoofF,'#e8c060','#d0a840',{windows:true});
        box(15,9, 2,3,38, '#b04080','#e8d0b0','#d8b890',{windows:true});
        box(11,13,3,2,35, P.rRoofA, P.rWallA,P.rWallB,{windows:true});

        // ─── MAITAMA – HIGH-END RESIDENTIAL ────────────────────────────────
        for(let i=0;i<5;i++) {
            const c = 33+i*2, r = 9;
            const roofs = [P.rRoofA,P.rRoofB,P.rRoofD,P.rRoofC,P.rRoofF];
            box(c,r,2,2,34+i*4, roofs[i%5], P.rWallA,P.rWallB,{windows:true});
        }
        for(let i=0;i<5;i++) {
            const c = 33+i*2, r = 14;
            const roofs = [P.rRoofD,P.rRoofC,P.rRoofA,P.rRoofF,P.rRoofB];
            box(c,r,2,2,32+i*3, roofs[i%5], P.rWallB,P.rWallC,{windows:true});
        }

        // ─── ASOKORO – EMBASSY ROW ──────────────────────────────────────────
        box(45,1,3,3,60, '#d0e0f0','#e8f4ff','#c8dce8',{windows:true,winC:'#90c0e0'});
        box(45,5,3,3,55, '#c0d4e8','#d8ecf8','#b8cce0',{windows:true,winC:'#80b0d8'});
        box(49,1,4,3,70, '#d8eaf8','#e8f4ff','#c8dcee',{windows:true,winC:'#a0c8e8'});
        box(49,5,4,2,52, '#c8dce8','#d8eaf4','#b8cce0',{windows:true});
        // Aso Rock Villa suggestion (right edge)
        box(52,19,5,5,80, P.gRoof,P.gWall,P.gTrim,{windows:true,winC:'#90b8d8'});
        box(53,20,3,3,50, '#88a8c8',P.gWall,P.gTrim);
        // National flag suggestion
        const asoS=iso(53,19); ctx.fillStyle='#008751';
        ctx.fillRect(asoS.x-1,asoS.y-110,2,60); ctx.fillStyle='#008751';
        ctx.fillRect(asoS.x+1,asoS.y-110,16,8); ctx.fillStyle='#fff';
        ctx.fillRect(asoS.x+1,asoS.y-102,16,8); ctx.fillStyle='#008751';
        ctx.fillRect(asoS.x+1,asoS.y-94,16,8);

        // ─── CBD – TWIN TOWERS (Abuja's skyline) ────────────────────────────
        // Tower A
        box(21,19,3,3,140, P.cGlassA,P.cGlassB,P.cGlassC,{windows:true,windowsSide:true,winC:'#b0e0ff'});
        // Glass gradient facade on Tower A
        const tA=iso(24,19); const tA2=iso(24,22);
        const grA=ctx.createLinearGradient(tA.x,tA.y-140,tA2.x,tA2.y);
        grA.addColorStop(0,'rgba(100,200,255,0.4)'); grA.addColorStop(1,'rgba(20,80,180,0.2)');
        ctx.beginPath();
        ctx.moveTo(tA.x,tA.y-140); ctx.lineTo(tA2.x,tA2.y-140);
        ctx.lineTo(tA2.x,tA2.y); ctx.lineTo(tA.x,tA.y); ctx.closePath();
        ctx.fillStyle=grA; ctx.fill();
        // Tower B (slightly taller)
        box(25,19,3,3,160, '#4090d8','#60a8e8','#50b0f0',{windows:true,windowsSide:true,winC:'#c0e8ff'});
        // Antenna spire
        const spire=iso(27,19); ctx.strokeStyle='#304060'; ctx.lineWidth=2;
        ctx.beginPath(); ctx.moveTo(spire.x,spire.y-160); ctx.lineTo(spire.x,spire.y-200); ctx.stroke();
        ctx.fillStyle='#ef4444'; ctx.beginPath(); ctx.arc(spire.x,spire.y-200,3,0,Math.PI*2); ctx.fill();

        // ─── NATIONAL MOSQUE ─────────────────────────────────────────────────
        const msqS=iso(20,9);
        // Base platform
        ctx.fillStyle='#e0e8f0'; ctx.beginPath();
        ctx.ellipse(msqS.x,msqS.y,60,30,0,0,Math.PI*2); ctx.fill();
        // Main dome
        const dg=ctx.createRadialGradient(msqS.x-8,msqS.y-60,0,msqS.x,msqS.y-40,55);
        dg.addColorStop(0,'#80c8e8'); dg.addColorStop(0.5,'#40a0c8'); dg.addColorStop(1,'#2070a0');
        ctx.fillStyle=dg; ctx.beginPath(); ctx.arc(msqS.x,msqS.y-40,45,Math.PI,0,false); ctx.fill();
        ctx.fillStyle='rgba(255,255,255,0.2)'; ctx.beginPath();
        ctx.ellipse(msqS.x-12,msqS.y-60,16,10,-0.5,0,Math.PI*2); ctx.fill();
        // Gold finial
        ctx.fillStyle='#d4a020'; ctx.beginPath(); ctx.arc(msqS.x,msqS.y-85,5,0,Math.PI*2); ctx.fill();
        // Four minarets
        for(const [mx,my] of [[-50,0],[50,0],[-35,-14],[35,-14]]) {
            ctx.fillStyle='#3878a0';
            ctx.fillRect(msqS.x+mx-4,msqS.y+my-70,8,70);
            ctx.fillStyle='#50a0c8'; ctx.beginPath();
            ctx.arc(msqS.x+mx,msqS.y+my-70,6,0,Math.PI*2); ctx.fill();
            ctx.fillStyle='#d4a020'; ctx.beginPath();
            ctx.arc(msqS.x+mx,msqS.y+my-76,2.5,0,Math.PI*2); ctx.fill();
        }
        // Body walls
        ctx.fillStyle='#d8e8f0';
        ctx.fillRect(msqS.x-42,msqS.y-20,84,20);

        // ─── NATIONAL CHURCH ─────────────────────────────────────────────────
        const chS=iso(20,24);
        ctx.fillStyle='#e8e8f0'; ctx.fillRect(chS.x-30,chS.y-50,60,50);
        // Spire
        ctx.fillStyle='#808090'; ctx.beginPath();
        ctx.moveTo(chS.x,chS.y-90); ctx.lineTo(chS.x-12,chS.y-50); ctx.lineTo(chS.x+12,chS.y-50);
        ctx.closePath(); ctx.fill();
        ctx.strokeStyle='#606070'; ctx.lineWidth=1.5;
        ctx.beginPath(); ctx.moveTo(chS.x,chS.y-50); ctx.lineTo(chS.x,chS.y-95); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(chS.x-6,chS.y-78); ctx.lineTo(chS.x+6,chS.y-78); ctx.stroke();
        // Arched windows
        ctx.fillStyle='#c0a070'; ctx.fillRect(chS.x-24,chS.y-40,12,20);
        ctx.beginPath(); ctx.arc(chS.x-18,chS.y-40,6,Math.PI,0); ctx.fill();
        ctx.fillStyle='#c0a070'; ctx.fillRect(chS.x+12,chS.y-40,12,20);
        ctx.beginPath(); ctx.arc(chS.x+18,chS.y-40,6,Math.PI,0); ctx.fill();

        // ─── NATIONAL HOSPITAL ────────────────────────────────────────────
        box(11,19,4,4,50,'#e03045','#f4f4f8','#e8e8f0',{windows:true,winC:'#c0e0ff'});
        const hospS=iso(13,21);
        ctx.fillStyle='#e02030'; ctx.fillRect(hospS.x-3,hospS.y-65,6,18);
        ctx.fillRect(hospS.x-9,hospS.y-59,18,6);

        // ─── MOSHOOD ABIOLA STADIUM ──────────────────────────────────────
        const stS=iso(5,36);
        // Outer ring
        ctx.fillStyle='#c03050'; ctx.beginPath();
        ctx.ellipse(stS.x,stS.y,80,45,0,0,Math.PI*2); ctx.fill();
        // Track
        ctx.fillStyle='#d06020'; ctx.beginPath();
        ctx.ellipse(stS.x,stS.y,68,38,0,0,Math.PI*2); ctx.fill();
        // Pitch
        ctx.fillStyle='#3a9828'; ctx.beginPath();
        ctx.ellipse(stS.x,stS.y,54,30,0,0,Math.PI*2); ctx.fill();
        // Lines
        ctx.strokeStyle='rgba(255,255,255,0.7)'; ctx.lineWidth=1.5; ctx.setLineDash([]);
        ctx.beginPath(); ctx.moveTo(stS.x-54,stS.y); ctx.lineTo(stS.x+54,stS.y); ctx.stroke();
        ctx.beginPath(); ctx.ellipse(stS.x,stS.y,14,8,0,0,Math.PI*2); ctx.stroke();

        // ─── THREE ARMS ZONE / SECRETARIAT ──────────────────────────────
        box(45,19,6,6,90, P.gRoof,P.gWall,P.gTrim,{windows:true,windowsSide:true,winC:'#a0c8e8'});
        box(45,26,3,3,70, P.gRoof,'#dde8f4',P.gTrim,{windows:true});
        box(49,26,3,3,65, '#7898b8','#c8dce8',P.gTrim,{windows:true});
        // Flagpoles
        for(const [fc,fr] of [[46,19],[48,19],[50,19]]) {
            const fs=iso(fc,fr);
            ctx.fillStyle='#708090'; ctx.fillRect(fs.x-1,fs.y-90,2,90);
            ctx.fillStyle='#008751'; ctx.fillRect(fs.x+1,fs.y-90,14,8);
            ctx.fillStyle='#fff';    ctx.fillRect(fs.x+1,fs.y-82,14,6);
            ctx.fillStyle='#008751'; ctx.fillRect(fs.x+1,fs.y-76,14,6);
        }

        // ─── FRASER SUITES / LUXURY HOTELS ──────────────────────────────
        box(33,19,3,4,80,'#1c3858','#c0ccd8','#a8b8c8',{windows:true,windowsSide:true,winC:'#d0e8ff'});
        box(37,19,2,4,70,'#20406a','#b8c8d8','#a0b0c0',{windows:true});

        // ─── GWARIMPA RESIDENTIAL ────────────────────────────────────────
        for(let i=0;i<6;i++) for(let j=0;j<3;j++) {
            const roofChoice=[P.rRoofA,P.rRoofB,P.rRoofD,P.rRoofC,P.rRoofE,P.rRoofF];
            box(33+i*3, 31+j*3, 2,2, 28+i*3,
                roofChoice[(i+j)%6], P.rWallA,P.rWallB,{windows:true});
        }

        // ─── AIRPORT AREA ────────────────────────────────────────────────
        // Terminal
        box(0,42,8,4,45,'#4060b0','#e0e8f8','#c8d4e8',{windows:true,winC:'#a0c0e8'});
        // Control tower
        box(9,42,2,2,90,'#3050c0','#b0c4dc','#90a8c4',{windows:true,winC:'#80b0d8'});
        // Runway strips
        for(let c=0;c<8;c++) tile(c,47,'#606070',null);
        for(let c=0;c<8;c+=2) {
            const rs=iso(c,47); ctx.fillStyle='#fff';
            ctx.fillRect(rs.x-4,rs.y-2,8,4);
        }

        // ─── SCATTERED RESIDENTIAL BLOCKS ───────────────────────────────
        const extraBlocks=[
            [0,19,2,2,30,P.rRoofA],[0,22,2,2,28,P.rRoofD],[3,19,2,2,32,P.rRoofB],
            [3,22,2,2,26,P.rRoofC],[6,19,2,2,28,P.rRoofF],[6,22,2,2,30,P.rRoofA],
            [0,31,2,2,26,P.rRoofB],[0,34,2,2,24,P.rRoofD],[3,31,2,2,28,P.rRoofA],
            [3,34,2,2,30,P.rRoofC],[6,31,2,2,26,P.rRoofF],[6,34,2,2,28,P.rRoofB],
            [40,9,2,2,30,P.rRoofA],[40,12,2,2,28,P.rRoofD],[43,9,2,2,32,P.rRoofB],
            [43,12,2,2,26,P.rRoofC],[46,9,2,2,28,P.rRoofF],[46,12,2,2,30,P.rRoofA],
        ];
        for(const [c,r,w,d,h,rc] of extraBlocks) {
            box(c,r,w,d,h,rc,P.rWallA,P.rWallB,{windows:true});
        }

        // ─── TREES ALONG ROADS ──────────────────────────────────────────
        const treePlots=[];
        for(let c=0;c<55;c+=3) { treePlots.push([c,7]); treePlots.push([c,19]); }
        for(let r=0;r<55;r+=3) { treePlots.push([10,r]); treePlots.push([20,r]); }
        for(const [tc,tr] of treePlots) tree(tc,tr,0.8);

        // Extra trees in open areas
        for(let c=33;c<44;c+=4) for(let r=9;r<18;r+=4) {
            if(c%6!==0) tree(c+1,r+1,0.85);
        }

        // ─── STREETLIGHTS ───────────────────────────────────────────────
        for(let c=0;c<55;c+=6) streetlight(c,8);
        for(let c=0;c<55;c+=6) streetlight(c,18);
        for(let r=0;r<55;r+=6) streetlight(10,r);
        for(let r=0;r<55;r+=6) streetlight(20,r);

        // ─── INTERACTIVE LANDMARK PINS ──────────────────────────────────
        const pins=[
            {id:'jabi_lake',   label:'Jabi Lake',      icon:'⛵', color:P.pinCyan,   col:2,  row:2},
            {id:'banex',       label:'Banex Plaza',     icon:'📱', color:P.pinPurple, col:12, row:2},
            {id:'market',      label:'Wuse Market',     icon:'🛍️', color:P.pinRed,    col:12, row:10},
            {id:'gym',         label:'Maitama Gym',     icon:'🏋️', color:P.pinGreen,  col:35, row:10},
            {id:'restaurant',  label:'Jabi Grill',      icon:'🍲', color:P.pinAmber,  col:2,  row:14},
            {id:'hospital',    label:'Nat. Hospital',   icon:'🏥', color:'#f43f5e',   col:13, row:21},
            {id:'mosque',      label:'Nat. Mosque',     icon:'🕌', color:P.pinCyan,   col:20, row:9},
            {id:'church',      label:'Nat. Church',     icon:'⛪', color:'#a0a0c0',   col:20, row:24},
            {id:'fraser',      label:'Fraser Suites',   icon:'🏨', color:P.pinRed,    col:34, row:20},
            {id:'cbd',         label:'CBD Towers',      icon:'🏦', color:P.pinBlue,   col:23, row:20},
            {id:'secretariat', label:'Three Arms Zone', icon:'🏛️', color:'#60788a',   col:47, row:22},
            {id:'stadium',     label:'Nat. Stadium',    icon:'⚽', color:P.pinGreen,  col:5,  row:36},
            {id:'airport',     label:'Airport Terminal',icon:'✈️', color:P.pinPurple, col:4,  row:43},
            {id:'millennium',  label:'Millennium Park', icon:'🌳', color:P.pinGreen,  col:25, row:12},
        ];
        for(const p of pins) {
            const s=iso(p.col,p.row);
            pin(s.x, s.y, p.label, p.icon, p.color);
        }

        // ─── CITIZEN BADGES ─────────────────────────────────────────────
        for(const c of citizens) citizenBadge(c);
    }

    // ─── Main animate loop ──────────────────────────────────────────────────
    function loop() {
        frame++;
        ctx.clearRect(0,0,W,H);

        // Sky
        const sky=ctx.createLinearGradient(0,0,0,H);
        sky.addColorStop(0,'#b8d8f8'); sky.addColorStop(0.6,'#d8eeff'); sky.addColorStop(1,'#eef8ff');
        ctx.fillStyle=sky; ctx.fillRect(0,0,W,H);

        // Clouds
        const t=frame*0.0015;
        for(const [cx2,cy2,cr2,s2] of [[W*0.1+t*40%W,H*0.08,60,0.6],[W*0.4+t*28%W,H*0.12,80,0.8],[W*0.7+t*35%W,H*0.07,55,0.5]]) {
            ctx.save(); ctx.globalAlpha=0.55;
            ctx.fillStyle='#fff';
            ctx.beginPath(); ctx.ellipse(cx2%W,cy2,cr2*s2,cr2*0.35*s2,0,0,Math.PI*2); ctx.fill();
            ctx.beginPath(); ctx.ellipse(cx2%W-cr2*0.3,cy2+4,cr2*0.55*s2,cr2*0.25*s2,0,0,Math.PI*2); ctx.fill();
            ctx.beginPath(); ctx.ellipse(cx2%W+cr2*0.35,cy2+3,cr2*0.5*s2,cr2*0.22*s2,0,0,Math.PI*2); ctx.fill();
            ctx.restore();
        }

        ctx.save();
        ctx.translate(W/2 + camX, H*0.35 + camY);
        ctx.scale(scale, scale);

        // Zuma Rock in the background
        const zumaS=iso(60,-8);
        drawZumaRock(zumaS.x, zumaS.y);

        drawCity();

        ctx.restore();
        requestAnimationFrame(loop);
    }

    // ─── Coordinate conversion ──────────────────────────────────────────────
    function toWorld(ex,ey) {
        return { x:(ex-W/2-camX)/scale, y:(ey-H*0.35-camY)/scale };
    }

    // ─── Hit testing ────────────────────────────────────────────────────────
    function testHit(ex,ey) {
        const {x,y}=toWorld(ex,ey);
        for(const c of citizens) {
            if((c.x-x)**2+(c.y-y)**2<400) return {type:'citizen',citizen:c};
        }
        for(const z of hitZones) {
            if((z.x-x)**2+(z.y-y)**2<z.r*z.r) return {type:'location',id:z.id,label:z.label};
        }
        return null;
    }

    // ─── Public API ─────────────────────────────────────────────────────────
    function init(canvasEl, locCb, citCb) {
        canvas=canvasEl; ctx=canvas.getContext('2d');
        onClickLocation=locCb; onClickCitizen=citCb;

        function resize() {
            W=canvas.width=canvas.parentElement.clientWidth;
            H=canvas.height=canvas.parentElement.clientHeight;
        }
        resize(); window.addEventListener('resize',resize);

        // Mouse
        canvas.addEventListener('mousedown',e=>{
            isDragging=true; dragStartX=e.clientX; dragStartY=e.clientY;
            dragCamX=camX; dragCamY=camY; canvas.style.cursor='grabbing';
        });
        canvas.addEventListener('mousemove',e=>{
            if(isDragging){ camX=dragCamX+(e.clientX-dragStartX); camY=dragCamY+(e.clientY-dragStartY); }
            else {
                const r=canvas.getBoundingClientRect();
                canvas.style.cursor=testHit(e.clientX-r.left,e.clientY-r.top)?'pointer':'grab';
            }
        });
        canvas.addEventListener('mouseup',e=>{
            const moved=Math.abs(e.clientX-dragStartX)+Math.abs(e.clientY-dragStartY);
            isDragging=false; canvas.style.cursor='grab';
            if(moved<6){
                const r=canvas.getBoundingClientRect();
                const hit=testHit(e.clientX-r.left,e.clientY-r.top);
                if(hit){
                    if(hit.type==='location'&&onClickLocation) onClickLocation(hit.id,hit.label);
                    if(hit.type==='citizen' &&onClickCitizen)  onClickCitizen(hit.citizen);
                }
            }
        });
        canvas.addEventListener('mouseleave',()=>{ isDragging=false; canvas.style.cursor='grab'; });

        // Zoom
        canvas.addEventListener('wheel',e=>{
            e.preventDefault();
            scale=Math.min(2.2,Math.max(0.35,scale*(e.deltaY>0?0.9:1.1)));
        },{passive:false});

        // Touch
        let td=null;
        canvas.addEventListener('touchstart',e=>{
            if(e.touches.length===1){isDragging=true;dragStartX=e.touches[0].clientX;dragStartY=e.touches[0].clientY;dragCamX=camX;dragCamY=camY;}
            else if(e.touches.length===2){isDragging=false;td=Math.hypot(e.touches[0].clientX-e.touches[1].clientX,e.touches[0].clientY-e.touches[1].clientY);}
        },{passive:true});
        canvas.addEventListener('touchmove',e=>{
            e.preventDefault();
            if(e.touches.length===1&&isDragging){camX=dragCamX+(e.touches[0].clientX-dragStartX);camY=dragCamY+(e.touches[0].clientY-dragStartY);}
            else if(e.touches.length===2&&td){const nd=Math.hypot(e.touches[0].clientX-e.touches[1].clientX,e.touches[0].clientY-e.touches[1].clientY);scale=Math.min(2.2,Math.max(0.35,scale*(nd/td)));td=nd;}
        },{passive:false});
        canvas.addEventListener('touchend',e=>{
            if(e.changedTouches.length===1&&!isDragging){const t=e.changedTouches[0];const r=canvas.getBoundingClientRect();const hit=testHit(t.clientX-r.left,t.clientY-r.top);if(hit){if(hit.type==='location'&&onClickLocation)onClickLocation(hit.id,hit.label);if(hit.type==='citizen'&&onClickCitizen)onClickCitizen(hit.citizen);}}
            isDragging=false;
        },{passive:true});

        loop();
    }

    function zoom(f){ scale=Math.min(2.2,Math.max(0.35,scale*f)); }
    function resetView(){ camX=0;camY=0;scale=0.85; }

    function loadCitizens(list) {
        citizens.length=0;
        const emojis=['🧑','👨','👩','🧔','👸','🤴','💂','🧑‍💼','👨‍💼','👩‍💼','🧑‍🎤','👨‍🍳','👩‍🔬','🧑‍🚀'];
        const colors=['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6','#14b8a6','#f97316','#84cc16'];
        // Spread them across the full city grid
        const spots=[
            [3,10],[7,13],[13,2],[17,5],[22,15],[28,10],[35,6],[38,14],
            [12,22],[16,28],[23,28],[30,20],[40,22],[48,10],[44,30],[8,38],
            [18,36],[26,34],[32,38],[42,36],[50,24],[52,15],[46,6],[54,30],
        ];
        list.forEach((c,i)=>{
            const spot=spots[i%spots.length];
            const s=iso(spot[0],spot[1]);
            citizens.push({
                x:s.x+(Math.random()-0.5)*TW,
                y:s.y+(Math.random()-0.5)*TH,
                username:c.username||('@citizen'+i),
                emoji:emojis[i%emojis.length],
                color:colors[i%colors.length],
                raw:c
            });
        });
    }

    return {init,zoom,resetView,loadCitizens};
})();
