# Plan de mise en œuvre - Système de Tracking des Devis

## 📋 Vue d'ensemble

Ce document détaille la mise en place d'un système de tracking complet pour les devis/commandes sur la plateforme Symfony Duo Import MDG.

**Objectif :** Permettre aux clients de suivre l'évolution de leurs devis en temps réel avec un historique complet des changements de statut.

## 🎯 Statuts proposés

```
new → viewed → in_progress → waiting_customer → accepted → declined → converted → shipped → delivered → canceled
```

**Statuts actuels existants :** `pending`, `in_progress`, `completed`, `rejected`

## 📊 Analyse de l'existant

### ✅ Éléments déjà en place
- [x] Entité `Quote` avec champ `status`
- [x] Champ `quoteNumber` unique (format: DUO-YYYYMMDD-XXXX)
- [x] Relations avec `User`, `QuoteItem`, `QuoteOffer`
- [x] Gestion des paiements (`paymentStatus`, `transactionReference`, `paymentDate`)
- [x] Contrôleurs `QuoteController` et `QuoteOfferController`
- [x] Routes de mise à jour de statut : `/quote/{id}/status/{status}`
- [x] Services : `QuoteFeeCalculator`, `ExchangeRateService`, `PdfGenerator`
- [x] Templates avec affichage des statuts
- [x] Interface admin pour changer les statuts
- [x] Timeline basique dans `quote/view.html.twig`

### 🔧 Adaptations nécessaires
- [ ] Ajouter `trackingToken` (UUID) à l'entité Quote
- [ ] Créer l'entité `QuoteStatusHistory`
- [ ] Centraliser la logique de changement de statut
- [ ] Ajouter les endpoints de tracking public
- [ ] Implémenter les notifications automatiques

## 🚀 Plan de mise en œuvre

### Phase 1 : Extension du modèle de données
**Durée estimée :** 2-3h  
**Statut :** ✅ Terminé

#### 1.1 Modifier l'entité Quote
- [x] Ajouter le champ `trackingToken` (UUID)
- [x] Modifier le constructeur pour générer automatiquement le token
- [x] Ajouter les getters/setters nécessaires

#### 1.2 Créer l'entité QuoteStatusHistory
- [x] Créer la classe `QuoteStatusHistory`
- [x] Définir les relations avec Quote
- [x] Champs : `oldStatus`, `newStatus`, `changedBy`, `comment`, `createdAt`
- [x] Ajouter les annotations Doctrine
- [x] Créer le repository `QuoteStatusHistoryRepository`

#### 1.3 Migration Doctrine
- [x] Créer la migration pour ajouter `tracking_token` à la table `quotes`
- [x] Créer la table `quote_status_history`
- [x] Créer la migration pour générer les tokens des devis existants

### Phase 2 : Service de tracking centralisé
**Durée estimée :** 3-4h  
**Statut :** ✅ Terminé

#### 2.1 Créer QuoteTrackerService
- [x] Créer la classe `QuoteTrackerService`
- [x] Méthode `changeStatus()` centralisée
- [x] Validation des transitions de statut
- [x] Enregistrement automatique de l'historique
- [x] Dispatch d'événements
- [x] Constantes pour les statuts valides et transitions autorisées
- [x] Méthodes utilitaires (statistiques, historique, etc.)

#### 2.2 Créer l'événement QuoteStatusChangedEvent
- [x] Créer la classe `QuoteStatusChangedEvent`
- [x] Définir les propriétés : quote, oldStatus, newStatus, changedBy
- [x] Configurer le dispatch d'événements
- [x] Créer l'Event Listener `QuoteStatusChangeListener`
- [x] Templates d'emails pour notifications client et admin
- [x] Configuration des services dans `services.yaml`

### Phase 3 : Endpoints API
**Durée estimée :** 2-3h  
**Statut :** ✅ Terminé

#### 3.1 Controller de tracking public
- [x] Créer `TrackingController`
- [x] Route `GET /tracking/{token}` - Page publique de suivi
- [x] Route `GET /api/tracking/{token}` - API JSON pour le statut
- [x] Route `GET /api/tracking/{token}/status` - API pour statut uniquement
- [x] Route `GET /tracking/search` - Page de recherche de devis
- [x] Route `POST /webhook/quote-status` - Webhook pour systèmes tiers
- [x] Route `GET /api/tracking/stats` - API statistiques (admin)
- [x] Gestion des erreurs (token invalide, devis non trouvé)

#### 3.2 Modifier QuoteController
- [x] Remplacer les appels directs à `setStatus()` par `QuoteTrackerService`
- [x] Ajouter les commentaires lors des changements de statut
- [x] Nouvelle route `/quote/{id}/status-update` avec formulaire
- [x] Template `status_update.html.twig` pour changement avec commentaire
- [x] Création de l'historique initial lors de la création de devis
- [x] Mise à jour de la méthode `processQuote()`

### Phase 4 : Interface utilisateur
**Durée estimée :** 3-4h  
**Statut :** ✅ Terminé

#### 4.1 Page de tracking public
- [x] Créer le template `tracking/show.html.twig`
- [x] Timeline des statuts avec dates et commentaires
- [x] Design responsive et moderne
- [x] Affichage des informations du devis (sans données sensibles)

#### 4.2 Améliorer l'interface admin
- [x] Formulaire de changement de statut avec commentaire
- [x] Historique complet des changements
- [x] Interface de gestion des statuts
- [x] Mise à jour des templates existants

#### 4.3 Intégration dans les templates existants
- [x] Ajout du token de tracking dans l'interface admin
- [x] Section d'historique des changements de statut
- [x] Lien vers le suivi public dans l'interface admin
- [x] Intégration du tracking dans les emails de confirmation
- [x] Intégration du tracking dans les emails d'offre
- [x] Ajout du suivi dans la liste des devis utilisateur
- [x] Section de suivi dans la page de détail des devis utilisateur
- [x] Fonction JavaScript pour copier le token de suivi

### Phase 5 : Notifications
**Durée estimée :** 2-3h  
**Statut :** ⏳ En attente

#### 5.1 Event Listeners
- [ ] Créer `QuoteStatusChangeListener`
- [ ] Email automatique lors des changements de statut
- [ ] Templates d'emails pour chaque type de changement
- [ ] Configuration des notifications

#### 5.2 Intégration Mercure (optionnel)
- [ ] Configuration du hub Mercure
- [ ] Mise à jour temps réel sur la page de tracking
- [ ] JavaScript pour EventSource

### Phase 6 : Tests et finalisation
**Durée estimée :** 2h  
**Statut :** ⏳ En attente

#### 6.1 Tests unitaires
- [ ] Tests pour `QuoteTrackerService`
- [ ] Validation des transitions de statut
- [ ] Tests des événements

#### 6.2 Tests d'intégration
- [ ] Tests des endpoints de tracking
- [ ] Tests des notifications
- [ ] Tests de l'interface utilisateur

## 📈 Progression globale

**Total estimé :** 12-18 heures de développement

- [x] **Phase 1** : Extension du modèle de données (3/3 tâches) ✅
- [x] **Phase 2** : Service de tracking centralisé (2/2 tâches) ✅
- [x] **Phase 3** : Endpoints API (2/2 tâches) ✅
- [x] **Phase 4** : Interface utilisateur (2/2 tâches) ✅
- [ ] **Phase 5** : Notifications (0/2 tâches)
- [ ] **Phase 6** : Tests et finalisation (0/2 tâches)

**Progression :** 69% (9/13 tâches principales)

## 🔧 Configuration requise

### Dépendances à ajouter
- [x] `symfony/uid` (pour la génération d'UUID) ✅
- [ ] `symfony/mercure-bundle` (optionnel, pour le temps réel)
- [ ] `symfony/workflow` (optionnel, pour la validation des transitions)

### Configuration
- [ ] Paramètres de configuration pour les notifications
- [ ] Configuration Mercure (si activé)
- [ ] Templates d'emails

## 🎨 Exemples de code

### Entité QuoteStatusHistory
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quote_status_history')]
class QuoteStatusHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Quote::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Quote $quote;

    #[ORM\Column(length: 64)]
    private string $oldStatus;

    #[ORM\Column(length: 64)]
    private string $newStatus;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $changedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters et setters...
}
```

### Service QuoteTracker
```php
<?php

namespace App\Service;

use App\Entity\Quote;
use App\Entity\QuoteStatusHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QuoteTrackerService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventDispatcherInterface $dispatcher
    ) {}

    public function changeStatus(
        Quote $quote, 
        string $newStatus, 
        ?string $changedBy = null, 
        ?string $comment = null
    ): Quote {
        $oldStatus = $quote->getStatus();
        
        if ($oldStatus === $newStatus) {
            return $quote;
        }

        // Changer le statut
        $quote->setStatus($newStatus);

        // Enregistrer l'historique
        $history = new QuoteStatusHistory();
        $history->setQuote($quote);
        $history->setOldStatus($oldStatus);
        $history->setNewStatus($newStatus);
        $history->setChangedBy($changedBy);
        $history->setComment($comment);

        $this->em->persist($history);
        $this->em->persist($quote);
        $this->em->flush();

        // Dispatcher l'événement
        $this->dispatcher->dispatch(
            new QuoteStatusChangedEvent($quote, $oldStatus, $newStatus, $changedBy)
        );

        return $quote;
    }
}
```

## 📝 Notes de développement

### Points d'attention
- Ne pas exposer de données sensibles sur la page publique de tracking
- Penser à la rotation/révocation du token si nécessaire
- Prévoir la scalabilité pour un volume élevé de devis
- Maintenir la compatibilité avec l'existant

### Sécurité
- Token UUID non séquentiel pour éviter les fuites d'informations
- Validation des droits avant changement de statut
- CSRF pour les formulaires
- Audit trail complet

---

**Dernière mise à jour :** 2025-01-01  
**Version :** 1.5  
**Auteur :** Assistant IA Claude

## 📋 Journal des modifications

### Version 1.5 (2025-01-01)
- ✅ **Phase 4 terminée** : Interface utilisateur
  - Amélioration de l'interface admin avec historique des changements de statut
  - Intégration du tracking dans tous les templates existants
  - Ajout du token de tracking dans l'interface admin
  - Section d'historique des changements de statut avec timeline
  - Lien vers le suivi public dans l'interface admin
  - Intégration du tracking dans les emails de confirmation et d'offre
  - Ajout du suivi dans la liste des devis utilisateur
  - Section de suivi dans la page de détail des devis utilisateur
  - Fonction JavaScript pour copier le token de suivi
  - Progression : 69% (9/13 tâches principales)

### Version 1.4 (2025-01-01)
- ✅ **Phase 3 terminée** : Endpoints API
  - Création du `TrackingController` avec 6 routes complètes
  - Page publique de suivi avec timeline interactive
  - Page de recherche de devis par numéro et email
  - API JSON pour intégrations externes
  - Webhook pour systèmes tiers
  - Modification du `QuoteController` pour utiliser le service de tracking
  - Template de mise à jour de statut avec commentaires
  - Progression : 54% (7/13 tâches principales)

### Version 1.3 (2025-01-01)
- ✅ **Phase 2 terminée** : Service de tracking centralisé
  - Création du `QuoteTrackerService` avec validation des transitions
  - Système d'événements `QuoteStatusChangedEvent` complet
  - Event Listener pour notifications automatiques
  - Templates d'emails pour clients et administrateurs
  - Configuration des services Symfony
  - Progression : 38% (5/13 tâches principales)

### Version 1.2 (2025-01-01)
- ✅ **Phase 1 terminée** : Extension du modèle de données
  - Ajout du champ `trackingToken` à l'entité Quote (nullable, unique)
  - Création de l'entité QuoteStatusHistory avec repository complet
  - Création des migrations Doctrine (3 migrations)
  - Commande `app:generate-tracking-tokens` pour générer les tokens existants
  - Génération automatique d'UUID v4 dans le constructeur Quote
  - Progression : 23% (3/13 tâches principales)

### Version 1.1 (2025-01-01)
- ✅ **Phase 1 terminée** : Extension du modèle de données
  - Ajout du champ `trackingToken` à l'entité Quote
  - Création de l'entité QuoteStatusHistory avec repository
  - Création des migrations Doctrine
  - Progression : 23% (3/13 tâches principales)

### Version 1.0 (2025-01-01)
- 📋 Création du plan initial de mise en œuvre
- 📊 Analyse de l'existant et évaluation de faisabilité
