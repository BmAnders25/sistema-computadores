@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])

@section('auth_header', 'Verificación en dos pasos')

@section('auth_body')
    <!-- Texto descriptivo forzado a color blanco completo -->
    <p class="text-white small mb-4" style="color: #ffffff !important; opacity: 1 !important;">
        Ingresa el código de 6 dígitos que hemos enviado a tu correo electrónico.
    </p>

    <!-- Alertas con bordes y relleno nativos de Bootstrap 4 -->
    
    
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm small py-2 px-3 mb-3 rounded" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf
        
        <!-- Input Group con espaciado mb-4 -->
        <div class="input-group mb-4">
            <input type="text" 
                   name="codigo" 
                   maxlength="6" 
                   inputmode="numeric"
                   class="form-control form-control-lg text-center font-weight-bold @error('codigo') is-invalid @enderror" 
                   placeholder="000000" 
                   style="letter-spacing: 4px; font-size: 1.5rem;"
                   autofocus>
            
            @error('codigo') 
                <span class="invalid-feedback text-left font-weight-normal">{{ $message }}</span> 
            @enderror
        </div>
        
        <!-- Botón de bloque completo de Bootstrap 4 con sombra -->
        <button class="btn btn-primary btn-block btn-lg shadow-sm font-weight-bold">
            Verificar
        </button>
    </form>

    <!-- Enlace de reenvío con texto secundario aclarado para fondo oscuro -->
    <form method="POST" action="{{ route('two-factor.resend') }}" class="mt-3 text-center">
        @csrf
        <button class="btn btn-link btn-sm text-decoration-none text-white font-weight-light" style="color: #ffffff !important;">
            ¿No recibiste el código? <span class="text-info font-weight-normal">Reenviar código</span>
        </button>
    </form>
@endsection
