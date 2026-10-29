// =============================
// EMAILJS
// =============================
(function() {
    if (typeof emailjs !== 'undefined') {
        emailjs.init("contacto@aureamed.com.bo");
    }
})();

const formContacto = document.getElementById('form-contacto');
if (formContacto && typeof emailjs !== 'undefined') {
    formContacto.addEventListener('submit', function(event) {
        event.preventDefault();

        emailjs.send("contacto@aureamed.com.bo", "TU_TEMPLATE_ID", {
          nombre: document.getElementById("nombre").value,
          correo: document.getElementById("correo").value,
          telefono: document.getElementById("telefono").value,
          asunto: document.getElementById("asunto").value,
          mensaje: document.getElementById("mensaje").value
        })
        .then(() => alert("Mensaje enviado con éxito"))
        .catch(error => alert("Error al enviar: " + JSON.stringify(error)));
    });
}

// =======================================================
// ANIMACIONES - SE EJECUTA AL CARGAR EL DOM
// =======================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página de información auditiva cargada');
    
    // 1. ANIMACIÓN PARA TARJETAS (Esto se mueve)
    const cards = document.querySelectorAll('.grid-item');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 200);
    });
    
    // 2. EFECTOS HOVER MEJORADOS PARA IMÁGENES (Esto se mueve)
    const images = document.querySelectorAll('.centered-img img');
    
    images.forEach(img => {
        img.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        img.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

   // =======================================================
// ANIMACIONES CORREGIDAS - LOS NÚMEROS SÍ SE MUEVEN
// =======================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página cargada - Iniciando animaciones de números');
    
    // 1. FUNCIÓN DE ANIMACIÓN CORREGIDA
    function animateCounter(element) {
        const target = parseInt(element.getAttribute('data-count'));
        const hasPlus = element.getAttribute('data-plus') === "true";
        
        console.log(`Animando: 0 -> ${target}${hasPlus ? '+' : ''}`);
        
        // COMENZAR DESDE 0 (¡ESTO ES CLAVE!)
        let current = 0;
        element.textContent = "0" + (hasPlus ? "+" : "");
        
        const duration = 2000; // 2 segundos
        const startTime = Date.now();
        
        function update() {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Fórmula para efecto "explosivo"
            const easeOutCubic = 1 - Math.pow(1 - progress, 3);
            current = Math.floor(easeOutCubic * target);
            
            // Actualizar display
            element.textContent = current + (hasPlus ? "+" : "");
            
            // Efectos visuales DINÁMICOS
            const scale = 0.8 + (current / target) * 0.4;
            const bounce = Math.sin(progress * Math.PI * 5) * (1 - progress) * 15;
            
            element.style.transform = `scale(${scale}) translateY(${-bounce}px)`;
            element.style.color = `hsl(${20 + (current/target)*60}, 85%, 45%)`;
            
            // Continuar si no terminó
            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                // Efecto final
                element.textContent = target + (hasPlus ? "+" : "");
                element.style.transform = 'scale(1.2)';
                element.style.color = '#e74c3c';
                
                setTimeout(() => {
                    element.style.transform = 'scale(1)';
                    element.style.color = '#8B2C2C';
                    element.style.transition = 'all 0.5s ease';
                }, 300);
                
                console.log(`Animación completada: ${target}${hasPlus ? '+' : ''}`);
            }
        }
        
        requestAnimationFrame(update);
    }
    
    // 2. OBSERVADOR SIMPLIFICADO Y FUNCIONAL
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                console.log(`Elemento ${index + 1} visible`);
                
                const numberElement = entry.target.querySelector('.stat-number');
                if (numberElement && !numberElement.classList.contains('animated')) {
                    numberElement.classList.add('animated');
                    
                    // Preparar elemento para animación
                    numberElement.style.display = 'inline-block';
                    numberElement.style.transition = 'all 0.2s ease';
                    
                    // Retraso escalonado
                    setTimeout(() => {
                        animateCounter(numberElement);
                    }, index * 400);
                }
                
                // Dejar de observar después de activar
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.2,
        rootMargin: '50px'
    });
    
    // 3. OBSERVAR ELEMENTOS
    const statItems = document.querySelectorAll('.stat-item');
    console.log(`Encontrados ${statItems.length} elementos para animar`);
    
    statItems.forEach(item => {
        // Preparar elementos
        const number = item.querySelector('.stat-number');
        if (number) {
            // Guardar el valor final como atributo
            const finalValue = number.textContent;
            number.setAttribute('data-original', finalValue);
            number.textContent = "0"; // ¡COMENZAR EN 0!
        }
        observer.observe(item);
    });
    
    // 4. FUNCIÓN DE PRUEBA MANUAL
    window.testNumberAnimation = function() {
        console.log('=== PRUEBA MANUAL DE ANIMACIONES ===');
        const numbers = document.querySelectorAll('.stat-number');
        numbers.forEach((num, i) => {
            setTimeout(() => {
                num.style.backgroundColor = '#ffeb3b';
                num.style.padding = '10px';
                num.style.borderRadius = '10px';
                animateCounter(num);
                
                setTimeout(() => {
                    num.style.backgroundColor = '';
                    num.style.padding = '';
                }, 2500);
            }, i * 500);
        });
    };
    
    console.log('Listo! Los números comenzarán a moverse al hacer scroll');
});
});
