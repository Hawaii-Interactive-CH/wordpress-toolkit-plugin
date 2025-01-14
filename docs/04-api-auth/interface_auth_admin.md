# Documentation pour l'Authentification API avec Transients dans WordPress

Ce document explique le fonctionnement et l'utilisation de l'interface d'authentification API que nous avons créée avec la classe `ApiAuthService`.

## Introduction

L'interface d'authentification API permet de gérer des tokens d'authentification pour sécuriser l'accès aux endpoints de votre API WordPress. Les principales fonctionnalités comprennent :

- Génération et stockage d'une clé de chiffrement.
- Génération d'un token master.
- Gestion de la durée de vie des tokens transients.
- Ajout et suppression d'adresses IP et de domaines dans une liste blanche.
- Nettoyage des tokens transients expirés.

## Fonctionnalités

### Génération de la clé de chiffrement

Cette fonctionnalité génère une clé de chiffrement unique et l'ajoute automatiquement au fichier `wp-config.php`.

1. Accédez à la page d'administration `API Authentication`.
2. Cliquez sur le bouton **Generate Encryption Key**.
3. Si une clé de chiffrement est déjà définie, le bouton sera désactivé et un message vous en informera.

### Génération du token master

Le token master est nécessaire pour générer des tokens transients.

1. Accédez à la page d'administration `API Authentication`.
2. Cliquez sur le bouton **Generate Master Token**.
3. Si la clé de chiffrement n'est pas définie, le bouton sera désactivé. Veuillez générer la clé de chiffrement d'abord.

### Configuration de la durée de vie des tokens transients

Vous pouvez définir la durée de vie des tokens transients en minutes.

1. Accédez à la page d'administration `API Authentication`.
2. Saisissez la durée de vie souhaitée (en minutes) dans le champ **Expiry Time (in minutes)**.
3. Cliquez sur le bouton **Save Expiry Time** pour enregistrer les modifications.

### Gestion de la liste blanche d'IP/Domaine

Cette section permet d'ajouter ou de supprimer des adresses IP ou des domaines autorisés à accéder à l'API.

1. **Ajouter une IP/Un domaine :**
    - Accédez à la page d'administration `API Authentication`.
    - Saisissez l'adresse IP ou le domaine dans le champ **IP/Domain**.
    - Cliquez sur le bouton **Add to Whitelist**.

2. **Supprimer une IP/Un domaine :**
    - Accédez à la page d'administration `API Authentication`.
    - Dans la section **Current Settings**, trouvez l'IP ou le domaine que vous souhaitez supprimer.
    - Cliquez sur le bouton **Remove** à côté de l'IP ou du domaine.

## Nettoyage des tokens transients expirés

Un cron job est configuré pour nettoyer automatiquement les tokens transients expirés toutes les heures. Cette tâche de nettoyage supprime les transients dont la date d'expiration est dépassée.

## Interface d'administration

La page d'administration `API Authentication` fournit une interface utilisateur pour gérer les fonctionnalités décrites ci-dessus. Voici un aperçu des sections disponibles :

1. **Generate Encryption Key**
    - Générer une clé de chiffrement unique.
    - Le bouton est désactivé si une clé est déjà définie.

2. **Generate Master Token**
    - Générer un token master.
    - Le bouton est désactivé si la clé de chiffrement n'est pas définie.

3. **Set Transient Token Expiry**
    - Définir la durée de vie des tokens transients en minutes.
    - Un champ de saisie pour la durée de vie et un bouton pour enregistrer les modifications.

4. **Whitelist IP/Domain**
    - Ajouter des adresses IP ou des domaines à la liste blanche.
    - Un champ de saisie pour l'IP ou le domaine et un bouton pour ajouter à la liste blanche.

5. **Current Settings**
    - Afficher les adresses IP et les domaines actuels dans la liste blanche.
    - Un bouton pour supprimer chaque IP ou domaine de la liste blanche.

---

Cette documentation vous aide à comprendre et utiliser l'interface d'authentification API que nous avons mise en place. Pour toute question ou assistance supplémentaire, veuillez consulter le développeur de votre projet ou la documentation officielle de WordPress.