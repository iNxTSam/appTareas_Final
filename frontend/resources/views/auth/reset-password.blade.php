@extends('layouts.app')

@section('title', 'Restablecer contraseña')

@section('content')
    <h1 class="text-primario mb-1">Restablecer contraseña</h1>
    <p class="text-gris mb-8 font-medium">
        Define una nueva contraseña para tu cuenta. El enlace es válido por 30 minutos y solo puede usarse una vez.
    </p>

    @if ($errors->has('email'))
        <p class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-primario" role="alert">
            {{ $errors->first('email') }}
        </p>
    @endif

    <form method="POST" action="{{ route('password.reset.update') }}" class="space-y-5" data-validation="reset">
        @csrf
        <input type="hidden" name="token" value="{{ old('token', $token) }}">
        <input type="hidden" name="email" value="{{ old('email', $email) }}">

        <p class="text-sm text-gris">
            Cuenta: <span class="font-medium text-negro">{{ $email }}</span>
        </p>

        <div>
            <label for="password" class="mb-1 block font-medium text-negro">Nueva contraseña</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autofocus
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
            Restablecer contraseña
        </button>
    </form>

    <p class="mt-6 text-center text-gris">
        ¿Ya la cambiaste?
        <a href="{{ route('login') }}" class="font-semibold text-primario hover:text-primario-oscuro">Iniciar sesión</a>
    </p>
@endsection
