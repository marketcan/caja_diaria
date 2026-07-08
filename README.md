# Caja Diaria - Control Interno

Sistema minimalista de gestión de caja diaria. Migrado a **Novadata** bajo el dominio `caja.marketapp.com.ar`.

## Características
- Registro de ingresos y egresos.
- Autenticación simple.
- Filtro por fecha.
- Exportación a Excel (CSV).
- Diseño responsivo con Tailwind CSS.
- **Soporte para bases de datos compartidas:** Configuración flexible del nombre de la tabla para evitar colisiones.

## Requisitos
- PHP 8.3 o superior.
- MySQL / MariaDB.

## Instalación y Migración (Novadata)
1. Sube los archivos al servidor de Novadata en el directorio correspondiente para `caja.marketapp.com.ar`.
2. Para compartir la base de datos `marketcanet_marketapp` sin colisiones:
   - Importa el archivo `marketcanet_marketapp_caja.sql` (que ya contiene los datos migrados y la estructura de la tabla renombrada a `caja_movimientos`).
3. Renombra `config.php.example` a `config.php` (o edita el `config.php` existente).
4. Edita `config.php` con las credenciales de la base de datos compartida y define la constante `DB_TABLE_MOVIMIENTOS`:
   ```php
   define('DB_TABLE_MOVIMIENTOS', 'caja_movimientos');
   ```
5. ¡Listo! Accede a través de `caja.marketapp.com.ar`.

## Seguridad
El archivo `config.php` está excluido de Git para proteger tus credenciales. Asegúrate de nunca subir este archivo al repositorio público.
