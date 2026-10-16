<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
</head>
<body>
    <h1>Crear cuenta</h1>

    @if ($errors->any())
        <ul style="color: red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    <form method="POST" action="{{ route('register.store') }}">
        @csrf

        <div>
            <label for="name">Nombre</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required>
        </div>

        <div>
            <label for="email">Correo</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
        </div>

        <div>
            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required>
        </div>

        <div>
            <label for="password_confirmation">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>
        </div>

        <button type="submit">Registrarse</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
</body>
</html>
