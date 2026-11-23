@extends('adminlte::page')

@section('title', 'Detalles del Rol')

@section('content_header')
    <section class="src-catalog-detail-heading">
        <nav aria-label="Migas de pan">
            <a href="{{ route('home') }}">S.R.C</a><span aria-hidden="true">/</span>
            <a href="{{ route('roles.index') }}">Seguridad</a><span aria-hidden="true">/</span>
            <span aria-current="page">{{ $role->name }}</span>
        </nav>
        <div class="src-catalog-detail-title-row">
            <h1>{{ $role->name }}</h1>
            <div class="src-catalog-detail-actions">
                @can('editar-roles')
                    <a class="src-catalog-primary-action" href="{{ route('roles.edit', $role->id) }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar rol</a>
                @endcan
                <a class="src-catalog-secondary-action" href="{{ route('roles.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </div>
    </section>
@stop

@section('content')
    @php
        $roleUsersCount = $role->users()->count();
        $rolePermissionsCount = count($rolePermissions);
    @endphp
    <div class="src-catalog-detail-layout">
        <aside class="src-catalog-summary-card">
            <span class="src-catalog-summary-icon"><i class="fas fa-user-shield" aria-hidden="true"></i></span>
            <h2>{{ $role->name }}</h2>
            <p>Perfil de acceso al sistema</p>
            <div class="src-catalog-detail-rows">
                <div class="src-catalog-detail-row"><span>Usuarios asignados</span><strong>{{ $roleUsersCount }}</strong></div>
                <div class="src-catalog-detail-row"><span>Permisos asociados</span><strong>{{ $rolePermissionsCount }}</strong></div>
            </div>
        </aside>

        <div class="src-catalog-detail-main">
            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-key" aria-hidden="true"></i></span>
                    <h2>Permisos asignados ({{ $rolePermissionsCount }})</h2>
                </header>
                @include('roles._permission-groups', ['readOnly' => true, 'rolePermissions' => $rolePermissions])
            </section>
            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-users" aria-hidden="true"></i></span>
                    <h2>Usuarios con este rol</h2>
                </header>
                <p class="src-catalog-card-note mb-0">{{ $roleUsersCount }} {{ \Illuminate\Support\Str::plural('usuario', $roleUsersCount) }} tiene{{ $roleUsersCount === 1 ? '' : 'n' }} asignado este perfil.</p>
            </section>
        </div>
    </div>
@stop
