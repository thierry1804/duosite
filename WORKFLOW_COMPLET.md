# Workflow Complet - Système de Devis Duo Import MDG

## 📋 Vue d'ensemble

Ce document décrit le workflow complet depuis la demande de devis jusqu'à la réception des marchandises, avec tous les statuts, transitions et responsabilités.

## 🔄 Diagramme des Transitions de Statuts

```
new → pending → viewed → in_progress → waiting_customer → accepted → converted → shipped → delivered
                                    ↓
                                 declined → in_progress (nouvelle offre)
                                    ↓
                                 canceled (à tout moment)
```

## 📊 Statuts Détaillés

| Statut | Libellé | Description | Responsable |
|--------|---------|-------------|-------------|
| `new` | Nouveau | Devis créé mais pas encore traité | Système |
| `pending` | En attente | Devis soumis, en attente de traitement | Admin |
| `viewed` | Consulté | Devis consulté par l'admin | Admin |
| `in_progress` | En cours de traitement | Devis pris en charge par l'équipe | Admin |
| `waiting_customer` | En attente du client | Offre envoyée, en attente de réponse | Client |
| `accepted` | Accepté | Offre acceptée par le client | Client |
| `declined` | Refusé | Offre refusée par le client | Client |
| `completed` | Terminé | Offre créée et envoyée | Admin |
| `rejected` | Rejeté | Devis rejeté par l'admin | Admin |
| `converted` | Converti en commande | Devis accepté converti en commande | Admin |
| `shipped` | Expédié | Marchandises expédiées | Admin |
| `delivered` | Livré | Marchandises livrées au client | Admin |
| `canceled` | Annulé | Devis annulé | Admin/Client |

## 🚀 Workflow Détaillé

### 1. Demande de Devis (Client)

**Page** : `/quote`
**Actions** :
- Le client remplit le formulaire avec ses informations
- Ajoute les produits souhaités avec photos et descriptions
- Choisit les méthodes d'expédition (maritime, aérien express, aérien normal)
- Sélectionne les services requis
- Accepte la politique de confidentialité

**Résultat** :
- Création automatique d'un devis avec numéro unique : `DUO-YYYYMMDD-XXXX`
- Génération d'un token de suivi UUID
- Statut initial : `pending`
- Envoi d'email de confirmation au client
- Notification admin de nouvelle demande

### 2. Consultation Admin

**Statut** : `pending` → `viewed`
**Actions admin** :
- Consultation du devis dans l'interface admin (`/admin/quotes/{id}`)
- Vérification des informations client
- Analyse des produits demandés

### 3. Traitement du Devis

**Statut** : `viewed` → `in_progress`
**Actions admin** :
- Prise en charge du devis via bouton "Traiter"
- Vérification du paiement si requis
- Recherche des produits sur le marché chinois
- Négociation avec les fournisseurs
- Calcul des coûts (produits + frais + transport)

### 4. Création de l'Offre

**Page admin** : `/admin/quote-offer/quote/{id}/create-offer`
**Actions** :
- Création d'une offre détaillée avec produits proposés
- Ajout d'images des produits trouvés
- Calcul des prix avec taux de change RMB/MGA
- Définition des options d'expédition
- Génération du PDF de l'offre

### 5. Envoi de l'Offre au Client

**Statut** : `in_progress` → `waiting_customer`
**Actions** :
- Génération automatique du PDF
- Envoi par email avec pièce jointe
- Notification client de réception de l'offre
- Mise à jour du statut de l'offre à `sent`

### 6. Réponse du Client

#### Option A - Acceptation (Client connecté)
**Route** : `/user/quotes/{id}/accept`
**Statut** : `waiting_customer` → `accepted`
**Actions** :
- Confirmation de l'acceptation
- Notification admin automatique
- Préparation de la conversion en commande

#### Option B - Acceptation (Client non connecté)
**Route** : `/tracking/{token}/accept`
**Statut** : `waiting_customer` → `accepted`
**Actions** :
- Même processus que l'acceptation connectée
- Accès via token de suivi

#### Option C - Refus (Client connecté)
**Route** : `/user/quotes/{id}/reject`
**Statut** : `waiting_customer` → `declined` → `in_progress`
**Actions** :
- Confirmation du refus
- Retour au traitement pour nouvelle offre
- Notification admin

#### Option D - Refus (Client non connecté)
**Route** : `/tracking/{token}/decline`
**Statut** : `waiting_customer` → `declined` → `in_progress`
**Actions** :
- Même processus que le refus connecté

### 7. Conversion en Commande

**Statut** : `accepted` → `converted`
**Actions admin** :
- Finalisation de la commande via bouton "Convertir en commande"
- Confirmation avec les fournisseurs
- Début du processus d'achat et d'expédition
- Notification client de la conversion

### 8. Expédition

**Statut** : `converted` → `shipped`
**Actions admin** :
- Achat des produits chez les fournisseurs
- Préparation de l'expédition
- Envoi vers Madagascar
- Mise à jour du statut via interface admin
- Notification client du départ

### 9. Livraison

**Statut** : `shipped` → `delivered`
**Actions admin** :
- Réception à Madagascar
- Dédouanement
- Livraison au client
- Confirmation de réception
- Mise à jour du statut final
- Notification client de livraison

## 🔍 Système de Suivi

### Pour le Client

**Lien de suivi** : `/tracking/{token}`
**Fonctionnalités** :
- Timeline des changements de statut
- Historique complet avec dates et commentaires
- Notifications automatiques par email
- Possibilité de répondre aux offres
- Recherche par numéro de devis (`/tracking/search`)

### Pour l'Admin

**Interface** : `/admin/quotes`
**Fonctionnalités** :
- Gestion complète des statuts
- Changement de statut avec commentaires
- Historique détaillé de chaque devis
- Notifications automatiques
- Statistiques et rapports
- Accès au suivi public

## 📧 Notifications Automatiques

| Événement | Destinataire | Contenu |
|-----------|--------------|---------|
| Création devis | Client | Confirmation avec token de suivi |
| Création devis | Admin | Notification nouvelle demande |
| Changement statut | Client | Mise à jour avec lien de suivi |
| Offre envoyée | Client | Offre avec boutons d'action |
| Offre acceptée | Admin | Notification pour conversion |
| Offre refusée | Admin | Notification pour nouvelle offre |
| Commande convertie | Client | Confirmation commande |
| Expédition | Client | Info tracking |
| Livraison | Client | Confirmation livraison |

## 🔒 Transitions Autorisées

```php
ALLOWED_TRANSITIONS = [
    'new' => ['pending', 'viewed', 'canceled'],
    'pending' => ['viewed', 'in_progress', 'canceled'],
    'viewed' => ['in_progress', 'pending', 'canceled'],
    'in_progress' => ['waiting_customer', 'completed', 'rejected', 'canceled'],
    'waiting_customer' => ['accepted', 'declined', 'in_progress', 'canceled'],
    'accepted' => ['converted', 'in_progress', 'canceled'],
    'declined' => ['in_progress', 'canceled'],
    'completed' => ['converted', 'shipped', 'canceled'],
    'rejected' => ['in_progress', 'canceled'],
    'converted' => ['shipped', 'delivered', 'canceled'],
    'shipped' => ['delivered', 'canceled'],
    'delivered' => [],
    'canceled' => ['pending', 'in_progress']
];
```

## 🌐 API Endpoints

### Public
- `GET /tracking/{token}` - Page de suivi
- `GET /api/tracking/{token}` - API JSON complète
- `GET /api/tracking/{token}/status` - API statut uniquement
- `GET /tracking/search` - Recherche de devis
- `GET /tracking/{token}/respond` - Page de réponse à l'offre
- `POST /tracking/{token}/accept` - Accepter l'offre
- `POST /tracking/{token}/decline` - Refuser l'offre

### Admin
- `GET /admin/quotes` - Liste des devis
- `GET /admin/quotes/{id}` - Détail d'un devis
- `POST /quote/{id}/process` - Traiter un devis
- `POST /quote/{id}/status/{status}` - Changer le statut
- `POST /quote/{id}/status-update` - Changer le statut avec commentaire
- `POST /quote/{id}/convert` - Convertir en commande
- `POST /quote/{id}/mark-as-paid` - Marquer comme payé

### Webhook
- `POST /webhook/quote-status` - Webhook pour systèmes tiers

## 🎯 Points de Contrôle

1. **Validation des transitions** : Seules les transitions autorisées sont possibles
2. **Historique complet** : Chaque changement est enregistré avec date, auteur et commentaire
3. **Notifications automatiques** : Emails clients et admin à chaque étape importante
4. **Suivi public** : Les clients peuvent suivre leur devis sans compte
5. **Gestion des paiements** : Système intégré pour les devis payants
6. **Génération PDF** : Offres automatiquement converties en PDF
7. **API complète** : Intégration possible avec systèmes tiers

## 📱 Responsive Design

Le système est entièrement responsive et fonctionne sur :
- Desktop
- Tablette
- Mobile

Toutes les interfaces s'adaptent automatiquement à la taille d'écran.

## 🔧 Maintenance

### Logs
- Tous les changements de statut sont loggés
- Erreurs d'email sont enregistrées
- Actions admin sont tracées

### Sauvegarde
- Base de données sauvegardée quotidiennement
- Fichiers PDF stockés de manière sécurisée
- Images uploadées optimisées automatiquement

### Performance
- Cache des taux de change
- Optimisation des requêtes base de données
- Compression des images
- Minification des assets CSS/JS
