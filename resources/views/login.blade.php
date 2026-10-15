<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>login</title>
</head>
<body>
    <h1>Inicio de sesion</h1>
    <form action="login.php" method="POST">
        <div>
            <label for="usuario">Ingresa tu usuario: </label>
            <input type="text" id="usuario" name="usuario" required>
        </div>
        <br>
        <div>
            <label for="contraseña">Ingresa tu contraseña: </label>
            <input type="text" id="contraseña" name="contraseña" required>
        </div>
        <br>
        <button type="submit">Ingresar</button>
        <p>¿No tienes una cuenta? <a href="{{ route('register') }}">Regístrate aquí</a></p>
    </form>
</body>
</html>
