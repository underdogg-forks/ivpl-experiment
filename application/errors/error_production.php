<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($heading) ? htmlspecialchars($heading) : 'Error'; ?> - InvoicePlane</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
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
        }
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 15px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
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
        <p><?php echo isset($message) ? htmlspecialchars($message) : 'An unexpected error occurred. Please try again later.'; ?></p>
        <a href="<?php echo function_exists('base_url') ? base_url() : '/'; ?>" class="btn">Return to Home</a>
    </div>
</body>
</html>
