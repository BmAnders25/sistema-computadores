@extends('adminlte::page')

@section('title', 'Permisos')

@section('content_header')
    <x-src-page-hero
        eyebrow="Seguridad"
        title="Administración de permisos"
        description="Consulta y administra las capacidades disponibles para los roles del sistema."
        icon="fas fa-user-shield" />
@stop

@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success" role="status">{{ $message }}</div>
    @endif

    @php
        $catalogHeaders = ['Permiso', 'Roles asignados'];
    @endphp

    <div class="src-catalog-page" data-catalog-page data-catalog-title="Permisos · S.R.C" data-catalog-filename="permisos">
        <div class="src-catalog-kpis">
            <article class="src-catalog-kpi">
                <span class="src-catalog-kpi-icon"><i class="fas fa-key" aria-hidden="true"></i></span>
                <div><small>Total de permisos</small><strong>{{ $totalPermisos }}</strong></div>
            </article>
            <article class="src-catalog-kpi src-catalog-kpi-positive">
                <span class="src-catalog-kpi-icon"><i class="fas fa-check-circle" aria-hidden="true"></i></span>
                <div><small>Asignados a roles</small><strong>{{ $asignados }}</strong></div>
            </article>
            <article class="src-catalog-kpi src-catalog-kpi-neutral">
                <span class="src-catalog-kpi-icon"><i class="fas fa-minus-circle" aria-hidden="true"></i></span>
                <div><small>Sin asignar</small><strong>{{ $sinAsignar }}</strong></div>
            </article>
        </div>

        <div class="src-catalog-create-row">
            @can('crear-permisos')
                <a class="src-catalog-create-button" href="{{ route('permissions.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Nuevo Permiso</a>
            @endcan
        </div>

        <x-src-catalog-toolbar :filters="[
            ['label' => 'Todos', 'value' => 'all', 'query' => '', 'count' => $totalPermisos],
            ['label' => 'Asignados', 'value' => 'assigned', 'query' => 'asignado', 'count' => $asignados],
            ['label' => 'Sin asignar', 'value' => 'unassigned', 'query' => 'sin_asignar', 'count' => $sinAsignar],
        ]" :selected-filter="request()->query('estado', '')" search-placeholder="Buscar por nombre de permiso...">
        </x-src-catalog-toolbar>

        <section class="src-catalog-grid" data-catalog-grid data-catalog-headers="{{ json_encode($catalogHeaders) }}" aria-label="Permisos registrados">
            @forelse ($permissions as $permission)
                @php
                    $isAssigned = $permission->roles_count > 0;
                    $filterStatus = $isAssigned ? 'assigned' : 'unassigned';
                    $catalogRecord = [$permission->name, $permission->roles_count];
                @endphp
                <article
                    class="src-catalog-card src-role-card"
                    data-catalog-card
                    data-catalog-status="{{ $filterStatus }}"
                    data-catalog-search="{{ \Illuminate\Support\Str::lower($permission->name) }}"
                    data-catalog-record="{{ json_encode($catalogRecord, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                >
                    <header class="src-catalog-card-header">
                        <div class="src-catalog-card-heading">
                            <span class="src-catalog-icon"><i class="fas fa-key" aria-hidden="true"></i></span>
                            <div><h2>{{ $permission->name }}</h2><p>Permiso del sistema</p></div>
                        </div>
                        <span class="src-catalog-status src-catalog-status-{{ $isAssigned ? 'active' : 'inactive' }}"><span aria-hidden="true"></span>{{ $isAssigned ? 'Asignado' : 'Sin asignar' }}</span>
                    </header>
                    <div class="src-catalog-card-body">
                        <div class="src-role-counts src-role-counts-single">
                            <div><strong>{{ $permission->roles_count }}</strong><span>{{ \Illuminate\Support\Str::plural('rol vinculado', $permission->roles_count) }}</span></div>
                        </div>
                    </div>
                    <footer class="src-catalog-card-footer">
                        <span class="src-catalog-card-note"><i class="fas fa-shield-alt mr-1" aria-hidden="true"></i> Control de acceso</span>
                        <div class="src-catalog-actions">
                            <a href="{{ route('permissions.show', $permission->id) }}"><i class="far fa-eye" aria-hidden="true"></i> Ver</a>
                            @can('editar-permisos')
                                <a class="src-catalog-edit" href="{{ route('permissions.edit', $permission->id) }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar</a>
                            @endcan
                            @can('borrar-permisos')
                                <form method="POST" action="{{ route('permissions.destroy', $permission->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="button"
                                        class="src-catalog-delete"
                                        data-catalog-delete
                                        data-confirm-title="¿Eliminar este permiso?"
                                        data-confirm-text="Los roles que tengan asignada esta capacidad dejarán de contar con ella."
                                        data-confirm-button="Sí, eliminar"
                                    ><i class="far fa-trash-alt" aria-hidden="true"></i> Eliminar</button>
                                </form>
                            @endcan
                        </div>
                    </footer>
                </article>
            @empty
                <div class="src-catalog-empty">
                    <i class="fas fa-key" aria-hidden="true"></i>
                    <strong>No hay permisos registrados</strong>
                    <span>Las capacidades del sistema aparecerán aquí.</span>
                </div>
            @endforelse
            @if ($permissions->isNotEmpty())
                <div class="src-catalog-empty" data-catalog-filter-empty hidden>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <strong>No encontramos permisos</strong>
                    <span>Ajusta el filtro o la búsqueda e inténtalo de nuevo.</span>
                </div>
            @endif
        </section>
        <x-src-pagination :items="$permissions" />
    </div>
@stop

@section('js')
    <script src="{{ asset('js/src-catalog.js') }}"></script>
@stop
