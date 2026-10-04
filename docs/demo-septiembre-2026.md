# Demo de septiembre de 2026

Demo local: **http://127.0.0.1:8183**. Usuario `demo@example.test`, contraseña `demo-commerce-test`.

Se agregaron 9 pedidos, 5 ventas (una anulada), 5 productos y 7 ingresos de stock. Las fechas de ingreso, compra, cambios de estado, ventas y cobros pertenecen a septiembre de 2026. Los registros existentes se conservaron.

La configuración usada es la que ya tenía la demo: **Tefi 50 % y Hermana 50 % de la ganancia de productos**. “Hermana” es el nombre configurado del segundo participante. El costo vuelve a quien compró; repuestos y mano de obra corresponden íntegramente a Tefi. Los importes de esta guía están expresados en pesos e incluyen recuperación de costo cuando corresponde.

## Dónde entrar

- [Pedidos activos de prueba](http://127.0.0.1:8183/Pedido?search=DEMO%20Septiembre&estado=activos): Gabriela, Hugo e Iris.
- [Pedidos finalizados](http://127.0.0.1:8183/Pedido?search=DEMO%20Septiembre&estado=finalizados): Felipe.
- [Pedidos entregados](http://127.0.0.1:8183/Pedido?search=DEMO%20Septiembre&estado=entregados): Ana, Bruno, Carla, Diego y Elena.
- [Ventas](http://127.0.0.1:8183/comercio/ventas).
- [Rendimientos de septiembre](http://127.0.0.1:8183/rendimientos?selectedYear=2026&selectedMonth=9).

## Pedidos

La tabla conserva los importes del presupuesto original (reparación y productos). Ahora los productos de esos presupuestos aparecen como ventas asociadas, con sus mismos precios, repartos y fechas de cobro. Los importes de pedidos pendientes son previstos y todavía no suman cobros en Rendimientos.

| Pedido | Estado | Qué permite verificar | Total reparación | Tefi | Hermana |
| --- | --- | --- | ---: | ---: | ---: |
| [Ana · PE570ADS](http://127.0.0.1:8183/Pedido/570) | Entregado 10/9 | Producto de Tefi, dos repuestos y mano de obra | $64.100 | $62.300 | $1.800 |
| [Bruno · PE571BDS](http://127.0.0.1:8183/Pedido/571) | Entregado 12/9 | Dos fundas compradas por Hermana y un repuesto | $47.200 | $25.600 | $21.600 |
| [Carla · PE572CDS](http://127.0.0.1:8183/Pedido/572) | Entregado 14/9 | Mismo producto comprado por distintas personas, más RAM | $51.200 | $23.600 | $27.600 |
| [Diego · PE573DDS](http://127.0.0.1:8183/Pedido/573) | Entregado 16/9 | Sólo repuestos y mano de obra | $74.000 | $74.000 | $0 |
| [Elena · PE574EDS](http://127.0.0.1:8183/Pedido/574) | Entregado 18/9 | Sólo mano de obra | $22.000 | $22.000 | $0 |
| [Felipe · PE575FDS](http://127.0.0.1:8183/Pedido/575) | Finalizado, sin entregar | Stock descontado y repuesto comprado; todavía sin cobro | $58.000 | $55.000 | $3.000 |
| [Gabriela · PE576GDS](http://127.0.0.1:8183/Pedido/576) | Pendiente de aprobación | Repuesto sin comprar; no registra gasto ni descuenta stock | $45.100 | $34.300 | $10.800 |
| [Hugo · PE577HDS](http://127.0.0.1:8183/Pedido/577) | En proceso | Reparación pendiente con una venta adicional ya cobrada | $26.500 | $20.250 | $6.250 |
| [Iris · PE578IDS](http://127.0.0.1:8183/Pedido/578) | En revisión | Ingreso sin presupuesto | $0 | $0 | $0 |

## Ventas

| Venta | Fecha | Qué se vendió | Total | Tefi | Hermana |
| --- | --- | --- | ---: | ---: | ---: |
| [Adicional de Ana](http://127.0.0.1:8183/comercio/operaciones/4) | 9/9 | Mouse comprado por Hermana | $7.500 | $1.250 | $6.250 |
| [Adicional de Hugo](http://127.0.0.1:8183/comercio/operaciones/12) | 25/9 | Teclado comprado por Tefi | $11.200 | $9.600 | $1.600 |
| [Mostrador · compra de Tefi](http://127.0.0.1:8183/comercio/operaciones/14) | 19/9 | SSD y funda | $38.600 | $33.800 | $4.800 |
| [Mostrador · compra de Hermana](http://127.0.0.1:8183/comercio/operaciones/15) | 20/9 | Dos RAM y un mouse | $43.500 | $7.250 | $36.250 |
| [Anulada](http://127.0.0.1:8183/comercio/operaciones/16) | 21/9, anulada 22/9 | Teclado de $11.200, devuelto al stock | $0 en Rendimientos | $0 en Rendimientos | $0 en Rendimientos |

El pedido de **Ana**, sumando reparación y venta, tiene $71.600 cobrados: $63.550 para Tefi y $8.050 para Hermana.

El pedido de **Hugo** suma $37.700, pero sólo están cobrados los $11.200 de la venta. La reparación de $26.500 todavía está pendiente.

Ejemplo de cálculo: una funda cuesta $9.000 y se vende a $12.600. La ganancia es $3.600: $1.800 para cada participante. Quien compró recibe además los $9.000 de costo, totalizando $10.800.

## Totales para comparar

| Concepto | Sólo esta carga de prueba | Septiembre completo, con datos anteriores |
| --- | ---: | ---: |
| Cobros totales a clientes | $359.300 | $369.299,90 |
| Aportes al resultado de Tefi (sin recuperar costo de repuestos) | $177.400 | $186.899,95 |
| Costo de productos recuperado por Tefi | $55.000 | $64.000 |
| Ganancia de Tefi | $122.400 | $122.899,95 |
| Compras de productos de Tefi | $328.000 | $1.802.000 |
| Resultado del mes de Tefi | -$150.600 | -$1.615.100,05 |
| Total que corresponde a Hermana | $99.900 | $100.399,95 |

Las compras nuevas de Tefi son $328.000 de productos. Los $103.000 de compras de repuestos conservan su registro, pero no se restan de Rendimientos. Hermana compró $174.000 de productos; esos gastos no se incluyen en las compras de Tefi. El resultado negativo incluye las compras para stock, incluidas las pruebas anteriores. Las reparaciones aportan mano de obra y margen de repuestos al cobrarse; el total pagado por el cliente sigue viéndose completo en el pedido.

En el cuadro **Ventas** de Rendimientos, que considera únicamente productos cobrados —también los incluidos en reparaciones—, los totales de septiembre completo son:

| Participante | Costo que recupera | Ganancia | Total |
| --- | ---: | ---: | ---: |
| Tefi | $64.000 | $27.399,95 | $91.399,95 |
| Hermana | $73.000 | $27.399,95 | $100.399,95 |

La diferencia con los cobros generales de Tefi corresponde a repuestos y mano de obra. El participante inactivo existente no tiene importes.

## Stock disponible después de la carga

Buscá `DEMO Septiembre` en Productos. Funda y mouse tienen stock de ambos compradores para probar la selección de quién recupera el costo.

| Producto | Stock comprado por Tefi | Stock comprado por Hermana | Precio de venta |
| --- | ---: | ---: | ---: |
| Funda notebook 15,6 | 7 | 5 | $12.600 |
| Mouse inalámbrico | 6 | 3 | $7.500 |
| SSD 480 GB | 6 | 0 | $26.000 |
| Memoria RAM 8 GB | 0 | 3 | $18.000 |
| Teclado USB | 5 | 0 | $11.200 |

## Carga y comprobaciones

Seeder explícito: `database/seeders/DemoSeptiembre2026Seeder.php`. Sólo permite ejecutarse en entorno local y base `scomputacion_demo`; no forma parte del seeder general. Se ejecuta en una transacción, verifica totales y stock antes de confirmar, y repetirlo no duplica ni modifica los ejemplos existentes.

```bash
docker exec scomputacion-comercio-demo-app php artisan db:seed --class=DemoSeptiembre2026Seeder --force
```

Se verificaron los repartos de los 9 pedidos y las 5 ventas, 308 fechas de septiembre, la repetición sin duplicados y el acceso autenticado a los detalles, formularios, listados y Rendimientos. Los assets compilados responden correctamente. El resumen original de la carga queda en `storage/app/demo-septiembre-2026.json`.

Estos valores corresponden al momento de la carga. Si entregás pedidos pendientes, registrás compras, anulás ventas o editás los ejemplos, los totales cambiarán según esas operaciones y sus fechas.


## Circuito integrado (29/9)

- **Agregar productos** abre un modal en el pedido. Cancelarlo no guarda nada; confirmar crea una venta pendiente.
- Cada producto muestra **Quién recupera el costo**, tomado del registro de la operación.
- **Cobrar esta venta por separado** descuenta su importe del saldo del pedido.
- **Entregar y cobrar** muestra la reparación y las ventas aún pendientes, con fecha y medio de pago explícitos.
- El reparto se consulta en el detalle de una venta cobrada. Ya no hay un cuadro vacío de participantes al pie del pedido.
- Volver a un estado anterior conserva los cobros reales y sus fechas.
- Repuestos: una compra de $100.000 en septiembre, vendida a $130.000 con $50.000 de mano de obra y cobrada en octubre, aporta $0 por ese repuesto en septiembre y $80.000 a octubre. El cliente paga $180.000.
- La migración separó los productos de presupuestos anteriores del circuito nuevo en ventas asociadas. Conservó IDs de renglones, costos, precios, repartos, fechas, stock y totales. No modificó pedidos ni productos legacy.

La copia previa a esta integración está en `/tmp/scomputacion-demo/before-integracion-cobros-20260929.sql`. El JSON del seeder original conserva los resultados de la carga bajo el criterio anterior; los resultados vigentes son los de esta guía.
