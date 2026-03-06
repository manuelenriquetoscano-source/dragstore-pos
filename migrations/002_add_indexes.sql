CREATE INDEX idx_productos_nombre ON productos(nombre);
CREATE INDEX idx_productos_stock ON productos(stock);
CREATE INDEX idx_ventas_fecha ON ventas(fecha);
CREATE INDEX idx_detalle_venta_id ON detalle_ventas(venta_id);

