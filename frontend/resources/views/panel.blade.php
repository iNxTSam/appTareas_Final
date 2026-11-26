@extends('layouts.app')

@php
    $displayName = $apiUser['name'] ?? 'Usuario';
    $email = $apiUser['email'] ?? '';
    $responsibles = $accounts ?? [];
    $teamOptions = $teams ?? [];
    $projectOptions = $projects ?? [];
    $firstName = trim(explode(' ', $displayName)[0] ?? $displayName);
@endphp

@section('title', 'AppTareas')
@section('body-class', 'min-h-screen bg-fondo text-negro')
@section('main-class', 'w-full')

@section('content')
    <div
        id="task-app"
        class="flex min-h-screen flex-col overflow-x-hidden"
        data-tasks-url="{{ route('tasks.index') }}"
        data-task-url-template="{{ route('tasks.show', ['task' => '__task__']) }}"
        data-session-timeout="{{ (int) config('session.lifetime') * 60 }}"
    >
        @include('panel.partials.header')
        @include('panel.partials.navigation')
        @include('panel.partials.views')

        <div id="task-loading" class="mx-auto flex w-full max-w-3xl items-center justify-center gap-2 px-5 py-6 text-sm font-medium text-slate-500">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-primario/25 border-t-primario" aria-hidden="true"></span>
            Cargando tareas…
        </div>

        <div id="task-storage-error" class="mx-auto mb-4 hidden w-full max-w-3xl items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm leading-6 text-red-800" role="alert">
            <p id="task-storage-error-message" class="min-w-0 flex-1 break-words [overflow-wrap:anywhere]"></p>
            <button
                type="button"
                id="task-retry"
                class="shrink-0 rounded-full border border-red-300 bg-white px-3 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-300"
            >
                Reintentar
            </button>
        </div>

        <p id="task-status-announcer" class="sr-only" role="status" aria-live="polite"></p>

        <div id="task-toast" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 flex justify-center px-4 opacity-0 transition-opacity duration-200" role="status" aria-live="polite">
            <p id="task-toast-message" class="pointer-events-auto max-w-md break-words rounded-full bg-negro px-5 py-3 text-sm font-semibold text-white shadow-xl [overflow-wrap:anywhere]"></p>
        </div>

        <div id="session-warning" class="pointer-events-none fixed inset-x-0 bottom-6 z-[60] hidden px-4" role="alert" aria-live="assertive">
            <div class="pointer-events-auto mx-auto flex w-full max-w-md flex-col gap-3 rounded-2xl border border-aviso/40 bg-white px-5 py-4 text-sm shadow-xl sm:flex-row sm:items-center">
                <p class="min-w-0 flex-1 font-semibold leading-5 text-negro">Tu sesión expirará en <span id="session-warning-time">2 minutos</span> por inactividad.</p>
                <button type="button" id="session-warning-continue" class="btn-primary shrink-0 px-4 py-2 text-[10px]">Seguir conectado</button>
            </div>
        </div>

        @include('panel.partials.dialogs')

        <button
            type="button"
            data-open-new-task
            aria-label="Agregar una tarea"
            class="fixed bottom-6 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primario text-3xl font-light leading-none text-white shadow-[0_12px_26px_rgba(78,27,35,0.35)] transition hover:bg-primario-oscuro focus:outline-none focus:ring-2 focus:ring-primario/50 focus:ring-offset-2 focus:ring-offset-fondo sm:bottom-8 sm:right-8"
        >
            +
        </button>
    </div>
@endsection
