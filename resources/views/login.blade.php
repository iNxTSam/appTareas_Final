@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
    <h1 class="text-primario mb-1">Iniciar sesión</h1>
    <p class="text-gris mb-8 font-medium">Ingresa con tu cuenta para continuar.</p>

    @if (session('success'))
        <p class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</p>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="mb-1 block font-medium text-negro">Correo</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="w-full rounded-full border border-gris/30 bg-input px-5 py-3 text-negro outline-none placeholder:text-gris/50 focus:border-primario focus:ring-2 focus:ring-primario/30"
            >
            @error('email')
                <p class="mt-1 text-sm text-primario">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block font-medium text-negro">Contraseña</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                class="w-full rounded-full border border-gris/30 bg-input px-5 py-3 text-negro outline-none placeholder:text-gris/50 focus:border-primario focus:ring-2 focus:ring-primario/30"
            >
            @error('password')
                <p class="mt-1 text-sm text-primario">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-full bg-primario px-5 py-3 font-semibold text-white transition hover:bg-primario-oscuro focus:outline-none focus:ring-2 focus:ring-primario/40 disabled:cursor-not-allowed disabled:opacity-60"
        >
            Ingresar
        </button>
    </form>

    <p class="mt-6 text-center text-gris">
        ¿No tienes cuenta?
        <a href="{{ route('register') }}" class="font-semibold text-primario hover:text-primario-oscuro">Regístrate</a>
    </p>
@endsection
