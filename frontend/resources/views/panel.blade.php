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

        <p id="task-storage-error" class="mx-auto mb-4 hidden w-full max-w-3xl rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm leading-6 text-red-800" role="alert"></p>
        <p id="task-status" class="sr-only" role="status" aria-live="polite"></p>

        @include('panel.partials.dialogs')
    </div>
@endsection
