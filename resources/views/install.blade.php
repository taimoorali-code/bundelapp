<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install App</title>
</head>
<body>
    <h1>Install the Shopify App</h1>
    <form action="{{ route('shopify.install') }}">
        <input type="hidden" name="shop" value="{{ request('shop') }}">
        <button type="submit">Install</button>
    </form>
</body>
</html>
