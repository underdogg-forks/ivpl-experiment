<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fatal Error - InvoicePlane</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #e74c3c;
            color: white;
            padding: 20px;
        }
        .header h1 {
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .content p {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .error-class {
            background: #ecf0f1;
            padding: 10px 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
            margin: 10px 0;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.6;
            margin: 15px 0;
        }
        strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Fatal Error</h1>
        </div>
        <div class="content">
            <?php if ((defined('ENVIRONMENT') && ENVIRONMENT === 'development') || (defined('IP_DEBUG') && IP_DEBUG)): ?>
                <p class="error-class">
                    <strong><?php echo htmlspecialchars(get_class($exception)); ?>:</strong>
                    <?php echo htmlspecialchars($exception->getMessage()); ?>
                </p>
                <p>
                    <strong>File:</strong> <?php echo htmlspecialchars($exception->getFile()); ?><br>
                    <strong>Line:</strong> <?php echo $exception->getLine(); ?>
                </p>
                <pre><?php echo htmlspecialchars($exception->getTraceAsString()); ?></pre>
            <?php else: ?>
                <h1>An Error Occurred</h1>
                <p>The application encountered an unexpected error. Please try again later.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
