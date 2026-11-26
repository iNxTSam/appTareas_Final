@extends('layouts.auth')

@section('title', 'Iniciar sesión')

@section('content')
    <section class="rounded-2xl bg-white px-6 py-9 shadow-[0_18px_50px_rgba(112,38,50,0.14)] sm:px-10">
        <h1 class="text-center text-[30px] font-extrabold leading-tight tracking-tight text-negro">Inicia sesión</h1>
        <p class="mt-2 text-center text-[13px] font-medium text-gris">Bienvenido de nuevo. Accede a tu tablero de tareas.</p>

        @if (request()->boolean('expired'))
            <p class="mt-5 rounded-xl bg-[#FBF3DF] px-4 py-3 text-center text-sm font-semibold text-[#5A3E08]" role="status">
                Tu sesión expiró por inactividad. Inicia sesión de nuevo.
            </p>
        @endif

        @if (session('success'))
            <p class="mt-5 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</p>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4" data-validation="login">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">

            <div>
                <label for="email" class="field-label">Correo</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="tu@correo.com"
                    required
                    autofocus
                    autocomplete="email"
                    data-rules="required email"
                    aria-describedby="error-email"
                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    class="field-input"
                >
                <p
                    id="error-email"
                    data-error-for="email"
                    role="alert"
                    class="mt-1 text-sm text-peligro @if (! $errors->has('email')) hidden @endif"
                >{{ $errors->first('email') }}</p>
            </div>

            <div>
                <label for="password" class="field-label">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                    data-rules="required"
                    aria-describedby="error-password"
                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                    class="field-input"
                >
                <p
                    id="error-password"
                    data-error-for="password"
                    role="alert"
                    class="mt-1 text-sm text-peligro @if (! $errors->has('password')) hidden @endif"
                >{{ $errors->first('password') }}</p>
            </div>

            <div class="text-right">
                <a href="{{ route('password.recovery') }}" class="text-[11px] font-bold text-primario underline underline-offset-4 hover:text-primario-oscuro">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

            <button type="submit" class="btn-primary w-full py-3.5">
                Iniciar sesión
            </button>
        </form>

        <div class="my-5 flex items-center gap-3 text-[10px] font-bold uppercase tracking-[0.14em] text-gris/50">
            <span class="h-px flex-1 bg-borde"></span>
            o
            <span class="h-px flex-1 bg-borde"></span>
        </div>

        <a href="{{ route('register') }}" class="btn-outline w-full py-3">
            ¿No tienes cuenta? Regístrate
        </a>
    </section>
@endsection
