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
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center; /* center horizontally */
            align-items: center;    /* center vertically */
            font-family: 'Inter', sans-serif;
        }

        .container-flex {
            display: flex;
            width: 100%;
            max-width: 1200px;
            gap: 20px;
        }

        .sidebar {
            min-height: 100vh;
            background: #111827;
            color: #fff;
            padding: 20px;
            flex: 0 0 220px; /* fixed width sidebar */
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

        .main-content {
            flex: 1;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }
    </style>
</head>
<body>
    <div class="container-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="#" class="active">Dashboard</a>
            <a href="#">Bundles</a>
            <a href="#">Settings</a>
        </div>

        <!-- Main content -->
        <div class="main-content">
            @yield('content')
        </div>
    </div>
</body>
</html>
