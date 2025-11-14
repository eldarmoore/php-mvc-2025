# Custom PHP MVC Framework

A powerful, lightweight PHP MVC framework built from scratch with modern features and best practices. This framework provides everything you need to build robust web applications.

## Features

- ✅ **MVC Architecture** - Clean separation of concerns
- ✅ **Routing System** - Flexible routing with dynamic parameters and middleware
- ✅ **Dependency Injection** - Powerful IoC container with automatic resolution
- ✅ **Database Layer** - PDO-based abstraction with query builder and ORM
- ✅ **Template Engine** - Clean view system with layouts and partials
- ✅ **Validation** - Comprehensive data validation system
- ✅ **Security** - CSRF protection, XSS prevention, password hashing
- ✅ **PSR-4 Autoloading** - Modern PHP autoloading standard
- ✅ **Docker Support** - Complete Docker setup for development and production

## Quick Start with Docker

The fastest way to get started is using Docker:

```bash
# Linux/Mac
make setup

# Windows
docker-setup.bat
```

Access your application at http://localhost:8080

For detailed Docker instructions, see [DOCKER.md](DOCKER.md)

## Table of Contents

- [Installation](#installation)
- [Docker Setup](#docker-setup)
- [Configuration](#configuration)
- [Routing](#routing)
- [Controllers](#controllers)
- [Models](#models)
- [Views](#views)
- [Validation](#validation)
- [Security](#security)
- [Database](#database)
- [Middleware](#middleware)
- [Helper Functions](#helper-functions)

## Installation

### Requirements

- PHP 8.0 or higher
- PDO extension (for database operations)
- Apache/Nginx web server

### Setup

1. **Clone or download this repository**

2. **Configure your web server** to point to the `public` directory

   **Apache (.htaccess included)**
   ```apache
   DocumentRoot "/path/to/project/public"
   ```

   **Nginx**
   ```nginx
   root /path/to/project/public;
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

3. **Create your environment file**
   ```bash
   cp .env.example .env
   ```

4. **Configure your database** in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Set up the database** by running the SQL migrations:
   ```bash
   # Import the migration file
   mysql -u your_username -p your_database < database/migrations/001_create_users_table.sql
   ```

6. **Access your application** at `http://localhost`

## Docker Setup

### Quick Start

The recommended way to run this framework is using Docker:

**Linux/Mac:**
```bash
# Initial setup
make setup

# Start containers
make up

# Stop containers
make down

# View logs
make logs
```

**Windows:**
```batch
REM Initial setup
docker-setup.bat

REM Start containers
docker-start.bat

REM Stop containers
docker-stop.bat
```

### Services

When running with Docker, you get:

- **Application**: http://localhost:8080
- **PHPMyAdmin**: http://localhost:8081 (username: `mvc_user`, password: `secret`)
- **Mailhog** (Email testing): http://localhost:8025

### Docker Commands

```bash
# Open shell in app container
make shell

# Run composer commands
make composer
make composer-update

# Access MySQL
make db-shell

# Enable/disable Xdebug
make xdebug-on
make xdebug-off

# Backup database
make backup

# View all available commands
make help
```

### Production Deployment

```bash
# Build production images
make prod-build

# Deploy to production
make prod-up
```

For comprehensive Docker documentation, see **[DOCKER.md](DOCKER.md)**

## Configuration

All configuration files are located in the `config` directory:

### Application Configuration (`config/app.php`)

```php
return [
    'name' => 'My Application',
    'env' => 'development',  // development, production, testing
    'debug' => true,         // Show detailed errors
    'url' => 'http://localhost',
    'timezone' => 'UTC',
];
```

### Database Configuration (`config/database.php`)

Supports MySQL, PostgreSQL, and SQLite.

## Routing

Routes are defined in `routes/web.php`. The router supports all HTTP methods and dynamic parameters.

### Basic Routing

```php
use Core\Routing\Router;

$router = app(Router::class);

// GET request
$router->get('/', function () {
    return 'Hello, World!';
});

// POST request
$router->post('/submit', 'FormController@submit');

// Other HTTP methods
$router->put('/users/{id}', 'UserController@update');
$router->delete('/users/{id}', 'UserController@destroy');
$router->patch('/users/{id}', 'UserController@patch');
```

### Route Parameters

```php
// Required parameter
$router->get('/users/{id}', function ($id) {
    return "User ID: $id";
});

// Optional parameter
$router->get('/posts/{id}/comments/{commentId?}', function ($id, $commentId = null) {
    if ($commentId) {
        return "Post $id, Comment $commentId";
    }
    return "Post $id";
});
```

### Controller Routes

```php
// Basic controller route
$router->get('/users', 'UserController@index');

// Full CRUD routes
$router->get('/users', 'UserController@index');
$router->get('/users/create', 'UserController@create');
$router->post('/users', 'UserController@store');
$router->get('/users/{id}', 'UserController@show');
$router->get('/users/{id}/edit', 'UserController@edit');
$router->put('/users/{id}', 'UserController@update');
$router->delete('/users/{id}', 'UserController@destroy');
```

### Route Groups

```php
// Group with prefix
$router->group(['prefix' => 'api'], function ($router) {
    $router->get('/users', 'Api\UserController@index');
    $router->get('/posts', 'Api\PostController@index');
});

// Group with middleware
$router->group(['middleware' => 'auth'], function ($router) {
    $router->get('/dashboard', 'DashboardController@index');
    $router->get('/profile', 'ProfileController@index');
});

// Combined prefix and middleware
$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function ($router) {
    $router->get('/users', 'Admin\UserController@index');
});
```

### Named Routes

```php
$router->get('/profile', 'ProfileController@show')->name('profile');

// Generate URL from named route
$url = $router->route('profile');
```

## Controllers

Controllers handle the application logic and are located in `app/Controllers`.

### Creating a Controller

```php
<?php

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        // Get all users
        $users = User::all();

        // Return view with data
        return $this->view('users.index', [
            'users' => $users
        ]);
    }

    public function show(string $id): Response
    {
        $user = User::findOrFail($id);

        return $this->view('users.show', [
            'user' => $user
        ]);
    }

    public function store(): Response
    {
        // Validate request
        $validated = $this->validate([
            'name' => 'required|min:3',
            'email' => 'required|email',
        ]);

        // Create user
        $user = User::create($validated);

        // Redirect with flash message
        $this->flash('success', 'User created!');
        return $this->redirect('/users');
    }

    public function apiIndex(): Response
    {
        $users = User::all();

        // Return JSON response
        return $this->json([
            'success' => true,
            'data' => $users
        ]);
    }
}
```

### Controller Methods

- `view($view, $data)` - Render a view
- `json($data, $status)` - Return JSON response
- `redirect($url)` - Redirect to URL
- `back()` - Redirect to previous page
- `validate($rules, $messages)` - Validate request data
- `flash($key, $value)` - Flash data to session

## Models

Models represent database tables and provide ORM functionality.

### Creating a Model

```php
<?php

namespace App\Models;

use Core\Database\Model;

class User extends Model
{
    // Table name (auto-detected as 'users' from class name)
    protected string $table = 'users';

    // Primary key
    protected string $primaryKey = 'id';

    // Mass assignable attributes
    protected array $fillable = ['name', 'email', 'password'];

    // Enable timestamps (created_at, updated_at)
    protected bool $timestamps = true;
}
```

### Model Operations

#### Create

```php
// Method 1: Using create()
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('password123')
]);

// Method 2: Using new and save()
$user = new User();
$user->name = 'Jane Doe';
$user->email = 'jane@example.com';
$user->save();
```

#### Read

```php
// Find by ID
$user = User::find(1);

// Find or throw exception
$user = User::findOrFail(1);

// Get all records
$users = User::all();

// Get first record
$user = User::first();

// Query with conditions
$users = User::where('email', '=', 'john@example.com')->get();
$user = User::where('id', '>', 10)->first();
```

#### Update

```php
$user = User::find(1);
$user->name = 'Updated Name';
$user->save();

// Or update multiple at once
$user->fill([
    'name' => 'New Name',
    'email' => 'new@example.com'
])->save();
```

#### Delete

```php
$user = User::find(1);
$user->delete();
```

### Relationships

```php
class User extends Model
{
    // One-to-many
    public function posts(): array
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    // One-to-one
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }
}

class Post extends Model
{
    // Inverse relationship
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts(); // Get all posts by user
```

## Views

Views are located in `app/Views` and use PHP templates with layout support.

### Basic View

```php
// app/Views/users/index.php
<?php $this->extend('layouts.app'); ?>

<?php $this->section('title'); ?>
Users List
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<h1>Users</h1>
<ul>
    <?php foreach ($users as $user): ?>
        <li><?php echo e($user->name); ?></li>
    <?php endforeach; ?>
</ul>
<?php $this->endSection(); ?>
```

### Layouts

```php
// app/Views/layouts/app.php
<!DOCTYPE html>
<html>
<head>
    <title><?php $this->yield('title', 'Default Title'); ?></title>
</head>
<body>
    <div class="content">
        <?php $this->yield('content'); ?>
    </div>
</body>
</html>
```

### Rendering Views

```php
// In controller
return $this->view('users.index', [
    'users' => $users
]);

// Or using helper
return view('users.index', ['users' => $users]);
```

### Escaping Output

Always escape user input to prevent XSS:

```php
<?php echo e($user->name); ?>
<!-- or -->
<?php $this->e($user->name); ?>
```

## Validation

Validate request data using the built-in validator.

### Available Rules

- `required` - Field must be present
- `email` - Must be valid email
- `min:value` - Minimum length/value
- `max:value` - Maximum length/value
- `numeric` - Must be numeric
- `integer` - Must be integer
- `string` - Must be string
- `alpha` - Only letters
- `alpha_num` - Letters and numbers
- `in:foo,bar` - Must be in list
- `url` - Valid URL
- `confirmed` - Must match {field}_confirmation
- `same:other_field` - Must match other field
- `different:other_field` - Must differ from other field
- `regex:pattern` - Match regex pattern

### Using Validation

```php
// In controller
$validated = $this->validate([
    'name' => 'required|min:3|max:255',
    'email' => 'required|email',
    'age' => 'required|integer|min:18',
    'password' => 'required|min:8|confirmed',
]);

// Custom messages
$validated = $this->validate([
    'email' => 'required|email',
], [
    'email.required' => 'Please provide your email address',
    'email.email' => 'Email format is invalid',
]);
```

## Security

### CSRF Protection

```php
// In your form view
<form method="POST" action="/users">
    <?php echo csrf_field(); ?>
    <!-- form fields -->
</form>
```

```php
// Or manually
<?php echo '<input type="hidden" name="_token" value="' . csrf_token() . '">'; ?>
```

### XSS Protection

Always escape output:

```php
<?php echo e($userInput); ?>
```

### Password Hashing

```php
use Core\Security\Hash;

// Hash password
$hash = Hash::make('password123');

// Verify password
if (Hash::check('password123', $hash)) {
    // Password is correct
}
```

### Middleware

Apply middleware to routes for authentication, CSRF, etc:

```php
// Register middleware in your application
$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('csrf', CsrfMiddleware::class);

// Apply to routes
$router->get('/dashboard', 'DashboardController@index')
       ->middleware('auth');

// Multiple middleware
$router->post('/users', 'UserController@store')
       ->middleware('auth', 'csrf');
```

## Database

### Query Builder

```php
use Core\Database\Database;

// Select
$users = Database::table('users')
    ->select('id', 'name', 'email')
    ->where('active', '=', 1)
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->get();

// Joins
$posts = Database::table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->where('posts.published', '=', true)
    ->get();

// Insert
$id = Database::table('users')->insert([
    'name' => 'John',
    'email' => 'john@example.com'
]);

// Update
$affected = Database::table('users')
    ->where('id', '=', 1)
    ->update(['name' => 'Updated Name']);

// Delete
$deleted = Database::table('users')
    ->where('id', '=', 1)
    ->delete();
```

### Transactions

```php
Database::transaction(function ($db) {
    $userId = Database::table('users')->insert([
        'name' => 'John',
        'email' => 'john@example.com'
    ]);

    Database::table('profiles')->insert([
        'user_id' => $userId,
        'bio' => 'Hello!'
    ]);
});
```

## Helper Functions

### Available Helpers

```php
// Configuration
$value = config('app.name', 'Default');

// Environment
$value = env('APP_ENV', 'production');

// Application
$app = app();
$service = app(ServiceClass::class);

// Views
$content = view('welcome', ['name' => 'John']);

// URLs
$url = url('/path');
$asset = asset('css/style.css');

// Redirects
redirect('/home');

// Debugging
dd($variable);  // Dump and die
dump($variable);  // Dump

// Security
$token = csrf_token();
$field = csrf_field();
$escaped = e($input);

// Paths
$path = base_path('file.txt');
$path = storage_path('logs/app.log');
$path = public_path('index.php');

// Old input (after validation failure)
$value = old('email');
```

## Project Structure

```
├── app/
│   ├── Controllers/      # Application controllers
│   ├── Models/          # Application models
│   ├── Views/           # View templates
│   └── Middleware/      # Custom middleware
├── config/              # Configuration files
│   ├── app.php
│   └── database.php
├── core/                # Framework core files
│   ├── Application.php
│   ├── Container/
│   ├── Database/
│   ├── Http/
│   ├── Routing/
│   ├── Security/
│   ├── Support/
│   ├── Validation/
│   └── View/
├── database/
│   └── migrations/      # SQL migration files
├── public/              # Web server document root
│   ├── index.php       # Entry point
│   └── .htaccess
├── routes/
│   └── web.php         # Route definitions
├── storage/             # Application storage
│   ├── cache/
│   ├── logs/
│   └── uploads/
├── vendor/              # Dependencies (if using Composer)
├── .env.example         # Example environment file
└── composer.json        # Composer configuration
```

## Best Practices

1. **Always validate user input** using the validation system
2. **Escape output** to prevent XSS attacks
3. **Use CSRF protection** for forms
4. **Hash passwords** using the Hash class
5. **Use prepared statements** (handled automatically by query builder)
6. **Follow MVC patterns** - keep logic in controllers, data in models
7. **Use environment variables** for sensitive configuration
8. **Enable error reporting** in development, disable in production

## License

This framework is open-source and available for educational and commercial use.

## Contributing

Contributions are welcome! Feel free to submit issues and pull requests.

## Support

For questions and support, please open an issue on the repository.
