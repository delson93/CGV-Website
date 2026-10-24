(() => {
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const fine = matchMedia('(pointer: fine)');
  const hero = document.querySelector('.hero-section');
  let frame = 0;
  hero.addEventListener('pointermove', event => {
    if (reduced.matches || !fine.matches || frame) return;
    frame = requestAnimationFrame(() => {
      const rect = hero.getBoundingClientRect();
      hero.style.setProperty('--mx', `${(event.clientX / rect.width - .5) * 16}px`);
      hero.style.setProperty('--my', `${((event.clientY - rect.top) / rect.height - .5) * 12}px`);
      frame = 0;
    });
  });
  hero.addEventListener('pointerleave', () => {hero.style.setProperty('--mx','0px');hero.style.setProperty('--my','0px');});
  const photos = [...document.querySelectorAll('.journey-photo')];
  const dialog = document.querySelector('#photo-viewer');
  let selected = 0;
  function select(index) {
    selected = (index + photos.length) % photos.length;
    photos.forEach((photo, i) => {
      let offset = (i - selected + photos.length) % photos.length;
      if (offset > 2) offset -= photos.length;
      photo.style.setProperty('--offset', offset);
      photo.classList.toggle('is-selected', i === selected);
      photo.setAttribute('aria-pressed', String(i === selected));
    });
    document.querySelector('#journey-caption').textContent = `${String(selected+1).padStart(2,'0')} / ${photos[selected].querySelector('img').alt}`;
  }
  document.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => select(selected + Number(button.dataset.step))));
  photos.forEach((photo,i) => photo.addEventListener('click', () => {
    if (selected !== i) { select(i); return; }
    dialog.querySelector('img').src = photo.querySelector('img').src;
    dialog.querySelector('img').alt = photo.querySelector('img').alt;
    dialog.querySelector('p').textContent = photo.querySelector('img').alt;
    dialog.showModal();
  }));
  dialog.querySelector('button').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  select(0);
  document.querySelector('.journey-stage').classList.add('is-enhanced');
})();
