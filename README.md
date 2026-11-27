# Sistema de Reservas de Computadores (S.R.C.)

Aplicación web para gestionar el préstamo y uso de computadores y aulas de cómputo. Centraliza las reservas y el control de los recursos tecnológicos para estudiantes, celadores y personal administrativo.

Desarrollado con **Laravel 11**, framework de PHP que estructura la aplicación mediante rutas, controladores y modelos Eloquent. Laravel también gestiona la autenticación, los permisos, las migraciones y la interacción con la base de datos.

## Módulos principales

- **Reservas:** consulta de disponibilidad, selección de horarios y gestión de reservas de computadores y aulas.
- **Inventario:** administración y consulta de computadores, aulas, bloques y laboratorios.
- **Entregas y devoluciones:** registro y seguimiento de la entrega y retorno de los recursos reservados.
- **Dashboard y reportes:** resumen de la actividad del sistema e informes de ocupación.
- **Notificaciones y recordatorios:** avisos internos y correos relacionados con las reservas.
- **Usuarios y acceso:** autenticación, verificación en dos pasos y permisos según roles.

## Tecnologías

- **Backend:** PHP 8.1+, Laravel 11 y Eloquent ORM.
- **Interfaz:** Blade, Laravel AdminLTE, Bootstrap 5, JavaScript y Chart.js.
- **Base de datos:** MySQL.
- **Recursos frontend:** Vite.
- **Acceso:** Spatie Laravel Permission para roles y permisos; autenticación en dos pasos.
- **Calidad:** PHPUnit para pruebas y Laravel Pint para formato de código.
