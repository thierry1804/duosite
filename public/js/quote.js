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
            const input = otherProductTypeContainer.querySelector('input');
            if (input) input.required = true;
        } else {
            otherProductTypeContainer.style.display = 'none';
            const input = otherProductTypeContainer.querySelector('input');
            if (input) input.required = false;
        }
        
        // Ajouter l'écouteur d'événements
        productTypeSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                otherProductTypeContainer.style.display = 'block';
                const input = otherProductTypeContainer.querySelector('input');
                if (input) input.required = true;
            } else {
                otherProductTypeContainer.style.display = 'none';
                const input = otherProductTypeContainer.querySelector('input');
                if (input) input.required = false;
            }
        });
    }

    // Gestion des champs "Autre type de produit" pour les éléments dynamiques
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('product-type-select')) {
            const container = e.target.closest('.quote-item');
            if (container) {
                const otherTypeContainer = container.querySelector('.other-product-type-container');
                if (otherTypeContainer) {
                    if (e.target.value === 'Autre') {
                        otherTypeContainer.style.display = 'block';
                        const input = otherTypeContainer.querySelector('input');
                        if (input) input.required = true;
                    } else {
                        otherTypeContainer.style.display = 'none';
                        const input = otherTypeContainer.querySelector('input');
                        if (input) input.required = false;
                    }
                }
            }
        }
    });

    // Fonction pour initialiser l'aperçu des photos
    function initPhotoPreview(fileInput) {
        if (!fileInput) return;
        
        const container = fileInput.closest('.photo-upload-container');
        if (!container) return;
        
        const preview = container.querySelector('.product-photo-preview');
        if (!preview) return;
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
            }
        });
    }
    
    // Initialiser l'aperçu des photos pour les éléments existants
    document.querySelectorAll('.form-control-file').forEach(function(fileInput) {
        initPhotoPreview(fileInput);
    });
    
    // Gestion de l'aperçu des photos pour les éléments dynamiques
    document.addEventListener('change', function(e) {
        if (e.target && e.target.type === 'file' && e.target.classList.contains('form-control-file')) {
            const container = e.target.closest('.photo-upload-container');
            if (container) {
                const preview = container.querySelector('.product-photo-preview');
                if (preview) {
                    if (e.target.files && e.target.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        }
                        
                        reader.readAsDataURL(e.target.files[0]);
                    } else {
                        preview.src = '#';
                        preview.style.display = 'none';
                    }
                }
            }
        }
    });

    // Gestion du bouton d'ajout d'élément
    const addItemButton = document.querySelector('.add-item-btn');
    const itemsWrapper = document.querySelector('.quote-items-wrapper');
    
    if (addItemButton && itemsWrapper) {
        addItemButton.addEventListener('click', function() {
            const prototype = itemsWrapper.dataset.prototype;
            const index = parseInt(itemsWrapper.dataset.index);
            
            // Créer un nouvel élément à partir du prototype
            const newItem = prototype.replace(/__name__/g, index);
            
            // Créer un conteneur pour le nouvel élément
            const container = document.createElement('div');
            container.classList.add('quote-item');
            container.innerHTML = newItem;
            
            // Ajouter le bouton de suppression
            const removeButton = document.createElement('span');
            removeButton.classList.add('remove-item');
            removeButton.innerHTML = '<i class="fas fa-times-circle"></i>';
            container.prepend(removeButton);
            
            // Ajouter l'élément au DOM
            itemsWrapper.appendChild(container);
            
            // Initialiser l'aperçu des photos pour le nouvel élément
            const fileInput = container.querySelector('.form-control-file');
            if (fileInput) {
                initPhotoPreview(fileInput);
            }
            
            // Mettre à jour l'index
            itemsWrapper.dataset.index = index + 1;
        });
    }
    
    // Gestion de la suppression d'élément
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('remove-item') || e.target.closest('.remove-item'))) {
            const item = e.target.closest('.quote-item');
            if (item) {
                item.remove();
            }
        }
    });

    // Gestion du loader pendant la soumission
    if (form) {
        form.addEventListener('submit', function() {
            // Désactiver le bouton de soumission pour éviter les soumissions multiples
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