@extends('layouts.app')

@section('title', 'App Tareas | Organiza tu día')
@section('body-class', 'min-h-screen bg-fondo text-negro')
@section('main-class', 'w-full')

@section('content')
    <div class="overflow-hidden">
        <header class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-12">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="App Tareas, inicio">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primario text-white shadow-lg shadow-primario/20">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                        <path d="m7 12.5 3.2 3.2L17.5 8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M20 12a8 8 0 1 1-2.34-5.66" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="text-lg font-extrabold tracking-tight text-negro">App <span class="text-primario">Tareas</span></span>
            </a>

            <nav aria-label="Navegación principal" class="flex items-center gap-3 sm:gap-6">
                <a href="#como-funciona" class="hidden text-sm font-semibold text-gris transition hover:text-primario sm:inline">
                    Cómo funciona
                </a>
                @if (session()->has('api_token'))
                    <a
                        href="{{ route('panel') }}"
                        class="btn-primary"
                    >
                        Ir a mi panel
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost">
                        Iniciar sesión
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="btn-primary"
                    >
                        Crear cuenta
                    </a>
                @endif
            </nav>
        </header>

        <div>
            <section class="mx-auto grid max-w-7xl items-center gap-14 px-5 pb-20 pt-10 sm:px-8 sm:pb-28 sm:pt-16 lg:grid-cols-[1.05fr_0.95fr] lg:gap-20 lg:px-12 lg:pt-20">
                <div class="max-w-2xl">
                    <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-primario/15 bg-white/80 px-4 py-2 text-sm font-bold text-primario shadow-sm">
                        <span class="h-2 w-2 rounded-full bg-primario"></span>
                        Un espacio para tus pendientes
                    </p>
                    <h1 class="max-w-xl text-5xl font-extrabold leading-[1.08] tracking-tight text-negro sm:text-6xl">
                        Organiza tu día,
                        <span class="text-primario">una tarea a la vez.</span>
                    </h1>
                    <p class="mt-6 max-w-lg text-lg leading-8 text-gris">
                        Empieza con claridad, mantén tus pendientes a la vista y avanza a tu propio ritmo.
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        @if (session()->has('api_token'))
                            <a
                                href="{{ route('panel') }}"
                                class="btn-primary px-7 py-3.5"
                            >
                                Ir a mi panel
                                <span aria-hidden="true">→</span>
                            </a>
                        @else
                            <a
                                href="{{ route('register') }}"
                                class="btn-primary px-7 py-3.5"
                            >
                                Comenzar ahora
                                <span aria-hidden="true">→</span>
                            </a>
                            <a
                                href="{{ route('login') }}"
                                class="btn-outline px-7 py-3.5"
                            >
                                Ya tengo una cuenta
                            </a>
                        @endif
                    </div>

                    <p class="mt-5 text-sm text-gris">
                        ¿Necesitas recuperar tu acceso?
                        <a href="{{ route('password.recovery') }}" class="font-bold text-primario underline decoration-primario/30 underline-offset-4 hover:text-primario-oscuro">
                            Recupera tu contraseña
                        </a>
                    </p>
                </div>

                <div class="relative mx-auto w-full max-w-xl">
                    <div aria-hidden="true" class="absolute -left-8 top-8 h-40 w-40 rounded-full bg-primario/10 blur-3xl sm:-left-12 sm:h-56 sm:w-56"></div>
                    <div aria-hidden="true" class="absolute -bottom-8 right-0 h-48 w-48 rounded-full bg-amber-200/40 blur-3xl"></div>

                    <div class="relative rounded-[2rem] border border-white/80 bg-white/90 p-5 shadow-2xl shadow-primario/10 sm:p-8">
                        <div class="flex items-center justify-between border-b border-gris/10 pb-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-primario">Tu espacio</p>
                                <p class="mt-1 text-xl font-extrabold text-negro">Un paso a la vez</p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-fondo text-primario">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                                    <path d="M8 3v3m8-3v3M4.5 9h15M6 5h12a1.5 1.5 0 0 1 1.5 1.5v12A1.5 1.5 0 0 1 18 20H6a1.5 1.5 0 0 1-1.5-1.5v-12A1.5 1.5 0 0 1 6 5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    <path d="m9 14 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>

                        <div class="mt-6 space-y-3">
                            <div class="flex items-center gap-4 rounded-2xl bg-fondo/80 p-4">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-primario shadow-sm">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                                        <path d="m7 12.5 3.2 3.2L17.5 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <div class="h-2.5 w-3/4 rounded-full bg-gris/10"></div>
                                <span class="ml-auto h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            </div>
                            <div class="flex items-center gap-4 rounded-2xl bg-fondo/80 p-4">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-primario shadow-sm">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                                        <path d="M12 7v5l3 2m5-2a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <div class="h-2.5 w-2/3 rounded-full bg-gris/10"></div>
                                <span class="ml-auto h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                            </div>
                            <div class="flex items-center gap-4 rounded-2xl bg-fondo/80 p-4">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-primario shadow-sm">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                                        <path d="M12 4v16m-8-8h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <div class="h-2.5 w-1/2 rounded-full bg-gris/10"></div>
                                <span class="ml-auto h-2.5 w-2.5 rounded-full bg-primario/40"></span>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-3 rounded-2xl bg-primario p-4 text-white">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                                    <path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div>
                                <p class="font-bold">Cada avance cuenta</p>
                                <p class="mt-0.5 text-sm text-white/75">Haz espacio para lo que sigue.</p>
                            </div>
                        </div>
                    </div>
                    <p class="relative mt-4 text-center text-xs font-medium text-gris">Una vista previa de tu espacio de trabajo</p>
                </div>
            </section>

            <section id="como-funciona" class="border-y border-gris/10 bg-white/70">
                <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20 lg:px-12">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-primario">Empieza a tu manera</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-negro sm:text-4xl">Menos enredos, más claridad.</h2>
                        <p class="mt-4 leading-7 text-gris">Entra a tu cuenta o crea una nueva para continuar.</p>
                    </div>

                    <div class="mt-10 grid gap-4 md:grid-cols-3">
                        <a href="{{ route('register') }}" class="group rounded-3xl border border-gris/10 bg-white p-6 transition hover:-translate-y-1 hover:border-primario/20 hover:shadow-xl hover:shadow-primario/5 focus:outline-none focus:ring-2 focus:ring-primario/40">
                            <span class="text-sm font-extrabold tracking-[0.15em] text-primario">01</span>
                            <h3 class="mt-4 text-xl font-extrabold text-negro group-hover:text-primario">Crea tu cuenta</h3>
                            <p class="mt-2 leading-6 text-gris">Regístrate para entrar a tu espacio personal.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-primario">Registrarme <span aria-hidden="true">→</span></span>
                        </a>

                        <a href="{{ route('login') }}" class="group rounded-3xl border border-gris/10 bg-white p-6 transition hover:-translate-y-1 hover:border-primario/20 hover:shadow-xl hover:shadow-primario/5 focus:outline-none focus:ring-2 focus:ring-primario/40">
                            <span class="text-sm font-extrabold tracking-[0.15em] text-primario">02</span>
                            <h3 class="mt-4 text-xl font-extrabold text-negro group-hover:text-primario">Inicia sesión</h3>
                            <p class="mt-2 leading-6 text-gris">Vuelve a tu cuenta de forma segura cuando quieras.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-primario">Entrar <span aria-hidden="true">→</span></span>
                        </a>

                        <a href="{{ route('password.recovery') }}" class="group rounded-3xl border border-gris/10 bg-white p-6 transition hover:-translate-y-1 hover:border-primario/20 hover:shadow-xl hover:shadow-primario/5 focus:outline-none focus:ring-2 focus:ring-primario/40">
                            <span class="text-sm font-extrabold tracking-[0.15em] text-primario">03</span>
                            <h3 class="mt-4 text-xl font-extrabold text-negro group-hover:text-primario">Recupera el acceso</h3>
                            <p class="mt-2 leading-6 text-gris">Solicita un enlace si olvidaste tu contraseña.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-primario">Recuperar cuenta <span aria-hidden="true">→</span></span>
                        </a>
                    </div>
                </div>
            </section>
        </div>

        <footer class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-7 text-sm text-gris sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
            <a href="{{ route('home') }}" class="font-extrabold text-negro">App <span class="text-primario">Tareas</span></a>
            <nav aria-label="Enlaces de cuenta" class="flex flex-wrap gap-x-5 gap-y-2">
                <a href="{{ route('login') }}" class="transition hover:text-primario">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="transition hover:text-primario">Crear cuenta</a>
                <a href="{{ route('password.recovery') }}" class="transition hover:text-primario">Recuperar contraseña</a>
            </nav>
        </footer>
    </div>
@endsection
