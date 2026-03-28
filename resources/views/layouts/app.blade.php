<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'GraceSoft Capture' }}</title>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --line: #dbe3ec;
            --text: #0f172a;
            --muted: #475569;
            --accent: #0f766e;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: linear-gradient(160deg, #eef6ff 0%, var(--bg) 100%);
        }

        .shell {
            max-width: 1080px;
            margin: 0 auto;
            padding: 1.5rem 1rem 3rem;
        }

        .nav {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .nav a {
            text-decoration: none;
            color: var(--accent);
            font-weight: 600;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 1rem;
        }

        .grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .full { grid-column: 1 / -1; }

        label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 0.4rem;
            font-weight: 600;
        }

        input,
        select,
        textarea,
        button {
            width: 100%;
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            border: 1px solid var(--line);
            font: inherit;
        }

        button {
            width: auto;
            border: 0;
            background: var(--accent);
            color: #fff;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th,
        td {
            border-bottom: 1px solid var(--line);
            text-align: left;
            padding: 0.65rem;
            font-size: 0.95rem;
        }

        .flash {
            margin-bottom: 1rem;
            padding: 0.65rem 0.8rem;
            border-radius: 8px;
        }

        .flash.ok {
            border: 1px solid #10b981;
            background: #ecfdf5;
            color: #065f46;
        }

        .flash.error {
            border: 1px solid #ef4444;
            background: #fef2f2;
            color: #7f1d1d;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        @media (max-width: 760px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <nav class="nav">
            <a href="{{ route('manage.forms.index') }}">Forms</a>
            <a href="{{ route('inbox.index') }}">Inbox</a>
        </nav>

        @if (session('status'))
            <div class="flash ok">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash error">{{ $errors->first() }}</div>
        @endif

        @yield('content')
    </div>
</body>
</html>
