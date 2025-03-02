/**
 * Duo Import MDG - Scripts principaux
 */

document.addEventListener('DOMContentLoaded', function() {
    // Activer tous les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Activer tous les popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Animation au défilement
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 100) {
                element.classList.add('animated');
            }
        });
    };
    
    // Exécuter l'animation au chargement et au défilement
    animateOnScroll();
    window.addEventListener('scroll', animateOnScroll);
    
    // Gestion du formulaire de contact
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simuler l'envoi du formulaire (à remplacer par l'appel API réel)
            const formSuccess = document.getElementById('formSuccess');
            
            // Afficher le message de succès
            setTimeout(function() {
                contactForm.reset();
                formSuccess.classList.remove('d-none');
                
                // Masquer le message après 5 secondes
                setTimeout(function() {
                    formSuccess.classList.add('d-none');
                }, 5000);
            }, 1000);
        });
    }
    
    // Gestion du formulaire de demande de devis
    const quoteForm = document.getElementById('quoteForm');
    if (quoteForm) {
        // Gestion du champ "Autre type de produit"
        const productTypeSelect = document.getElementById('productType');
        const otherProductTypeContainer = document.getElementById('otherProductTypeContainer');
        const otherProductTypeInput = document.getElementById('otherProductType');
        
        if (productTypeSelect) {
            productTypeSelect.addEventListener('change', function() {
                if (this.value === 'other') {
                    otherProductTypeContainer.style.display = 'block';
                    otherProductTypeInput.setAttribute('required', 'required');
                } else {
                    otherProductTypeContainer.style.display = 'none';
                    otherProductTypeInput.removeAttribute('required');
                }
            });
        }
        
        // Soumission du formulaire
        quoteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simuler l'envoi du formulaire (à remplacer par l'appel API réel)
            const formSuccess = document.getElementById('formSuccess');
            
            // Afficher le message de succès
            setTimeout(function() {
                quoteForm.reset();
                if (otherProductTypeContainer) {
                    otherProductTypeContainer.style.display = 'none';
                }
                formSuccess.classList.remove('d-none');
                
                // Redirection vers le tableau de bord après 3 secondes
                setTimeout(function() {
                    const dashboardUrl = formSuccess.querySelector('a').getAttribute('href');
                    window.location.href = dashboardUrl;
                }, 3000);
            }, 1000);
        });
    }
    
    // Smooth scroll pour les ancres
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });
}); 