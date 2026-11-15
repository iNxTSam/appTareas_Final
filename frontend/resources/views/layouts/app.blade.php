<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'App Tareas')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('body-class', 'min-h-screen flex items-center justify-center px-4 py-10')">
    <main class="@yield('main-class', 'w-full max-w-[420px]')">
        @yield('content')
    </main>
</body>
</html>
