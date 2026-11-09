-- Migración CONFISENA: asociación explícita de operaciones con tienda.
-- Compatible con registros existentes: las columnas nuevas aceptan NULL.
-- Ejecutar una sola vez sobre la base de datos confisena.

ALTER TABLE apartado
    ADD COLUMN id_tienda INT(11) NULL AFTER id_producto,
    ADD KEY idx_apartado_tienda (id_tienda),
    ADD CONSTRAINT fk_apartado_tienda
        FOREIGN KEY (id_tienda) REFERENCES tienda (id_tienda);

ALTER TABLE detalle_pedido
    ADD COLUMN id_tienda INT(11) NULL AFTER id_producto,
    ADD KEY idx_detalle_pedido_tienda (id_tienda),
    ADD CONSTRAINT fk_detalle_pedido_tienda
        FOREIGN KEY (id_tienda) REFERENCES tienda (id_tienda);

-- Los registros anteriores quedan en NULL intencionalmente:
-- el esquema anterior no permite determinar con certeza la tienda propietaria
-- cuando el mismo producto existe en inventarios de varias tiendas.
