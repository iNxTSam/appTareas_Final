        <div class="flex flex-1 justify-center">
            <section id="view-home" role="tabpanel" aria-labelledby="btn-nav-home" class="w-full flex-1">
                <div class="mx-auto flex w-full max-w-[1600px] flex-col px-4 pb-10 pt-2 sm:px-8">
                    <div class="order-1 flex justify-center">
                        <div class="flex flex-col items-center">
                            <p id="home-view-selector-label" class="mb-2 text-[9px] font-extrabold uppercase tracking-[0.16em] text-gris">Vistas</p>
                            <div class="flex items-center gap-1.5 rounded-2xl border border-primario/10 bg-white/90 p-1.5 shadow-md shadow-primario/5" role="group" aria-labelledby="home-view-selector-label">
                                <button
                                    type="button"
                                    data-home-mode="board"
                                    aria-label="Vista de tablero"
                                    aria-pressed="true"
                                    class="btn-icon-fill h-11 w-11 rounded-xl"
                                >
                                    <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="4" y="5" width="7" height="14" rx="1" stroke-width="2"/>
                                        <rect x="13" y="5" width="7" height="8" rx="1" stroke-width="2"/>
                                        <rect x="13" y="15" width="7" height="4" rx="1" stroke-width="2"/>
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    data-home-mode="list"
                                    aria-label="Vista de lista"
                                    aria-pressed="false"
                                    class="btn-icon-outline h-11 w-11 rounded-xl"
                                >
                                    <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-width="2" d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/>
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    data-home-mode="grid"
                                    aria-label="Vista en cuadrícula"
                                    aria-pressed="false"
                                    class="btn-icon-outline h-11 w-11 rounded-xl"
                                >
                                    <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="4" y="5" width="6" height="14" rx="1" stroke-width="2"/>
                                        <rect x="14" y="5" width="6" height="14" rx="1" stroke-width="2"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="order-3 mx-auto mt-5 grid w-full max-w-[1440px] grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-1 sm:mt-3 sm:gap-4">
                        <button
                            type="button"
                            data-date-shift="-1"
                            aria-label="Mostrar día anterior"
                            class="min-w-0 justify-self-end text-right text-[10px] font-semibold text-slate-300 transition hover:text-primario focus:outline-none focus:ring-2 focus:ring-primario/30 sm:text-sm"
                        >
                            <span id="previous-date-label" class="block truncate"></span>
                        </button>
                        <div class="min-w-0 text-center">
                            <h1 id="selected-date-label" class="whitespace-nowrap text-2xl font-extrabold leading-tight tracking-tight text-negro sm:text-4xl lg:text-5xl"></h1>
                            <p id="selected-date-stats" class="mt-1 flex flex-wrap items-center justify-center gap-x-1.5 text-[10px] font-medium sm:text-sm"></p>
                        </div>
                        <button
                            type="button"
                            data-date-shift="1"
                            aria-label="Mostrar día siguiente"
                            class="min-w-0 justify-self-start text-left text-[10px] font-semibold text-slate-300 transition hover:text-primario focus:outline-none focus:ring-2 focus:ring-primario/30 sm:text-sm"
                        >
                            <span id="next-date-label" class="block truncate"></span>
                        </button>
                    </div>

                    <div id="home-board-view" class="order-4 mx-auto mt-8 w-full max-w-[1440px]">
                        <div id="home-board-columns" class="carousel-mask draggable-carousel flex gap-4 overflow-x-auto overscroll-x-contain pb-3" tabindex="0" aria-label="Carrusel horizontal de tareas"></div>
                        <p id="home-board-empty" class="hidden py-12 text-center text-sm text-slate-500">No hay tareas para esta fecha.</p>
                    </div>

                    <div id="home-list-view" class="order-4 mx-auto mt-8 hidden w-full max-w-3xl">
                        <div id="home-list-items" class="space-y-3"></div>
                        <p id="home-list-empty" class="hidden py-12 text-center text-sm text-slate-500">No hay tareas para esta fecha.</p>
                    </div>

                    <div id="home-grid-view" class="order-4 mx-auto mt-8 hidden w-full max-w-6xl">
                        <div id="home-grid-items" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
                        <p id="home-grid-empty" class="hidden py-12 text-center text-sm text-slate-500">No hay tareas para esta fecha.</p>
                    </div>
                </div>
            </section>

            <section id="view-tasks" role="tabpanel" aria-labelledby="btn-nav-tasks" class="hidden w-full px-4 py-5 sm:px-8">
                <div class="mb-5 text-center">
                    <h1 class="text-2xl font-extrabold uppercase tracking-tight text-negro">Tareas</h1>
                    <p id="tasks-view-date" class="mt-2 text-base font-medium text-slate-400"></p>
                    <p id="tasks-view-count" class="mt-1 text-xs font-medium text-slate-400">0 tareas</p>
                </div>
                <div id="tasks-view-list" class="grid items-start gap-4 md:grid-cols-3"></div>
                <p id="tasks-view-empty" class="hidden py-10 text-center text-sm text-slate-500">Aún no tienes tareas. Usa + para agregar una.</p>
            </section>

            <section id="view-agenda" role="tabpanel" aria-labelledby="btn-nav-agenda" class="hidden w-full px-4 py-6 sm:px-8">
                <div class="mx-auto w-full max-w-3xl">
                    <div class="mb-5 text-center">
                        <h1 class="text-2xl font-extrabold uppercase tracking-tight text-negro">Calendario</h1>
                        <p id="agenda-selected-date" class="mt-2 text-base font-medium capitalize text-slate-400"></p>
                    </div>
                    <div class="rounded-3xl border border-primario/10 bg-white p-4 shadow-lg shadow-primario/5 sm:p-6">
                        <div class="mb-5 flex items-center justify-between">
                            <button
                                type="button"
                                data-calendar-shift="-1"
                                aria-label="Mes anterior"
                                class="btn-icon-outline"
                            >
                                <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"/>
                                </svg>
                            </button>
                            <h2 id="home-calendar-month" class="text-base font-extrabold capitalize text-negro sm:text-lg"></h2>
                            <button
                                type="button"
                                data-calendar-shift="1"
                                aria-label="Mes siguiente"
                                class="btn-icon-outline"
                            >
                                <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/>
                                </svg>
                            </button>
                        </div>
                        <div class="mb-2 grid grid-cols-7 text-center text-[10px] font-bold uppercase text-slate-400" aria-hidden="true">
                            <span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span><span>D</span>
                        </div>
                        <div id="home-calendar-days" class="grid grid-cols-7 gap-y-1"></div>
                    </div>
                    <div id="home-calendar-tasks" class="mt-6 space-y-3"></div>
                    <p id="home-calendar-empty" class="hidden py-8 text-center text-sm text-slate-500">No hay tareas para esta fecha.</p>
                </div>
            </section>
        </div>

