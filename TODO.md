# PSR-4 Migration TODO List

## Current State Analysis

### Module Structure
The application uses CodeIgniter 3's HMVC (Hierarchical Model-View-Controller) pattern via the Modular Extensions (MX) library located in `application/third_party/MX/`.

Current module structure:
```
application/modules/
├── invoices/
│   ├── controllers/
│   │   ├── Invoices.php        (class Invoices extends Admin_Controller)
│   │   ├── Ajax.php             (class Ajax extends Admin_Controller)
│   │   ├── Cron.php
│   │   └── Recurring.php
│   ├── models/
│   │   └── Mdl_invoices.php
│   └── views/
├── clients/
├── dashboard/
└── [28+ other modules]
```

### Current Naming Convention
- **Controllers**: Named after the module (e.g., `Invoices`, `Clients`, `Dashboard`)
- **Models**: Prefixed with `Mdl_` (e.g., `Mdl_invoices`, `Mdl_clients`)
- **Files**: PascalCase class names with `.php` extension
- **No namespaces**: Pure PHP class names without namespace declarations

### MX (Modular Extensions) Analysis

#### How MX Currently Works
1. **Autoloading** (`MX/Modules.php` line 19): `spl_autoload_register('Modules::autoload')`
2. **Controller Loading** (`MX/Modules.php` lines 100-134):
   - Locates controller via `CI::$APP->router->locate()`
   - Adds controller suffix: `$class .= CI::$APP->config->item('controller_suffix')`
   - Uses `ucfirst()` for class name: `$controller = ucfirst($class)`
   - Instantiates: `new $controller($params)`

3. **File Loading** (`MX/Modules.php` lines 137-164):
   - Constructs path: `$path . $file . EXT`
   - Uses `include_once` for loading
   - No namespace support

#### Key MX Files to Modify
- `application/third_party/MX/Modules.php` - Core module loading logic
- `application/third_party/MX/Router.php` - URL routing to controllers
- `application/third_party/MX/Loader.php` - Resource loading
- `application/third_party/MX/Controller.php` - Base controller class

## PSR-4 Migration Strategy

### Option 1: Gradual Migration with Dual Support (RECOMMENDED) ✅ IN PROGRESS
Keep both old and new naming conventions working simultaneously.

#### Step 1: Add Namespace Support to MX ✅ COMPLETED
- [x] Modify `MX/Modules.php::load()` to check for namespaced classes
- [x] Add PSR-4 autoloader registration via Composer
- [x] Create namespace mapping: `App\Modules\Invoices\Controllers\InvoicesController`

#### Step 2: Create PSR-4 Compatible Structure (Parallel) ✅ COMPLETED
```
application/modules/
├── invoices/
│   ├── Controllers/           ✅ (NEW - PSR-4)
│   │   └── InvoicesController.php  (namespace App\Modules\Invoices\Controllers)
│   ├── controllers/           (OLD - keep for BC)
│   │   └── Invoices.php
│   ├── Models/                ✅ (NEW - PSR-4)
│   │   └── Invoice.php        (namespace App\Modules\Invoices\Models)
│   ├── models/                (OLD - keep for BC)
│   │   └── Mdl_invoices.php
│   └── views/
```

#### Step 3: Update MX Router to Support Both Conventions ✅ COMPLETED
Modify `MX/Router.php::locate()` to:
- [x] First try PSR-4 pattern: `Controllers/{Module}Controller`
- [x] Fall back to legacy pattern: `controllers/{module}` class
- [x] Checks PSR-4 Controllers/ directory before legacy controllers/

#### Step 4: Implement Controller Suffix Flexibility ✅ COMPLETED
- [x] Modified controller loading to support both `Invoices` and `InvoicesController`
- [x] PSR-4 classes use `Controller` suffix automatically
- [x] Router tries PSR-4 naming first, then legacy

#### Step 5: Update Model Loading ✅ COMPLETED
- [x] Modified `MX/Loader.php::model()` to support PSR-4 models
- [x] Checks for namespaced models first: `App\Modules\{Module}\Models\{Model}`
- [x] Falls back to legacy `Mdl_` prefix models

### Option 2: Complete Migration (Higher Risk)
Migrate everything at once with breaking changes.

#### Steps:
- [ ] Create new PSR-4 directory structure
- [ ] Move and rename all controllers (Invoices → InvoicesController)
- [ ] Move and rename all models (Mdl_invoices → Invoice)
- [ ] Add namespaces to all files
- [ ] Update all internal references
- [ ] Update routing configuration
- [ ] Rewrite MX to only support PSR-4

**Risk**: High chance of breaking existing functionality

### Option 3: Composer Autoloader with ClassMap (EASIEST)
Use Composer's classmap autoloading as intermediate step.

#### Steps:
- [ ] Add to `composer.json`:
```json
{
  "autoload": {
    "psr-4": {
      "App\\Modules\\": "application/modules/"
    },
    "classmap": [
      "application/modules/*/controllers",
      "application/modules/*/models"
    ]
  }
}
```
- [ ] Run `composer dump-autoload`
- [ ] Gradually add namespaces while classmap still loads old files
- [ ] Transition one module at a time

## Required MX Modifications

### 1. Modify `MX/Modules.php::load()` (lines 100-134)
**Current Code:**
```php
$class .= CI::$APP->config->item('controller_suffix');
self::load_file(ucfirst($class), $path);
$controller = ucfirst($class);
self::$registry[$alias] = new $controller($params);
```

**Proposed Change:**
```php
// Try PSR-4 first
$psr4_namespace = "App\\Modules\\" . ucfirst($module) . "\\Controllers\\";
$psr4_class = $psr4_namespace . ucfirst($class) . 'Controller';

if (class_exists($psr4_class)) {
    $controller = $psr4_class;
} else {
    // Fall back to legacy
    $class .= CI::$APP->config->item('controller_suffix');
    self::load_file(ucfirst($class), $path);
    $controller = ucfirst($class);
}

self::$registry[$alias] = new $controller($params);
```

### 2. Modify `MX/Router.php::locate()`
Add support for locating controllers in both:
- `application/modules/{module}/Controllers/{Module}Controller.php` (PSR-4)
- `application/modules/{module}/controllers/{Module}.php` (Legacy)

### 3. Update `composer.json` Autoload Section
```json
{
  "autoload": {
    "psr-4": {
      "App\\": "application/",
      "App\\Modules\\": "application/modules/"
    },
    "files": [
      "application/core/MY_Controller.php"
    ]
  }
}
```

## Migration Checklist

### Phase 1: Preparation
- [ ] Document current module structure and dependencies
- [ ] Create backup branch
- [ ] Set up PSR-4 directory structure alongside existing
- [ ] Configure Composer autoloading

### Phase 2: MX Modifications
- [ ] Add PSR-4 class name resolution to `MX/Modules.php`
- [ ] Add PSR-4 path resolution to `MX/Router.php`
- [ ] Update `MX/Loader.php` to support namespaced models
- [ ] Add configuration flags for PSR-4 vs legacy mode
- [ ] Test both naming conventions work

### Phase 3: Pilot Module Migration
- [ ] Choose simple module for testing (e.g., `dashboard`)
- [ ] Create PSR-4 structure for pilot module
- [ ] Add namespace declarations
- [ ] Rename controller: `Dashboard` → `DashboardController`
- [ ] Rename model: `Mdl_dashboard` → `Dashboard` model
- [ ] Update internal references within module
- [ ] Test thoroughly
- [ ] Document lessons learned

### Phase 4: Gradual Module Migration
- [ ] Create migration script/tool
- [ ] Migrate modules in order of complexity (simple → complex)
- [ ] Test each module thoroughly after migration
- [ ] Update documentation

### Phase 5: Cleanup (Once All Modules Migrated)
- [ ] Remove legacy controller loading code from MX
- [ ] Remove old controller/model files
- [ ] Update configuration to PSR-4 only
- [ ] Remove backward compatibility code

## Implementation Recommendations

### 1. Start with Composer Autoloader (Option 3)
This is the safest approach:
- Add classmap for existing structure
- Add PSR-4 rules for new structure
- Gradually migrate one module at a time
- No need to heavily modify MX initially

### 2. Controller Naming Strategy
**Recommended approach**: Support both simultaneously
```php
// In MX/Modules.php - try both patterns
$psr4_controller = "App\\Modules\\{$module}\\Controllers\\{$module}Controller";
$legacy_controller = ucfirst($module);

if (class_exists($psr4_controller)) {
    return new $psr4_controller();
} elseif (class_exists($legacy_controller)) {
    return new $legacy_controller();
}
```

### 3. Model Naming Strategy
Move from `Mdl_` prefix to proper namespaced models:
- Old: `$this->load->model('mdl_invoices')`
- New: `use App\Modules\Invoices\Models\Invoice`

### 4. View Loading
Keep views in same location, update loader to support both:
- `application/modules/invoices/views/` (stays the same)
- Update `MX/Loader.php` to resolve views regardless of controller namespace

## Critical Considerations

### 1. Backward Compatibility
- Keep old URLs working via router configuration
- Support both `invoices/create` and `invoices-controller/create`
- Maintain API compatibility for external integrations

### 2. Testing Strategy
- Unit tests for each migrated module
- Integration tests for cross-module functionality
- Regression tests for existing features
- Load testing to ensure no performance degradation

### 3. Performance Impact
- Composer autoloader is highly optimized (use `--optimize-autoloader`)
- PSR-4 may be slightly slower than direct includes (negligible)
- Consider APCu or OpCache for production

### 4. Third-Party Dependencies
Check if any CodeIgniter libraries/helpers depend on:
- [ ] Specific controller naming conventions
- [ ] Model naming patterns
- [ ] Direct class instantiation

## Timeline Estimate

**Option 3 (Composer Classmap + Gradual PSR-4)**: ~40-80 hours
- Setup: 4-8 hours
- MX modifications: 8-16 hours
- Pilot module: 4-8 hours
- Per module migration: 1-2 hours × 29 modules = 29-58 hours
- Testing & debugging: 8-16 hours

**Option 1 (Dual Support)**: ~60-120 hours
**Option 2 (Complete Migration)**: ~100-200 hours + high risk

## Success Criteria

- [ ] All modules work with PSR-4 naming
- [ ] No breaking changes to public APIs
- [ ] All tests passing
- [ ] Performance unchanged or improved
- [ ] Code is more maintainable
- [ ] Documentation updated
- [ ] Team trained on new conventions

## Next Steps

1. **Immediate**: Set up Composer autoloader with classmap
2. **Week 1**: Modify MX to detect and load PSR-4 controllers
3. **Week 2**: Migrate pilot module (dashboard) to PSR-4
4. **Week 3+**: Gradual migration of remaining modules

---

## Additional Notes

### Benefits of PSR-4 Migration
- **Better IDE Support**: Namespaces enable better autocomplete
- **Modern Standards**: Aligns with PHP ecosystem
- **Composer Integration**: Easier third-party library integration
- **Clearer Dependencies**: Namespaces show module relationships
- **Easier Testing**: PSR-4 works better with testing frameworks

### Risks
- **Breaking Changes**: Incorrect migration could break functionality
- **Learning Curve**: Team needs to learn namespaced PHP
- **Time Investment**: Significant development time required
- **MX Compatibility**: Heavy MX modifications might be fragile

### Miracle Requirement
The problem statement mentions "besides a miracle" - the harsh reality is:
- **No miracle needed**, but significant engineering effort required
- MX is old and not designed for PSR-4, but it CAN be modified
- The safest path is gradual migration, not big-bang rewrite
- Composer's autoloader is the "miracle" tool that bridges old and new

### Alternative: Migrate Away from MX Entirely
Long-term consideration: Replace MX with modern routing:
- Use CodeIgniter 4's native module system
- Or migrate to Laravel/Symfony (major undertaking)
- Or use League/Route with PSR-7 middleware
