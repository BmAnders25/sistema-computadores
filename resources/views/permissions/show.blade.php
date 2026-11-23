@extends('adminlte::page')

@section('title', 'Detalles del Permiso')

@section('content_header')
    <section class="src-catalog-detail-heading">
        <nav aria-label="Migas de pan">
            <a href="{{ route('home') }}">S.R.C</a><span aria-hidden="true">/</span>
            <a href="{{ route('permissions.index') }}">Seguridad</a><span aria-hidden="true">/</span>
            <span aria-current="page">{{ $permission->name }}</span>
        </nav>
        <div class="src-catalog-detail-title-row">
            <h1>{{ $permission->name }}</h1>
            <div class="src-catalog-detail-actions">
                @can('editar-permisos')
                    <a class="src-catalog-primary-action" href="{{ route('permissions.edit', $permission->id) }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar permiso</a>
                @endcan
                <a class="src-catalog-secondary-action" href="{{ route('permissions.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </div>
    </section>
@stop

@section('content')
    @php
        $rolesAsignados = $permission->roles;
    @endphp
    <div class="src-catalog-detail-layout">
        <aside class="src-catalog-summary-card">
            <span class="src-catalog-summary-icon"><i class="fas fa-key" aria-hidden="true"></i></span>
            <h2>{{ $permission->name }}</h2>
            <p>Capacidad del sistema</p>
            <div class="src-catalog-detail-rows">
                <div class="src-catalog-detail-row"><span>Roles vinculados</span><strong>{{ $rolesAsignados->count() }}</strong></div>
            </div>
        </aside>

        <div class="src-catalog-detail-main">
            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-info-circle" aria-hidden="true"></i></span>
                    <h2>Información del permiso</h2>
                </header>
                <div class="src-catalog-detail-rows">
                    <div class="src-catalog-detail-row"><span>Nombre</span><strong>{{ $permission->name }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Guard</span><strong>{{ $permission->guard_name }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Roles asignados</span><strong>{{ $rolesAsignados->count() }}</strong></div>
                </div>
            </section>
            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-user-shield" aria-hidden="true"></i></span>
                    <h2>Roles que tienen este permiso</h2>
                </header>
                @if ($rolesAsignados->isNotEmpty())
                    <div class="src-role-chip-list">
                        @foreach ($rolesAsignados as $role)
                            <a href="{{ route('roles.show', $role->id) }}"><i class="fas fa-user-shield" aria-hidden="true"></i> {{ $role->name }}</a>
                        @endforeach
                    </div>
                @else
                    <p class="src-catalog-card-note mb-0">Este permiso todavía no está asignado a ningún rol.</p>
                @endif
            </section>
        </div>
    </div>
@stop
