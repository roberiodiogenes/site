    // Cursor
    const cur=document.getElementById('cursor'),cAn=document.getElementById('cursorAnel');
    let mx=0,my=0,ax=0,ay=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px'});
    (function t(){ax+=(mx-ax)*.11;ay+=(my-ay)*.11;cAn.style.left=ax+'px';cAn.style.top=ay+'px';requestAnimationFrame(t)})();

    // Nav / topo
    const nav=document.getElementById('nav'),topoBtn=document.getElementById('topoBtn');
    window.addEventListener('scroll',()=>{nav.classList.toggle('scrolled',scrollY>40);topoBtn.classList.toggle('v',scrollY>500)},{passive:true});

    // Hero BG
    const hBg=document.getElementById('heroBg');
    setTimeout(()=>hBg.classList.add('carregada'),300);
    window.addEventListener('scroll',()=>{hBg.style.transform=`translateY(${scrollY*.18}px)`;},{passive:true});

    // Partículas de luz subindo do abismo
    const hP=document.getElementById('hParticulas');
    for(let i=0;i<25;i++){
      const p=document.createElement('div');
      p.className='hp';
      const s=1+Math.random()*2;
      p.style.cssText=`left:${40+Math.random()*20}%;width:${s}px;height:${s}px;--dur:${8+Math.random()*14}s;--del:${Math.random()*12}s`;
      hP.appendChild(p);
    }

    // Scroll reveal
    const obs=new IntersectionObserver(entries=>{
      entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('v');obs.unobserve(e.target)}});
    },{threshold:.07});
    document.querySelectorAll('.r,.re,.rd').forEach(el=>obs.observe(el));

    // ============================================================
    // COUNTDOWN — Junho 2026
    // ============================================================
      const d=Math.floor(diff/(1000*60*60*24));
      const h=Math.floor((diff%(1000*60*60*24))/(1000*60*60));
      const m=Math.floor((diff%(1000*60*60))/(1000*60));
      const s=Math.floor((diff%(1000*60))/1000);
      document.getElementById('cd-dias').textContent=String(d).padStart(3,'0');
      document.getElementById('cd-horas').textContent=String(h).padStart(2,'0');
      document.getElementById('cd-mins').textContent=String(m).padStart(2,'0');
      document.getElementById('cd-segs').textContent=String(s).padStart(2,'0');
    }
    // Formulário de pré-lançamento removido — livro já lançado,5000);
    }