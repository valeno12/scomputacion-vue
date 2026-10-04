# Propuesta visual: pedidos y ventas

`flujo-pedidos-ventas.html` es una maqueta autónoma para revisar la presentación y la navegación antes de modificar el sistema. Se puede abrir como archivo o servir desde este directorio. No consulta la aplicación, no utiliza su base de datos y no guarda cambios al recargar.

## Recorridos representados

- Pedido finalizado con reparación de $30.000 y venta pendiente de $12.600: total común, bloques separados e historial lateral abierto.
- Cobrar primero los productos y luego entregar/cobrar únicamente los $30.000 pendientes.
- Agregar uno o varios productos mediante un modal dentro del pedido, con buscador inicialmente vacío, cantidad editable y precio fijo. El pie muestra el total agregado y el nuevo saldo pendiente. Confirmar actualiza el pedido conservando el desplazamiento; cancelar no guarda la selección. La venta queda pendiente de cobro.
- Crear una venta directa, guardarla pendiente o confirmar explícitamente venta y cobro.
- Consultar ambas clases de venta desde la misma tabla, con filtro de origen y cobro.
- Volver a la pantalla de origen desde el detalle y consultar rendimientos por fecha efectiva de cobro.

Los nombres, productos, cantidades y el reparto 50/50 son datos ficticios del ejemplo. La maqueta no implementa movimientos de stock, anulación, edición del presupuesto ni persistencia. Esos comportamientos deben usar las reglas compartidas del sistema cuando se apruebe la propuesta. Los componentes finales de búsqueda deben reutilizar el buscador existente.

## Rendimientos: regla acordada para repuestos

Los repuestos específicos de un pedido conservan su costo y fecha de compra para calcular la ganancia y consultar el detalle, pero su compra no se descuenta como egreso en rendimientos. Al cobrar el pedido, se incluye únicamente la ganancia del repuesto más la mano de obra. El total del pedido y el cobro real al cliente mantienen el precio completo.

Ejemplo aprobado: repuesto comprado el 29/09 a $100.000, vendido a $130.000, más $50.000 de mano de obra; cobro el 02/10. Este pedido aporta $0 a rendimientos de septiembre y $80.000 a octubre. El cliente paga $180.000. Los $100.000 de costo no se vuelven a restar como compra ni se incluyen como ingreso recuperado en rendimientos.

Para productos del inventario se mantiene el criterio anterior: compras del titular restadas en su mes y, al cobrar una venta, costo recuperado y ganancia que le corresponden según el reparto materializado. Un SSD comprado específicamente para una reparación es un repuesto; uno vendido desde el inventario es un producto. La clasificación depende del circuito, no del nombre del artículo.

El indicador conjunto se presenta como **Resultado del mes**: ganancia de reparaciones cobradas + parte del titular en ventas de productos cobradas − compras de productos del titular. No se denomina caja ni cobros totales, ya que excluye el costo recuperado de repuestos. En la maqueta, el pedido de $30.000 aporta $14.000 al cobrarse ($10.000 de mano de obra y $4.000 de ganancia del repuesto); su compra de $16.000 deja de restarse. La compra de productos del titular sigue siendo $17.000.

Esta regla está incorporada en la propuesta; aún no se modificó el cálculo de la aplicación ni se recalcularon operaciones históricas.

## Criterios visuales

- Mantener los formularios centrados y los listados en tabla que ya usa el sistema.
- Recuperar la estructura anterior del detalle: encabezado con código/cliente/estado; **Información del pedido** con datos del equipo a la izquierda y trabajo/importes a la derecha; historial vertical al costado.
- Reunir mano de obra, precio de repuestos, ventas de productos, total, cobrado y saldo pendiente en un único resumen. En móvil este resumen precede a los datos secundarios del equipo.
- Debajo, mostrar tablas de **Repuestos** y **Ventas de productos**. Las ventas se agrupan en una única sección con su estado de cobro; no se presentan como dos categorías diferentes de productos incluidos y ventas adicionales. Los costos y porcentajes de repuestos están a la vista.
- Reparación antes de productos, precios de productos de sólo lectura y resultados de búsqueda únicamente al escribir.
- Azul para acciones y productos; ámbar para reparación/pendiente; verde para cobros; violeta para participantes. Todos los estados también llevan texto.
- Mantener el historial vertical y visible al desplazarse en escritorio.
- Mostrar el reparto en un cuadro compacto con pestañas de participantes, después de cobrar productos.
- En rendimientos, integrar la separación por actividad conservando los filtros, gráficos y consultas actuales. La maqueta sólo representa los bloques necesarios para comprobar este recorrido, no propone eliminar los informes restantes.

## Validación

Revisada en Chromium a 1440 px y 390 px, temas oscuro y claro. Se comprobaron cobro separado seguido de entrega sin duplicar importes, venta directa pendiente, vuelta contextual, búsqueda vacía sin resultados y exclusión de cobros de octubre del ejemplo de rendimientos de septiembre. No se ejecutaron operaciones reales.
