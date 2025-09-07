# 🏨 Hotel Paraíso - Sistema de Gestión (PHP + MySQL)

Este proyecto es un sistema completo de administración de hotel desarrollado en **PHP**, **MySQL**, **HTML**, **CSS** y **JavaScript**.  
Permite gestionar clientes, habitaciones, empleados, reservaciones, cobros y reportes en PDF.

---

## 📂 Estructura del proyecto

hotel-php-mysql/
├── backend/
│ ├── config/ # Configuración de conexión a BD
│ ├── clientes/ # CRUD de clientes
│ ├── empleados/ # CRUD de empleados
│ ├── habitaciones/ # CRUD de habitaciones
│ ├── reservaciones/ # Crear, listar, disponibilidad, check-in/out
│ └── reportes/ # Reportes en HTML y PDF (FPDF)
│
├── public/
│ ├── assets/
│ │ ├── css/ # app.css, form.css, estilos de tablas
│ │ └── js/ # auth.js (demo de login)
│ └── pages/
│ ├── index.html # Página de bienvenida
│ ├── menu-completo.html # Menú principal
│ ├── consultas.html # Consultas
│ ├── reservaciones.html # Reservaciones
│ ├── registro-cliente.html
│ ├── registro-empleado.html
│ ├── habitacion.html
│ └── reportes.html # Acceso a reportes
│
├── database/
│ └── hotelBD.sql # Script con la estructura e inserts iniciales
│
└── README.md

---

## ⚙️ Requisitos

- PHP >= 8.0
- MySQL/MariaDB
- Servidor web (ej: Apache con XAMPP o MAMP)
- Composer (opcional)
- Extensión **mbstring** habilitada

---

## 🚀 Instalación

1. Clona este repositorio:

   ```bash
   git clone https://github.com/tuusuario/hotel-php-mysql.git
   cd hotel-php-mysql

   ```

2. Crea la base de datos e importa el script:
   mysql -u root -p < database/hotelBD.sql

3. Configura la conexión en:
   backend/config/db.php

4. Levanta el servidor local (ejemplo con PHP embebido):
   php -S localhost:8000 -t public

5. Abre en tu navegador:
   http://localhost:8000

📊 Módulos principales
• Clientes: registro y consultas de clientes
• Empleados: alta de empleados y asignación de área/turno
• Habitaciones: gestión de habitaciones (tipo, estado, capacidad)
• Reservaciones: crear, listar, disponibilidad, check-in/out
• Cobros: registro de pagos (efectivo, tarjeta, transferencia)
• Reportes PDF:
• Reservaciones
• Cobros detallados
• Totales por fecha (con moneda MXN)

🧪 Ejemplos de uso SQL
• Consultar reservas con cliente y habitación:
SELECT r.id_reservacion, r.fecha_reservacion,
CONCAT(c.nombre,' ',c.apellido_paterno) AS Cliente,
h.numero_habitacion, h.precio
FROM Reservacion r
JOIN Cliente c ON r.id_cliente = c.id_cliente
JOIN Habitacion h ON r.id_habitacion = h.id_habitacion;

    •	Totales por día:
    SELECT YEAR(c.fecha_transaccion) AS Anio,
        MONTHNAME(c.fecha_transaccion) AS Mes,
        DAY(c.fecha_transaccion) AS Dia,
        SUM(c.monto) AS TotalCobrado
    FROM Cobro c
    GROUP BY Anio, Mes, Dia;

📌 Notas
• Reportes en PDF generados con FPDF
• Estilo responsivo usando CSS vanilla
• Listo para extender con login real o conexión a frameworks

👨‍💻 Autor

Proyecto académico desarrollado para prácticas de PHP + MySQL.
Puedes usarlo como base para tu portafolio o ampliarlo con más funciones.

---

## 📝 Esqueleto de README (minimalista, con comandos y estructura)

````markdown
# 🏨 Hotel PHP + MySQL

Sistema básico de gestión de hotel en PHP y MySQL.

## 🚀 Instalación rápida

```bash
git clone https://github.com/tuusuario/hotel-php-mysql.git
cd hotel-php-mysql
mysql -u root -p < database/hotelBD.sql
php -S localhost:8000 -t public

📂 Estructura
hotel-php-mysql/
├── backend/        # PHP (CRUD + reportes PDF)
├── public/         # Frontend HTML/CSS/JS
└── database/       # Script SQL

📊 Módulos
	•	Clientes
	•	Empleados
	•	Habitaciones
	•	Reservaciones (check-in/out, disponibilidad)
	•	Cobros
	•	Reportes PDF (reservas, cobros, totales)

⸻

⚙️ Requisitos
	•	PHP >= 8.0
	•	MySQL/MariaDB
	•	XAMPP/MAMP/LAMP
```
````
