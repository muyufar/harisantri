<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; background: #f8faf8; color: #12261c; display: grid; min-height: 100vh; place-items: center; margin: 0; }
        main { max-width: 28rem; padding: 1.5rem; text-align: center; }
        a { color: #087445; }
    </style>
</head>
<body>
    <main>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <p><a href="{{ url('/') }}">Kembali ke beranda</a></p>
    </main>
</body>
</html>
