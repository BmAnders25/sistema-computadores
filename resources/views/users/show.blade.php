@extends('adminlte::page')

@section('title', 'Detalles del Usuario')

@section('content_header')
    <section class="src-catalog-detail-heading">
        <nav aria-label="Migas de pan">
            <a href="{{ route('home') }}">S.R.C</a><span aria-hidden="true">/</span>
            <a href="{{ route('users.index') }}">Administración</a><span aria-hidden="true">/</span>
            <span aria-current="page">{{ $user->name }}</span>
        </nav>
        <div class="src-catalog-detail-title-row">
            <h1>Perfil de usuario</h1>
            <div class="src-catalog-detail-actions">
                @can('editar-usuarios')
                    <a class="src-catalog-primary-action" href="{{ route('users.edit', $user->id) }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar usuario</a>
                @endcan
                <a class="src-catalog-secondary-action" href="{{ route('users.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver</a>
            </div>
        </div>
    </section>
@stop

@section('content')
    @php
        $isActive = $user->esActivo();
        $estadoLabel = $isActive ? 'Activo' : ($user->estado === 'suspendido' ? 'Suspendido' : 'Inactivo');
        $nombrePartes = preg_split('/\s+/', trim($user->name), -1, PREG_SPLIT_NO_EMPTY);
        $initials = collect($nombrePartes)->take(2)->map(fn ($parte) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($parte, 0, 1)))->implode('');
        $rolesUsuario = $user->getRoleNames()->implode(', ');
    @endphp

    <div class="src-catalog-detail-layout">
        <aside class="src-catalog-summary-card">
            <span class="src-user-avatar src-user-avatar-large" aria-hidden="true">{{ $initials }}</span>
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->email }}</p>
            <div class="src-computer-summary-badges">
                <span class="src-catalog-status src-catalog-status-{{ $isActive ? 'active' : 'inactive' }}"><span aria-hidden="true"></span>{{ $estadoLabel }}</span>
                <span class="src-catalog-badge">{{ $rolesUsuario ?: 'Sin rol asignado' }}</span>
            </div>
            <div class="src-catalog-detail-rows">
                <div class="src-catalog-detail-row"><span>Código institucional</span><strong>{{ $user->codigo_institucional ?: 'No registrado' }}</strong></div>
                <div class="src-catalog-detail-row"><span>Programa</span><strong>{{ $user->programa ?: 'No registrado' }}</strong></div>
            </div>
        </aside>

        <div class="src-catalog-detail-main">
            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-id-card" aria-hidden="true"></i></span>
                    <h2>Información de la cuenta</h2>
                </header>
                <div class="src-catalog-detail-rows">
                    <div class="src-catalog-detail-row"><span>Nombre completo</span><strong>{{ $user->name }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Correo electrónico</span><strong>{{ $user->email }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Rol asignado</span><strong>{{ $rolesUsuario ?: 'Sin rol asignado' }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Estado de la cuenta</span><strong>{{ $estadoLabel }}</strong></div>
                </div>
            </section>
            <section class="src-catalog-detail-card">
                <header>
                    <span class="src-catalog-detail-card-icon"><i class="fas fa-shield-alt" aria-hidden="true"></i></span>
                    <h2>Seguridad y datos institucionales</h2>
                </header>
                <div class="src-catalog-detail-rows">
                    <div class="src-catalog-detail-row"><span>Código institucional</span><strong>{{ $user->codigo_institucional ?: 'No registrado' }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Programa académico</span><strong>{{ $user->programa ?: 'No registrado' }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Verificación en dos pasos</span><strong>{{ $user->two_factor_enabled ? 'Habilitada' : 'No habilitada manualmente' }}</strong></div>
                    <div class="src-catalog-detail-row"><span>Correo institucional</span><strong>{{ $user->esInstitucional() ? 'Sí' : 'No' }}</strong></div>
                </div>
                <p class="src-catalog-card-note mb-0 mt-3">Los correos institucionales requieren verificación en dos pasos independientemente de la preferencia manual.</p>
            </section>
        </div>
    </div>
@stop
