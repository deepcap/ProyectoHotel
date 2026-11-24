# Hotel Paraíso — Sistema de gestión hotelera

Aplicación web para administrar la operación básica de un hotel: clientes, habitaciones,
empleados, reservaciones con check-out y reportes de cobros en HTML y PDF.

Está construida con **PHP sin framework**, **MySQL** y un frontend de **HTML, CSS y JavaScript
sin dependencias**. Es un proyecto de aprendizaje en proceso de mejora: antes de usarlo con datos
reales, revisa la sección [Limitaciones conocidas](#limitaciones-conocidas).

---

## Contenido

1. [Tecnologías](#tecnologías)
2. [Requisitos](#requisitos)
3. [Estructura del proyecto](#estructura-del-proyecto)
4. [Instalación](#instalación)
5. [Configuración (.env)](#configuración-env)
6. [Ejecución](#ejecución)
7. [Módulos](#módulos)
8. [Respaldo y restauración de la base de datos](#respaldo-y-restauración-de-la-base-de-datos)
9. [Limitaciones conocidas](#limitaciones-conocidas)
10. [Flujo de trabajo y hoja de ruta](#flujo-de-trabajo-y-hoja-de-ruta)

---

## Tecnologías

| Capa | Tecnología |
|---|---|
| Backend | PHP 8 (extensión `mysqli`), un archivo PHP por acción |
| Base de datos | MySQL 8 o superior (InnoDB, `utf8mb4`) |
| Frontend | HTML5, CSS y JavaScript nativo (`fetch`) |
| Reportes PDF | [FPDF](http://www.fpdf.org/) 1.85, incluida en `backend/lib/` |
| Servidor de desarrollo | Servidor embebido de PHP con `router.php` |

## Requisitos

- **PHP 8.1 o superior** (probado con PHP 8.5) con las extensiones `mysqli`, `mbstring` e `iconv`.
- **MySQL 8.0 o superior** (probado con MySQL 9.0). MariaDB no se ha probado.
- **Git**.
- Un navegador moderno.

No se necesita Composer, Node.js, Apache ni XAMPP/MAMP.

Para comprobar lo instalado:

```bash
php -v
php -m | grep -E 'mysqli|mbstring|iconv'
mysql --version
```

## Estructura del proyecto

```text
.
├── backend/                  # Endpoints PHP (uno por acción)
│   ├── config/
│   │   ├── db.php            # Conexión $mysqli a partir de .env
│   │   └── env.php           # Lector de .env y registro en logs/app.log
│   ├── lib/
│   │   ├── fpdf.php          # Librería FPDF (+ font/)
│   │   ├── format.php        # Formato de moneda
│   │   └── ui.php            # Página de aviso (éxito/error)
│   ├── areas/                # opciones.php
│   ├── clientes/             # crear, listar, ver, actualizar, eliminar, opciones
│   ├── empleados/            # crear, listar, actualizar, eliminar
│   ├── habitaciones/         # crear, listar, actualizar, eliminar, opciones
│   ├── pagos/                # opciones.php
│   ├── reservaciones/        # crear, listar, disponibles, checkin, checkout, eliminar
│   └── reportes/             # cobros, reservaciones, totales (HTML y *_pdf.php)
├── public/                   # Frontend
│   ├── index.html            # Página de bienvenida
│   ├── assets/
│   │   ├── css/              # app, form, consultas, reservas, reports, notice
│   │   └── js/auth.js        # Comprobación de sesión (demostración)
│   └── pages/                # login, menú, registros, consultas, reservaciones, reportes
├── HotelBD.sql               # Script de creación con datos de ejemplo (ver limitaciones)
├── router.php                # Router del servidor de desarrollo (lista de rutas permitidas)
├── .env.example              # Plantilla de configuración
├── .gitignore
└── README.md
```

Archivos locales que **no** se versionan: `.env` (tu configuración) y `logs/` (registro de errores).

## Instalación

### 1. Clonar el repositorio

```bash
git clone <URL-del-repositorio>
cd ProyectoHotel
```

> ⚠️ Clona el proyecto en una carpeta **que no se sincronice con la nube** (por ejemplo
> `~/Proyectos`), no en Documentos, Escritorio, iCloud Drive ni Dropbox. Si el sistema
> libera espacio enviando archivos a la nube, Git y PHP se bloquean al leerlos y la carpeta
> `.git` puede dañarse.

### 2. Crear la base de datos

> ⚠️ `HotelBD.sql` crea la base `HotelDB` e inserta datos de ejemplo. Ejecútalo **solo en un
> servidor donde `HotelDB` no exista**: si ya existe, el script se detiene con un error en la
> primera tabla. Si ya tienes datos, haz primero un [respaldo](#respaldo).

```bash
mysql -u <usuario_admin> -p < HotelBD.sql
```

### 3. Crear un usuario de MySQL para la aplicación (recomendado)

Evita usar `root` desde la aplicación. Conectado como administrador:

```sql
CREATE USER 'hotel_app'@'localhost' IDENTIFIED BY '<una_contraseña_segura>';
GRANT SELECT, INSERT, UPDATE, DELETE ON HotelDB.* TO 'hotel_app'@'localhost';
```

### 4. Configurar `.env`

Ver la sección siguiente.

### 5. Crear la carpeta de logs

```bash
mkdir -p logs
```

Si no existe, los errores de conexión se envían al log de PHP en lugar de `logs/app.log`.

## Configuración (.env)

La conexión a MySQL se lee **exclusivamente** del archivo `.env` en la raíz del proyecto.
No hay valores por defecto en el código.

```bash
cp .env.example .env
chmod 600 .env        # solo tu usuario puede leerlo
```

Edita `.env` con tus valores:

| Variable | Obligatoria | Descripción |
|---|---|---|
| `DB_HOST` | Sí | Servidor MySQL (normalmente `127.0.0.1`) |
| `DB_PORT` | Sí | Puerto, número entre 1 y 65535 (normalmente `3306`) |
| `DB_NAME` | Sí | Nombre de la base (`HotelDB`) |
| `DB_USER` | Sí | Usuario de MySQL de la aplicación |
| `DB_PASS` | Sí | Contraseña. La línea debe existir; puede quedar vacía solo en un entorno local sin contraseña |

Formato: `CLAVE=valor`, una por línea. Se admiten comentarios con `#` y valores entre comillas.

Reglas importantes:

- **Nunca subas `.env` a Git** (ya está en `.gitignore`) ni pongas credenciales reales en `.env.example`.
- **Las variables de entorno del sistema no sustituyen al `.env`.** Nombres como `DB_USER` suelen
  estar definidos por otras herramientas (por ejemplo, en la configuración de la terminal);
  ignorarlos evita que la aplicación se conecte con credenciales ajenas.
- Si falta una variable o la conexión falla, la aplicación responde con un error genérico
  (`Servicio no disponible`) y registra el detalle en `logs/app.log`, sin contraseñas.

## Ejecución

Desde la raíz del proyecto:

```bash
php -S 127.0.0.1:8000 router.php
```

Abre <http://127.0.0.1:8000/> (redirige a la página de bienvenida) o directamente la de inicio de
sesión: <http://127.0.0.1:8000/public/pages/login.html>.

Sobre el servidor de desarrollo:

- **Usa siempre `127.0.0.1`**, nunca `0.0.0.0`: el router además rechaza clientes que no sean locales.
- **Arranca desde la raíz del proyecto**, no con `-t public`: las páginas usan rutas `/public/...` y `/backend/...`.
- `router.php` aplica **denegación por defecto**: solo sirve archivos estáticos de `public/` y los
  endpoints de `backend/` listados en su constante `ENDPOINTS`. Bloquea `.git`, `.env`, `*.sql`,
  `backend/config/`, `backend/lib/`, `logs/` y cualquier otra ruta.
- **Al crear un endpoint nuevo, agrégalo a `ENDPOINTS`**; si no, responderá 404.
- Es solo para desarrollo. En producción se usaría Apache o Nginx con una configuración equivalente.

Para detener el servidor: `Ctrl + C`.

### Comprobación rápida

Con el servidor en marcha:

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8000/backend/habitaciones/listar.php   # 200
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8000/HotelBD.sql                        # 404
```

## Módulos

Recorrido: página de bienvenida (`public/index.html`) → inicio de sesión (`login.html`, acceso de
demostración; ver limitaciones) → **menú principal** (`menu-completo.html`), desde el que se accede
a los módulos. Todas las páginas están en `public/pages/`.

| Módulo | Página | Qué permite |
|---|---|---|
| Clientes | `clientes.html` | Registrar clientes |
| Habitaciones | `habitacion.html` | Registrar habitaciones (número, tipo, estado, capacidad, precio) |
| Empleados | `registro-empleado.html` | Registrar empleados con área y turno |
| Consultas | `consultas.html` | Buscar, editar y eliminar habitaciones, empleados y clientes |
| Reservaciones | `reservaciones.html` | Pestañas *Reservar*, *Disponibilidad* y *Listado*. El listado se filtra por texto, fechas y estado (*Reservada*, *En curso*, *Finalizada*) y permite check-out y eliminar |
| Reportes | `reportes.html` | Cobros detallados, reservaciones y totales por fecha, en HTML y PDF (MXN) |

## Respaldo y restauración de la base de datos

Guarda los respaldos **fuera del repositorio** (por ejemplo, en una carpeta de respaldos de tu
usuario). `.gitignore` excluye patrones como `HotelDB-*.sql`, `*.dump` y `backups/`, pero no
dependas solo de eso.

### Respaldo

```bash
mkdir -p ~/Respaldos/HotelParaiso
f=~/Respaldos/HotelParaiso/HotelDB-$(date +%Y%m%d-%H%M%S).sql
( set -C   # no sobrescribe si el archivo ya existe
  mysqldump -u <usuario_admin> -p -h 127.0.0.1 \
    --single-transaction --routines --triggers --events \
    --databases HotelDB > "$f" ) && chmod 600 "$f"

tail -1 "$f"                                    # debe decir: -- Dump completed on ...
shasum -a 256 "$f" > "$f.sha256"                # huella para verificar integridad
```

`--single-transaction` hace una copia consistente sin bloquear las tablas y **no modifica la base**.

### Restauración

> ⚠️ **Lee esto antes de restaurar.** Un respaldo hecho con `--databases` contiene
> `CREATE DATABASE`, `USE \`HotelDB\`` y un `DROP TABLE IF EXISTS` por cada tabla.
> Restaurarlo **borra y reemplaza las tablas de `HotelDB`** con el contenido del respaldo;
> todo lo registrado después del respaldo se pierde.

Antes de restaurar sobre la base real:

1. Haz un **respaldo nuevo** del estado actual.
2. Verifica la integridad del archivo: `shasum -a 256 -c archivo.sql.sha256`.
3. Si solo quieres revisar el contenido, restaura en una **base temporal**.

**Restaurar en una base temporal (no toca `HotelDB`):**

```bash
# 1. Copia del respaldo SIN las líneas CREATE DATABASE y USE
grep -v -E '^(CREATE DATABASE .*`HotelDB`|USE `HotelDB`;)' respaldo.sql > /tmp/restaurar_verif.sql
grep -n -i -E '^(USE|CREATE DATABASE)' /tmp/restaurar_verif.sql   # debe no mostrar nada

# 2. Base temporal y restauración (el nombre de la base va como argumento posicional)
mysql -u <usuario_admin> -p -e 'CREATE DATABASE `HotelDB_verif` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
mysql -u <usuario_admin> -p --one-database HotelDB_verif < /tmp/restaurar_verif.sql

# 3. Revisar y, al terminar, eliminar la base temporal
mysql -u <usuario_admin> -p -e 'DROP DATABASE `HotelDB_verif`;'
```

**Restaurar sobre `HotelDB` (reemplaza los datos actuales):**

```bash
mysql -u <usuario_admin> -p < respaldo.sql
```

## Limitaciones conocidas

El proyecto está en mejora por etapas. Estas son las limitaciones actuales más importantes.

### Seguridad

- **La autenticación es solo de demostración.** El usuario y la contraseña se validan en
  JavaScript dentro de `login.html` y la "sesión" es una marca en `sessionStorage`; se puede
  saltar desde la consola del navegador.
- **Los endpoints del backend no verifican ninguna sesión.** Cualquiera que llegue al servidor
  puede consultar, crear, modificar o eliminar datos. No hay protección CSRF.
- `auth.js` solo se carga en `menu-completo.html` (en las páginas de reportes está comentado);
  el resto de páginas no comprueba ni siquiera la sesión de demostración.
- Los enlaces "¿Olvidaste tu contraseña?" (`login.html`) y "Salir" (`menu-completo.html`, que
  usa `href="#"` con un `onclick`) se revisarán junto con la autenticación real.
- `habitaciones/actualizar.php` acepta cualquier texto como tipo de habitación. La interfaz lo
  muestra siempre como texto (sin interpretarlo como HTML), pero el servidor aún no lo valida.
- Los endpoints activan `display_errors`, por lo que algunos errores de PHP o MySQL pueden mostrarse.
- **No expongas este servidor fuera de tu equipo** hasta completar la etapa de seguridad.

### Reservaciones y cobros

- **La fecha de salida no se guarda.** Se usa para comprobar la disponibilidad, pero la
  reservación queda sin salida: la habitación se considera ocupada hasta que se registra el check-out.
- Las noches y el total del listado se calculan hasta la fecha actual, no con la estancia reservada.
- **Los servicios extra, la forma de pago y las notas del formulario de reserva no se guardan.**
  El formulario lo indica junto a cada campo; los importes de los servicios son estimaciones.
  La interfaz tampoco registra tickets ni cobros: los reportes de cobros solo muestran los
  registros que ya existen en la base.
- El listado solo muestra las reservaciones que tienen registro en `CheckInOut`; una reservación
  sin ese registro no aparece.
- `reservaciones/checkin.php` existe, pero ninguna pantalla lo utiliza.
- La disponibilidad se comprueba fuera de la transacción: dos reservaciones simultáneas podrían
  traslaparse.

### Otros módulos

- Eliminar una habitación o un empleado con historial produce un error 500 (restricción de llave foránea).
- Al editar un empleado, el área actual no aparece preseleccionada; el área se envía por nombre y,
  si no existe, se crea una nueva.
- En el reporte de totales, los nombres de los meses aparecen en inglés.

### `HotelBD.sql` frente al esquema real

`HotelBD.sql` **no reproduce exactamente** la base usada durante el desarrollo:

- La tabla `Reservacion` del esquema real tiene la columna `id_tipo_pago_sugerido` (con llave
  foránea a `TipoPago`), que no está en el script.
- El script define el trigger `trg_UpdatePrecioHabitacion` y el procedimiento
  `sp_PrecioTotalHabitacion`, que no existen en la base real (los bloques con `DELIMITER` pueden
  no aplicarse según cómo se importe). La aplicación no los utiliza, y el trigger modifica el
  precio de la habitación de forma permanente.
- No se puede ejecutar dos veces (usa `CREATE TABLE` sin `IF NOT EXISTS`) y mezcla el esquema con
  datos y consultas de ejemplo.

Para reconstruir una base idéntica a la real, usa un respaldo hecho con `mysqldump` en lugar de
`HotelBD.sql`. El script se regenerará a partir del esquema real en una etapa posterior.

## Flujo de trabajo y hoja de ruta

### Trabajo con Git

- `main` contiene la versión estable; la etiqueta `v0-baseline` marca el estado anterior a la mejora.
- Cada cambio se hace en una rama (`chore/…`, `fix/…`, `feat/…`) con commits pequeños y verificables,
  y se integra a `main` mediante Pull Request.
- Antes de cambiar el esquema de la base, haz un respaldo.

### Etapas de mejora

| Etapa | Objetivo |
|---|---|
| 0. Preparación | `.gitignore`, router del servidor local, configuración con `.env`, documentación |
| 1. Seguridad | Login real con sesiones de PHP y contraseñas con hash, protección de endpoints y CSRF, ocultar errores |
| 2. Lógica de negocio | Fechas de estancia en la reservación, flujo de pago (ticket y cobro), check-in, esquema SQL regenerado |
| 3. Organización del código | Respuestas JSON uniformes, código común reutilizable, JavaScript fuera del HTML |
| 4. Calidad | Pruebas automatizadas (PHPUnit), análisis estático y estilo de código |

---

Proyecto académico de práctica en PHP y MySQL. Aún no se ha definido una licencia.
