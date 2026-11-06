(() => {
  document.body.classList.add('admin-ready');
  const toggle = document.querySelector('.sidebar-toggle');
  const setMenu = open => {
    open = Boolean(open && matchMedia('(max-width:760px)').matches);
    document.body.classList.toggle('nav-open', open);
    toggle?.setAttribute('aria-expanded', String(open));
    const label=toggle?.querySelector('span'); if(label) label.textContent=open?'Close':'Menu';
    const main=document.getElementById('workspace'); if(main) main.inert=open;
  };
  toggle?.addEventListener('click', () => setMenu(!document.body.classList.contains('nav-open')));
  document.addEventListener('keydown', e => { if(e.key === 'Escape' && document.body.classList.contains('nav-open')) {setMenu(false); toggle?.focus();} });
  matchMedia('(min-width:761px)').addEventListener('change', () => setMenu(false));
  let dirty = false;
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('input', () => { dirty=true; const state=form.querySelector('[data-save-state]'); if(state) state.textContent='You have unsaved changes.'; });
    form.addEventListener('submit', () => { dirty=false; });
  });
  window.addEventListener('beforeunload', e => { if(dirty) { e.preventDefault(); e.returnValue=''; } });
  const title=document.querySelector('[name=seo_meta_title]'), description=document.querySelector('[name=seo_meta_description]'), canonical=document.querySelector('[name=seo_canonical_url]');
  const preview=()=>{ for(const [el,id] of [[title,'preview-title'],[description,'preview-description'],[canonical,'preview-url']]){const target=document.getElementById(id); if(el&&target) target.textContent=el.value;} };
  [title,description,canonical].forEach(el=>el?.addEventListener('input',preview)); preview();
  [title,description].forEach(el=>{if(!el)return;const count=document.createElement('span');count.className='char-count';el.after(count);const sync=()=>count.textContent=`${Array.from(el.value).length} characters`;el.addEventListener('input',sync);sync();});
})();
