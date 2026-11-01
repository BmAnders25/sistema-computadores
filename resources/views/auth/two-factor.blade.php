@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('adminlte_css_pre')
    <style>
        /* 1. Fondo de pantalla con la misma capa de atenuación oscura (oscurecido/desvanecido) */
        body.login-page {
            background: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)), 
                        url("{{ asset('vendor/adminlte/dist/img/fondo-login.jpg') }}") no-repeat center center fixed !important;
            background-size: cover !important;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* 2. Contenedor principal con efecto Glassmorphism, sombra y borde blanco grueso idéntico al Login */
        .login-box {
            width: 450px !important;
            background: rgba(255, 255, 255, 0.85) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            border: 6px solid #ffffff !important;
            border-radius: 12px !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important;
            padding: 30px 25px 20px 25px !important;
        }

        /* Limpieza de componentes nativos heredados de AdminLTE */
        .card, .card-body.login-card-body {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* 3. Título intermedio exterior "Verificación en dos pasos" */
        .login-box-msg {
            font-size: 1rem !important;
            color: #2c333e !important;
            text-align: center !important;
            margin-bottom: 20px !important;
            padding: 0 !important;
            font-weight: 500 !important;
        }

        /* 4. Bloque oscuro interno que encapsula el formulario */
        .auth-body-container {
            background-color: #2b333e !important;
            padding: 25px 20px !important;
            border-radius: 6px !important;
            margin-bottom: 0 !important;
        }

        /* Texto explicativo dentro de la caja oscura */
        .auth-body-container .instruction-text {
            color: #ffffff !important;
            font-size: 0.9rem !important;
            line-height: 1.4;
            font-weight: 400 !important;
        }

        /* Input de código estilizado en color blanco */
        .auth-body-container .form-control-code {
            background-color: #ffffff !important;
            border: none !important;
            height: 46px !important;
            color: #333333 !important;
            font-size: 1.4rem !important;
            font-weight: 700 !important;
            letter-spacing: 6px !important;
            text-align: center !important;
            border-radius: 4px !important;
        }

        /* 5. Botón de verificación azul plano */
        .btn-verificar {
            background-color: #007bff !important;
            border: none !important;
            color: #ffffff !important;
            height: 40px !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            border-radius: 4px !important;
            width: 100% !important;
            transition: background-color 0.2s ease !important;
        }

        .btn-verificar:hover {
            background-color: #0056b3 !important;
            color: #ffffff !important;
        }

        /* 6. Botón/Enlace inferior de reenvío dentro del contenedor oscuro */
        .btn-resend-link {
            color: #ffffff !important;
            font-size: 0.85rem !important;
            text-decoration: none !important;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            font-weight: 300 !important;
        }

        .btn-resend-link .highlight-text {
            color: #007bff !important; /* Azul rey del sistema */
            font-weight: 500 !important;
        }

        .btn-resend-link:hover .highlight-text {
            text-decoration: underline !important;
        }
    </style>
@stop

@section('auth_header')
    <div class="login-box-msg">Verificación en dos pasos</div>
@stop

@section('auth_body')
<div class="auth-body-container">
    
    {{-- Texto descriptivo --}}
    <p class="instruction-text text-center mb-4">
        Ingresa el código de 6 dígitos que hemos enviado a tu correo electrónico.
    </p>

    {{-- Manejo de estados de sesión --}}
    @if (session('status'))
        <div class="alert alert-success border-0 small py-2 px-3 mb-3 text-center" role="alert" style="font-size: 0.85rem;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf
        
        {{-- Campo de entrada numérico --}}
        <div class="form-group mb-4">
            <input type="text" 
                   name="codigo" 
                   maxlength="6" 
                   inputmode="numeric"
                   class="form-control form-control-code @error('codigo') is-invalid @enderror" 
                   placeholder="000000" 
                   autofocus
                   required>
            
            @error('codigo') 
                <span class="invalid-feedback text-center font-weight-normal mt-2 d-block" role="alert">
                    <strong>{{ $message }}</strong>
                </span> 
            @enderror
        </div>
        
        {{-- Botón de envío --}}
        <button type="submit" class="btn btn-verificar mb-3">
            Verificar
        </button>
    </form>

    {{-- Bloque de reenvío integrado armoniosamente abajo --}}
    <form method="POST" action="{{ route('two-factor.resend') }}" class="text-center mt-3">
        @csrf
        <button type="submit" class="btn btn-resend-link">
            ¿No recibiste el código? <span class="highlight-text">Reenviar código</span>
        </button>
    </form>
</div>
@stop

@section('auth_footer')
    {{-- Se deja vacío intencionalmente para mantener el flujo limpio dentro de la tarjeta --}}
@stop
