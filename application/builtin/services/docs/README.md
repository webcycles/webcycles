# Complete WebCycles Services Module Documentation

Welcome to the definitive reference manual for the **WebCycles Services Module** (Dependency Injection Container). This document provides an exhaustive, in-depth guide covering every feature, parameter, option, class, exception, and method available, demonstrating all possible ways of using the module with practical code examples.

---

## Table of Contents
1. [Core Architecture & Lifecycle](#1-core-architecture--lifecycle)
2. [Basic Container Operations](#2-basic-container-operations)
   - [2.1 Initializing & Global Singleton Instance](#21-initializing--global-singleton-instance)
   - [2.2 PSR-11 Compliance (`ContainerInterface`)](#22-psr-11-compliance-containerinterface)
   - [2.3 Checking Service Presence (`has`, `bound`)](#23-checking-service-presence-has-bound)
   - [2.4 Resolving Services (`make`, `get`)](#24-resolving-services-make-get)
3. [Binding Strategies & Lifecycle Management](#3-binding-strategies--lifecycle-management)
   - [3.1 Transient Bindings (`bind`)](#31-transient-bindings-bind)
   - [3.2 Shared Singleton Bindings (`singleton`)](#32-shared-singleton-bindings-singleton)
   - [3.3 Lazy Loading Proxy Singletons (`lazy`)](#33-lazy-loading-proxy-singletons-lazy)
   - [3.4 Scoped Bindings (`scoped`, `runInScope`)](#34-scoped-bindings-scoped-runinscope)
   - [3.5 Existing Instance Bindings (`instance`)](#35-existing-instance-bindings-instance)
   - [3.6 Binding Aliases (`alias`)](#36-binding-aliases-alias)
4. [Automatic Dependency Resolution (Autowiring)](#4-automatic-dependency-resolution-autowiring)
   - [4.1 Zero-Configuration Class Instantiation](#41-zero-configuration-class-instantiation)
   - [4.2 Recursive Constructor Injection via Reflection](#42-recursive-constructor-injection-via-reflection)
   - [4.3 Passing Manual Parameter Overrides](#43-passing-manual-parameter-overrides)
   - [4.4 Handling Default & Nullable Parameters](#44-handling-default--nullable-parameters)
5. [Contextual Dependency Injection (`when` & `ContextualBuilder`)](#5-contextual-dependency-injection-when--contextualbuilder)
   - [5.1 Basic Contextual Injections](#51-basic-contextual-injections)
   - [5.2 Providing Concrete Implementations](#52-providing-concrete-implementations)
   - [5.3 Providing Closures / Computed Values](#53-providing-closures--computed-values)
6. [Method & Function Invocation (`call`)](#6-method--function-invocation-call)
   - [6.1 Calling Closures & Anonymous Functions](#61-calling-closures--anonymous-functions)
   - [6.2 Calling Class Methods `[Object, 'method']` & `[Class::class, 'method']`](#62-calling-class-methods-object-method--classclass-method)
   - [6.3 Calling Class Methods via String Syntax (`'Class@method'`)](#63-calling-class-methods-via-string-syntax-classmethod)
   - [6.4 Mixing Autowired Dependencies & Explicit Parameters](#64-mixing-autowired-dependencies--explicit-parameters)
7. [Service Tagging & Group Resolution (`tag`, `tagged`)](#7-service-tagging--group-resolution-tag-tagged)
   - [7.1 Assigning Tags during Binding](#71-assigning-tags-during-binding)
   - [7.2 Assigning Tags Post-Binding (`tag`)](#72-assigning-tags-post-binding-tag)
   - [7.3 Resolving Tagged Collections (`tagged`)](#73-resolving-tagged-collections-tagged)
8. [Extending & Decorating Services (`extend`)](#8-extending--decorating-services-extend)
   - [8.1 Wrapping Services (Decorator Pattern)](#81-wrapping-services-decorator-pattern)
   - [8.2 Configuring or Mutating Existing Singletons](#82-configuring-or-mutating-existing-singletons)
9. [Resolving Hooks & Lifecycle Callbacks](#9-resolving-hooks--lifecycle-callbacks)
   - [9.1 Before-Resolution Hooks (`resolving`)](#91-before-resolution-hooks-resolving)
   - [9.2 After-Resolution Hooks (`afterResolving`)](#92-after-resolution-hooks-afterresolving)
10. [Service Providers (`ServiceProvider`)](#10-service-providers-serviceprovider)
    - [10.1 Creating a Custom `ServiceProvider`](#101-creating-a-custom-serviceprovider)
    - [10.2 Registration Phase (`register`)](#102-registration-phase-register)
    - [10.3 Bootstrapping Phase (`boot`)](#103-bootstrapping-phase-boot)
    - [10.4 Registering Providers in the Container](#104-registering-providers-in-the-container)
11. [Lazy Loading Proxies (`LazyProxy`)](#11-lazy-loading-proxies-lazyproxy)
    - [11.1 How Virtual Proxies Work](#111-how-virtual-proxies-work)
    - [11.2 Magic Method & Property Forwarding](#112-magic-method--property-forwarding)
    - [11.3 Explicit Resolution (`resolve`)](#113-explicit-resolution-resolve)
12. [Container Introspection & Cache Management](#12-container-introspection--cache-management)
    - [12.1 Checking Shared Status (`isShared`)](#121-checking-shared-status-isshared)
    - [12.2 Forgetting Instances (`forgetInstance`, `forgetInstances`)](#122-forgetting-instances-forgetinstance-forgetinstances)
    - [12.3 Inspecting Registered Services & Aliases](#123-inspecting-registered-services--aliases)
13. [Exceptions & Error Handling](#13-exceptions--error-handling)
    - [13.1 `ContainerException`](#131-containerexception)
    - [13.2 `NotFoundException`](#132-notfoundexception)
    - [13.3 `BindingResolutionException`](#133-bindingresolutionexception)
    - [13.4 `CircularDependencyException`](#134-circulardependencyexception)
14. [End-to-End Real World Examples](#14-end-to-end-real-world-examples)
    - [14.1 Application Bootstrapping with Multi-Provider Architecture](#141-application-bootstrapping-with-multi-provider-architecture)
    - [14.2 Database & Repository Pattern with Contextual Adapters](#142-database--repository-pattern-with-contextual-adapters)
    - [14.3 Request-Scoped HTTP & Security Context](#143-request-scoped-http--security-context)
    - [14.4 Event Bus & Plugin Architecture via Tagging & Extending](#144-event-bus--plugin-architecture-via-tagging--extending)

---

## 1. Core Architecture & Lifecycle

The **WebCycles Services Module** provides a state-of-the-art Dependency Injection Container (`ServiceContainer`) designed for high performance, flexible lifecycle management, full recursive autowiring, and clean architectural modularity.

```
                  ┌────────────────────────────────────────────────────────┐
                  │            WebCycles ServiceContainer                  │
                  └────────────────────────────────────────────────────────┘
                                            │
         ┌───────────────────┬──────────────┴─────────────┬───────────────────┐
         ▼                   ▼                            ▼                   ▼
 ┌──────────────┐    ┌──────────────┐             ┌──────────────┐    ┌──────────────┐
 │   Transient  │    │  Singleton   │             │ Lazy Loading │    │    Scoped    │
 │ (New object  │    │ (Shared app  │             │   (Virtual   │    │ (Bound to a  │
 │  every make) │    │  lifecycle)  │             │    Proxy)    │    │ custom scope)│
 └──────────────┘    └──────────────┘             └──────────────┘    └──────────────┘
         │                   │                            │                   │
         └───────────────────┼────────────────────────────┼───────────────────┘
                             ▼
         ┌────────────────────────────────────────────────────────┐
         │              Resolution & Autowiring Engine            │
         │  1. Check Instances Cache (Singleton / Scoped)         │
         │  2. Detect Circular Dependencies (A -> B -> A)         │
         │  3. Execute `resolving` Pre-Hooks                      │
         │  4. Inspect Type Hints via Reflection Cache            │
         │  5. Apply Contextual Bindings (`when` -> `needs`)      │
         │  6. Autowire Parameters & Default Values               │
         │  7. Apply Service Extenders / Decorators               │
         │  8. Execute `afterResolving` Post-Hooks                │
         └────────────────────────────────────────────────────────┘
```

---

## 2. Basic Container Operations

### 2.1 Initializing & Global Singleton Instance
You can instantiate `ServiceContainer` as an isolated container or manage a global static instance for the application lifecycle:

```php
use WebCycles\Foundations\Services\ServiceContainer;

// 1. Create a container instance
$container = new ServiceContainer();

// 2. Set as global instance
ServiceContainer::setInstance($container);

// 3. Access global instance anywhere
$global = ServiceContainer::getInstance();
```

---

### 2.2 PSR-11 Compliance (`ContainerInterface`)
`ServiceContainer` implements `WebCycles\Foundations\Services\Interfaces\ContainerInterface` (compatible with the standard PSR-11 specification):

```php
use WebCycles\Foundations\Services\Interfaces\ContainerInterface;

function bootstrap(ContainerInterface $container): void
{
    if ($container->has('logger')) {
        $logger = $container->get('logger');
    }
}
```

- `get(string $id): mixed`: Resolves an entry or throws a `NotFoundException` if no binding or instantiable class is found.
- `has(string $id): bool`: Returns `true` if an abstract type, alias, or instance is registered.

---

### 2.3 Checking Service Presence (`has`, `bound`)

```php
// Check if service is registered or bound
if ($container->bound(DatabaseConnection::class)) {
    // Registered via bind(), singleton(), instance(), or alias()
}

// PSR-11 alias
if ($container->has('db.primary')) {
    $db = $container->get('db.primary');
}
```

---

### 2.4 Resolving Services (`make`, `get`)

```php
// Resolves using make() - supports constructor parameter overrides
$userService = $container->make(UserService::class);

$customService = $container->make(ReportGenerator::class, [
    'format' => 'pdf',
    'debug'  => true,
]);

// Resolves using PSR-11 get()
$mailer = $container->get(MailerInterface::class);
```

---

## 3. Binding Strategies & Lifecycle Management

### 3.1 Transient Bindings (`bind`)
A transient binding creates a brand new instance every time the service is requested via `make()` or `get()`:

```php
use WebCycles\Foundations\Services\ServiceContainer;

$container = new ServiceContainer();

// 1. Binding an interface to a concrete class
$container->bind(LoggerInterface::class, FileLogger::class);

// 2. Self-binding with a closure factory
$container->bind(UuidGenerator::class, function (ServiceContainer $c) {
    return new UuidGenerator(prefix: 'wc_');
});

// 3. Passing parameters from make() to closure factory
$container->bind(PaymentGateway::class, function (ServiceContainer $c, array $params) {
    $apiKey = $params['apiKey'] ?? 'default_key';
    return new StripePaymentGateway($apiKey);
});

$g1 = $container->make(PaymentGateway::class, ['apiKey' => 'sk_live_123']);
$g2 = $container->make(PaymentGateway::class, ['apiKey' => 'sk_live_456']);
// $g1 !== $g2 (two different instances)
```

---

### 3.2 Shared Singleton Bindings (`singleton`)
Singletons are instantiated once and cached for the entire lifespan of the container. Every subsequent resolution returns the exact same instance:

```php
// 1. Singleton bound to class
$container->singleton(DatabaseConnection::class, MysqlConnection::class);

// 2. Singleton bound to a Closure factory
$container->singleton(CacheManager::class, function (ServiceContainer $c) {
    $config = $c->make('config');
    return new RedisCacheManager($config['redis']);
});

$instance1 = $container->make(CacheManager::class);
$instance2 = $container->make(CacheManager::class);

var_dump($instance1 === $instance2); // true
```

---

### 3.3 Lazy Loading Proxy Singletons (`lazy`)
Heavyweight services (e.g. cloud SDK clients, remote connections, PDF renderers) can be registered with `lazy()`. The container immediately returns a lightweight `LazyProxy` virtual object without invoking the factory. The actual object is instantiated only when a method or property is first accessed:

```php
$container->lazy(HeavyMailerClient::class, function (ServiceContainer $c) {
    // This expensive initialization only runs when a mail method is called!
    return new HeavyMailerClient(connectToRemoteServer: true);
});

// Returns instantly! No connection opened yet.
$mailer = $container->make(HeavyMailerClient::class);

// Connection happens on demand right here:
$mailer->sendEmail('user@example.com', 'Welcome!');
```

---

### 3.4 Scoped Bindings (`scoped`, `runInScope`)
Scoped bindings act as singletons within a specific execution boundary (e.g., an HTTP Request or Queue Job) and are automatically destroyed once the scope exits:

```php
// Register a scoped service
$container->scoped(SecurityContext::class, function (ServiceContainer $c) {
    return new SecurityContext();
});

// Execute within an isolated scope
$response = $container->runInScope(function (ServiceContainer $c) {
    $ctx1 = $c->make(SecurityContext::class);
    $ctx1->setUser(['id' => 42, 'role' => 'admin']);

    $ctx2 = $c->make(SecurityContext::class);
    // $ctx1 === $ctx2 inside the same scope

    return "Processed for user: " . $ctx2->getUser()['id'];
});

// Outside the scope, instances are cleaned up
```

---

### 3.5 Existing Instance Bindings (`instance`)
Directly register an already created object instance as a singleton:

```php
$autoloader = new Autoloader();
$container->instance(Autoloader::class, $autoloader);

// Can also bind directly to an interface
$container->instance(ContainerInterface::class, $container);
```

---

### 3.6 Binding Aliases (`alias`)
Aliases allow retrieving a service using alternative string keys or shortcuts:

```php
$container->singleton(DatabaseConnection::class, MysqlConnection::class);

// Create short aliases
$container->alias(DatabaseConnection::class, 'db');
$container->alias(DatabaseConnection::class, 'db.connection');

// All resolve the same singleton
$db1 = $container->get('db');
$db2 = $container->get('db.connection');
$db3 = $container->make(DatabaseConnection::class);

var_dump($db1 === $db2 && $db2 === $db3); // true
```

---

## 4. Automatic Dependency Resolution (Autowiring)

### 4.1 Zero-Configuration Class Instantiation
Any concrete class can be instantiated automatically without explicit registration if its constructor dependencies can be resolved:

```php
class UserRepository {
    public function find(int $id): array {
        return ['id' => $id, 'name' => 'John'];
    }
}

class UserService {
    public function __construct(public UserRepository $users) {}
}

// ServiceContainer automatically instantiates UserRepository and injects it into UserService
$userService = $container->make(UserService::class);
```

---

### 4.2 Recursive Constructor Injection via Reflection
The container recursively resolves complex dependency graphs:

```php
class Config {
    public array $values = ['app_name' => 'WebCycles'];
}

class Database {
    public function __construct(public Config $config) {}
}

class AuthManager {
    public function __construct(public Database $db) {}
}

class UserController {
    public function __construct(public AuthManager $auth, public UserService $users) {}
}

// Container resolves:
// UserController -> (AuthManager -> Database -> Config) & (UserService -> UserRepository)
$controller = $container->make(UserController::class);
```

---

### 4.3 Passing Manual Parameter Overrides
Explicit parameters passed to `make()` override autowired values by parameter name:

```php
class ApiClient {
    public function __construct(
        public HttpClient $http,
        public string $apiKey,
        public int $timeout = 30
    ) {}
}

$client = $container->make(ApiClient::class, [
    'apiKey'  => 'secret_token_xyz',
    'timeout' => 60,
]);
```

---

### 4.4 Handling Default & Nullable Parameters
Optional constructor parameters and nullable dependencies are gracefully resolved:

```php
class NotificationService {
    public function __construct(
        public LoggerInterface $logger,
        public ?SmsGateway $sms = null,
        public string $defaultChannel = 'email'
    ) {}
}

// If SmsGateway is not bound and not instantiable, null is passed
// defaultChannel receives 'email'
$service = $container->make(NotificationService::class);
```

---

## 5. Contextual Dependency Injection (`when` & `ContextualBuilder`)

Contextual bindings allow different classes that depend on the same interface to receive different concrete implementations:

```php
interface StorageAdapterInterface {
    public function store(string $key, string $data): void;
}

class S3StorageAdapter implements StorageAdapterInterface { /* ... */ }
class LocalStorageAdapter implements StorageAdapterInterface { /* ... */ }

class VideoUploader {
    public function __construct(public StorageAdapterInterface $storage) {}
}

class ProfilePhotoUploader {
    public function __construct(public StorageAdapterInterface $storage) {}
}
```

### 5.1 Providing Concrete Implementations
```php
// When VideoUploader needs StorageAdapterInterface, give S3StorageAdapter
$container->when(VideoUploader::class)
          ->needs(StorageAdapterInterface::class)
          ->give(S3StorageAdapter::class);

// When ProfilePhotoUploader needs StorageAdapterInterface, give LocalStorageAdapter
$container->when(ProfilePhotoUploader::class)
          ->needs(StorageAdapterInterface::class)
          ->give(LocalStorageAdapter::class);

$video = $container->make(VideoUploader::class);
// $video->storage is S3StorageAdapter

$photo = $container->make(ProfilePhotoUploader::class);
// $photo->storage is LocalStorageAdapter
```

### 5.2 Providing Closures / Computed Values
```php
$container->when(ReportService::class)
          ->needs(StorageAdapterInterface::class)
          ->give(function (ServiceContainer $c) {
              return new LocalStorageAdapter('/custom/report/path');
          });
```

---

## 6. Method & Function Invocation (`call`)

`call()` invokes callables, injecting type-hinted dependencies from the container while allowing manual argument overrides.

### 6.1 Calling Closures & Anonymous Functions
```php
$result = $container->call(function (UserService $users, Config $config) {
    return $config->values['app_name'] . ' -> ' . count($users->find(1));
});
```

### 6.2 Calling Class Methods `[Object, 'method']` & `[Class::class, 'method']`
```php
class OrderController {
    public function process(int $orderId, MailerInterface $mailer, OrderRepository $orders): string {
        $order = $orders->find($orderId);
        $mailer->send('Order processed');
        return "Order #{$orderId} completed";
    }
}

// 1. With an instantiated object
$controller = new OrderController();
$res = $container->call([$controller, 'process'], ['orderId' => 101]);

// 2. With class name (auto-instantiates controller)
$res = $container->call([OrderController::class, 'process'], ['orderId' => 102]);
```

### 6.3 Calling Class Methods via String Syntax (`'Class@method'`)
```php
$response = $container->call('OrderController@process', [
    'orderId' => 103,
]);
```

---

## 7. Service Tagging & Group Resolution (`tag`, `tagged`)

Tags let you group related services together and resolve them as an array.

### 7.1 Assigning Tags during Binding
```php
$container->bind(EmailNotifier::class, EmailNotifier::class, ['notifiers', 'alerts']);
$container->bind(SmsNotifier::class, SmsNotifier::class, ['notifiers']);
$container->bind(SlackNotifier::class, SlackNotifier::class, ['notifiers', 'alerts']);
```

### 7.2 Assigning Tags Post-Binding (`tag`)
```php
$container->bind(DiscordNotifier::class, DiscordNotifier::class);
$container->tag(DiscordNotifier::class, ['notifiers', 'alerts']);

// Tag multiple services at once
$container->tag([EmailNotifier::class, SmsNotifier::class], 'critical');
```

### 7.3 Resolving Tagged Collections (`tagged`)
```php
$notifiers = $container->tagged('notifiers');

foreach ($notifiers as $notifier) {
    $notifier->sendNotification('Deployment completed!');
}
```

---

## 8. Extending & Decorating Services (`extend`)

Extenders modify or wrap services after they are resolved from the container.

### 8.1 Wrapping Services (Decorator Pattern)
```php
interface LoggerInterface {
    public function log(string $msg): void;
}

class StandardLogger implements LoggerInterface {
    public function log(string $msg): void {
        echo "[LOG] $msg\n";
    }
}

class TimestampLogger implements LoggerInterface {
    public function __construct(public LoggerInterface $inner) {}
    public function log(string $msg): void {
        $this->inner->log(date('Y-m-d H:i:s') . ' ' . $msg);
    }
}

$container->singleton(LoggerInterface::class, StandardLogger::class);

// Decorate LoggerInterface with TimestampLogger
$container->extend(LoggerInterface::class, function (LoggerInterface $logger, ServiceContainer $c) {
    return new TimestampLogger($logger);
});

$logger = $container->make(LoggerInterface::class);
$logger->log("Hello World"); 
// Outputs: [LOG] 2026-08-25 14:45:00 Hello World
```

---

## 9. Resolving Hooks & Lifecycle Callbacks

### 9.1 Before-Resolution Hooks (`resolving`)
Fires right before building an object:
```php
$container->resolving(function (string $abstract, ServiceContainer $c) {
    // Log resolution attempt or prepare environment
    // error_log("Resolving: {$abstract}");
});
```

### 9.2 After-Resolution Hooks (`afterResolving`)
Fires immediately after an object is constructed and extended:
```php
$container->afterResolving(function (mixed $object, ServiceContainer $c) {
    if ($object instanceof AwareOfContainerInterface) {
        $object->setContainer($c);
    }
});
```

---

## 10. Service Providers (`ServiceProvider`)

Service Providers group service bindings and bootstrap logic into clean, reusable modules.

### 10.1 Creating a Custom `ServiceProvider`
```php
namespace App\Providers;

use WebCycles\Foundations\Services\ServiceProvider;
use WebCycles\Foundations\Services\ServiceContainer;
use App\Services\DatabaseConnection;
use App\Services\UserRepository;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in container.
     */
    public function register(ServiceContainer $container): void
    {
        $container->singleton(DatabaseConnection::class, function ($c) {
            return new DatabaseConnection(
                dsn: 'mysql:host=localhost;dbname=app',
                user: 'root',
                pass: 'secret'
            );
        });

        $container->bind(UserRepository::class, UserRepository::class);
    }

    /**
     * Bootstrap logic after all providers are registered.
     */
    public function boot(ServiceContainer $container): void
    {
        $db = $container->make(DatabaseConnection::class);
        $db->ping();
    }
}
```

### 10.2 Registering Providers in the Container
```php
$container->register(new DatabaseServiceProvider());
$container->register(new AuthServiceProvider());
$container->register(new RouteServiceProvider());
```

---

## 11. Lazy Loading Proxies (`LazyProxy`)

`LazyProxy` acts as a transparent proxy for lazy singletons registered via `$container->lazy()`.

### 11.1 How Virtual Proxies Work
- The proxy encapsulates a `Closure $resolver`.
- The real instance is created only upon first access (`__call`, `__get`, `__set`, or `resolve()`).
- Resolution is memoized so the closure executes exactly once.

### 11.2 Magic Method & Property Forwarding
```php
$lazyProxy = $container->lazy(SlowService::class, fn() => new SlowService());

$service = $container->make(SlowService::class); // Instance of LazyProxy

// Method forwarding:
$result = $service->calculateMetrics();

// Property forwarding:
$service->timeout = 10;
echo $service->status;
```

---

## 12. Container Introspection & Cache Management

```php
// Check if service is shared/singleton
$isShared = $container->isShared(DatabaseConnection::class);

// Invalidate a specific cached singleton
$container->forgetInstance(DatabaseConnection::class);

// Flush all cached singletons
$container->forgetInstances();

// Get list of all registered services and aliases
$services = $container->getRegisteredServices();

// Get list of all alias mappings
$aliases = $container->getAliases();
```

---

## 13. Exceptions & Error Handling

All services exceptions are located in `WebCycles\Foundations\Services\Exceptions`:

| Exception Class | Description |
| :--- | :--- |
| `ContainerException` | Base container runtime exception. |
| `NotFoundException` | Thrown by PSR-11 `get()` when an entry is not registered or cannot be resolved. |
| `BindingResolutionException` | Thrown when autowiring fails (uninstantiable class, unresolvable parameter). |
| `CircularDependencyException` | Thrown when circular dependencies are detected (e.g., `A` -> `B` -> `A`). |

### Circular Dependency Detection Example
If `ServiceA` requires `ServiceB`, and `ServiceB` requires `ServiceA`:
```php
try {
    $container->make(ServiceA::class);
} catch (CircularDependencyException $e) {
    // Message: Circular dependency detected: ServiceA → ServiceB → ServiceA
}
```

---

## 14. End-to-End Real World Examples

### 14.1 Application Bootstrapping with Multi-Provider Architecture

```php
use WebCycles\Foundations\Services\ServiceContainer;
use WebCycles\Foundations\Services\ServiceProvider;

// 1. Initialize Kernel Container
$container = new ServiceContainer();
ServiceContainer::setInstance($container);

// 2. Register Framework & Domain Providers
$providers = [
    App\Providers\ConfigServiceProvider::class,
    App\Providers\DatabaseServiceProvider::class,
    App\Providers\CacheServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\HttpServiceProvider::class,
];

foreach ($providers as $providerClass) {
    $container->register(new $providerClass());
}

// 3. Dispatch Console or Web application
$app = $container->make(WebCycles\Foundations\Console\Application::class);
$app->run();
```

---

### 14.2 Database & Repository Pattern with Contextual Adapters

```php
interface DatabaseInterface {
    public function query(string $sql): array;
}

class ReadOnlyDatabase implements DatabaseInterface {
    public function query(string $sql): array { return ['mode' => 'read-replica']; }
}

class PrimaryDatabase implements DatabaseInterface {
    public function query(string $sql): array { return ['mode' => 'primary-write']; }
}

class AnalyticsService {
    public function __construct(public DatabaseInterface $db) {}
}

class OrderService {
    public function __construct(public DatabaseInterface $db) {}
}

// Contextual routing:
$container->when(AnalyticsService::class)
          ->needs(DatabaseInterface::class)
          ->give(ReadOnlyDatabase::class);

$container->when(OrderService::class)
          ->needs(DatabaseInterface::class)
          ->give(PrimaryDatabase::class);

$analytics = $container->make(AnalyticsService::class);
$orders    = $container->make(OrderService::class);
```

---

### 14.3 Request-Scoped HTTP & Security Context

```php
class CurrentUser {
    public function __construct(public int $id, public string $username) {}
}

$container->scoped(CurrentUser::class, function () {
    // In real app, resolved from request token/session
    return new CurrentUser(id: 1, username: 'admin');
});

// Process web request inside scope
$output = $container->runInScope(function (ServiceContainer $c) {
    $user = $c->make(CurrentUser::class);
    return "Handling request for: " . $user->username;
});
```

---

### 14.4 Event Bus & Plugin Architecture via Tagging & Extending

```php
interface EventSubscriberInterface {
    public function handle(string $event, array $payload): void;
}

class UserRegisteredEmailSubscriber implements EventSubscriberInterface {
    public function handle(string $event, array $payload): void {
        if ($event === 'user.registered') {
            // send welcome email
        }
    }
}

class EventBus {
    /** @param EventSubscriberInterface[] $subscribers */
    public function __construct(public array $subscribers = []) {}

    public function dispatch(string $event, array $payload = []): void {
        foreach ($this->subscribers as $subscriber) {
            $subscriber->handle($event, $payload);
        }
    }
}

// Register subscribers with tag
$container->bind(UserRegisteredEmailSubscriber::class, UserRegisteredEmailSubscriber::class, ['event.subscribers']);

// Bind EventBus injecting all tagged subscribers
$container->singleton(EventBus::class, function (ServiceContainer $c) {
    $subscribers = $c->tagged('event.subscribers');
    return new EventBus($subscribers);
});

$eventBus = $container->make(EventBus::class);
$eventBus->dispatch('user.registered', ['userId' => 42]);
```
