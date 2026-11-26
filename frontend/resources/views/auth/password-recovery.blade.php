@extends('layouts.auth')

@section('title', 'Recuperar contraseña')

@section('content')
    <section class="rounded-2xl bg-white px-6 py-9 shadow-[0_18px_50px_rgba(112,38,50,0.14)] sm:px-10">
        <h1 class="text-center text-[28px] font-extrabold leading-tight tracking-tight text-negro">Recuperar contraseña</h1>
        <p class="mt-2 text-center text-[13px] font-medium text-gris">
            Ingresa el correo asociado a tu cuenta y te enviaremos un enlace para restablecer la contraseña.
        </p>

        @if (session('status'))
            <p class="mt-5 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700" role="status">
                {{ session('status') }}
            </p>
        @endif

        <form method="POST" action="{{ route('password.recovery.store') }}" class="mt-6 space-y-4" data-validation="recovery">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">

            <div>
                <label for="email" class="field-label">Correo</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="tu@correo.com"
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

            <button type="submit" class="btn-primary w-full py-3.5">
                Enviar instrucciones
            </button>
        </form>

        <p class="mt-6 text-center text-[13px] text-gris">
            ¿Recordaste tu contraseña?
            <a href="{{ route('login') }}" class="font-bold text-primario underline underline-offset-4 hover:text-primario-oscuro">Volver a iniciar sesión</a>
        </p>
    </section>
@endsection
