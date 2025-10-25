(async function(){
  try{
    const mount = document.getElementById('site-footer');
    if(!mount) return;
    
    // Determine the correct path based on current location
    const isInBookingDir = window.location.pathname.includes('/booking/');
    const basePath = isInBookingDir ? '../' : './';
    
    // Load HTML partial
    const htmlResp = await fetch(`${basePath}partials/footer.html`, {cache: 'no-cache'});
    const html = await htmlResp.text();
    
    // Ensure CSS is applied once
    const styleId = 'sb-footer-css';
    if(!document.getElementById(styleId)){
      const cssResp = await fetch(`${basePath}css/footer.css`, {cache: 'no-cache'});
      const css = await cssResp.text();
      const style = document.createElement('style');
      style.id = styleId;
      style.textContent = css;
      document.head.appendChild(style);
    }
    
    mount.innerHTML = html;
  }catch(e){ 
    console.error('Footer load failed:', e); 
  }
})();
