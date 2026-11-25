        <header class="mx-auto flex w-full max-w-[1440px] flex-wrap items-start justify-between gap-3 px-4 pb-2 pt-6 sm:px-10">
            <div class="flex min-w-0 items-center gap-3">
                <button
                    type="button"
                    data-nav-toggle
                    aria-label="Volver al menú principal"
                    class="btn-icon-outline hidden h-10 w-10 sm:h-11 sm:w-11"
                >
                    <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="1.75" d="M4 7h16M4 12h16M4 17h16"/>
                    </svg>
                </button>

                <button
                    type="button"
                    data-open-profile
                    aria-label="Abrir opciones de cuenta y sesión"
                    class="min-w-0 rounded-xl text-left transition hover:opacity-80 focus:outline-none focus:ring-2 focus:ring-primario/30"
                >
                    <span class="block truncate text-[26px] font-extrabold uppercase leading-none tracking-tight text-negro sm:text-[30px]">¡Hola {{ $firstName }}!</span>
                    <span id="top-current-date" class="mt-1.5 block text-[13px] font-medium capitalize text-gris/80">{{ now()->translatedFormat('d \d\e F \d\e Y') }}</span>
                </button>
            </div>

            <div class="ml-auto flex min-w-0 flex-wrap items-center justify-end gap-2">
                <div id="header-timer-pill" class="flex min-w-0 items-center gap-2 rounded-full border border-primario/20 bg-primario-suave/70 py-1.5 pl-4 pr-2 sm:gap-3">
                    <button
                        id="active-task-button"
                        type="button"
                        data-open-active-task
                        class="btn-ghost hidden min-h-0 max-w-40 truncate rounded-full px-0 py-0 text-[10px] tracking-[0.12em] text-primario sm:inline-flex"
                        aria-label="Ver tarea activa"
                    >
                        SIN TAREA ACTIVA
                    </button>
                    <div class="flex shrink-0 items-center gap-0.5 text-[11px] font-extrabold uppercase text-negro numeric sm:text-xs" role="timer" aria-label="Tiempo restante" aria-live="off">
                        <span id="timer-days">00</span><span class="mr-0.5 text-[9px] font-bold text-gris/70">D</span>:
                        <span id="timer-hours">00</span><span class="mr-0.5 text-[9px] font-bold text-gris/70">H</span>:
                        <span id="timer-minutes">00</span><span class="mr-0.5 text-[9px] font-bold text-gris/70">M</span>:
                        <span id="timer-seconds">00</span><span class="text-[9px] font-bold text-gris/70">S</span>
                    </div>
                    <span id="timer-caption" class="sr-only"></span>
                </div>

                <div class="flex min-w-0 items-center gap-2 rounded-full border border-primario/20 bg-white px-3 py-1.5 sm:gap-3 sm:pl-5">
                    <p id="header-reminder" class="hidden max-w-[260px] truncate text-[11px] font-medium text-gris lg:block">La meta “Hacer diseño” vence en menos de 2 días</p>
                    <button
                        id="notifications-button"
                        type="button"
                        data-open-notifications
                        aria-label="Ver avisos"
                        class="relative inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-primario transition hover:bg-primario-suave/60 focus:outline-none focus:ring-2 focus:ring-primario/30"
                    >
                        <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4.5-5.8V4a1.5 1.5 0 0 0-3 0v1.2A6 6 0 0 0 6 11v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 0 1-6 0"/>
                        </svg>
                        <span id="notification-count" class="absolute -right-0.5 -top-0.5 hidden h-4 min-w-4 items-center justify-center rounded-full bg-peligro px-1 text-[9px] font-extrabold text-white"></span>
                    </button>
                </div>
            </div>
        </header>

        @if (session('success') || ! empty($apiError))
            <div class="mx-auto w-full max-w-3xl px-5 pt-1 sm:px-8">
                @if (session('success'))
                    <p class="rounded-xl border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-800" role="status">
                        {{ session('success') }}
                    </p>
                @endif
                @if (! empty($apiError))
                    <p class="mt-2 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-2 text-sm font-medium text-yellow-800" role="alert">
                        {{ $apiError }}
                    </p>
                @endif
            </div>
        @endif
