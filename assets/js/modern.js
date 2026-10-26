(() => {
  const menu = document.querySelector('.menu');
  const nav = document.querySelector('#navigation');
  menu.addEventListener('click', () => {
    const expanded = menu.getAttribute('aria-expanded') !== 'true';
    menu.setAttribute('aria-expanded', String(expanded));
    nav.classList.toggle('open', expanded);
  });
  nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
    menu.setAttribute('aria-expanded', 'false'); nav.classList.remove('open');
  }));
  const viewer = document.querySelector('#viewer');
  document.querySelectorAll('[data-photo]').forEach(link => link.addEventListener('click', event => {
    if (typeof viewer.showModal !== 'function') return;
    event.preventDefault();
    viewer.querySelector('img').src = link.href;
    viewer.querySelector('img').alt = link.querySelector('img').alt;
    viewer.querySelector('p').textContent = link.querySelector('img').alt;
    viewer.showModal();
  }));
  viewer.querySelector('button').addEventListener('click', () => viewer.close());
  viewer.addEventListener('click', event => {if (event.target === viewer) viewer.close();});
})();
