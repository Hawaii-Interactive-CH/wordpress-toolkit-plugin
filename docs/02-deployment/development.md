# How to Build the Theme in Development Mode

## Introduction

You need to build the theme in local mode to specify the correct asset paths when running on a local server. When the site is in a subfolder — for example with Local by Flywheel — you need to build in local mode to set the correct asset paths when `vite` is not used for development.

Example: `http://my-site.local`

For the site to work correctly with `vite`, a `.dev` file must exist at the project root. This enables assets to be loaded from the development server. The `.dev` file is automatically created when running `npm run watch`.

## Configuration

### 1. Make sure the theme name or subfolder is set in the `.env` file at the project root

```bash
# .env
APP_NAME=my-theme
```

### 2. Start the development server with hot reload

```bash
npm run watch
```

### 3. Build in local mode (preview of the compiled site)

```bash
npm run local
```

If the locally compiled site looks correct, you can deploy it to the test server by building in `staging` or `production` mode for the live site.
