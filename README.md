# Plugin Amhorti Schedule

Un plugin WordPress qui crée un tableau de planification similaire à Excel avec plusieurs feuilles pour la réservation de créneaux horaires.

## Fonctionnalités

- **Interface similaire à Excel** : Tableau avec onglets pour différentes feuilles
- **Vue sur 7 jours** : Affichage d'une semaine complète à partir de la date actuelle
- **Cellules éditables** : Les utilisateurs peuvent écrire et effacer dans les créneaux
- **Gestion administrative** : Interface d'administration pour gérer les feuilles et horaires
- **Nettoyage automatique** : Suppression automatique des données après 14 jours
- **Design responsive** : Interface moderne adaptée mobile et desktop
- **Créneaux configurables** : Horaires et nombre de créneaux modifiables par jour

## Installation

1. Téléchargez ou clonez le plugin dans le dossier `/wp-content/plugins/`
2. Activez le plugin depuis l'interface d'administration WordPress
3. Les tables de base de données seront créées automatiquement lors de l'activation

## Configuration par défaut

Le plugin est livré avec des horaires pré-configurés :

### Lundi
- 06:00 - 07:00 (3 créneaux)
- 07:30 - 08:30 (3 créneaux)
- 08:30 - 10:00 (2 créneaux)
- 10:00 - 11:30 (2 créneaux)
- 11:30 - 13:00 (2 créneaux)
- 13:00 - 14:30 (2 créneaux)
- 14:30 - 16:00 (2 créneaux)
- 16:00 - 17:30 (2 créneaux)
- 17:30 - 19:00 (3 créneaux)
- 19:00 - 20:00 (3 créneaux)

### Mardi à Vendredi
- 07:30 - 08:30 (3 créneaux)
- 08:30 - 10:00 (2 créneaux)
- 10:00 - 11:30 (2 créneaux)
- 11:30 - 13:00 (2 créneaux)
- 13:00 - 14:30 (2 créneaux)
- 14:30 - 16:00 (2 créneaux)
- 16:00 - 17:30 (2 créneaux)
- 17:30 - 19:00 (3 créneaux)
- 19:00 - 20:00 (3 créneaux)

### Samedi
- 13:00 - 14:30 (2 créneaux)
- 14:30 - 16:00 (2 créneaux)
- 16:00 - 17:30 (2 créneaux)
- 17:30 - 19:00 (3 créneaux)
- 19:00 - 20:00 (3 créneaux)

### Dimanche
- Aucun créneau par défaut

## Utilisation

### Affichage public

Utilisez le shortcode suivant pour afficher le tableau de planification :

```
[amhorti_schedule]
```

Pour afficher une feuille spécifique :

```
[amhorti_schedule sheet="1"]
```

### Administration

1. Rendez-vous dans **Amhorti Schedule** dans le menu d'administration
2. Utilisez **Manage Sheets** pour ajouter/supprimer des feuilles
3. Utilisez **Manage Schedules** pour configurer les horaires par jour

### Navigation

- **Onglets** : Cliquez sur les onglets pour changer de feuille
- **Navigation temporelle** : Utilisez les boutons "Previous Week", "Today", "Next Week"
- **Édition** : Cliquez dans les cellules pour écrire du texte (nom, prénom, etc.)

## Permissions

- **Utilisateurs publics** : Peuvent modifier les cellules de réservation uniquement
- **Administrateurs** : Peuvent modifier les horaires, créneaux, noms de feuilles et bloquer des créneaux

## Technique

### Structure de la base de données

Le plugin crée 3 tables :
- `wp_amhorti_bookings` : Stockage des réservations
- `wp_amhorti_sheets` : Configuration des feuilles
- `wp_amhorti_schedules` : Configuration des horaires

### Technologies utilisées

- **PHP** : Backend WordPress
- **JavaScript/jQuery** : Interface interactive
- **CSS** : Design responsive moderne
- **AJAX** : Sauvegarde en temps réel

### Nettoyage automatique

- Les réservations sont automatiquement supprimées après 14 jours
- Tâche cron programmée quotidiennement

## Support

Pour toute question ou problème, veuillez créer une issue sur le repository GitHub.

## Licence

GPL v2 ou ultérieure