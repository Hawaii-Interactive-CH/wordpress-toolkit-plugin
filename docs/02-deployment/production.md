# How to Build the Theme for Production and Deploy to a Live Server

## Introduction

You need to build the theme in production mode to specify the correct asset paths for the live server.

Example: `http://my-site.domain`

## Configuration

### 1. Make sure the theme name or subfolder is set in the `.env` file at the project root

```bash
# .env
APP_NAME=my-theme
```

### 2. Run the build command

```bash
npm run production
```

## Deployment

- 1. Copy the `toolkit` folder to the production server in the `wp-content/themes` directory.
- 2. Make sure there is no `.dev` file at the theme root.
- 3. Activate the theme in the WordPress admin.
