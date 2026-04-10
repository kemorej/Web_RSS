# 📰 Lecteur de Flux RSS/Atom

Un **lecteur de flux RSS/Atom** moderne et personnalisable, avec gestion des catégories, affichage des images.

---

## ✨ Fonctionnalités

### 📌 **Gestion des flux**
- **Ajout/Modification/Supppression** de flux RSS et Atom.
- **Import/Export** de flux au format **OPML** (compatible avec Feedly, Inoreader, etc.).
- **Catégorisation** des flux avec des onglets pour une navigation intuitive.

### 🎨 **Affichage personnalisable**
- **5 modes d'affichage** pour chaque flux :
  - 10 derniers articles complets.
  - Uniquement les 10 derniers titres.
  - Articles complets des 2 derniers jours.
  - Uniquement les titres des 2 derniers jours.
  - Article le plus récent + 9 titres.
- **Affichage des images** :
  - Image du flux (depuis `<channel><image>` ou `<logo>`).
  - Images des articles (depuis `<enclosure>`, `<media:content>`, ou balises `<img>`).
- **Liens cliquables** :
  - Titre du flux lié à son site web.
  - Titres des articles liés à leur URL.
- **Indication des dates** :
  - Affichage de la date (DD/MM/YYYY) pour les articles anciens.

### 🔧 **Technologies utilisées**
- **Frontend** : HTML5, CSS3, JavaScript (vanilla).
- **Backend** : PHP 7.4+ (avec `simplexml`, `file_get_contents`, etc.).
- **Stockage** : Fichier XML (`feeds.xml`) pour les flux et leurs configurations.

---

## 🛠 Prérequis

- **Serveur web** (Apache, Nginx, etc.) avec **PHP 7.4 ou supérieur**.
- **Extensions PHP activées** :
  - `simplexml`
  - `file_get_contents` (avec `allow_url_fopen = On` dans `php.ini`).
  - `curl` (recommandé pour une meilleure gestion des requêtes HTTP).
- **Permissions** :
  - Le serveur doit avoir les droits d'écriture sur le dossier du projet (pour `feeds.xml` et les fichiers temporaires).

---

## 🚀 Installation

1. **Cloner ou télécharger le projet** :
   ```bash
   git clone https://github.com/ton-utilisateur/lecteur-flux-rss.git
   cd lecteur-flux-rss
