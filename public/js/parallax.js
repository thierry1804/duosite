document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'appareil est mobile
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (!isMobile) {
        window.addEventListener('scroll', function() {
            const parallaxSections = document.querySelectorAll('.parallax-section');
            
            parallaxSections.forEach(section => {
                const distance = window.pageYOffset;
                const speed = section.dataset.speed || 0.5;
                
                // Calculer le décalage pour l'effet parallax
                const yPos = -(distance * speed);
                section.style.backgroundPositionY = yPos + 'px';
            });
        });
    }
    
    // Initialiser les sections parallax
    function initParallaxSections() {
        const parallaxSections = document.querySelectorAll('.parallax-section');
        
        parallaxSections.forEach(section => {
            // S'assurer que l'image de fond est chargée
            if (section.dataset.background) {
                section.style.backgroundImage = `url(${section.dataset.background})`;
            }
        });
    }
    
    initParallaxSections();
}); 