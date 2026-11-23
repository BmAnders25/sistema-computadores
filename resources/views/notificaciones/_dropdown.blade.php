<div class="src-notification-dropdown">
    @forelse ($notificaciones as $notificacion)
        @php($datos = $notificacion->data)
        <form action="{{ route('notificaciones.leer', $notificacion->id) }}" method="POST">
            @csrf
            <button type="submit" class="src-notification-dropdown-item {{ $notificacion->read_at ? '' : 'is-unread' }}">
                <span class="src-notification-icon">
                    <i class="fas {{ ($datos['tipo'] ?? '') === 'fin' ? 'fa-hourglass-end' : 'fa-bell' }}" aria-hidden="true"></i>
                </span>
                <span class="src-notification-dropdown-copy">
                    <strong>{{ $datos['titulo'] ?? 'Notificación' }}</strong>
                    <span>{{ $datos['mensaje'] ?? '' }}</span>
                    <small>{{ $notificacion->created_at->locale('es')->diffForHumans() }}</small>
                </span>
            </button>
        </form>
    @empty
        <p class="src-notification-dropdown-empty">No tienes notificaciones recientes.</p>
    @endforelse

    <form action="{{ route('notificaciones.leer-todas') }}" method="POST" class="src-notification-read-all">
        @csrf
        <button type="submit"><i class="fas fa-check-double" aria-hidden="true"></i> Marcar todas como leídas</button>
    </form>
</div>
