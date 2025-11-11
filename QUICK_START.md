# Quick Start - AJAX Pattern

## 🚀 Get Started in 5 Minutes

### Step 1: Open the Test Page

Simply open `AJAX_PATTERN_TEST.html` in your browser to see the new pattern in action:

```bash
# From the repository root
open AJAX_PATTERN_TEST.html
# or
firefox AJAX_PATTERN_TEST.html
# or
chrome AJAX_PATTERN_TEST.html
```

### Step 2: See the Pattern in Action

The test page demonstrates:
- ✅ How `showErrors()` highlights form fields
- ✅ How `ajaxPost()` handles success and failure
- ✅ Side-by-side comparison of old vs new pattern
- ✅ Benefits of the new approach

### Step 3: Use in Your Code

Copy this basic pattern:

```javascript
$('#btn_submit').click(function () {
    ajaxPost("<?php echo site_url('your/endpoint'); ?>", {
        field1: $('#field1').val(),
        field2: $('#field2').val()
    }).done(function(response) {
        // Success! Response is already validated
        window.location = '/success/page';
    }).fail(function(errors) {
        // Errors are automatically displayed
        console.log('Submission failed');
    });
});
```

### Step 4: Read the Docs

For complete information:
- **AJAX_PATTERN_GUIDE.md** - Full developer guide
- **EXAMPLE_AJAX_PATTERN.php** - Working example
- **IMPLEMENTATION_SUMMARY.md** - Technical details

## 📖 Common Use Cases

### Use Case 1: Simple Form Submission
```javascript
ajaxPost('/invoices/ajax/create', {
    client_id: $('#client_id').val()
}).done(function(response) {
    window.location = '/invoices/view/' + response.invoice_id;
});
```

### Use Case 2: With Error Container
```javascript
ajaxPost('/payments/ajax/add', formData, {
    errorTarget: '#modal-errors'
}).done(function(response) {
    $('#modal').modal('hide');
});
```

### Use Case 3: With Button State
```javascript
var $btn = $('#btn_submit');

ajaxPost('/quotes/ajax/save', data, {
    beforeSend: function() {
        $btn.prop('disabled', true);
    },
    always: function() {
        $btn.prop('disabled', false);
    }
}).done(function(response) {
    alert('Saved!');
});
```

## 🎯 Benefits at a Glance

| Feature | Old Pattern | New Pattern |
|---------|-------------|-------------|
| Lines of code | ~40 lines | ~8 lines |
| JSON parsing | Manual | Automatic |
| Success validation | Manual | Automatic |
| Error display | Manual loop | Automatic |
| Promise-based | ❌ No | ✅ Yes |
| Consistent errors | ❌ No | ✅ Yes |

## �� Backend Requirements

Your backend just needs to return:

**Success:**
```php
echo json_encode(['success' => 1, 'invoice_id' => 123]);
```

**Error:**
```php
echo json_encode([
    'success' => 0,
    'validation_errors' => ['amount' => 'Required']
]);
```

That's it! The rest is handled automatically.

## ❓ FAQ

**Q: Do I need to update existing forms?**
A: No! The old pattern still works. Use the new pattern for new code.

**Q: Does this require backend changes?**
A: No! It works with your existing AJAX endpoints.

**Q: Can I customize error display?**
A: Yes! Pass an `errorTarget` selector to display errors in a specific container.

**Q: What about CSRF tokens?**
A: They're automatically included (same as before via `$.ajaxPrefilter`).

## 📞 Need Help?

See the comprehensive docs:
- Full Guide: `AJAX_PATTERN_GUIDE.md`
- Examples: `EXAMPLE_AJAX_PATTERN.php`
- Summary: `IMPLEMENTATION_SUMMARY.md`
