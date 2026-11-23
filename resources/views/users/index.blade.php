@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <x-src-page-hero
        eyebrow="Administración"
        title="Usuarios"
        description="Gestiona las cuentas, los roles y el estado de acceso de la comunidad."
        icon="fas fa-users" />
@stop

@section('content')
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif
    @if ($message = Session::get('success'))
        <div class="alert alert-success" role="status">{{ $message }}</div>
    @endif

    @php
        $catalogHeaders = ['Nombre', 'Correo', 'Código institucional', 'Rol', 'Estado', 'Programa'];
    @endphp

    <div class="src-catalog-page" data-catalog-page data-catalog-title="Usuarios · S.R.C" data-catalog-filename="usuarios">
        <div class="src-catalog-kpis">
            <article class="src-catalog-kpi">
                <span class="src-catalog-kpi-icon"><i class="fas fa-users" aria-hidden="true"></i></span>
                <div><small>Total de usuarios</small><strong>{{ $totalUsuarios }}</strong></div>
            </article>
            <article class="src-catalog-kpi src-catalog-kpi-positive">
                <span class="src-catalog-kpi-icon"><i class="fas fa-user-check" aria-hidden="true"></i></span>
                <div><small>Activos</small><strong>{{ $usuariosActivos }}</strong></div>
            </article>
            <article class="src-catalog-kpi src-catalog-kpi-neutral">
                <span class="src-catalog-kpi-icon"><i class="fas fa-user-slash" aria-hidden="true"></i></span>
                <div><small>Inactivos / suspendidos</small><strong>{{ $usuariosInactivos }}</strong></div>
            </article>
        </div>

        <div class="src-catalog-create-row">
            @can('crear-usuarios')
                <a class="src-catalog-create-button" href="{{ route('users.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Nuevo Usuario</a>
            @endcan
        </div>

        <x-src-catalog-toolbar :filters="[
            ['label' => 'Todos', 'value' => 'all', 'query' => '', 'count' => $totalUsuarios],
            ['label' => 'Activos', 'value' => 'active', 'query' => 'activo', 'count' => $usuariosActivos],
            ['label' => 'Inactivos / suspendidos', 'value' => 'inactive', 'query' => 'inactivos', 'count' => $usuariosInactivos],
        ]" :selected-filter="request()->query('estado', '')" search-placeholder="Buscar por nombre, correo, código o rol...">
        </x-src-catalog-toolbar>

        <section class="src-catalog-grid" data-catalog-grid data-catalog-headers="{{ json_encode($catalogHeaders) }}" aria-label="Usuarios registrados">
            @forelse ($users as $user)
                @php
                    $isActive = $user->esActivo();
                    $estadoLabel = $isActive ? 'Activo' : ($user->estado === 'suspendido' ? 'Suspendido' : 'Inactivo');
                    $filterStatus = $isActive ? 'active' : 'inactive';
                    $rolesUsuario = $user->getRoleNames()->implode(', ');
                    $nombrePartes = preg_split('/\s+/', trim($user->name), -1, PREG_SPLIT_NO_EMPTY);
                    $initials = collect($nombrePartes)->take(2)->map(fn ($parte) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($parte, 0, 1)))->implode('');
                    $searchText = \Illuminate\Support\Str::lower(implode(' ', [$user->name, $user->email, $user->codigo_institucional, $rolesUsuario, $user->programa]));
                    $catalogRecord = [$user->name, $user->email, $user->codigo_institucional ?? 'N/A', $rolesUsuario ?: 'Sin rol', $estadoLabel, $user->programa ?? 'N/A'];
                @endphp
                <article
                    class="src-catalog-card src-user-card"
                    data-catalog-card
                    data-catalog-status="{{ $filterStatus }}"
                    data-catalog-search="{{ $searchText }}"
                    data-catalog-record="{{ json_encode($catalogRecord, JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                >
                    <header class="src-catalog-card-header">
                        <div class="src-user-identity">
                            <span class="src-user-avatar" aria-hidden="true">{{ $initials }}</span>
                            <div>
                                <h2>{{ $user->name }}</h2>
                                <p>{{ $user->email }}</p>
                            </div>
                        </div>
                        <span class="src-catalog-status src-catalog-status-{{ $isActive ? 'active' : 'inactive' }}"><span aria-hidden="true"></span>{{ $estadoLabel }}</span>
                    </header>

                    <div class="src-catalog-card-body">
                        <div class="src-catalog-facts">
                            <div class="src-catalog-fact"><span>Rol</span><strong>{{ $rolesUsuario ?: 'Sin rol asignado' }}</strong></div>
                            <div class="src-catalog-fact"><span>Código institucional</span><strong>{{ $user->codigo_institucional ?: 'No registrado' }}</strong></div>
                            <div class="src-catalog-fact"><span>Programa académico</span><strong>{{ $user->programa ?: 'No registrado' }}</strong></div>
                        </div>
                    </div>

                    <footer class="src-catalog-card-footer">
                        <span class="src-catalog-card-note"><i class="fas fa-user-tag mr-1" aria-hidden="true"></i> {{ $rolesUsuario ?: 'Sin rol asignado' }}</span>
                        <div class="src-catalog-actions">
                            <a href="{{ route('users.show', $user->id) }}"><i class="far fa-eye" aria-hidden="true"></i> Ver</a>
                            @can('editar-usuarios')
                                <a class="src-catalog-edit" href="{{ route('users.edit', $user->id) }}"><i class="fas fa-pencil-alt" aria-hidden="true"></i> Editar</a>
                            @endcan
                            @can('borrar-usuarios')
                                <form method="POST" action="{{ route('users.destroy', $user->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="button"
                                        class="src-catalog-delete"
                                        data-catalog-delete
                                        data-confirm-title="¿Eliminar este usuario?"
                                        data-confirm-text="No se podrá recuperar la información de la cuenta."
                                        data-confirm-button="Sí, eliminar"
                                    ><i class="far fa-trash-alt" aria-hidden="true"></i> Eliminar</button>
                                </form>
                            @endcan
                        </div>
                    </footer>
                </article>
            @empty
                <div class="src-catalog-empty">
                    <i class="fas fa-users" aria-hidden="true"></i>
                    <strong>No hay usuarios registrados</strong>
                    <span>Las cuentas aparecerán aquí una vez creadas.</span>
                </div>
            @endforelse

            @if ($users->isNotEmpty())
                <div class="src-catalog-empty" data-catalog-filter-empty hidden>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <strong>No encontramos usuarios</strong>
                    <span>Ajusta el filtro o la búsqueda e inténtalo de nuevo.</span>
                </div>
            @endif
        </section>
        <x-src-pagination :items="$users" />
    </div>
@stop

@section('js')
    <script src="{{ asset('js/src-catalog.js') }}"></script>
@stop
