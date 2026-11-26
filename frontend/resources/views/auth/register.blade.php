@extends('layouts.auth')

@section('title', 'Crear cuenta')

@section('content')
    <section class="rounded-2xl bg-white px-6 py-9 shadow-[0_18px_50px_rgba(112,38,50,0.14)] sm:px-10">
        <h1 class="text-center text-[30px] font-extrabold leading-tight tracking-tight text-negro">Regístrate</h1>
        <p class="mt-2 text-center text-[13px] font-medium text-gris">Crea tu espacio en menos de un minuto.</p>

        @if (session('success'))
            <p class="mt-5 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</p>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4" data-validation="register">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">

            <div>
                <label for="name" class="field-label">Nombre</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Juan Pérez"
                    required
                    autofocus
                    autocomplete="name"
                    data-rules="required"
                    aria-describedby="error-name"
                    aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                    class="field-input"
                >
                <p
                    id="error-name"
                    data-error-for="name"
                    role="alert"
                    class="mt-1 text-sm text-peligro @if (! $errors->has('name')) hidden @endif"
                >{{ $errors->first('name') }}</p>
            </div>

            <div>
                <label for="email" class="field-label">Correo</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="tu@correo.com"
                    required
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
                    placeholder="Mínimo 8 caracteres"
                    required
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
                    placeholder="Repite tu contraseña"
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
                Crear cuenta
            </button>
        </form>

        <div class="my-5 flex items-center gap-3 text-[10px] font-bold uppercase tracking-[0.14em] text-gris/50">
            <span class="h-px flex-1 bg-borde"></span>
            o
            <span class="h-px flex-1 bg-borde"></span>
        </div>

        <a href="{{ route('login') }}" class="btn-outline w-full py-3">
            Ya tengo cuenta
        </a>
    </section>
@endsection
