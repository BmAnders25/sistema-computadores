@extends('adminlte::page')

@section('title', 'Notificaciones')

@section('content_header')
    <section class="src-page-hero">
        <div>
            <span class="src-availability-eyebrow">AVISOS Y RECORDATORIOS</span>
            <h1>Notificaciones</h1>
            <p>Consulta confirmaciones, recordatorios y novedades de tus reservas.</p>
        </div>
        <i class="fas fa-bell src-page-hero-icon" aria-hidden="true"></i>
    </section>
@stop

@section('content')
    <section class="src-notifications-card">
        <div class="src-notifications-card-heading">
            <div>
                <h2>Historial de notificaciones</h2>
                <p>Las notificaciones nuevas aparecen resaltadas.</p>
            </div>
            @if ($notificaciones->isNotEmpty())
                <form action="{{ route('notificaciones.leer-todas') }}" method="POST">
                    @csrf
                    <button class="src-notification-read-all-button" type="submit">
                        <i class="fas fa-check-double" aria-hidden="true"></i>
                        Marcar todas como leídas
                    </button>
                </form>
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        <div class="src-notification-list">
            @forelse ($notificaciones as $notificacion)
                @php($datos = $notificacion->data)
                <article class="src-notification-row {{ $notificacion->read_at ? '' : 'is-unread' }}">
                    <span class="src-notification-icon">
                        <i class="fas {{ match ($datos['tipo'] ?? '') {
                            'fin' => 'fa-hourglass-end',
                            'inicio' => 'fa-clock',
                            'confirmacion' => 'fa-calendar-check',
                            'no_show' => 'fa-user-clock',
                            default => 'fa-bell',
                        } }}" aria-hidden="true"></i>
                    </span>
                    <div class="src-notification-content">
                        <div class="src-notification-title-row">
                            <h3>{{ $datos['titulo'] ?? 'Notificación' }}</h3>
                            @unless ($notificacion->read_at)
                                <span class="src-notification-new-badge">Nueva</span>
                            @endunless
                        </div>
                        <p>{{ $datos['mensaje'] ?? '' }}</p>
                        <time datetime="{{ $notificacion->created_at->toIso8601String() }}">
                            {{ $notificacion->created_at->locale('es')->diffForHumans() }}
                        </time>
                    </div>
                    <form action="{{ route('notificaciones.leer', $notificacion->id) }}" method="POST">
                        @csrf
                        <button class="src-notification-open-button" type="submit">Ver mis reservas</button>
                    </form>
                </article>
            @empty
                <div class="src-notifications-empty">
                    <span><i class="far fa-bell-slash" aria-hidden="true"></i></span>
                    <h3>Todo al día</h3>
                    <p>Cuando haya novedades sobre tus reservas, aparecerán aquí.</p>
                </div>
            @endforelse
        </div>

        <x-src-pagination :items="$notificaciones" />
    </section>
@stop
