# InvoicePlane Modernization Guidelines

## Project Mission
Transform InvoicePlane from an ancient CodeIgniter 3 codebase into a modern, maintainable PHP application while preserving backward compatibility and functionality.

## Architecture Principles

### 1. Progressive Enhancement
- **Add, don't replace**: Create new PSR-4 structures alongside legacy code
- **Dual support**: Both old and new naming conventions should work simultaneously
- **Gradual migration**: Migrate one module at a time, not all at once
- **Test continuously**: Ensure nothing breaks during transition

### 2. Separation of Concerns
- **Source vs. Built**: Source files in `resources/`, built files in `public/`
- **Public directory**: All web-accessible files in `public/` for security
- **Asset compilation**: SASS/JS source separate from compiled output
- **Configuration**: Keep config outside web root (already done with ipconfig.php)

### 3. Modern PHP Standards
- **PSR-4 Autoloading**: Namespaced classes with proper directory structure
- **PSR-12 Code Style**: Consistent formatting across codebase
- **Type Declarations**: Use type hints and return types
- **Strict Types**: Consider `declare(strict_types=1)` for new files

## Naming Conventions

### Current (Legacy) - Still Supported
```
Controllers: Invoices (class Invoices extends Admin_Controller)
Models: Mdl_invoices (class Mdl_invoices extends Response_Model)
Files: Invoices.php, Mdl_invoices.php
Location: application/modules/{module}/controllers/, models/
Namespace: None
```

### Active (PSR-4) - Preferred ✅
```
Controllers: InvoicesController (class InvoicesController extends Admin_Controller)
Models: Invoice (class Invoice extends Response_Model)  
Files: InvoicesController.php, Invoice.php
Location: application/modules/{module}/Controllers/, Models/
Namespace: App\Modules\{Module}\{Controllers|Models}
Examples:
  - App\Modules\Invoices\Controllers\InvoicesController
  - App\Modules\Invoices\Models\Invoice
```

### Transition Strategy
1. **Phase 1**: ✅ Add Composer PSR-4 autoloader
2. **Phase 2**: ✅ Modify MX to detect and load PSR-4 controllers
3. **Phase 3**: ✅ Create new controllers with PSR-4 naming in parallel
4. **Phase 4**: Gradually migrate existing controllers
5. **Phase 5**: Deprecate and remove legacy controllers

### Routing Behavior
- **PSR-4 Controllers**: `invoices/form` → `App\Modules\Invoices\Controllers\InvoicesController::form()`
- **Legacy Controllers**: `invoices/index` → `Invoices::index()` (backward compatible)
- MX Router checks PSR-4 first, then falls back to legacy
- URL structure remains unchanged: `invoices/form` works for both

## Directory Structure

### Root Level
```
/
├── public/                 # Web root (NEW)
│   ├── index.php          # Entry point (moved from root)
│   └── assets/            # Compiled assets (CSS, JS, fonts)
├── resources/             # Source files (NEW)
│   └── assets/            # SASS/SCSS source files
├── application/           # CodeIgniter application
│   ├── modules/           # HMVC modules
│   ├── third_party/       # MX and other libraries
│   ├── core/              # Extended CI core classes
│   └── config/            # Configuration files
├── vendor/                # Composer dependencies
├── uploads/               # User uploaded files
├── index.php              # OLD entry point (deprecated)
├── assets/                # OLD assets directory (deprecated)
├── composer.json          # PHP dependencies
├── package.json           # Node dependencies
└── Gruntfile.js           # Asset build configuration
```

## Modernization Roadmap

### ✅ Phase 1: Infrastructure (COMPLETE)
- [x] Move index.php to public/
- [x] Create resources/assets/ structure
- [x] Update Grunt to compile to public/assets/
- [x] Update index.php paths
- [x] Create TODO.md
- [x] Create developer guidelines
- [x] Create Copilot instructions

### Phase 2: Autoloading (NEXT)
- [ ] Add PSR-4 autoload to composer.json
- [ ] Run composer dump-autoload
- [ ] Modify MX to support PSR-4
- [ ] Test dual loading (legacy + PSR-4)

### Phase 3: Pilot Migration
- [ ] Choose simple module (dashboard)
- [ ] Create PSR-4 structure
- [ ] Migrate controller
- [ ] Migrate model
- [ ] Test thoroughly
- [ ] Document process

For complete details, see:
- **TODO.md**: Detailed PSR-4 migration plan
- **.github/copilot-instructions.md**: Code patterns and conventions
