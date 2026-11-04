# Application de signalement des problèmes urbains

Application web (HTML, CSS, JavaScript, PHP, MySQL) qui permet aux habitants
et aux visiteurs de signaler un problème dans l'espace public et de suivre
son traitement, du dépôt jusqu'à la résolution.

Projet DW1 — Sujet n° 7.

## Fonctionnalités (cahier des charges du professeur)

| Exigence | Où elle est implémentée |
|---|---|
| Déclaration avec catégorie, description et photographie | `signaler.php` |
| Localisation sur une carte | `signaler.php` + `index.php` (Leaflet / OpenStreetMap, clic sur la carte, géolocalisation) |
| Consultation des signalements existants | `index.php` (public, avec filtres) |
| Affectation à un service de traitement | Fiche d'un signalement, rôle administrateur (`signalement.php`) |
| Suivi des statuts : reçu, validé, en cours, résolu ou rejeté | Fiche d'un signalement, historique des statuts |
| Ajout de commentaires et de photographies de résolution | Fiche d'un signalement (`signalement.php`) |
| Notification des changements de statut | `notifications.php` + pastille dans l'en-tête (notification créée à chaque changement de statut) |
| Tableau de bord par quartier et type de problème | `tableau-de-bord.php` (par quartier, par catégorie, par statut, par service) |
| Règle de gestion : un signalement rejeté doit comporter un motif visible par son auteur | Le motif est obligatoire pour passer au statut `rejeté`, et il est affiché en évidence sur la fiche, pour l'auteur comme pour les autres rôles |

## Rôles

- **Déclarant** : crée un signalement, consulte ses signalements, commente,
  reçoit les notifications, voit le motif en cas de rejet.
- **Agent de traitement** : voit les signalements qui lui sont affectés (et
  ceux de son service), change le statut, ajoute des commentaires et des
  photographies de résolution.
- **Administrateur** : gère les utilisateurs, les catégories et les services,
  affecte un signalement à un service et/ou à un agent, change le statut.

À l'inscription, un nouvel utilisateur est toujours créé comme déclarant.
Les comptes agent et administrateur sont créés par l'administrateur
(`admin.php`), pour éviter qu'un visiteur se donne lui-même un rôle
privilégié.

## Installation

### 1. Base de données

Créez une base MySQL vide, par exemple `signalement_urbain`, puis au choix :

- **Option A (la plus simple)** : ouvrez `install.php` dans le navigateur.
  Il crée les tables, les catégories, les services et les comptes de
  démonstration. Supprimez ensuite `install.php` du serveur.
- **Option B** : importez `database/schema.sql` dans phpMyAdmin ou avec
  `mysql -u root -p signalement_urbain < database/schema.sql`, puis créez
  les comptes depuis `admin.php` (ou adaptez le fichier SQL).

### 2. Configuration

Modifiez les identifiants dans `config/config.php` :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'signalement_urbain');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Lancement

Placez le dossier dans le répertoire de votre serveur local
(`htdocs` pour XAMPP, `www` pour WampServer), démarrez Apache et MySQL,
puis ouvrez `http://localhost/signalement-urbain/`.

Le dossier `uploads/` doit être accessible en écriture par le serveur web.
Les photographies envoyées sont limitées à JPG, PNG et WebP (5 Mo maximum),
renommées avec un nom aléatoire, et jamais exécutées comme du code.

## Comptes de démonstration (créés par `install.php`)

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | admin@demo.local | Admin123! |
| Agent de traitement | agent@demo.local | Agent123! |
| Déclarant | citoyen@demo.local | Citoyen123! |

Changez ces mots de passe après la démonstration, ou supprimez ces comptes.

## Structure du projet

```
config/config.php          Configuration de la base de données
includes/db.php            Connexion PDO à MySQL
includes/auth.php          Session, rôles, contrôle d'accès
includes/functions.php     Statuts, notifications, upload de photos, helpers
includes/header.php        En-tête commun (menu selon le rôle)
includes/footer.php        Pied de page commun
assets/css/style.css       Tout le style de l'application
assets/js/app.js           Cartes Leaflet (dépôt + consultation)
index.php                  Liste publique + carte des signalements
signaler.php               Formulaire de déclaration
signalement.php            Fiche : détails, carte, statut, affectation,
                           commentaires, photos de résolution, historique
mes-signalements.php       Signalements de l'utilisateur connecté
mes-affectations.php       Signalements affectés à l'agent connecté
notifications.php          Notifications de l'utilisateur connecté
tableau-de-bord.php        Statistiques par quartier, catégorie, statut, service
admin.php            Gestion des utilisateurs, catégories et services
login.php / register.php / logout.php   Authentification
install.php                Création des tables + données de démonstration
database/schema.sql        Même schéma, pour import manuel
uploads/                   Photographies envoyées (dossier vide au départ)
```

## Choix techniques, inspirés des projets existants

L'idée est la même que FixMyStreet, Mark-a-Spot, uReport ou GetItFixed
(signalement géolocalisé + suivi public), mais l'implémentation ici est
entièrement originale et volontairement simple, adaptée à un projet DW1 :
pas de framework, PHP procédural lisible, requêtes préparées PDO,
Leaflet + OpenStreetMap (gratuit, sans clé API) pour la carte, et
Chart.js pour les graphiques du tableau de bord.

## Sécurité (bases)

- Mots de passe hachés avec `password_hash()` / `password_verify()`.
- Requêtes préparées PDO contre les injections SQL.
- Échappement HTML (`htmlspecialchars`) contre les XSS.
- Vérification du type et de la taille des photographies envoyées.
- Contrôle du rôle à chaque page sensible, pas seulement dans le menu.
