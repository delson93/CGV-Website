/* Charles Gala Venue — public script v3
   Progressive: every feature works without GSAP; GSAP + ScrollTrigger (loaded from cdnjs) add the cinematic layer.
   Motion respects prefers-reduced-motion, Save-Data and the on-page "Pause motion" toggle. */
(() => {
  'use strict';
  const copy = JSON.parse(document.getElementById('venue-copy')?.textContent || '{}');
  const root = document.documentElement;
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const hasGsap = typeof window.gsap !== 'undefined';
  const hasST = hasGsap && typeof window.ScrollTrigger !== 'undefined';
  if (hasST) gsap.registerPlugin(ScrollTrigger);
  const fine = matchMedia('(pointer: fine)');
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const desktop = matchMedia('(min-width: 821px)');
  let paused = reduced.matches || Boolean(navigator.connection?.saveData);
  let ctx = null;              // gsap.context so the motion layer can be torn down and rebuilt
  let player;                  // YouTube background player
  let dust = null;             // gold dust canvas controller

  /* ---------- navigation ---------- */
  const header = $('header');
  const menu = $('.menu');
  const nav = $('#navigation');
  const setMenu = open => {
    open = Boolean(open && !desktop.matches);
    if (open) header.classList.remove('hide');
    header.classList.toggle('open', open);
    root.classList.toggle('menu-open', open);
    menu.setAttribute('aria-expanded', String(open));
    $('.menu-label', menu).textContent = open ? (copy.copy_menu_close || 'Close') : (copy.copy_menu_open || 'Menu');
    if (!desktop.matches) $$('main, footer, .dock').forEach(el => { el.inert = open; });
    else $$('main, footer, .dock').forEach(el => { el.inert = false; });
  };
  menu.addEventListener('click', () => setMenu(!header.classList.contains('open')));
  nav.addEventListener('click', e => { if (e.target.closest('a')) setMenu(false); });
  document.addEventListener('keydown', e => {
    if (!header.classList.contains('open')) return;
    if (e.key === 'Escape') { setMenu(false); menu.focus(); }
    if (e.key === 'Tab' && !desktop.matches) {
      const focusable = [menu, ...$$('a,button', nav)];
      const first = focusable[0], last = focusable[focusable.length - 1];
      if (e.shiftKey && (document.activeElement === first || !focusable.includes(document.activeElement))) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });
  root.classList.add('nav-ready');
  desktop.addEventListener('change', () => { setMenu(false); header.classList.remove('hide'); });

  /* ---------- scroll state: progress bar, glass header, hide on scroll down, contact dock ---------- */
  const dock = $('.dock');
  const hero = $('.hero');
  let lastY = scrollY, frame;
  const onScroll = () => {
    if (frame) return;
    frame = requestAnimationFrame(() => {
      const y = scrollY;
      const range = root.scrollHeight - innerHeight;
      root.style.setProperty('--p', range > 0 ? y / range : 0);
      header.classList.toggle('solid', y > 40);
      header.classList.toggle('hide', desktop.matches && y > lastY && y > 320 && !header.classList.contains('open'));
      dock?.classList.toggle('show', y > (hero?.offsetHeight || 600) * .6);
      lastY = y;
      frame = null;
    });
  };
  addEventListener('scroll', onScroll, { passive: true }); onScroll();

  /* ---------- motion toggle ---------- */
  const toggle = $('.motion-toggle');
  const video = $('[data-video]');
  const sync = () => {
    root.classList.toggle('motion-paused', paused);
    toggle.textContent = paused ? (copy.copy_motion_play || 'Play motion') : (copy.copy_motion_pause || 'Pause motion');
    toggle.setAttribute('aria-pressed', String(paused));
    if (player?.pauseVideo) { if (paused || document.hidden) player.pauseVideo(); else { player.mute(); player.playVideo(); } }
    if (paused) { teardownMotion(); dust?.stop(); if (hasGsap) { gsap.killTweensOf($$('[data-magnetic], .faq-body')); gsap.set($$('[data-magnetic]'), { clearProps: 'transform' }); gsap.set($$('.faq-body'), { clearProps: 'height,opacity' }); } } else { buildMotion(); dust?.start(); }
  };
  toggle.hidden = false;
  toggle.addEventListener('click', () => { paused = !paused; sync(); if (!paused) loadVideo(); });
  reduced.addEventListener('change', e => { paused = e.matches; sync(); });
  document.addEventListener('visibilitychange', () => { if (player?.pauseVideo) { if (document.hidden || paused) player.pauseVideo(); else player.playVideo(); } if (document.hidden) dust?.stop(); else if (!paused) dust?.start(); });

  /* ---------- heading splitter (lines → words) ---------- */
  const splitHeading = h => {
    if (h.dataset.splitDone) return;
    const lines = [[]];
    h.childNodes.forEach(node => {
      if (node.nodeType === 3) {
        node.textContent.split(/(\s+)/).forEach(part => { if (part.trim()) lines[lines.length - 1].push(part); });
      } else if (node.nodeName === 'BR') {
        lines.push([]);
      } else if (node.nodeType === 1) {
        lines[lines.length - 1].push(node);
      }
    });
    h.textContent = '';
    lines.forEach(words => {
      const ln = document.createElement('span'); ln.className = 'ln';
      words.forEach((w, i) => {
        const ch = document.createElement('span'); ch.className = 'ch';
        if (typeof w === 'string') ch.textContent = w; else ch.append(w);
        ln.append(ch);
        if (i < words.length - 1) ln.append(' ');
      });
      h.append(ln);
    });
    h.dataset.splitDone = '1';
  };

  /* ---------- gold dust (canvas) ---------- */
  const makeDust = canvas => {
    const c = canvas.getContext('2d');
    if (!c) return { start() {}, stop() {} };
    let w, h, raf, parts = [], visible = true, running = false;
    const dpr = Math.min(devicePixelRatio || 1, 2);
    const resize = () => {
      w = canvas.clientWidth; h = canvas.clientHeight;
      canvas.width = w * dpr; canvas.height = h * dpr; c.setTransform(dpr, 0, 0, dpr, 0, 0);
      const n = Math.round(Math.min(90, w * h / 16000));
      parts = Array.from({ length: n }, () => spawn(true));
    };
    const spawn = seed => ({ x: Math.random() * w, y: seed ? Math.random() * h : h + 10, r: .6 + Math.random() * 1.8, s: .15 + Math.random() * .45, d: (Math.random() - .5) * .3, a: Math.random() * Math.PI * 2, o: .3 + Math.random() * .6 });
    const tick = () => {
      c.clearRect(0, 0, w, h);
      for (const p of parts) {
        p.y -= p.s; p.a += .01; p.x += Math.sin(p.a) * .25 + p.d;
        if (p.y < -10 || p.x < -10 || p.x > w + 10) Object.assign(p, spawn(false));
        const fade = Math.min(1, (h - p.y) / 120) * Math.min(1, p.y / 80);
        const g = c.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 3);
        g.addColorStop(0, `rgba(255,243,196,${p.o * fade})`); g.addColorStop(1, 'rgba(236,212,150,0)');
        c.fillStyle = g; c.beginPath(); c.arc(p.x, p.y, p.r * 3, 0, Math.PI * 2); c.fill();
      }
      raf = requestAnimationFrame(tick);
    };
    const start = () => { if (running || !visible) return; running = true; raf = requestAnimationFrame(tick); };
    const stop = () => { running = false; cancelAnimationFrame(raf); };
    if ('IntersectionObserver' in window) new IntersectionObserver(([e]) => { visible = e.isIntersecting; if (!visible) stop(); else if (!paused && !document.hidden) start(); }).observe(canvas);
    addEventListener('resize', resize, { passive: true }); resize();
    return { start, stop };
  };
  const dustCanvas = $('.dust');
  if (dustCanvas) dust = makeDust(dustCanvas);

  /* ---------- preloader ---------- */
  const loader = $('.loader');
  const finishLoading = () => { root.classList.remove('loading'); loader?.classList.add('done'); if (hasST) ScrollTrigger.refresh(); };
  const runLoader = () => {
    if (!loader || paused || !hasGsap) { finishLoading(); return; }
    root.classList.add('loading');
    setTimeout(finishLoading, 3500); // Never leave navigation blocked if the animation is interrupted.
    const count = $('.loader-count', loader), line = $('.loader-line', loader);
    const n = { v: 0 };
    gsap.timeline({ defaults: { ease: 'power3.out' }, onComplete: finishLoading })
      .to($('img', loader), { opacity: 1, scale: 1, duration: .8 }, 0)
      .to($('.loader-name', loader), { opacity: 1, duration: .8 }, .15)
      .to(count, { opacity: 1, duration: .4 }, .3)
      .to(n, { v: 100, duration: 1.5, ease: 'power2.inOut', onUpdate: () => { count.textContent = Math.round(n.v); line.style.setProperty('--load', n.v / 100); } }, .3)
      .to($('.loader-inner', loader), { yPercent: -30, opacity: 0, duration: .6, ease: 'power3.in' }, '-=.15')
      .to(loader, { yPercent: -100, duration: .9, ease: 'expo.inOut' }, '-=.25');
  };

  /* ---------- cursor ---------- */
  const cursor = $('.cursor');
  if (cursor && fine.matches && hasGsap && !paused) {
    cursor.classList.add('cursor-ready');
    const dot = $('.cursor-dot', cursor), ring = $('.cursor-ring', cursor);
    const dotX = gsap.quickTo(dot, 'x', { duration: .08 }), dotY = gsap.quickTo(dot, 'y', { duration: .08 });
    const rX = gsap.quickTo(ring, 'x', { duration: .4, ease: 'power3' }), rY = gsap.quickTo(ring, 'y', { duration: .4, ease: 'power3' });
    cursor.classList.add('is-hidden');
    addEventListener('pointermove', e => {
      if (paused) return;
      dotX(e.clientX); dotY(e.clientY); rX(e.clientX); rY(e.clientY);
      cursor.classList.remove('is-hidden');
      const t = e.target;
      const viewTarget = t.closest?.('[data-photo]');
      cursor.classList.toggle('is-view', Boolean(viewTarget));
      cursor.classList.toggle('is-link', !viewTarget && Boolean(t.closest?.('a,button,summary,label,select,input,[data-magnetic]')));
      $('em', ring).textContent = viewTarget ? (copy.copy_cursor_view || 'View') : '';
    }, { passive: true });
    document.addEventListener('mouseleave', () => cursor.classList.add('is-hidden'));
  } else cursor?.remove();

  /* ---------- magnetic buttons ---------- */
  if (fine.matches && hasGsap) {
    $$('[data-magnetic]').forEach(el => {
      const x = gsap.quickTo(el, 'x', { duration: .5, ease: 'power3' }), y = gsap.quickTo(el, 'y', { duration: .5, ease: 'power3' });
      el.addEventListener('pointermove', e => {
        if (paused) return;
        const r = el.getBoundingClientRect();
        x((e.clientX - r.left - r.width / 2) * .25); y((e.clientY - r.top - r.height / 2) * .35);
      });
      el.addEventListener('pointerleave', () => { x(0); y(0); });
    });
  }

  /* ---------- the GSAP motion layer ---------- */
  function teardownMotion() {
    if (ctx) { ctx.revert(); ctx = null; }
    root.classList.remove('gallery-motion');
    $$('[data-count]').forEach(el => { el.textContent = (parseInt(el.dataset.count, 10) || 0).toLocaleString('en-IN'); });
  }
  function buildMotion() {
    if (!hasST || ctx || paused) return;
    ctx = gsap.context(() => {
      const mm = gsap.matchMedia();

      // split headings once (DOM change persists; fine to keep when motion is paused)
      $$('[data-split]').forEach(splitHeading);

      // section reveals
      $$('[data-reveal]').forEach(el => {
        const heads = $$('[data-split] .ch', el);
        const tl = gsap.timeline({ scrollTrigger: { trigger: el, start: 'top 82%', once: true } });
        const rule = $('.rule', el);
        if (rule) tl.from(rule, { scaleX: 0, duration: 1, ease: 'power3.out' }, 0);
        if (heads.length) tl.from(heads, { yPercent: 115, rotate: 3, duration: 1.1, ease: 'power4.out', stagger: .05 }, 0);
        const rest = $$('.head > p, .space-copy > :not(.features), .features li, .pool .wrap > *, .welcome .wrap > div > :not(.highlights), .highlights li, .filters, .faq-list details, .visit-copy > :not(.details), .details > div', el)
          .filter(n => !n.closest('[data-split]') && !n.matches('[data-split], .rule'));
        if (rest.length) tl.from(rest, { y: 36, opacity: 0, duration: 1, ease: 'power3.out', stagger: .06 }, .15);
        const photo = $('.photo.arch', el);
        if (photo) tl.from(photo, { clipPath: 'inset(45% 0 45% 0 round 999px 999px 10px 10px)', duration: 1.4, ease: 'expo.out' }, 0);
      });

      // parallax photos (translate + scale are separate properties, so the Ken Burns zoom still runs)
      $$('.photo img[data-par]').forEach(img => {
        const kind = img.dataset.par;
        gsap.fromTo(img, { yPercent: kind === 'hero' ? 0 : -10 }, {
          yPercent: kind === 'hero' ? 22 : 10, ease: 'none',
          scrollTrigger: { trigger: img.closest('.photo'), start: kind === 'hero' ? 'top top' : 'top bottom', end: 'bottom top', scrub: true }
        });
      });
      if (hero) gsap.to('.hero-grid', { yPercent: 18, opacity: 0, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom 30%', scrub: true } });

      // counters
      $$('[data-count]').forEach(el => {
        const end = parseInt(el.dataset.count, 10) || 0; const o = { v: 0 };
        gsap.to(o, { v: end, duration: 2, ease: 'power3.out', scrollTrigger: { trigger: el, start: 'top 90%', once: true }, onUpdate: () => { el.textContent = Math.round(o.v).toLocaleString('en-IN'); } });
      });

      // marquee reacts to scroll velocity
      // (the skew goes on .band: .track's transform belongs to its CSS keyframe animation)
      const track = $('.band');
      if (track) {
        const skew = gsap.quickTo(track, 'skewX', { duration: .6, ease: 'power3' });
        ScrollTrigger.create({ onUpdate: self => skew(gsap.utils.clamp(-10, 10, self.getVelocity() / -220)) });
      }

      // pool text drifts slower than its background
      const poolWrap = $('.pool .wrap');
      if (poolWrap) gsap.fromTo(poolWrap, { y: 60 }, { y: -60, ease: 'none', scrollTrigger: { trigger: '.pool', start: 'top bottom', end: 'bottom top', scrub: true } });

      // active nav link
      $$('.nav-links a[href^="#"]').forEach(a => {
        const sec = $(a.getAttribute('href'));
        if (sec) ScrollTrigger.create({ trigger: sec, start: 'top 45%', end: 'bottom 45%', onToggle: s => a.classList.toggle('active', s.isActive) });
      });

      // horizontal gallery rail — desktop only; narrow screens use the CSS grid
      mm.add('(min-width: 821px)', () => {
        const rail = $('.track-h'), section = $('.gallery');
        if (!rail || !section) return;
        root.classList.add('gallery-motion');
        const dist = () => Math.max(0, rail.scrollWidth - innerWidth);
        gsap.to(rail, { x: () => -dist(), ease: 'none', scrollTrigger: { trigger: section, start: 'top top', end: () => '+=' + dist(), pin: true, scrub: .6, invalidateOnRefresh: true, anticipatePin: 1 } });
        gsap.from($$('.track-h > *'), { xPercent: 20, opacity: 0, duration: 1.2, ease: 'power3.out', stagger: .08, scrollTrigger: { trigger: section, start: 'top 60%', once: true } });
        return () => root.classList.remove('gallery-motion');
      });
    });
    requestAnimationFrame(() => ScrollTrigger.refresh());
  }

  // fallback reveals when GSAP is unavailable or motion is paused from the start
  if (!hasST && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => entries.forEach(en => { if (en.isIntersecting) { if (!paused) en.target.classList.add('reveal-in'); io.unobserve(en.target); } }), { threshold: .08 });
    $$('[data-reveal]').forEach(el => io.observe(el));
    $$('[data-count]').forEach(el => { el.textContent = (parseInt(el.dataset.count, 10) || 0).toLocaleString('en-IN'); });
  }
  if (paused) $$('[data-count]').forEach(el => { el.textContent = (parseInt(el.dataset.count, 10) || 0).toLocaleString('en-IN'); });

  /* ---------- gallery filters ---------- */
  const filters = $$('.filters button');
  const items = $$('.track-h > [data-cat]');
  filters.forEach(btn => btn.addEventListener('click', () => {
    filters.forEach(b => { b.classList.toggle('on', b === btn); b.setAttribute('aria-pressed', String(b === btn)); });
    const f = btn.dataset.filter;
    items.forEach(it => it.classList.toggle('is-hidden', f !== 'all' && it.dataset.cat !== f));
    const shown = items.filter(it => !it.classList.contains('is-hidden'));
    if (hasGsap && !paused) gsap.fromTo(shown, { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: .7, ease: 'power3.out', stagger: .05, clearProps: 'opacity,transform' });
    if (hasST) { teardownMotion(); if (!paused) buildMotion(); ScrollTrigger.refresh(); }
  }));

  /* ---------- lightbox with prev / next ---------- */
  const viewer = $('#viewer');
  if (viewer?.showModal) {
    const img = $('img', viewer), cap = $('figcaption p', viewer), counter = $('.counter', viewer);
    let list = [], index = 0;
    const show = i => {
      index = (i + list.length) % list.length;
      const a = list[index];
      img.classList.remove('swap'); void img.offsetWidth;
      img.src = a.href; img.alt = a.dataset.caption || ''; img.classList.add('swap');
      cap.textContent = a.dataset.caption || '';
      counter.textContent = `${index + 1} / ${list.length}`;
      $('.prev', viewer).hidden = $('.next', viewer).hidden = list.length < 2;
    };
    $$('[data-photo]').forEach(a => a.addEventListener('click', e => {
      e.preventDefault();
      list = $$('[data-photo]').filter(p => !p.classList.contains('is-hidden'));
      show(list.indexOf(a));
      viewer.showModal();
    }));
    $('.close', viewer).addEventListener('click', () => viewer.close());
    $('.prev', viewer).addEventListener('click', () => show(index - 1));
    $('.next', viewer).addEventListener('click', () => show(index + 1));
    viewer.addEventListener('click', e => { if (e.target === viewer) viewer.close(); });
    viewer.addEventListener('keydown', e => { if (e.key === 'ArrowLeft') show(index - 1); if (e.key === 'ArrowRight') show(index + 1); });
    let sx = 0;
    viewer.addEventListener('pointerdown', e => { sx = e.clientX; }, { passive: true });
    viewer.addEventListener('pointerup', e => { const dx = e.clientX - sx; if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1)); }, { passive: true });
  }

  /* ---------- FAQ accordion (animated height) ---------- */
  $$('.faq details').forEach(d => {
    const summary = $('summary', d), body = $('.faq-body', d);
    summary.addEventListener('click', e => {
      if (!hasGsap || paused) return; // native behaviour
      e.preventDefault();
      gsap.killTweensOf(body);
      if (d.open) {
        gsap.to(body, { height: 0, opacity: 0, duration: .45, ease: 'power3.inOut', onComplete: () => { d.open = false; gsap.set(body, { clearProps: 'all' }); } });
      } else {
        d.open = true;
        gsap.from(body, { height: 0, opacity: 0, duration: .6, ease: 'power3.out', clearProps: 'all' });
      }
    });
  });

  /* ---------- quick enquiry composer (WhatsApp) ---------- */
  const booking = $('.booking');
  if (booking) {
    const out = $('.wa-compose', booking), base = booking.dataset.wa || '';
    const compose = () => {
      const occasion = $('input[name=occasion]:checked', booking)?.value || 'an event';
      const guests = $('select[name=guests]', booking)?.value || '';
      const date = $('input[name=date]', booking)?.value;
      const when = date ? new Date(date + 'T00:00:00').toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' }) : (copy.copy_booking_date_fallback || 'a date to be confirmed');
      const values = {venue: copy.site_name || '', occasion, guests, date: when};
      const msg = (copy.copy_booking_message || '{venue}: {occasion}, {guests}, {date}').replace(/\{(venue|occasion|guests|date)\}/g, (_, key) => values[key]);
      if (base) out.href = base + encodeURIComponent(msg);
    };
    booking.addEventListener('change', compose);
    booking.addEventListener('submit', e => { e.preventDefault(); out.click(); });
    const dateInput = $('input[name=date]', booking);
    if (dateInput) { const today = new Date(); today.setMinutes(today.getMinutes() - today.getTimezoneOffset()); dateInput.min = today.toISOString().slice(0, 10); }
    compose();
  }

  // The map is server-rendered with native lazy loading; no click handler is required.

  /* ---------- background video (unchanged behaviour: image first, player deferred) ---------- */
  let requested = false;
  function loadVideo() {
    if (!video || requested || paused || !/^[\w-]{11}$/.test(video.dataset.video)) return;
    requested = true;
    const init = () => {
      const mount = document.createElement('div'); video.append(mount);
      player = new YT.Player(mount, {
        host: 'https://www.youtube-nocookie.com', videoId: video.dataset.video,
        playerVars: { autoplay: 1, mute: 1, controls: 0, playsinline: 1, loop: 1, playlist: video.dataset.video, disablekb: 1, origin: location.origin },
        events: {
          onReady: e => { const iframe = e.target.getIframe(); iframe.tabIndex = -1; iframe.title = 'Venue background video'; iframe.setAttribute('aria-hidden', 'true'); e.target.mute(); sync(); },
          onStateChange: e => video.classList.toggle('ready', e.data === 1 || e.data === 2),
          onError: () => video.classList.remove('ready')
        }
      });
    };
    if (window.YT?.Player) init(); else {
      window.onYouTubeIframeAPIReady = init;
      const script = document.createElement('script'); script.src = 'https://www.youtube.com/iframe_api'; script.async = true;
      document.head.append(script);
    }
  }

  // Keyboard focus must reach every gallery item even when the scroll rail is pinned.
  $('.rail')?.addEventListener('focusin', () => { if (ctx && desktop.matches) { paused = true; sync(); } });

  /* ---------- boot ---------- */
  sync();
  runLoader();
  if (document.readyState === 'complete') loadVideo(); else addEventListener('load', loadVideo, { once: true });
})();
