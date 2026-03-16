// Sistema de calificaciones
document.querySelectorAll('.stars').forEach(starsContainer => {
    const stars = starsContainer.querySelectorAll('.star');
    const tipo = starsContainer.dataset.tipo;
    const itemId = starsContainer.dataset.id;
    
    stars.forEach((star, index) => {
        star.addEventListener('click', () => {
            const calificacion = index + 1;
            
            fetch('/FLOWZONE/api/calificar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `tipo=${tipo}&item_id=${itemId}&calificacion=${calificacion}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('¡Calificación guardada!');
                    location.reload();
                } else {
                    alert(data.message || 'Error al calificar');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la calificación');
            });
        });
        
        star.addEventListener('mouseenter', () => {
            stars.forEach((s, i) => {
                if (i <= index) {
                    s.style.opacity = '1';
                } else {
                    s.style.opacity = '0.3';
                }
            });
        });
    });
    
    starsContainer.addEventListener('mouseleave', () => {
        stars.forEach(s => s.style.opacity = '1');
    });
});

// Sistema de comentarios
const formComentario = document.getElementById('form-comentario');
if (formComentario) {
    formComentario.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const formData = new FormData(formComentario);
        
        fetch('/FLOWZONE/api/comentar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const listaComentarios = document.getElementById('lista-comentarios');
                const nuevoComentario = document.createElement('div');
                nuevoComentario.className = 'comentario';
                nuevoComentario.innerHTML = `
                    <div class="comentario-header">
                        <strong>${data.comentario.usuario_nombre}</strong>
                        <span class="fecha">${new Date(data.comentario.fecha).toLocaleString('es-CO')}</span>
                    </div>
                    <p>${data.comentario.comentario}</p>
                `;
                listaComentarios.insertBefore(nuevoComentario, listaComentarios.firstChild);
                formComentario.reset();
                alert('¡Comentario publicado!');
            } else {
                alert(data.message || 'Error al publicar comentario');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar el comentario');
        });
    });
}

// Sistema de favoritos
document.querySelectorAll('.btn-favorito').forEach(btn => {
    btn.addEventListener('click', function() {
        const tipo = this.dataset.tipo;
        const itemId = this.dataset.id;
        
        fetch('/FLOWZONE/api/toggle_favorito.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `tipo=${tipo}&item_id=${itemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.action === 'added') {
                    this.classList.add('active');
                    this.textContent = '❤️ En Favoritos';
                } else {
                    this.classList.remove('active');
                    this.textContent = '🤍 Agregar a Favoritos';
                }
                
                // Si estamos en la página de favoritos, recargar
                if (window.location.pathname.includes('favoritos.php')) {
                    location.reload();
                }
            } else {
                alert(data.message || 'Error al procesar favorito');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar favorito');
        });
    });
});

// Búsqueda en tiempo real (opcional)
const searchInput = document.querySelector('input[name="busqueda"]');
if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value;
        
        if (query.length >= 3) {
            searchTimeout = setTimeout(() => {
                // Implementar búsqueda en tiempo real si se desea
                console.log('Buscando:', query);
            }, 500);
        }
    });
}

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});
