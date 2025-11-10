# Model Loading Refactoring - Remove mdl_ Prefix

## Summary

Removed the `mdl_` prefix from all model loading calls across the codebase to align with the PSR-4 model naming conventions implemented in previous commits.

## Background

After the model singularization refactoring (commit cdb0fa2), model class names were updated from:
- `Mdl_custom_values` → `CustomValue`
- `Mdl_items` → `Item`
- `Mdl_settings` → `Setting`

However, many model loading calls still used the old `mdl_` prefix pattern:
```php
$this->load->model('invoices/mdl_items');
$this->load->model('custom_values/mdl_custom_values', 'cv');
```

## Changes Made

### Files Updated: 34 files
- **6 helper files** (application/helpers/)
- **3 core classes** (application/Core/)
- **2 library files** (application/Libraries/ and application/libraries/)
- **23 model files** (application/Modules/*/Models/)

### Total References Updated: 60+

## Mapping Examples

| Old Reference | New Reference |
|--------------|---------------|
| `invoices/mdl_items` | `invoices/item` |
| `invoices/mdl_item` | `invoices/item` |
| `custom_values/mdl_custom_values` | `custom_values/custom_value` |
| `custom_values/mdl_custom_value` | `custom_values/custom_value` |
| `custom_fields/mdl_custom_fields` | `custom_fields/custom_field` |
| `custom_fields/mdl_custom_field` | `custom_fields/custom_field` |
| `settings/mdl_setting` | `settings/setting` |
| `settings/mdl_settings` | `settings/setting` |
| `invoices/mdl_invoices` | `invoices/invoice` |
| `invoices/mdl_invoice` | `invoices/invoice` |
| `users/mdl_users` | `users/user` |
| `clients/mdl_clients` | `clients/client` |
| `quotes/mdl_quote_items` | `quotes/quote_item` |
| `quotes/mdl_quote_amount` | `quotes/quote_amount` |
| `invoice_groups/mdl_invoice_group` | `invoice_groups/invoice_group` |
| `payment_methods/mdl_payment_methods` | `payment_methods/payment_method` |

## Detailed File Changes

### Helper Files
1. `application/helpers/custom_values_helper.php`
   - `custom_values/mdl_custom_values` → `custom_values/custom_value`

2. `application/helpers/dropzone_helper.php`
   - `upload/mdl_uploads` → `upload/upload`

3. `application/helpers/user_helper.php`
   - `users/mdl_users` → `users/user`

4. `application/helpers/client_helper.php`
   - `clients/mdl_clients` → `clients/client`

5. `application/helpers/pdf_helper.php`
   - `invoices/mdl_items` → `invoices/item`

6. `application/helpers/template_helper.php`
   - `custom_fields/mdl_custom_fields` → `custom_fields/custom_field`
   - `custom_values/mdl_custom_values` → `custom_values/custom_value`
   - `invoices/mdl_invoices` → `invoices/invoice`

### Core Classes
1. `application/Core/BaseController.php`
   - `settings/mdl_setting` → `settings/setting`

2. `application/Core/GuestController.php`
   - `user_clients/mdl_user_client` → `user_clients/user_client`

3. `application/Core/Validator.php`
   - `custom_values/mdl_custom_value` → `custom_values/custom_value`
   - `custom_values/mdl_custom_fields` → `custom_values/custom_field`
   - `custom_fields/mdl_custom_field` → `custom_fields/custom_field`

### Library Files
1. `application/Libraries/Sumex.php`
   - `payment_methods/mdl_payment_methods` → `payment_methods/payment_method`

2. `application/libraries/Sumex.php`
   - `payment_methods/mdl_payment_methods` → `payment_methods/payment_method`

### Model Files (23 files)
Updated model-to-model loading calls in:
- Quotes module models (5 files)
- Invoices module models (5 files)
- Projects, UserClients, Reports, Settings, Upload, Clients, CustomFields, CustomValues, Payments, Users, Tasks, Setup modules

## Implementation Method

Used a Python script with regex patterns to systematically replace all `mdl_` prefixed model loads:

```python
# Pattern matching and replacement
content = re.sub(
    rf"(load->model\(['\"]([^/]+)/{old_name}['\"])",
    rf"load->model('\2/{new_name}'",
    content
)
```

## Validation

### Syntax Check
```bash
find application -name "*.php" -exec php -l {} \;
# Result: 0 syntax errors
```

### Verification
```bash
grep -r "load->model.*mdl_" application/
# Result: 0 matches (all mdl_ prefixes removed)
```

## Benefits

1. **Consistency**: Model loading now matches the actual class names
2. **Clarity**: No confusion between `mdl_` prefix and actual class structure
3. **Modern Standards**: Aligns with PSR-4 and modern PHP conventions
4. **Maintainability**: Single naming convention throughout the codebase

## Backward Compatibility

✅ **Maintained** - CodeIgniter's model loader handles both patterns:
- `$this->load->model('module/model')` loads the `Model` class
- Model class names: `Item`, `CustomValue`, `Setting`, etc. (without `Mdl_` prefix)
- No breaking changes to functionality

## Related Changes

This change completes the model refactoring work:
1. **Commit f33487c**: Added legacy PHPDoc annotations
2. **Commit cdb0fa2**: Refactored models to singular form
3. **Commit 178e7ef**: Moved MY_Model back to core
4. **Commit db72e8a**: ✅ Removed mdl_ prefix from model loads

## Commit Information

**Commit**: db72e8a
**Message**: Remove mdl_ prefix from all model loading calls
**Date**: 2025-11-10
**Files Changed**: 34
**Insertions**: 60
**Deletions**: 60

## Status

✅ **COMPLETE** - All model loading calls now use clean, singular model names without the `mdl_` prefix.
