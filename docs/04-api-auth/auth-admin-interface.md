# API Authentication with Transients in WordPress

This document explains how the API authentication interface built with the `ApiAuthService` class works and how to use it.

## Introduction

The API authentication interface manages authentication tokens to secure access to your WordPress API endpoints. Key features include:

- Generating and storing an encryption key.
- Generating a master token.
- Managing the lifetime of transient tokens.
- Adding and removing IP addresses and domains from a whitelist.
- Cleaning up expired transient tokens.

## Features

### Generating the Encryption Key

This feature generates a unique encryption key and automatically adds it to the `wp-config.php` file.

1. Go to the `API Authentication` admin page.
2. Click the **Generate Encryption Key** button.
3. If an encryption key is already defined, the button will be disabled and a message will inform you.

### Generating the Master Token

The master token is required to generate transient tokens.

1. Go to the `API Authentication` admin page.
2. Click the **Generate Master Token** button.
3. If the encryption key is not defined, the button will be disabled. Generate the encryption key first.

### Configuring Transient Token Lifetime

You can set the lifetime of transient tokens in minutes.

1. Go to the `API Authentication` admin page.
2. Enter the desired lifetime (in minutes) in the **Expiry Time (in minutes)** field.
3. Click **Save Expiry Time** to save the changes.

### Managing the IP/Domain Whitelist

This section allows adding or removing IP addresses or domains authorised to access the API.

1. **Adding an IP/Domain:**
    - Go to the `API Authentication` admin page.
    - Enter the IP address or domain in the **IP/Domain** field.
    - Click **Add to Whitelist**.

2. **Removing an IP/Domain:**
    - Go to the `API Authentication` admin page.
    - In the **Current Settings** section, find the IP or domain you want to remove.
    - Click the **Remove** button next to it.

## Cleaning Up Expired Transient Tokens

A cron job is configured to automatically clean up expired transient tokens every hour. This task removes transients whose expiry date has passed.

## Admin Interface

The `API Authentication` admin page provides a UI for managing the features described above. Here is an overview of the available sections:

1. **Generate Encryption Key**
    - Generate a unique encryption key.
    - The button is disabled if a key is already defined.

2. **Generate Master Token**
    - Generate a master token.
    - The button is disabled if the encryption key is not defined.

3. **Set Transient Token Expiry**
    - Set the lifetime of transient tokens in minutes.
    - An input field for the lifetime and a button to save changes.

4. **Whitelist IP/Domain**
    - Add IP addresses or domains to the whitelist.
    - An input field for the IP or domain and a button to add to the whitelist.

5. **Current Settings**
    - Display current whitelisted IP addresses and domains.
    - A button to remove each IP or domain from the whitelist.

---

For any questions or further assistance, consult your project developer or the official WordPress documentation.
