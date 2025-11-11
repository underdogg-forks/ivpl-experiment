<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error - InvoicePlane</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
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
            margin-bottom: 5px;
        }
        .header p {
            opacity: 0.9;
        }
        .content {
            padding: 20px;
        }
        .error-type {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 14px;
        }
        .error-message {
            color: #e74c3c;
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .file-info {
            background: #ecf0f1;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 13px;
        }
        .stack-trace {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.6;
        }
        .stack-trace pre {
            margin: 0;
            white-space: pre-wrap;
        }
        .env-badge {
            display: inline-block;
            background: #f39c12;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ An Error Occurred <span class="env-badge">DEVELOPMENT MODE</span></h1>
            <p>InvoicePlane Error Handler</p>
        </div>
        <div class="content">
            <div class="error-type">
                <?php echo htmlspecialchars(get_class($exception)); ?>
            </div>
            <div class="error-message">
                <?php echo htmlspecialchars($exception->getMessage()); ?>
            </div>
            <div class="file-info">
                <strong>File:</strong> <?php echo htmlspecialchars($exception->getFile()); ?><br>
                <strong>Line:</strong> <?php echo $exception->getLine(); ?>
            </div>
            <h3 style="margin-bottom: 10px;">Stack Trace</h3>
            <div class="stack-trace">
                <pre><?php echo htmlspecialchars($exception->getTraceAsString()); ?></pre>
            </div>
        </div>
    </div>
</body>
</html>
