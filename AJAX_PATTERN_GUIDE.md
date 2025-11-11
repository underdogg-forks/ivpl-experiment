# AJAX Call Pattern Guide

This guide explains the standardized pattern for making AJAX calls in InvoicePlane.

## Overview

InvoicePlane now provides two utility functions for consistent AJAX handling:

1. **`showErrors()`** - Displays validation errors consistently
2. **`ajaxPost()`** - Wrapper for jQuery POST with standardized error handling

## The New Pattern

### Basic Usage

```javascript
ajaxPost('/payments/ajax/add', {
    invoice_id: $('#invoice_id').val(),
    payment_amount: $('#payment_amount').val(),
    payment_method_id: $('#payment_method_id').val(),
    payment_date: $('#payment_date').val(),
    payment_note: $('#payment_note').val()
}).done(function(response) {
    // Success! Response has already been validated and parsed
    window.location = '/invoices/view/' + response.invoice_id;
}).fail(function(errors) {
    // Errors are automatically displayed to the user
    console.log('Request failed:', errors);
});
```

### Advanced Usage with Options

```javascript
ajaxPost('/payments/ajax/add', {
    invoice_id: $('#invoice_id').val(),
    amount: $('#payment_amount').val()
}, {
    errorTarget: '#modal-status-placeholder',  // Where to show error summary
    beforeSend: function() {
        $('#btn_submit').prop('disabled', true);
    },
    always: function() {
        $('#btn_submit').prop('disabled', false);
    }
}).done(function(response) {
    window.location = '/invoices/view/' + response.invoice_id;
}).fail(function(errors) {
    // Handle specific failure case if needed
});
```

### With Button State Management

```javascript
var $btn = $('#btn_modal_payment_submit');
$btn.button('loading');  // If using Bootstrap button plugin

ajaxPost('/payments/ajax/add', {
    invoice_id: $('#invoice_id').val(),
    payment_amount: $('#payment_amount').val()
}, {
    always: function() {
        $btn.button('reset');
    }
}).done(function(response) {
    window.location = '/invoices/view/' + response.invoice_id;
});
```

## Function Reference

### `ajaxPost(url, data, options)`

Standardized AJAX POST wrapper with consistent error handling.

**Parameters:**
- `url` (string) - The URL to post to
- `data` (object) - Data to send in the POST request
- `options` (object, optional) - Configuration options:
  - `errorTarget` (string) - CSS selector for error display container
  - `beforeSend` (function) - Callback before request is sent
  - `always` (function) - Callback that runs after success or failure

**Returns:** jQuery Promise with `.done()` and `.fail()` methods

**Features:**
- Automatically parses JSON responses
- Validates `success` flag in response
- Automatically displays validation errors
- Handles both successful validation and error responses
- CSRF tokens are automatically added (via existing `$.ajaxPrefilter`)

### `showErrors(errors, targetSelector, clearPrevious)`

Display validation errors in a consistent way.

**Parameters:**
- `errors` (object) - Validation errors object (key: field_name, value: error_message)
- `targetSelector` (string, optional) - CSS selector for the container to display errors
- `clearPrevious` (boolean, optional) - Whether to clear previous error states (default: true)

**Features:**
- Highlights form fields with errors
- Optionally displays error summary in a target container
- Adds error classes to form groups
- Can display inline error messages below fields

**Example:**
```javascript
showErrors({
    'payment_amount': 'Amount is required',
    'payment_date': 'Invalid date format'
}, '#modal-status-placeholder');
```

## Backend Response Format

Your AJAX controllers should return JSON in this format:

### Success Response
```php
echo json_encode([
    'success' => 1,
    'payment_id' => $payment_id,
    // ... other data
]);
```

### Error Response
```php
echo json_encode([
    'success' => 0,
    'validation_errors' => [
        'payment_amount' => 'Amount is required',
        'payment_date' => 'Date is invalid'
    ]
]);
```

**Note:** The key can be either `validation_errors` or `errors` - both are supported.

## Migration from Old Pattern

### Old Pattern (Still Supported)
```javascript
$.post("<?php echo site_url('payments/ajax/add'); ?>", {
    invoice_id: $('#invoice_id').val(),
    payment_amount: $('#payment_amount').val()
}, function (data) {
    var response = json_parse(data);
    if (response.success === 1) {
        window.location = "<?php echo site_url('invoices/view'); ?>/" + response.invoice_id;
    } else {
        $('.control-group').removeClass('has-error');
        for (var key in response.validation_errors) {
            if(response.validation_errors.hasOwnProperty(key)) {
                $('#' + key).parent().parent().addClass('has-error');
            }
        }
    }
});
```

### New Pattern (Recommended)
```javascript
ajaxPost("<?php echo site_url('payments/ajax/add'); ?>", {
    invoice_id: $('#invoice_id').val(),
    payment_amount: $('#payment_amount').val()
}).done(function(response) {
    window.location = "<?php echo site_url('invoices/view'); ?>/" + response.invoice_id;
}).fail(function(errors) {
    // Errors are automatically handled - no need for manual field highlighting
});
```

## Benefits

1. **Less Boilerplate** - No need to manually parse JSON or handle success/error logic
2. **Consistent Error Display** - All forms show errors in the same way
3. **Cleaner Code** - Separation of success and failure handlers
4. **Better UX** - Consistent error display improves user experience
5. **Easier Maintenance** - Centralized error handling logic
6. **Modern Promise-based API** - Familiar to developers using modern frameworks

## Backward Compatibility

The old pattern using `$.post()` and `json_parse()` is still fully supported. You can migrate to the new pattern gradually as you work on different modules.

## Examples in the Codebase

Look for these examples:
- `application/Modules/Payments/views/modal_add_payment.php` (can be migrated)
- `application/Modules/Invoices/views/modal_create_invoice.php` (can be migrated)
- `application/Modules/Quotes/views/modal_create_quote.php` (can be migrated)

## Testing Your Changes

After implementing the new pattern:

1. Test successful form submission
2. Test validation errors (submit with empty required fields)
3. Test server errors (temporarily break the backend endpoint)
4. Check that error messages are displayed correctly
5. Verify field highlighting works as expected
