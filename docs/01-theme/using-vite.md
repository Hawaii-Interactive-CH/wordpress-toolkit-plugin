# Vite and WordPress

## Introduction

Vite is a fast build tool for modern project development. It is based on ESBuild technology and compiles JavaScript, CSS, and other static files.

Make sure to copy the `.env.example` file to `.env` and configure it according to the project's needs.

## Configuration

### 1. Install dependencies

```bash
npm install
```

### 2. Start the development server

```bash
npm run watch
```

### 3. Build the theme for production

```bash
npm run production
```

### 4. Build the theme for staging

```bash
npm run staging
```

## Usage

### 1. Adding files

Add files to the theme's `src` folder. They will be automatically compiled and minified.

### 2. Using aliases

Aliases are configured in `vite.config.js` and simplify file imports. Instead of writing:

```javascript
import { myFunction } from '../../../utils/<file>';
```

You can write:

```javascript
import { myFunction } from '@utils/<file>';
```

To add an alias, edit `vite.config.js`:

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

### 3. Installed plugins

#### 3.1. Vue.js

The theme is configured to use Vue.js. You can add `.vue` files in `src/javascript/vue` and use them in JavaScript files.

The Vue entry point is `src/javascript/vue/main.js`.

#### 3.2. React

The theme is configured to use React. You can add `.jsx` files in `src/javascript/react` and use them in JavaScript files.

The React entry point is `src/javascript/react/main.jsx`.

#### Dynamic import

For both Vue.js and React, you can use dynamic imports to load components on demand in their respective entry files.

This loads components only when needed. Simply add them to the `componentImports` object.

Example:

```javascript
const componentImports = {
  "id-of-component": () => import("./path/to/component.ext")
};
```
