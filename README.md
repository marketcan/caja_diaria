# Caja Diaria - Control Interno

Sistema minimalista de gestión de caja diaria optimizado para hosting compartido (Webempresa).

## Características
- Registro de ingresos y egresos.
- Autenticación simple.
- Filtro por fecha.
- Exportación a Excel (CSV).
- Diseño responsivo con Tailwind CSS.

## Requisitos
- PHP 8.3 o superior.
- MySQL / MariaDB.

## Instalación
1. Clona este repositorio o sube los archivos a tu servidor.
2. Crea una base de datos MySQL.
3. Ejecuta el archivo `schema.sql` en tu base de datos para crear la tabla `movimientos`.
4. Renombra `config.php.example` a `config.php`.
5. Edita `config.php` con tus credenciales de base de datos y define tu usuario/contraseña de acceso.
6. ¡Listo! Accede a través de tu navegador.

## Seguridad
El archivo `config.php` está excluido de Git para proteger tus credenciales. Asegúrate de nunca subir este archivo al repositorio público.
