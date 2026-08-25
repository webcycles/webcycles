# Complete WebCycles Console Module Documentation

Welcome to the definitive reference manual for the **WebCycles Console Module** (CLI Command Runner & Application Framework). This document provides an exhaustive, in-depth guide covering every feature, parameter, option, class, ANSI styling capability, and method available, demonstrating all possible ways of using the module with practical code examples.

---

## Table of Contents
1. [Core Architecture & Lifecycle](#1-core-architecture--lifecycle)
2. [Console Application (`Application`)](#2-console-application-application)
   - [2.1 Initializing the Application](#21-initializing-the-application)
   - [2.2 Registering Commands (`add`)](#22-registering-commands-add)
   - [2.3 Running the Application via CLI (`run`)](#23-running-the-application-via-cli-run)
   - [2.4 Programmatic Command Execution (`execute`)](#24-programmatic-command-execution-execute)
   - [2.5 Command Groups & Namespaces (`group:command`)](#25-command-groups--namespaces-groupcommand)
   - [2.6 Built-in Commands (`help`, `about`, `version`)](#26-built-in-commands-help-about-version)
   - [2.7 Intelligent Fuzzy Suggestion Engine](#27-intelligent-fuzzy-suggestion-engine)
3. [Creating CLI Commands (`Command`)](#3-creating-cli-commands-command)
   - [3.1 Structure of a Command Class](#31-structure-of-a-command-class)
   - [3.2 Configuring Metadata (`setName`, `setDescription`, `addUsage`)](#32-configuring-metadata-setname-setdescription-addusage)
   - [3.3 Defining Expected Arguments (`addArgument`)](#33-defining-expected-arguments-addargument)
   - [3.4 Defining Expected Options (`addOption`)](#34-defining-expected-options-addoption)
   - [3.5 Execution & Exit Codes (`execute`)](#35-execution--exit-codes-execute)
4. [CLI Input Parsing (`Input`)](#4-cli-input-parsing-input)
   - [4.1 How Arguments & Options are Parsed](#41-how-arguments--options-are-parsed)
   - [4.2 Positional Arguments (`getArgument`, `hasArgument`, `getArguments`)](#42-positional-arguments-getargument-hasargument-getarguments)
   - [4.3 Long Options (`--flag`, `--key=value`)](#43-long-options---flag---keyvalue)
   - [4.4 Short Options (`-f`, `-f=value`, `-abc`)](#44-short-options--f--fvalue--abc)
   - [4.5 Default Option Values](#45-default-option-values)
   - [4.6 Custom Argv Injection for Testing](#46-custom-argv-injection-for-testing)
5. [Formatted Terminal Output & ANSI Styling (`Output`)](#5-formatted-terminal-output--ansi-styling-output)
   - [5.1 Basic Writing (`write`, `writeln`, `newLine`)](#51-basic-writing-write-writeln-newline)
   - [5.2 Status Messages (`success`, `error`, `warning`, `info`, `comment`)](#52-status-messages-success-error-warning-info-comment)
   - [5.3 Structural Elements (`title`, `section`, `keyValue`)](#53-structural-elements-title-section-keyvalue)
   - [5.4 Progress Indicators (`progress`)](#54-progress-indicators-progress)
   - [5.5 Tables & Column Alignment (`table`)](#55-tables--column-alignment-table)
   - [5.6 Low-Level ANSI Styles & Colors (`style`)](#56-low-level-ansi-styles--colors-style)
   - [5.7 ANSI Support Detection & Overriding](#57-ansi-support-detection--overriding)
6. [Dependency Injection & Service Container Integration](#6-dependency-injection--service-container-integration)
   - [6.1 Registering Console in `ServiceContainer`](#61-registering-console-in-servicecontainer)
   - [6.2 Auto-injecting Dependencies into Commands](#62-auto-injecting-dependencies-into-commands)
7. [End-to-End Real World Examples](#7-end-to-end-real-world-examples)
   - [7.1 Database Migration Runner Command](#71-database-migration-runner-command)
   - [7.2 User Generator with Formatted Table Output](#72-user-generator-with-formatted-table-output)
   - [7.3 Cache Clear & System Maintenance Command](#73-cache-clear--system-maintenance-command)
   - [7.4 Interactive Command Pipeline & Sub-Process Trigger](#74-interactive-command-pipeline--sub-process-trigger)

---

## 1. Core Architecture & Lifecycle

When a CLI command is executed via `php webcycles <command> [arguments] [options]`, the console lifecycle proceeds as follows:

```
[ CLI Command Invocation ] (e.g. php webcycles composer:require monolog/monolog --dev)
            │
            ▼
   1. Input Parsing (`Input`)
      ├── Extracts command name: "composer:require"
      ├── Parses positional arguments: ["monolog/monolog"]
      └── Parses options/flags: ["dev" => true]
            │
            ▼
   2. Application Dispatch (`Application::run`)
      ├── Match command against registered commands table
      ├── If no command: Display ASCII banner & grouped command list
      ├── If command not found: Check for namespace group or suggest similar commands (Levenshtein)
      ├── If `--help` or `help <command>`: Render command documentation & usage
      └── If valid command: Invoke `$command->run($input, $output)`
            │
            ▼
   3. Command Execution (`Command::execute`)
      ├── Reads arguments/options via `$input`
      ├── Executes business logic (services, sub-processes, files)
      ├── Formats terminal messages via `$output` (ANSI colored)
      └── Returns integer exit code (0 for success, >0 for errors)
```

---

## 2. Console Application (`Application`)

### 2.1 Initializing the Application
Create an `Application` instance with your custom app name and version string:

```php
use WebCycles\Foundations\Console\Application;

$app = new Application('WebCycles', '1.0.0');
```

---

### 2.2 Registering Commands (`add`)
Register instances of `Command` subclasses:

```php
use App\Commands\GreetCommand;
use App\Commands\MigrateCommand;
use WebCycles\Foundations\Composer\Commands\ComposerInstallCommand;

$app->add(new GreetCommand());
$app->add(new MigrateCommand());
$app->add(new ComposerInstallCommand());
```

---

### 2.3 Running the Application via CLI (`run`)
`run()` automatically parses CLI global `$argv` and writes to standard streams (`STDOUT` and `STDERR`):

```php
// Typically invoked at the bottom of the CLI binary script
exit($app->run());
```

---

### 2.4 Programmatic Command Execution (`execute`)
Run any registered command programmatically from your PHP code (e.g. inside background jobs, web controllers, or other commands):

```php
// Execute without parameters
$exitCode = $app->execute('composer:install');

// Execute with arguments and flags
$exitCode = $app->execute('composer:require', [
    'monolog/monolog:^2.0',
    '--dev',
]);

// Execute with a custom Output buffer/instance
$customOutput = new \WebCycles\Foundations\Console\Output();
$exitCode = $app->execute('migrate:run', ['--force'], $customOutput);
```

---

### 2.5 Command Groups & Namespaces (`group:command`)
When command names contain colons (e.g., `composer:install`, `db:migrate`, `cache:clear`), `Application` automatically groups them by their prefix in the help screen and lists them together:

```bash
# Running a group name lists all commands belonging to that namespace
php webcycles composer
```

Output:
```
 Available commands in [composer]:

    composer:install  Download and install Composer to the runtime directory.
    composer:remove   Remove a package from the project dependencies.
    composer:require  Add a package to the project dependencies.
    composer:run      Run an arbitrary Composer command.
    composer:update   Update project dependencies.
```

---

### 2.6 Built-in Commands (`help`, `about`, `version`)
`Application` includes built-in commands and global flags out of the box:

- **`php webcycles`** – Shows the application ASCII logo banner and full list of commands.
- **`php webcycles help <command>`** or **`php webcycles <command> --help`** – Shows detailed syntax, argument explanations, options, and usage examples for that command.
- **`php webcycles version`** or **`php webcycles -V`** – Displays application version.
- **`php webcycles about`** – Displays system information (PHP version, OS, Root path, Storage path, Composer status).

---

### 2.7 Intelligent Fuzzy Suggestion Engine
If a user mistypes a command name, `Application` uses the Levenshtein distance algorithm to find and suggest similar commands:

```bash
$ php webcycles compsoer:requir

 ✗ Unknown command: "compsoer:requir"

 Did you mean one of these?
   composer:require
```

---

## 3. Creating CLI Commands (`Command`)

### 3.1 Structure of a Command Class
To create a new CLI command, extend `WebCycles\Foundations\Console\Command` and implement the `configure()` and `execute()` methods:

```php
namespace App\Commands;

use WebCycles\Foundations\Console\Command;
use WebCycles\Foundations\Console\Input;
use WebCycles\Foundations\Console\Output;

class GreetCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('greet')
             ->setDescription('Send a friendly greeting to a user.')
             ->addArgument('name', 'The name of the person to greet.')
             ->addOption('yell', 'Output the greeting in uppercase.')
             ->addUsage('php webcycles greet John')
             ->addUsage('php webcycles greet Alice --yell');
    }

    protected function execute(Input $input, Output $output): int
    {
        $name = $input->getArgument(0) ?? 'Guest';
        $message = "Hello, {$name}!";

        if ($input->hasOption('yell')) {
            $message = strtoupper($message);
        }

        $output->success($message);
        return 0; // Exit code 0 = Success
    }
}
```

---

### 3.2 Configuring Metadata (`setName`, `setDescription`, `addUsage`)
- **`setName(string $name)`**: Unique command identifier (e.g. `'cache:clear'`).
- **`setDescription(string $description)`**: Short one-line summary displayed in command lists.
- **`addUsage(string $example)`**: Practical syntax examples displayed in the `--help` menu.

---

### 3.3 Defining Expected Arguments (`addArgument`)
Define positional parameters required or accepted by the command:

```php
$this->addArgument('filename', 'Path to the input JSON file.')
     ->addArgument('destination', 'Output directory path.');
```

---

### 3.4 Defining Expected Options (`addOption`)
Define boolean flags or valued options:

```php
$this->addOption('force', 'Overwrite existing files without asking.')
     ->addOption('format', 'Output format (json, csv, table).');
```

---

### 3.5 Execution & Exit Codes (`execute`)
`execute(Input $input, Output $output): int` must return an integer exit code:
- **`0`**: Standard POSIX code for success.
- **`1` - `255`**: Indicates an error or failure condition.

---

## 4. CLI Input Parsing (`Input`)

`Input` parses the raw CLI arguments array (`$argv`) into structured tokens.

### 4.1 How Arguments & Options are Parsed
Given command invocation:
```bash
php webcycles make:model User --table=users -f --timestamps
```
`Input` extracts:
- Command name: `'make:model'`
- Positional arguments: `[0 => 'User']`
- Options: `['table' => 'users', 'f' => true, 'timestamps' => true]`

---

### 4.2 Positional Arguments (`getArgument`, `hasArgument`, `getArguments`)

```php
// Check if an argument was provided at index 0
if ($input->hasArgument(0)) {
    $firstArg = $input->getArgument(0);
}

// Retrieve with default fallback
$modelName = $input->getArgument(0) ?? 'DefaultModel';

// Get all positional arguments as an array
$allArgs = $input->getArguments();
```

---

### 4.3 Long Options (`--flag`, `--key=value`)

```php
// Flag option (--force) -> returns true if present
if ($input->hasOption('force')) {
    // Force enabled
}

// Key-value option (--env=production) -> returns 'production'
$env = $input->getOption('env', 'local');
```

---

### 4.4 Short Options (`-f`, `-f=value`, `-abc`)
Short options (single dash) can be standalone, valued, or clustered:
- **`-v`** -> `['v' => true]`
- **`-d=test`** -> `['d' => 'test']`
- **`-abc`** (Clustered flags) -> `['a' => true, 'b' => true, 'c' => true]`

```php
if ($input->hasOption('v')) {
    $output->info("Verbose mode enabled.");
}
```

---

### 4.5 Default Option Values
Pass a default fallback to `getOption()` if the option was not provided:

```php
$timeout = (int) $input->getOption('timeout', 30);
$driver  = $input->getOption('driver', 'redis');
```

---

### 4.6 Custom Argv Injection for Testing
You can manually instantiate `Input` with custom `$argv` arrays to write clean unit tests:

```php
$input = new Input(['webcycles', 'user:create', 'admin@example.com', '--role=admin', '--active']);

assert($input->getCommandName() === 'user:create');
assert($input->getArgument(0) === 'admin@example.com');
assert($input->getOption('role') === 'admin');
assert($input->hasOption('active') === true);
```

---

## 5. Formatted Terminal Output & ANSI Styling (`Output`)

The `Output` class provides rich formatting methods with automatic ANSI color support.

### 5.1 Basic Writing (`write`, `writeln`, `newLine`)

```php
$output->write('Loading...');
$output->writeln(' Done!');
$output->newLine(2); // Outputs 2 blank lines
```

---

### 5.2 Status Messages (`success`, `error`, `warning`, `info`, `comment`)

```php
// Green with checkmark (✓)
$output->success('Database migrated successfully.');

// Red with crossmark (✗) to STDERR
$output->error('Unable to connect to database host.');

// Yellow with warning symbol (⚠)
$output->warning('Configuration file missing default values.');

// Cyan with info symbol (ℹ)
$output->info('Found 42 records pending processing.');

// Muted gray text
$output->comment('Run "php webcycles list" for more commands.');
```

---

### 5.3 Structural Elements (`title`, `section`, `keyValue`)

```php
// Renders large underlined Cyan Header
$output->title('WebCycles System Health Check');

// Renders Yellow Section Separator
$output->section('Database Connections');

// Renders aligned label and value pair
$output->keyValue('Primary DB', 'Connected (127.0.0.1:3306)');
$output->keyValue('Cache Driver', 'Redis 7.2.0');
```

---

### 5.4 Progress Indicators (`progress`)

```php
// Renders magenta spinning indicator (⟳)
$output->progress('Optimizing classmap autoloader');
```

---

### 5.5 Tables & Column Alignment (`table`)
Renders formatted UTF-8 box-drawing tables with automatic column width calculation:

```php
$headers = ['ID', 'Service Name', 'Lifecycle', 'Status'];

$rows = [
    [1, 'DatabaseConnection', 'Singleton', 'Active'],
    [2, 'Router', 'Singleton', 'Active'],
    [3, 'SecurityContext', 'Scoped', 'Ready'],
    [4, 'PdfGenerator', 'Lazy Proxy', 'Standby'],
];

$output->table($headers, $rows);
```

Output:
```
┌───┬────────────────────┬────────────┬─────────┐
│ ID│ Service Name       │ Lifecycle  │ Status  │
├───┼────────────────────┼────────────┼─────────┤
│ 1 │ DatabaseConnection │ Singleton  │ Active  │
│ 2 │ Router             │ Singleton  │ Active  │
│ 3 │ SecurityContext    │ Scoped     │ Ready   │
│ 4 │ PdfGenerator       │ Lazy Proxy │ Standby │
└───┴────────────────────┴────────────┴─────────┘
```

---

### 5.6 Low-Level ANSI Styles & Colors (`style`)
Combine styles (`bold`, `dim`, `underline`), foreground colors (`red`, `green`, `yellow`, `blue`, `magenta`, `cyan`, `white`, `gray`), and background colors (`bg_red`, `bg_green`, `bg_blue`, etc.):

```php
$text = $output->style('CRITICAL ERROR', 'bold', 'white', 'bg_red');
$output->writeln($text);

$highlight = $output->style('Highlighted Keyword', 'bold', 'cyan');
$output->writeln("Processing: {$highlight}");
```

---

### 5.7 ANSI Support Detection & Overriding
`Output` automatically detects terminal capabilities across Windows (ConPTY / VT100), macOS, and Linux. You can also manually force or disable colors:

```php
// Check if ANSI is active
if ($output->isAnsiSupported()) {
    // Terminal supports colors
}

// Force enable ANSI (e.g. for CI pipelines that support color codes)
$output->setAnsiSupported(true);

// Disable ANSI (e.g. for raw text log dumping)
$output->setAnsiSupported(false);
```

---

## 6. Dependency Injection & Service Container Integration

### 6.1 Registering Console in `ServiceContainer`
Bind `Application` as a singleton in the service container during kernel bootstrap:

```php
use WebCycles\Foundations\Console\Application;
use WebCycles\Foundations\Services\ServiceContainer;

$services = ServiceContainer::getInstance();

$application = new Application('WebCycles', WEBCYCLES_VERSION);
$services->singleton(Application::class, $application);
```

---

### 6.2 Auto-injecting Dependencies into Commands
Use `ServiceContainer` to resolve dependencies directly inside command constructors or execution logic:

```php
namespace App\Commands;

use WebCycles\Foundations\Console\Command;
use WebCycles\Foundations\Console\Input;
use WebCycles\Foundations\Console\Output;
use WebCycles\Foundations\Services\ServiceContainer;
use App\Services\DatabaseService;

class DatabaseSeedCommand extends Command
{
    private DatabaseService $db;

    public function __construct(?DatabaseService $db = null)
    {
        $this->db = $db ?? ServiceContainer::getInstance()->make(DatabaseService::class);
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('db:seed')
             ->setDescription('Seed the database with test records.');
    }

    protected function execute(Input $input, Output $output): int
    {
        $output->progress('Seeding database tables');
        $this->db->seed();
        $output->success('Seeding complete.');
        return 0;
    }
}
```

---

## 7. End-to-End Real World Examples

### 7.1 Database Migration Runner Command

```php
namespace App\Commands;

use WebCycles\Foundations\Console\Command;
use WebCycles\Foundations\Console\Input;
use WebCycles\Foundations\Console\Output;

class MigrateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('migrate')
             ->setDescription('Run all pending database migrations.')
             ->addOption('fresh', 'Drop all tables before running migrations.')
             ->addOption('seed', 'Seed the database after migrating.')
             ->addUsage('php webcycles migrate')
             ->addUsage('php webcycles migrate --fresh --seed');
    }

    protected function execute(Input $input, Output $output): int
    {
        $output->title('WebCycles Migration Engine');

        if ($input->hasOption('fresh')) {
            $output->warning('Dropping all tables...');
        }

        $output->progress('Applying 2026_08_25_create_users_table');
        $output->progress('Applying 2026_08_25_create_orders_table');

        $output->newLine();
        $output->success('All 2 migrations executed successfully.');

        if ($input->hasOption('seed')) {
            $output->info('Running database seeders...');
            $output->success('Database seeded.');
        }

        return 0;
    }
}
```

---

### 7.2 User Generator with Formatted Table Output

```php
namespace App\Commands;

use WebCycles\Foundations\Console\Command;
use WebCycles\Foundations\Console\Input;
use WebCycles\Foundations\Console\Output;

class UserListCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('user:list')
             ->setDescription('Display a table of all registered application users.')
             ->addOption('role', 'Filter users by role (admin, user, editor).')
             ->addUsage('php webcycles user:list')
             ->addUsage('php webcycles user:list --role=admin');
    }

    protected function execute(Input $input, Output $output): int
    {
        $roleFilter = $input->getOption('role');

        $output->title('Application User Directory');
        if ($roleFilter) {
            $output->info("Filtering by role: {$roleFilter}");
        }

        $headers = ['ID', 'Full Name', 'Email Address', 'Role', 'Status'];
        $users = [
            [1, 'Bartłomiej Walczak', 'machina@duck.com', 'admin', 'Active'],
            [2, 'Alice Smith', 'alice@example.com', 'editor', 'Active'],
            [3, 'Bob Johnson', 'bob@example.com', 'user', 'Suspended'],
        ];

        if ($roleFilter) {
            $users = array_filter($users, fn($u) => $u[3] === $roleFilter);
        }

        $output->table($headers, $users);
        $output->comment('Total users shown: ' . count($users));
        $output->newLine();

        return 0;
    }
}
```

---

### 7.3 Cache Clear & System Maintenance Command

```php
namespace App\Commands;

use WebCycles\Foundations\Console\Command;
use WebCycles\Foundations\Console\Input;
use WebCycles\Foundations\Console\Output;

class CacheClearCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('cache:clear')
             ->setDescription('Flush application, route, and autoloader cache stores.')
             ->addOption('all', 'Flush all cache drivers including session and Redis.');
    }

    protected function execute(Input $input, Output $output): int
    {
        $output->progress('Clearing compiled autoloader classmap');
        
        $classmapFile = WEBCYCLES_STORAGE_CACHE_PATH . '/webcycles/autoloader_classmap.generated.php';
        if (file_exists($classmapFile)) {
            unlink($classmapFile);
        }

        $output->success('Autoloader classmap cache removed.');

        if ($input->hasOption('all')) {
            $output->progress('Flushing Redis memory cache');
            $output->success('Redis cache flushed.');
        }

        $output->newLine();
        $output->success('System cache cleanup completed.');
        return 0;
    }
}
```

---

### 7.4 Interactive Command Pipeline & Sub-Process Trigger

```php
namespace App\Commands;

use WebCycles\Foundations\Console\Command;
use WebCycles\Foundations\Console\Input;
use WebCycles\Foundations\Console\Output;
use WebCycles\Foundations\Console\Application;
use WebCycles\Foundations\Services\ServiceContainer;

class DeployCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('app:deploy')
             ->setDescription('Run full deployment pipeline.')
             ->addOption('prod', 'Deploy directly to production.');
    }

    protected function execute(Input $input, Output $output): int
    {
        $output->title('Starting WebCycles Deployment Sequence');

        $app = ServiceContainer::getInstance()->make(Application::class);

        // 1. Run migrations
        $output->section('Step 1: Database Migrations');
        $code = $app->execute('migrate', [], $output);
        if ($code !== 0) {
            $output->error('Deployment aborted: Migration failed.');
            return $code;
        }

        // 2. Clear cache
        $output->section('Step 2: Cache Eviction');
        $code = $app->execute('cache:clear', ['--all'], $output);
        if ($code !== 0) {
            $output->error('Deployment aborted: Cache clear failed.');
            return $code;
        }

        $output->newLine();
        $output->success('Deployment completed successfully!');
        return 0;
    }
}
```
