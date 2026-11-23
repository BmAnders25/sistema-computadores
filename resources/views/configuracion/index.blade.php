@extends('adminlte::page')

@section('title', 'Información institucional')

@section('content_header')
    <x-src-page-hero
        eyebrow="Configuración"
        title="Información institucional"
        description="Datos generales del sistema y su contexto institucional."
        icon="fas fa-university" />
@stop

@section('content')
    <section class="src-institution-card">
        <div class="src-institution-icon" aria-hidden="true">
            <i class="fas fa-university"></i>
        </div>
        <div>
            <span class="src-module-hero-eyebrow">Sistema</span>
            <h2>Sistema de Reserva de Computadores</h2>
            <p>Universidad de Pamplona · Sede Villa del Rosario</p>
        </div>
    </section>
@stop
