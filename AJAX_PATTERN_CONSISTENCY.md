# AJAX Pattern Consistency Guide

## Standard Pattern

All `ajaxPost()` calls now follow a consistent pattern throughout the application:

### Basic Pattern (No loader needed)
```javascript
ajaxPost(url, data)
    .done(function(response) {
        // Handle success
    })
    .fail(function(errors) {
        // Errors are automatically displayed by ajaxPost
    });
```

### Pattern with Loader
```javascript
ajaxPost(url, data, {
    beforeSend: function() {
        show_loader();
    },
    always: function() {
        close_loader();
    }
})
    .done(function(response) {
        // Handle success
    })
    .fail(function(errors) {
        // Errors are automatically displayed by ajaxPost
    });
```

### Pattern with Error Target
```javascript
ajaxPost(url, data, {
    errorTarget: '#form-id'
})
    .done(function(response) {
        // Handle success
    })
    .fail(function(errors) {
        // Errors are automatically displayed by ajaxPost
    });
```

## Consistency Rules

1. **All calls have `.done()` and `.fail()`**: Every `ajaxPost()` call includes both success and failure handlers
2. **`beforeSend` paired with `always`**: When showing a loader, always close it in the `always` callback
3. **Consistent error comments**: Failure handlers include the standard comment explaining automatic error handling
4. **Options object placement**: When using options, they come between data and promise handlers

## Files Updated for Consistency

### Added `.fail()` handlers to:
- `Filter/views/jquery_filter.php`
- `Settings/views/partial_settings_general.php`
- `Mailer/views/invoice.php`
- `Mailer/views/quote.php`
- `Products/views/modal_product_lookups.php`
- `Tasks/views/modal_task_lookups.php`
- `Invoices/views/modal_create_recurring.php` (get_recur_start_date function)

### Added `always` callback to:
- `Quotes/views/modal_add_quote_tax.php`
- `Invoices/views/modal_add_invoice_tax.php`
- `Layout/views/ajax/modal_change_user_client.php`

## Benefits of Consistency

1. **Easier to maintain**: Developers know what to expect in every file
2. **Better error handling**: All failures are caught and displayed
3. **Predictable loader behavior**: Loaders always close, even on error
4. **Code review**: Easier to spot deviations from the pattern
5. **Documentation**: Standard pattern serves as living documentation

## Pattern Verification

```bash
# Verify all ajaxPost calls have .fail()
grep -r "ajaxPost(" --include="*.php" application/Modules -l | \
    while read f; do grep -q "\.fail(function" "$f" || echo "Missing .fail(): $f"; done

# Verify beforeSend has always
grep -r "beforeSend" --include="*.php" application/Modules -l | \
    while read f; do grep -q "always:" "$f" || echo "Missing always: $f"; done
```

All checks should return empty results (all files comply).
