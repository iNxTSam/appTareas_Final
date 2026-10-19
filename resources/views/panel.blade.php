<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Área privada</title>
</head>
<body>
    <h1>Área privada</h1>

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    <p>Hola, {{ auth()->user()->name }}. Has iniciado sesión correctamente.</p>
</body>
</html>
