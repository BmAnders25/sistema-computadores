@extends('adminlte::page')

@section('title', 'Herramientas de prueba')

@section('content_header')
    <x-src-page-hero
        eyebrow="ADMINISTRACIÓN · ENTORNO LOCAL"
        title="Herramientas de prueba"
        description="Ejecuta recordatorios y escenarios controlados sin usar la terminal."
        icon="fas fa-vial" />
@stop

@section('content')
    <div class="src-test-tools">
        @foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $class)
            @if (session($key))
                <div class="alert alert-{{ $class }}" role="status">{{ session($key) }}</div>
            @endif
        @endforeach

        @if (session('resultado'))
            <section class="src-test-tools-result" role="status">
                <strong>Resultado de la ejecución</strong>
                <pre>{{ session('resultado') }}</pre>
            </section>
        @endif

        <section class="src-test-tools-card">
            <header>
                <span><i class="fas fa-bell" aria-hidden="true"></i></span>
                <div>
                    <h2>Recordatorios</h2>
                    <p>Ejecuta el mismo comando que usa el programador del sistema.</p>
                </div>
            </header>
            <div class="src-test-tools-actions">
                <form action="{{ route('herramientas.prueba.recordatorios') }}" method="POST">
                    @csrf
                    <button class="src-test-tools-primary" type="submit">
                        <i class="fas fa-play" aria-hidden="true"></i> Ejecutar recordatorios ahora
                    </button>
                </form>
                <form action="{{ route('herramientas.prueba.reiniciar') }}" method="POST">
                    @csrf
                    <button class="src-test-tools-secondary" type="submit">
                        <i class="fas fa-redo" aria-hidden="true"></i> Reiniciar escenarios
                    </button>
                </form>
            </div>
        </section>

        <section class="src-test-tools-card">
            <header>
                <span><i class="fas fa-users" aria-hidden="true"></i></span>
                <div>
                    <h2>Usuarios de prueba</h2>
                    <p>Impersonación limitada a cuentas con el prefijo <code>prueba.</code>.</p>
                </div>
            </header>

            <div class="table-responsive">
                <table class="table src-test-tools-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Escenario</th>
                            <th>Estado de reserva</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($usuarios as $usuario)
                            @php
                                $reservasUsuario = $reservasPorUsuario->get($usuario->id, collect());
                                $escenario = \Illuminate\Support\Str::before(
                                    \Illuminate\Support\Str::after($usuario->email, 'prueba.'),
                                    '@'
                                );
                            @endphp
                            <tr>
                                <td><strong>{{ $usuario->name }}</strong><small>{{ $usuario->email }}</small></td>
                                <td>{{ optional($usuario->roles->first())->name ?? 'Sin rol' }}</td>
                                <td>{{ $escenario }}</td>
                                <td>
                                    @if ($reservasUsuario->isNotEmpty())
                                        {{ $reservasUsuario->map(fn ($reserva) => $reserva->estado_etiqueta)->unique()->implode(', ') }}
                                    @else
                                        <span class="text-muted">Sin reserva</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="src-test-tools-row-actions">
                                        <form action="{{ route('herramientas.prueba.impersonar', $usuario) }}" method="POST">
                                            @csrf
                                            <button class="src-test-tools-small-action" type="submit">Entrar como</button>
                                        </form>
                                        @if ($reservasUsuario->isNotEmpty())
                                            <form action="{{ route('herramientas.prueba.confirmacion', $usuario) }}" method="POST">
                                                @csrf
                                                <button class="src-test-tools-small-action is-secondary" type="submit">Enviar confirmación de prueba</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="src-test-tools-empty">No hay usuarios de prueba. Reinicia los escenarios para crearlos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@stop
