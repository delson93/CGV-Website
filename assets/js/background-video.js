(() => {
  const hero = document.querySelector('[data-video-id]');
  if (!hero) return;
  const layer = hero.querySelector('.hero-video');
  const button = hero.querySelector('.video-toggle');
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  let player, started = false, userPaused = false;
  function pause() {
    userPaused = true;
    player?.pauseVideo?.();
    layer.classList.remove('is-playing');
    button.textContent = 'Play video';
  }
  function start() {
    userPaused = false;
    if (player) { player.mute?.(); player.playVideo?.(); return; }
    if (started) return;
    started = true;
    const create = () => {
      player = new YT.Player('hero-player', {
        host: 'https://www.youtube-nocookie.com',
        videoId: hero.dataset.videoId,
        playerVars: {autoplay: 0, mute: 1, controls: 0, playsinline: 1, loop: 1, playlist: hero.dataset.videoId, disablekb: 1, fs: 0, rel: 0, origin: location.origin},
        events: {
          onReady: event => {
            const frame = event.target.getIframe();
            frame.setAttribute('tabindex', '-1');
            frame.setAttribute('title', 'Muted venue background video');
            event.target.mute();
            if (!userPaused) event.target.playVideo();
          },
          onStateChange: event => {
            const playing = event.data === 1 && !userPaused;
            if (userPaused && event.data === 1) player.pauseVideo();
            layer.classList.toggle('is-playing', playing);
            button.textContent = playing ? 'Pause video' : 'Play video';
          },
          onError: () => { layer.classList.remove('is-playing'); button.hidden = true; }
        }
      });
    };
    if (window.YT?.Player) create();
    else {
      const previous = window.onYouTubeIframeAPIReady;
      window.onYouTubeIframeAPIReady = () => {previous?.(); create();};
      const script = document.createElement('script');
      script.src = 'https://www.youtube.com/iframe_api';
      script.onerror = () => {started = false; button.hidden = true;};
      document.head.append(script);
    }
  }
  button.hidden = false;
  button.textContent = 'Play video';
  button.addEventListener('click', () => layer.classList.contains('is-playing') ? pause() : start());
  reduced.addEventListener('change', event => {if(event.matches) pause();});
  document.addEventListener('visibilitychange', () => {if(document.hidden && player) pause();});
  if (!reduced.matches && !navigator.connection?.saveData) {
    if (document.readyState === 'complete') start();
    else window.addEventListener('load', start, {once:true});
  }
})();
