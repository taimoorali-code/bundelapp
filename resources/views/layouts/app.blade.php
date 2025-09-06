<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bundle Discount App')</title>

    <!-- Bootstrap for quick layout -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f9fafb;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center; /* center horizontally */
            align-items: flex-start; /* align top but keep centered horizontally */
            padding-top: 40px;       /* some spacing from top */
            font-family: 'Inter', sans-serif;
        }

        .main-content {
            width: 100%;
            max-width: 1000px;      /* restrict width */
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }
    </style>
</head>
<body>
    <div class="main-content">
        @yield('content')
    </div>
</body>
</html>
