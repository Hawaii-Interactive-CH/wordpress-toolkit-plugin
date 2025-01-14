# Guide d'Authentification pour Accéder aux Routes API Sécurisées

## Introduction
Pour accéder aux routes API sécurisées, un client doit suivre un processus d'authentification qui implique l'obtention d'un token transient en utilisant un token master. Voici comment procéder :

## Étapes d'Authentification

### 1. Obtenir un Token Master
Le client doit d'abord obtenir un token master de l'administrateur du site. Ce token est essentiel pour générer un token transient qui sera utilisé pour les requêtes API sécurisées.

### 2. Faire une Requête à la Route `/auth`
Avec le token master en main, le client doit envoyer une requête POST à la route `/auth`. Cette requête doit inclure le token master dans l'en-tête `Authorization`.

**Exemple de Requête** :
- **URL** : `https://votre-site.com/wp-json/api/v1/auth`
- **Méthode** : POST
- **En-tête** : Authorization: Bearer VOTRE_TOKEN_MASTER

**Exemple de Code (cURL)** :
```sh
curl -X POST https://votre-site.com/wp-json/api/v1/auth \
-H "Authorization: Bearer VOTRE_TOKEN_MASTER"
```
### 3. Réception du Token Transient

Si la requête est réussie, la réponse contiendra un token transient. Ce token doit être utilisé pour toutes les requêtes suivantes aux routes sécurisées de l’API.

**Exemple de Réponse** :

```json
{
  "transient_token": "TOKEN_TRANSIENT_GÉNÉRÉ"
}
```
### 4. Utiliser le Token Transient pour les Requêtes API

Pour accéder aux autres routes de l’API, le client doit inclure le token transient dans les en-têtes de ses requêtes.

**Exemple de Requête** :

- **URL**: https://votre-site.com/wp-json/api/v1/auth/test
- **Méthode** : POST
- **En-tête** :	En-tête : Authorization: Bearer VOTRE_TOKEN_TRANSIENT

**Exemple de Code (cURL)** :

```sh
curl -X POST https://votre-site.com/wp-json/api/v1/auth/test \
-H "Authorization: Bearer VOTRE_TOKEN_TRANSIENT"
```

### 5. Comment Définir une Route API Sécurisée

Pour définir une route API sécurisée, vous devez utiliser la fonction register_rest_route. Il est essentiel d’inclure une vérification du token transient dans la définition de la route pour s’assurer que seuls les clients authentifiés peuvent accéder à cette route.

**Voici un exemple de définition de route**

```php
register_rest_route($namespace, '/test', array(
    'methods' => 'GET',
    'permission_callback' => array($authController, 'verify_transient_token'),
    'callback' => function() {
        return "test";
    },
));
```
**Dans cet exemple :**
- **permission_callback** : Cette clé est définie pour appeler la méthode verify_transient_token de l’objet $authController. Cette méthode vérifie la validité du token transient fourni par le client dans l’en-tête de la requête.
- **callback** : Cette clé définit la fonction à exécuter si la vérification du token est réussie. Dans cet exemple, elle retourne simplement “test”.

## Cas d’Erreur

### Token Master Invalide ou Manquant

Si le token master fourni est invalide ou manquant, la requête à la route /auth échouera avec une erreur 403.

**Exemple de Réponse** :
```json
{
  "code": "invalid_master_token",
  "message": "Invalid or missing master token",
  "data": {
    "status": 403
  }
}
```
### Token Transient Invalide ou Expiré

Si le token transient est invalide ou a expiré, la requête à une route sécurisée échouera avec une erreur 403.

**Exemple de Réponse** :
```json
{
  "code": "invalid_transient_token",
  "message": "Token expired or invalid, please provide master token to generate a new transient token",
  "data": {
    "status": 403
  }
}
```

### Conclusion

En suivant ces étapes, un client peut s’authentifier et accéder aux routes API sécurisées en utilisant des tokens transients générés à partir d’un token master. Assurez-vous de gérer correctement les tokens et de renouveler les tokens transients avant leur expiration pour maintenir un accès continu aux API sécurisées.