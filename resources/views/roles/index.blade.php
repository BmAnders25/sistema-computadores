@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <x-src-page-hero
        eyebrow="Seguridad"
        title="Roles de usuarios"
        description="Organiza los perfiles de acceso y administra los permisos asignados a cada rol."
        icon="fas fa-user-cog" />
@stop

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success" role="status">{{ $message }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    @php
        $catalogHeaders = ['Rol', 'Usuarios asignados', 'Permisos asignados'];
    @endphp

    <div class="src-catalog-page" data-catalog-page data-catalog-title="Roles · S.R.C" data-catalog-filename="roles">
        <div class="src-catalog-create-row">
            @can('crear-roles')
                <a class="src-catalog-create-button" href="{{ route('roles.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Nuevo Rol</a>
            @endcan
        </div>

        <x-src-catalog-toolbar :filters="[
            ['label' => 'Todos los roles', 'value' => 'all', 'query' => '', 'count' => $totalRoles],
        ]" :selected-filter="request()->query('estado', '')" search-placeholder="Buscar por nombre de rol...">
        </x-src-catalog-toolbar>

        <section class="src-catalog-grid" data-catalog-grid data-catalog-headers="{{ json_encode($catalogHeaders) }}" aria-label="Roles registrados">
            @forelse ($roles as $rol)
                @php
                    $searchText = \Illuminate\Support\Str::lower($rol->name);
                    $catalogRecord = [$rol->name, $rol->users_count, $rol->permissions_count];
                @endphp
                <article
                    class="src-catalog-card src-role-card"
                    data-catalog-card
                    data-catalog-status="all"
                    data-catalog-search="{{ $searchText }}"
                    data-catalog-record="{{ json_encode($catalogRecord, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                >
                    <header class="src-catalog-card-header">
                        <div class="src-catalog-card-heading">
                            <span class="src-catalog-icon"><i class="fas fa-user-shield" aria-hidden="true"></i></span>
                            <div><h2>{{ $rol->name }}</h2><p>Perfil de acceso</p></div>
                        </div>
                    </header>
                    <div class="src-catalog-card-body">
                        <div class="src-role-counts">
                            <div><strong>{{ $rol->users_count }}</strong><span>Usuarios asignados</span></div>
                            <div><strong>{{ $rol->permissions_count }}</strong><span>Permisos asociados</span></div>
                        </div>
                    </div>
                    <footer class="src-catalog-card-footer">
                        <span class="src-catalog-card-note"><i class="fas fa-shield-alt mr-1" aria-hidden="true"></i> Control de acceso</span>
                        <div class="src-catalog-actions">
                            <a href="{{ route('roles.show', $rol->id) }}"><i class="far fa-eye" aria-hidden="true"></i> Ver</a>
                            @can('editar-roles')
                                <a class="src-catalog-edit" href="{{ route('roles.edit', $rol->id) }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar</a>
                            @endcan
                            @can('borrar-roles')
                                <form method="POST" action="{{ route('roles.destroy', $rol->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="button"
                                        class="src-catalog-delete"
                                        data-catalog-delete
                                        data-confirm-title="¿Eliminar este rol?"
                                        data-confirm-text="Los permisos y las asignaciones asociadas a este rol dejarán de estar disponibles."
                                        data-confirm-button="Sí, eliminar"
                                    ><i class="far fa-trash-alt" aria-hidden="true"></i> Eliminar</button>
                                </form>
                            @endcan
                        </div>
                    </footer>
                </article>
            @empty
                <div class="src-catalog-empty">
                    <i class="fas fa-user-shield" aria-hidden="true"></i>
                    <strong>No hay roles registrados</strong>
                    <span>Los perfiles de acceso aparecerán aquí.</span>
                </div>
            @endforelse
            @if ($roles->isNotEmpty())
                <div class="src-catalog-empty" data-catalog-filter-empty hidden>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <strong>No encontramos roles</strong>
                    <span>Prueba con otro término de búsqueda.</span>
                </div>
            @endif
        </section>
        <x-src-pagination :items="$roles" />
    </div>
@stop

@section('js')
    <script src="{{ asset('js/src-catalog.js') }}"></script>
@stop
