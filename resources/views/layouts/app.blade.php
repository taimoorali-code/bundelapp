<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bundle Discount App')</title>

    <!-- Shopify Polaris CSS (optional for UI look) -->
    <link rel="stylesheet" href="https://unpkg.com/@shopify/polaris@12.0.0/build/esm/styles.css" />

    <!-- Bootstrap for quick layout -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f9fafb;
        }
        .sidebar {
            min-height: 100vh;
            background: #111827;
            color: #fff;
            padding: 20px;
        }
        .sidebar a {
            color: #d1d5db;
            display: block;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            margin-bottom: 6px;
        }
        .sidebar a.active,
        .sidebar a:hover {
            background: #2563eb;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        

        <!-- Main content -->
        <div class="col-10 p-4">
            @yield('content')
        </div>
    </div>
</div>
</body>
</html>
