
    // Buscamos el mensaje por su ID
    const toast = document.getElementById('toastMsg');
    
    if (toast) {
        // Esperamos 5 segundos (5000ms)
        setTimeout(() => {
            toast.style.transition = "0.6s opacity, 0.6s transform";
            toast.style.opacity = "0";
            toast.style.transform = "translateX(100%)";
            
            // Lo borramos del código después de la animación
            setTimeout(() => toast.remove(), 600);
        }, 5000);
    }
