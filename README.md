##   Calendario de Eventos con PHP y MYSQL

###### FullCalendar es una herramienta ideal para proyectos de gestión de eventos. Con su interfaz intuitiva y amigable, podrás crear un calendario en el que puedas agendar, editar y eliminar eventos de manera sencilla. Con FullCalendar, podrás personalizar fácilmente el diseño y la funcionalidad del calendario para que se ajuste a las necesidades específicas de tu proyecto. ¡Intégralo en tu proyecto y haz la gestión de eventos una tarea fácil y agradable!

![](https://raw.githubusercontent.com/urian121/imagenes-proyectos-github/master/calendario_con_full_calendar_urain_viera_webdeveloper.PNG)


###### Por Ing. Urian Viera

# laToxicaPHP

## Catálogo público

El menú para clientes está en `catalogo.php`. No requiere inicio de sesión y muestra automáticamente los productos visibles con stock, agrupados por tipo. Esta es la URL que debe usarse al generar el código QR.

# API administrativa

La API de administradores es de solo lectura y esta separada de `wp_reservas_api.php`.

Configure una clave exclusiva en el entorno del servidor:

```env
ADMIN_API_KEY=genere-aqui-una-clave-larga-y-aleatoria
```

La clave se envia con uno de estos encabezados:

```http
Authorization: Bearer <ADMIN_API_KEY>
X-Admin-API-Key: <ADMIN_API_KEY>
```

Endpoints disponibles:

```http
GET /admin_api.php?action=turnos&fecha=2026-07-13
GET /admin_api.php?action=informe_diario&fecha=2026-07-13
GET /admin_api.php?action=informe_mensual&mes=2026-07
```

El informe diario usa el mismo dia comercial que el panel: desde las 04:01 de la fecha seleccionada hasta las 04:00 del dia siguiente. La API no admite operaciones de escritura.

# Invitaciones de cumpleaños por WhatsApp

Cuando Mercado Pago acredita la seña de una reserva correspondiente a la cancha de cumpleaños, el sistema inicia el flujo de invitación personalizada en el bot.

El endpoint puede configurarse con la variable de entorno `WP_BOT_BIRTHDAY_ENDPOINT`. Por defecto se usa:

```text
https://bot-wp.darioapp.online/clients/toxica_negocio/birthday-invitation
```

# Confirmacion y conciliacion de pagos

Cada preferencia de pago incluye automaticamente el webhook del dominio configurado en `APP_PUBLIC_URL`. Como respaldo, `cron_cancelar_reservas.php` consulta a Mercado Pago por `external_reference` antes de cancelar una reserva pendiente y reintenta las confirmaciones de WhatsApp que hayan fallado.

En produccion se debe programar este archivo desde el panel de tareas cron de Hostinger para ejecutarlo una vez por minuto, usando la ruta PHP y la ruta absoluta reales de la cuenta. Por ejemplo:

```sh
/usr/bin/php /home/USUARIO/domains/latoxica-fc.com/public_html/cron_cancelar_reservas.php
```

El proceso necesita que `MERCADOPAGO_ACCESS_TOKEN`, `APP_PUBLIC_URL`, `API_KEY` y los endpoints del bot esten configurados en el servidor. Si Mercado Pago no puede consultarse, el cron no cancela reservas para evitar eliminar un turno que podria estar pagado.

## Configuraci?n de una copia nueva

Copiar `config.example.php` como `config.php` y configurar la conexi?n MySQL,
correo, Mercado Pago y las claves API para ese servidor. Tambi?n se pueden
configurar las variables de entorno indicadas en el archivo de ejemplo.
`config.php`, `configBkp.php` y los registros locales quedan fuera de Git.
La base de datos y sus datos se trasladan por separado; no est?n incluidos en este repositorio.

## Precios por d?a y horario

En Administraci?n ? Canchas ? Precios por horario se pueden definir tarifas
semanales. Fuera de las franjas se aplica el precio base. Las reservas nuevas
calculan el importe de cada tramo; los cumplea?os usan el precio del paquete
seg?n su hora de inicio. La tabla `cancha_precios_horarios` se crea autom?ticamente
al usar esta configuraci?n; el usuario MySQL debe tener permiso para crear tablas.

Pruebas del c?lculo: `php tests/precios_canchas_test.php`.

El webhook de WhatsApp requiere `WHATSAPP_WEBHOOK_VERIFY_TOKEN` en el entorno del servidor; no contiene una clave predeterminada.
