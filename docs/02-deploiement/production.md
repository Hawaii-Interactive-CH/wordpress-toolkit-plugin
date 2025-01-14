# Comment compiler le thème en mode staging et le déployer sur un serveur de test

## Introduction

Il est nécessaire de compiler le thème en mode production pour indiquer le bon chemin des assets une fois sur le serveur de test.

Example: `http://mon-site.domain`

## Configuration

### 1. S'assurer d'avoir le nom du theme ou sous dossier dans le fichier `.env` à la racine du projet

```bash
# .env
APP_NAME=mon-theme
```

### 2. Lancer la commande de compilation

```bash
npm run production
```

## Déploiement

- 1. Copier le dossier `toolkit` sur le serveur de production dans le dossier `wp-content/themes`.
- 2. S'assurer de ne pas avoir un fichie `.dev` à la racine du thème.
- 3. Activer le thème dans l'administration de WordPress.
