        <nav class="mt-4 flex w-full flex-col items-center justify-center pb-2 sm:mt-6" role="tablist" aria-label="Secciones de la aplicación">
            <div class="flex items-center gap-7 sm:gap-12">
                <button
                    id="btn-nav-home"
                    type="button"
                    role="tab"
                    aria-selected="true"
                    aria-controls="view-home"
                    data-view-target="home"
                    aria-label="Inicio"
                    class="flex items-center justify-center rounded-2xl focus:outline-none focus:ring-2 focus:ring-primario/40"
                >
                    <span class="sr-only">HOME</span>
                    <span data-view-icon class="flex h-14 w-14 items-center justify-center rounded-2xl border-4 border-negro bg-primario text-white shadow-lg shadow-primario/20 transition-all sm:h-[72px] sm:w-[72px]">
                        <svg aria-hidden="true" class="h-5 w-5 sm:h-6 sm:w-6" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 0 0-1.414 0l-7 7a1 1 0 0 0 1.414 1.414L4 10.414V17a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-6.586l.293.293a1 1 0 0 0 1.414-1.414l-7-7Z"/>
                        </svg>
                    </span>
                </button>
                <button
                    id="btn-nav-tasks"
                    type="button"
                    role="tab"
                    aria-selected="false"
                    aria-controls="view-tasks"
                    data-view-target="tasks"
                    aria-label="Tareas"
                    class="flex items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-primario/40"
                >
                    <span class="sr-only">TAREAS</span>
                    <span data-view-icon class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-slate-400 bg-white text-primario shadow-sm transition-all sm:h-[72px] sm:w-[72px]">
                        <svg aria-hidden="true" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4"/>
                        </svg>
                    </span>
                </button>
                <button
                    id="btn-nav-agenda"
                    type="button"
                    role="tab"
                    aria-selected="false"
                    aria-controls="view-agenda"
                    data-view-target="agenda"
                    aria-label="Calendario"
                    class="flex items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-primario/40"
                >
                    <span class="sr-only">CALENDARIO</span>
                    <span data-view-icon class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-slate-400 bg-white text-primario shadow-sm transition-all sm:h-[72px] sm:w-[72px]">
                        <svg aria-hidden="true" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                        </svg>
                    </span>
                </button>
            </div>
            <div class="mt-4 h-0.5 w-4 rounded-full bg-slate-200"></div>
        </nav>

