<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #12261c; }
        h1 { font-size: 16px; color: #087445; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #d9e3dc; padding: 4px; text-align: left; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p>{{ $event->name }}</p>
    <table>
        @foreach ($rows as $row)
            <tr>
                @foreach ($row as $cell)
                    @if ($loop->parent->first)
                        <th>{{ $cell }}</th>
                    @else
                        <td>{{ $cell }}</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </table>
</body>
</html>
