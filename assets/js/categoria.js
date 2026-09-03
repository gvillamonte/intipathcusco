/* JS PARA INTERACCIÓN EN CATEGORÍAS */
document.addEventListener('DOMContentLoaded', function() {
    const tourItems = document.querySelectorAll('.tour-item');

    // Animación de entrada suave para las tarjetas
    tourItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = 'all 0.5s ease';
        
        setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, 150 * index);
    });

    console.log("Categoría cargada: IntiPath Tours");
});