    const cur=document.getElementById('cursor'),cAn=document.getElementById('cursorAnel');
    let mx=0,my=0,ax=0,ay=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px'});
    (function t(){ax+=(mx-ax)*.11;ay+=(my-ay)*.11;cAn.style.left=ax+'px';cAn.style.top=ay+'px';requestAnimationFrame(t)})();
    const nav=document.getElementById('nav'),topoBtn=document.getElementById('topoBtn');
    window.addEventListener('scroll',()=>{nav.classList.toggle('scrolled',scrollY>40);topoBtn.classList.toggle('v',scrollY>500)},{passive:true});
    const l3d=document.getElementById('livro3d');
    document.addEventListener('mousemove',e=>{if(window.innerWidth<768)return;const rx=-(e.clientY-innerHeight/2)/innerHeight*6;const ry=(e.clientX-innerWidth/2)/innerWidth*10;l3d.style.transform=`rotateX(${rx}deg) rotateY(${ry-15}deg)`});
    document.addEventListener('mouseleave',()=>{l3d.style.transform='rotateY(-15deg) rotateX(2deg)'});

    // Folhas caindo
    const folhasEl=document.getElementById('folhas');
    const emojis=['🍂','🍁'];
    for(let i=0;i<14;i++){
      const f=document.createElement('span');
      f.className='folha';
      f.textContent=emojis[i%2];
      f.style.cssText=`left:${Math.random()*100}%;--dur:${10+Math.random()*14}s;--del:${Math.random()*12}s;font-size:${.8+Math.random()*.8}rem`;
      folhasEl.appendChild(f);
    }

    const obs=new IntersectionObserver(entries=>{entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('v');obs.unobserve(e.target)}})},{threshold:.08});
    document.querySelectorAll('.r,.re,.rd').forEach(el=>obs.observe(el));

    function baixarAmostra(e){
      e.preventDefault();
      const btn=e.target.querySelector('button');
      btn.textContent='⏳ Preparando...';btn.disabled=true;
      setTimeout(()=>{const a=document.createElement('a');a.href='../img/caminhos-de-outono.jpg';a.download='Caminhos-de-Outono-Roberio-Diogenes.pdf';a.click();btn.textContent='✓ Download iniciado!';btn.style.background='var(--verde-musgo)';setTimeout(()=>{btn.textContent='🍂 Baixar Capítulo Grátis';btn.style.background='';btn.disabled=false;},4000);},700);
    }
    // Comentário gerenciado pelo livros-shared.js (window.enviarComentario)