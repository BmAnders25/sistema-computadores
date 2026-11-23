<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#7b1e24">
    <title>S.R.C. · Sistema de Reserva de Computadores</title>
    <style>
        * { box-sizing: border-box; }
        body {
            display: grid;
            min-height: 100vh;
            margin: 0;
            padding: 1.5rem;
            place-items: center;
            color: #202b3b;
            background: #f8fafc;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }
        main {
            width: min(720px, 100%);
            padding: clamp(1.5rem, 5vw, 3rem);
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 16px 42px rgba(31, 41, 55, 0.08);
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 2rem;
            color: #7b1e24;
            font-size: 0.9rem;
            font-weight: 800;
            letter-spacing: 0.08em;
        }
        .brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            color: #fff;
            background: #7b1e24;
            border-radius: 12px;
        }
        .eyebrow {
            display: block;
            margin-bottom: 0.65rem;
            color: #a6262e;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        h1 {
            max-width: 580px;
            margin: 0;
            font-size: clamp(2rem, 6vw, 3.2rem);
            line-height: 1.08;
            letter-spacing: -0.035em;
        }
        p {
            max-width: 560px;
            margin: 1rem 0 1.6rem;
            color: #647184;
            font-size: 1rem;
            line-height: 1.65;
        }
        nav { display: flex; flex-wrap: wrap; gap: 0.65rem; }
        nav a {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            padding: 0.65rem 1rem;
            color: #fff;
            background: #a6262e;
            border: 1px solid #a6262e;
            border-radius: 9px;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
        }
        nav a:hover { background: #7b1e24; border-color: #7b1e24; }
        nav a.secondary { color: #7b1e24; background: #fff; border-color: #e2e8f0; }
        nav a.secondary:hover { background: #fff5f5; border-color: #f1d8da; }
        footer {
            margin-top: 2rem;
            padding-top: 1rem;
            color: #8792a0;
            border-top: 1px solid #edf0f4;
            font-size: 0.72rem;
        }
    </style>
</head>
<body>
    <main>
        <div class="brand"><span class="brand-mark" aria-hidden="true">SRC</span> SISTEMA INSTITUCIONAL</div>
        <span class="eyebrow">Universidad de Pamplona · Villa del Rosario</span>
        <h1>Reserva el equipo que necesitas para aprender.</h1>
        <p>Consulta la disponibilidad de computadores y administra tus reservas desde un solo lugar.</p>
        <nav aria-label="Acceso al sistema">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/home') }}">Ir al sistema</a>
                @else
                    <a href="{{ route('login') }}">Iniciar sesión</a>
                    @if (Route::has('register'))
                        <a class="secondary" href="{{ route('register') }}">Crear cuenta</a>
                    @endif
                @endauth
            @endif
        </nav>
        <footer>© {{ date('Y') }} S.R.C. Sistema de Reserva de Computadores.</footer>
    </main>
</body>
</html>
