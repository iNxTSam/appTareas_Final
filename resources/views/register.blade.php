<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>
    <form >
    <div>
        <label for="name"> Nombre </label>
        <input id="name" typer="text" name="name" value="{{ old('name') }}" required>
    
    </div>
    <div>
        <label for="email"> Correo </label>
        <input id="email" typer="email" name="email" value="{{ old('email') }}" required>
     
    </div>
    <div>
        <label for="password"> Contraseña </label>
        <input id="password" typer="password" name="password" value="{{ old('password') }}" required>
      
    </div>
    <div>
        <label for="password_confirmation">  </label>
        <input id="password_confirmation" typer="password" name="password_confirmation" required>
       
    </div>
    <p>¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>

    </form>       
</body>
</html>
