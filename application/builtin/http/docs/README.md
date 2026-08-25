# Complete WebCycles HTTP Module Documentation

Welcome to the definitive reference manual for the **WebCycles HTTP Module**. This document provides an exhaustive, in-depth guide covering every feature, parameter, option, class, and method available, demonstrating all possible ways of using the module with practical code examples.

---

## Table of Contents
1. [Core Architecture & Lifecycle](#1-core-architecture--lifecycle)
2. [HTTP Routing (`Router` & `Route`)](#2-http-routing-router--route)
   - [2.1 Registering Routes for Standard Verbs](#21-registering-routes-for-standard-verbs)
   - [2.2 Multi-Method & Any-Method Routes](#22-multi-method--any-method-routes)
   - [2.3 All Ways to Define Route Handlers](#23-all-ways-to-define-route-handlers)
   - [2.4 Route Parameters & Dynamic Segments](#24-route-parameters--dynamic-segments)
   - [2.5 Regex Constraints & Validation](#25-regex-constraints--validation)
   - [2.6 Wildcards: Single-Segment and Multi-Segment](#26-wildcards-single-segment-and-multi-segment)
   - [2.7 Default Parameter Values](#27-default-parameter-values)
   - [2.8 Named Routes & Reverse Lookup](#28-named-routes--reverse-lookup)
   - [2.9 Sub-Routers & Modular Mounting (`mount`)](#29-sub-routers--modular-mounting-mount)
   - [2.10 Automatic Dependency Injection via Reflection](#210-automatic-dependency-injection-via-reflection)
   - [2.11 Dispatching & Running the Router](#211-dispatching--running-the-router)
3. [Incoming Requests (`Request`)](#3-incoming-requests-request)
   - [3.1 Creating Request Instances](#31-creating-request-instances)
   - [3.2 All Ways to Read Input Parameters](#32-all-ways-to-read-input-parameters)
   - [3.3 Reading Specifically from GET, POST, or JSON Body](#33-reading-specifically-from-get-post-or-json-body)
   - [3.4 Strictly Typed Input Casting](#34-strictly-typed-input-casting)
   - [3.5 Filtering & Checking Input Presence](#35-filtering--checking-input-presence)
   - [3.6 HTTP Method & Method Spoofing](#36-http-method--method-spoofing)
   - [3.7 URL, Scheme, Port, Host & Path Detection](#37-url-scheme-port-host--path-detection)
   - [3.8 URL Path Segments](#38-url-path-segments)
   - [3.9 Marketing UTM Parameters](#39-marketing-utm-parameters)
   - [3.10 Headers & Bearer Token Authentication](#310-headers--bearer-token-authentication)
   - [3.11 Client IP & Trusted Proxies](#311-client-ip--trusted-proxies)
   - [3.12 Content Negotiation (AJAX, JSON, etc.)](#312-content-negotiation-ajax-json-etc)
   - [3.13 Raw Body & Payload Parsing](#313-raw-body--payload-parsing)
   - [3.14 Custom Request Attributes](#314-custom-request-attributes)
4. [HTTP Responses (`Response`)](#4-http-responses-response)
   - [4.1 Automatic Type Normalization from Handlers](#41-automatic-type-normalization-from-handlers)
   - [4.2 Base `Response` Object](#42-base-response-object)
   - [4.3 JSON Responses (`JsonResponse`)](#43-json-responses-jsonresponse)
   - [4.4 Redirection Responses (`RedirectResponse`)](#44-redirection-responses-redirectresponse)
   - [4.5 Streaming & SSE Responses (`StreamedResponse`)](#45-streaming--sse-responses-streamedresponse)
   - [4.6 HTTP Status Codes & Reason Phrases (`HttpStatus`)](#46-http-status-codes--reason-phrases-httpstatus)
   - [4.7 Managing Response Headers & Cookies](#47-managing-response-headers--cookies)
   - [4.8 Sending and Flushing the Response](#48-sending-and-flushing-the-response)
5. [HTTP Cookies (`Cookie`)](#5-http-cookies-cookie)
   - [5.1 Constructing & Creating Cookies](#51-constructing--creating-cookies)
   - [5.2 Cookie Expiration Options](#52-cookie-expiration-options)
   - [5.3 SameSite Policies](#53-samesite-policies)
   - [5.4 Deleting / Forgetting Cookies](#54-deleting--forgetting-cookies)
   - [5.5 Emitting Set-Cookie Headers](#55-emitting-set-cookie-headers)
6. [Middleware & Execution Pipeline (`Pipeline`)](#6-middleware--execution-pipeline-pipeline)
   - [6.1 Implementing `MiddlewareInterface`](#61-implementing-middlewareinterface)
   - [6.2 All Ways to Supply Middleware (Class, Instance, Callable, Closure)](#62-all-ways-to-supply-middleware-class-instance-callable-closure)
   - [6.3 Global Middleware](#63-global-middleware)
   - [6.4 Pattern-Based & Wildcard Middleware](#64-pattern-based--wildcard-middleware)
   - [6.5 Route-Specific Middleware](#65-route-specific-middleware)
   - [6.6 Sub-Router Mounted Middleware](#66-sub-router-mounted-middleware)
   - [6.7 Standalone `Pipeline` Execution](#67-standalone-pipeline-execution)
7. [Uploaded Files (`UploadedFile`)](#7-uploaded-files-uploadedfile)
   - [7.1 Accessing Files from the Request](#71-accessing-files-from-the-request)
   - [7.2 Validation & Error Checking](#72-validation--error-checking)
   - [7.3 Inspecting File Metadata](#73-inspecting-file-metadata)
   - [7.4 Moving and Storing Uploaded Files](#74-moving-and-storing-uploaded-files)
   - [7.5 Handling Nested and Multiple File Arrays](#75-handling-nested-and-multiple-file-arrays)
8. [Data Containers (`ParameterBag` & `HeaderBag`)](#8-data-containers-parameterbag--headerbag)
   - [8.1 ParameterBag Operations & Typed Retrieval](#81-parameterbag-operations--typed-retrieval)
   - [8.2 HeaderBag Case-Insensitive Header Management](#82-headerbag-case-insensitive-header-management)
9. [HTTP Exceptions & Error Handling](#9-http-exceptions--error-handling)
   - [9.1 Base `HttpException`](#91-base-httpexception)
   - [9.2 `RouteNotFoundException` (404)](#92-routenotfoundexception-404)
   - [9.3 `MethodNotAllowedException` (405)](#93-methodnotallowedexception-405)
   - [9.4 `ControllerResolutionException` (500)](#94-controllerresolutionexception-500)
   - [9.5 Automatic JSON / HTML Error Formatting](#95-automatic-json--html-error-formatting)
10. [End-to-End Real World Examples](#10-end-to-end-real-world-examples)
    - [10.1 Complete RESTful JSON API](#101-complete-restful-json-api)
    - [10.2 Server-Sent Events (SSE) Live Feed](#102-server-sent-events-sse-live-feed)
    - [10.3 Authenticated File Upload with CSRF & Token Verification](#103-authenticated-file-upload-with-csrf--token-verification)

---

## 1. Core Architecture & Lifecycle

When a web request hits `public/index.php`, the lifecycle proceeds as follows:

```
[ Incoming HTTP Request ]
          │
          ▼
  Request::createFromGlobals()
          │
          ▼
  Router::run($request)
          │
          ├── 1. Matches Path & HTTP Method against registered Routes
          ├── 2. Extracts Parameters & Wildcards into $request->attributes
          ├── 3. Compiles Middleware Pipeline:
          │      [ Global MW ] ➔ [ Pattern MW ] ➔ [ Route MW ]
          ├── 4. Resolves Destination Controller / Closure via Reflection DI
          ├── 5. Normalizes Handler Return Value to a Response object
          └── 6. Sends Status Code, Headers, Cookies, and Body to Client
```

---

## 2. HTTP Routing (`Router` & `Route`)

### 2.1 Registering Routes for Standard Verbs
The `Router` provides dedicated convenience methods for every common HTTP verb:

```php
use WebCycles\Foundations\HTTP\Router;
use WebCycles\Foundations\HTTP\Request;

$router = new Router();

// GET & HEAD
$router->get('/articles', fn () => 'List of articles');

// POST
$router->post('/articles', fn (Request $req) => 'Article created: ' . $req->title);

// PUT (full update/replace)
$router->put('/articles/{id}', fn (int $id, Request $req) => "Article {$id} replaced");

// PATCH (partial update)
$router->patch('/articles/{id}', fn (int $id, Request $req) => "Article {$id} patched");

// DELETE
$router->delete('/articles/{id}', fn (int $id) => "Article {$id} deleted");

// OPTIONS
$router->options('/articles', fn () => 'Allow: GET, POST, OPTIONS');
```

---

### 2.2 Multi-Method & Any-Method Routes

#### Matching Any HTTP Method (`any`)
Matches any valid HTTP method:
```php
$router->any('/webhook', function (Request $request) {
    return 'Received ' . $request->getMethod() . ' webhook payload';
});
```

#### Matching Specific HTTP Methods (`match`)
Accepts an array of allowed HTTP verbs:
```php
$router->match(['GET', 'POST'], '/contact', function (Request $request) {
    if ($request->isMethod('POST')) {
        return 'Processing contact form submission...';
    }
    return '<form method="POST"><input name="message"/><button>Send</button></form>';
});
```

#### Registering a Raw `Route` Object (`addRoute`)
```php
use WebCycles\Foundations\HTTP\Route;

$customRoute = new Route(['GET', 'POST'], '/custom', fn () => 'Custom route instance');
$router->addRoute($customRoute);
```

---

### 2.3 All Ways to Define Route Handlers

The router supports **5 distinct handler formats**:

#### 1. Anonymous Function / Closure
```php
$router->get('/closure', function (Request $request) {
    return 'Closure response';
});
```

#### 2. Short Arrow Function (`fn`)
```php
$router->get('/arrow', fn () => 'Short arrow function response');
```

#### 3. String Action Syntax (`ControllerClass@method` or `ControllerClass::method`)
```php
$router->get('/users/{id}', 'App\Controllers\UserController@show');
$router->get('/posts/{id}', 'App\Controllers\PostController::view');
```

#### 4. Array Callable Syntax (`[ControllerClass, 'method']` or `[$instance, 'method']`)
```php
// With class name (instantiated on demand via Reflection DI):
$router->get('/users', [\App\Controllers\UserController::class, 'index']);

// With pre-existing object instance:
$controller = new \App\Controllers\UserController();
$router->get('/users/active', [$controller, 'activeUsers']);
```

#### 5. Invokable Class Name (`__invoke`)
```php
namespace App\Controllers;

class ShowDashboardController
{
    public function __invoke(Request $request): string
    {
        return 'Welcome to dashboard!';
    }
}

// In router:
$router->get('/dashboard', \App\Controllers\ShowDashboardController::class);
```

---

### 2.4 Route Parameters & Dynamic Segments

Parameters are written inside curly braces `{name}`:

```php
$router->get('/users/{userId}/posts/{postId}', function (int $userId, int $postId) {
    return "User #{$userId}, Post #{$postId}";
});
```

#### How Parameters are Injected:
1. **By parameter name in the handler function signature**:
   ```php
   $router->get('/users/{id}', function (int $id) { ... });
   ```
2. **From `$request->attributes`**:
   ```php
   $router->get('/users/{id}', function (Request $request) {
       $id = $request->attributes->get('id');
       // or: $id = $request->getAttribute('id');
       // or: $id = $request->id;
   });
   ```

---

### 2.5 Regex Constraints & Validation

#### Approach 1: Inline Regex inside Curly Braces
Format: `{parameterName:regexPattern}`
```php
// Only matches if {id} is one or more digits:
$router->get('/users/{id:[0-9]+}', fn (int $id) => "User ID: {$id}");

// Only matches if {slug} is lowercase letters and hyphens:
$router->get('/blog/{slug:[a-z0-9\-]+}', fn (string $slug) => "Blog slug: {$slug}");

// Only matches alphanumeric usernames with 3 to 16 characters:
$router->get('/profiles/{user:[a-zA-Z0-9_]{3,16}}', fn (string $user) => "Profile of {$user}");
```

#### Approach 2: Fluent `where()` for a Single Parameter
```php
$router->get('/categories/{category}', fn (string $category) => "Category: {$category}")
       ->where('category', '[a-zA-Z]+');
```

#### Approach 3: Fluent `where()` with Associative Array for Multiple Parameters
```php
$router->get('/archive/{year}/{month}', function (int $year, int $month) {
    return "Archive for {$year}-{$month}";
})->where([
    'year'  => '[0-9]{4}',
    'month' => '0[1-9]|1[0-2]',
]);
```

---

### 2.6 Wildcards: Single-Segment and Multi-Segment

#### Single-Segment Wildcard (`/*`)
Matches exactly one URL path segment. The captured value is accessible as `$wildcard`:

```php
// Matches /files/report.pdf, /files/photo.png, but NOT /files/2026/report.pdf
$router->get('/files/*', function (string $wildcard) {
    return "Serving root file: {$wildcard}";
});
```

#### Multi-Segment Catch-All Wildcard (`/**`)
Matches all remaining subdirectories and segments:

```php
// Matches /docs/getting-started, /docs/v1/routing/controllers/middleware, etc.
$router->get('/docs/**', function (string $wildcard, Request $request) {
    return "Documentation subpath: {$wildcard}";
});
```

#### Multiple Wildcards in One Route
```php
$router->get('/media/*/**', function (string $wildcard, string $wildcard_2) {
    return "Directory: {$wildcard}, Subpath: {$wildcard_2}";
});
```

---

### 2.7 Default Parameter Values

You can assign fallback default values for route parameters:

```php
$router->get('/catalog/{page}', function (int $page) {
    return "Showing page {$page}";
})->default('page', 1);
```

---

### 2.8 Named Routes & Reverse Lookup

Naming routes allows you to query and inspect routes across your application:

```php
$router->get('/account/settings', fn () => 'Settings Page')
       ->name('account.settings');

// Finding route by name:
$route = $router->findRouteByName('account.settings');
$path  = $route->getPath(); // "/account/settings"
```

---

### 2.9 Sub-Routers & Modular Mounting (`mount`)

You can create isolated sub-routers for modules, micro-apps, or API versions, and mount them with a unified prefix and shared middlewares:

```php
use WebCycles\Foundations\HTTP\Router;

// 1. Create API Sub-Router
$apiV1 = new Router();

$apiV1->get('/users', fn () => ['user1', 'user2']);
$apiV1->get('/posts', fn () => ['post1', 'post2']);
$apiV1->post('/posts', fn (Request $req) => ['created' => $req->title]);

// 2. Create Admin Sub-Router
$admin = new Router();
$admin->get('/stats', fn () => 'Admin Statistics');
$admin->get('/logs', fn () => 'System Logs');

// 3. Mount to Main Router
$mainRouter = new Router();

// Mounted under /api/v1 with ApiAuthMiddleware
$mainRouter->mount('/api/v1', $apiV1, [\App\Middleware\ApiAuthMiddleware::class]);

// Mounted under /admin with AdminMiddleware
$mainRouter->mount('/admin', $admin, [\App\Middleware\AdminMiddleware::class]);
```

---

### 2.10 Automatic Dependency Injection via Reflection

When routing to controllers, the router automatically resolves constructor and method parameters:

1. **`Request` instances** (or subclasses) are automatically injected.
2. **Route parameters** matching variable names are cast to their typed hint (`int`, `string`, `bool`, `float`, `array`).
3. **Optional parameters** receive their default values.
4. **Nullable parameters** default to `null` if not provided.

#### Example:
```php
namespace App\Controllers;

use WebCycles\Foundations\HTTP\Request;
use WebCycles\Foundations\HTTP\JsonResponse;

class ProductController
{
    // Constructor Dependency Injection
    public function __construct(Request $request)
    {
        // $request is auto-injected if required in constructor
    }

    // Method Dependency Injection
    public function update(int $id, Request $request, bool $notify = false): JsonResponse
    {
        return new JsonResponse([
            'id' => $id,
            'title' => $request->title,
            'notified' => $notify,
        ]);
    }
}

// Route:
$router->put('/products/{id}', [\App\Controllers\ProductController::class, 'update']);
```

---

### 2.11 Dispatching & Running the Router

#### Way 1: `Router::run()` (Recommended for Production)
Automatically retrieves PHP globals, dispatches the route, catches HTTP exceptions, formats error output based on the `Accept` header (HTML vs JSON), and sends the response:

```php
// In public/index.php:
$router->run();
```

#### Way 2: `Router::dispatch($request)` (For Custom Workflows & Unit Testing)
Dispatches the request through the middleware pipeline and returns a `Response` instance without sending it immediately:

```php
$request = \WebCycles\Foundations\HTTP\Request::create('/users/42', 'GET');

$response = $router->dispatch($request);

echo $response->getStatusCode(); // 200
echo $response->getContent();    // User #42
```

---

## 3. Incoming Requests (`Request`)

### 3.1 Creating Request Instances

#### From Global Server Variables (Production)
```php
$request = Request::createFromGlobals();
```

#### Simulated Request (Testing & CLI)
```php
$request = Request::create(
    uri: 'https://example.com/api/users?page=2',
    method: 'POST',
    parameters: ['name' => 'Alice', 'role' => 'admin'],
    cookies: ['session' => 'xyz'],
    files: [],
    server: ['HTTP_AUTHORIZATION' => 'Bearer secret-token'],
    content: json_encode(['payload' => 'data'])
);
```

---

### 3.2 All Ways to Read Input Parameters

WebCycles provides **7 different ways** to read incoming input:

```php
// 1. Magic property access (checks GET, POST, JSON, and Route parameters)
$name = $request->name;

// 2. Universal get() method with default fallback
$name = $request->get('name', 'Default');

// 3. Universal input() method with default fallback
$name = $request->input('name', 'Default');

// 4. Checking existence:
if (isset($request->name)) { ... }
if ($request->has('name')) { ... }

// 5. Checking presence and non-emptiness:
if ($request->filled('name')) { ... } // true if not null, not '', not []

// 6. Retrieving all merged inputs as array:
$all = $request->all();

// 7. Retrieving array of route dynamic parameters specifically:
$id = $request->attributes->get('id');
```

---

### 3.3 Reading Specifically from GET, POST, or JSON Body

```php
// --- GET Query String ($_GET) ---
$queryParam = $request->query('filter', 'none');
$allQuery   = $request->query(); // Returns full associative array

// --- POST Form Data ($_POST) ---
$postField  = $request->post('password');
$allPost    = $request->post();  // Returns full associative array

// --- JSON Payload (Content-Type: application/json) ---
$jsonField  = $request->json('user.profile.age', 25);
$allJson    = $request->json();  // Returns full associative array
```

---

### 3.4 Strictly Typed Input Casting

Convert inputs cleanly into native PHP scalar types without manual typecasting or warnings:

```php
// Integer
$page = $request->int('page', 1);

// String
$search = $request->str('search', '');

// Boolean (parses "true", "1", "yes", "on", 1, true)
$isActive = $request->bool('is_active', false);

// Float / Double
$price = $request->float('price', 0.0);

// Array
$tags = $request->request->getArray('tags', []);
```

---

### 3.5 Filtering & Checking Input Presence

```php
// Extract ONLY specified keys (useful for mass assignment protection):
$data = $request->only(['username', 'email', 'bio']);

// Extract ALL inputs EXCEPT blacklisted keys:
$filtered = $request->except(['password', '_csrf_token', 'admin']);
```

---

### 3.6 HTTP Method & Method Spoofing

Supports standard methods and method spoofing for HTML forms via `_method` or `X-HTTP-Method-Override`:

```php
// Resolved method (considers _method spoofing)
$method = $request->getMethod(); // e.g. "PUT"

// Real underlying server method (ignoring spoofing)
$realMethod = $request->getRealMethod(); // e.g. "POST"

// Method comparisons
if ($request->isMethod('POST')) { ... }
if ($request->isMethod('DELETE')) { ... }
```

---

### 3.7 URL, Scheme, Port, Host & Path Detection

```php
$request->getScheme();            // "http" or "https"
$request->isSecure();             // true if HTTPS, false otherwise
$request->getHost();              // "example.com" or "localhost"
$request->getPort();              // 80, 443, 8080, etc.
$request->getSchemeAndHttpHost(); // "https://example.com:8080"
$request->path();                 // "/users/profile"
$request->basePath();             // "/mysite" (if hosted in subdirectory)
$request->baseUrl();              // "https://example.com/mysite"
$request->url();                  // "https://example.com/mysite/users/profile" (without query)
$request->fullUrl();              // "https://example.com/mysite/users/profile?tab=photos"
```

---

### 3.8 URL Path Segments

URL segments are parsed into a 1-indexed array:

```php
// For URL: /users/123/edit
$request->segment(1); // "users"
$request->segment(2); // "123"
$request->segment(3); // "edit"
$request->segment(4, 'default'); // "default"

$allSegments = $request->segments(); // [1 => 'users', 2 => '123', 3 => 'edit']
```

---

### 3.9 Marketing UTM Parameters

Quickly extract UTM campaign parameters for analytics tracking:

```php
// Single UTM:
$source = $request->utm('source'); // reads utm_source
$campaign = $request->utm('campaign'); // reads utm_campaign

// All UTMs as associative array:
$allUtms = $request->utms(); // ['utm_source' => 'google', 'utm_medium' => 'cpc', ...]
```

---

### 3.10 Headers & Bearer Token Authentication

```php
// Direct header access (case-insensitive):
$contentType = $request->headers->get('Content-Type');
$userAgent   = $request->userAgent();

// Check header presence or value:
if ($request->headers->has('X-API-KEY')) { ... }
if ($request->headers->contains('Accept', 'application/json')) { ... }

// Bearer Token Extraction (from "Authorization: Bearer <token>"):
$token = $request->bearerToken();
```

---

### 3.11 Client IP & Trusted Proxies

Safe client IP resolution with reverse proxy (Nginx, Cloudflare, AWS ALB) support:

```php
// Configure trusted reverse proxies:
Request::setTrustedProxies(['127.0.0.1', '10.0.0.1']);

// Retrieve real client IP:
$clientIp = $request->ip();
```

---

### 3.12 Content Negotiation (AJAX, JSON, etc.)

```php
// Check if request was sent via XMLHttpRequest / Fetch
$isAjax = $request->isAjax();

// Check if client requests a JSON response (Accept: application/json)
$wantsJson = $request->wantsJson();

// Check if request body is JSON (Content-Type: application/json)
$isJson = $request->isJson();
```

---

### 3.13 Raw Body & Payload Parsing

```php
// Get raw string payload (php://input)
$rawBody = $request->getContent();

// Parsed JSON payload
$jsonArray = $request->json();
```

---

### 3.14 Custom Request Attributes

Store custom objects (such as authenticated User model, tenant ID, or permission metadata) inside the request:

```php
// Set attribute (e.g. inside middleware)
$request->withAttribute('authenticated_user', $userModel);

// Retrieve attribute (e.g. inside controller)
$user = $request->getAttribute('authenticated_user');
// or:
$user = $request->attributes->get('authenticated_user');
```

---

## 4. HTTP Responses (`Response`)

### 4.1 Automatic Type Normalization from Handlers

The router automatically converts whatever value your handler returns into an appropriate `Response` object:

| Returned Value | Resulting Response Object | Default Content-Type | Status Code |
| :--- | :--- | :--- | :--- |
| `string` / `int` / `float` | `Response` | `text/html; charset=UTF-8` | `200 OK` |
| `array` / `stdClass` / `JsonSerializable` | `JsonResponse` | `application/json; charset=UTF-8` | `200 OK` |
| `null` | `Response` | `text/html; charset=UTF-8` | `204 No Content` |
| `Response` (or child class) | Unchanged | As specified on object | As specified |

---

### 4.2 Base `Response` Object

```php
use WebCycles\Foundations\HTTP\Response;

$response = new Response(
    content: '<h1>Hello World</h1>',
    status: 200,
    headers: ['X-Powered-By' => 'WebCycles']
);

// Fluent modifications:
$response->setContent('<h2>Updated Content</h2>')
         ->setStatusCode(201, 'Created')
         ->setProtocolVersion('1.1')
         ->header('Cache-Control', 'no-cache');
```

#### Status Code Helper Checkers:
```php
$response->isOk();          // true if 200
$response->isSuccessful();  // true if 200-299
$response->isRedirection(); // true if 300-399
$response->isClientError(); // true if 400-499
$response->isServerError(); // true if 500-599
$response->isForbidden();   // true if 403
$response->isNotFound();    // true if 404
```

---

### 4.3 JSON Responses (`JsonResponse`)

Serializes data to JSON with UTF-8 and unescaped slashes:

```php
use WebCycles\Foundations\HTTP\JsonResponse;

$response = new JsonResponse(
    data: ['status' => 'success', 'data' => ['user_id' => 42]],
    status: 200,
    headers: ['X-API-Version' => '1.0'],
    encodingOptions: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);

// Updating data fluently:
$response->setData(['status' => 'updated']);
```

---

### 4.4 Redirection Responses (`RedirectResponse`)

Sets the `Location` header and generates fallback HTML redirect markup:

```php
use WebCycles\Foundations\HTTP\RedirectResponse;

// Temporary redirect (302 Found)
return new RedirectResponse('/login');

// Permanent redirect (301 Moved Permanently)
return new RedirectResponse('/new-url', 301);

// See Other (303 See Other)
return new RedirectResponse('/dashboard', 303);
```

---

### 4.5 Streaming & SSE Responses (`StreamedResponse`)

Streams chunked output directly to the client without buffering everything in memory:

#### CSV Export Example:
```php
use WebCycles\Foundations\HTTP\StreamedResponse;

$router->get('/export', function () {
    $response = new StreamedResponse(function () {
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Username', 'Email']);
        for ($i = 1; $i <= 1000; $i++) {
            fputcsv($out, [$i, "user_{$i}", "user{$i}@example.com"]);
        }
        fclose($out);
    });

    $response->header('Content-Type', 'text/csv');
    $response->header('Content-Disposition', 'attachment; filename="export.csv"');

    return $response;
});
```

---

### 4.6 HTTP Status Codes & Reason Phrases (`HttpStatus`)

The `HttpStatus` enum contains all standard RFC status codes:

```php
use WebCycles\Foundations\HTTP\HttpStatus;

// Enum instances:
$status = HttpStatus::OK;                     // 200
$notFound = HttpStatus::NOT_FOUND;            // 404
$teapot = HttpStatus::IM_A_TEAPOT;            // 418
$serverError = HttpStatus::INTERNAL_SERVER_ERROR; // 500

// Reason phrases:
echo HttpStatus::OK->reasonPhrase(); // "OK"
echo HttpStatus::getReasonPhrase(404); // "Not Found"
```

---

### 4.7 Managing Response Headers & Cookies

```php
$response = new Response('Hello');

// Add / replace header:
$response->header('X-Custom-Header', 'Value');

// Add multiple values for the same header:
$response->header('Set-Cookie', 'a=1', false);
$response->header('Set-Cookie', 'b=2', false);

// Attach a cookie:
$response->withCookie('theme', 'dark', time() + 86400, '/');

// Delete a cookie:
$response->withoutCookie('session_token', '/');
```

---

### 4.8 Sending and Flushing the Response

```php
// Sends headers, sends content, flushes FastCGI buffers
$response->send();
```

---

## 5. HTTP Cookies (`Cookie`)

### 5.1 Constructing & Creating Cookies

#### Way 1: Direct Constructor
```php
use WebCycles\Foundations\HTTP\Cookie;

$cookie = new Cookie(
    name: 'cart_id',
    value: 'abc12345',
    expire: time() + 3600,
    path: '/',
    domain: '.example.com',
    secure: true,
    httpOnly: true,
    raw: false,
    sameSite: Cookie::SAME_SITE_LAX
);
```

#### Way 2: Fluent Static Factory `Cookie::create()`
```php
$cookie = Cookie::create(
    name: 'user_pref',
    value: 'dark_mode',
    expire: '+7 days',
    path: '/'
);
```

---

### 5.2 Cookie Expiration Options

Expiration accepts `int` timestamp, relative string, or `DateTimeInterface`:

```php
// 1. Unix timestamp:
Cookie::create('a', 'val', time() + 3600);

// 2. Relative date string:
Cookie::create('b', 'val', '+1 month');

// 3. DateTime object:
Cookie::create('c', 'val', new \DateTime('+1 year'));

// 4. Session cookie (expires when browser closes):
Cookie::create('d', 'val', 0);
```

---

### 5.3 SameSite Policies

```php
Cookie::SAME_SITE_LAX;    // 'Lax'
Cookie::SAME_SITE_STRICT; // 'Strict'
Cookie::SAME_SITE_NONE;   // 'None'
```

---

### 5.4 Deleting / Forgetting Cookies

```php
$expiredCookie = Cookie::forget('auth_token', '/', '.example.com');
```

---

### 5.5 Emitting Set-Cookie Headers

```php
// Convert to Set-Cookie header value:
$headerValue = $cookie->toHeaderString();
// e.g. "cart_id=abc12345; Expires=Tue, 25 Aug 2026 13:00:00 GMT; Max-Age=3600; Path=/; Secure; HttpOnly; SameSite=Lax"

// Send directly via PHP setcookie:
$cookie->send();
```

---

## 6. Middleware & Execution Pipeline (`Pipeline`)

Middlewares wrap around the execution lifecycle in an **onion architecture**:

```
Request ──► [ Global Middleware ] ──► [ Pattern Middleware ] ──► [ Route Middleware ] ──► (Controller Handler)
Response ◄── [ Global Middleware ] ◄── [ Pattern Middleware ] ◄── [ Route Middleware ] ◄── (Controller Handler)
```

---

### 6.1 Implementing `MiddlewareInterface`

```php
namespace App\Middleware;

use Closure;
use WebCycles\Foundations\HTTP\Request;
use WebCycles\Foundations\HTTP\Response;
use WebCycles\Foundations\HTTP\JsonResponse;
use WebCycles\Foundations\HTTP\Middleware\MiddlewareInterface;

class RequireApiKeyMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->headers->get('X-API-KEY');

        if ($apiKey !== 'secret-key-123') {
            return new JsonResponse(['error' => 'Forbidden: Invalid API Key'], 403);
        }

        // Proceed to next layer
        $response = $next($request);

        // Modify response post-execution
        $response->header('X-API-Processed-By', 'RequireApiKeyMiddleware');

        return $response;
    }
}
```

---

### 6.2 All Ways to Supply Middleware (Class, Instance, Callable, Closure)

The pipeline accepts middlewares in **4 formats**:

```php
// 1. Class Name string (Instantiated on demand)
$router->middleware([\App\Middleware\RequireApiKeyMiddleware::class]);

// 2. Pre-instantiated object implementing MiddlewareInterface
$router->middleware([new \App\Middleware\RequireApiKeyMiddleware()]);

// 3. Anonymous Closure (Request $request, Closure $next)
$router->middleware(function (Request $request, Closure $next) {
    // Before logic
    $response = $next($request);
    // After logic
    return $response;
});

// 4. Invokable Class
class LoggerMiddleware {
    public function __invoke(Request $req, Closure $next) {
        error_log("Incoming request: " . $req->path());
        return $next($req);
    }
}
$router->middleware([\App\Middleware\LoggerMiddleware::class]);
```

---

### 6.3 Global Middleware

Executes on **every incoming request**:

```php
$router->middleware([
    \App\Middleware\CorsMiddleware::class,
    \App\Middleware\SessionStartMiddleware::class,
]);
```

---

### 6.4 Pattern-Based & Wildcard Middleware

Executes only when the request path matches a wildcard pattern:

```php
// Applies to /admin and all subpaths (/admin/users, /admin/settings/logs, etc.)
$router->middleware('/admin/**', [
    \App\Middleware\AdminAuthMiddleware::class,
]);

// Applies to /api/v1/ and immediate subpaths
$router->middleware('/api/v1/*', [
    \App\Middleware\RateLimitMiddleware::class,
]);
```

---

### 6.5 Route-Specific Middleware

Assigned directly to a route:

```php
$router->get('/profile', fn () => 'User Profile')
       ->middleware([
           \App\Middleware\AuthMiddleware::class,
           \App\Middleware\VerifyEmailMiddleware::class,
       ]);
```

---

### 6.6 Sub-Router Mounted Middleware

Applies to all routes inside a mounted sub-router:

```php
$router->mount('/billing', $billingRouter, [
    \App\Middleware\RequireSubscribedMiddleware::class,
]);
```

---

### 6.7 Standalone `Pipeline` Execution

You can use `Pipeline` independently for any request processing workflow:

```php
use WebCycles\Foundations\HTTP\Pipeline;

$response = (new Pipeline())
    ->send($request)
    ->through([
        \App\Middleware\FirstMiddleware::class,
        \App\Middleware\SecondMiddleware::class,
    ])
    ->then(function (Request $req) {
        return new Response('Destination reached!');
    });
```

---

## 7. Uploaded Files (`UploadedFile`)

### 7.1 Accessing Files from the Request

```php
// Single uploaded file:
$file = $request->files->get('avatar'); // Instance of UploadedFile or null

// Array of uploaded files:
$galleryFiles = $request->files->get('photos'); // Array of UploadedFile instances
```

---

### 7.2 Validation & Error Checking

```php
if ($file !== null && $file->isValid()) {
    // File uploaded without errors and verified with is_uploaded_file()
} else {
    $errorMessage = $file?->getErrorMessage();
}
```

---

### 7.3 Inspecting File Metadata

```php
$originalName = $file->getClientOriginalName();      // e.g. "my_photo.jpg"
$extension    = $file->getClientOriginalExtension(); // e.g. "jpg"
$mimeType     = $file->getClientMimeType();          // e.g. "image/jpeg"
$sizeInBytes  = $file->getSize();                    // e.g. 204800 (bytes)
$tempPath     = $file->getPathname();                // e.g. "C:\Windows\Temp\php9A.tmp"
$content      = $file->getContent();                 // Raw binary content of the file
```

---

### 7.4 Moving and Storing Uploaded Files

The `moveTo()` method automatically creates target directories, sanitizes filenames, and moves the uploaded file:

```php
$targetDirectory = WEBCYCLES_PATH . '/storage/uploads/avatars';

// 1. Move preserving original name:
$savedPath = $file->moveTo($targetDirectory);

// 2. Move with custom new filename:
$savedPath = $file->moveTo($targetDirectory, 'avatar_' . $userId . '.' . $file->getClientOriginalExtension());
```

---

### 7.5 Handling Nested and Multiple File Arrays

HTML forms with multiple files are automatically normalized into clean recursive `UploadedFile` object trees:

```html
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="documents[tax][]" multiple />
</form>
```

```php
$taxDocuments = $request->files->get('documents')['tax'];

foreach ($taxDocuments as $doc) {
    if ($doc instanceof \WebCycles\Foundations\HTTP\UploadedFile && $doc->isValid()) {
        $doc->moveTo('/storage/documents');
    }
}
```

---

## 8. Data Containers (`ParameterBag` & `HeaderBag`)

### 8.1 ParameterBag Operations & Typed Retrieval

`ParameterBag` wraps `$_GET`, `$_POST`, `$_COOKIE`, and custom attributes:

```php
use WebCycles\Foundations\HTTP\ParameterBag;

$bag = new ParameterBag(['name' => 'Alice', 'age' => '30', 'is_admin' => '1']);

$bag->all();                  // ['name' => 'Alice', 'age' => '30', ...]
$bag->keys();                 // ['name', 'age', 'is_admin']
$bag->has('name');            // true
$bag->get('name');            // "Alice"
$bag->getInt('age');          // 30
$bag->getBool('is_admin');    // true
$bag->set('role', 'editor');  // adds/replaces key
$bag->remove('age');          // removes key
$bag->only(['name']);         // ['name' => 'Alice']
$bag->except(['is_admin']);   // ['name' => 'Alice']
$bag->count();                // count elements
```

---

### 8.2 HeaderBag Case-Insensitive Header Management

`HeaderBag` manages HTTP headers case-insensitively with support for multi-value headers:

```php
use WebCycles\Foundations\HTTP\HeaderBag;

$headers = new HeaderBag([
    'Content-Type' => 'application/json',
    'X-Custom'     => ['value1', 'value2']
]);

// Case-insensitive lookups:
$headers->get('content-type'); // "application/json"
$headers->get('CONTENT-TYPE'); // "application/json"

// Multi-value access:
$headers->allValues('x-custom'); // ['value1', 'value2']

// Substring check:
$headers->contains('content-type', 'json'); // true

// Formatted header lines for PHP header() emission:
$headers->toHeaderLines();
// [
//   "Content-Type: application/json",
//   "X-Custom: value1",
//   "X-Custom: value2"
// ]
```

---

## 9. HTTP Exceptions & Error Handling

### 9.1 Base `HttpException`

Throw anywhere in controllers or middlewares to immediately abort execution with an HTTP status code:

```php
use WebCycles\Foundations\HTTP\Exceptions\HttpException;

throw new HttpException(statusCode: 403, message: 'Access Denied to this Resource');
```

---

### 9.2 `RouteNotFoundException` (404)

Thrown automatically when no route matches the request path:

```php
use WebCycles\Foundations\HTTP\Exceptions\RouteNotFoundException;

throw new RouteNotFoundException('/missing-page', 'The requested page was not found.');
```

---

### 9.3 `MethodNotAllowedException` (405)

Thrown automatically when a route matches the URL path, but does not support the requested HTTP method. Automatically sets the `Allow` header:

```php
use WebCycles\Foundations\HTTP\Exceptions\MethodNotAllowedException;

throw new MethodNotAllowedException(
    allowedMethods: ['GET', 'POST'],
    currentMethod: 'DELETE'
);
```

---

### 9.4 `ControllerResolutionException` (500)

Thrown when a controller class or method cannot be found, or when required parameters cannot be resolved via reflection:

```php
use WebCycles\Foundations\HTTP\Exceptions\ControllerResolutionException;

throw new ControllerResolutionException('Missing required parameter $userId');
```

---

### 9.5 Automatic JSON / HTML Error Formatting

When running `$router->run()`, exceptions are automatically rendered:

- **HTML Request (`Accept: text/html`)**:
  ```html
  <h1>404 Not Found</h1>
  <p>The requested resource was not found.</p>
  ```
- **JSON Request (`Accept: application/json`)**:
  ```json
  {
    "error": {
      "code": 404,
      "message": "The requested resource was not found."
    }
  }
  ```

---

## 10. End-to-End Real World Examples

### 10.1 Complete RESTful JSON API

```php
<?php

use WebCycles\Foundations\HTTP\Router;
use WebCycles\Foundations\HTTP\Request;
use WebCycles\Foundations\HTTP\JsonResponse;
use WebCycles\Foundations\HTTP\Exceptions\HttpException;

$router = new Router();

// In-memory mock database
$database = [
    1 => ['id' => 1, 'name' => 'Alice', 'role' => 'admin'],
    2 => ['id' => 2, 'name' => 'Bob', 'role' => 'member'],
];

// LIST
$router->get('/api/users', function (Request $req) use (&$database) {
    return new JsonResponse(array_values($database));
});

// GET ONE
$router->get('/api/users/{id:[0-9]+}', function (int $id) use (&$database) {
    if (!isset($database[$id])) {
        throw new HttpException(404, "User #{$id} not found");
    }
    return new JsonResponse($database[$id]);
});

// CREATE
$router->post('/api/users', function (Request $req) use (&$database) {
    if (!$req->filled('name')) {
        throw new HttpException(422, 'Field "name" is required');
    }

    $id = count($database) + 1;
    $database[$id] = [
        'id' => $id,
        'name' => $req->name,
        'role' => $req->get('role', 'member'),
    ];

    return new JsonResponse($database[$id], 201);
});

// DELETE
$router->delete('/api/users/{id:[0-9]+}', function (int $id) use (&$database) {
    if (!isset($database[$id])) {
        throw new HttpException(404, "User #{$id} not found");
    }
    unset($database[$id]);
    return new JsonResponse(['deleted' => true]);
});

$router->run();
```

---

### 10.2 Server-Sent Events (SSE) Live Feed

```php
<?php

use WebCycles\Foundations\HTTP\Router;
use WebCycles\Foundations\HTTP\StreamedResponse;

$router = new Router();

$router->get('/live-feed', function () {
    $response = new StreamedResponse(function () {
        for ($i = 1; $i <= 5; $i++) {
            $data = json_encode(['iteration' => $i, 'time' => date('H:i:s')]);
            echo "data: {$data}\n\n";
            ob_flush();
            flush();
            sleep(1);
        }
    });

    $response->header('Content-Type', 'text/event-stream');
    $response->header('Cache-Control', 'no-cache');
    $response->header('Connection', 'keep-alive');

    return $response;
});

$router->run();
```

---

### 10.3 Authenticated File Upload with CSRF & Token Verification

```php
<?php

use WebCycles\Foundations\HTTP\Router;
use WebCycles\Foundations\HTTP\Request;
use WebCycles\Foundations\HTTP\JsonResponse;
use WebCycles\Foundations\HTTP\Exceptions\HttpException;

$router = new Router();

$router->post('/upload-avatar', function (Request $request) {
    // 1. Verify Bearer Token
    if ($request->bearerToken() !== 'auth-token-123') {
        throw new HttpException(401, 'Unauthorized');
    }

    // 2. Validate File
    /** @var \WebCycles\Foundations\HTTP\UploadedFile|null $file */
    $file = $request->files->get('avatar');

    if ($file === null || !$file->isValid()) {
        throw new HttpException(400, 'Invalid file: ' . $file?->getErrorMessage());
    }

    // 3. Validate Extension & Size
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array(strtolower($file->getClientOriginalExtension()), $allowed, true)) {
        throw new HttpException(422, 'Invalid image format. Allowed: ' . implode(', ', $allowed));
    }

    if ($file->getSize() > 2 * 1024 * 1024) {
        throw new HttpException(422, 'File exceeds maximum 2MB size limit');
    }

    // 4. Move File
    $filename = 'avatar_' . uniqid('', true) . '.' . $file->getClientOriginalExtension();
    $targetPath = $file->moveTo(__DIR__ . '/../../storage/avatars', $filename);

    return new JsonResponse([
        'status' => 'success',
        'file' => $filename,
        'path' => $targetPath,
    ], 201);
});

$router->run();
```
