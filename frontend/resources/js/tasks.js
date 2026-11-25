const TASK_STATUSES = {
    INICIADO: 'Pendiente',
    PROCESO: 'En progreso',
    TERMINADOS: 'Completada',
};

const API_TASK_STATUSES = {
    INICIADO: 'pendiente',
    PROCESO: 'en_progreso',
    TERMINADOS: 'completada',
};

const TASK_PRIORITIES = ['low', 'medium', 'high'];
const NOTIFICATION_WINDOW_MS = 24 * 60 * 60 * 1000;

function createTaskId() {
    if (typeof window.crypto?.randomUUID === 'function') {
        return window.crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

function toDateString(date) {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
}

function normalizeDate(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const rawDate = String(value);

    if (rawDate.toLowerCase() === 'por definir') {
        return '';
    }

    if (rawDate.toUpperCase() === 'HOY') {
        return toDateString(new Date());
    }

    const shortDate = /^(\d{2})-(\d{2})-(\d{4})$/.exec(rawDate);
    const date = shortDate
        ? `${shortDate[3]}-${shortDate[2]}-${shortDate[1]}`
        : rawDate.slice(0, 10);

    if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) {
        return null;
    }

    const [year, month, day] = date.split('-').map(Number);
    const parsedDate = new Date(year, month - 1, day);

    return parsedDate.getFullYear() === year
        && parsedDate.getMonth() === month - 1
        && parsedDate.getDate() === day
        ? date
        : null;
}

function normalizeSubtasks(value) {
    if (value === null || value === undefined) {
        return [];
    }

    if (!Array.isArray(value)) {
        return null;
    }

    const subtasks = value.map((subtask) => {
        if (typeof subtask === 'string') {
            return {
                text: subtask.trim(),
                done: false,
                responsible_id: '',
                responsible_name: '',
            };
        }

        if (
            subtask === null
            || typeof subtask !== 'object'
            || typeof subtask.text !== 'string'
            || typeof subtask.done !== 'boolean'
        ) {
            return null;
        }

        const responsibleId = subtask.responsible_id === null
            || subtask.responsible_id === undefined
            || subtask.responsible_id === ''
            ? ''
            : String(subtask.responsible_id);

        return {
            text: subtask.text,
            done: subtask.done,
            responsible_id: responsibleId,
            responsible_name: typeof subtask.responsible_name === 'string' ? subtask.responsible_name : '',
        };
    });

    return subtasks.some((subtask) => subtask === null) ? null : subtasks;
}

function calculateProgress(subtasks, status) {
    if (status === 'TERMINADOS') {
        return 100;
    }

    if (subtasks.length === 0) {
        return 0;
    }

    const completedCount = subtasks.filter((subtask) => subtask.done).length;

    return Math.round((completedCount / subtasks.length) * 100);
}

function normalizeTaskStatus(value) {
    const status = String(value || '').toLowerCase();

    return ({
        pendiente: 'INICIADO',
        pending: 'INICIADO',
        iniciado: 'INICIADO',
        en_progreso: 'PROCESO',
        in_progress: 'PROCESO',
        proceso: 'PROCESO',
        completada: 'TERMINADOS',
        completed: 'TERMINADOS',
        terminado: 'TERMINADOS',
        terminados: 'TERMINADOS',
    })[status] || null;
}

function normalizeTask(value) {
    if (value === null || typeof value !== 'object' || !['string', 'number'].includes(typeof value.id)) {
        return null;
    }

    const status = typeof value.status === 'string'
        ? normalizeTaskStatus(value.status)
        : value.completed === true
            ? 'TERMINADOS'
            : 'INICIADO';

    if (!status || typeof value.title !== 'string') {
        return null;
    }

    const subtasks = normalizeSubtasks(value.subtasks);
    const startDate = normalizeDate(value.start_date ?? value.startDate ?? value.start);
    const dueDate = normalizeDate(value.due_date ?? value.dueDate ?? value.end);
    const priority = String(value.priority ?? (value.is_important === true || value.important === true ? 'high' : 'medium')).toLowerCase();
    const responsibleId = value.responsible_id === null || value.responsible_id === undefined || value.responsible_id === ''
        ? ''
        : String(value.responsible_id);
    const progressValue = value.progress_percentage ?? value.percent ?? calculateProgress(subtasks || [], status);
    const progress = Number(progressValue);

    if (
        subtasks === null
        || startDate === null
        || dueDate === null
        || !TASK_PRIORITIES.includes(priority)
        || !Number.isFinite(progress)
        || progress < 0
        || progress > 100
    ) {
        return null;
    }

    return {
        id: String(value.id),
        category: String(value.category ?? 'GENERAL'),
        title: value.title,
        description: String(value.description ?? ''),
        status,
        priority,
        team: typeof value.team === 'string'
            ? value.team
            : typeof value.team?.name === 'string'
                ? value.team.name
                : '',
        is_important: priority === 'high' || value.is_important === true || value.important === true,
        subtasks,
        progress_percentage: Math.round(progress),
        start_date: startDate,
        due_date: dueDate,
        responsible_id: responsibleId,
        responsible_name: typeof value.responsible_name === 'string' ? value.responsible_name : '',
    };
}

function normalizeTaskCollection(payload) {
    const values = Array.isArray(payload)
        ? payload
        : Array.isArray(payload?.data)
            ? payload.data
            : Array.isArray(payload?.tasks)
                ? payload.tasks
                : null;

    if (values === null) {
        throw new Error('El servidor devolvió una lista de tareas con formato inválido.');
    }

    const normalized = values.map(normalizeTask);

    if (normalized.some((task) => task === null)) {
        throw new Error('El servidor devolvió una tarea con datos inválidos.');
    }

    return normalized;
}

function serializeTaskForApi(task) {
    return {
        category: task.category,
        title: task.title,
        description: task.description,
        status: API_TASK_STATUSES[task.status],
        priority: task.priority,
        team: task.team,
        start_date: task.start_date || null,
        due_date: task.due_date || null,
        responsible_id: task.responsible_id || null,
        responsible_name: task.responsible_name || null,
        subtasks: task.subtasks,
    };
}

async function requestTaskApi(method, url, data = null) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const headers = { Accept: 'application/json' };
    const options = {
        method,
        credentials: 'same-origin',
        headers,
    };

    if (!['GET', 'HEAD'].includes(method)) {
        headers['X-CSRF-TOKEN'] = csrfToken || '';
    }

    if (data !== null) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(data);
    }

    let response;

    try {
        response = await window.fetch(url, options);
    } catch {
        throw new Error('No se pudo conectar con el servidor de tareas.');
    }

    let payload = null;

    if (response.status !== 204) {
        try {
            payload = await response.json();
        } catch {
            throw new Error('El servidor devolvió una respuesta de tareas inválida.');
        }
    }

    if (!response.ok) {
        const validationMessage = payload?.errors && typeof payload.errors === 'object'
            ? Object.values(payload.errors).flat().find((message) => typeof message === 'string')
            : null;
        const message = validationMessage || payload?.message;

        throw new Error(typeof message === 'string' ? message : 'No se pudo completar la operación de tareas.');
    }

    return payload;
}

function formatDueDate(value) {
    if (!value) {
        return 'Sin fecha límite';
    }

    const [year, month, day] = value.split('-').map(Number);

    return new Intl.DateTimeFormat('es', { dateStyle: 'medium' }).format(new Date(year, month - 1, day));
}

function dateFromString(value) {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}

function shiftDate(value, days) {
    const date = dateFromString(value);
    date.setDate(date.getDate() + days);

    return toDateString(date);
}

function startOfMonth(value) {
    const date = dateFromString(value);

    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function formatCarouselDate(value, includeYear = true) {
    const date = dateFromString(value);
    const day = String(date.getDate()).padStart(2, '0');
    const month = new Intl.DateTimeFormat('es', { month: 'long' }).format(date);
    const formattedMonth = month.charAt(0).toUpperCase() + month.slice(1);

    return includeYear
        ? `${day} - ${formattedMonth} - ${date.getFullYear()}`
        : `${day} - ${formattedMonth}`;
}

function formatAccessibleDate(value) {
    const date = dateFromString(value);

    return new Intl.DateTimeFormat('es', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

function taskOccursOnDate(task, date) {
    const firstDate = task.start_date || task.due_date;
    const lastDate = task.due_date || task.start_date;

    if (!firstDate || !lastDate) {
        return false;
    }

    return date >= (firstDate < lastDate ? firstDate : lastDate)
        && date <= (firstDate > lastDate ? firstDate : lastDate);
}

function deadlineTimestamp(dateString) {
    if (!dateString) {
        return null;
    }

    const [year, month, day] = dateString.split('-').map(Number);

    return new Date(year, month - 1, day, 23, 59, 59).getTime();
}

function createTaskViewCard(task) {
    const button = document.createElement('button');
    button.type = 'button';
    button.dataset.taskViewId = task.id;
    button.className = 'w-full rounded-2xl border border-gris/10 bg-white p-4 text-left shadow-sm transition hover:border-primario/30 focus:outline-none focus:ring-2 focus:ring-primario/30';

    const header = document.createElement('div');
    header.className = 'flex items-start justify-between gap-3';

    const titleGroup = document.createElement('div');
    titleGroup.className = 'min-w-0';

    const category = document.createElement('span');
    category.className = 'block truncate text-[10px] font-extrabold uppercase tracking-widest text-slate-600';
    category.textContent = task.category;

    const title = document.createElement('span');
    title.className = 'mt-1 block break-words font-bold text-negro';
    title.textContent = task.title;
    titleGroup.append(category, title);

    const status = document.createElement('span');
    status.className = 'shrink-0 rounded-full bg-primario-suave px-2.5 py-1 text-[10px] font-bold uppercase text-primario';
    status.textContent = TASK_STATUSES[task.status];
    header.append(titleGroup, status);

    const footer = document.createElement('span');
    footer.className = 'mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gris';

    const dueDate = document.createElement('span');
    dueDate.textContent = `Fecha límite: ${formatDueDate(task.due_date)}`;
    footer.append(dueDate);

    if (task.is_important) {
        const important = document.createElement('span');
        important.className = 'rounded-full bg-badgeGold px-2 py-0.5 text-[9px] font-bold uppercase text-negro';
        important.textContent = 'Importante';
        footer.append(important);
    }

    const progress = document.createElement('span');
    progress.className = 'ml-auto font-bold text-primario';
    progress.textContent = `${task.progress_percentage}%`;
    footer.append(progress);

    const responsible = document.createElement('span');
    responsible.className = 'w-full truncate text-[10px] font-semibold text-gris';
    responsible.textContent = `Responsable: ${task.responsible_name || 'Sin asignar'}`;
    footer.append(responsible);

    button.append(header, footer);

    return button;
}

function createBoardTaskCard(task, isCarousel = true) {
    const card = document.createElement('button');
    card.type = 'button';
    card.dataset.taskViewId = task.id;
    card.className = `group relative ${isCarousel ? 'w-[min(82vw,310px)] min-w-[270px] shrink-0' : 'w-full'} rounded-2xl border border-primario-oscuro bg-primario p-4 pb-3 text-left text-white shadow-[0_12px_24px_rgba(78,27,35,0.16)] transition hover:-translate-y-0.5 hover:shadow-[0_18px_30px_rgba(78,27,35,0.22)] focus:outline-none focus:ring-2 focus:ring-primario/40`;

    const header = document.createElement('div');
    header.className = 'mb-3 flex items-start justify-between gap-3';

    const statusGroup = document.createElement('div');
    statusGroup.className = 'flex flex-wrap items-center gap-1';

    const statusBadge = document.createElement('span');
    statusBadge.className = 'inline-flex items-center rounded-full border border-white/20 bg-[#EAF8F1] px-2 py-0.5 text-[8px] font-extrabold uppercase tracking-[0.15em] text-[#1A7A54]';
    statusBadge.textContent = TASK_STATUSES[task.status];

    const priorityBadge = document.createElement('span');
    priorityBadge.className = 'inline-flex items-center rounded-full border border-white/20 bg-white/10 px-2 py-0.5 text-[8px] font-extrabold uppercase tracking-[0.15em] text-white';
    priorityBadge.textContent = task.priority === 'high'
        ? 'Alta'
        : task.priority === 'low'
            ? 'Baja'
            : 'Media';

    statusGroup.append(statusBadge, priorityBadge);

    const title = document.createElement('span');
    title.className = 'mt-3 block text-[19px] font-extrabold uppercase leading-tight tracking-tight text-white';
    title.textContent = task.title;

    const team = document.createElement('p');
    team.className = 'mt-1 truncate text-[9px] font-semibold uppercase tracking-wide text-white/75';
    team.textContent = `Equipo: ${task.team || 'Sin equipo'}`;

    const meta = document.createElement('div');
    meta.className = 'mt-3 flex items-center justify-between text-[9px] font-bold uppercase tracking-[0.14em] text-white/80';

    const taskCount = document.createElement('span');
    const completedSubtasks = task.subtasks.filter((subtask) => subtask.done).length;
    taskCount.textContent = `Tareas ${completedSubtasks}/${task.subtasks.length}`;

    const percentage = document.createElement('span');
    percentage.textContent = `${task.progress_percentage}%`;
    meta.append(taskCount, percentage);

    const progressTrack = document.createElement('div');
    progressTrack.className = 'mt-2 h-2.5 overflow-hidden rounded-full bg-white/20';

    const progressFill = document.createElement('div');
    progressFill.className = 'h-full rounded-full bg-[#7FE0B2]';
    progressFill.style.width = `${task.progress_percentage}%`;
    progressTrack.append(progressFill);

    const dateGrid = document.createElement('div');
    dateGrid.className = 'mt-4 grid grid-cols-2 gap-2';

    const createDateCell = (label, date) => {
        const cell = document.createElement('div');
        cell.className = 'rounded-xl border border-white/15 bg-[#F5F2F1] px-2 py-2 text-center text-[10px] font-bold uppercase text-[#2B2B36]';

        const caption = document.createElement('span');
        caption.className = 'mb-1 block text-[9px] font-bold uppercase tracking-[0.14em] text-slate-600';
        caption.textContent = label;

        const value = document.createElement('span');
        value.className = 'block whitespace-nowrap';
        value.textContent = formatDueDate(date).split(' ').slice(0, 2).join(' ');
        cell.append(caption, value);

        return cell;
    };

    const startDate = createDateCell('Inicio', task.start_date);
    const endDate = createDateCell('Fin', task.due_date);

    dateGrid.append(startDate, endDate);

    const footer = document.createElement('div');
    footer.className = 'mt-4 flex items-center justify-between';

    const assignment = document.createElement('span');
    assignment.className = 'flex min-w-0 items-center gap-2';

    const avatar = document.createElement('span');
    avatar.className = 'flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-white/40 bg-white/90 text-[9px] font-extrabold text-primario';
    avatar.textContent = task.responsible_name
        ? task.responsible_name.split(/\s+/).slice(0, 2).map((part) => part.charAt(0).toUpperCase()).join('')
        : '—';

    const responsibleName = document.createElement('span');
    responsibleName.className = 'max-w-[150px] truncate text-[9px] font-bold uppercase tracking-[0.08em] text-white/85';
    responsibleName.textContent = task.responsible_name || 'Sin asignar';
    assignment.append(avatar, responsibleName);

    const action = document.createElement('span');
    action.className = 'flex h-9 w-9 items-center justify-center rounded-full bg-[#F6F7F5] text-xl font-bold text-[#7D2F3E] shadow-sm';
    action.textContent = '→';

    footer.append(assignment, action);
    header.append(statusGroup);
    card.append(header, title, team, meta, progressTrack, dateGrid, footer);

    return card;
}

function setupTaskHeader(root) {
    const tasksUrl = root.dataset.tasksUrl;
    const taskUrlTemplate = root.dataset.taskUrlTemplate;
    const taskForm = root.querySelector('#task-form');
    const taskTitleInput = taskForm.querySelector('#task-title');
    const taskSubmitButton = taskForm.querySelector('#task-submit');
    const taskFormTitle = taskForm.querySelector('#task-form-title');
    const taskFormSubtitle = taskForm.querySelector('#task-form-subtitle');
    const taskFormError = taskForm.querySelector('#task-form-error');
    const taskFormSubtitleDefault = taskFormSubtitle.textContent;
    const newTaskButtons = [...root.querySelectorAll('[data-open-new-task]')];
    const responsibleSelect = taskForm.querySelector('#task-responsible');
    const storageError = root.querySelector('#task-storage-error');
    const storageErrorMessage = root.querySelector('#task-storage-error-message');
    const retryButton = root.querySelector('#task-retry');
    const loadingIndicator = root.querySelector('#task-loading');
    const toast = root.querySelector('#task-toast');
    const toastMessage = root.querySelector('#task-toast-message');
    const statusMessage = root.querySelector('#task-status-announcer');
    const newTaskDialog = root.querySelector('#new-task-dialog');
    const notificationsDialog = root.querySelector('#notifications-dialog');
    const profileDialog = root.querySelector('#profile-dialog');
    const detailDialog = root.querySelector('#task-detail-modal');
    const detailResponsibleSelect = detailDialog.querySelector('#detail-responsible-select');
    const detailFeedback = detailDialog.querySelector('#detail-feedback');
    const activeTaskButton = root.querySelector('[data-open-active-task]');
    const viewButtons = [...root.querySelectorAll('[data-view-target]')];
    const views = {
        home: root.querySelector('#view-home'),
        tasks: root.querySelector('#view-tasks'),
        agenda: root.querySelector('#view-agenda'),
    };
    const homeModeButtons = [...root.querySelectorAll('[data-home-mode]')];
    const homeModeViews = {
        board: root.querySelector('#home-board-view'),
        list: root.querySelector('#home-list-view'),
        grid: root.querySelector('#home-grid-view'),
    };
    const timerElements = {
        days: root.querySelector('#timer-days'),
        hours: root.querySelector('#timer-hours'),
        minutes: root.querySelector('#timer-minutes'),
        seconds: root.querySelector('#timer-seconds'),
    };
    let tasks = [];
    let isAvailable = false;
    let hasLoaded = false;
    let editingTaskId = null;
    let isSaving = false;
    let activeView = 'home';
    let selectedDate = toDateString(new Date());
    let homeMode = 'board';
    let displayedCalendarMonth = startOfMonth(selectedDate);

    let toastTimer = null;

    const showToast = (message, type = 'success') => {
        toastMessage.textContent = message;
        toastMessage.className = type === 'error'
            ? 'pointer-events-auto max-w-md rounded-full bg-red-700 px-5 py-3 text-sm font-semibold text-white shadow-xl'
            : 'pointer-events-auto max-w-md rounded-full bg-negro px-5 py-3 text-sm font-semibold text-white shadow-xl';
        toast.classList.remove('opacity-0');
        toast.classList.add('opacity-100');

        if (toastTimer !== null) {
            window.clearTimeout(toastTimer);
        }

        toastTimer = window.setTimeout(() => {
            toast.classList.add('opacity-0');
            toast.classList.remove('opacity-100');
        }, 3200);
    };

    let detailFeedbackTimer = null;

    const showDetailFeedback = (message, type = 'success') => {
        detailFeedback.textContent = message;
        detailFeedback.className = type === 'error'
            ? 'mx-1 rounded-lg bg-red-50 px-3 py-1.5 text-[11px] font-semibold text-red-700'
            : 'mx-1 rounded-lg bg-[#E3F1E9] px-3 py-1.5 text-[11px] font-semibold text-[#2E7D5B]';

        if (detailFeedbackTimer !== null) {
            window.clearTimeout(detailFeedbackTimer);
        }

        detailFeedbackTimer = window.setTimeout(() => {
            detailFeedback.textContent = '';
            detailFeedback.className = 'hidden mx-1 rounded-lg px-3 py-1.5 text-[11px] font-semibold';
        }, 2600);
    };

    const notify = (message, type = 'success') => {
        if (detailDialog.open) {
            showDetailFeedback(message, type);
        } else {
            showToast(message, type);
        }
    };

    const setTaskError = (message) => {
        isAvailable = false;
        storageErrorMessage.textContent = message;
        storageError.classList.remove('hidden');
        storageError.classList.add('flex');
        taskForm.querySelectorAll('input, select, textarea, button').forEach((control) => {
            control.disabled = true;
        });
        setNewTaskButtonsDisabled(true);
    };

    const clearTaskError = () => {
        storageErrorMessage.textContent = '';
        storageError.classList.add('hidden');
        storageError.classList.remove('flex');
    };

    const announce = (message) => {
        statusMessage.textContent = message;
        notify(message);
    };

    const taskUrl = (taskId) => taskUrlTemplate.replace('__task__', encodeURIComponent(taskId));

    const setNewTaskButtonsDisabled = (disabled) => {
        newTaskButtons.forEach((button) => {
            button.disabled = disabled;
        });
    };

    const showTaskFormError = (message) => {
        taskFormError.textContent = message;
        taskFormError.classList.remove('hidden');
        taskFormError.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    };

    const clearTaskFormError = () => {
        taskFormError.textContent = '';
        taskFormError.classList.add('hidden');
    };

    const loadTasks = async () => {
        if (!hasLoaded) {
            loadingIndicator.classList.remove('hidden');
        }

        try {
            tasks = normalizeTaskCollection(await requestTaskApi('GET', tasksUrl));
            isAvailable = true;
            clearTaskError();
            taskForm.querySelectorAll('input, select, textarea, button').forEach((control) => {
                control.disabled = false;
            });
            setNewTaskButtonsDisabled(false);
            renderTaskViews();
            updateHeader();

            return true;
        } catch (error) {
            setTaskError(error instanceof Error ? error.message : 'No se pudieron cargar las tareas.');

            return false;
        } finally {
            hasLoaded = true;
            loadingIndicator.classList.add('hidden');
        }
    };

    retryButton.addEventListener('click', () => {
        void loadTasks();
    });

    const saveTasks = async (nextTasks) => {
        const added = nextTasks.filter((nextTask) => !tasks.some((task) => task.id === nextTask.id));
        const removed = tasks.filter((task) => !nextTasks.some((nextTask) => nextTask.id === task.id));
        const updated = nextTasks.filter((nextTask) => {
            const currentTask = tasks.find((task) => task.id === nextTask.id);

            return currentTask && JSON.stringify(currentTask) !== JSON.stringify(nextTask);
        });
        const changes = added.length + removed.length + updated.length;

        if (changes !== 1) {
            return { ok: false, message: 'No se pudo identificar una única tarea para guardar.' };
        }

        try {
            if (added.length === 1) {
                await requestTaskApi('POST', tasksUrl, serializeTaskForApi(added[0]));
            } else if (removed.length === 1) {
                await requestTaskApi('DELETE', taskUrl(removed[0].id));
            } else {
                await requestTaskApi('PUT', taskUrl(updated[0].id), serializeTaskForApi(updated[0]));
            }

            const loaded = await loadTasks();

            return { ok: loaded, message: '' };
        } catch (error) {
            return {
                ok: false,
                message: error instanceof Error ? error.message : 'No se pudo guardar la tarea.',
            };
        }
    };

    const updateNavigation = () => {
        viewButtons.forEach((button) => {
            const isActive = button.dataset.viewTarget === activeView;
            const icon = button.querySelector('[data-view-icon]');

            button.setAttribute('aria-selected', String(isActive));
            icon.classList.toggle('rounded-2xl', isActive);
            icon.classList.toggle('rounded-full', !isActive);
            icon.classList.toggle('border-4', isActive);
            icon.classList.toggle('border-2', !isActive);
            icon.classList.toggle('border-negro', isActive);
            icon.classList.toggle('border-slate-400', !isActive);
            icon.classList.toggle('bg-primario', isActive);
            icon.classList.toggle('bg-white', !isActive);
            icon.classList.toggle('text-white', isActive);
            icon.classList.toggle('text-primario', !isActive);
            icon.classList.toggle('shadow-lg', isActive);
            icon.classList.toggle('shadow-sm', !isActive);
        });

        Object.entries(views).forEach(([viewName, view]) => {
            view.classList.toggle('hidden', viewName !== activeView);
        });
    };

    const renderHomeView = () => {
        const dateTasks = tasks
            .filter((task) => taskOccursOnDate(task, selectedDate))
            .sort((first, second) => first.title.localeCompare(second.title));
        const previousDate = shiftDate(selectedDate, -1);
        const nextDate = shiftDate(selectedDate, 1);
        const dateStats = root.querySelector('#selected-date-stats');

        const previousDateLabel = formatCarouselDate(previousDate, false);
        const nextDateLabel = formatCarouselDate(nextDate, false);
        root.querySelector('#previous-date-label').textContent = previousDateLabel;
        root.querySelector('#next-date-label').textContent = nextDateLabel;
        root.querySelector('[data-date-shift="-1"]').setAttribute('aria-label', `Mostrar día anterior: ${previousDateLabel}`);
        root.querySelector('[data-date-shift="1"]').setAttribute('aria-label', `Mostrar día siguiente: ${nextDateLabel}`);
        root.querySelector('#selected-date-label').textContent = formatCarouselDate(selectedDate);
        root.querySelector('#tasks-view-date').textContent = 'Todas tus tareas';
        root.querySelector('#agenda-selected-date').textContent = formatCarouselDate(selectedDate);

        const metrics = [
            { text: `${dateTasks.length} tareas`, className: 'text-primario' },
            { text: `${dateTasks.filter((task) => task.status === 'TERMINADOS').length} terminadas`, className: 'text-[#2F6B4F]' },
            { text: `${dateTasks.filter((task) => task.status === 'PROCESO').length} en proceso`, className: 'text-[#7A5A1A]' },
            { text: `${dateTasks.filter((task) => task.status === 'INICIADO').length} iniciadas`, className: 'text-[#8A3D4D]' },
        ];
        const statsElements = [];

        metrics.forEach((metric, index) => {
            if (index > 0) {
                const separator = document.createElement('span');
                separator.className = 'text-slate-500';
                separator.textContent = '–';
                statsElements.push(separator);
            }

            const value = document.createElement('span');
            value.className = metric.className;
            value.textContent = metric.text;
            statsElements.push(value);
        });

        dateStats.replaceChildren(...statsElements);

        homeModeButtons.forEach((button) => {
            const isActive = button.dataset.homeMode === homeMode;

            button.setAttribute('aria-pressed', String(isActive));
            button.classList.toggle('btn-icon-fill', isActive);
            button.classList.toggle('btn-icon-outline', !isActive);
        });

        Object.entries(homeModeViews).forEach(([mode, view]) => {
            view.classList.toggle('hidden', mode !== homeMode);
        });
        const boardCards = dateTasks.map(createBoardTaskCard);

        root.querySelector('#home-board-columns').replaceChildren(...boardCards);
        root.querySelector('#home-board-columns').classList.toggle('hidden', dateTasks.length === 0);
        root.querySelector('#home-board-empty').classList.toggle('hidden', dateTasks.length > 0);

        const listItems = root.querySelector('#home-list-items');
        listItems.replaceChildren(...dateTasks.map(createTaskViewCard));
        root.querySelector('#home-list-empty').classList.toggle('hidden', dateTasks.length > 0);

        const gridItems = root.querySelector('#home-grid-items');
        gridItems.replaceChildren(...dateTasks.map((task) => createBoardTaskCard(task, false)));
        root.querySelector('#home-grid-empty').classList.toggle('hidden', dateTasks.length > 0);

        const monthLabel = new Intl.DateTimeFormat('es', {
            month: 'long',
            year: 'numeric',
        }).format(displayedCalendarMonth);
        root.querySelector('#home-calendar-month').textContent = monthLabel;

        const firstMonthDay = new Date(
            displayedCalendarMonth.getFullYear(),
            displayedCalendarMonth.getMonth(),
            1
        );
        const calendarStart = new Date(firstMonthDay);
        calendarStart.setDate(calendarStart.getDate() - ((calendarStart.getDay() + 6) % 7));
        const calendarButtons = Array.from({ length: 42 }, (_, index) => {
            const date = new Date(calendarStart);
            date.setDate(calendarStart.getDate() + index);
            const dateString = toDateString(date);
            const isSelected = dateString === selectedDate;
            const isCurrentMonth = date.getMonth() === displayedCalendarMonth.getMonth();
            const hasTasks = tasks.some((task) => taskOccursOnDate(task, dateString));
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.calendarDate = dateString;
            button.setAttribute('aria-label', formatAccessibleDate(dateString));
            button.setAttribute('aria-pressed', String(isSelected));
            button.className = `relative mx-auto flex h-9 w-9 items-center justify-center rounded-full text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-primario/30 sm:h-10 sm:w-10 ${
                isSelected
                    ? 'bg-primario text-white'
                    : isCurrentMonth
                        ? 'text-negro hover:bg-primario-suave'
                        : 'text-slate-500 hover:bg-fondo'
            }`;

            const dayNumber = document.createElement('span');
            dayNumber.textContent = String(date.getDate());
            button.append(dayNumber);

            if (hasTasks) {
                const taskMarker = document.createElement('span');
                taskMarker.className = `absolute bottom-1 h-1 w-1 rounded-full ${isSelected ? 'bg-white' : 'bg-primario'}`;
                button.append(taskMarker);
            }

            return button;
        });

        root.querySelector('#home-calendar-days').replaceChildren(...calendarButtons);
        const calendarTasks = root.querySelector('#home-calendar-tasks');
        calendarTasks.replaceChildren(...dateTasks.map(createTaskViewCard));
        root.querySelector('#home-calendar-empty').classList.toggle('hidden', dateTasks.length > 0);
    };

    const renderTaskViews = () => {
        const sortedTasks = [...tasks].sort((first, second) => {
            if (!first.due_date) {
                return second.due_date ? 1 : first.title.localeCompare(second.title);
            }

            if (!second.due_date) {
                return -1;
            }

            return first.due_date.localeCompare(second.due_date);
        });
        const statusColumns = [
            { status: 'INICIADO', title: 'Pendiente', color: 'bg-tab-iniciado' },
            { status: 'PROCESO', title: 'En progreso', color: 'bg-tab-proceso' },
            { status: 'TERMINADOS', title: 'Completadas', color: 'bg-tab-terminado' },
        ];
        const taskColumns = statusColumns.map(({ status, title, color }) => {
            const column = document.createElement('section');
            column.className = 'flex min-h-[240px] flex-col overflow-hidden rounded-2xl border border-primario/15 bg-white/80 shadow-sm';

            const heading = document.createElement('h2');
            heading.className = `flex items-center justify-between ${color} px-4 py-4 text-xs font-extrabold uppercase tracking-wide text-negro`;

            const headingTitle = document.createElement('span');
            headingTitle.textContent = title;

            const columnTasks = sortedTasks.filter((task) => task.status === status);
            const count = document.createElement('span');
            count.className = 'rounded-full bg-white/70 px-2 py-0.5 text-[10px]';
            count.textContent = String(columnTasks.length);
            heading.append(headingTitle, count);

            const list = document.createElement('div');
            list.className = 'flex-1 space-y-3 p-3';

            if (columnTasks.length > 0) {
                list.replaceChildren(...columnTasks.map((task) => createBoardTaskCard(task, false)));
            } else {
                const empty = document.createElement('p');
                empty.className = 'flex h-full min-h-[140px] items-center justify-center rounded-xl border border-dashed border-gris/15 bg-fondo/70 px-3 py-6 text-center text-xs font-medium text-slate-500';
                empty.textContent = 'Sin tareas';
                list.append(empty);
            }

            column.append(heading, list);

            return column;
        });
        const taskList = root.querySelector('#tasks-view-list');

        taskList.replaceChildren(...taskColumns);
        taskList.classList.toggle('hidden', tasks.length === 0);
        root.querySelector('#tasks-view-date').textContent = 'Todas tus tareas';
        root.querySelector('#tasks-view-count').textContent = `${tasks.length} ${tasks.length === 1 ? 'tarea' : 'tareas'}`;
        root.querySelector('#tasks-view-empty').classList.toggle('hidden', tasks.length > 0);

        renderHomeView();
    };

    const getActiveTask = () => {
        const openTasks = tasks.filter((task) => task.status !== 'TERMINADOS');
        const tasksInProcess = openTasks
            .filter((task) => task.status === 'PROCESO' && task.due_date)
            .sort((first, second) => first.due_date.localeCompare(second.due_date));

        if (tasksInProcess.length > 0) {
            return tasksInProcess[0];
        }

        const tasksWithDeadline = openTasks
            .filter((task) => task.due_date)
            .sort((first, second) => first.due_date.localeCompare(second.due_date));

        if (tasksWithDeadline.length > 0) {
            return tasksWithDeadline[0];
        }

        return openTasks.find((task) => task.status === 'PROCESO') || openTasks[0] || null;
    };

    const updateCountdown = () => {
        const task = getActiveTask();
        const timerCaption = root.querySelector('#timer-caption');

        if (!task) {
            activeTaskButton.textContent = 'SIN TAREA ACTIVA';
            activeTaskButton.disabled = true;
            activeTaskButton.title = 'Agrega una tarea para iniciar';
            timerCaption.textContent = 'No hay tareas activas';
            Object.values(timerElements).forEach((element) => {
                element.textContent = '--';
            });

            return;
        }

        activeTaskButton.disabled = false;
        activeTaskButton.textContent = task.title.toUpperCase();
        activeTaskButton.title = task.title;

        const deadline = deadlineTimestamp(task.due_date);

        if (deadline === null) {
            timerCaption.textContent = 'La tarea no tiene fecha límite';
            Object.values(timerElements).forEach((element) => {
                element.textContent = '--';
            });

            return;
        }

        const remainingSeconds = Math.max(0, Math.floor((deadline - Date.now()) / 1000));
        const days = Math.floor(remainingSeconds / 86400);
        const hours = Math.floor((remainingSeconds % 86400) / 3600);
        const minutes = Math.floor((remainingSeconds % 3600) / 60);
        const seconds = remainingSeconds % 60;

        timerElements.days.textContent = String(days).padStart(2, '0');
        timerElements.hours.textContent = String(hours).padStart(2, '0');
        timerElements.minutes.textContent = String(minutes).padStart(2, '0');
        timerElements.seconds.textContent = String(seconds).padStart(2, '0');
        timerCaption.textContent = remainingSeconds === 0
            ? `La tarea venció el ${formatDueDate(task.due_date)}`
            : `Tiempo restante para ${task.title}: ${formatDueDate(task.due_date)}`;
    };

    const renderNotifications = () => {
        const now = Date.now();
        const notifications = tasks
            .filter((task) => task.status !== 'TERMINADOS' && task.due_date)
            .map((task) => ({ task, deadline: deadlineTimestamp(task.due_date) }))
            .filter(({ deadline }) => deadline - now <= NOTIFICATION_WINDOW_MS)
            .sort((first, second) => first.deadline - second.deadline);
        const notificationList = root.querySelector('#notifications-list');
        const notificationCount = root.querySelector('#notification-count');

        notificationCount.textContent = String(notifications.length);
        notificationCount.classList.toggle('hidden', notifications.length === 0);
        notificationCount.classList.toggle('flex', notifications.length > 0);

        const cards = notifications.map(({ task, deadline }) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.notificationTaskId = task.id;
            button.className = 'w-full rounded-2xl border border-gris/10 bg-fondo p-4 text-left transition hover:border-primario/30 focus:outline-none focus:ring-2 focus:ring-primario/30';

            const title = document.createElement('span');
            title.className = 'block font-bold text-negro';
            title.textContent = task.title;

            const message = document.createElement('span');
            message.className = 'mt-1 block text-xs text-gris';
            message.textContent = deadline < now
                ? `Vencida · ${formatDueDate(task.due_date)}`
                : `Vence ${formatDueDate(task.due_date)}`;

            button.append(title, message);

            return button;
        });

        notificationList.replaceChildren(...cards);
        root.querySelector('#notifications-empty').classList.toggle('hidden', notifications.length > 0);
    };

    const updateHeader = () => {
        const now = new Date();
        const dateLabel = new Intl.DateTimeFormat('es', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(now);

        root.querySelector('#top-current-date').textContent = dateLabel.charAt(0).toUpperCase() + dateLabel.slice(1);
        updateCountdown();
        renderNotifications();
    };

    const fillDetailDialog = (task) => {
        detailDialog.dataset.taskId = task.id;
        detailDialog.querySelector('#detail-project-category').textContent = task.category;
        detailDialog.querySelector('#detail-task-title').textContent = task.title;
        detailDialog.querySelector('#detail-task-description').textContent = task.description || 'Sin descripción';
        detailDialog.querySelector('#detail-status-text').textContent = TASK_STATUSES[task.status];
        detailResponsibleSelect.value = task.responsible_id || '';
        detailDialog.querySelector('#detail-percent-value').textContent = `${task.progress_percentage}%`;
        detailDialog.querySelector('#detail-start-date').textContent = formatDueDate(task.start_date);
        detailDialog.querySelector('#detail-end-date').textContent = formatDueDate(task.due_date);
        const completedSubtasks = task.subtasks.filter((subtask) => subtask.done).length;
        const subtasksCount = task.subtasks.length;
        detailDialog.querySelector('#detail-subtasks-counter').textContent = `${completedSubtasks} de ${subtasksCount} tareas`;
        detailDialog.querySelector('#detail-subtasks-list-count').textContent = `${subtasksCount} ${subtasksCount === 1 ? 'tarea' : 'tareas'}`;

        const list = detailDialog.querySelector('#detail-subtasks-list');
        const emptyMessage = detailDialog.querySelector('#detail-subtasks-empty');
        const subtaskForm = detailDialog.querySelector('#detail-subtask-form');
        const subtaskButton = detailDialog.querySelector('[data-open-subtask-form]');

        subtaskForm.reset();
        subtaskForm.classList.add('hidden');
        subtaskButton.classList.remove('hidden');
        emptyMessage.classList.toggle('hidden', subtasksCount > 0);

        const subtasks = task.subtasks.map((subtask, index) => {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-[20px_minmax(0,1fr)] items-start gap-3 py-3';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.checked = subtask.done;
            checkbox.dataset.subtaskTaskId = task.id;
            checkbox.dataset.subtaskIndex = String(index);
            checkbox.setAttribute('aria-label', `Marcar ${subtask.text} como completada`);
            checkbox.className = 'h-4 w-4 cursor-pointer accent-primario';

            const text = document.createElement('span');
            text.textContent = subtask.text;
            text.className = subtask.done
                ? 'break-words text-xs font-medium text-slate-600 line-through'
                : 'break-words text-xs font-medium text-gris';

            const header = document.createElement('div');
            header.className = 'flex min-w-0 items-center justify-between gap-2';

            const state = document.createElement('span');
            state.className = subtask.done
                ? 'rounded-full bg-[#E3F1E9] px-2 py-1 text-[9px] font-bold text-[#2F6B4F]'
                : 'rounded-full bg-primario-suave px-2 py-1 text-[9px] font-bold text-primario';
            state.textContent = subtask.done ? 'Hecha' : 'Pendiente';
            header.append(text, state);

            const assignment = document.createElement('label');
            assignment.className = 'mt-2 flex items-center gap-2 text-[10px] font-semibold text-gris';

            const assignmentLabel = document.createElement('span');
            assignmentLabel.textContent = 'Responsable';

            const subtaskAssigneeSelect = document.createElement('select');
            subtaskAssigneeSelect.className = 'min-w-0 flex-1 rounded-lg border border-gris/20 bg-white px-2 py-1 text-[10px] font-semibold text-negro outline-none focus:border-primario focus:ring-2 focus:ring-primario/20';
            subtaskAssigneeSelect.dataset.subtaskTaskId = task.id;
            subtaskAssigneeSelect.dataset.subtaskIndex = String(index);
            subtaskAssigneeSelect.setAttribute('aria-label', `Responsable de ${subtask.text}`);

            [...responsibleSelect.options].forEach((option) => {
                const optionCopy = document.createElement('option');
                optionCopy.value = option.value;
                optionCopy.textContent = option.textContent;
                subtaskAssigneeSelect.append(optionCopy);
            });
            subtaskAssigneeSelect.value = subtask.responsible_id || '';
            assignment.append(assignmentLabel, subtaskAssigneeSelect);

            const content = document.createElement('div');
            content.className = 'min-w-0';
            content.append(header, assignment);

            row.append(checkbox, content);

            return row;
        });
        list.replaceChildren(...subtasks);

        if (!detailDialog.open) {
            detailDialog.showModal();
        }
    };

    const API_STATUS_BY_TASK_STATUS = {
        INICIADO: 'pendiente',
        PROCESO: 'en_progreso',
        TERMINADOS: 'completada',
    };

    const buildSubtasksFromForm = (formData, baseTask) => {
        const lines = String(formData.get('subtasks') || '')
            .split(/\r?\n/)
            .map((text) => text.trim())
            .filter(Boolean);
        const previous = baseTask?.subtasks ?? [];

        return lines.map((text, index) => {
            const match = previous.find((subtask) => subtask.text === text)
                ?? (previous[index]?.text === text ? previous[index] : null);

            return {
                text,
                done: match?.done ?? false,
                responsible_id: match?.responsible_id ?? '',
                responsible_name: match?.responsible_name ?? '',
            };
        });
    };

    const resetTaskForm = () => {
        editingTaskId = null;
        taskForm.reset();
        taskFormTitle.textContent = 'Nueva tarea';
        taskFormSubtitle.textContent = taskFormSubtitleDefault;
        taskSubmitButton.textContent = 'Agregar tarea';
        clearTaskFormError();
        taskForm.querySelector('#task-start-date').value = toDateString(new Date());
    };

    const openTaskForm = (task = null) => {
        if (!isAvailable) {
            return;
        }

        resetTaskForm();

        if (task) {
            editingTaskId = task.id;
            taskFormTitle.textContent = 'Editar tarea';
            taskFormSubtitle.textContent = 'Actualiza los datos y guarda los cambios.';
            taskSubmitButton.textContent = 'Guardar cambios';
            taskForm.querySelector('#task-category').value = task.category === 'GENERAL' ? '' : task.category;
            taskForm.querySelector('#task-team').value = task.team || '';
            responsibleSelect.value = task.responsible_id || '';
            taskForm.querySelector('#task-title').value = task.title;
            taskForm.querySelector('#task-description').value = task.description || '';
            taskForm.querySelector('#task-status').value = API_STATUS_BY_TASK_STATUS[task.status] ?? 'pendiente';
            taskForm.querySelector('#task-priority').value = task.priority || 'medium';
            taskForm.querySelector('#task-start-date').value = task.start_date || '';
            taskForm.querySelector('#task-date').value = task.due_date || '';
            taskForm.querySelector('#task-subtasks').value = task.subtasks.map((subtask) => subtask.text).join('\n');
        }

        newTaskDialog.showModal();
        taskTitleInput.focus();
    };

    const saveTaskFromForm = async (formData) => {
        const title = String(formData.get('title') || '').trim();

        if (!title) {
            taskTitleInput.setCustomValidity('Escribe una tarea antes de guardarla.');
            taskTitleInput.reportValidity();

            return false;
        }

        taskTitleInput.setCustomValidity('');
        clearTaskFormError();

        const baseTask = editingTaskId === null ? null : tasks.find((task) => task.id === editingTaskId);
        const responsibleOption = responsibleSelect.selectedOptions[0];
        const task = normalizeTask({
            id: baseTask?.id ?? createTaskId(),
            category: String(formData.get('category') || '').trim() || 'GENERAL',
            title,
            description: String(formData.get('description') || '').trim(),
            status: String(formData.get('status') || 'pendiente'),
            priority: String(formData.get('priority') || 'medium'),
            team: String(formData.get('team') || '').trim(),
            responsible_id: String(formData.get('responsible_id') || ''),
            responsible_name: responsibleOption?.value ? responsibleOption.textContent.trim() : '',
            subtasks: buildSubtasksFromForm(formData, baseTask),
            start_date: String(formData.get('start_date') || ''),
            due_date: String(formData.get('due_date') || ''),
        });

        if (!task) {
            showTaskFormError('Los datos de la tarea no son válidos. Revisa las fechas e inténtalo de nuevo.');

            return false;
        }

        const nextTasks = baseTask
            ? tasks.map((item) => (item.id === baseTask.id ? task : item))
            : [task, ...tasks];

        const result = await saveTasks(nextTasks);

        if (!result.ok) {
            if (result.message) {
                showTaskFormError(result.message);
            }

            return false;
        }

        if (!baseTask && task.start_date) {
            selectedDate = task.start_date;
            displayedCalendarMonth = startOfMonth(selectedDate);
            renderTaskViews();
        }

        announce(baseTask ? `Tarea actualizada: ${task.title}` : `Tarea agregada: ${task.title}`);
        newTaskDialog.close();

        return true;
    };

    const deleteTask = async (task) => {
        if (!task) {
            return;
        }

        const confirmed = window.confirm(`¿Eliminar "${task.title}"? Esta acción no se puede deshacer.`);

        if (!confirmed) {
            return;
        }

        const result = await saveTasks(tasks.filter((item) => item.id !== task.id));

        if (result.ok) {
            detailDialog.close();
            announce(`Tarea eliminada: ${task.title}`);
        } else if (result.message) {
            notify(result.message, 'error');
        }
    };

    taskForm.querySelectorAll('input, select, textarea, button').forEach((control) => {
        control.disabled = true;
    });
    setNewTaskButtonsDisabled(true);
    void loadTasks();

    window.setInterval(updateHeader, 1000);

    root.querySelectorAll('[data-open-profile]').forEach((button) => {
        button.addEventListener('click', () => profileDialog.showModal());
    });

    viewButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeView = button.dataset.viewTarget;
            updateNavigation();
            renderTaskViews();
        });
    });

    root.querySelectorAll('[data-date-shift]').forEach((button) => {
        button.addEventListener('click', () => {
            selectedDate = shiftDate(selectedDate, Number(button.dataset.dateShift));
            displayedCalendarMonth = startOfMonth(selectedDate);
            renderHomeView();
        });
    });

    const homeBoard = root.querySelector('#home-board-columns');
    homeBoard.addEventListener('wheel', (event) => {
        let delta = event.deltaY || event.deltaX;

        if (delta === 0) {
            return;
        }

        if (event.deltaMode === 1) {
            delta *= 40;
        } else if (event.deltaMode === 2) {
            delta *= homeBoard.clientWidth;
        } else {
            delta *= 1.25;
        }

        const maxScroll = homeBoard.scrollWidth - homeBoard.clientWidth;

        if (maxScroll <= 0) {
            return;
        }

        const nextScroll = homeBoard.scrollLeft + delta;

        if ((delta < 0 && homeBoard.scrollLeft <= 0) || (delta > 0 && homeBoard.scrollLeft >= maxScroll)) {
            return;
        }

        event.preventDefault();
        homeBoard.scrollLeft = Math.max(0, Math.min(maxScroll, nextScroll));
    }, { passive: false });

    homeModeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            homeMode = button.dataset.homeMode;
            renderHomeView();
        });
    });

    root.querySelectorAll('[data-calendar-shift]').forEach((button) => {
        button.addEventListener('click', () => {
            displayedCalendarMonth = new Date(
                displayedCalendarMonth.getFullYear(),
                displayedCalendarMonth.getMonth() + Number(button.dataset.calendarShift),
                1
            );
            renderHomeView();
        });
    });

    root.querySelector('#home-calendar-days').addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const button = event.target.closest('[data-calendar-date]');

        if (button) {
            selectedDate = button.dataset.calendarDate;
            displayedCalendarMonth = startOfMonth(selectedDate);
            renderHomeView();
        }
    });

    [
        root.querySelector('#home-board-columns'),
        root.querySelector('#home-grid-items'),
        root.querySelector('#home-list-items'),
        root.querySelector('#home-calendar-tasks'),
    ].forEach((container) => {
        container.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            const button = event.target.closest('[data-task-view-id]');
            const task = tasks.find((item) => item.id === button?.dataset.taskViewId);

            if (task) {
                fillDetailDialog(task);
            }
        });
    });

    root.querySelector('[data-close-profile]').addEventListener('click', () => {
        profileDialog.close();
    });

    newTaskButtons.forEach((button) => {
        button.addEventListener('click', () => openTaskForm());
    });

    root.querySelectorAll('[data-close-new-task]').forEach((button) => {
        button.addEventListener('click', () => {
            newTaskDialog.close();
            resetTaskForm();
        });
    });

    newTaskDialog.addEventListener('close', resetTaskForm);

    taskForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!isAvailable || isSaving) {
            return;
        }

        isSaving = true;
        taskSubmitButton.disabled = true;
        const previousLabel = taskSubmitButton.textContent;
        taskSubmitButton.textContent = editingTaskId === null ? 'Agregando…' : 'Guardando…';

        const saved = await saveTaskFromForm(new FormData(taskForm));

        isSaving = false;
        taskSubmitButton.disabled = !isAvailable;

        if (saved) {
            resetTaskForm();
        } else {
            taskSubmitButton.textContent = previousLabel;
        }
    });

    taskTitleInput.addEventListener('input', () => {
        taskTitleInput.setCustomValidity('');
    });

    root.querySelector('[data-open-notifications]').addEventListener('click', () => {
        renderNotifications();
        notificationsDialog.showModal();
    });

    root.querySelector('[data-close-notifications]').addEventListener('click', () => {
        notificationsDialog.close();
    });

    root.querySelector('#notifications-list').addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const button = event.target.closest('[data-notification-task-id]');
        const task = tasks.find((item) => item.id === button?.dataset.notificationTaskId);

        if (task) {
            notificationsDialog.close();
            fillDetailDialog(task);
        }
    });

    root.querySelector('#tasks-view-list').addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const button = event.target.closest('[data-task-view-id]');
        const task = tasks.find((item) => item.id === button?.dataset.taskViewId);

        if (task) {
            fillDetailDialog(task);
        }
    });

    activeTaskButton.addEventListener('click', () => {
        const task = getActiveTask();

        if (task) {
            fillDetailDialog(task);
        }
    });

    detailDialog.querySelector('[data-close-detail]').addEventListener('click', () => {
        detailDialog.close();
    });

    detailDialog.querySelector('[data-edit-task]').addEventListener('click', () => {
        const task = tasks.find((item) => item.id === detailDialog.dataset.taskId);

        if (!task) {
            return;
        }

        detailDialog.close();
        openTaskForm(task);
    });

    detailDialog.querySelector('[data-delete-task]').addEventListener('click', () => {
        const task = tasks.find((item) => item.id === detailDialog.dataset.taskId);

        void deleteTask(task);
    });

    detailResponsibleSelect.addEventListener('change', async () => {
        const task = tasks.find((item) => item.id === detailDialog.dataset.taskId);

        if (!isAvailable || !task) {
            return;
        }

        const responsibleId = detailResponsibleSelect.value;
        const responsibleName = detailResponsibleSelect.selectedOptions[0]?.value
            ? detailResponsibleSelect.selectedOptions[0].textContent.trim()
            : '';
        const updatedTask = {
            ...task,
            responsible_id: responsibleId,
            responsible_name: responsibleName,
        };
        const nextTasks = tasks.map((item) => (item.id === task.id ? updatedTask : item));

        const result = await saveTasks(nextTasks);

        if (result.ok) {
            announce(responsibleName
                ? `Responsable actualizado: ${responsibleName}`
                : 'Se quitó el responsable de la tarea.');
        } else if (result.message) {
            notify(result.message, 'error');
        }
    });

    const subtaskForm = detailDialog.querySelector('#detail-subtask-form');
    const subtaskInput = detailDialog.querySelector('#detail-subtask-input');
    const subtaskResponsibleSelect = detailDialog.querySelector('#detail-subtask-responsible');
    const openSubtaskFormButton = detailDialog.querySelector('[data-open-subtask-form]');

    openSubtaskFormButton.addEventListener('click', () => {
        subtaskForm.classList.remove('hidden');
        openSubtaskFormButton.classList.add('hidden');
        subtaskInput.focus();
    });

    detailDialog.querySelector('[data-cancel-add-subtask]').addEventListener('click', () => {
        subtaskForm.reset();
        subtaskForm.classList.add('hidden');
        openSubtaskFormButton.classList.remove('hidden');
    });

    subtaskForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const text = subtaskInput.value.trim();
        const task = tasks.find((item) => item.id === detailDialog.dataset.taskId);

        if (!isAvailable || !task || !text) {
            return;
        }

        const responsibleId = subtaskResponsibleSelect.value;
        const responsibleName = subtaskResponsibleSelect.selectedOptions[0]?.value
            ? subtaskResponsibleSelect.selectedOptions[0].textContent.trim()
            : '';
        const subtasks = [...task.subtasks, {
            text,
            done: false,
            responsible_id: responsibleId,
            responsible_name: responsibleName,
        }];
        const status = task.status === 'TERMINADOS' ? 'PROCESO' : task.status;
        const updatedTask = {
            ...task,
            status,
            subtasks,
            progress_percentage: calculateProgress(subtasks, status),
        };
        const nextTasks = tasks.map((item) => (item.id === task.id ? updatedTask : item));

        const result = await saveTasks(nextTasks);

        if (result.ok) {
            fillDetailDialog(tasks.find((item) => item.id === task.id) || updatedTask);
            announce(`Subtarea agregada: ${text}`);
        } else if (result.message) {
            notify(result.message, 'error');
        }
    });

    detailDialog.addEventListener('change', async (event) => {
        if (event.target instanceof HTMLSelectElement && event.target.matches('[data-subtask-task-id]')) {
            const taskId = event.target.dataset.subtaskTaskId;
            const subtaskIndex = Number(event.target.dataset.subtaskIndex);
            const task = tasks.find((item) => item.id === taskId);

            if (!isAvailable || !task || !task.subtasks[subtaskIndex]) {
                return;
            }

            const responsibleId = event.target.value;
            const responsibleName = event.target.selectedOptions[0]?.value
                ? event.target.selectedOptions[0].textContent.trim()
                : '';
            const subtasks = task.subtasks.map((subtask, index) => (
                index === subtaskIndex
                    ? { ...subtask, responsible_id: responsibleId, responsible_name: responsibleName }
                    : subtask
            ));
            const updatedTask = { ...task, subtasks };
            const nextTasks = tasks.map((item) => (item.id === taskId ? updatedTask : item));

            const result = await saveTasks(nextTasks);

            if (result.ok) {
                announce(responsibleName
                    ? `Responsable actualizado: ${responsibleName}`
                    : 'Se quitó el responsable de la subtarea.');
            } else if (result.message) {
                notify(result.message, 'error');
            }

            return;
        }

        if (!(event.target instanceof HTMLInputElement) || !event.target.matches('[data-subtask-task-id]')) {
            return;
        }

        const taskId = event.target.dataset.subtaskTaskId;
        const subtaskIndex = Number(event.target.dataset.subtaskIndex);
        const task = tasks.find((item) => item.id === taskId);

        if (!isAvailable || !task || !task.subtasks[subtaskIndex]) {
            return;
        }

        const subtasks = task.subtasks.map((subtask, index) => (
            index === subtaskIndex ? { ...subtask, done: event.target.checked } : subtask
        ));
        const updatedTask = {
            ...task,
            subtasks,
            progress_percentage: calculateProgress(subtasks, task.status),
        };
        const nextTasks = tasks.map((item) => (item.id === taskId ? updatedTask : item));

        const result = await saveTasks(nextTasks);

        if (result.ok) {
            fillDetailDialog(tasks.find((item) => item.id === task.id) || updatedTask);
            announce(`Avance actualizado: ${updatedTask.title}`);
        } else if (result.message) {
            notify(result.message, 'error');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const taskApp = document.querySelector('#task-app[data-tasks-url]');

    if (taskApp) {
        setupTaskHeader(taskApp);
    }
});
