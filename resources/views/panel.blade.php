@extends('layouts.app')

@section('title', 'Área privada')

@section('content')
    <h1 class="text-primario mb-1">Área privada</h1>
    <p class="text-gris mb-8 font-medium">Has iniciado sesión correctamente.</p>

    @if (session('success'))
        <p class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</p>
    @endif

    <div class="rounded-2xl border border-gris/20 bg-white p-6 shadow-sm">
        <p class="text-negro">
            Hola, <span class="font-semibold text-primario">{{ auth()->user()->name }}</span>.
        </p>
        <p class="mt-2 text-gris">Bienvenido a tu área privada.</p>
    </div>
@endsection
