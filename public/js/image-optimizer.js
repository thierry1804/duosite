/**
 * Image Optimizer Script
 * Optimise le chargement des images pour améliorer le LCP (Largest Contentful Paint)
 */

// Fonction pour précharger les images importantes
// Test
function preloadCriticalImages() {
    const criticalImages = [
        '/public/images/hero-image-300.webp',
        '/public/images/logo.webp'
    ];
    
    criticalImages.forEach(src => {
        const img = new Image();
        img.src = src;
        img.fetchPriority = 'high';
    });
}

// Fonction pour marquer les images comme chargées
function markImagesAsLoaded() {
    const images = document.querySelectorAll('.hero-image img, .img-fluid');
    images.forEach(img => {
        if (img.complete) {
            img.classList.add('loaded');
        } else {
            img.addEventListener('load', () => {
                img.classList.add('loaded');
            });
        }
    });
}

// Fonction pour optimiser les images en arrière-plan
function optimizeBackgroundImages() {
    const lazyBackgrounds = document.querySelectorAll('.lazy-bg');
    
    if ('IntersectionObserver' in window) {
        const backgroundObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    const bg = target.getAttribute('data-bg');
                    if (bg) {
                        target.style.backgroundImage = `url(${bg})`;
                        target.classList.add('loaded-bg');
                        backgroundObserver.unobserve(target);
                    }
                }
            });
        });
        
        lazyBackgrounds.forEach(bg => {
            backgroundObserver.observe(bg);
        });
    } else {
        // Fallback pour les navigateurs qui ne supportent pas IntersectionObserver
        lazyBackgrounds.forEach(bg => {
            const bgSrc = bg.getAttribute('data-bg');
            if (bgSrc) {
                bg.style.backgroundImage = `url(${bgSrc})`;
                bg.classList.add('loaded-bg');
            }
        });
    }
}

// Exécuter les fonctions au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    preloadCriticalImages();
    markImagesAsLoaded();
    optimizeBackgroundImages();
});

// Exécuter immédiatement pour les images déjà chargées
preloadCriticalImages();
markImagesAsLoaded(); 