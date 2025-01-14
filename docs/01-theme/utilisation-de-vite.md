# Vite et WordPress

## Introduction

Vite est un outil de build rapide pour le développement de projets modernes. Il est basé sur la technologie ESBuild et permet de compiler les fichiers JavaScript, CSS, et autres fichiers statiques.

Il est important de copier le fichier `.env.example` en `.env` et de le configurer selon les besoins du projet.

## Configuration

### 1. Installer les dépendances

```bash
npm install
```

### 2. Lancer le serveur de développement

```bash
npm run watch
```

### 3. Compiler le thème pour la production

```bash
npm run production
```

### 4. Compiler le thème pour le staging

```bash
npm run staging
```

## Utilisation

### 1. Ajouter des fichiers

Ajouter les fichiers dans le dossier `src` du thème. Les fichiers seront automatiquement compilés et minifiés.

### 2. Utiliser les alias

Les alias sont configurés dans le fichier `vite.config.js` et permettent de simplifier l'import des fichiers. Au lieu d'écrire:

```javascript
import { myFunction } from '../../../utils/<file>';
```

Il est possible d'écrire:

```javascript
import { myFunction } from '@utils/<file>';
```

Pour ajouter un alias, il suffit de modifier le fichier `vite.config.js`:

```javascript
export default defineConfig({
  // ...
  resolve: {
    alias: {
      '@utils': path.resolve(__dirname, 'src/utils'),
    },
  },
  // ...
});
```

### 3. Plugins installés

#### 3.1. Vue.js

Le thème est configuré pour utiliser Vue.js. Il est possible d'ajouter des fichiers `.vue` dans le dossier `src/javascript/vue` et de les utiliser dans les fichiers JavaScript.

Le main du vue est dans le fichier `src/javascript/vue/main.js`.

#### 3.2. React

Le thème est configuré pour utiliser React. Il est possible d'ajouter des fichiers `.jsx` dans le dossier `src/javascript/react` et de les utiliser dans les fichiers JavaScript.

Le main du react est dans le fichier `src/javascript/react/main.jsx`.

#### Dynamic import

Que ce soit pour Vue.js ou React, il est possible d'utiliser le dynamic import pour charger les composants à la demande dans leur main respectif.

Cela permet de charger les composants uniquement lorsqu'ils sont nécessaires. Il suffit de les imports dans l'object `componentImports`.

Exemple:

```javascript
const componentImports = {
  "id-of-component": () => import("./path/to/component.ext")
};
```
