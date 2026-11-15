@extends('layouts.app')

@php
    $displayName = $apiUser['name'] ?? 'Usuario';
    $email = $apiUser['email'] ?? '';
    $responsibles = [
        ['id' => 'nicolas', 'name' => 'Nicolas'],
        ['id' => 'daniel', 'name' => 'Daniel'],
        ['id' => 'samuel', 'name' => 'Samuel'],
        ['id' => 'feipe', 'name' => 'Feipe'],
        ['id' => 'jse', 'name' => 'Jse'],
    ];
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
    >
        @include('panel.partials.header')
        @include('panel.partials.navigation')
        @include('panel.partials.views')

        <div id="task-loading" class="mx-auto flex w-full max-w-3xl items-center justify-center gap-2 px-5 py-6 text-sm font-medium text-slate-500">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-primario/25 border-t-primario" aria-hidden="true"></span>
            Cargando tareas…
        </div>

        <div id="task-storage-error" class="mx-auto mb-4 hidden w-full max-w-3xl items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm leading-6 text-red-800" role="alert">
            <p id="task-storage-error-message" class="min-w-0 flex-1"></p>
            <button
                type="button"
                id="task-retry"
                class="shrink-0 rounded-full border border-red-300 bg-white px-3 py-1 text-xs font-semibold text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-300"
            >
                Reintentar
            </button>
        </div>

        <p id="task-status" class="sr-only" role="status" aria-live="polite"></p>

        <div id="task-toast" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 flex justify-center px-4 opacity-0 transition-opacity duration-200" role="status" aria-live="polite">
            <p id="task-toast-message" class="pointer-events-auto max-w-md rounded-full bg-negro px-5 py-3 text-sm font-semibold text-white shadow-xl"></p>
        </div>

        @include('panel.partials.dialogs')
    </div>
@endsection
