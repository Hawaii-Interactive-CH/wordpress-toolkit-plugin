# Authentication Guide for Accessing Secured API Routes

## Introduction
To access secured API routes, a client must follow an authentication process that involves obtaining a transient token using a master token. Here is how to proceed:

## Authentication Steps

### 1. Obtain a Master Token
The client must first obtain a master token from the site administrator. This token is required to generate a transient token used for secured API requests.

### 2. Make a Request to the `/auth` Route
With the master token in hand, the client must send a POST request to the `/auth` route. This request must include the master token in the `Authorization` header.

**Request Example:**
- **URL**: `https://your-site.com/wp-json/api/v1/auth`
- **Method**: POST
- **Header**: Authorization: Bearer YOUR_MASTER_TOKEN

**cURL Example:**
```sh
curl -X POST https://your-site.com/wp-json/api/v1/auth \
-H "Authorization: Bearer YOUR_MASTER_TOKEN"
```

### 3. Receiving the Transient Token

If the request is successful, the response will contain a transient token. This token must be used for all subsequent requests to secured API routes.

**Response Example:**

```json
{
  "transient_token": "GENERATED_TRANSIENT_TOKEN"
}
```

### 4. Using the Transient Token for API Requests

To access other API routes, the client must include the transient token in the request headers.

**Request Example:**

- **URL**: https://your-site.com/wp-json/api/v1/auth/test
- **Method**: POST
- **Header**: Authorization: Bearer YOUR_TRANSIENT_TOKEN

**cURL Example:**

```sh
curl -X POST https://your-site.com/wp-json/api/v1/auth/test \
-H "Authorization: Bearer YOUR_TRANSIENT_TOKEN"
```

### 5. How to Define a Secured API Route

To define a secured API route, use the `register_rest_route` function. Make sure to include a transient token check in the route definition to ensure only authenticated clients can access it.

**Route definition example:**

```php
register_rest_route($namespace, '/test', array(
    'methods' => 'GET',
    'permission_callback' => array($authController, 'verify_transient_token'),
    'callback' => function() {
        return "test";
    },
));
```

**In this example:**
- **permission_callback**: Calls the `verify_transient_token` method on the `$authController` object. This method validates the transient token provided by the client in the request header.
- **callback**: Defines the function to execute if the token check passes. In this example, it simply returns "test".

## Error Cases

### Invalid or Missing Master Token

If the master token is invalid or missing, the request to `/auth` will fail with a 403 error.

**Response Example:**
```json
{
  "code": "invalid_master_token",
  "message": "Invalid or missing master token",
  "data": {
    "status": 403
  }
}
```

### Invalid or Expired Transient Token

If the transient token is invalid or has expired, the request to a secured route will fail with a 403 error.

**Response Example:**
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

By following these steps, a client can authenticate and access secured API routes using transient tokens generated from a master token. Make sure to handle tokens correctly and renew transient tokens before they expire to maintain continuous access to secured APIs.
