<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'App Tareas')</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10">
    <main class="w-full max-w-[420px]">
        @yield('content')
    </main>
</body>
</html>
