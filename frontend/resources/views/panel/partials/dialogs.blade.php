        <dialog id="new-task-dialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-3xl border border-gris/10 bg-white p-0 shadow-2xl">
            <form id="task-form" class="space-y-4 p-6 sm:p-8">
                <div>
                    <h2 class="text-2xl font-extrabold text-negro">Nueva tarea</h2>
                    <p class="mt-1 text-sm text-slate-500">Agrega una tarea al espacio de {{ $displayName }}.</p>
                </div>
                <div>
                    <label for="task-category" class="mb-1.5 block text-sm font-bold text-negro">Categoría</label>
                    <input id="task-category" name="category" type="text" maxlength="80" placeholder="Ej. Diseño UI/UX" class="w-full rounded-2xl border border-gris/20 bg-fondo px-4 py-3 text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20">
                </div>
                <div>
                    <label for="task-team" class="mb-1.5 block text-sm font-bold text-negro">Equipo</label>
                    <input id="task-team" name="team" type="text" maxlength="120" placeholder="Ej. Diseño" class="w-full rounded-2xl border border-gris/20 bg-fondo px-4 py-3 text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20">
                </div>
                <div>
                    <label for="task-responsible" class="mb-1.5 block text-sm font-bold text-negro">Responsable</label>
                    <select
                        id="task-responsible"
                        name="responsible_id"
                        class="w-full rounded-2xl border border-gris/20 bg-fondo px-4 py-3 text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20"
                    >
                        <option value="">Sin asignar</option>
                        @foreach ($responsibles as $responsible)
                            <option value="{{ $responsible['id'] }}">{{ $responsible['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="task-title" class="mb-1.5 block text-sm font-bold text-negro">Tarea</label>
                    <input id="task-title" name="title" type="text" maxlength="120" required placeholder="Ej. Diseñar el dashboard" class="w-full rounded-2xl border border-gris/20 bg-fondo px-4 py-3 text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20">
                </div>
                <div>
                    <label for="task-description" class="mb-1.5 block text-sm font-bold text-negro">Descripción <span class="font-normal text-slate-500">(opcional)</span></label>
                    <textarea id="task-description" name="description" rows="2" maxlength="1000" class="w-full rounded-2xl border border-gris/20 bg-fondo px-4 py-3 text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20"></textarea>
                </div>
                <div>
                    <label for="task-status" class="mb-1.5 block text-sm font-bold text-negro">Estado</label>
                    <select id="task-status" name="status" class="w-full rounded-2xl border border-gris/20 bg-fondo px-4 py-3 text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20">
                        <option value="pendiente">Pendiente</option>
                        <option value="en_progreso">En progreso</option>
                        <option value="completada">Completada</option>
                    </select>
                </div>
                <div>
                    <label for="task-priority" class="mb-1.5 block text-sm font-bold text-negro">Prioridad</label>
                    <select id="task-priority" name="priority" class="w-full rounded-2xl border border-gris/20 bg-fondo px-4 py-3 text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20">
                        <option value="low">Baja</option>
                        <option value="medium" selected>Media</option>
                        <option value="high">Alta</option>
                    </select>
                </div>
                <div>
                    <label for="task-start-date" class="mb-1.5 block text-sm font-bold text-negro">Fecha de inicio <span class="font-normal text-slate-500">(opcional)</span></label>
                    <input id="task-start-date" name="start_date" type="date" class="w-full rounded-2xl border border-gris/20 bg-fondo px-4 py-3 text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20">
                </div>
                <div>
                    <label for="task-date" class="mb-1.5 block text-sm font-bold text-negro">Fecha límite <span class="font-normal text-slate-500">(opcional)</span></label>
                    <input id="task-date" name="due_date" type="date" class="w-full rounded-2xl border border-gris/20 bg-fondo px-4 py-3 text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20">
                </div>
                <div>
                    <label for="task-subtasks" class="mb-1.5 block text-sm font-bold text-negro">Subtareas <span class="font-normal text-slate-500">(una por línea)</span></label>
                    <textarea id="task-subtasks" name="subtasks" rows="3" placeholder="Planear la tarea&#10;Revisar el resultado" class="w-full rounded-2xl border border-gris/20 bg-fondo px-4 py-3 text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20"></textarea>
                    <p class="mt-1 text-[11px] leading-5 text-slate-500">Al abrir esta tarea podrás asignar un responsable a cada subtarea.</p>
                </div>
                <p class="text-xs leading-5 text-slate-500">Las tareas se guardan en tu cuenta.</p>
                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" data-close-new-task class="btn-outline">Cancelar</button>
                    <button type="submit" class="btn-primary">Agregar tarea</button>
                </div>
            </form>
        </dialog>

        <dialog id="notifications-dialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-3xl border border-gris/10 bg-white p-0 shadow-2xl">
            <div class="p-6 sm:p-8">
                <h2 class="text-2xl font-extrabold text-negro">Avisos</h2>
                <p class="mt-1 text-sm text-slate-500">Tareas vencidas o con fecha límite próxima.</p>
                <div id="notifications-list" class="mt-5 max-h-80 space-y-3 overflow-y-auto"></div>
                <p id="notifications-empty" class="hidden py-8 text-center text-sm text-slate-500">No tienes avisos por ahora.</p>
                <div class="mt-5 flex justify-end">
                    <button type="button" data-close-notifications class="btn-outline">Cerrar</button>
                </div>
            </div>
        </dialog>

        <dialog id="profile-dialog" class="m-auto w-[calc(100%-2rem)] max-w-sm rounded-3xl border border-gris/10 bg-white p-0 shadow-2xl">
            <div class="p-6 sm:p-8">
                <h2 class="text-xl font-extrabold text-negro">Tu cuenta</h2>
                <p class="mt-2 text-sm text-slate-600">Sesión iniciada como <span class="font-bold text-negro">{{ $displayName }}</span>.</p>
                @if ($email !== '')
                    <p class="mt-1 break-all text-sm text-slate-500">{{ $email }}</p>
                @endif
                <p class="mt-4 text-sm leading-6 text-slate-500">Para iniciar sesión con otra cuenta, primero cierra esta sesión.</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-6">
                    @csrf
                    <button type="submit" class="btn-primary w-full">
                        Cerrar sesión y cambiar de cuenta
                    </button>
                </form>
                <button type="button" data-close-profile class="btn-outline mt-3 w-full">Volver</button>
            </div>
        </dialog>

        <dialog id="task-detail-modal" class="m-auto w-[calc(100%-2rem)] max-w-xl border-0 bg-transparent p-0 shadow-none">
            <div class="space-y-2">
                <section class="relative overflow-hidden rounded-2xl border border-gris/30 bg-[#FFFAFA] shadow-lg">
                    <button
                        type="button"
                        data-close-detail
                        aria-label="Cerrar información de tarea"
                        class="btn-icon-fill absolute right-3 top-3 h-8 w-8 text-lg leading-none"
                    >
                        ×
                    </button>
                    <div class="px-5 pb-4 pt-5 sm:px-6">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 pr-10">
                            <p id="detail-project-category" class="text-xs font-extrabold uppercase text-primario"></p>
                            <span id="detail-subtasks-counter" class="text-[10px] font-medium text-gris"></span>
                        </div>
                        <h2 id="detail-task-title" class="mt-2 break-words text-xl font-extrabold uppercase leading-tight text-negro sm:text-2xl"></h2>
                        <p id="detail-task-description" class="mt-2 whitespace-pre-wrap text-sm leading-6 text-gris"></p>
                    </div>
                    <div class="grid grid-cols-2 border-t border-gris/15 text-[10px] sm:grid-cols-[1fr_1fr_1fr_1fr_0.9fr]">
                        <div class="border-r border-gris/15 px-5 py-3 sm:px-4">
                            <span class="block text-slate-500">Estado</span>
                            <span id="detail-status-text" class="mt-1 block font-bold text-negro"></span>
                        </div>
                        <div class="border-b border-gris/15 px-5 py-3 sm:border-b-0 sm:border-r sm:px-4">
                            <span class="block text-slate-500">Fecha inicio</span>
                            <span id="detail-start-date" class="mt-1 block font-bold text-negro"></span>
                        </div>
                        <div class="border-r border-gris/15 px-5 py-3 sm:px-4">
                            <span class="block text-slate-500">Fecha límite</span>
                            <span id="detail-end-date" class="mt-1 block font-bold text-negro"></span>
                        </div>
                        <div class="border-b border-gris/15 px-5 py-3 sm:border-b-0 sm:border-r sm:px-4">
                            <label for="detail-responsible-select" class="block text-slate-500">Responsable</label>
                            <select
                                id="detail-responsible-select"
                                class="mt-1 w-full rounded-lg border border-gris/20 bg-white px-2 py-1 text-[10px] font-bold text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20"
                            >
                                <option value="">Sin asignar</option>
                                @foreach ($responsibles as $responsible)
                                    <option value="{{ $responsible['id'] }}">{{ $responsible['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col justify-center bg-[#91C9AD] px-5 py-3 text-[#173A2D] sm:px-4">
                            <span class="block">Avance</span>
                            <span id="detail-percent-value" class="mt-0.5 block text-lg font-extrabold leading-tight"></span>
                        </div>
                    </div>
                </section>

                <section class="flex min-h-[280px] flex-col rounded-2xl border border-gris/30 bg-[#FFFAFA] px-5 py-5 shadow-lg sm:min-h-[315px] sm:px-6">
                    <div class="mb-2 flex items-center justify-between border-b border-gris/20 pb-2">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gris">Subtareas</h3>
                        <span id="detail-subtasks-list-count" class="text-[10px] font-medium text-gris"></span>
                    </div>
                    <div id="detail-subtasks-list" class="max-h-56 divide-y divide-gris/15 overflow-y-auto"></div>
                    <p id="detail-subtasks-empty" class="hidden py-8 text-center text-sm text-slate-500">Todavía no hay tareas en esta lista.</p>
                    <button
                        type="button"
                        data-open-subtask-form
                        class="btn-ghost mt-auto self-center px-4 py-2 text-[10px]"
                    >
                        Agregar tarea
                    </button>
                    <form id="detail-subtask-form" class="mt-3 hidden flex flex-col gap-2 sm:flex-row">
                        <input
                            id="detail-subtask-input"
                            type="text"
                            maxlength="160"
                            required
                            aria-label="Nombre de la nueva tarea"
                            placeholder="Escribe una tarea"
                            class="min-w-0 flex-1 rounded-xl border border-primario/20 bg-white px-3 py-2 text-sm text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/15"
                        >
                        <label for="detail-subtask-responsible" class="sr-only">Responsable de la subtarea</label>
                        <select
                            id="detail-subtask-responsible"
                            class="min-w-0 rounded-xl border border-primario/20 bg-white px-3 py-2 text-xs text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/15 sm:max-w-36"
                        >
                            <option value="">Sin asignar</option>
                            @foreach ($responsibles as $responsible)
                                <option value="{{ $responsible['id'] }}">{{ $responsible['name'] }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-primary px-4 py-2 text-[10px]">Agregar</button>
                        <button type="button" data-cancel-add-subtask class="btn-outline px-4 py-2 text-[10px]">Cancelar</button>
                    </form>
                </section>
            </div>
        </dialog>
