/**
 * Duo Import MDG - Script pour le formulaire de devis
 */

document.addEventListener('DOMContentLoaded', function() {
    const productTypeSelect = document.querySelector('#quote_productType');
    const otherProductTypeContainer = document.querySelector('#otherProductTypeContainer');
    const form = document.querySelector('#quoteForm');
    const loader = document.querySelector('#loader');
    
    // Gestion du champ "Autre type de produit"
    if (productTypeSelect && otherProductTypeContainer) {
        // Vérifier l'état initial
        if (productTypeSelect.value === 'other') {
            otherProductTypeContainer.style.display = 'block';
            otherProductTypeContainer.querySelector('input').required = true;
        } else {
            otherProductTypeContainer.style.display = 'none';
            otherProductTypeContainer.querySelector('input').required = false;
        }
        
        // Ajouter l'écouteur d'événements
        productTypeSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                otherProductTypeContainer.style.display = 'block';
                otherProductTypeContainer.querySelector('input').required = true;
            } else {
                otherProductTypeContainer.style.display = 'none';
                otherProductTypeContainer.querySelector('input').required = false;
            }
        });
    }

    // Gestion du loader pendant la soumission
    if (form) {
        form.addEventListener('submit', function() {
            // Désactiver le bouton de soumission
            const submitButton = document.querySelector('#submitButton');
            if (submitButton) {
                submitButton.disabled = true;
            }
            
            // Afficher le loader
            if (loader) {
                loader.style.display = 'flex';
            }
        });
    }
}); 