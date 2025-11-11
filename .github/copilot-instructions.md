# GitHub Copilot Instructions for InvoicePlane Modernization

## Table of Contents

1. [Project Overview](#project-overview)
2. [Current Architecture](#current-architecture)
3. [Modernization Goals](#modernization-goals)
4. [Code Style and Standards](#code-style-and-standards)
5. [Directory Structure](#directory-structure)
6. [Module Structure](#module-structure)
7. [HMVC and MX Extensions](#hmvc-and-mx-extensions)
8. [Assets and Frontend](#assets-and-frontend)
9. [Database and Models](#database-and-models)
10. [Testing](#testing)
11. [Security Best Practices](#security-best-practices)
12. [Git Workflow](#git-workflow)
13. [Common Pitfalls to Avoid](#common-pitfalls-to-avoid)
14. [Local Development Setup](#local-development-setup)
15. [Debugging](#debugging)
16. [Performance Considerations](#performance-considerations)
17. [When in Doubt](#when-in-doubt)
18. [Helpful Commands](#helpful-commands)
19. [Project File Structure Overview](#project-file-structure-overview)
20. [References](#references)

---

## Project Overview
InvoicePlane is an ancient CodeIgniter 3 application being modernized to follow modern PHP standards while maintaining backward compatibility.

## Current Architecture
- **Framework**: CodeIgniter 3 (via pocketarc/codeigniter)
- **Pattern**: HMVC using Modular Extensions (MX)
- **Modules**: Located in `application/modules/`
- **Assets**: Compiled to `public/assets/` from `resources/assets/`
- **Entry Point**: `public/index.php`

## Modernization Goals
1. Move application to use `public/` directory structure
2. Separate source assets (`resources/assets/`) from built assets (`public/assets/`)
3. Gradually migrate to PSR-4 autoloading and naming conventions
4. Maintain backward compatibility during transition

## Code Style and Standards

### PHP Code Style
- **PHP Version**: 7.4+ (with 8.0+ features being added)
- **Standard**: PSR-12 for new code, legacy CodeIgniter style for existing code
- **Attributes**: Use PHP 8 attributes where applicable (e.g., `#[AllowDynamicProperties]`)
- **Type Hints**: Add type hints to new code and when refactoring
- **Return Types**: Declare return types for all new methods

### Linting and Formatting
- **Laravel Pint**: Run `composer pint` before committing
- **PHPCS**: Run `composer phpcs` for style checking
- **Rector**: Run `composer rector` for automated refactoring

### File Naming Conventions

#### Current State (Legacy - DO NOT CHANGE EXISTING)
- Controllers: `Invoices.php` (class Invoices)
- Models: `Mdl_invoices.php` (class Mdl_invoices)
- Helpers: `invoice_helper.php`

#### Target State (For New Code)
- Controllers: `InvoicesController.php` (class InvoicesController)
  - Namespace: `App\Modules\Invoices\Controllers`
- Models: `Invoice.php` (class Invoice)
  - Namespace: `App\Modules\Invoices\Models`
- Use PSR-4 autoloading

### When Creating New Controllers
**DO NOT** create controllers in the old style. When asked to create a new controller:

```php
<?php
// WRONG (old style) - Don't do this
// File: application/modules/invoices/controllers/Invoices.php
class Invoices extends Admin_Controller
{
    public function index()
    {
        // ...
    }
}
```

```php
<?php
// CORRECT (new style) - Do this for new controllers
// File: application/modules/invoices/Controllers/InvoicesController.php
namespace App\Modules\Invoices\Controllers;

use Admin_Controller;

class InvoicesController extends Admin_Controller
{
    public function index(): void
    {
        // ...
    }
}
```

### When Creating New Models
```php
<?php
// WRONG (old style)
// File: application/modules/invoices/models/Mdl_invoices.php
class Mdl_invoices extends Response_Model
{
    // ...
}
```

```php
<?php
// CORRECT (new style)
// File: application/modules/invoices/Models/Invoice.php
namespace App\Modules\Invoices\Models;

use Response_Model;

class Invoice extends Response_Model
{
    protected string $table = 'ip_invoices';
    
    // ...
}
```

## PSR-4 Migration Status

### ✅ Completed Features
The application now supports **dual loading** - both PSR-4 and legacy naming conventions work simultaneously.

**MX Enhancements:**
- `MX/Modules.php::load()` - Checks for PSR-4 controllers first, falls back to legacy
- `MX/Router.php::locate()` - Looks in `Controllers/` before `controllers/`
- `MX/Loader.php::model()` - Supports PSR-4 models: `App\Modules\{Module}\Models\{Model}`

**URL Routing:**
- `invoices/form` → `App\Modules\Invoices\Controllers\InvoicesController::form()` ✅
- Legacy URLs continue to work unchanged
- No URL structure changes required

**Autoloading:**
```json
{
  "autoload": {
    "psr-4": {
      "App\\": "application/",
      "App\\Modules\\": "application/modules/"
    }
  }
}
```

### How MX Loads Controllers (Priority Order)

1. **PSR-4 First**: Checks for `App\Modules\{Module}\Controllers\{Controller}Controller`
   - Example: `invoices/form` → checks for `App\Modules\Invoices\Controllers\InvoicesController`
   - Location: `application/modules/invoices/Controllers/InvoicesController.php`

2. **Legacy Fallback**: Loads from `controllers/` directory
   - Example: `invoices/status` → loads `Invoices` class
   - Location: `application/modules/invoices/controllers/Invoices.php`

### How MX Loads Models (Priority Order)

1. **PSR-4 First**: Checks for `App\Modules\{Module}\Models\{Model}`
   - Example: `$this->load->model('invoice')` → checks `App\Modules\Invoices\Models\Invoice`
   - Location: `application/modules/invoices/Models/Invoice.php`

2. **Legacy Fallback**: Loads from `models/` directory
   - Example: `$this->load->model('mdl_invoices')` → loads `Mdl_invoices`
   - Location: `application/modules/invoices/models/Mdl_invoices.php`

### Migration Example: Invoices Module

**Legacy Structure (Still Works):**
```
application/modules/invoices/
├── controllers/
│   └── Invoices.php          (class Invoices)
└── models/
    └── Mdl_invoices.php      (class Mdl_invoices)
```

**PSR-4 Structure (Now Active):**
```
application/modules/invoices/
├── Controllers/              ← Note: Capital C
│   └── InvoicesController.php  (namespace App\Modules\Invoices\Controllers)
└── Models/                   ← Note: Capital M
    └── Invoice.php           (namespace App\Modules\Invoices\Models)
```

Both structures coexist. MX tries PSR-4 first, then falls back to legacy.

## Directory Structure

### Source vs Built Assets
```
resources/
  └── assets/                    # SOURCE files (edit these)
      ├── core_scss/             # Core SCSS files
      ├── invoiceplane_sass/     # Default theme SASS
      └── invoiceplane_blue_sass/# Blue theme SASS

public/                          # BUILT/COMPILED files (don't edit directly)
  ├── index.php                  # Application entry point
  └── assets/                    # Compiled CSS/JS/fonts
      ├── core/
      ├── invoiceplane/
      └── invoiceplane_blue/
```

### Build Process
- **Build**: `npm run build` - Production build
- **Dev Build**: `npm run dev-build` - Development build with source maps
- **Watch**: `npm run dev` - Watch for changes and rebuild

### Never Edit These Directly
- `public/assets/**/*.css` - Generated from SASS
- `public/assets/**/*.min.js` - Generated from source JS
- `public/assets/core/fonts/*` - Copied from node_modules

### Always Edit These
- `resources/assets/**/*.scss` - SASS source files
- `public/assets/core/js/scripts.js` - Custom JavaScript (not minified)

## Module Structure

### Current Module Structure
```
application/modules/invoices/
├── controllers/
│   ├── Invoices.php      # Main controller
│   ├── Ajax.php          # AJAX endpoints
│   └── Cron.php          # Scheduled tasks
├── models/
│   └── Mdl_invoices.php  # Database model
└── views/
    ├── index.php         # List view
    ├── form.php          # Create/edit form
    └── view.php          # Detail view
```

### Target Module Structure (Gradual Migration)
```
application/modules/invoices/
├── Controllers/          # NEW - PSR-4
│   └── InvoicesController.php
├── controllers/          # OLD - Keep for now
│   └── Invoices.php
├── Models/               # NEW - PSR-4
│   └── Invoice.php
├── models/               # OLD - Keep for now
│   └── Mdl_invoices.php
└── views/                # SAME - No change
    └── ...
```

## HMVC and MX Extensions

### Current Routing
- MX handles routing: `application/third_party/MX/`
- URL: `invoices/create` → `Invoices::create()`
- Module loading: `Modules::run('invoices/create')`

### DO NOT Modify MX Unless
- You're implementing PSR-4 controller loading
- You're fixing a critical bug
- You have thoroughly tested changes

### Loading Models in Controllers
```php
// Current (legacy) way - still supported
$this->load->model('mdl_invoices');
$invoices = $this->mdl_invoices->get_all();

// Transitional way - also works
$this->load->model('invoices/Mdl_invoices');

// Future way - when fully migrated
use App\Modules\Invoices\Models\Invoice;
$invoices = Invoice::all();
```

## Assets and Frontend

### SASS/SCSS Files
- **Location**: `resources/assets/*/`
- **Imports**: Use relative paths from resources/assets
- **Output**: Automatically goes to `public/assets/*/css/`

Example SASS import:
```scss
// In resources/assets/invoiceplane_sass/style.scss
@import "../core_scss/bootstrap";  // Correct
@import "../../core/scss/bootstrap"; // Wrong - old path
```

### JavaScript Files
- **Dependencies**: Concatenated from node_modules
- **Custom Scripts**: `public/assets/core/js/scripts.js`
- **jQuery UI**: Pre-included, don't modify
- **Build**: Uses Grunt to concatenate and minify

### Adding New JavaScript Dependencies
1. Add to `package.json` devDependencies
2. Update `Gruntfile.js` concat section
3. Run `npm install`
4. Run `npm run build`

## Database and Models

### Current Convention
- **Prefix**: All tables prefixed with `ip_` (e.g., `ip_invoices`)
- **Primary Key**: Usually `invoice_id`, `client_id`, etc.
- **Timestamps**: `invoice_date_created`, `invoice_date_modified`

### Model Structure
```php
class Mdl_invoices extends Response_Model
{
    public $table = 'ip_invoices';
    public $primary_key = 'ip_invoices.invoice_id';
    
    public function default_select()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    }
    
    public function default_order_by()
    {
        $this->db->order_by('ip_invoices.invoice_date_created DESC');
    }
}
```

## Testing

### Current State
- **No automated tests** - This is a problem we're working on
- Manual testing required
- Test in development environment before committing

### When Adding Features
1. Test the happy path
2. Test error conditions
3. Test edge cases (empty data, max values, etc.)
4. Verify database changes
5. Check for XSS/SQL injection vulnerabilities

## Security Best Practices

### Always Validate Input
```php
// WRONG
$invoice_id = $_POST['invoice_id'];
$this->db->where('invoice_id', $invoice_id);

// CORRECT
$invoice_id = $this->input->post('invoice_id');
$this->form_validation->set_rules('invoice_id', 'Invoice ID', 'required|integer');
if ($this->form_validation->run()) {
    $this->db->where('invoice_id', (int)$invoice_id);
}
```

### Escape Output
```php
// In views
<?php echo htmlsc($invoice->invoice_number); ?>
<?php echo html_escape($client->client_name); ?>
```

### SQL Injection Prevention
```php
// Use query builder
$this->db->where('invoice_id', $invoice_id);
$this->db->get('ip_invoices');

// Or use bindings
$this->db->query('SELECT * FROM ip_invoices WHERE invoice_id = ?', [$invoice_id]);
```

## Git Workflow

### Commit Messages
- Use conventional commits: `feat:`, `fix:`, `refactor:`, `docs:`
- Be descriptive but concise
- Reference issue numbers when applicable

### Pull Requests
- Keep PRs focused and small
- Update TODO.md checklist as you progress
- Run linters before committing: `composer check`

## Common Pitfalls to Avoid

### 1. Don't Edit Compiled Assets
❌ Editing `public/assets/invoiceplane/css/style.css`
✅ Edit `resources/assets/invoiceplane_sass/style.scss` and rebuild

### 2. Don't Break Backward Compatibility
❌ Renaming existing controllers without compatibility layer
✅ Add new PSR-4 controllers alongside old ones

### 3. Don't Ignore Code Style
❌ Committing without running Pint
✅ Run `composer pint` before commit

### 4. Don't Hard-Code Paths
❌ `require_once '/var/www/application/config/database.php'`
✅ Use CodeIgniter constants: `APPPATH . 'config/database.php'`

### 5. Don't Skip Input Validation
❌ `$id = $_GET['id']`
✅ `$id = $this->input->get('id'); /* + validation */`

## Local Development Setup

### Using Docker (Recommended for Development)

```bash
# Clone the repository
git clone https://github.com/InvoicePlane/InvoicePlane.git
cd InvoicePlane

# Start Docker containers
docker-compose up --build -d

# Install PHP dependencies
docker-compose exec php composer install

# Install Node dependencies and build assets
docker-compose exec php npm install
docker-compose exec php npm run build
```

Access the application at `http://localhost/index.php/setup`

**Available Services:**
- Application: `http://localhost` (nginx on port 80)
- phpMyAdmin: `http://localhost:8081` (database admin)
- MariaDB: `localhost:3306` (user/password: `ipdevdb`)

**Container Names:**
- `invoiceplane-php` - PHP 8.1 FPM
- `invoiceplane-nginx` - nginx web server
- `invoiceplane-db` - MariaDB 10.9 database
- `invoiceplane-dbadmin` - phpMyAdmin

### Traditional Setup (Without Docker)

**Prerequisites:**
- PHP 8.1 or higher
- MariaDB or MySQL
- Composer
- Node.js and npm

**Setup Steps:**
```bash
# Clone and enter directory
git clone https://github.com/InvoicePlane/InvoicePlane.git
cd InvoicePlane

# Install dependencies
composer install
npm install

# Build assets
npm run build

# Configure the application
cp ipconfig.php.example ipconfig.php
# Edit ipconfig.php and set your base URL

# Set up web server to point to public/ directory
# Visit http://your-domain.com/index.php/setup
```

For detailed installation instructions, see [INSTALLATION.md](../INSTALLATION.md).

## Debugging

### PHP Debugging
- **Whoops** is included in dev dependencies for better error pages
- Set `ENVIRONMENT` to `development` in `index.php` for detailed errors
- Use `var_dump()` or Symfony VarDumper: `dump($variable)`
- Check logs in `application/logs/`

### Frontend Debugging
- Use browser DevTools console
- Check `public/assets/core/js/scripts.js` for custom JavaScript
- Source maps available with `npm run dev-build`

### Common Issues
- **White screen**: Check PHP error logs, ensure all dependencies installed
- **Assets not loading**: Run `npm run build` and clear browser cache
- **Database errors**: Verify database credentials in `ipconfig.php`
- **Permission errors**: Ensure `uploads/` and `application/logs/` are writable

## Performance Considerations

### Database
- Index frequently queried columns
- Use `SQL_CALC_FOUND_ROWS` sparingly (already used in models)
- Consider pagination for large datasets
- Avoid N+1 queries in controllers

### Assets
- Always minify for production: `npm run build`
- Use `npm run dev` only in development
- Don't commit compiled assets to Git (already in `.gitignore`)
- Leverage browser caching (configured in web server)

### PHP
- Enable OPcache in production
- Use appropriate CodeIgniter caching where beneficial
- Avoid loading unnecessary libraries in controllers

## When in Doubt

1. Check existing code for patterns
2. Refer to TODO.md for migration guidelines
3. Consult CodeIgniter 3 documentation
4. Ask for clarification rather than guessing
5. Maintain backward compatibility unless explicitly told otherwise
6. Review INSTALLATION.md for setup questions
7. Check CONTRIBUTING.md for workflow guidance

## Helpful Commands

```bash
# Installation & Dependencies
composer install                    # Install PHP dependencies
npm install                        # Install Node.js dependencies
yarn install                       # Alternative to npm install

# Asset Building
npm run build                      # Production build (minified, no source maps)
npm run dev-build                  # Development build (expanded, with source maps)
npm run dev                        # Watch mode (auto-rebuild on changes)
grunt build                        # Alternative build command
grunt watch                        # Alternative watch command

# Code Quality & Linting
composer check                     # Run all checks (rector, phpcs, pint)
composer pint                      # Fix PHP code style with Laravel Pint
composer phpcs                     # Check and fix code style with PHP_CodeSniffer
composer rector                    # Run automated refactoring with Rector

# Docker Commands
docker-compose up -d               # Start containers in background
docker-compose down                # Stop containers
docker-compose logs -f php         # View PHP container logs
docker-compose logs -f nginx       # View nginx logs
docker-compose exec php bash       # Access PHP container shell
docker-compose exec php composer install  # Run composer in container
docker-compose exec php npm install        # Run npm in container
docker-compose restart             # Restart all containers
docker-compose ps                  # List running containers

# Database
# (Run these inside your database container or locally)
mysql -u root -p                   # Access MariaDB/MySQL
mysqldump -u root -p database > backup.sql  # Backup database

# Git Workflow
git status                         # Check working tree status
git diff                          # Show changes
git add .                         # Stage all changes
git commit -m "type: description" # Commit with conventional commit message
git push                          # Push to remote
git pull origin development       # Pull latest development branch

# Useful Development Commands
tail -f application/logs/log-*.php  # Watch application logs
find . -name "*.php" -type f       # Find all PHP files
grep -r "search_term" application/ # Search in application directory
```

## Project File Structure Overview

```
ivpl-experiment/
├── .github/
│   ├── copilot-instructions.md    # This file - AI coding assistant guide
│   ├── workflows/                 # GitHub Actions CI/CD
│   └── ISSUE_TEMPLATE/
├── application/
│   ├── modules/                   # HMVC modules (controllers, models, views)
│   │   ├── invoices/
│   │   ├── clients/
│   │   └── [30+ modules]
│   ├── core/                      # Extended core classes
│   ├── helpers/                   # Helper functions
│   ├── libraries/                 # Custom libraries
│   ├── third_party/              # MX (HMVC) and other libraries
│   │   └── MX/                   # Modular Extensions - DO NOT MODIFY
│   ├── views/                    # Global views
│   └── config/                   # Configuration files
├── public/                        # Web root (NEW structure)
│   ├── index.php                 # Application entry point
│   └── assets/                   # BUILT assets (don't edit directly)
│       ├── core/
│       ├── invoiceplane/
│       └── invoiceplane_blue/
├── resources/
│   └── assets/                   # SOURCE assets (edit these)
│       ├── core_scss/
│       ├── invoiceplane_sass/
│       └── invoiceplane_blue_sass/
├── uploads/                       # User uploaded files
├── storage/                       # Application storage
├── vendor/                        # Composer dependencies (ignored)
├── node_modules/                  # NPM dependencies (ignored)
├── composer.json                  # PHP dependencies
├── package.json                   # Node.js dependencies
├── Gruntfile.js                  # Asset build configuration
├── TODO.md                       # PSR-4 migration roadmap
├── CONTRIBUTING.md               # Contribution guidelines
├── INSTALLATION.md               # Installation instructions
└── README.md                     # Project overview
```

## References

### Official Documentation
- **CodeIgniter 3 Docs**: https://codeigniter.com/userguide3/
- **InvoicePlane Wiki**: https://wiki.invoiceplane.com/
- **Community Forums**: https://community.invoiceplane.com/
- **Discord**: https://discord.gg/PPzD2hTrXt

### PHP Standards
- **PSR-12 Style Guide**: https://www.php-fig.org/psr/psr-12/
- **PSR-4 Autoloading**: https://www.php-fig.org/psr/psr-4/

### Project Documentation
- **TODO.md**: PSR-4 migration strategy and checklist
- **CONTRIBUTING.md**: How to contribute to the project
- **INSTALLATION.md**: Detailed installation instructions
- **MODERNIZATION_SUMMARY.md**: Summary of modernization changes

### Tools & Libraries
- **Laravel Pint**: https://laravel.com/docs/pint
- **PHP_CodeSniffer**: https://github.com/squizlabs/PHP_CodeSniffer
- **Rector**: https://github.com/rectorphp/rector
- **Grunt**: https://gruntjs.com/
- **SASS**: https://sass-lang.com/

## ⚠️ CRITICAL: File Integrity During Refactoring

### Preventing Empty Files During Migration

**NEVER create empty files during refactoring or migration operations!**

When renaming, moving, or restructuring files:

1. **ALWAYS verify file contents** after any move/copy operation
2. **Check file sizes** - any 0-byte PHP file is a critical error
3. **Use proper git operations** for file renames:
   ```bash
   git mv old_path new_path  # Preserves file content
   ```
4. **After bulk operations**, run:
   ```bash
   find application/Modules -name "*.php" -type f -size 0
   ```
   If this returns ANY files, **STOP immediately** and restore them!

5. **Before committing**, verify no empty files:
   ```bash
   git diff --stat | grep "0 insertions"  # Should not show PHP files
   ```

### Common Causes of Empty Files

- Using `touch` or `>` redirect to create placeholder files
- Moving files with incorrect paths
- Using `mv` instead of `git mv` in combination with git operations
- Script errors during bulk file operations

### Recovery Process

If empty files are discovered:

1. Find the last good commit: `git log --all --full-history -- path/to/file`
2. Restore from git: `git show <commit>:path/to/file > path/to/file`
3. Verify restoration: `wc -l path/to/file` (should show line count)
4. Update any namespaces/class names if the file was renamed

### Validation Checklist

Before pushing any refactoring commits:

- [ ] No empty PHP files in codebase
- [ ] All moved files have original content
- [ ] Namespaces updated in moved files
- [ ] Class names updated to match filenames
- [ ] Syntax check passes: `find . -name "*.php" -exec php -l {} \;`

