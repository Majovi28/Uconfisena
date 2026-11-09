-- CONFISENA: asignación segura de pedidos y apartados a una tienda.
-- Ejecutar una sola vez en phpMyAdmin sobre la base de datos confisena.
-- Las filas históricas quedan NULL porque no es posible atribuirlas con certeza.

ALTER TABLE detalle_pedido
    ADD COLUMN id_tienda INT NULL AFTER id_producto,
    ADD INDEX idx_detalle_pedido_tienda (id_tienda),
    ADD CONSTRAINT fk_detalle_pedido_tienda
        FOREIGN KEY (id_tienda) REFERENCES tienda(id_tienda)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE apartado
    ADD COLUMN id_tienda INT NULL AFTER id_producto,
    ADD INDEX idx_apartado_tienda (id_tienda),
    ADD CONSTRAINT fk_apartado_tienda
        FOREIGN KEY (id_tienda) REFERENCES tienda(id_tienda)
        ON UPDATE CASCADE ON DELETE RESTRICT;
