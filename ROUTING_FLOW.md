# PSR-4 Routing Flow Diagram

## URL: `invoices/form` → `InvoicesController::form()`

```
┌─────────────────────────────────────────────────────────────────────┐
│ 1. User Request: http://example.com/invoices/form                  │
└─────────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│ 2. MX_Router::locate(['invoices', 'form'])                         │
│    - segments[0] = 'invoices' (module)                             │
│    - segments[1] = 'form' (method)                                 │
└─────────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│ 3. Router checks PSR-4 directory FIRST ✅                           │
│    Path: application/modules/invoices/Controllers/                 │
│    File: InvoicesController.php                                    │
│    Status: FOUND                                                    │
└─────────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│ 4. Modules::load('invoices')                                       │
│    - module_name = 'invoices'                                      │
│    - class = 'invoices' (from segment)                             │
└─────────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│ 5. Try PSR-4 class FIRST ✅                                         │
│    Namespace: App\Modules\Invoices\Controllers\                    │
│    Class: InvoicesController                                       │
│    Full name: App\Modules\Invoices\Controllers\InvoicesController  │
│    class_exists(): TRUE (via Composer autoloader)                  │
└─────────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│ 6. Instantiate PSR-4 Controller                                    │
│    $controller = new InvoicesController($params)                   │
│    Registered in: self::$registry['invoices']                      │
└─────────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│ 7. Call method                                                      │
│    InvoicesController::form()                                      │
│    Result: Invoice form rendered ✅                                │
└─────────────────────────────────────────────────────────────────────┘
```

## Fallback Flow (if PSR-4 not found)

```
┌─────────────────────────────────────────────────────────────────────┐
│ 5. Try PSR-4 class                                                  │
│    class_exists(): FALSE                                            │
└─────────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│ 6. Fallback to Legacy Loading                                      │
│    Path: application/modules/invoices/controllers/                 │
│    File: Invoices.php                                              │
│    Class: Invoices (no namespace)                                  │
│    Load via: include_once                                          │
│    Instantiate: new Invoices($params)                              │
└─────────────────────────────────────────────────────────────────────┘
```

## Key Features

### ✅ PSR-4 Priority
- PSR-4 controllers checked **first**
- Faster resolution for new code
- Modern namespace support

### ✅ Backward Compatibility
- Legacy controllers still work
- No URL changes required
- Gradual migration possible

### ✅ Dual Directory Support
```
application/modules/invoices/
├── Controllers/              ← PSR-4 (checked first)
│   └── InvoicesController.php
├── controllers/              ← Legacy (fallback)
│   └── Invoices.php
├── Models/                   ← PSR-4 (checked first)
│   └── Invoice.php
└── models/                   ← Legacy (fallback)
    └── Mdl_invoices.php
```

### ✅ URL Mapping

| URL                 | PSR-4 Controller                              | Method    |
|---------------------|-----------------------------------------------|-----------|
| `invoices/form`     | `App\Modules\Invoices\Controllers\InvoicesController` | `form()`  |
| `invoices/view/123` | `App\Modules\Invoices\Controllers\InvoicesController` | `view(123)` |
| `invoices/status/draft` | `App\Modules\Invoices\Controllers\InvoicesController` | `status('draft')` |

**NOT** `invoices-controller/form` - The URL stays clean and RESTful!

## Code Quality

### SOLID Principles ✅
- **S**ingle Responsibility: Each controller handles one module
- **O**pen/Closed: Extended MX without modifying core logic
- **L**iskov Substitution: PSR-4 controllers extend same base classes
- **I**nterface Segregation: Controllers implement specific interfaces
- **D**ependency Inversion: Depend on abstractions (Admin_Controller)

### DRY (Don't Repeat Yourself) ✅
- MX checks PSR-4 once, falls back cleanly
- Reusable loading logic in Modules::load()
- Common routing in Router::locate()

### Early Returns ✅
```php
if (empty($class)) {
    return;  // Early return on error
}

if (class_exists($psr4_class)) {
    $controller = $psr4_class;
    // Use PSR-4
} else {
    // Fallback to legacy
}
```
