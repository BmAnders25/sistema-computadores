@if (app()->environment('local') && session()->has('impersonador_id') && auth()->check())
    <aside class="src-impersonation-banner" role="status">
        <div class="src-impersonation-banner__message">
            <i class="fas fa-user-secret" aria-hidden="true"></i>
            <span>
                Estás probando como <strong>{{ auth()->user()->name }}</strong>
                ({{ optional(auth()->user()->roles->first())->name ?? 'Sin rol' }})
            </span>
        </div>
        <form action="{{ route('herramientas.prueba.volver') }}" method="POST">
            @csrf
            <button type="submit">Volver a mi cuenta</button>
        </form>
    </aside>
@endif
