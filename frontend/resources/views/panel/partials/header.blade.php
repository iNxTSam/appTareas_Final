        <header class="mx-auto flex w-full max-w-[1440px] flex-wrap items-center justify-between gap-2 px-4 py-4 sm:gap-3 sm:px-8">
            <button
                type="button"
                data-open-profile
                class="flex min-w-0 flex-1 items-center gap-2 rounded-2xl border border-primario/15 bg-white px-2.5 py-2 text-left shadow-sm transition hover:border-primario/40 focus:outline-none focus:ring-2 focus:ring-primario/40 sm:flex-none sm:gap-3 sm:px-3"
            >
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primario-suave text-primario">
                    <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM4 21a8 8 0 0 1 16 0"/>
                    </svg>
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-xs font-extrabold uppercase tracking-wide text-negro sm:text-sm"><span class="hidden sm:inline">Mi cuenta · </span>{{ $displayName }}</span>
                    <span class="block truncate text-[9px] font-semibold text-primario sm:text-[10px]"><span class="sm:hidden">Ver perfil</span><span class="hidden sm:inline">Ver perfil y cerrar sesión</span></span>
                    <span id="top-current-date" class="mt-0.5 hidden text-[9px] font-medium text-slate-500 sm:block sm:text-[10px]"></span>
                </span>
            </button>

            <div class="ml-auto flex min-w-0 items-center gap-2 sm:gap-3">
                <div id="header-timer-pill" class="flex min-w-0 items-center gap-2 rounded-full border border-primario/30 bg-primario-suave py-1.5 pl-3 pr-1.5 shadow-sm sm:gap-4 sm:pl-5">
                    <button
                        id="active-task-button"
                        type="button"
                        data-open-active-task
                        class="btn-ghost hidden min-h-0 max-w-24 truncate rounded-full px-0 py-0 text-[9px] text-gris sm:inline-flex sm:max-w-36 sm:text-[10px]"
                    >
                        SIN TAREA ACTIVA
                    </button>
                    <div class="flex shrink-0 items-center gap-0.5 font-sans text-[10px] font-extrabold text-negro sm:gap-1 sm:text-xs" role="timer" aria-label="Tiempo restante" aria-live="off">
                        <span id="timer-days">00</span><span class="mr-0.5 text-[8px] font-sans text-gris sm:text-[9px]">D</span>:
                        <span id="timer-hours">00</span><span class="mr-0.5 text-[8px] font-sans text-gris sm:text-[9px]">H</span>:
                        <span id="timer-minutes">00</span><span class="mr-0.5 text-[8px] font-sans text-gris sm:text-[9px]">M</span>:
                        <span id="timer-seconds">00</span><span class="text-[8px] font-sans text-gris sm:text-[9px]">S</span>
                    </div>
                    <span id="timer-caption" class="sr-only"></span>
                    <button
                        id="notifications-button"
                        type="button"
                        data-open-notifications
                        aria-label="Ver avisos"
                        class="btn-icon-outline bell-pulse relative"
                    >
                        <svg aria-hidden="true" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6Zm0 16a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3Z"/>
                        </svg>
                        <span id="notification-count" class="absolute -right-1 -top-1 hidden h-4 min-w-4 items-center justify-center rounded-full bg-badgeGold px-1 text-[9px] font-extrabold text-negro"></span>
                    </button>
                </div>

                <button
                    type="button"
                    data-open-new-task
                    aria-label="Agregar una tarea"
                    class="btn-icon-fill text-xl leading-none sm:h-11 sm:w-11"
                >
                    <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                    </svg>
                </button>
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

