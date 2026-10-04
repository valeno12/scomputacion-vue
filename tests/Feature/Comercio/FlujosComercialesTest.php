<?php

use App\Models\Cliente;
use App\Models\LoteStock;
use App\Models\Operacion;
use App\Models\Participante;
use App\Models\Pedido;
use App\Models\ProductoNuevo;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\Comercio\Repartos;
use App\Services\Comercio\ResumenComercial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::create(['name' => 'Operador único', 'email' => 'flujos@example.test', 'password' => bcrypt('password')]);
    $this->actingAs($this->user);
    $this->owner = app(Repartos::class)->titular();
    $this->sister = Participante::create(['nombre' => 'Hermana']);
    $this->put('/settings/repartos', ['reparto_mercaderia' => [['participante_id' => $this->owner->id, 'porcentaje' => 50], ['participante_id' => $this->sister->id, 'porcentaje' => 50]]])->assertSessionHasNoErrors();
    $this->product = ProductoNuevo::create(['nombre' => 'Funda para notebook', 'marca' => 'Prueba', 'precio_venta_centavos' => 1000000]);
    $this->lot = LoteStock::create(['articulo_id' => $this->product->id, 'comprador_id' => $this->owner->id, 'cantidad_inicial' => 2, 'cantidad_disponible' => 2, 'costo_unitario_centavos' => 900000, 'tipo' => 'compra', 'fecha' => now()->subDay()->toDateString()]);
    $this->row = ['grupo' => (string) Str::uuid(), 'tipo' => 'stock', 'producto_id' => $this->product->id, 'cantidad' => 1, 'precio' => '10000'];
    $this->sale = ['cobrar' => true, 'fecha_cobro' => \App\Services\Comercio\FechaComercial::hoy(), 'clave' => (string) Str::uuid(), 'fecha' => \App\Services\Comercio\FechaComercial::hoy(), 'medio_pago' => 'Efectivo', 'items' => [$this->row]];
});

function workflowLot($test, int $buyer, int $cost = 950000): LoteStock
{
    return LoteStock::create(['articulo_id' => $test->product->id, 'comprador_id' => $buyer, 'cantidad_inicial' => 3, 'cantidad_disponible' => 3, 'costo_unitario_centavos' => $cost, 'tipo' => 'compra', 'fecha' => \App\Services\Comercio\FechaComercial::hoy()]);
}
function workflowOrder($test): array
{
    foreach (['Revisión', 'Pendiente', 'En proceso', 'Finalizado', 'Entregado'] as $index => $name) {
        DB::table('estado')->insert(['id' => $index + 1, 'nombre' => $name]);
    }

    return ['cliente_id' => Cliente::create(['nombre' => 'Ana', 'apellido' => 'Prueba'])->id, 'equipo' => 'Notebook', 'estado_ingreso' => 'No enciende', 'cargador' => false, 'trabajo_realizar' => 'Reparar', 'costo_mano_obra' => 1000, 'items' => [$test->row]];
}

test('one financier allocates oldest stock first and records every cost without duplicating catalog', function () {
    $second = workflowLot($this, $this->owner->id);
    $this->sale['items'][0]['cantidad'] = 4;
    $this->post('/comercio/ventas', $this->sale)->assertRedirect()->assertSessionHasNoErrors();
    $op = Operacion::first();
    expect(ProductoNuevo::count())->toBe(1)->and($op->items)->toHaveCount(2)
        ->and($op->items->pluck('cantidad')->all())->toBe([2, 2])
        ->and($op->items->pluck('costo_unitario_centavos')->all())->toBe([900000, 950000])
        ->and($op->items->pluck('grupo')->unique())->toHaveCount(1)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(0)->and($second->fresh()->cantidad_disponible)->toBe(1)
        ->and($op->registrado_por)->toBe($this->user->id);
    $this->get('/productos/'.$this->product->id)->assertInertia(fn (Assert $page) => $page->component('Productos/Show')->where('producto.registrador.name', 'Operador único')->has('ingresos.data', 2)->has('ventas.data', 2));
});

test('stock of different financiers requires a choice and never silently mixes their costs', function () {
    $second = workflowLot($this, $this->sister->id);
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasErrors('items.0.comprador_id');
    $this->sale['items'][0]['comprador_id'] = $this->owner->id;
    $this->sale['items'][0]['cantidad'] = 3;
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasErrors('items.0.cantidad');
    expect(Operacion::count())->toBe(0)->and($second->fresh()->cantidad_disponible)->toBe(3);
    $this->sale['items'][0]['cantidad'] = 2;
    $this->sale['items'][] = [...$this->row, 'grupo' => (string) Str::uuid(), 'comprador_id' => $this->sister->id];
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasNoErrors();
    $op = Operacion::first();
    $share = collect($op->reparto_resumen)->firstWhere('rol', 'hermana');
    expect($share['costo_centavos'])->toBe(950000)->and($share['ganancia_centavos'])->toBe(125000)->and($share['total_centavos'])->toBe(1075000);
});

test('changing configuration names stock costs and retail prices cannot alter a past sale or report', function () {
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasNoErrors();
    $op = Operacion::first();
    $before = $op->items->first()->toArray();
    $summary = app(ResumenComercial::class)->mes(now()->year, now()->month);
    $this->put('/settings/repartos', ['reparto_mercaderia' => [['participante_id' => $this->owner->id, 'porcentaje' => 80], ['participante_id' => $this->sister->id, 'porcentaje' => 20]]])->assertSessionHasNoErrors();
    $this->sister->update(['nombre' => 'Otro nombre']);
    $this->product->update(['precio_venta_centavos' => 2000000]);
    expect($op->fresh()->items->first()->toArray())->toBe($before)
        ->and(app(ResumenComercial::class)->mes(now()->year, now()->month)['distribucion'])->toBe($summary['distribucion']);
    $this->sale['clave'] = (string) Str::uuid();
    $this->sale['items'][0]['precio'] = '20000';
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasNoErrors();
    expect(Operacion::latest('id')->first()->items->first()->reparto[1]['porcentaje'])->toBe(20)
        ->and($op->fresh()->items->first()->reparto[1]['porcentaje'])->toBe(50);
});

test('the owner report excludes the sisters cost recovery and purchases', function () {
    $this->lot->update(['cantidad_disponible' => 0]);
    workflowLot($this, $this->sister->id, 900000);
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasNoErrors();
    $summary = app(ResumenComercial::class)->mes(now()->year, now()->month);
    expect($summary['cobros_centavos'])->toBe(50000)->and($summary['ganancia_centavos'])->toBe(50000)
        ->and($summary['gastos_centavos'])->toBe(1800000)->and($summary['costo_recuperado_centavos'])->toBe(0)
        ->and($summary['cobros_totales_centavos'])->toBe(1000000);
    $this->get('/rendimientos')->assertInertia(fn (Assert $page) => $page->where('gananciasPorMes.0.total_ganancias', 500)->where('comercio.titular.id', $this->owner->id));
});

test('order edits and quantity increases keep materialized percentages after settings change', function () {
    workflowLot($this, $this->owner->id);
    $data = workflowOrder($this);
    $this->post('/Pedido', $data)->assertSessionHasNoErrors();
    $order = Pedido::first();
    $op = $order->operaciones->firstWhere('tipo', 'venta');
    $this->post('/Pedido/'.$order->id.'/actualizarEstado/3')->assertSessionHasNoErrors();
    $before = $op->items()->orderBy('id')->get()->toArray();
    $this->put('/settings/repartos', ['reparto_mercaderia' => [['participante_id' => $this->sister->id, 'porcentaje' => 100]]])->assertSessionHasNoErrors();
    $data['equipo'] = 'Notebook corregida';
    $this->put('/Pedido/'.$order->id, $data)->assertSessionHasNoErrors();
    expect($op->items()->orderBy('id')->get()->toArray())->toBe($before);
    $data['items'][0]['cantidad'] = 4;
    $this->put('/Pedido/'.$order->id, $data)->assertSessionHasNoErrors();
    foreach ($op->items()->where('tipo', 'stock')->get() as $item) {
        expect($item->reparto[0]['porcentaje'])->toBe(50)->and($item->reparto[1]['porcentaje'])->toBe(50);
    }
    $this->get('/Pedido/'.$order->id.'/edit')->assertOk();
});

test('multiple parts use markup and provider suggestions and always belong to the owner', function () {
    $provider = Proveedor::create(['nombre' => 'Distribuidor']);
    $data = workflowOrder($this);
    $part = ['tipo' => 'repuesto', 'descripcion' => 'Batería', 'cantidad' => 2, 'costo' => 9000, 'porcentaje_ganancia' => 40, 'proveedor' => 'distribuidor', 'fecha_compra' => \App\Services\Comercio\FechaComercial::hoy(), 'comprador_id' => $this->sister->id, 'reparto' => [['participante_id' => $this->sister->id, 'porcentaje' => 100]]];
    $data['items'] = [$part, [...$part, 'descripcion' => 'Pantalla', 'proveedor' => 'Proveedor nuevo', 'cantidad' => 1]];
    $this->post('/Pedido', $data)->assertSessionHasNoErrors();
    $parts = Operacion::first()->items->where('tipo', 'repuesto');
    expect($parts)->toHaveCount(2)->and(Proveedor::count())->toBe(2)->and($parts->first()->proveedor_id)->toBe($provider->id);
    foreach ($parts as $p) {
        expect($p->comprador_id)->toBe($this->owner->id)->and($p->precio_unitario_centavos)->toBe(1260000)->and($p->reparto[0]['participante_id'])->toBe($this->owner->id)->and($p->reparto[0]['porcentaje'])->toBe(100);
    }
});

test('customer registration only requires name and surname and returns the exact newly created customer', function () {
    $first = $this->postJson('/Cliente', ['nombre' => 'Ana', 'apellido' => 'Prueba'])->assertCreated()->json('cliente');
    $second = $this->postJson('/Cliente', ['nombre' => 'Ana', 'apellido' => 'Prueba'])->assertCreated()->json('cliente');
    expect($first['id'])->not->toBe($second['id'])->and(Cliente::whereNull('dni')->count())->toBe(2);
    $this->getJson('/Cliente/search?q=Ana')->assertOk()->assertJsonCount(2);
});

test('inventory search stays empty until typing and exposes financing options only for available stock', function () {
    workflowLot($this, $this->sister->id);
    $this->getJson('/productos/buscar?inventario=1')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/productos/buscar?inventario=1&q=Funda')->assertOk()->assertJsonCount(1, 'data')->assertJsonCount(2, 'data.0.financiadores');
});

test('supplier suggestions allow new text and json creation returns the exact provider', function () {
    $first = $this->postJson('/Proveedor', ['nombre' => 'Proveedor de prueba'])->assertCreated()->json('proveedor');
    $second = $this->postJson('/Proveedor', ['nombre' => 'Proveedor de prueba'])->assertCreated()->json('proveedor');
    expect($first['id'])->not->toBe($second['id']);
    $this->getJson('/Proveedor/search')->assertOk()->assertExactJson([]);
    $this->getJson('/Proveedor/search?q=prueba')->assertOk()->assertJsonCount(2);
});

test('product allocation rejects a row identifier belonging to another operation', function () {
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasNoErrors();
    $sale = Operacion::first();
    $before = $sale->items->first()->toArray();
    $data = workflowOrder($this);
    $data['items'][0]['id'] = $sale->items->first()->id;
    $this->post('/Pedido', $data)->assertSessionHasErrors('items.0.id');
    expect(Pedido::count())->toBe(0)->and(Operacion::count())->toBe(1)
        ->and($sale->fresh()->items->first()->toArray())->toBe($before)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(1);
});

test('the restored supplier chart includes current and legacy purchases for the selected month and owner only', function () {
    $provider = Proveedor::create(['nombre' => 'Distribuidor A']);
    $excluded = Proveedor::create(['nombre' => 'No corresponde al período o titular']);
    $today = \App\Services\Comercio\FechaComercial::hoy();
    $this->lot->update(['proveedor_id' => $provider->id, 'fecha' => $today]);
    workflowLot($this, $this->sister->id)->update(['proveedor_id' => $excluded->id]);
    workflowLot($this, $this->owner->id)->update(['proveedor_id' => $excluded->id, 'fecha' => now()->subMonthNoOverflow()->toDateString()]);
    workflowLot($this, $this->owner->id)->update(['proveedor_id' => $excluded->id, 'tipo' => 'inicial']);
    workflowLot($this, $this->owner->id); // A purchase without a supplier remains visible.
    $legacy = \App\Models\Producto::create(['nombre' => 'Anterior', 'marca' => 'Prueba', 'precio' => 100, 'cantidad_disponible' => 1]);
    \App\Models\MovimientoStock::create(['producto_id' => $legacy->id, 'tipo_movimiento' => 'entrada', 'cantidad' => 1, 'precio' => 100, 'fecha' => $today, 'proveedor_id' => $provider->id]);
    $op = Operacion::create(['tipo' => 'reparacion', 'clave' => (string) Str::uuid(), 'fecha' => $today]);
    \App\Models\CompraRepuesto::create(['operacion_id' => $op->id, 'descripcion' => 'Repuesto comprado', 'comprador_id' => $this->owner->id, 'proveedor_id' => $provider->id, 'cantidad' => 1, 'costo_unitario_centavos' => 10000, 'fecha' => $today]);
    $this->get('/rendimientos?selectedYear='.now()->year.'&selectedMonth='.now()->month)->assertInertia(fn (Assert $page) => $page
        ->has('proveedores', 2)
        ->where('proveedores.0.proveedor.nombre', 'Distribuidor A')->where('proveedores.0.cantidad_pedidos', 2)
        ->where('proveedores.1.proveedor.nombre', 'Sin proveedor')->where('proveedores.1.cantidad_pedidos', 1));
});

test('inventory products use the catalog price and reject manual or stale prices', function () {
    $this->sale['items'][0]['precio'] = '1';
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasErrors('items.0.precio');
    expect(Operacion::count())->toBe(0)->and($this->lot->fresh()->cantidad_disponible)->toBe(2);
    unset($this->sale['items'][0]['precio']);
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasNoErrors();
    expect(Operacion::first()->items->first()->precio_unitario_centavos)->toBe(1000000);

    $this->product->update(['precio_venta_centavos' => 1200000]);
    $data = workflowOrder($this);
    $this->post('/Pedido', $data)->assertSessionHasErrors('items.0.precio');
    expect(Pedido::count())->toBe(0);
    $data['items'][0]['precio'] = '12000';
    $this->post('/Pedido', $data)->assertSessionHasNoErrors();
    expect(Pedido::first()->operaciones->firstWhere('tipo', 'venta')->items->firstWhere('tipo', 'stock')->precio_unitario_centavos)->toBe(1200000);
});

test('order product prices stay materialized when catalog prices and quantities change', function () {
    $data = workflowOrder($this);
    $this->post('/Pedido', $data)->assertSessionHasNoErrors();
    $order = Pedido::first();
    $op = $order->operaciones->firstWhere('tipo', 'venta');
    $data['items'][0]['id'] = $op->items->firstWhere('tipo', 'stock')->id;
    $this->product->update(['precio_venta_centavos' => 2000000]);
    $data['items'][0]['cantidad'] = 2;
    $this->put('/Pedido/'.$order->id, $data)->assertSessionHasNoErrors();
    expect($op->fresh()->items->where('tipo', 'stock')->pluck('precio_unitario_centavos')->unique()->all())->toBe([1000000])
        ->and((float) $order->fresh()->presupuesto)->toBe(21000.0);
    $before = $op->fresh()->items->toArray();
    $data['items'][0]['precio'] = '1';
    $this->put('/Pedido/'.$order->id, $data)->assertSessionHasErrors('items.0.precio');
    expect($op->fresh()->items->toArray())->toBe($before);
});

test('sales participant tabs include everyone and use only recorded product allocations', function () {
    $third = Participante::create(['nombre' => 'Alex']);
    $inactive = Participante::create(['nombre' => 'Sin ventas', 'activo' => false]);
    $this->put('/settings/repartos', ['reparto_mercaderia' => [
        ['participante_id' => $this->owner->id, 'porcentaje' => 20],
        ['participante_id' => $this->sister->id, 'porcentaje' => 30],
        ['participante_id' => $third->id, 'porcentaje' => 50],
    ]])->assertSessionHasNoErrors();
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasNoErrors();
    $data = workflowOrder($this);
    $data['items'][] = ['tipo' => 'repuesto', 'descripcion' => 'Batería', 'cantidad' => 1, 'costo' => 100, 'porcentaje_ganancia' => 20];
    $this->post('/Pedido', $data)->assertSessionHasNoErrors();
    $order = Pedido::first();
    for ($next = 3; $next <= 5; $next++) {
        $this->post('/Pedido/'.$order->id.'/actualizarEstado/'.$next, ['fecha_cobro' => \App\Services\Comercio\FechaComercial::hoy(), 'medio_pago' => 'Efectivo', 'total_esperado' => $order->operaciones()->whereNull('fecha_cobro')->sum('total_centavos')])->assertSessionHasNoErrors();
    }
    $rows = collect(app(ResumenComercial::class)->mes(now()->year, now()->month)['ventas_por_participante'])->keyBy('participante_id');
    expect($rows)->toHaveCount(4)
        ->and($rows[$this->owner->id]['cantidad'])->toBe(2)
        ->and($rows[$this->owner->id]['total_centavos'])->toBe(1840000)
        ->and($rows[$this->sister->id]['total_centavos'])->toBe(60000)
        ->and($rows[$third->id]['total_centavos'])->toBe(100000)
        ->and($rows[$inactive->id]['total_centavos'])->toBe(0)
        ->and($rows[$inactive->id]['activo'])->toBeFalse();
    $this->put('/settings/repartos', ['reparto_mercaderia' => [['participante_id' => $this->owner->id, 'porcentaje' => 100]]])->assertSessionHasNoErrors();
    expect(collect(app(ResumenComercial::class)->mes(now()->year, now()->month)['ventas_por_participante'])->keyBy('participante_id')->all())->toBe($rows->all());
    $this->get('/rendimientos')->assertInertia(fn (Assert $page) => $page->has('comercio.ventas_por_participante', 4));
});
