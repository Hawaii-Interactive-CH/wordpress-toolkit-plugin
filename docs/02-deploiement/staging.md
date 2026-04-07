# How to Build the Theme for Staging and Deploy to a Test Server

## Introduction

When deploying to a test server where the site is in a subfolder, you need to build the theme in staging mode to specify the correct asset paths.

Example: `http://my-site.local/my-subfolder/`

## Configuration

### 1. Make sure the theme name or subfolder is set in the `.env` file at the project root

```bash
# .env
APP_NAME=my-theme
```

### 2. Run the build command

```bash
npm run staging
```

## Deployment

- 1. Copy the `toolkit` folder to the test server in the `wp-content/themes` directory.
- 2. Make sure there is no `.dev` file at the theme root.
- 3. Activate the theme in the WordPress admin.
