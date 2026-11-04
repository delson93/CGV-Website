(() => {
  'use strict';
  const root = document.documentElement;
  const menu = document.querySelector('.menu');
  const nav = document.querySelector('#navigation');
  const closeMenu = () => { nav.classList.remove('open'); menu.setAttribute('aria-expanded', 'false'); };
  menu.addEventListener('click', () => { const open = nav.classList.toggle('open'); menu.setAttribute('aria-expanded', String(open)); });
  nav.addEventListener('click', e => { if(e.target.closest('a')) closeMenu(); });
  document.addEventListener('keydown', e => { if(e.key === 'Escape') { closeMenu(); } });
  root.classList.add('nav-ready');
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  let paused = reduced.matches || Boolean(navigator.connection?.saveData);
  let player;
  const toggle = document.querySelector('.motion-toggle');
  const video = document.querySelector('[data-video]');
  const sync = () => {
    root.classList.toggle('motion-paused', paused);
    toggle.textContent = paused ? 'Play motion' : 'Pause motion';
    toggle.setAttribute('aria-pressed', String(paused));
    if(player?.pauseVideo) { if(paused || document.hidden) player.pauseVideo(); else { player.mute(); player.playVideo(); } }
  };
  toggle.hidden = false;
  toggle.addEventListener('click', () => { paused = !paused; sync(); if(!paused) loadVideo(); });
  reduced.addEventListener('change', e => { paused = e.matches; sync(); });
  document.addEventListener('visibilitychange', sync);
  sync();
  const header = document.querySelector('header');
  let frame;
  const scroll = () => {
    if(frame) return;
    frame = requestAnimationFrame(() => {
      const range = root.scrollHeight - innerHeight;
      root.style.setProperty('--p', range > 0 ? scrollY / range : 0);
      header.classList.toggle('solid', scrollY > 40);
      frame = null;
    });
  };
  addEventListener('scroll', scroll, {passive:true}); scroll();
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if(entry.isIntersecting) { if(!paused) entry.target.classList.add('reveal-in'); observer.unobserve(entry.target); }
    }), {threshold:.08});
    document.querySelectorAll('[data-reveal]').forEach(el => observer.observe(el));
  }
  const viewer = document.querySelector('#viewer');
  if(viewer?.showModal) {
    document.querySelectorAll('[data-photo]').forEach(a => a.addEventListener('click', e => {
      e.preventDefault(); const img = a.querySelector('img');
      viewer.querySelector('img').src = a.href;
      viewer.querySelector('img').alt = img.alt;
      viewer.querySelector('p').textContent = img.alt;
      viewer.showModal();
    }));
    viewer.querySelector('button').addEventListener('click', () => viewer.close());
    viewer.addEventListener('click', e => { if(e.target === viewer) viewer.close(); });
  }
  let requested = false;
  function loadVideo() {
    if(requested || paused || !/^[\w-]{11}$/.test(video.dataset.video)) return;
    requested = true;
    const init = () => {
      const mount = document.createElement('div'); video.append(mount);
      player = new YT.Player(mount, {
        host:'https://www.youtube-nocookie.com', videoId:video.dataset.video,
        playerVars:{autoplay:1, mute:1, controls:0, playsinline:1, loop:1, playlist:video.dataset.video, disablekb:1, origin:location.origin},
        events:{onReady:e => { const iframe = e.target.getIframe(); iframe.tabIndex=-1; iframe.title='Venue background video'; iframe.setAttribute('aria-hidden','true'); e.target.mute(); sync(); },
          onStateChange:e => video.classList.toggle('ready', e.data === 1 || e.data === 2),
          onError:() => video.classList.remove('ready')}
      });
    };
    if(window.YT?.Player) init(); else {
      window.onYouTubeIframeAPIReady = init;
      const script = document.createElement('script'); script.src='https://www.youtube.com/iframe_api'; script.async=true;
      document.head.append(script);
    }
  }
  // Keep the image as the immediate hero; defer the optional third-party player.
  if(document.readyState === 'complete') loadVideo(); else addEventListener('load', loadVideo, {once:true});
})();
