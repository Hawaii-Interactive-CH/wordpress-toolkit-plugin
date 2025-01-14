# Comment compiler le thème en mode staging et le déployer sur un serveur de test

## Introduction

Il est nécessaire de compiler le thème en mode local pour indiquer le bon chemin des assets une fois sur le serveur de test. Lorsque le site se trouve dans un sous-dossier, par example dans le cas d'un serveur de Local by Flywheel, il est nécessaire de compiler le thème en mode local uniquement pour indiquer le bon chemin des assets quand `vite` n'est pas utilisé pour le développement.

Example: `http://mon-site.local`

Pour que le site fonctionne correctement avec `vite`, il est nécessaire d'avoir un fichier `.dev` à la racine du projet. Cela permet de charger les assets depuis le serveur de développement. Le fichier `.dev` est automatiquement créé lors de l'utilisation de la commande `npm run watch`.

## Configuration

### 1. S'assurer d'avoir le nom du theme ou sous dossier dans le fichier `.env` à la racine du projet

```bash
# .env
APP_NAME=mon-theme
```

### 2. Lancer la commande de compilation de development hot reload

```bash
npm run watch
```

### 3. Lancer la commande de compilation en mode local (preview du site compilé)

```bash
npm run local
```

Si le site compilé en local convient, il est possible de le déployer sur le serveur de test en compilant en mode `staging` ou `production` pour le site live.
