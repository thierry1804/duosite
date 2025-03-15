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
            // Récupérer un élément existant pour le cloner
            const existingItems = document.querySelectorAll('.quote-item');
            if (existingItems.length > 0) {
                // Cloner un élément existant
                const clone = existingItems[0].cloneNode(true);
                
                // Mettre à jour les IDs et noms des champs
                const index = parseInt(itemsWrapper.dataset.index);
                
                // Mettre à jour les IDs et noms des champs
                const inputs = clone.querySelectorAll('input, select, textarea');
                inputs.forEach(function(input) {
                    const oldId = input.id;
                    const oldName = input.name;
                    
                    if (oldId) {
                        const newId = oldId.replace(/\d+/, index);
                        input.id = newId;
                        
                        // Mettre à jour les labels associés
                        const labels = clone.querySelectorAll(`label[for="${oldId}"]`);
                        labels.forEach(function(label) {
                            label.setAttribute('for', newId);
                        });
                    }
                    
                    if (oldName) {
                        const newName = oldName.replace(/\[\d+\]/, `[${index}]`);
                        input.name = newName;
                    }
                    
                    // Réinitialiser les valeurs
                    if (input.type === 'file') {
                        // Ne rien faire pour les champs de fichier
                    } else if (input.type === 'select-one') {
                        input.selectedIndex = 0;
                    } else {
                        input.value = '';
                    }
                });
                
                // Mettre à jour les IDs des divs
                const divs = clone.querySelectorAll('div[id]');
                divs.forEach(function(div) {
                    const oldId = div.id;
                    if (oldId) {
                        const newId = oldId.replace(/\d+/, index);
                        div.id = newId;
                    }
                });
                
                // Réinitialiser l'aperçu de la photo
                const photoPreview = clone.querySelector('.product-photo-preview');
                if (photoPreview) {
                    photoPreview.src = '#';
                    photoPreview.style.display = 'none';
                }
                
                // Masquer le champ "Autre type de produit"
                const otherTypeContainer = clone.querySelector('.other-product-type-container');
                if (otherTypeContainer) {
                    otherTypeContainer.style.display = 'none';
                    const input = otherTypeContainer.querySelector('input');
                    if (input) input.required = false;
                }
                
                // Ajouter l'élément cloné au DOM
                itemsWrapper.appendChild(clone);
                
                // Initialiser l'aperçu des photos pour le nouvel élément
                const fileInput = clone.querySelector('.form-control-file');
                if (fileInput) {
                    initPhotoPreview(fileInput);
                }
                
                // Mettre à jour l'index
                itemsWrapper.dataset.index = index + 1;
            } else {
                // S'il n'y a pas d'élément existant, utiliser le prototype
                const prototype = itemsWrapper.dataset.prototype;
                const index = parseInt(itemsWrapper.dataset.index);
                
                // Créer un nouvel élément à partir du prototype
                const newItem = prototype.replace(/__name__/g, index);
                
                // Créer un conteneur pour le nouvel élément
                const container = document.createElement('div');
                container.classList.add('quote-item');
                
                // Créer la structure HTML
                const html = `
                    <span class="remove-item"><i class="fas fa-times-circle"></i></span>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="photo-container">
                                <div class="photo-upload-container">
                                    <!-- Le contenu sera ajouté dynamiquement -->
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <!-- Le contenu sera ajouté dynamiquement -->
                            </div>
                        </div>
                    </div>
                `;
                
                container.innerHTML = html;
                
                // Ajouter le conteneur au DOM
                itemsWrapper.appendChild(container);
                
                // Créer un div temporaire pour parser le HTML du prototype
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = newItem;
                
                // Trouver les éléments de formulaire
                const photoFileDiv = tempDiv.querySelector('div[id$="_photoFile"]');
                const productTypeDiv = tempDiv.querySelector('div[id$="_productType"]');
                const quantityDiv = tempDiv.querySelector('div[id$="_quantity"]');
                const budgetDiv = tempDiv.querySelector('div[id$="_budget"]');
                const otherProductTypeDiv = tempDiv.querySelector('div[id$="_otherProductType"]');
                const descriptionDiv = tempDiv.querySelector('div[id$="_description"]');
                
                // Ajouter les éléments dans la structure
                if (photoFileDiv) {
                    const photoUploadContainer = container.querySelector('.photo-upload-container');
                    if (photoUploadContainer) {
                        const photoFileInput = photoFileDiv.querySelector('input[type="file"]');
                        if (photoFileInput) {
                            photoFileInput.className = 'form-control-file d-none';
                            
                            // Créer le label
                            const label = document.createElement('label');
                            label.setAttribute('for', photoFileInput.id);
                            label.className = 'upload-label';
                            
                            const iconDiv = document.createElement('div');
                            iconDiv.className = 'photo-upload-icon';
                            iconDiv.innerHTML = '<i class="fas fa-camera"></i>';
                            
                            const span = document.createElement('span');
                            span.textContent = 'Cliquez pour ajouter une photo';
                            
                            label.appendChild(iconDiv);
                            label.appendChild(span);
                            
                            // Créer l'aperçu de l'image
                            const imgPreview = document.createElement('img');
                            imgPreview.src = '#';
                            imgPreview.className = 'product-photo-preview';
                            imgPreview.alt = 'Aperçu de la photo';
                            
                            // Assembler le conteneur d'upload
                            photoUploadContainer.appendChild(label);
                            photoUploadContainer.appendChild(photoFileInput);
                            photoUploadContainer.appendChild(imgPreview);
                            
                            // Initialiser l'aperçu des photos
                            initPhotoPreview(photoFileInput);
                        }
                    }
                }
                
                // Ajouter les champs de formulaire dans les colonnes appropriées
                const infoRow = container.querySelector('.col-md-8 .row');
                if (infoRow) {
                    if (productTypeDiv) {
                        const productTypeCol = document.createElement('div');
                        productTypeCol.className = 'col-md-4 mb-3';
                        productTypeCol.appendChild(productTypeDiv);
                        infoRow.appendChild(productTypeCol);
                    }
                    
                    if (quantityDiv) {
                        const quantityCol = document.createElement('div');
                        quantityCol.className = 'col-md-4 mb-3';
                        quantityCol.appendChild(quantityDiv);
                        infoRow.appendChild(quantityCol);
                    }
                    
                    if (budgetDiv) {
                        const budgetCol = document.createElement('div');
                        budgetCol.className = 'col-md-4 mb-3';
                        budgetCol.appendChild(budgetDiv);
                        infoRow.appendChild(budgetCol);
                    }
                    
                    // Ajouter le champ "Autre type de produit"
                    if (otherProductTypeDiv) {
                        const otherTypeContainer = document.createElement('div');
                        otherTypeContainer.className = 'col-12 mb-3 other-product-type-container';
                        otherTypeContainer.style.display = 'none';
                        otherTypeContainer.appendChild(otherProductTypeDiv);
                        infoRow.appendChild(otherTypeContainer);
                    }
                    
                    // Ajouter le champ de description
                    if (descriptionDiv) {
                        const descriptionCol = document.createElement('div');
                        descriptionCol.className = 'col-12 mb-3';
                        descriptionCol.appendChild(descriptionDiv);
                        infoRow.appendChild(descriptionCol);
                    }
                }
                
                // Mettre à jour l'index
                itemsWrapper.dataset.index = index + 1;
            }
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