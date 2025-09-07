-- ======================================================
-- CREAR BASE DE DATOS (MySQL)
-- ======================================================
CREATE DATABASE IF NOT EXISTS HotelDB
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE HotelDB;

-- ======================================================
-- TABLAS PRINCIPALES
-- ======================================================

-- TABLA CLIENTE
CREATE TABLE Cliente (
  id_cliente INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  apellido_paterno VARCHAR(50) NOT NULL,
  apellido_materno VARCHAR(50),
  correo VARCHAR(100) UNIQUE,
  telefono VARCHAR(20)
) ENGINE=InnoDB;

-- TABLA HABITACION
CREATE TABLE Habitacion (
  id_habitacion INT PRIMARY KEY AUTO_INCREMENT,
  numero_habitacion INT NOT NULL UNIQUE,
  precio DECIMAL(10,2) NOT NULL,
  tipo_habitacion VARCHAR(50) NOT NULL,
  estado VARCHAR(20) NOT NULL,
  cantidad_personas INT NOT NULL
) ENGINE=InnoDB;

-- TABLA AREA
CREATE TABLE Area (
  id_area INT PRIMARY KEY AUTO_INCREMENT,
  nombre_area VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- TABLA TURNO
CREATE TABLE Turno (
  id_turno INT PRIMARY KEY AUTO_INCREMENT,
  tipo_turno VARCHAR(5) NOT NULL
) ENGINE=InnoDB;

-- TABLA TIPO DE PAGO
CREATE TABLE TipoPago (
  id_tipo_pago INT PRIMARY KEY AUTO_INCREMENT,
  metodo VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- ======================================================
-- TABLAS RELACIONADAS
-- ======================================================

-- TABLA EMPLEADO
CREATE TABLE Empleado (
  id_empleado INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  apellido_paterno VARCHAR(50) NOT NULL,
  apellido_materno VARCHAR(50),
  telefono VARCHAR(20),
  area INT NOT NULL,
  turno INT NOT NULL,
  CONSTRAINT fk_empleado_area  FOREIGN KEY (area)  REFERENCES Area(id_area),
  CONSTRAINT fk_empleado_turno FOREIGN KEY (turno) REFERENCES Turno(id_turno)
) ENGINE=InnoDB;

-- TABLA RESERVACION
CREATE TABLE Reservacion (
  id_reservacion INT PRIMARY KEY AUTO_INCREMENT,
  fecha_reservacion DATE NOT NULL,
  id_cliente INT NOT NULL,
  id_habitacion INT NOT NULL,
  CONSTRAINT fk_reservacion_cliente   FOREIGN KEY (id_cliente)   REFERENCES Cliente(id_cliente),
  CONSTRAINT fk_reservacion_habitacion FOREIGN KEY (id_habitacion) REFERENCES Habitacion(id_habitacion)
) ENGINE=InnoDB;

-- TABLA CHECK IN/OUT
CREATE TABLE CheckInOut (
  id_check INT PRIMARY KEY AUTO_INCREMENT,
  hora_entrada DATETIME NOT NULL,
  hora_salida DATETIME,
  id_reservacion INT NOT NULL,
  CONSTRAINT fk_check_reservacion FOREIGN KEY (id_reservacion) REFERENCES Reservacion(id_reservacion)
) ENGINE=InnoDB;

-- TABLA TICKET
CREATE TABLE Ticket (
  id_ticket INT PRIMARY KEY AUTO_INCREMENT,
  numero_ticket VARCHAR(50) NOT NULL UNIQUE,
  fecha DATE NOT NULL,
  id_reservacion INT NOT NULL,
  CONSTRAINT fk_ticket_reservacion FOREIGN KEY (id_reservacion) REFERENCES Reservacion(id_reservacion)
) ENGINE=InnoDB;

-- TABLA COBRO
CREATE TABLE Cobro (
  id_cobro INT PRIMARY KEY AUTO_INCREMENT,
  fecha_transaccion DATETIME NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  id_ticket INT NOT NULL,
  id_tipo_pago INT NOT NULL,
  CONSTRAINT fk_cobro_ticket    FOREIGN KEY (id_ticket)    REFERENCES Ticket(id_ticket),
  CONSTRAINT fk_cobro_tipopago  FOREIGN KEY (id_tipo_pago) REFERENCES TipoPago(id_tipo_pago)
) ENGINE=InnoDB;

-- TABLA SERVICIO
CREATE TABLE Servicio (
  id_servicio INT PRIMARY KEY AUTO_INCREMENT,
  nombre_servicio VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255),
  costo DECIMAL(10,2) NOT NULL,
  id_empleado INT NOT NULL,
  id_reservacion INT,
  CONSTRAINT fk_servicio_empleado     FOREIGN KEY (id_empleado)   REFERENCES Empleado(id_empleado),
  CONSTRAINT fk_servicio_reservacion  FOREIGN KEY (id_reservacion) REFERENCES Reservacion(id_reservacion)
) ENGINE=InnoDB;

-- TABLA HABITACION_SERVICIO (N:N)
CREATE TABLE Habitacion_Servicio (
  id_servicio INT NOT NULL,
  id_habitacion INT NOT NULL,
  PRIMARY KEY (id_servicio, id_habitacion),
  CONSTRAINT fk_hs_servicio   FOREIGN KEY (id_servicio)   REFERENCES Servicio(id_servicio),
  CONSTRAINT fk_hs_habitacion FOREIGN KEY (id_habitacion) REFERENCES Habitacion(id_habitacion)
) ENGINE=InnoDB;

-- ======================================================
-- INSERTS TABLAS PRINCIPALES
-- ======================================================

-- CLIENTE
INSERT INTO Cliente (nombre, apellido_paterno, apellido_materno, correo, telefono) VALUES
('Juan',  'Pérez',   'López',     'juan.perez@example.com',  '555-1234'),
('María', 'García',  'Hernández', 'maria.garcia@example.com','555-5678'),
('Carlos','Ramírez', 'Ramirez',   'carlos.ramirez@example.com','555-8765');

-- HABITACION
INSERT INTO Habitacion (numero_habitacion, precio, tipo_habitacion, estado, cantidad_personas) VALUES
(101, 1200.00, 'Sencilla',     'Disponible',   2),
(102, 1800.00, 'Doble',        'Ocupada',      4),
(103, 2500.00, 'Suite',        'Mantenimiento',5);

-- AREA
INSERT INTO Area (nombre_area) VALUES ('Recepción'), ('Limpieza'), ('Restaurante');

-- TURNO
INSERT INTO Turno (tipo_turno) VALUES ('AM'), ('PM'), ('NOC');

-- TIPO DE PAGO
INSERT INTO TipoPago (metodo) VALUES ('Efectivo'), ('Tarjeta Crédito'), ('Transferencia');

-- ======================================================
-- INSERTS TABLAS RELACIONADAS
-- ======================================================

-- EMPLEADO
INSERT INTO Empleado (nombre, apellido_paterno, apellido_materno, telefono, area, turno) VALUES
('Laura', 'Martínez', 'Gomez',  '555-1111', 1, 1),
('Pedro', 'Sánchez',  'Gómez',  '555-2222', 2, 2),
('Ana',   'Torres',   'Flores', '555-3333', 3, 3);

-- RESERVACION (nota: usa IDs 1..3 existentes)
INSERT INTO Reservacion (fecha_reservacion, id_cliente, id_habitacion) VALUES
('2025-08-01', 1, 1),
('2025-08-05', 2, 2),
('2025-08-10', 3, 3);

-- CHECK IN/OUT
INSERT INTO CheckInOut (hora_entrada, hora_salida, id_reservacion) VALUES
('2025-08-01 14:00:00', '2025-08-03 11:00:00', 1),
('2025-08-05 15:00:00', NULL,                  2),
('2025-08-10 13:30:00', '2025-08-12 12:00:00', 3);

-- TICKET
INSERT INTO Ticket (numero_ticket, fecha, id_reservacion) VALUES
('TCK-001', '2025-08-01', 1),
('TCK-002', '2025-08-05', 2),
('TCK-003', '2025-08-10', 3);

-- COBRO
INSERT INTO Cobro (fecha_transaccion, monto, id_ticket, id_tipo_pago) VALUES
('2025-08-01 16:00:00', 2400.00, 1, 1),
('2025-08-05 17:30:00', 1800.00, 2, 2),
('2025-08-10 14:15:00', 2500.00, 3, 3);

-- SERVICIO
INSERT INTO Servicio (nombre_servicio, descripcion, costo, id_empleado, id_reservacion) VALUES
('Desayuno buffet', 'Incluye bebidas y postre', 300.00, 3, 1),
('Limpieza extra',  'Servicio especial de limpieza', 200.00, 2, 2),
('Spa',             'Masaje por 30 min', 500.00, 1, 3);

-- HABITACION_SERVICIO
INSERT INTO Habitacion_Servicio (id_servicio, id_habitacion) VALUES
(1, 1),
(2, 2),
(3, 3);

-- ======================================================
-- CONSULTAS
-- ======================================================

-- 1) Lista de reservas con datos del cliente y la habitación
-- (Corrección del JOIN respecto a tu versión original)
SELECT 
  r.id_reservacion,
  r.fecha_reservacion,
  CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', COALESCE(c.apellido_materno, '')) AS Cliente,
  h.numero_habitacion,
  h.precio
FROM Reservacion r
INNER JOIN Cliente   c ON r.id_cliente    = c.id_cliente
INNER JOIN Habitacion h ON r.id_habitacion = h.id_habitacion;

-- 2) Cobros con tipo de pago, ticket, habitación y cliente
SELECT 
  cbr.id_cobro,
  cbr.fecha_transaccion,
  cbr.monto,
  tp.metodo AS TipoPago,
  t.numero_ticket,
  CONCAT(cl.nombre, ' ', cl.apellido_paterno, ' ', COALESCE(cl.apellido_materno, '')) AS Cliente,
  h.numero_habitacion
FROM Cobro cbr
INNER JOIN Ticket      t  ON cbr.id_ticket = t.id_ticket
INNER JOIN TipoPago    tp ON cbr.id_tipo_pago = tp.id_tipo_pago
INNER JOIN Reservacion r  ON t.id_reservacion = r.id_reservacion
INNER JOIN Cliente     cl ON r.id_cliente = cl.id_cliente
INNER JOIN Habitacion  h  ON r.id_habitacion = h.id_habitacion;

-- 3) Total cobrado por día, mes y año (agrego MesNum para orden correcto)
SELECT 
  YEAR(c.fecha_transaccion)      AS Anio,
  MONTHNAME(c.fecha_transaccion) AS Mes,
  MONTH(c.fecha_transaccion)     AS MesNum,
  DAY(c.fecha_transaccion)       AS Dia,
  SUM(c.monto)                   AS TotalCobrado
FROM Cobro c
GROUP BY YEAR(c.fecha_transaccion), MONTHNAME(c.fecha_transaccion), MONTH(c.fecha_transaccion), DAY(c.fecha_transaccion)
ORDER BY Anio, MesNum, Dia;

-- ======================================================
-- TRIGGER (MySQL) - Actualiza precio de habitación al agregar servicio
-- ======================================================
DROP TRIGGER IF EXISTS trg_UpdatePrecioHabitacion;
DELIMITER $$
CREATE TRIGGER trg_UpdatePrecioHabitacion
AFTER INSERT ON Habitacion_Servicio
FOR EACH ROW
BEGIN
  UPDATE Habitacion h
  JOIN Servicio s ON s.id_servicio = NEW.id_servicio
  SET h.precio = h.precio + s.costo
  WHERE h.id_habitacion = NEW.id_habitacion;
END$$
DELIMITER ;

-- Prueba rápida del trigger
-- SELECT * FROM Habitacion_Servicio WHERE id_habitacion = 3;
-- INSERT INTO Habitacion_Servicio (id_habitacion, id_servicio) VALUES (3, 1);

-- ======================================================
-- STORED PROCEDURE (MySQL) - Precio total de una habitación
-- ======================================================
DROP PROCEDURE IF EXISTS sp_PrecioTotalHabitacion;
DELIMITER $$
CREATE PROCEDURE sp_PrecioTotalHabitacion(IN p_id_habitacion INT)
BEGIN
  SELECT 
    h.id_habitacion,
    h.numero_habitacion,
    h.precio AS PrecioBase,
    COALESCE(SUM(s.costo), 0) AS TotalServicios,
    h.precio + COALESCE(SUM(s.costo), 0) AS PrecioTotal
  FROM Habitacion h
  LEFT JOIN Habitacion_Servicio hs ON h.id_habitacion = hs.id_habitacion
  LEFT JOIN Servicio s ON hs.id_servicio = s.id_servicio
  WHERE h.id_habitacion = p_id_habitacion
  GROUP BY h.id_habitacion, h.numero_habitacion, h.precio;
END$$
DELIMITER ;

-- Prueba del SP:
-- CALL sp_PrecioTotalHabitacion(3);

-- Verificación rápida:
-- SELECT * FROM Cobro;
