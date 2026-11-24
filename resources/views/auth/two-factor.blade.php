@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('classes_body', 'login-page two-factor-page')

@section('auth_header')
    Verificación en dos pasos
@stop

@section('auth_body')
<div class="auth-body-container two-factor-auth-content">
    <p id="two-factor-instruction" class="instruction-text text-center">
        Ingresa el código de 6 dígitos que hemos enviado a tu correo electrónico.
    </p>

    @if (session('status'))
        <div class="alert alert-success border-0 small py-2 px-3 mb-3 text-center" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <div class="form-group mb-3">
            <label class="sr-only" for="two-factor-code">Código de verificación de 6 dígitos</label>
            <input id="two-factor-code"
                   type="text"
                   name="codigo"
                   maxlength="6"
                   pattern="[0-9]{6}"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   aria-describedby="two-factor-instruction"
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

        <button type="submit" class="btn btn-verificar">Verificar</button>
    </form>

    <form method="POST" action="{{ route('two-factor.resend') }}" class="two-factor-resend-form">
        @csrf
        <button type="submit" class="btn btn-resend-link">
            ¿No recibiste el código? <span class="highlight-text">Reenviar código</span>
        </button>
    </form>
</div>
@stop
