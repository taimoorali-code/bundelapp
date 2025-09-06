<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Shopify App</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .install-card {
            max-width: 400px;
            margin: 100px auto;
            padding: 35px 30px;
            border-radius: 12px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.1);
            background-color: #ffffff;
            transition: transform 0.2s;
        }
        .install-card:hover {
            transform: translateY(-5px);
        }
        .install-card h1 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }
        .install-card p {
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-control {
            height: 45px;
            font-size: 0.95rem;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .btn-install {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="install-card">
    <h1>Install the Shopify App</h1>
    <p>Enter your Shopify store URL and click below to install the app.</p>

    <form action="{{ route('shopify.install') }}" method="GET">
        <input type="text" name="shop" class="form-control" placeholder="your-store.myshopify.com" value="{{ request('shop') }}" required>
        <button type="submit" class="btn btn-primary btn-install">Install App</button>
    </form>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
