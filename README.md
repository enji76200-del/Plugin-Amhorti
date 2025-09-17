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
- **🆕 Restriction temporelle** : Réservations limitées aux 7 prochains jours uniquement
- **🆕 Configuration avancée des feuilles** : Possibilité de configurer les jours actifs par feuille
- **🆕 Horaires spécifiques par feuille** : Chaque feuille peut avoir ses propres horaires
- **🆕 Éditeur CSS intégré** : Interface pour personnaliser l'apparence avec prévisualisation en temps réel
- **🆕 Interface en français** : Navigation et administration entièrement traduites
- **🆕 Édition inline des horaires** : Modifier directement les heures et le nombre de créneaux dans l’admin
- **🆕 Restauration des horaires par défaut** : Bouton pour réinsérer les horaires globaux si supprimés
- **🆕 Clonage des horaires globaux** : Copier les horaires globaux existants vers une feuille pour les personnaliser

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

1. Rendez-vous dans **Planification Amhorti** dans le menu d'administration
2. Utilisez **Gérer les Feuilles** pour ajouter/supprimer des feuilles
3. Utilisez **Gérer les Horaires** pour configurer les horaires par jour
4. **🆕 Configuration Avancée** : Configurez les jours actifs et horaires spécifiques par feuille
5. **🆕 Éditeur CSS** : Personnalisez l'apparence du tableau avec prévisualisation en temps réel

#### Nouvelles fonctionnalités d'administration

**Configuration Avancée des Feuilles** :
- Modification du nom des feuilles
- Sélection des jours de la semaine actifs par feuille (par exemple : une feuille uniquement pour les dimanches)
- Création d'horaires spécifiques à chaque feuille
- Interface intuitive pour une gestion fine des plannings

**Éditeur CSS Intégré** :
**Horaires** :
- Édition inline (bouton Modifier > Sauvegarder / Annuler)
- Suppression logique (les horaires restent en base mais inactifs)
- Bouton de restauration des horaires globaux par défaut si tous supprimés
- Clonage rapide des horaires globaux vers une feuille (page Configuration Avancée)

- Éditeur de code avec syntaxe highlighting
- Prévisualisation en temps réel des modifications
- Sauvegarde automatique des styles personnalisés
- Réinitialisation facile aux styles par défaut

### Navigation

- **Onglets** : Cliquez sur les onglets pour changer de feuille
- **Navigation temporelle** : Utilisez les boutons "Semaine précédente", "Aujourd'hui", "Semaine suivante"
- **Édition** : Cliquez dans les cellules pour écrire du texte (nom, prénom, etc.)
- **🆕 Restriction** : Seules les cellules des 7 prochains jours sont éditables

## Permissions

- **Utilisateurs publics** : Peuvent modifier les cellules de réservation uniquement
- **Administrateurs** : Peuvent modifier les horaires, créneaux, noms de feuilles et bloquer des créneaux

## Technique

### Structure de la base de données

Le plugin crée 4 tables :
- `wp_amhorti_bookings` : Stockage des réservations
- `wp_amhorti_sheets` : Configuration des feuilles (avec config des jours actifs)
- `wp_amhorti_schedules` : Configuration des horaires (globaux et par feuille)
- `wp_amhorti_css_settings` : **🆕** Stockage du CSS personnalisé

### Technologies utilisées

- **PHP** : Backend WordPress
- **JavaScript/jQuery** : Interface interactive
- **CSS** : Design responsive moderne
- **AJAX** : Sauvegarde en temps réel
- **JSON** : Configuration des jours actifs par feuille

### Nettoyage automatique

- Les réservations sont automatiquement supprimées après 14 jours
- Tâche cron programmée quotidiennement
- **🆕** Les dates antérieures à aujourd'hui ne sont plus affichées

### Sécurité et Restrictions

- **🆕** Réservations limitées aux 7 prochains jours maximum
- Validation côté serveur et client
- Protection CSRF avec nonces WordPress
- Sanitisation de toutes les entrées utilisateur
- **🆕** Validation serveur des heures (début < fin) et du nombre de créneaux

## Exemples d'utilisation

### Configuration par feuille
- **Feuille 1** : Planning général (tous les jours sauf dimanche)
- **Feuille 2** : Planning du week-end (samedi et dimanche uniquement)
- **Feuille 3** : Planning spécial (jours personnalisés avec horaires spécifiques)
- **Feuille 4** : Planning professionnel (lundi à vendredi uniquement)
- **Astuce** : Utilisez le bouton "Cloner les horaires globaux manquants" dans Configuration Avancée pour partir d'une base et ensuite ajuster feuille par feuille.

### Personnalisation CSS
```css
/* Exemple de personnalisation */
.amhorti-schedule-table {
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.booking-cell.editable {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
```

## Support

Pour toute question ou problème, veuillez créer une issue sur le repository GitHub.

## Licence

GPL v2 ou ultérieure