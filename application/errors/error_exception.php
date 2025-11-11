<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($heading) ? htmlspecialchars($heading) : 'Error'; ?> - InvoicePlane</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
            line-height: 1;
        }

        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .error-message {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s, transform 0.2s;
            font-weight: 500;
        }

        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .error-details {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
            text-align: left;
        }

        .error-details h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
        }

        .error-details pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.5;
            color: #666;
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 48px;
            }

            h1 {
                font-size: 20px;
            }

            .error-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?php echo isset($statusCode) ? $statusCode : '500'; ?></div>
        <h1><?php echo isset($heading) ? htmlspecialchars($heading) : 'An Error Occurred'; ?></h1>
        <div class="error-message">
            <?php echo isset($message) ? htmlspecialchars($message) : 'An unexpected error occurred. Please try again later.'; ?>
        </div>
        
        <?php if (function_exists('base_url')): ?>
            <a href="<?php echo base_url(); ?>" class="btn">Return to Home</a>
        <?php else: ?>
            <a href="/" class="btn">Return to Home</a>
        <?php endif; ?>

        <?php if ((defined('ENVIRONMENT') && ENVIRONMENT === 'development') || (defined('IP_DEBUG') && IP_DEBUG)): ?>
            <?php if (isset($exception) && is_object($exception)): ?>
                <div class="error-details">
                    <h2>Error Details (Development Mode)</h2>
                    <pre><?php echo htmlspecialchars(get_class($exception)); ?>: <?php echo htmlspecialchars($exception->getMessage()); ?>

File: <?php echo htmlspecialchars($exception->getFile()); ?>
Line: <?php echo $exception->getLine(); ?>

Stack Trace:
<?php echo htmlspecialchars($exception->getTraceAsString()); ?></pre>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
