# GitHub Copilot Instructions for InvoicePlane Modernization

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

## When in Doubt

1. Check existing code for patterns
2. Refer to TODO.md for migration guidelines
3. Consult CodeIgniter 3 documentation
4. Ask for clarification rather than guessing
5. Maintain backward compatibility unless explicitly told otherwise

## Helpful Commands

```bash
# Install dependencies
npm install
composer install

# Build assets
npm run build        # Production
npm run dev-build    # Development
npm run dev          # Watch mode

# Code quality
composer check       # Run all checks
composer pint        # Fix code style
composer phpcs       # Check code style
composer rector      # Automated refactoring

# Git
git status
git add .
git commit -m "type: description"
git push
```

## References

- CodeIgniter 3 Docs: https://codeigniter.com/userguide3/
- PSR-12 Style Guide: https://www.php-fig.org/psr/psr-12/
- PSR-4 Autoloading: https://www.php-fig.org/psr/psr-4/
- TODO.md: PSR-4 migration strategy and checklist
