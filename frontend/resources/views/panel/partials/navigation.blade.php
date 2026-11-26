        <nav id="main-nav" class="mx-auto mt-7 w-full max-w-[1440px] px-4 sm:px-10" aria-label="Secciones de la aplicación">
            <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4">
                <div role="tablist" aria-label="Navegación principal" class="contents">
                <button
                    id="btn-nav-home"
                    type="button"
                    role="tab"
                    aria-selected="true"
                    aria-controls="view-home"
                    data-view-target="home"
                    class="group relative flex h-[130px] w-[132px] flex-col items-center justify-center gap-3 rounded-2xl border border-primario bg-primario-suave text-primario shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primario/40 focus:ring-offset-2 focus:ring-offset-fondo sm:w-[152px]"
                >
                    <span data-nav-badge class="absolute -top-2.5 right-3 rounded-full bg-primario px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-[0.08em] text-white">Actual</span>
                    <span data-view-icon class="flex items-center justify-center">
                        <svg aria-hidden="true" class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m3 10 9-7 9 7v9a2 2 0 0 1-2 2h-4v-6H9v6H5a2 2 0 0 1-2-2v-9Z"/>
                        </svg>
                    </span>
                    <span class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-negro">Home</span>
                </button>

                <button
                    id="btn-nav-tasks"
                    type="button"
                    role="tab"
                    aria-selected="false"
                    aria-controls="view-tasks"
                    data-view-target="tasks"
                    class="group relative flex h-[130px] w-[132px] flex-col items-center justify-center gap-3 rounded-2xl border border-transparent bg-white text-primario shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primario/40 focus:ring-offset-2 focus:ring-offset-fondo sm:w-[152px]"
                >
                    <span data-nav-badge class="absolute -top-2.5 right-3 hidden rounded-full bg-primario px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-[0.08em] text-white">Actual</span>
                    <span data-view-icon class="flex items-center justify-center">
                        <svg aria-hidden="true" class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 4v16m0-16 9 3-2.5 3L15 13 6 10"/>
                        </svg>
                    </span>
                    <span class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-negro">Tareas</span>
                </button>

                <button
                    id="btn-nav-agenda"
                    type="button"
                    role="tab"
                    aria-selected="false"
                    aria-controls="view-agenda"
                    data-view-target="agenda"
                    class="group relative flex h-[130px] w-[132px] flex-col items-center justify-center gap-3 rounded-2xl border border-transparent bg-white text-primario shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primario/40 focus:ring-offset-2 focus:ring-offset-fondo sm:w-[152px]"
                >
                    <span data-nav-badge class="absolute -top-2.5 right-3 hidden rounded-full bg-primario px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-[0.08em] text-white">Actual</span>
                    <span data-view-icon class="flex items-center justify-center">
                        <svg aria-hidden="true" class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Zm3-3v6m8-6v6M3 11h18"/>
                        </svg>
                    </span>
                    <span class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-negro">Calendario</span>
                </button>
                </div>

                <button
                    id="btn-nav-profile"
                    type="button"
                    data-open-profile
                    aria-label="Abrir opciones de cuenta y sesión"
                    class="group relative flex h-[130px] w-[132px] flex-col items-center justify-center gap-3 rounded-2xl border border-transparent bg-white text-primario shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primario/40 focus:ring-offset-2 focus:ring-offset-fondo sm:w-[152px]"
                >
                    <span class="flex items-center justify-center">
                        <svg aria-hidden="true" class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="8" r="4" stroke-width="1.75"/>
                            <path stroke-linecap="round" stroke-width="1.75" d="M4 21a8 8 0 0 1 16 0"/>
                        </svg>
                    </span>
                    <span class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-negro">Perfil</span>
                </button>
            </div>
        </nav>
