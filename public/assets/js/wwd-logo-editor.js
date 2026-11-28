/* WWD Admin: logo editor, copied from WWD Business (public/assets/js/wwd-logo-editor.js) so both work the same.
   The admin picks, drops or pastes an image, then moves, zooms, rotates, trims and previews it.
   The browser makes a 500 x 500 PNG and puts it in the normal file field, so the form's single Save still works.
   The server checks and re-draws every logo anyway; without this script the plain upload still works. */
(function () {
    'use strict';

    var root = document.querySelector('[data-logo-field]');
    var modal_el = document.getElementById('logoEditorModal');
    if (!root || !modal_el) {
        return;
    }

    var OUT = 500;                 // saved logo size
    var MAX_BYTES = 5 * 1024 * 1024;
    var MIN_LONG = 200;            // smaller than this is refused (the server refuses it too)
    var BASE_MAX = 3000;           // big photos are scaled to this before editing
    var FIT_K = 0.9;               // "Fit whole logo": the longest side uses 90% of the square
    var K_MIN = 0.25;
    var FRAME = 0.8;               // the square takes 80% of the editing area
    var TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];

    var input = root.querySelector('input[type=file]');
    var drop = root.querySelector('[data-logo-drop]');
    var drop_label = root.querySelector('[data-logo-drop-label]');
    var status = root.querySelector('[data-logo-status]');
    var error = root.querySelector('[data-logo-error]');
    var adjust = root.querySelector('[data-logo-adjust]');
    var previews = document.querySelectorAll(root.dataset.previews || '');

    function show_in_previews(url) {
        previews.forEach(function (box) {
            box.textContent = '';
            var img = document.createElement('img');
            img.src = url;
            img.alt = '';
            box.appendChild(img);
        });
    }
    function show_error(msg) {
        error.textContent = msg;
        error.hidden = !msg;
        // An older server message for this field is replaced by the new one
        root.querySelectorAll('.field-error:not([data-logo-error])').forEach(function (el) { el.remove(); });
    }

    // Old browsers: keep the plain upload with a simple preview
    var supported = (function () {
        try { new DataTransfer(); } catch (e) { return false; }
        return !!(window.bootstrap && HTMLCanvasElement.prototype.toBlob && window.URL && URL.createObjectURL);
    })();
    if (!supported) {
        input.addEventListener('change', function () {
            var file = input.files[0];
            if (file && /^image\/(png|jpeg|webp)$/.test(file.type)) {
                show_in_previews(URL.createObjectURL(file));
                status.textContent = file.name;
            }
        });
        return;
    }

    // SVG logos are turned into a PNG here, so the server never receives SVG
    input.accept = TYPES.join(',');

    function $le(name) { return modal_el.querySelector('[data-le="' + name + '"]'); }
    function make_canvas(w, h) { var c = document.createElement('canvas'); c.width = Math.max(1, Math.round(w)); c.height = Math.max(1, Math.round(h)); return c; }
    function clamp(v, a, b) { return Math.min(b, Math.max(a, v)); }
    function ratio() { return clamp(window.devicePixelRatio || 1, 1, 3); }

    var ui = {
        stage: $le('stage'), zoom: $le('zoom'), src: $le('src'), trim: $le('trim'), trim_block: $le('trim-block'),
        quality: $le('quality'), wide_tip: $le('wide-tip'), color: $le('color')
    };
    var modal = bootstrap.Modal.getOrCreateInstance(modal_el);

    var src = null;        // { base, w, h, type, trim }
    var work = null;       // base after trim and rotation
    var st = null;         // { k, x, y, rot, trim, bg, color }
    var applied = null;    // the logo put in the form this visit: { file, src, st }
    var just_applied = false;
    var ignore_change = false;
    var scaled_cache = {};
    var guide = { x: false, y: false };
    var out = make_canvas(OUT, OUT);
    var raf = 0;

    // ---------- The file field ----------
    function set_input_file(file) {
        var dt = new DataTransfer();
        if (file) {
            dt.items.add(file);
        }
        input.files = dt.files;
    }
    // Back to what the form had before this attempt
    function restore_input() {
        set_input_file(applied ? applied.file : null);
    }

    // ---------- Reading a file ----------
    function type_label(t) { return { 'image/png': 'PNG', 'image/jpeg': 'JPG', 'image/webp': 'WebP', 'image/svg+xml': 'SVG' }[t] || 'image'; }

    function load_image(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () { resolve({ img: img, url: url }); };
            img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('decode')); };
            img.src = url;
        });
    }

    function refuse(msg) {
        show_error(msg);
        restore_input();
    }

    function handle_file(file) {
        if (!file) {
            return;
        }
        if (/hei[cf]/i.test(file.type) || /\.hei[cf]$/i.test(file.name)) {
            return refuse('iPhone HEIC photos can\'t be used. Please save the logo as JPG or PNG and try again.');
        }
        if (TYPES.indexOf(file.type) === -1) {
            return refuse('Please choose a JPG, PNG, WebP or SVG image.');
        }
        if (file.size > MAX_BYTES) {
            return refuse('This file is ' + (file.size / 1048576).toFixed(1) + ' MB. Please use one up to 5 MB.');
        }

        load_image(file).then(function (r) {
            var img = r.img;
            var is_svg = file.type === 'image/svg+xml';
            var w = img.naturalWidth, h = img.naturalHeight;
            if (is_svg) {
                // Vector logos have no fixed size: draw them large and sharp
                var aspect = (w && h) ? w / h : 1;
                w = aspect >= 1 ? 1500 : 1500 * aspect;
                h = aspect >= 1 ? 1500 / aspect : 1500;
            }
            if (Math.max(w, h) < MIN_LONG) {
                URL.revokeObjectURL(r.url);
                return refuse('This image is only ' + w + ' × ' + h + ' px, too small to look sharp. Please use one at least ' + MIN_LONG + ' px wide; 500 × 500 px or larger looks best.');
            }
            var s = is_svg ? 1 : Math.min(1, BASE_MAX / Math.max(w, h));
            var base = make_canvas(w * s, h * s);
            var ctx = base.getContext('2d');
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, 0, 0, base.width, base.height);
            URL.revokeObjectURL(r.url);

            show_error('');
            src = { base: base, w: Math.round(w), h: Math.round(h), type: file.type, trim: find_trim(base) };
            st = default_state();
            build_work();
            open_editor();
        }).catch(function () {
            refuse('We couldn\'t read this image. Please try another file.');
        });
    }

    // Empty space around the logo: the same colour (or transparent) on every edge
    function find_trim(base) {
        var sc = Math.min(1, 600 / Math.max(base.width, base.height));
        var w = Math.max(1, Math.round(base.width * sc)), h = Math.max(1, Math.round(base.height * sc));
        var c = make_canvas(w, h), x = c.getContext('2d', { willReadFrequently: true });
        x.drawImage(base, 0, 0, w, h);
        var d;
        try { d = x.getImageData(0, 0, w, h).data; } catch (e) { return null; }

        var r0 = d[0], g0 = d[1], b0 = d[2], a0 = d[3];
        var ref_clear = a0 < 16;
        function same(o) {
            var a = d[o + 3];
            if (ref_clear) { return a < 16; }
            return a >= 16 && Math.abs(d[o] - r0) + Math.abs(d[o + 1] - g0) + Math.abs(d[o + 2] - b0) <= 40 && Math.abs(a - a0) < 40;
        }
        var corners = [0, (w - 1) * 4, (h - 1) * w * 4, ((h - 1) * w + w - 1) * 4];
        if (!corners.every(same)) { return null; }

        var x0 = w, y0 = h, x1 = -1, y1 = -1;
        for (var j = 0; j < h; j++) {
            for (var i = 0; i < w; i++) {
                if (!same((j * w + i) * 4)) {
                    if (i < x0) { x0 = i; }
                    if (i > x1) { x1 = i; }
                    if (j < y0) { y0 = j; }
                    if (j > y1) { y1 = j; }
                }
            }
        }
        var ref = { r: r0, g: g0, b: b0, opaque: a0 >= 200 };
        if (x1 < 0) { return { rect: null, ref: ref }; }
        var rx = Math.max(0, Math.floor((x0 - 1) / sc)), ry = Math.max(0, Math.floor((y0 - 1) / sc));
        var rect = {
            x: rx, y: ry,
            w: Math.min(base.width, Math.ceil((x1 + 2) / sc)) - rx,
            h: Math.min(base.height, Math.ceil((y1 + 2) / sc)) - ry
        };
        var kept = (rect.w * rect.h) / (base.width * base.height);
        return { rect: kept < 0.92 ? rect : null, ref: ref };
    }

    function to_hex(r, g, b) { return '#' + [r, g, b].map(function (v) { return ('0' + v.toString(16)).slice(-2); }).join(''); }

    function default_state() {
        var ref = src.trim && src.trim.ref;
        var bg = 'transparent', color = '#ffffff';
        // A logo on a solid colour keeps that colour as the background, so no box edges show
        if (ref && ref.opaque) {
            if (ref.r >= 245 && ref.g >= 245 && ref.b >= 245) { bg = 'white'; }
            else { bg = 'custom'; color = to_hex(ref.r, ref.g, ref.b); }
        }
        return { k: FIT_K, x: 0, y: 0, rot: 0, trim: !!(src.trim && src.trim.rect), bg: bg, color: color };
    }

    function build_work() {
        var c = src.base;
        var r = st.trim && src.trim && src.trim.rect;
        if (r) {
            var t = make_canvas(r.w, r.h);
            t.getContext('2d').drawImage(c, r.x, r.y, r.w, r.h, 0, 0, r.w, r.h);
            c = t;
        }
        if (st.rot) {
            var swap = st.rot % 180 !== 0;
            var t2 = make_canvas(swap ? c.height : c.width, swap ? c.width : c.height);
            var x = t2.getContext('2d');
            x.translate(t2.width / 2, t2.height / 2);
            x.rotate(st.rot * Math.PI / 180);
            x.drawImage(c, -c.width / 2, -c.height / 2);
            c = t2;
        }
        work = c;
        scaled_cache = {};
    }

    // Halve big images step by step before drawing them small: sharper than one big jump
    function scaled_for(target_long) {
        var steps = 0, l = Math.max(work.width, work.height);
        while (l / 2 >= target_long) { l /= 2; steps++; }
        if (!steps) { return work; }
        if (scaled_cache.steps === steps && scaled_cache.work === work) { return scaled_cache.canvas; }
        var cur = work;
        for (var i = 0; i < steps; i++) {
            var c = make_canvas(cur.width / 2, cur.height / 2);
            var x = c.getContext('2d');
            x.imageSmoothingQuality = 'high';
            x.drawImage(cur, 0, 0, c.width, c.height);
            cur = c;
        }
        scaled_cache = { steps: steps, work: work, canvas: cur };
        return cur;
    }

    // ---------- Zoom and position ----------
    function long_side() { return Math.max(work.width, work.height); }
    function fill_k() { return long_side() / Math.min(work.width, work.height); }
    function k_max() { return Math.max(4, fill_k() * 2.5); }

    function clamp_pos() {
        var l = long_side();
        var hw = work.width / l * st.k / 2, hh = work.height / l * st.k / 2;
        var mx = 0.5 + hw - 0.04, my = 0.5 + hh - 0.04;
        st.x = clamp(st.x, -mx, mx);
        st.y = clamp(st.y, -my, my);
    }

    function sync_zoom() {
        var a = Math.log(K_MIN), b = Math.log(k_max());
        ui.zoom.value = Math.round((Math.log(st.k) - a) / (b - a) * 1000);
    }

    function set_k(k) {
        st.k = clamp(k, K_MIN, k_max());
        clamp_pos();
        sync_zoom();
        schedule();
    }

    function set_mode(mode) {
        st.k = mode === 'fill' ? fill_k() : FIT_K;
        st.x = 0;
        st.y = 0;
        sync_zoom();
        schedule();
    }

    function sync_controls() {
        sync_zoom();
        ui.trim.checked = st.trim;
        ui.trim_block.hidden = !(src.trim && src.trim.rect);
        ui.color.value = st.color;
        modal_el.querySelectorAll('input[name=le_bg]').forEach(function (r) { r.checked = r.value === st.bg; });
    }

    // ---------- Drawing ----------
    var checker_cache = {};
    function checker(ctx, d) {
        if (checker_cache.d === d) { return checker_cache.p; }
        var s = Math.round(8 * d), c = make_canvas(s * 2, s * 2), x = c.getContext('2d');
        x.fillStyle = '#ffffff';
        x.fillRect(0, 0, s * 2, s * 2);
        x.fillStyle = '#e5e7eb';
        x.fillRect(0, 0, s, s);
        x.fillRect(s, s, s, s);
        checker_cache = { d: d, p: ctx.createPattern(c, 'repeat') };
        return checker_cache.p;
    }
    function bg_color() { return st.bg === 'white' ? '#ffffff' : st.color; }

    function draw_image(ctx, size, ox, oy) {
        var s = st.k * size / long_side();
        var dw = work.width * s, dh = work.height * s;
        var cx = ox + size / 2 + st.x * size, cy = oy + size / 2 + st.y * size;
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(scaled_for(Math.max(dw, dh)), cx - dw / 2, cy - dh / 2, dw, dh);
    }

    function size_stage() {
        var css = ui.stage.getBoundingClientRect().width || 400;
        var px = Math.round(css * ratio());
        if (ui.stage.width !== px) {
            ui.stage.width = px;
            ui.stage.height = px;
        }
    }

    function draw_stage() {
        var c = ui.stage, W = c.width, d = ratio(), ctx = c.getContext('2d');
        var F = W * FRAME, f0 = (W - F) / 2;
        ctx.clearRect(0, 0, W, W);
        ctx.fillStyle = '#eef0f3';
        ctx.fillRect(0, 0, W, W);

        ctx.fillStyle = st.bg === 'transparent' ? checker(ctx, d) : bg_color();
        ctx.fillRect(f0, f0, F, F);

        draw_image(ctx, F, f0, f0);

        // Everything outside the square is dimmed: it won't be saved
        ctx.save();
        ctx.fillStyle = 'rgba(17,24,39,.55)';
        ctx.beginPath();
        ctx.rect(0, 0, W, W);
        ctx.rect(f0, f0, F, F);
        ctx.fill('evenodd');
        ctx.restore();

        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2 * d;
        ctx.strokeRect(f0, f0, F, F);

        if (guide.x || guide.y) {
            ctx.save();
            ctx.setLineDash([6 * d, 5 * d]);
            ctx.strokeStyle = '#dd3f3b';
            ctx.lineWidth = 1.5 * d;
            ctx.beginPath();
            if (guide.x) { ctx.moveTo(W / 2, f0); ctx.lineTo(W / 2, f0 + F); }
            if (guide.y) { ctx.moveTo(f0, W / 2); ctx.lineTo(f0 + F, W / 2); }
            ctx.stroke();
            ctx.restore();
        }
    }

    function render_out() {
        var ctx = out.getContext('2d');
        ctx.clearRect(0, 0, OUT, OUT);
        if (st.bg !== 'transparent') {
            ctx.fillStyle = bg_color();
            ctx.fillRect(0, 0, OUT, OUT);
        }
        draw_image(ctx, OUT, 0, 0);
    }

    function draw_previews() {
        var d = ratio();
        modal_el.querySelectorAll('.le-pv canvas').forEach(function (c) {
            var size = Math.round(parseInt(c.dataset.size, 10) * d);
            if (c.width !== size) {
                c.width = size;
                c.height = size;
            }
            var x = c.getContext('2d');
            x.fillStyle = '#ffffff';   // the site shows logos on white
            x.fillRect(0, 0, size, size);
            x.imageSmoothingQuality = 'high';
            x.drawImage(out, 0, 0, size, size);
        });
    }

    function update_quality() {
        // How many of the image's own pixels end up across the 500 px square
        var across = long_side() / st.k;
        var level, title, sub;
        if (across >= 500) { level = 'is-good'; title = 'Sharp'; sub = 'Enough detail for a 500 × 500 px logo.'; }
        else if (across >= 250) { level = 'is-ok'; title = 'Okay'; sub = 'A bigger image (500 px or more) would look sharper.'; }
        else { level = 'is-low'; title = 'May look blurry'; sub = 'The image is small for this zoom. Zoom out or use a larger file.'; }
        ui.quality.className = 'le-quality ' + level;
        ui.quality.querySelector('b').textContent = title;
        ui.quality.querySelector('small').textContent = sub;
        // Very wide logo shown whole: suggest an icon-only version
        ui.wide_tip.hidden = !(fill_k() >= 2.5 && st.k < 1.5);
    }

    function sync_mode_buttons() {
        var centered = st.x === 0 && st.y === 0;
        modal_el.querySelectorAll('[data-mode]').forEach(function (b) {
            var k = b.dataset.mode === 'fill' ? fill_k() : FIT_K;
            b.setAttribute('aria-pressed', String(centered && Math.abs(st.k - k) < 0.01));
        });
    }

    function redraw() {
        raf = 0;
        if (!work) { return; }
        draw_stage();
        render_out();
        draw_previews();
        update_quality();
        sync_mode_buttons();
    }
    function schedule() {
        if (!raf) {
            raf = requestAnimationFrame(redraw);
        }
    }

    // ---------- Editor open / close ----------
    function open_editor() {
        ui.src.textContent = 'Your image: ' + src.w + ' × ' + src.h + ' px ' + type_label(src.type);
        just_applied = false;
        sync_controls();
        modal.show();
    }

    modal_el.addEventListener('shown.bs.modal', function () {
        size_stage();
        redraw();
        ui.stage.focus();
    });
    modal_el.addEventListener('hidden.bs.modal', function () {
        // Cancel: the form keeps the logo it had before
        if (!just_applied) {
            restore_input();
        }
    });

    function apply() {
        render_out();
        out.toBlob(function (blob) {
            if (!blob) {
                show_error('We couldn\'t prepare this logo. Please try another file.');
                modal.hide();
                return;
            }
            var file = new File([blob], 'logo.png', { type: 'image/png' });
            applied = { file: file, src: src, st: Object.assign({}, st) };
            set_input_file(file);
            // Let the page know the form changed (shows the save bar)
            ignore_change = true;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            ignore_change = false;

            show_in_previews(URL.createObjectURL(blob));
            // Lets the page say so if the save fails for another field and the logo has to be added again
            var pending = root.querySelector('[data-logo-pending]');
            if (pending) { pending.value = '1'; }
            status.textContent = root.dataset.readyText || 'New logo ready. Save to upload it.';
            if (drop_label) { drop_label.textContent = 'Replace logo'; }
            adjust.hidden = false;
            just_applied = true;
            modal.hide();
        }, 'image/png');
    }

    // ---------- Events: the logo field ----------
    input.addEventListener('change', function () {
        if (ignore_change) { return; }
        handle_file(input.files[0]);
    });
    ['dragenter', 'dragover'].forEach(function (name) {
        drop.addEventListener(name, function (event) { event.preventDefault(); drop.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (name) {
        drop.addEventListener(name, function (event) { event.preventDefault(); drop.classList.remove('is-over'); });
    });
    drop.addEventListener('drop', function (event) {
        handle_file(event.dataTransfer.files && event.dataTransfer.files[0]);
    });
    // Paste an image anywhere on the page, unless the cursor is in a text field
    document.addEventListener('paste', function (event) {
        var el = document.activeElement;
        if (modal_el.classList.contains('show') || !event.clipboardData
            || (el && (el.isContentEditable || /^(TEXTAREA|SELECT)$/.test(el.tagName) || (el.tagName === 'INPUT' && el.type !== 'file')))) {
            return;
        }
        var file = Array.prototype.find.call(event.clipboardData.files || [], function (f) { return /^image\//.test(f.type); });
        if (file) {
            event.preventDefault();
            handle_file(file);
            root.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
    adjust.addEventListener('click', function () {
        if (!applied) { return; }
        src = applied.src;
        st = Object.assign({}, applied.st);
        build_work();
        open_editor();
    });

    // ---------- Events: the editor ----------
    $le('apply').addEventListener('click', apply);
    ui.zoom.addEventListener('input', function () {
        var a = Math.log(K_MIN), b = Math.log(k_max());
        st.k = Math.exp(a + (b - a) * ui.zoom.value / 1000);
        clamp_pos();
        schedule();
    });
    $le('zoom-in').addEventListener('click', function () { set_k(st.k * 1.15); });
    $le('zoom-out').addEventListener('click', function () { set_k(st.k / 1.15); });
    modal_el.querySelectorAll('[data-mode]').forEach(function (b) {
        b.addEventListener('click', function () { set_mode(b.dataset.mode); });
    });
    function rotate() {
        st.rot = (st.rot + 90) % 360;
        var x = st.x;
        st.x = -st.y;
        st.y = x;
        build_work();
        clamp_pos();
        sync_zoom();
        schedule();
    }
    $le('rotate').addEventListener('click', rotate);
    $le('center').addEventListener('click', function () { st.x = 0; st.y = 0; schedule(); });
    $le('reset').addEventListener('click', function () { st = default_state(); build_work(); sync_controls(); schedule(); });
    ui.trim.addEventListener('change', function () { st.trim = ui.trim.checked; build_work(); clamp_pos(); sync_zoom(); schedule(); });
    modal_el.querySelectorAll('input[name=le_bg]').forEach(function (r) {
        r.addEventListener('change', function () { st.bg = r.value; schedule(); });
    });
    ui.color.addEventListener('input', function () {
        st.color = ui.color.value;
        st.bg = 'custom';
        modal_el.querySelector('input[name=le_bg][value=custom]').checked = true;
        schedule();
    });

    // Drag to move, two fingers to zoom
    var pts = new Map(), drag = null, pinch = null;
    function frame_css() { return ui.stage.getBoundingClientRect().width * FRAME; }
    function pinch_dist() {
        var p = Array.from(pts.values());
        return Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y) || 1;
    }
    ui.stage.addEventListener('pointerdown', function (e) {
        ui.stage.setPointerCapture(e.pointerId);
        pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
        if (pts.size === 1) { drag = { x: st.x, y: st.y }; }
        if (pts.size === 2) { pinch = { d: pinch_dist(), k: st.k }; drag = null; }
        ui.stage.classList.add('is-dragging');
    });
    ui.stage.addEventListener('pointermove', function (e) {
        var p = pts.get(e.pointerId);
        if (!p) { return; }
        var dx = e.clientX - p.x, dy = e.clientY - p.y;
        p.x = e.clientX;
        p.y = e.clientY;
        if (pts.size === 1 && drag) {
            var F = frame_css(), snap = 6 / F;
            drag.x += dx / F;
            drag.y += dy / F;
            // Snap to the middle, with a guide line
            st.x = Math.abs(drag.x) < snap ? 0 : drag.x;
            st.y = Math.abs(drag.y) < snap ? 0 : drag.y;
            guide.x = st.x === 0;
            guide.y = st.y === 0;
            clamp_pos();
            schedule();
        } else if (pts.size === 2 && pinch) {
            set_k(pinch.k * pinch_dist() / pinch.d);
        }
    });
    function pointer_end(e) {
        pts.delete(e.pointerId);
        if (pts.size < 2) { pinch = null; }
        if (pts.size === 1) { drag = { x: st.x, y: st.y }; }
        if (!pts.size) {
            drag = null;
            guide = { x: false, y: false };
            ui.stage.classList.remove('is-dragging');
            schedule();
        }
    }
    ui.stage.addEventListener('pointerup', pointer_end);
    ui.stage.addEventListener('pointercancel', pointer_end);
    ui.stage.addEventListener('wheel', function (e) {
        e.preventDefault();
        set_k(st.k * Math.exp(-e.deltaY * 0.0015));
    }, { passive: false });
    ui.stage.addEventListener('keydown', function (e) {
        var step = e.shiftKey ? 0.05 : 0.01, used = true;
        switch (e.key) {
            case 'ArrowLeft': st.x -= step; break;
            case 'ArrowRight': st.x += step; break;
            case 'ArrowUp': st.y -= step; break;
            case 'ArrowDown': st.y += step; break;
            case '+': case '=': set_k(st.k * 1.08); break;
            case '-': case '_': set_k(st.k / 1.08); break;
            case '0': set_mode('fit'); break;
            case 'r': case 'R': rotate(); break;
            default: used = false;
        }
        if (used) {
            e.preventDefault();
            clamp_pos();
            schedule();
        }
    });
    window.addEventListener('resize', function () {
        if (modal_el.classList.contains('show')) {
            size_stage();
            schedule();
        }
    });
})();
