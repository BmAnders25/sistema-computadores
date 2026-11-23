@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('adminlte_css_pre')
    <style>
        body.login-page {
            background: #f8fafc !important;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-box {
            width: min(450px, calc(100% - 2rem)) !important;
            padding: 1.5rem !important;
            background: #fff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            box-shadow: 0 14px 36px rgba(31, 41, 55, 0.1) !important;
        }

        .card, .card-body.login-card-body {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .login-box-msg {
            color: #202b3b !important;
            font-size: 1.15rem !important;
            text-align: center !important;
            margin-bottom: 1.1rem !important;
            padding: 0 !important;
            font-weight: 750 !important;
        }

        .auth-body-container {
            padding: 1.1rem !important;
            background: #fff !important;
            border: 1px solid #edf0f4 !important;
            border-radius: 12px !important;
        }

        .auth-body-container .instruction-text {
            color: #5d6878 !important;
            font-size: 0.86rem !important;
            line-height: 1.4;
        }

        .auth-body-container .form-control-code {
            height: 48px !important;
            color: #293445 !important;
            background: #fff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 9px !important;
            font-size: 1.3rem !important;
            font-weight: 700 !important;
            letter-spacing: 6px !important;
            text-align: center !important;
        }

        .auth-body-container .form-control-code:focus {
            border-color: #a6262e !important;
            box-shadow: 0 0 0 3px rgba(166, 38, 46, 0.12) !important;
        }

        .btn-verificar {
            background-color: #a6262e !important;
            border: none !important;
            color: #ffffff !important;
            height: 42px !important;
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            border-radius: 9px !important;
            width: 100% !important;
            transition: background-color 0.2s ease !important;
        }

        .btn-verificar:hover {
            background-color: #7b1e24 !important;
            color: #ffffff !important;
        }

        .btn-resend-link {
            color: #647184 !important;
            font-size: 0.85rem !important;
            text-decoration: none !important;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            font-weight: 300 !important;
        }

        .btn-resend-link .highlight-text {
            color: #a6262e !important;
            font-weight: 650 !important;
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
