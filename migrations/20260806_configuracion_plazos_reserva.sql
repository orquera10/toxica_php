CREATE TABLE IF NOT EXISTS configuracion_reservas (
    id TINYINT UNSIGNED NOT NULL,
    dias_anticipacion_turnos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    dias_anticipacion_cumpleanos SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    telefonos_alerta_reserva_hoy TEXT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuracion_reservas (
    id,
    dias_anticipacion_turnos,
    dias_anticipacion_cumpleanos
) VALUES (1, 30, 60);
