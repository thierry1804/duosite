document.addEventListener('DOMContentLoaded', function() {
    // Trouver tous les boutons de récupération de taux de change
    const exchangeRateButtons = document.querySelectorAll('.fetch-exchange-rate');
    
    exchangeRateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const exchangeRateField = document.querySelector('.exchange-rate-field');
            if (!exchangeRateField) return;
            
            const apiUrl = exchangeRateField.dataset.apiUrl || 'https://open.er-api.com/v6/latest/CNY';
            
            // Afficher un indicateur de chargement
            exchangeRateField.setAttribute('placeholder', 'Chargement du taux...');
            exchangeRateField.value = '';
            exchangeRateField.disabled = true;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement...';
            
            // Faire une requête à l'API
            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.rates && data.rates.MGA) {
                        // Mettre à jour le champ avec le taux de change
                        exchangeRateField.value = data.rates.MGA.toFixed(6);
                        
                        // Créer un élément pour afficher les informations supplémentaires
                        const helpText = exchangeRateField.closest('.mb-3').querySelector('.form-text');
                        if (helpText) {
                            const date = new Date();
                            const infoElement = document.createElement('div');
                            infoElement.className = 'mt-2 small text-success';
                            infoElement.innerHTML = 'Taux récupéré le ' + date.toLocaleString() + 
                                                   '<br>Source: ' + (data.provider || 'exchangerate-api.com');
                            
                            // Si un élément d'info existe déjà, le remplacer
                            const existingInfo = helpText.querySelector('.mt-2.small.text-success');
                            if (existingInfo) {
                                helpText.replaceChild(infoElement, existingInfo);
                            } else {
                                helpText.appendChild(infoElement);
                            }
                        }
                    } else {
                        throw new Error('Données de taux de change non disponibles');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération du taux de change:', error);
                    // Afficher une erreur
                    exchangeRateField.value = '';
                    exchangeRateField.setAttribute('placeholder', 'Erreur lors de la récupération du taux');
                    
                    const helpText = exchangeRateField.closest('.mb-3').querySelector('.form-text');
                    if (helpText) {
                        const errorElement = document.createElement('div');
                        errorElement.className = 'mt-2 small text-danger';
                        errorElement.textContent = 'Erreur: Impossible de récupérer le taux de change. Veuillez réessayer plus tard.';
                        
                        const existingError = helpText.querySelector('.mt-2.small.text-danger');
                        if (existingError) {
                            helpText.replaceChild(errorElement, existingError);
                        } else {
                            helpText.appendChild(errorElement);
                        }
                    }
                })
                .finally(() => {
                    // Réactiver le champ et le bouton
                    exchangeRateField.disabled = false;
                    button.disabled = false;
                    button.innerHTML = 'Récupérer le taux actuel';
                    exchangeRateField.focus();
                });
        });
    });
}); 