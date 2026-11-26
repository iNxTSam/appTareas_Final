const TASK_STATUSES = {
    INICIADO: 'Iniciado',
    PROCESO: 'Proceso',
    TERMINADOS: 'Terminado',
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
    const dueDate = normalizeDate(value.end_date ?? value.due_date ?? value.dueDate ?? value.end);
    const rawPriority = String(value.priority ?? (value.is_important === true || value.important === true ? 'high' : 'medium')).toLowerCase();
    const priority = ({
        baja: 'low',
        media: 'medium',
        alta: 'high',
    })[rawPriority] ?? rawPriority;
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
        team_id: value.team_id === null || value.team_id === undefined ? '' : String(value.team_id),
        project_id: value.project_id === null || value.project_id === undefined ? '' : String(value.project_id),
        is_important: priority === 'high' || value.is_important === true || value.important === true,
        is_overdue: status !== 'TERMINADOS' && dueDate !== '' && dueDate < toDateString(new Date()),
        subtasks,
        progress_percentage: Math.round(progress),
        start_date: startDate,
        due_date: dueDate,
        responsible_id: responsibleId,
        responsible_name: typeof value.responsible_name === 'string' ? value.responsible_name : '',
        assignees: Array.isArray(value.assignees)
            ? value.assignees
                .filter((assignee) => assignee && typeof assignee.name === 'string')
                .map((assignee) => ({
                    id: String(assignee.id ?? ''),
                    name: assignee.name,
                }))
            : [],
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
        title: task.title,
        description: task.description,
        status: API_TASK_STATUSES[task.status],
        priority: task.priority,
        team_id: task.team_id || null,
        project_id: task.project_id || null,
        start_date: task.start_date || null,
        due_date: task.due_date || null,
        responsible_id: task.responsible_id || null,
        subtasks: task.subtasks.map((subtask) => ({
            title: subtask.text,
            is_done: subtask.done,
        })),
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
    button.className = 'flex w-full items-start gap-3 rounded-xl border border-borde bg-white p-3 text-left shadow-sm transition hover:border-primario/50 focus:outline-none focus:ring-2 focus:ring-primario/30';

    const dot = document.createElement('span');
    dot.className = `mt-1.5 h-2 w-2 shrink-0 rounded-full ${
        task.is_overdue ? 'bg-peligro' : task.status === 'TERMINADOS' ? 'bg-exito' : task.status === 'PROCESO' ? 'bg-proceso' : 'bg-aviso'
    }`;

    const body = document.createElement('span');
    body.className = 'min-w-0 flex-1';

    const title = document.createElement('span');
    title.className = 'block break-words text-[13px] font-extrabold uppercase tracking-tight text-negro [overflow-wrap:anywhere]';
    title.textContent = task.title;

    const meta = document.createElement('span');
    meta.className = task.is_overdue
        ? 'mt-0.5 block text-[10px] font-semibold text-peligro'
        : 'mt-0.5 block text-[10px] font-medium text-gris';
    meta.textContent = `${task.team || 'Personal'} · ${formatDueDate(task.due_date)}${task.is_overdue ? ' · Vencida' : ''}`;

    body.append(title, meta);

    const progress = document.createElement('span');
    progress.className = 'shrink-0 text-[11px] font-extrabold text-primario numeric';
    progress.textContent = `${task.progress_percentage}%`;

    button.append(dot, body, progress);

    return button;
}

function createBoardTaskCard(task, isCarousel = true) {
    const card = document.createElement('article');
    card.dataset.taskViewId = task.id;
    card.className = `group relative flex ${isCarousel ? 'w-[min(84vw,380px)] min-w-[280px] shrink-0 cursor-pointer' : 'w-full cursor-grab active:cursor-grabbing'} flex-col rounded-[20px] bg-primario p-5 text-left text-white shadow-[0_14px_30px_rgba(78,27,35,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_20px_38px_rgba(78,27,35,0.28)] focus-within:ring-2 focus-within:ring-primario/50 focus-within:ring-offset-2 focus-within:ring-offset-fondo ${task.is_overdue ? 'ring-2 ring-peligro/80' : ''}`;

    if (!isCarousel) {
        card.draggable = true;
        card.title = 'Arrastra la tarjeta a otra columna, o usa las flechas izquierda y derecha para cambiar de estado.';
    }

    const header = document.createElement('div');
    header.className = 'flex flex-wrap items-center gap-1.5';

    const statusStyles = {
        INICIADO: 'bg-white text-[#0F6B5F]',
        PROCESO: 'bg-white text-[#5A3E08]',
        TERMINADOS: 'bg-white text-[#1F7A4A]',
        OVERDUE: 'bg-[#F9E3E3] text-[#A02A2A]',
    };
    const statusDots = {
        INICIADO: 'bg-proceso',
        PROCESO: 'bg-aviso',
        TERMINADOS: 'bg-exito',
        OVERDUE: 'bg-peligro',
    };

    const visualStatus = task.is_overdue ? 'OVERDUE' : task.status;
    const statusBadge = document.createElement('span');
    statusBadge.className = `inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[8px] font-extrabold uppercase tracking-[0.12em] ${statusStyles[visualStatus]}`;
    const statusDot = document.createElement('span');
    statusDot.className = `h-1.5 w-1.5 rounded-full ${statusDots[visualStatus]}`;
    const badgeLabels = {
        INICIADO: 'Iniciado',
        PROCESO: 'Proceso',
        TERMINADOS: 'Terminado',
        OVERDUE: 'Vencida',
    };
    statusBadge.append(statusDot, document.createTextNode(badgeLabels[visualStatus]));

    header.append(statusBadge);

    if (task.is_important) {
        const priorityBadge = document.createElement('span');
        priorityBadge.className = 'inline-flex items-center rounded-full bg-aviso px-2.5 py-1 text-[8px] font-extrabold uppercase tracking-[0.12em] text-[#4A3507]';
        priorityBadge.textContent = 'Importante';
        header.append(priorityBadge);
    }

    const title = document.createElement('h2');
    title.className = 'mt-4 text-[21px] font-extrabold uppercase leading-[1.08] tracking-tight text-white [overflow-wrap:anywhere]';
    title.textContent = task.title;

    const context = document.createElement('p');
    context.className = 'mt-1.5 truncate text-[9px] font-bold uppercase tracking-[0.12em] text-white/70';
    context.textContent = task.team || 'Personal';

    const meta = document.createElement('div');
    meta.className = 'mt-4 flex items-center justify-between gap-2 text-[10px] font-extrabold uppercase tracking-[0.08em] text-white';

    const taskCount = document.createElement('span');
    taskCount.className = 'inline-flex items-center gap-1.5';
    const completedSubtasks = task.subtasks.filter((subtask) => subtask.done).length;
    const taskCountLabel = document.createElement('span');
    taskCountLabel.textContent = 'Tareas';
    const taskCountPill = document.createElement('span');
    taskCountPill.className = 'rounded-full bg-white/15 px-2 py-0.5 text-[10px] numeric';
    taskCountPill.textContent = `${completedSubtasks}/${task.subtasks.length}`;
    taskCount.append(taskCountLabel, taskCountPill);

    const percentage = document.createElement('span');
    percentage.className = 'numeric';
    percentage.textContent = `${task.progress_percentage}%`;
    meta.append(taskCount, percentage);

    const progressTrack = document.createElement('div');
    progressTrack.className = 'mt-2 h-2 overflow-hidden rounded-full bg-white/25';

    const progressFill = document.createElement('div');
    progressFill.className = 'h-full rounded-full bg-[#4CC38A]';
    progressFill.style.width = `${task.progress_percentage}%`;
    progressTrack.append(progressFill);

    const dateGrid = document.createElement('div');
    dateGrid.className = 'mt-4 grid grid-cols-2 gap-2';

    const createDateCell = (label, date) => {
        const cell = document.createElement('div');
        cell.className = 'rounded-lg bg-[#FFFAFA] px-3 py-2 text-left';

        const caption = document.createElement('span');
        caption.className = 'block text-[8px] font-semibold text-gris';
        caption.textContent = label;

        const value = document.createElement('span');
        value.className = 'mt-0.5 block whitespace-nowrap text-[12px] font-extrabold uppercase text-negro numeric';
        const dateParts = date ? date.split('-') : [];
        value.textContent = dateParts.length === 3
            ? `${dateParts[2]} - ${dateParts[1]} - ${dateParts[0]}`
            : 'Por definir';
        cell.append(caption, value);

        return cell;
    };

    const startDate = createDateCell('Inicio', task.start_date);
    const endDate = createDateCell('Fin', task.due_date);

    dateGrid.append(startDate, endDate);

    const footer = document.createElement('div');
    footer.className = 'mt-4 flex items-center justify-between gap-2';

    const assignment = document.createElement('span');
    assignment.className = 'flex min-w-0 items-center';

    const people = task.assignees.length > 0
        ? task.assignees
        : task.responsible_name
            ? [{ id: task.responsible_id, name: task.responsible_name }]
            : [];
    const avatarStyles = [
        { background: 'bg-[#2B4BB5]', text: 'text-white' },
        { background: 'bg-[#C99AA4]', text: 'text-[#3A1F26]' },
        { background: 'bg-[#1FA08E]', text: 'text-[#06342F]' },
        { background: 'bg-[#E7C063]', text: 'text-[#3F2E06]' },
    ];

    people.slice(0, 3).forEach((person, index) => {
        const style = avatarStyles[index % avatarStyles.length];
        const avatar = document.createElement('span');
        avatar.className = `-ml-1.5 flex h-8 w-8 shrink-0 first:ml-0 items-center justify-center rounded-full border-2 border-primario text-[9px] font-extrabold ${style.background} ${style.text}`;
        avatar.title = person.name;
        avatar.textContent = person.name.split(/\s+/).slice(0, 2).map((part) => part.charAt(0).toUpperCase()).join('');
        assignment.append(avatar);
    });

    if (people.length > 3) {
        const more = document.createElement('span');
        more.className = '-ml-1.5 flex h-8 w-8 items-center justify-center rounded-full border-2 border-primario bg-white text-[9px] font-extrabold text-primario';
        more.textContent = `+${people.length - 3}`;
        assignment.append(more);
    }

    const action = document.createElement('button');
    action.type = 'button';
    action.setAttribute('aria-label', `Ver detalle de ${task.title}`);
    action.className = 'flex h-9 w-9 items-center justify-center rounded-full bg-[#FFFAFA] text-lg font-bold text-primario shadow-sm transition hover:bg-primario-suave focus:outline-none focus:ring-2 focus:ring-white/80';
    action.textContent = '→';

    footer.append(assignment, action);
    card.append(header, title, context, meta, progressTrack, dateGrid, footer);

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
    const projectSelect = taskForm.querySelector('#task-category');
    const teamSelect = taskForm.querySelector('#task-team');
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
    const detailStatusSelect = detailDialog.querySelector('#detail-status-select');
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
        gantt: root.querySelector('#home-gantt-view'),
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
    const viewHashes = {
        home: '#/home',
        tasks: '#/tareas',
        agenda: '#/calendario',
    };
    const viewFromHash = () => Object.entries(viewHashes)
        .find(([, hash]) => hash === window.location.hash)?.[0] ?? 'home';
    let activeView = viewFromHash();
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
            : 'mx-1 rounded-lg bg-[#E3F1E9] px-3 py-1.5 text-[11px] font-semibold text-[#1F7A4A]';

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

    projectSelect.addEventListener('change', () => {
        const teamId = projectSelect.selectedOptions[0]?.dataset.teamId || '';

        if (teamId !== '') {
            teamSelect.value = teamId;
        }
    });

    teamSelect.addEventListener('change', () => {
        const projectOption = projectSelect.selectedOptions[0];

        if (projectOption?.value && projectOption.dataset.teamId !== teamSelect.value) {
            projectSelect.value = '';
        }
    });

    const startDateInput = taskForm.querySelector('#task-start-date');
    const endDateInput = taskForm.querySelector('#task-date');
    const syncDateBounds = () => {
        endDateInput.min = startDateInput.value;

        if (startDateInput.value !== '' && endDateInput.value !== '' && endDateInput.value < startDateInput.value) {
            endDateInput.value = startDateInput.value;
        }
    };
    startDateInput.addEventListener('change', syncDateBounds);

    const saveTasks = async (nextTasks) => {
        const added = nextTasks.filter((nextTask) => !tasks.some((task) => task.id === nextTask.id));
        const removed = tasks.filter((task) => !nextTasks.some((nextTask) => nextTask.id === task.id));
        const updated = nextTasks.filter((nextTask) => {
            const currentTask = tasks.find((task) => task.id === nextTask.id);

            return currentTask && JSON.stringify(currentTask) !== JSON.stringify(nextTask);
        });
        const changes = added.length + removed.length + updated.length;

        if (changes === 0) {
            return { ok: true, message: '' };
        }

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

    const TASK_STATUS_SEQUENCE = ['INICIADO', 'PROCESO', 'TERMINADOS'];
    const TASK_STATUS_BOARD_LABELS = {
        INICIADO: 'Iniciado',
        PROCESO: 'Proceso',
        TERMINADOS: 'Terminados',
    };

    const moveTaskToStatus = async (taskId, nextStatus) => {
        const baseTask = tasks.find((task) => task.id === String(taskId));

        if (!baseTask || !TASK_STATUS_SEQUENCE.includes(nextStatus) || baseTask.status === nextStatus) {
            return false;
        }

        const updatedTask = {
            ...baseTask,
            status: nextStatus,
            progress_percentage: nextStatus === 'TERMINADOS'
                ? 100
                : calculateProgress(baseTask.subtasks, nextStatus),
        };

        const result = await saveTasks(tasks.map((task) => (
            task.id === baseTask.id ? updatedTask : task
        )));

        if (result.ok) {
            announce(`Tarea movida a ${TASK_STATUS_BOARD_LABELS[nextStatus]}: ${baseTask.title}`);

            return true;
        }

        notify(result.message || 'No se pudo mover la tarea.', 'error');
        renderTaskViews();

        return false;
    };

    const updateNavigation = () => {
        viewButtons.forEach((button) => {
            const isActive = button.dataset.viewTarget === activeView;

            button.setAttribute('aria-selected', String(isActive));
            button.classList.toggle('border-primario', isActive);
            button.classList.toggle('bg-primario-suave', isActive);
            button.classList.toggle('border-transparent', !isActive);
            button.classList.toggle('bg-white', !isActive);

            const badge = button.querySelector('[data-nav-badge]');
            badge?.classList.toggle('hidden', !isActive);
        });

        const navToggle = root.querySelector('[data-nav-toggle]');
        navToggle?.classList.toggle('hidden', activeView === 'home');
        root.querySelector('#main-nav')?.classList.toggle('hidden', activeView !== 'home');

        Object.entries(views).forEach(([viewName, view]) => {
            view.classList.toggle('hidden', viewName !== activeView);
        });
    };

    const showView = (viewName, updateHistory = true) => {
        if (!Object.hasOwn(viewHashes, viewName)) {
            return;
        }

        activeView = viewName;

        if (updateHistory) {
            window.history.pushState({ view: viewName }, '', viewHashes[viewName]);
        }

        updateNavigation();
        renderTaskViews();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    root.querySelector('#home-overdue-link')?.addEventListener('click', () => showView('tasks'));

    window.addEventListener('popstate', () => {
        activeView = viewFromHash();
        updateNavigation();
        renderTaskViews();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    if (!Object.values(viewHashes).includes(window.location.hash)) {
        window.history.replaceState({ view: 'home' }, '', viewHashes.home);
    }

    updateNavigation();

    const GANTT_DAYS = 31;
    const ganttState = {
        rangeStart: shiftDate(selectedDate, -14),
    };

    const dayDifference = (later, earlier) => {
        const toUtc = (value) => {
            const [year, month, day] = value.split('-').map(Number);

            return Date.UTC(year, month - 1, day);
        };

        return Math.round((toUtc(later) - toUtc(earlier)) / 86400000);
    };

    const createGanttBar = (task) => {
        const startIndex = Math.max(0, dayDifference(task.start_date, ganttState.rangeStart));
        const endIndex = Math.min(GANTT_DAYS - 1, dayDifference(task.due_date, ganttState.rangeStart));
        const palette = [
            { background: 'bg-[#0E3A44]', text: 'text-white' },
            { background: 'bg-[#2B4BB5]', text: 'text-white' },
            { background: 'bg-[#702632]', text: 'text-white' },
            { background: 'bg-[#C99AA4]', text: 'text-[#3A1F26]' },
            { background: 'bg-[#E7C063]', text: 'text-[#3F2E06]' },
        ];
        const color = palette[Number(task.project_id || 0) % palette.length];
        const statusLabel = { INICIADO: 'Iniciado', PROCESO: 'Proceso', TERMINADOS: 'Terminado' }[task.status];
        const statusText = { INICIADO: 'text-[#0F6B5F]', PROCESO: 'text-[#5A3E08]', TERMINADOS: 'text-[#1F7A4A]' }[task.status];
        const completedSubtasks = task.subtasks.filter((subtask) => subtask.done).length;
        const spanDays = endIndex - startIndex + 1;

        const bar = document.createElement('button');
        bar.type = 'button';
        bar.draggable = true;
        bar.dataset.ganttTaskId = task.id;
        bar.dataset.taskViewId = task.id;
        bar.style.gridColumn = `${startIndex + 1} / ${endIndex + 2}`;
        bar.className = `gantt-bar z-10 flex h-9 w-full min-w-[24px] cursor-grab items-center gap-2 justify-self-start overflow-hidden rounded-full px-3 text-left uppercase shadow-sm transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-primario/60 active:cursor-grabbing ${color.background} ${color.text}`;
        bar.title = `${task.title}: ${task.start_date} → ${task.due_date}. Arrastra para mover las fechas.`;
        bar.setAttribute('aria-label', `${task.title}, ${statusLabel}, del ${task.start_date} al ${task.due_date}. ${task.progress_percentage}% completado.`);

        if (task.is_important && spanDays >= 7) {
            const important = document.createElement('span');
            important.className = 'shrink-0 rounded-full bg-aviso px-2 py-0.5 text-[8px] font-extrabold tracking-[0.08em] text-[#4A3507]';
            important.textContent = 'Importante';
            bar.append(important);
        }

        if (spanDays >= 8) {
            const status = document.createElement('span');
            status.className = `hidden shrink-0 rounded-full bg-white/90 px-2 py-0.5 text-[8px] font-extrabold tracking-[0.08em] sm:inline ${statusText}`;
            status.textContent = statusLabel;
            bar.append(status);
        }

        const title = document.createElement('span');
        title.className = 'gantt-bar-title min-w-0 flex-1 overflow-hidden text-[10px] font-extrabold tracking-[0.04em]';
        const track = document.createElement('span');
        track.className = 'gantt-marquee-track';
        const titleText = document.createElement('span');
        titleText.textContent = task.title;
        const titleClone = document.createElement('span');
        titleClone.className = 'gantt-title-clone';
        titleClone.textContent = task.title;
        titleClone.setAttribute('aria-hidden', 'true');
        track.append(titleText, titleClone);
        title.append(track);
        bar.append(title);

        if (spanDays >= 8) {
            const counts = document.createElement('span');
            counts.className = 'hidden shrink-0 text-[9px] font-extrabold numeric sm:inline';
            counts.textContent = `${completedSubtasks}/${task.subtasks.length}`;
            bar.append(counts);
        }

        if (spanDays >= 3) {
            const progress = document.createElement('span');
            progress.className = 'shrink-0 text-[9px] font-extrabold numeric';
            progress.textContent = `${task.progress_percentage}%`;
            bar.append(progress);
        }

        const avatars = document.createElement('span');
        avatars.className = 'hidden shrink-0 items-center md:flex';

        if (spanDays >= 10) {
            (task.assignees.length > 0 ? task.assignees : task.responsible_name ? [{ id: task.responsible_id, name: task.responsible_name }] : [])
                .slice(0, 3)
                .forEach((person, index) => {
                    const avatar = document.createElement('span');
                    avatar.className = `-ml-1 flex h-5 w-5 items-center justify-center rounded-full border border-white/40 text-[7px] font-extrabold text-white ${['bg-[#0F6B5F]', 'bg-[#912740]', 'bg-[#2B4BB5]'][index]}`;
                    avatar.textContent = person.name.split(/\s+/).slice(0, 2).map((part) => part.charAt(0).toUpperCase()).join('');
                    avatars.append(avatar);
                });
        }

        bar.append(avatars);

        return bar;
    };

    const renderGanttView = () => {
        ganttState.rangeStart = shiftDate(selectedDate, -14);
        const rangeEnd = shiftDate(ganttState.rangeStart, GANTT_DAYS - 1);
        const startDate = dateFromString(ganttState.rangeStart);
        const monthFormatter = new Intl.DateTimeFormat('es', { month: 'long', year: 'numeric' });
        const monthLabels = [-1, 0, 1].map((offset) => monthFormatter
            .format(new Date(startDate.getFullYear(), startDate.getMonth() + offset, 1))
            .replace(' de ', ' '));

        root.querySelector('#gantt-previous-month').textContent = monthLabels[0];
        root.querySelector('#gantt-current-month').textContent = monthLabels[1];
        root.querySelector('#gantt-next-month').textContent = monthLabels[2];

        const weekdayFormatter = new Intl.DateTimeFormat('es', { weekday: 'narrow' });
        const daysElement = root.querySelector('#gantt-days');
        daysElement.style.gridTemplateColumns = `repeat(${GANTT_DAYS}, minmax(0, 1fr))`;

        const today = toDateString(new Date());
        daysElement.replaceChildren(...Array.from({ length: GANTT_DAYS }, (_, index) => {
            const dateString = shiftDate(ganttState.rangeStart, index);
            const date = dateFromString(dateString);
            const cell = document.createElement('div');
            cell.className = `flex flex-col items-center gap-0.5 py-1 text-[9px] ${dateString === today ? 'font-extrabold text-primario' : 'text-gris'}`;

            const number = document.createElement('span');
            number.className = 'font-bold numeric';
            number.textContent = String(date.getDate());

            const weekday = document.createElement('span');
            weekday.className = 'uppercase';
            weekday.textContent = weekdayFormatter.format(date).charAt(0).toUpperCase();

            cell.append(number, weekday);

            return cell;
        }));

        const weekends = root.querySelector('#gantt-weekends');
        weekends.style.gridTemplateColumns = `repeat(${GANTT_DAYS}, minmax(0, 1fr))`;
        weekends.replaceChildren(...Array.from({ length: GANTT_DAYS }, (_, index) => {
            const date = dateFromString(shiftDate(ganttState.rangeStart, index));
            const cell = document.createElement('div');
            cell.className = date.getDay() === 0 || date.getDay() === 6 ? 'bg-negro/[0.04]' : '';

            return cell;
        }));

        const todayIndex = dayDifference(today, ganttState.rangeStart);
        const todayLine = root.querySelector('#gantt-today-line');
        todayLine.classList.toggle('hidden', todayIndex < 0 || todayIndex >= GANTT_DAYS);
        todayLine.style.left = `${((todayIndex + 0.5) / GANTT_DAYS) * 100}%`;

        const focusDate = dateFromString(selectedDate);
        root.querySelector('#gantt-focus-date').textContent = new Intl.DateTimeFormat('es', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(focusDate);

        const visibleTasks = tasks
            .filter((task) => task.start_date && task.due_date && task.due_date >= ganttState.rangeStart && task.start_date <= rangeEnd)
            .sort((first, second) => first.start_date.localeCompare(second.start_date) || first.title.localeCompare(second.title));

        const rowsElement = root.querySelector('#gantt-rows');

        if (visibleTasks.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'rounded-xl border border-dashed border-borde bg-white/60 px-4 py-10 text-center text-sm font-medium text-gris';
            empty.textContent = 'No hay metas en este rango de fechas.';
            rowsElement.replaceChildren(empty);

            return;
        }

        rowsElement.replaceChildren(...visibleTasks.map((task) => {
            const row = document.createElement('div');
            row.className = 'grid items-center';
            row.style.gridTemplateColumns = `repeat(${GANTT_DAYS}, minmax(0, 1fr))`;
            row.setAttribute('role', 'listitem');
            row.append(createGanttBar(task));

            return row;
        }));

        window.requestAnimationFrame(() => {
            rowsElement.querySelectorAll('.gantt-bar-title').forEach((title) => {
                const track = title.querySelector('.gantt-marquee-track');
                const text = track?.firstElementChild;
                const overflow = text ? text.scrollWidth - title.clientWidth : 0;

                if (overflow > 2) {
                    title.style.setProperty('--marquee-duration', `${Math.max(6, Math.round(text.scrollWidth / 35))}s`);
                    title.classList.add('is-overflowing');
                } else {
                    title.classList.remove('is-overflowing');
                    title.style.removeProperty('--marquee-duration');
                }
            });
        });
    };

    const renderHomeView = () => {
        const dateTasks = tasks
            .filter((task) => taskOccursOnDate(task, selectedDate))
            .sort((first, second) => first.title.localeCompare(second.title));
        const previousDate = shiftDate(selectedDate, -1);
        const nextDate = shiftDate(selectedDate, 1);
        const dateStats = root.querySelector('#selected-date-stats');
        const overdueTasks = tasks.filter((task) => task.is_overdue);

        root.querySelector('#previous-date-label').textContent = formatCarouselDate(previousDate, false);
        root.querySelector('#next-date-label').textContent = formatCarouselDate(nextDate, false);
        root.querySelector('#selected-date-label').textContent = formatCarouselDate(selectedDate);
        root.querySelector('#agenda-selected-date').textContent = formatCarouselDate(selectedDate);

        const overdueBanner = root.querySelector('#home-overdue-banner');
        overdueBanner?.classList.toggle('hidden', overdueTasks.length === 0);
        root.querySelector('#home-overdue-text').textContent = overdueTasks.length === 1
            ? 'Tienes 1 tarea vencida sin completar.'
            : `Tienes ${overdueTasks.length} tareas vencidas sin completar.`;

        const overdueToday = dateTasks.filter((task) => task.is_overdue).length;
        const metrics = [
            { text: `${dateTasks.length} metas`, className: 'font-bold text-primario' },
            { text: `${dateTasks.reduce((total, task) => total + task.subtasks.length, 0)} tareas`, className: 'font-bold text-gris' },
            { text: `${dateTasks.filter((task) => task.status === 'TERMINADOS').length} terminadas`, className: 'font-bold text-[#1F7A4A]' },
            { text: `${dateTasks.filter((task) => task.status === 'INICIADO').length} iniciadas`, className: 'font-bold text-[#0F6B5F]' },
        ];

        if (overdueToday > 0) {
            metrics.push({ text: `${overdueToday} ${overdueToday === 1 ? 'vencida' : 'vencidas'} hoy`, className: 'font-bold text-[#A02A2A]' });
        }
        const statsElements = [];

        metrics.forEach((metric, index) => {
            if (index > 0) {
                const separator = document.createElement('span');
                separator.className = 'text-gris/40';
                separator.textContent = '·';
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
        const boardCards = dateTasks.map((task) => createBoardTaskCard(task));

        root.querySelector('#home-board-columns').replaceChildren(...boardCards);
        root.querySelector('#home-board-columns').classList.toggle('hidden', dateTasks.length === 0);
        root.querySelector('#home-board-empty').classList.toggle('hidden', dateTasks.length > 0);

        root.querySelector('#home-date-header').classList.toggle('hidden', homeMode !== 'board');
        renderGanttView();

        const monthLabel = new Intl.DateTimeFormat('es', {
            month: 'long',
            year: 'numeric',
        }).format(displayedCalendarMonth).replace(' de ', ' ');
        root.querySelector('#home-calendar-month').textContent = monthLabel;

        const firstMonthDay = new Date(
            displayedCalendarMonth.getFullYear(),
            displayedCalendarMonth.getMonth(),
            1
        );
        const calendarStart = new Date(firstMonthDay);
        calendarStart.setDate(calendarStart.getDate() - calendarStart.getDay());
        const calendarButtons = Array.from({ length: 42 }, (_, index) => {
            const date = new Date(calendarStart);
            date.setDate(calendarStart.getDate() + index);
            const dateString = toDateString(date);
            const isSelected = dateString === selectedDate;
            const isToday = dateString === toDateString(new Date());
            const isCurrentMonth = date.getMonth() === displayedCalendarMonth.getMonth();
            const dateTasks = tasks
                .filter((task) => taskOccursOnDate(task, dateString))
                .sort((first, second) => first.title.localeCompare(second.title));
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.calendarDate = dateString;
            button.setAttribute('aria-label', formatCarouselDate(dateString));
            button.setAttribute('aria-pressed', String(isSelected));
            button.className = `flex min-h-[72px] flex-col gap-1 rounded-lg border p-1.5 text-left transition focus:outline-none focus:ring-2 focus:ring-primario/40 sm:min-h-[88px] ${
                isSelected
                    ? 'border-2 border-primario bg-primario-suave'
                    : isCurrentMonth
                        ? `border-borde bg-[#FDFBFA] hover:border-primario/40 ${isToday ? 'ring-2 ring-primario/40' : ''}`
                        : 'border-transparent bg-fondo/40 text-gris hover:border-borde'
            }`;

            const dayNumber = document.createElement('span');
            dayNumber.className = 'text-[10px] font-bold leading-none numeric';
            dayNumber.textContent = String(date.getDate());
            button.append(dayNumber);

            if (isCurrentMonth) {
                const statusChip = {
                    INICIADO: 'bg-[#FBF3DF] text-[#5A3E08]',
                    PROCESO: 'bg-[#E2F2F0] text-[#0F6B5F]',
                    TERMINADOS: 'bg-[#E5F3EC] text-[#1F7A4A]',
                };

                dateTasks.slice(0, 2).forEach((task) => {
                    const chip = document.createElement('span');
                    chip.className = `block truncate rounded-full px-1.5 py-0.5 text-[8px] font-bold leading-tight ${statusChip[task.status]}`;
                    chip.textContent = `● ${task.title}`;
                    chip.title = task.title;
                    button.append(chip);
                });

                if (dateTasks.length > 2) {
                    const more = document.createElement('span');
                    more.className = 'pl-1 text-[8px] font-bold text-gris';
                    more.textContent = `+${dateTasks.length - 2}`;
                    button.append(more);
                }
            }

            return button;
        });

        root.querySelector('#home-calendar-days').replaceChildren(...calendarButtons);
        const selectedDateObject = dateFromString(selectedDate);
        const weekdayLabel = new Intl.DateTimeFormat('es', { weekday: 'long' }).format(selectedDateObject);
        const monthName = new Intl.DateTimeFormat('es', { month: 'long' }).format(selectedDateObject);
        root.querySelector('#agenda-day-title').textContent = `${weekdayLabel} ${selectedDateObject.getDate()} · ${monthName} · ${selectedDateObject.getFullYear()}`;
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
            { status: 'INICIADO', title: 'Iniciado', color: 'bg-tab-iniciado' },
            { status: 'PROCESO', title: 'Proceso', color: 'bg-tab-proceso' },
            { status: 'TERMINADOS', title: 'Terminados', color: 'bg-tab-terminado' },
        ];
        const taskColumns = statusColumns.map(({ status, title, color }) => {
            const column = document.createElement('section');
            column.className = 'flex flex-col';

            const heading = document.createElement('h2');
            heading.className = `relative z-10 mx-2.5 -mb-3 flex items-center justify-between rounded-t-2xl border-2 border-b-0 border-negro px-4 py-2.5 text-[10px] font-extrabold uppercase tracking-[0.1em] text-negro ${color}`;

            const headingTitle = document.createElement('span');
            headingTitle.textContent = title;

            const columnTasks = sortedTasks.filter((task) => task.status === status);
            const count = document.createElement('span');
            count.className = 'numeric';
            count.textContent = String(columnTasks.length).padStart(2, '0');
            heading.append(headingTitle, count);

            const list = document.createElement('div');
            list.className = 'flex-1 space-y-3 rounded-2xl border-2 border-negro bg-white/50 p-3 pt-5 transition-colors';
            list.dataset.dropStatus = status;

            if (columnTasks.length > 0) {
                list.replaceChildren(...columnTasks.map((task) => createBoardTaskCard(task, false)));
            } else {
                const empty = document.createElement('p');
                empty.className = 'flex h-full min-h-[140px] items-center justify-center rounded-xl border border-dashed border-borde bg-white px-3 py-6 text-center text-xs font-medium text-gris';
                empty.textContent = 'Sin tareas';
                list.append(empty);
            }

            column.append(heading, list);

            return column;
        });
        const taskList = root.querySelector('#tasks-view-list');

        taskList.replaceChildren(...taskColumns);
        taskList.classList.toggle('hidden', tasks.length === 0);
        const overdueCount = tasks.filter((task) => task.is_overdue).length;
        const tasksViewDate = root.querySelector('#tasks-view-date');
        tasksViewDate.textContent = overdueCount > 0 ? `Todas tus tareas · ${overdueCount} ${overdueCount === 1 ? 'vencida' : 'vencidas'}` : 'Todas tus tareas';
        tasksViewDate.classList.toggle('font-semibold', overdueCount > 0);
        tasksViewDate.classList.toggle('text-peligro', overdueCount > 0);
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
            const reminder = root.querySelector('#header-reminder');
            if (reminder) {
                reminder.textContent = 'Sin vencimientos próximos';
            }
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

        const reminder = root.querySelector('#header-reminder');
        if (reminder) {
            const daysUntilDue = Math.ceil((deadline - Date.now()) / (24 * 60 * 60 * 1000));
            reminder.textContent = Date.now() > deadline
                ? `La meta “${task.title}” venció el ${formatDueDate(task.due_date)}`
                : `La meta “${task.title}” vence en ${daysUntilDue} ${daysUntilDue === 1 ? 'día' : 'días'}`;
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
            title.className = 'block break-words font-bold text-negro [overflow-wrap:anywhere]';
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
        detailDialog.querySelector('#detail-overdue').classList.toggle('hidden', !task.is_overdue);
        detailDialog.querySelector('#detail-status-select').value = API_STATUS_BY_TASK_STATUS[task.status] ?? 'pendiente';
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
                ? 'min-w-0 flex-1 break-words text-xs font-medium text-gris line-through [overflow-wrap:anywhere]'
                : 'min-w-0 flex-1 break-words text-xs font-medium text-gris [overflow-wrap:anywhere]';

            const header = document.createElement('div');
            header.className = 'flex min-w-0 items-center justify-between gap-2';

            const state = document.createElement('span');
            state.className = subtask.done
                ? 'shrink-0 rounded-full bg-[#E3F1E9] px-2 py-1 text-[9px] font-bold text-[#1F7A4A]'
                : 'shrink-0 rounded-full bg-primario-suave px-2 py-1 text-[9px] font-bold text-primario';
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
        taskForm.querySelector('#task-status').value = 'pendiente';
        taskForm.querySelector('#task-start-date').value = toDateString(new Date());
        syncDateBounds();
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
            taskForm.querySelector('#task-category').value = task.project_id || '';
            taskForm.querySelector('#task-team').value = task.team_id || '';
            responsibleSelect.value = task.responsible_id || '';
            taskForm.querySelector('#task-title').value = task.title;
            taskForm.querySelector('#task-description').value = task.description || '';
            taskForm.querySelector('#task-status').value = API_STATUS_BY_TASK_STATUS[task.status] ?? 'pendiente';
            taskForm.querySelector('#task-priority').value = task.priority || 'medium';
            taskForm.querySelector('#task-start-date').value = task.start_date || '';
            taskForm.querySelector('#task-date').value = task.due_date || '';
            taskForm.querySelector('#task-subtasks').value = task.subtasks.map((subtask) => subtask.text).join('\n');
            syncDateBounds();
        }

        newTaskDialog.showModal();
        taskSubmitButton.disabled = false;
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
        const teamOption = taskForm.querySelector('#task-team').selectedOptions[0];
        const projectOption = taskForm.querySelector('#task-category').selectedOptions[0];
        const task = normalizeTask({
            id: baseTask?.id ?? createTaskId(),
            category: projectOption?.value ? projectOption.textContent.trim() : 'GENERAL',
            project_id: String(formData.get('project_id') || '') || null,
            title,
            description: String(formData.get('description') || '').trim(),
            status: String(formData.get('status') || 'pendiente'),
            priority: String(formData.get('priority') || 'medium'),
            team: teamOption?.value ? teamOption.textContent.trim() : '',
            team_id: String(formData.get('team_id') || '') || null,
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
            showView(button.dataset.viewTarget);
        });
    });

    root.querySelector('[data-nav-toggle]')?.addEventListener('click', () => {
        showView('home');
    });

    root.querySelector('[data-calendar-today]')?.addEventListener('click', () => {
        selectedDate = toDateString(new Date());
        displayedCalendarMonth = startOfMonth(selectedDate);
        renderHomeView();
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

        container.addEventListener('keydown', (event) => {
            if (!['Enter', ' '].includes(event.key) || !(event.target instanceof Element)) {
                return;
            }

            const button = event.target.closest('[data-task-view-id]');
            const task = tasks.find((item) => item.id === button?.dataset.taskViewId);

            if (task) {
                event.preventDefault();
                fillDetailDialog(task);
            }
        });
    });

    const ganttRows = root.querySelector('#gantt-rows');
    let draggedGanttTaskId = null;

    const moveGanttTaskByDays = async (task, deltaDays) => {
        if (!task || deltaDays === 0) {
            return false;
        }

        const updatedTask = {
            ...task,
            start_date: shiftDate(task.start_date, deltaDays),
            due_date: shiftDate(task.due_date, deltaDays),
        };
        const result = await saveTasks(tasks.map((item) => (item.id === task.id ? updatedTask : item)));

        if (result.ok) {
            announce(`Fechas actualizadas: ${task.title}`);

            return true;
        }

        notify(result.message || 'No se pudieron mover las fechas de la tarea.', 'error');
        renderTaskViews();

        return false;
    };

    ganttRows.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const bar = event.target.closest('[data-gantt-task-id]');
        const task = tasks.find((item) => item.id === bar?.dataset.ganttTaskId);

        if (task) {
            fillDetailDialog(task);
        }
    });

    ganttRows.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight'].includes(event.key) || !(event.target instanceof Element)) {
            return;
        }

        const bar = event.target.closest('[data-gantt-task-id]');
        const task = tasks.find((item) => item.id === bar?.dataset.ganttTaskId);

        if (!task) {
            return;
        }

        event.preventDefault();
        void moveGanttTaskByDays(task, event.key === 'ArrowRight' ? 1 : -1).then((moved) => {
            if (moved) {
                root.querySelector(`#gantt-rows [data-gantt-task-id="${task.id}"]`)?.focus();
            }
        });
    });

    ganttRows.addEventListener('dragstart', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const bar = event.target.closest('[data-gantt-task-id]');

        if (!bar || event.dataTransfer === null) {
            return;
        }

        draggedGanttTaskId = bar.dataset.ganttTaskId;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', draggedGanttTaskId);
        bar.classList.add('opacity-60');
    });

    ganttRows.addEventListener('dragend', (event) => {
        if (event.target instanceof Element) {
            event.target.closest('[data-gantt-task-id]')?.classList.remove('opacity-60');
        }

        draggedGanttTaskId = null;
        root.querySelector('#gantt-drop-line')?.classList.add('hidden');
    });

    ganttRows.addEventListener('dragover', (event) => {
        if (draggedGanttTaskId === null) {
            return;
        }

        event.preventDefault();

        if (event.dataTransfer !== null) {
            event.dataTransfer.dropEffect = 'move';
        }

        const bounds = ganttRows.getBoundingClientRect();
        const ratio = Math.min(1, Math.max(0, (event.clientX - bounds.left) / bounds.width));
        const dropLine = root.querySelector('#gantt-drop-line');

        if (dropLine) {
            dropLine.style.left = `${ratio * 100}%`;
            dropLine.classList.remove('hidden');
        }
    });

    ganttRows.addEventListener('drop', (event) => {
        if (draggedGanttTaskId === null) {
            return;
        }

        event.preventDefault();
        const task = tasks.find((item) => item.id === draggedGanttTaskId);
        const bounds = ganttRows.getBoundingClientRect();
        const ratio = Math.min(0.999, Math.max(0, (event.clientX - bounds.left) / bounds.width));
        const dropDate = shiftDate(ganttState.rangeStart, Math.floor(ratio * GANTT_DAYS));
        draggedGanttTaskId = null;
        root.querySelector('#gantt-drop-line')?.classList.add('hidden');

        if (!task) {
            return;
        }

        void moveGanttTaskByDays(task, dayDifference(dropDate, task.start_date));
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

    const taskBoard = root.querySelector('#tasks-view-list');
    let draggedTaskId = null;
    const dropHighlightClasses = ['ring-2', 'ring-primario/60', 'bg-primario-suave/40'];

    const clearDropHighlights = () => {
        taskBoard.querySelectorAll('[data-drop-status]').forEach((list) => {
            list.classList.remove(...dropHighlightClasses);
        });
    };

    taskBoard.addEventListener('dragstart', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const card = event.target.closest('[data-task-view-id]');

        if (!card || !card.draggable || event.dataTransfer === null) {
            return;
        }

        draggedTaskId = card.dataset.taskViewId;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', draggedTaskId);
        card.classList.add('opacity-50');
    });

    taskBoard.addEventListener('dragend', (event) => {
        if (event.target instanceof Element) {
            event.target.closest('[data-task-view-id]')?.classList.remove('opacity-50');
        }

        clearDropHighlights();
        draggedTaskId = null;
    });

    taskBoard.addEventListener('dragover', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const list = event.target.closest('[data-drop-status]');

        if (!list || draggedTaskId === null) {
            return;
        }

        event.preventDefault();

        if (event.dataTransfer !== null) {
            event.dataTransfer.dropEffect = 'move';
        }

        taskBoard.querySelectorAll('[data-drop-status]').forEach((other) => {
            if (other !== list) {
                other.classList.remove(...dropHighlightClasses);
            }
        });
        list.classList.add(...dropHighlightClasses);
    });

    taskBoard.addEventListener('dragleave', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const list = event.target.closest('[data-drop-status]');

        if (list && !(event.relatedTarget instanceof Node && list.contains(event.relatedTarget))) {
            list.classList.remove(...dropHighlightClasses);
        }
    });

    taskBoard.addEventListener('drop', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const list = event.target.closest('[data-drop-status]');

        if (!list) {
            return;
        }

        event.preventDefault();
        clearDropHighlights();
        const taskId = draggedTaskId || event.dataTransfer?.getData('text/plain') || '';
        draggedTaskId = null;

        if (taskId !== '') {
            void moveTaskToStatus(taskId, list.dataset.dropStatus);
        }
    });

    taskBoard.addEventListener('keydown', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const card = event.target.closest('[data-task-view-id]');
        const task = tasks.find((item) => item.id === card?.dataset.taskViewId);

        if (!task) {
            return;
        }

        if (['Enter', ' '].includes(event.key)) {
            event.preventDefault();
            fillDetailDialog(task);

            return;
        }

        if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) {
            return;
        }

        const currentIndex = TASK_STATUS_SEQUENCE.indexOf(task.status);
        const nextStatus = TASK_STATUS_SEQUENCE[currentIndex + (event.key === 'ArrowRight' ? 1 : -1)];

        if (nextStatus === undefined) {
            return;
        }

        event.preventDefault();
        void moveTaskToStatus(task.id, nextStatus).then((moved) => {
            if (moved) {
                root.querySelector(`#tasks-view-list [data-task-view-id="${task.id}"]`)?.focus();
            }
        });
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

    detailDialog.querySelector('[data-save-detail]').addEventListener('click', () => {
        const task = tasks.find((item) => item.id === detailDialog.dataset.taskId);
        detailDialog.close();
        announce(task ? `Cambios guardados: ${task.title}` : 'Cambios guardados.');
    });

    detailDialog.querySelector('[data-delete-task]').addEventListener('click', () => {
        const task = tasks.find((item) => item.id === detailDialog.dataset.taskId);

        void deleteTask(task);
    });

    detailStatusSelect.addEventListener('change', () => {
        const task = tasks.find((item) => item.id === detailDialog.dataset.taskId);
        const nextStatus = normalizeTaskStatus(detailStatusSelect.value);

        if (!isAvailable || !task || !nextStatus) {
            return;
        }

        void moveTaskToStatus(task.id, nextStatus).then((moved) => {
            if (moved) {
                const updatedTask = tasks.find((item) => item.id === task.id);
                detailDialog.querySelector('#detail-percent-value').textContent = `${updatedTask?.progress_percentage ?? task.progress_percentage}%`;
            } else {
                detailStatusSelect.value = API_STATUS_BY_TASK_STATUS[task.status] ?? 'pendiente';
            }
        });
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
