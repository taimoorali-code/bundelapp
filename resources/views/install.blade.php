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
        }
        .install-card {
            max-width: 450px;
            margin: 80px auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            background-color: #ffffff;
        }
        .install-card h1 {
            font-size: 1.8rem;
            margin-bottom: 25px;
            text-align: center;
        }
        .btn-install {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
        }
        .shop-input {
            display: none; /* hidden, as we pass shop via hidden input */
        }
    </style>
</head>
<body>

<div class="install-card">
    <h1>Install the Shopify App</h1>
    <p class="text-center text-muted">Click the button below to install this app on your Shopify store.</p>

    <form action="{{ route('shopify.install') }}" method="GET">
        <input type="hidden" name="shop" value="{{ request('shop') }}">
        <button type="submit" class="btn btn-primary btn-install">Install App</button>
    </form>
</div>

<!-- Bootstrap 5 JS (optional, for better components) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
