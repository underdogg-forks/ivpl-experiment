# Error Handling Visual Guide

This guide shows what users will see when different types of errors occur in InvoicePlane.

## Development Mode Error Display

When `ENVIRONMENT=development` or `ENABLE_DEBUG=true`, errors are displayed with full details to help developers debug issues.

### With Whoops (Recommended)

If the `filp/whoops` package is installed, you'll see a beautiful, interactive error page:

**Features:**
- Clean, modern design with color-coded syntax
- Full stack trace with clickable file paths
- Code snippets showing the exact line where the error occurred
- Environment variables and request details
- Interactive frames you can expand/collapse
- Search functionality within the stack trace
- Dark mode support

**What it looks like:**
- Orange/red header with error type and message
- Code editor-style display with line numbers
- Expandable stack frames showing:
  - File path
  - Line number
  - Code context (lines before and after)
  - Function/method called
  - Arguments passed
- Additional data tables showing:
  - InvoicePlane environment info
  - Request details (method, URI, host)
  - GET/POST data
  - Cookies
  - Session data
  - Server/Request data

### Without Whoops (Fallback)

If Whoops is not installed, a styled development error page is shown:

**Features:**
- Clean, professional design
- Error type (exception class name)
- Error message in red
- File and line number
- Full stack trace in a dark code block
- "DEVELOPMENT MODE" badge to indicate you're seeing debug info

**Visual Style:**
- White container with shadow
- Red header (⚠️ An Error Occurred)
- Gray boxes for error details
- Dark (navy) background for stack trace
- Monospace font for code elements

## Production Mode Error Display

When `ENVIRONMENT=production` and `ENABLE_DEBUG=false`, users see friendly, minimal error pages.

**Features:**
- Modern gradient background (purple to blue)
- White card with rounded corners and shadow
- Large, bold status code (404, 500, etc.)
- User-friendly heading
- Simple, non-technical error message
- "Return to Home" button with hover effect
- Fully responsive design

**Visual Style:**
- Gradient background: Purple (#667eea) to darker purple (#764ba2)
- White content card with 10px border radius
- Status code in large purple text (72px)
- Smooth hover animations on button
- Mobile-friendly layout

### Different Status Codes

#### 404 - Not Found
```
Error Code: 404
Heading: Page Not Found
Message: The page you are looking for could not be found.
```

#### 401 - Unauthorized
```
Error Code: 401
Heading: Unauthorized
Message: You are not authorized to access this resource.
```

#### 403 - Forbidden
```
Error Code: 403
Heading: Forbidden
Message: You do not have permission to access this resource.
```

#### 400 - Bad Request
```
Error Code: 400
Heading: Bad Request
Message: The request could not be understood by the server.
```

#### 500 - Internal Server Error
```
Error Code: 500
Heading: Internal Server Error
Message: The server encountered an internal error.
```

## Error Logging

All errors are logged regardless of the display mode:

### Exception Logs
**File:** `application/logs/exceptions-YYYY-MM-DD.php`

```
[2024-11-11 07:30:45] NotFoundException: Invoice not found in /path/to/file.php:123
Stack trace:
#0 /path/to/controller.php(45): Controller->method()
#1 {main}
```

### Request Logs (Debug Mode)
**File:** `application/logs/requests-YYYY-MM-DD.php`

```
[2024-11-11 07:30:45] GET /invoices/view/123 from 192.168.1.1
[2024-11-11 07:30:45] Response: 404 - Duration: 0.0234s
```

### HTTP Error Logs
**File:** `application/logs/http-errors-YYYY-MM-DD.php`

```
[2024-11-11 07:30:45] POST https://api.example.com/sync - Error: Connection timeout
```

## Color Scheme

### Development Mode (Whoops)
- Primary: Orange/Red (#e74c3c, #ff6b6b)
- Background: Light gray (#f5f5f5)
- Code background: Dark gray (#2c3e50)
- Text: Dark (#333)
- Accent: Blue (#3498db)

### Development Mode (Fallback)
- Header: Red (#e74c3c)
- Background: Light gray (#f5f5f5)
- Content box: White (#ffffff)
- Code background: Dark navy (#2c3e50)
- Code text: Light gray (#ecf0f1)

### Production Mode
- Background gradient: Purple to violet (#667eea → #764ba2)
- Card: White (#ffffff)
- Status code: Purple (#667eea)
- Text: Dark gray (#333, #666)
- Button: Purple (#667eea)
- Button hover: Darker purple (#5568d3)

## Responsive Design

All error pages are fully responsive:

### Desktop (> 768px)
- Maximum width: 600-800px
- Centered on screen
- Full padding and spacing
- Large fonts

### Mobile (< 768px)
- Full width with padding
- Smaller status code (48px instead of 72px)
- Smaller heading (20px instead of 24px)
- Touch-friendly button size
- Optimized spacing

## Accessibility

All error pages follow accessibility best practices:

- Semantic HTML5 structure
- Proper heading hierarchy (h1, h2, h3)
- Sufficient color contrast ratios
- Responsive meta viewport tag
- Clear, readable fonts
- No reliance on color alone for information
- Screen reader friendly error messages

## Browser Compatibility

Error pages work on all modern browsers:

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

Graceful degradation for older browsers:
- IE11: Basic layout without gradients
- Older mobile browsers: Simplified layout

## Testing

To see the error pages in action, visit:

### Development Mode
1. Set `ENABLE_DEBUG=true` in `ipconfig.php`
2. Visit `/error_test` to see all error types
3. Test individual errors:
   - `/error_test/test_404` - Not Found
   - `/error_test/test_500` - Internal Server Error
   - `/error_test/test_php_error` - PHP Error
   - etc.

### Production Mode
1. Set `ENABLE_DEBUG=false` and `CI_ENV=production`
2. Trigger an error (e.g., visit a non-existent page)
3. Verify you see the user-friendly error page

## Customization

### Changing Colors

Edit `application/errors/error_exception.php` to customize the production error page:

```css
/* Change gradient background */
background: linear-gradient(135deg, #yourcolor1 0%, #yourcolor2 100%);

/* Change status code color */
.error-code { color: #yourcolor; }

/* Change button color */
.btn { background: #yourcolor; }
```

### Adding Your Logo

Add your company logo to the error page:

```html
<div class="error-container">
    <img src="<?php echo base_url('assets/logo.png'); ?>" alt="Company Logo" style="max-width: 200px; margin-bottom: 20px;">
    <!-- rest of the error page -->
</div>
```

### Custom Error Messages

Modify the `getErrorMessage()` method in `ExceptionHandler.php`:

```php
protected function getErrorMessage($statusCode, $exception)
{
    $messages = [
        404 => 'Oops! We couldn\'t find what you\'re looking for.',
        500 => 'Something went wrong on our end. We\'re working to fix it!',
        // Add your custom messages
    ];
    
    return $messages[$statusCode] ?? 'An error occurred.';
}
```

## Best Practices

1. **Always enable error logging**: Even in production, you need logs to diagnose issues
2. **Use appropriate HTTP status codes**: Helps with SEO and API integrations
3. **Provide helpful error messages**: Guide users on what to do next
4. **Monitor your error logs**: Set up alerts for critical errors
5. **Test error pages regularly**: Ensure they display correctly
6. **Keep error pages simple**: Don't add features that might also fail
7. **Include contact information**: Let users report persistent issues

## Security Considerations

- **Never expose sensitive data in production**: Stack traces, file paths, database queries
- **Log everything securely**: Error logs may contain sensitive information
- **Use HTTPS**: Error pages should be served over secure connections
- **Rate limit error endpoints**: Prevent abuse of error-triggering URLs
- **Monitor for unusual error patterns**: Could indicate security probes

## Performance

Error handling is designed to have minimal performance impact:

- **No extra overhead in normal operation**: Only activates when errors occur
- **Efficient logging**: Asynchronous file writes
- **Lazy loading**: Whoops only loaded when needed
- **Minimal memory usage**: Clean up resources in finally blocks
- **Fast fallbacks**: If Whoops fails, simple HTML is generated quickly

## Summary

The error handling system provides:

✅ **Beautiful development errors** - Whoops integration with fallback  
✅ **User-friendly production errors** - Modern, responsive design  
✅ **Comprehensive logging** - All errors tracked in dated log files  
✅ **Proper HTTP status codes** - SEO and API friendly  
✅ **Mobile responsive** - Works on all devices  
✅ **Accessible** - Screen reader compatible  
✅ **Customizable** - Easy to modify colors and messages  
✅ **Secure** - No sensitive data exposed in production  
✅ **Fast** - Minimal performance impact  

The system enhances the developer experience while keeping end users informed without exposing technical details.
