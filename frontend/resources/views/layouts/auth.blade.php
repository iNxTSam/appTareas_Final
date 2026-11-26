<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'App Tareas')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-fondo text-negro">
    <header class="border-b border-borde/70 bg-fondo">
        <div class="mx-auto flex max-w-[1440px] items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="App Tareas, inicio">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primario text-[11px] font-extrabold tracking-tight text-white">AT</span>
                <span class="text-lg font-extrabold uppercase tracking-tight text-negro">App Tareas</span>
            </a>

            <a href="{{ route('login') }}" class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-primario underline underline-offset-4 transition hover:text-primario-oscuro">
                Iniciar sesión
            </a>
        </div>
    </header>

    <main class="flex min-h-[calc(100vh-65px)] items-center justify-center px-4 py-10 sm:py-14">
        <div class="w-full max-w-[440px]">
            @yield('content')
        </div>
    </main>
</body>
</html>
