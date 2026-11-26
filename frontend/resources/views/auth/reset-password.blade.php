@extends('layouts.auth')

@section('title', 'Restablecer contraseña')

@section('content')
    <section class="rounded-2xl bg-white px-6 py-9 shadow-[0_18px_50px_rgba(112,38,50,0.14)] sm:px-10">
        <h1 class="text-center text-[28px] font-extrabold leading-tight tracking-tight text-negro">Restablecer contraseña</h1>
        <p class="mt-2 text-center text-[13px] font-medium text-gris">
            Define una nueva contraseña. El enlace es válido por 30 minutos y solo puede usarse una vez.
        </p>

        @if ($errors->has('email'))
            <p class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-peligro" role="alert">
                {{ $errors->first('email') }}
            </p>
        @endif

        <form method="POST" action="{{ route('password.reset.update') }}" class="mt-6 space-y-4" data-validation="reset">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="token" value="{{ old('token', $token) }}">
            <input type="hidden" name="email" value="{{ old('email', $email) }}">

            <p class="text-[13px] text-gris">
                Cuenta: <span class="font-bold text-negro">{{ $email }}</span>
            </p>

            <div>
                <label for="password" class="field-label">Nueva contraseña</label>
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
                    class="field-input"
                >
                <p id="password-hint" class="mt-1 text-xs text-gris/80">
                    Mínimo 8 caracteres, con una mayúscula, una minúscula y un número.
                </p>
                <p
                    id="error-password"
                    data-error-for="password"
                    role="alert"
                    class="mt-1 text-sm text-peligro @if (! $errors->has('password')) hidden @endif"
                >{{ $errors->first('password') }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="field-label">Confirmar contraseña</label>
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
                    class="field-input"
                >
                <p
                    id="error-password_confirmation"
                    data-error-for="password_confirmation"
                    role="alert"
                    class="mt-1 text-sm text-peligro @if (! $errors->has('password_confirmation')) hidden @endif"
                >{{ $errors->first('password_confirmation') }}</p>
            </div>

            <button type="submit" class="btn-primary w-full py-3.5">
                Restablecer contraseña
            </button>
        </form>

        <p class="mt-6 text-center text-[13px] text-gris">
            ¿Ya la cambiaste?
            <a href="{{ route('login') }}" class="font-bold text-primario underline underline-offset-4 hover:text-primario-oscuro">Iniciar sesión</a>
        </p>
    </section>
@endsection
