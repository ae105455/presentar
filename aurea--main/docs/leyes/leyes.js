
// Carrusel 3D
(() => {
    const carousel = document.getElementById('carousel');
    const stage = document.getElementById('stage');
    const prevBtn = document.getElementById('prev');
    const nextBtn = document.getElementById('next');
    const playPauseBtn = document.getElementById('playPause');
    const indicador = document.getElementById('indicador');
    if (!carousel || !stage || !prevBtn || !nextBtn || !playPauseBtn || !indicador || !document.getElementById('moldeModal')) {
        return;
    }

    const moldeModal = new bootstrap.Modal(document.getElementById('moldeModal'));
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('moldeModalLabel');

    let items = Array.from(carousel.children);
    let itemCount = items.length;
    let currentIndex = 0;
    let radius = 0;

    let autoRotate = true;
    let autoTimer = null;
    const AUTO_INTERVAL = 3000;

    // Datos de los moldes auditivos con imágenes específicas
    const moldesData = [
        {
            title: "Manguera",
            imagenes: [
                "../leyes/manguera.png",
                "../leyes/manguera.png"
            ],
            subtitle: "Para pérdidas auditivas leves a moderadas",
            caracteristicas: [
                "Fabricado en acrílico o silicona suave",
                "Se adapta perfectamente al contorno del oído",
                "Disponible en diferentes colores",
                "Durabilidad y resistencia comprobada"
            ],
            ventajas: [
                "Sellado excelente que previene la retroalimentación",
                "Amplificación eficiente del sonido",
                "Fácil mantenimiento y limpieza",
                "Peizcado de Mangera"
            ]
        },
        {
            title: "Tapado Concha",
            imagenes: [
                "../leyes/concha3.png",
                "../leyes/concha4.png"
            ],
            subtitle: "Ideal para mayor ventilación del oído",
            caracteristicas: [
                "Diseño abierto que permite el paso del aire",
                "Parte superior recortada",
                "Material ligero y cómodo",
                "Reduce la sensación de oído tapado"
            ],
            ventajas: [
                "Permite que el oído 'respire' naturalmente",
                "Reduce la humedad en el canal auditivo",
                "Ideal para usuarios que sudan mucho",
                "Comodidad mejorada durante uso prolongado"
            ]
        },
        {
            title: "Molde Canal",
            imagenes: [
                "../leyes/canal.png",
                "../leyes/canal2.png"
            ],
            subtitle: "Diseño discreto y cómodo",
            caracteristicas: [
                "Se coloca profundamente en el canal auditivo",
                "Diseño más pequeño y menos visible",
                "Material suave y flexible",
                "Utiliza el pabellón auditivo natural"
            ],
            ventajas: [
                "Estéticamente más discreto",
                "Aprovecha la anatomía natural del oído",
                "Mejor calidad de sonido direccional",
                "Máxima comodidad en uso diario"
            ]
        },
        {
            title: "Cocha Biopor",
            imagenes: [
                "../leyes/concha.png",
                "../leyes/concha2.png"
            ],
            subtitle: "Permite el paso de aire natural",
            caracteristicas: [
                "Incluye pequeño orificio de ventilación",
                "Previene acumulación de humedad",
                "Disponible en diferentes diámetros",
                "Material transpirable"
            ],
            ventajas: [
                "Reduce el efecto de oclusión",
                "Mejora la calidad del sonido propio de la voz",
                "Experiencia auditiva más natural",
                "Ideal para usuarios sensibles"
            ]
        },
        {
            title: "THINTUBE",
            imagenes: [
                "../leyes/THINTUBE.png",
                "../leyes/THINTUBE2.png"
            ],
            subtitle: "Adaptado a necesidades específicas",
            caracteristicas: [
                "Diseño completamente personalizado",
                "Tecnología de impresión 3D",
                "Materiales premium seleccionados",
                "Ajuste milimétrico"
            ],
            ventajas: [
                "Máxima comodidad y adaptación",
                "Rendimiento auditivo optimizado",
                "Durabilidad superior",
                "Solución para casos complejos"
            ]
        },
        {
            title: "Molde Resistente",
            imagenes: [
                "../leyes/molde9.png",
                "../leyes/molde10.png"
            ],
            subtitle: "Para entornos exigentes",
            caracteristicas: [
                "Materiales de alta resistencia",
                "Protección contra humedad y polvo",
                "Diseño reforzado",
                "Tratamiento antibacteriano"
            ],
            ventajas: [
                "Larga vida útil en condiciones adversas",
                "Protección adicional para el dispositivo",
                "Ideal para actividades deportivas",
                "Mantenimiento simplificado"
            ]
        },
        {
            title: "Sleeve vented",
            imagenes: [
                "../leyes/venta.png",
                "../leyes/venta2.png"
            ],
            subtitle: "Máxima calidad y confort",
            caracteristicas: [
                "Materiales de última generación",
                "Tecnología antialérgica",
                "Diseño ergonómico avanzado",
                "Acabados de lujo"
            ],
            ventajas: [
                "Confort superior durante todo el día",
                "Estética premium",
                "Rendimiento auditivo excepcional",
                "Durabilidad garantizada"
            ]
        }
    ];

    function setupCarousel(){
        items = Array.from(carousel.children);
        itemCount = items.length;

        const itemWidth = items[0].offsetWidth;
        const theta = 360 / itemCount;
        radius = Math.round((itemWidth / 2) / Math.tan(Math.PI / itemCount));

        items.forEach((el, i) => {
            const angle = theta * i;
            el.style.transform = `rotateY(${angle}deg) translateZ(${radius}px)`;
            
            // Agregar evento click a cada item
            el.addEventListener('click', () => {
                const index = parseInt(el.dataset.index);
                showMoldeInfo(index);
            });
        });

        indicador.innerHTML = '';
        for(let i=0;i<itemCount;i++){
            const d = document.createElement('div');
            d.className = 'dot' + (i===currentIndex ? ' active' : '');
            d.dataset.i = i;
            d.addEventListener('click', ()=> goTo(parseInt(d.dataset.i)));
            indicador.appendChild(d);
        }

        rotateToIndex(currentIndex, 0);
    }

    function rotateToIndex(index, duration = 600){
        const theta = 360 / itemCount;
        const angle = -theta * index;

        carousel.style.transition = `transform ${duration}ms cubic-bezier(.2,.9,.25,1)`;
        carousel.style.transform = `translateZ(-${radius}px) rotateY(${angle}deg)`;

        items.forEach((el,i)=>{
            el.classList.toggle('dim', i !== index);
        });

        Array.from(indicador.children).forEach((d, i)=> 
            d.classList.toggle('active', i===index)
        );

        currentIndex = index;
    }

    function showMoldeInfo(index) {
        const molde = moldesData[index];
        
        // Crear galería de imágenes
        let imagenesHTML = '';
        if (molde.imagenes && molde.imagenes.length > 0) {
            imagenesHTML = `
                <div class="imagenes-gallery">
                    ${molde.imagenes.map(img => 
                        `<img src="${img}" alt="${molde.title}" class="img-fluid">`
                    ).join('')}
                </div>
            `;
        }

        const modalContent = `
            <div class="molde-info">
                <div class="molde-header">
                    <h2 class="molde-title">${molde.title}</h2>
                    <p class="molde-subtitle">${molde.subtitle}</p>
                    ${imagenesHTML}
                </div>
                
                <div class="molde-details">
                    <div class="detail-section">
                        <h4>Características Principales</h4>
                        <ul>
                            ${molde.caracteristicas.map(caract => `<li>${caract}</li>`).join('')}
                        </ul>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Ventajas</h4>
                        <ul>
                            ${molde.ventajas.map(ventaja => `<li>${ventaja}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            </div>
        `;

        modalTitle.textContent = molde.title;
        modalBody.innerHTML = modalContent;
        moldeModal.show();
    }

    function next(){ goTo((currentIndex + 1) % itemCount); }
    function prev(){ goTo((currentIndex - 1 + itemCount) % itemCount); }

    function goTo(index){
        rotateToIndex(index);
        resetAuto();
    }

    function startAuto(){
        stopAuto();
        autoTimer = setInterval(()=> next(), AUTO_INTERVAL);
        autoRotate = true;
        playPauseBtn.textContent = 'Pausa';
    }

    function stopAuto(){
        if(autoTimer) clearInterval(autoTimer);
        autoTimer = null;
        autoRotate = false;
        playPauseBtn.textContent = 'Reanudar';
    }

    function resetAuto(){
        if(autoRotate) startAuto();
    }

    // DRAG / TOUCH
    let dragging = false, startX = 0, startAngle = 0;

    function onDown(e){
        dragging = true;
        startX = (e.touches ? e.touches[0].clientX : e.clientX);
        const theta = 360 / itemCount;
        startAngle = -theta * currentIndex;
        carousel.style.transition = 'none';
        stopAuto();
    }

    function onMove(e){
        if(!dragging) return;
        const x = (e.touches ? e.touches[0].clientX : e.clientX);
        const dx = x - startX;
        carousel.style.transform = `translateZ(-${radius}px) rotateY(${startAngle + dx/3}deg)`;
    }

    function onUp(e){
        if(!dragging) return;
        dragging = false;
        const x = (e.changedTouches ? e.changedTouches[0].clientX : e.clientX);
        const dx = x - startX;
        const moved = Math.round((dx / stage.offsetWidth) * itemCount);
        let newIndex = currentIndex - moved;
        newIndex = ((newIndex % itemCount) + itemCount) % itemCount;
        rotateToIndex(newIndex);
        resetAuto();
    }

    nextBtn.addEventListener('click', next);
    prevBtn.addEventListener('click', prev);
    playPauseBtn.addEventListener('click', ()=> autoRotate ? stopAuto() : startAuto());

    stage.addEventListener('mousedown', onDown);
    stage.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);

    stage.addEventListener('touchstart', onDown, {passive:true});
    stage.addEventListener('touchmove', onMove, {passive:true});
    stage.addEventListener('touchend', onUp);

    window.addEventListener('resize', setupCarousel);

    setupCarousel();
    startAuto();
})();

(() => {
    const modalElement = document.getElementById('moldeModal');
    const modalTitle = document.getElementById('moldeModalLabel');
    const modalBody = document.getElementById('modalBody');
    const modal = new bootstrap.Modal(modalElement);

    document.querySelectorAll('.molde-card').forEach(card => {
        const title = card.querySelector('h3');
        const image = card.querySelector('img');
        const badge = card.querySelector('.tipo-badge');
        const description = card.querySelector('.descripcion');
        if (!title || !image || !description) return;

        const openDetails = () => {
            modalTitle.textContent = title.textContent.trim();
            const detailImage = document.createElement('img');
            detailImage.src = image.src;
            detailImage.alt = image.alt;
            detailImage.className = 'img-fluid d-block mx-auto mb-3';
            const detailText = document.createElement('p');
            detailText.textContent = description.textContent.trim();
            modalBody.replaceChildren(detailImage, ...(badge ? [badge.cloneNode(true)] : []), detailText);
            modal.show();
        };

        card.setAttribute('role', 'button');
        card.tabIndex = 0;
        card.setAttribute('aria-label', `Ver detalles de ${title.textContent.trim()}`);
        card.addEventListener('click', openDetails);
        card.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openDetails();
            }
        });
    });
})();

  // ================= ESTADÍSTICAS ANIMADO =================
const statItems = document.querySelectorAll('.stat-item');

const observer = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if(entry.isIntersecting){
      // Animación escalonada
      statItems.forEach((item, index) => {
        setTimeout(() => {
          item.classList.add('visible');
        }, index * 200); // 200ms de retraso entre cada ítem
      });

      observer.unobserve(entry.target); // solo se activa una vez
    }
  });
}, { threshold: 0.5 });

statItems.forEach(item => observer.observe(item));

//--------------------------------------------------------------
// Animaciones adicionales para las cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.molde-card');
    
    // Animación de entrada escalonada
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 200 * index);
    });
    
    // Efecto de clic
    cards.forEach(card => {
        card.addEventListener('click', function() {
            // Efecto de clic
            this.style.transform = 'translateY(-8px) scale(0.98)';
            
            // Remover selección de otras cards
            cards.forEach(c => {
                if (c !== this) {
                    c.classList.remove('selected');
                }
            });
            
            // Añadir clase selected
            this.classList.toggle('selected');
            
            // Restaurar transformación después de un tiempo
            setTimeout(() => {
                if (this.classList.contains('selected')) {
                    this.style.transform = 'translateY(-8px) scale(1)';
                } else {
                    this.style.transform = 'translateY(-8px) scale(1.03)';
                }
            }, 150);
        });
    });
    
    // Efecto para dispositivos táctiles
    cards.forEach(card => {
        card.addEventListener('touchstart', function() {
            this.style.transform = 'translateY(-8px) scale(0.98)';
        });
        
        card.addEventListener('touchend', function() {
            this.style.transform = 'translateY(-8px) scale(1.03)';
        });
    });
});
