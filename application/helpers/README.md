# InvoicePlane Helper Functions

This directory contains helper functions that modernize the InvoicePlane codebase.

## Available Helpers

### Inflector Helper (`inflector_helper.php`)

Provides automatic pluralization and singularization of English words.

**Functions:**
- `pluralize(string $word): string` - Convert singular to plural
- `singularize(string $word): string` - Convert plural to singular

**Example:**
```php
$this->load->helper('inflector');

echo pluralize('client');    // "clients"
echo pluralize('invoice');   // "invoices"
echo singularize('users');   // "user"
```

**Usage in Code:**
This helper is automatically loaded by `MY_Loader` to provide dynamic model name pluralization, eliminating the need for hardcoded plural mappings.

### Path Helper (`path_helper.php`)

Modern path manipulation functions inspired by Laravel helpers. These provide cleaner alternatives to using defines.

**Functions:**
- `app_path(string $path = ''): string` - Application directory path
- `base_path(string $path = ''): string` - Project root path
- `public_path(string $path = ''): string` - Public directory path
- `storage_path(string $path = ''): string` - Storage directory path
- `config_path(string $path = ''): string` - Config directory path
- `uploads_path(string $path = ''): string` - Uploads directory path
- `logs_path(string $path = ''): string` - Logs directory path
- `view_path(string $path = ''): string` - Views directory path
- `asset_path(string $path = ''): string` - Assets directory path
- `normalize_path(string $path): string` - Normalize path separators
- `join_paths(string ...$paths): string` - Join multiple path segments

**Example:**
```php
$this->load->helper('path');

// Instead of: APPPATH . 'models/Invoice.php'
$model_path = app_path('models/Invoice.php');

// Instead of: UPLOADS_FOLDER . 'customer_files/' . $filename
$file_path = uploads_path('customer_files/' . $filename);

// Join multiple paths safely
$full_path = join_paths(base_path(), 'storage', 'cache', 'file.txt');
```

**Benefits:**
- Cross-platform path separators
- Cleaner, more readable code
- Easier to test (functions instead of constants)
- Familiar Laravel-style API

## Loading Helpers

### Manual Loading
```php
// In a controller
$this->load->helper('inflector');
$this->load->helper('path');

// Or load multiple at once
$this->load->helper(['inflector', 'path']);
```

### Auto-loading
To auto-load these helpers for all controllers, edit `config/autoload.php`:

```php
$autoload['helper'] = ['inflector', 'path'];
```

## Migration Guide

### Replacing Defines with Path Helpers

**Before:**
```php
$log_file = LOGS_FOLDER . 'error.log';
$upload = UPLOADS_FOLDER . 'files/' . $filename;
$config = CONFIGPATH . 'database.php';
```

**After:**
```php
$this->load->helper('path');

$log_file = logs_path('error.log');
$upload = uploads_path('files/' . $filename);
$config = config_path('database.php');
```

## Contributing

When adding new helpers:
1. Follow CodeIgniter helper conventions
2. Use function guards: `if (!function_exists('function_name'))`
3. Add type hints and return types
4. Document with PHPDoc comments
5. Update this README
