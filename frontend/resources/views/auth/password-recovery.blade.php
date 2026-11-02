@extends('layouts.app')

@section('title', 'Recuperar cuenta')

@section('content')
    <h1 class="text-primario mb-1">Recuperar cuenta</h1>
    <p class="text-gris mb-8 font-medium">
        Ingresa el correo asociado a tu cuenta para recibir instrucciones y recuperar el acceso.
    </p>

    <div>
        <label for="email" class="mb-1 block font-medium text-negro">Correo</label>
        <input
            id="email"
            type="email"
            name="email"
            required
            autocomplete="email"
            placeholder="tucorreo@ejemplo.com"
            aria-describedby="recovery-status"
            class="w-full rounded-full border border-gris/30 bg-input px-5 py-3 text-negro outline-none placeholder:text-gris/50 focus:border-primario focus:ring-2 focus:ring-primario/30"
        >
    </div>

    <p id="recovery-status" class="mt-3 text-sm text-gris" role="status">
        El envío de instrucciones estará disponible cuando se conecte el servicio de recuperación.
    </p>

    <button
        type="button"
        disabled
        class="mt-6 w-full rounded-full bg-primario px-5 py-3 font-semibold text-white transition hover:bg-primario-oscuro focus:outline-none focus:ring-2 focus:ring-primario/40 disabled:cursor-not-allowed"
    >
        Enviar instrucciones
    </button>

    <p class="mt-6 text-center text-gris">
        ¿Recordaste tu contraseña?
        <a href="{{ route('login') }}" class="font-semibold text-primario hover:text-primario-oscuro">Volver a iniciar sesión</a>
    </p>
@endsection
