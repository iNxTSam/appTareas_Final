@extends('layouts.app')

@section('title', 'Crear cuenta')

@section('content')
    <h1 class="text-primario mb-1">Crear cuenta</h1>
    <p class="text-gris mb-8 font-medium">Regístrate para empezar a usar la plataforma.</p>

    @if (session('success'))
        <p class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</p>
    @endif

    <form method="POST" action="{{ route('register.store') }}" class="space-y-5" data-validation="register">
        @csrf

        <div>
            <label for="name" class="mb-1 block font-medium text-negro">Nombre</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                data-rules="required"
                aria-describedby="error-name"
                aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                class="w-full rounded-full border border-gris/30 bg-input px-5 py-3 text-negro outline-none placeholder:text-gris/50 focus:border-primario focus:ring-2 focus:ring-primario/30"
            >
            <p
                id="error-name"
                data-error-for="name"
                role="alert"
                class="mt-1 text-sm text-primario @if (! $errors->has('name')) hidden @endif"
            >{{ $errors->first('name') }}</p>
        </div>

        <div>
            <label for="email" class="mb-1 block font-medium text-negro">Correo</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                data-rules="required email"
                aria-describedby="error-email"
                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                class="w-full rounded-full border border-gris/30 bg-input px-5 py-3 text-negro outline-none placeholder:text-gris/50 focus:border-primario focus:ring-2 focus:ring-primario/30"
            >
            <p
                id="error-email"
                data-error-for="email"
                role="alert"
                class="mt-1 text-sm text-primario @if (! $errors->has('email')) hidden @endif"
            >{{ $errors->first('email') }}</p>
        </div>

        <div>
            <label for="password" class="mb-1 block font-medium text-negro">Contraseña</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                data-rules="required password"
                aria-describedby="password-hint error-password"
                aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                class="w-full rounded-full border border-gris/30 bg-input px-5 py-3 text-negro outline-none placeholder:text-gris/50 focus:border-primario focus:ring-2 focus:ring-primario/30"
            >
            <p id="password-hint" class="mt-1 text-xs text-gris/80">
                Mínimo 8 caracteres, con una mayúscula, una minúscula y un número.
            </p>
            <p
                id="error-password"
                data-error-for="password"
                role="alert"
                class="mt-1 text-sm text-primario @if (! $errors->has('password')) hidden @endif"
            >{{ $errors->first('password') }}</p>
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block font-medium text-negro">Confirmar contraseña</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                data-rules="required confirmation"
                data-match="password"
                aria-describedby="error-password_confirmation"
                aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}"
                class="w-full rounded-full border border-gris/30 bg-input px-5 py-3 text-negro outline-none placeholder:text-gris/50 focus:border-primario focus:ring-2 focus:ring-primario/30"
            >
            <p
                id="error-password_confirmation"
                data-error-for="password_confirmation"
                role="alert"
                class="mt-1 text-sm text-primario @if (! $errors->has('password_confirmation')) hidden @endif"
            >{{ $errors->first('password_confirmation') }}</p>
        </div>

        <button
            type="submit"
            class="w-full rounded-full bg-primario px-5 py-3 font-semibold text-white transition hover:bg-primario-oscuro focus:outline-none focus:ring-2 focus:ring-primario/40 disabled:cursor-not-allowed disabled:opacity-60"
        >
            Registrarse
        </button>
    </form>

    <p class="mt-6 text-center text-gris">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}" class="font-semibold text-primario hover:text-primario-oscuro">Inicia sesión</a>
    </p>
@endsection
