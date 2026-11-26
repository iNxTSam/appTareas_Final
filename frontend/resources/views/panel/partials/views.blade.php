        <div class="flex flex-1 justify-center">
            <section id="view-home" role="tabpanel" aria-labelledby="btn-nav-home" class="w-full flex-1">
                <div class="mx-auto flex w-full max-w-[1440px] flex-col px-4 pb-28 pt-6 sm:px-10">
                    <div class="flex justify-center">
                        <div class="flex items-center gap-1 rounded-full border border-primario/10 bg-white/70 p-1 shadow-sm" role="group" aria-label="Vistas del Home">
                            <button
                                type="button"
                                data-home-mode="board"
                                aria-label="Vista de tarjetas"
                                aria-pressed="true"
                                class="btn-icon-fill h-9 w-9"
                            >                                <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="6" width="12" height="12" rx="2" stroke-width="1.75"/>
                                    <rect x="17" y="8" width="4" height="8" rx="1" stroke-width="1.75"/>
                                </svg>
                            </button>
                            <button
                                type="button"
                                data-home-mode="gantt"
                                aria-label="Vista de diagrama Gantt"
                                aria-pressed="false"
                                class="btn-icon-outline h-9 w-9"
                            >
                                <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-width="1.75" d="M4 7h9M7 12h11M10 17h7M4 4v16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div id="home-date-header" class="mx-auto mt-8 grid w-full max-w-[1200px] grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 sm:gap-6">
                        <button
                            type="button"
                            data-date-shift="-1"
                            aria-label="Mostrar día anterior"
                            class="group inline-flex min-w-0 items-center justify-center gap-1.5 justify-self-end rounded-full border border-transparent px-2 py-1.5 text-xs font-bold text-gris transition hover:border-primario/20 hover:bg-white hover:text-primario focus:outline-none focus:ring-2 focus:ring-primario/20 sm:text-sm"
                        >
                            <svg aria-hidden="true" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"/>
                            </svg>
                            <span id="previous-date-label" class="hidden truncate sm:inline"></span>
                        </button>
                        <div class="min-w-0 px-1 text-center">
                            <h1 id="selected-date-label" class="whitespace-nowrap text-2xl font-extrabold leading-tight tracking-tight text-negro sm:text-[32px]"><span class="sr-only">Fecha seleccionada</span></h1>
                            <p id="selected-date-stats" class="mt-2 flex flex-wrap items-center justify-center gap-x-2 text-[12px] font-medium text-gris"></p>
                        </div>
                        <button
                            type="button"
                            data-date-shift="1"
                            aria-label="Mostrar día siguiente"
                            class="group inline-flex min-w-0 items-center justify-center gap-1.5 justify-self-start rounded-full border border-transparent px-2 py-1.5 text-xs font-bold text-gris transition hover:border-primario/20 hover:bg-white hover:text-primario focus:outline-none focus:ring-2 focus:ring-primario/20 sm:text-sm"
                        >
                            <span id="next-date-label" class="hidden truncate sm:inline"></span>
                            <svg aria-hidden="true" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                    </div>

                    <div id="home-overdue-banner" class="mx-auto mt-4 hidden w-full max-w-[1200px]">
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-peligro/30 bg-[#F9E3E3] px-4 py-3 sm:px-5">
                            <p id="home-overdue-text" class="min-w-0 text-xs font-bold text-[#A02A2A] sm:text-sm"></p>
                            <button
                                type="button"
                                id="home-overdue-link"
                                class="shrink-0 rounded-full bg-white px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[0.08em] text-[#A02A2A] transition hover:bg-white/80 focus:outline-none focus:ring-2 focus:ring-peligro/40"
                            >
                                Ver tareas
                            </button>
                        </div>
                    </div>

                    <div id="home-board-view" class="mt-8 w-full">
                        <div id="home-board-columns" class="carousel-mask draggable-carousel flex gap-5 overflow-x-auto overscroll-x-contain px-10 pb-4 pt-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" tabindex="0" role="region" aria-label="Carrusel horizontal de tareas"></div>
                        <div id="home-board-empty" class="hidden py-14">
                            <div class="mx-auto flex max-w-sm flex-col items-center gap-3 text-center">
                                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primario-suave text-primario">
                                    <svg aria-hidden="true" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="3" y="5" width="18" height="16" rx="2" stroke-width="1.75"/>
                                        <path stroke-linecap="round" stroke-width="1.75" d="M8 3v4M16 3v4M3 10h18"/>
                                    </svg>
                                </span>
                                <p class="text-sm font-medium text-gris">No hay tareas para esta fecha.</p>
                                <button type="button" data-open-new-task class="btn-outline px-4 py-2 text-[10px]">Agregar una tarea</button>
                            </div>
                        </div>
                    </div>

                    <div id="home-gantt-view" class="mt-6 hidden w-full">
                        <div class="flex items-end justify-between gap-4 px-2">
                            <p id="gantt-previous-month" class="hidden w-1/4 text-left text-sm font-semibold capitalize text-gris sm:block"></p>
                            <div class="flex-1 text-center">
                                <h1 id="gantt-current-month" class="text-[28px] font-extrabold capitalize leading-none tracking-tight text-negro sm:text-[32px]"></h1>
                                <p id="gantt-focus-date" class="mt-2 text-[12px] font-medium capitalize text-gris"></p>
                                <p class="mt-1 text-[10px] font-medium text-gris/80">Arrastra una barra para mover las fechas. También puedes usar las flechas izquierda y derecha.</p>
                            </div>
                            <p id="gantt-next-month" class="hidden w-1/4 text-right text-sm font-semibold capitalize text-gris sm:block"></p>
                        </div>

                        <div class="mt-5 overflow-x-auto pb-3">
                            <div id="gantt-grid" class="relative min-w-[980px]">
                                <div id="gantt-days" class="grid border-b border-borde/70 pb-2"></div>
                                <div class="relative mt-2">
                                    <div id="gantt-weekends" class="pointer-events-none absolute inset-0 grid" aria-hidden="true"></div>
                                    <div id="gantt-rows" class="relative space-y-2 py-2" role="list" aria-label="Diagrama Gantt de metas"></div>
                                    <div id="gantt-drop-line" class="pointer-events-none absolute bottom-0 top-0 z-30 hidden w-0.5 bg-primario/70" aria-hidden="true"></div>
                                    <div id="gantt-today-line" class="pointer-events-none absolute bottom-0 top-0 z-20 hidden w-px bg-primario" aria-hidden="true">
                                        <span class="absolute -left-[5px] -top-1 h-2.5 w-2.5 rounded-full bg-primario"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="view-tasks" role="tabpanel" aria-labelledby="btn-nav-tasks" class="hidden w-full px-4 pb-28 pt-4 sm:px-10">
                <div class="text-center">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-gris">Tareas</p>
                    <h1 class="mt-2 text-[28px] font-extrabold uppercase leading-none tracking-tight text-negro sm:text-[36px]">Tareas</h1>
                    <p id="tasks-view-date" class="mt-2 text-[13px] font-medium text-gris"></p>
                    <p id="tasks-view-count" class="sr-only">0 tareas</p>
                </div>
                <p class="mx-auto mt-6 hidden max-w-[1240px] text-center text-[10px] font-medium text-gris md:block">Arrastra una tarjeta entre columnas para cambiar su estado. También puedes enfocarla y usar las flechas izquierda y derecha.</p>
                <div id="tasks-view-list" class="mx-auto mt-4 grid w-full max-w-[1240px] items-start gap-5 md:grid-cols-3"></div>
                <p class="sr-only">Arrastra las tarjetas entre las columnas para cambiar su estado, o enfoca una tarjeta y usa las flechas izquierda y derecha.</p>
                <div id="tasks-view-empty" class="hidden py-10 text-center">
                    <p class="text-sm font-medium text-gris">Aún no tienes tareas.</p>
                    <button type="button" data-open-new-task class="btn-outline mt-4 px-4 py-2 text-[10px]">Agregar una tarea</button>
                </div>
            </section>

            <section id="view-agenda" role="tabpanel" aria-labelledby="btn-nav-agenda" class="hidden w-full px-4 pb-28 pt-4 sm:px-10">
                <div class="text-center">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-gris">Calendario</p>
                    <h1 class="mt-2 text-[28px] font-extrabold uppercase leading-none tracking-tight text-negro sm:text-[36px]">Calendario</h1>
                    <p id="agenda-selected-date" class="mt-2 text-[13px] font-medium capitalize text-gris"></p>
                </div>

                <div class="mx-auto mt-8 flex w-full max-w-[1240px] flex-col gap-6 xl:flex-row xl:items-start">
                    <div class="min-w-0 flex-1 rounded-2xl border-2 border-negro bg-white p-4 shadow-sm sm:p-6">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <h2 id="home-calendar-month" class="text-base font-extrabold uppercase tracking-[0.08em] text-negro sm:text-lg"><span class="sr-only">Mes del calendario</span></h2>
                            <div class="flex items-center gap-2">
                                <button type="button" data-calendar-shift="-1" aria-label="Mes anterior" class="btn-icon-outline h-9 w-9">
                                    <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"/>
                                    </svg>
                                </button>
                                <button type="button" data-calendar-shift="1" aria-label="Mes siguiente" class="btn-icon-outline h-9 w-9">
                                    <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/>
                                    </svg>
                                </button>
                                <button type="button" data-calendar-today class="btn-primary h-9 px-4 py-0 text-[10px]">Hoy</button>
                            </div>
                        </div>
                        <div class="mb-1 grid grid-cols-7 text-center text-[11px] font-bold text-gris" aria-hidden="true">
                            <span>D</span><span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span>
                        </div>
                        <div id="home-calendar-days" class="grid grid-cols-7 gap-1.5 sm:gap-2"></div>
                    </div>

                    <aside class="w-full shrink-0 rounded-2xl border-2 border-negro bg-white p-5 shadow-sm xl:w-[320px]">
                        <h2 id="agenda-day-title" class="text-[12px] font-extrabold uppercase tracking-[0.08em] text-negro">Miércoles 25 · Noviembre · 2026</h2>
                        <div id="home-calendar-tasks" class="mt-4 space-y-2"></div>
                        <div id="home-calendar-empty" class="mt-4 flex min-h-[120px] flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-borde bg-fondo/40 px-4 text-center">
                            <span class="h-2.5 w-2.5 rounded-full bg-proceso/60" aria-hidden="true"></span>
                            <p class="text-xs font-medium text-gris">Sin vencimientos este día</p>
                            <button type="button" data-open-new-task class="btn-outline px-3 py-1.5 text-[9px]">Agregar una tarea</button>
                        </div>
                    </aside>
                </div>
            </section>
        </div>
