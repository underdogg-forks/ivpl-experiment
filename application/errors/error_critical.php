<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Critical Error - InvoicePlane</title>
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
            background: #c0392b;
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
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .error-block {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 13px;
        }
        h2 {
            color: #333;
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⛔ Critical Error</h1>
        </div>
        <div class="content">
            <p>The application encountered an error and the error handler also failed.</p>
            
            <?php if ((defined('ENVIRONMENT') && ENVIRONMENT === 'development') || (defined('IP_DEBUG') && IP_DEBUG)): ?>
                <h2>Original Error:</h2>
                <div class="error-block">
                    <?php echo htmlspecialchars($originalException->getMessage()); ?>
                </div>
                
                <h2>Handler Error:</h2>
                <div class="error-block">
                    <?php echo htmlspecialchars($handlerException->getMessage()); ?>
                </div>
            <?php else: ?>
                <p>Please contact the administrator if this problem persists.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
