<?php

use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\CompraRepuesto;
use App\Models\InventarioMovimiento;
use App\Models\LoteStock;
use App\Models\Operacion;
use App\Models\OperacionItem;
use App\Models\Participante;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\Producto;
use App\Models\User;
use App\Services\Comercio\ResumenComercial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::create(['name' => 'Operador', 'email' => 'acciones@example.test', 'password' => bcrypt('test-password')]));
    foreach (['En revisión', 'Pendiente', 'En proceso', 'Finalizado', 'Entregado'] as $index => $name) {
        DB::table('estado')->insert(['id' => $index + 1, 'nombre' => $name]);
    }
    $this->buyer = app(\App\Services\Comercio\Repartos::class)->titular();
    $this->buyer->update(['nombre' => 'Comprador']);
    $this->partner = Participante::create(['nombre' => 'Socio']);
    $this->split = [['participante_id' => $this->buyer->id, 'porcentaje' => 50], ['participante_id' => $this->partner->id, 'porcentaje' => 50]];
    $this->put('/settings/repartos', ['reparto_mercaderia' => $this->split])->assertSessionHasNoErrors();
    $client = Cliente::create(['nombre' => 'Cliente', 'apellido' => 'Prueba', 'dni' => '123', 'mail' => 'cliente@example.test', 'telefono' => '123', 'direccion' => 'Local']);
    $this->product = Articulo::create(['nombre' => 'Producto nuevo', 'precio_venta_centavos' => 1000000]);
    $this->lot = LoteStock::create(['articulo_id' => $this->product->id, 'comprador_id' => $this->buyer->id, 'cantidad_inicial' => 5, 'cantidad_disponible' => 5, 'costo_unitario_centavos' => 900000, 'tipo' => 'inicial', 'fecha' => \App\Services\Comercio\FechaComercial::hoy()]);
    $this->legacy = Producto::create(['nombre' => 'Producto anterior', 'marca' => 'Anterior', 'precio' => 100, 'cantidad_disponible' => 8]);
    $this->data = [
        'cliente_id' => $client->id, 'equipo' => 'Notebook', 'estado_ingreso' => 'No enciende', 'cargador' => false,
        'trabajo_realizar' => 'Reparar', 'costo_mano_obra' => '1000', 'reparto_mano_obra' => $this->split,
        'items' => [['tipo' => 'stock', 'lote_id' => $this->lot->id, 'cantidad' => 1, 'precio' => '10000', 'reparto' => $this->split]],
    ];
    $this->post('/Pedido', $this->data)->assertRedirect()->assertSessionHasNoErrors();
    $this->order = Pedido::firstOrFail();
    $this->repair = $this->order->operaciones()->where('tipo', 'reparacion')->firstOrFail();
    $this->operation = $this->order->operaciones()->where('tipo', 'venta')->firstOrFail();
    $this->data['items'][0]['id'] = $this->operation->items()->where('tipo', 'stock')->firstOrFail()->id;
});

function avanzarPedidoAcciones($test, int $state): void
{
    for ($next = $test->order->fresh()->estadoActual_id + 1; $next <= $state; $next++) {
        $test->post('/Pedido/'.$test->order->id.'/actualizarEstado/'.$next, ['fecha_cobro' => \App\Services\Comercio\FechaComercial::hoy(), 'medio_pago' => 'Efectivo', 'total_esperado' => $test->order->operaciones()->where('estado', '!=', 'anulada')->whereNull('fecha_cobro')->sum('total_centavos')])->assertRedirect()->assertSessionHasNoErrors();
    }
}

test('order sales allocations appear only after payment and exclude repair labor', function () {
    $assertShares = function (int $count, int $owner, int $partner) {
        $this->get('/Pedido/'.$this->order->id)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('ventasParticipantes', 2)
            ->where('ventasParticipantes', function ($rows) use ($count, $owner, $partner) {
                $rows = collect($rows)->keyBy('participante_id');

                return $rows[$this->buyer->id]['cantidad'] === $count
                    && $rows[$this->buyer->id]['total_centavos'] === $owner
                    && $rows[$this->partner->id]['total_centavos'] === $partner;
            }));
    };
    $assertShares(0, 0, 0);
    avanzarPedidoAcciones($this, 4);
    $assertShares(0, 0, 0);

    // A separately charged sale is visible while the repair remains unpaid.
    $sale = ['clave' => (string) Str::uuid(), 'pedido_id' => $this->order->id, 'fecha' => \App\Services\Comercio\FechaComercial::hoy(), 'medio_pago' => 'Efectivo', 'items' => [array_diff_key($this->data['items'][0], ['id' => true])]];
    $this->post('/comercio/ventas', $sale)->assertRedirect()->assertSessionHasNoErrors();
    $sold = Operacion::where('tipo', 'venta')->latest('id')->firstOrFail();
    $assertShares(0, 0, 0);
    $this->post('/comercio/operaciones/'.$sold->id.'/cobrar', ['fecha_cobro' => \App\Services\Comercio\FechaComercial::hoy(), 'medio_pago' => 'Efectivo', 'total_esperado' => $sold->total_centavos])->assertSessionHasNoErrors();
    $assertShares(1, 950000, 50000);

    // The saved percentages still apply when configuration changes before payment.
    $this->put('/settings/repartos', ['reparto_mercaderia' => [['participante_id' => $this->buyer->id, 'porcentaje' => 100]]])->assertSessionHasNoErrors();
    avanzarPedidoAcciones($this, 5);
    $assertShares(2, 1900000, 100000);

    // Undoing delivery preserves every actual payment.
    $delivered = PedidoEstado::where('pedido_id', $this->order->id)->latest('id')->firstOrFail();
    $this->delete('/Pedido/'.$this->order->id.'/estados/'.$delivered->id)->assertRedirect()->assertSessionHasNoErrors();
    $assertShares(2, 1900000, 100000);
    $this->post('/comercio/operaciones/'.$sold->id.'/anular')->assertRedirect()->assertSessionHasNoErrors();
    $assertShares(1, 950000, 50000);
});

test('editing is available in every new order state and preserves materialized values', function (int $state) {
    avanzarPedidoAcciones($this, $state);
    $rows = $this->operation->items()->orderBy('id')->get()->toArray();
    $date = $this->operation->fresh()->fecha_cobro;
    $movements = InventarioMovimiento::count();
    $available = $this->lot->fresh()->cantidad_disponible;
    $this->product->update(['nombre' => 'Otro nombre', 'precio_venta_centavos' => 1500000, 'activo' => false]);
    $this->lot->update(['costo_unitario_centavos' => 950000, 'comprador_id' => $this->partner->id]);
    $this->buyer->update(['nombre' => 'Otro nombre de comprador', 'activo' => false]);
    $this->get('/Pedido/'.$this->order->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Pedidos/Edit'));
    $this->data['equipo'] = 'Notebook corregida';
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertRedirect()->assertSessionHasNoErrors();
    expect($this->order->fresh()->estadoActual_id)->toBe($state)
        ->and($this->order->fresh()->equipo)->toBe('Notebook corregida')
        ->and($this->operation->items()->orderBy('id')->get()->toArray())->toBe($rows)
        ->and($this->operation->fresh()->fecha_cobro)->toBe($date)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe($available)
        ->and(InventarioMovimiento::count())->toBe($movements)
        ->and($this->legacy->fresh()->cantidad_disponible)->toBe(8);
})->with([2, 3, 4, 5]);

test('changing approved quantities adjusts only the difference and preserves the cost snapshot', function () {
    avanzarPedidoAcciones($this, 3);
    $this->lot->update(['costo_unitario_centavos' => 950000]);
    $this->data['items'][0]['cantidad'] = 3;
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertRedirect()->assertSessionHasNoErrors();
    expect($this->lot->fresh()->cantidad_disponible)->toBe(2)
        ->and(InventarioMovimiento::orderBy('id')->pluck('cantidad')->all())->toBe([-1, -2])
        ->and($this->operation->fresh()->costo_centavos)->toBe(2700000)
        ->and($this->operation->fresh()->total_centavos)->toBe(3000000);
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertRedirect()->assertSessionHasNoErrors();
    expect(InventarioMovimiento::count())->toBe(2)->and($this->lot->fresh()->cantidad_disponible)->toBe(2);
    $this->data['items'][0]['cantidad'] = 2;
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertRedirect()->assertSessionHasNoErrors();
    expect($this->lot->fresh()->cantidad_disponible)->toBe(3)
        ->and(InventarioMovimiento::latest('id')->first()->cantidad)->toBe(1);
    $entry = PedidoEstado::where('pedido_id', $this->order->id)->latest('id')->first();
    $this->delete('/Pedido/'.$this->order->id.'/estados/'.$entry->id)->assertRedirect()->assertSessionHasNoErrors();
    expect($this->lot->fresh()->cantidad_disponible)->toBe(5);
});

test('existing assigned units can be edited when the shelf has no remaining stock', function () {
    $this->data['items'][0]['cantidad'] = 5;
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertRedirect()->assertSessionHasNoErrors();
    avanzarPedidoAcciones($this, 3);
    expect($this->lot->fresh()->cantidad_disponible)->toBe(0);
    $this->data['trabajo_realizar'] = 'Descripción corregida';
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertRedirect()->assertSessionHasNoErrors();
    expect($this->lot->fresh()->cantidad_disponible)->toBe(0)->and(InventarioMovimiento::count())->toBe(1);
    $before = $this->operation->items()->get()->toArray();
    $this->data['items'][0]['cantidad'] = 6;
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertSessionHasErrors('items');
    expect($this->operation->items()->get()->toArray())->toBe($before)
        ->and($this->operation->fresh()->estado)->toBe('confirmada')
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(0)
        ->and(InventarioMovimiento::count())->toBe(1);
});

test('replacing the product returns its previous units and takes the new ones atomically', function () {
    avanzarPedidoAcciones($this, 3);
    $other = LoteStock::create(['articulo_id' => $this->product->id, 'comprador_id' => $this->partner->id, 'cantidad_inicial' => 2, 'cantidad_disponible' => 2, 'costo_unitario_centavos' => 800000, 'tipo' => 'inicial', 'fecha' => \App\Services\Comercio\FechaComercial::hoy()]);
    $this->data['items'][0]['lote_id'] = $other->id;
    $this->data['items'][0]['cantidad'] = 3;
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertSessionHasErrors('items');
    expect($this->lot->fresh()->cantidad_disponible)->toBe(4)->and($other->fresh()->cantidad_disponible)->toBe(2);
    $this->data['items'][0]['cantidad'] = 2;
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertRedirect()->assertSessionHasNoErrors();
    expect($this->lot->fresh()->cantidad_disponible)->toBe(5)->and($other->fresh()->cantidad_disponible)->toBe(0)
        ->and(OperacionItem::find($this->data['items'][0]['id'])->costo_unitario_centavos)->toBe(800000);
});

test('delivered monetary edits are rejected and retain the original payment date', function () {
    avanzarPedidoAcciones($this, 5);
    $this->operation->update(['fecha_cobro' => '2026-08-15']);
    $this->order->update(['fecha_pago' => '2026-08-15']);
    $this->repair->update(['fecha_cobro' => '2026-08-15']);
    $this->data['costo_mano_obra'] = '1200';
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertSessionHasErrors('items');
    expect($this->operation->fresh()->fecha_cobro)->toBe('2026-08-15')
        ->and($this->order->fresh()->fecha_pago)->toBe('2026-08-15')
        ->and(Operacion::count())->toBe(2)
        ->and(app(ResumenComercial::class)->mes(2026, 8)['cobros_centavos'])->toBe(1050000)
        ->and(InventarioMovimiento::count())->toBe(1);
});

test('reverting delivery preserves all collections and does not charge again', function () {
    $sale = ['clave' => (string) Str::uuid(), 'pedido_id' => $this->order->id, 'fecha' => \App\Services\Comercio\FechaComercial::hoy(), 'medio_pago' => 'Efectivo', 'items' => [array_diff_key($this->data['items'][0], ['id' => true])]];
    $this->post('/comercio/ventas', $sale)->assertRedirect()->assertSessionHasNoErrors();
    $saleOperation = Operacion::where('tipo', 'venta')->latest('id')->first();
    avanzarPedidoAcciones($this, 5);
    $saleBefore = $saleOperation->fresh()->toArray();
    $entry = PedidoEstado::where('pedido_id', $this->order->id)->latest('id')->first();
    $this->delete('/Pedido/'.$this->order->id.'/estados/'.$entry->id)->assertRedirect()->assertSessionHasNoErrors();
    expect($this->order->fresh()->estadoActual_id)->toBe(4)
        ->and($this->order->fresh()->fecha_pago)->not->toBeNull()
        ->and($this->operation->fresh()->fecha_cobro)->not->toBeNull()
        ->and($saleOperation->fresh()->toArray())->toBe($saleBefore)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(3)
        ->and(app(ResumenComercial::class)->mes(now()->year, now()->month)['cobros_centavos'])->toBe(2000000);
    $this->delete('/Pedido/'.$this->order->id.'/estados/'.$entry->id)->assertStatus(422);
    avanzarPedidoAcciones($this, 5);
    expect(app(ResumenComercial::class)->mes(now()->year, now()->month)['cobros_centavos'])->toBe(2000000)
        ->and(InventarioMovimiento::count())->toBe(2);
});

test('deleting a delivered order keeps stock movements purchases and independent sales recorded', function () {
    $this->data['items'][] = ['tipo' => 'repuesto', 'descripcion' => 'Batería', 'comprador_id' => $this->buyer->id, 'cantidad' => 1, 'costo' => '7000', 'porcentaje_ganancia' => 20, 'precio' => '8400', 'fecha_compra' => \App\Services\Comercio\FechaComercial::hoy(), 'reparto' => $this->split];
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertRedirect()->assertSessionHasNoErrors();
    $sale = ['clave' => (string) Str::uuid(), 'pedido_id' => $this->order->id, 'fecha' => \App\Services\Comercio\FechaComercial::hoy(), 'medio_pago' => 'Efectivo', 'items' => [array_diff_key($this->data['items'][0], ['id' => true])]];
    $this->post('/comercio/ventas', $sale)->assertRedirect()->assertSessionHasNoErrors();
    avanzarPedidoAcciones($this, 5);
    $snapshot = $this->operation->items()->get()->toArray();
    $this->delete('/Pedido/'.$this->order->id)->assertRedirect('/Pedido')->assertSessionHasNoErrors();
    $this->assertSoftDeleted('pedido', ['id' => $this->order->id]);
    expect($this->operation->items()->get()->toArray())->toBe($snapshot)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(3)
        ->and(InventarioMovimiento::count())->toBe(2)
        ->and(CompraRepuesto::count())->toBe(1)
        ->and(app(ResumenComercial::class)->mes(now()->year, now()->month)['cobros_centavos'])->toBe(1900000)
        ->and(app(ResumenComercial::class)->mes(now()->year, now()->month)['gastos_centavos'])->toBe(0);
    $saleOperation = Operacion::where('tipo', 'venta')->latest('id')->first();
    $this->get('/comercio/operaciones/'.$saleOperation->id)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('operacion.estado', 'confirmada')->where('operacion.pedido.id', $this->order->id)
        ->where('operacion.pedido.deleted_at', fn ($date) => $date !== null));
});

test('purchased parts keep their purchase when editing an approved order from a stale form', function () {
    $this->data['items'][] = ['tipo' => 'repuesto', 'descripcion' => 'Batería', 'comprador_id' => $this->buyer->id, 'cantidad' => 1, 'costo' => '7000', 'porcentaje_ganancia' => 20, 'precio' => '8400', 'reparto' => $this->split];
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertRedirect()->assertSessionHasNoErrors();
    $part = $this->repair->items()->where('tipo', 'repuesto')->first();
    $this->data['items'][1]['id'] = $part->id;
    $this->post('/comercio/repuestos/'.$part->id.'/comprar', ['fecha_compra' => \App\Services\Comercio\FechaComercial::hoy()])->assertRedirect()->assertSessionHasNoErrors();
    avanzarPedidoAcciones($this, 3);
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertRedirect()->assertSessionHasNoErrors();
    expect(CompraRepuesto::count())->toBe(1)->and($part->fresh()->compra_id)->not->toBeNull();
    $this->data['items'] = [$this->data['items'][0]];
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertRedirect()->assertSessionHasNoErrors();
    expect(CompraRepuesto::count())->toBe(1)->and($part->fresh())->toBeNull()
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(4);
});

test('editing cannot use a row or reverse a state belonging to another order', function () {
    $this->post('/Pedido', $this->data)->assertSessionHasErrors('items.0.id');
    expect(Pedido::count())->toBe(1);
    $newData = $this->data;
    unset($newData['items'][0]['id']);
    $this->post('/Pedido', $newData)->assertRedirect()->assertSessionHasNoErrors();
    $other = Pedido::latest('id')->first();
    $foreign = $other->operaciones()->where('tipo', 'venta')->first()->items()->where('tipo', 'stock')->first();
    $this->data['items'][0]['id'] = $foreign->id;
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertSessionHasErrors('items.0.id');
    $entry = PedidoEstado::where('pedido_id', $other->id)->latest('id')->first();
    $this->delete('/Pedido/'.$this->order->id.'/estados/'.$entry->id)->assertStatus(422);
});

test('old and new order items stay in separate circuits', function () {
    $this->data['productos'] = [['id' => $this->legacy->id, 'cantidad' => 1]];
    $this->put('/Pedido/'.$this->order->id, $this->data)->assertSessionHasErrors('productos');
    $old = Pedido::create(['comercio_version' => 1, 'cliente_id' => $this->order->cliente_id, 'estadoActual_id' => 1, 'equipo' => 'Anterior', 'cargador' => false, 'fecha_ingreso' => now(), 'estado_ingreso' => 'No enciende']);
    $this->put('/Pedido/'.$old->id, $this->data)->assertSessionHasErrors('items');
    $this->get('/Pedido/'.$old->id.'/edit')->assertInertia(fn (Assert $page) => $page->component('Pedidos/EditLegacy')->missing('repartoRepuestos'));
    expect($this->legacy->fresh()->cantidad_disponible)->toBe(8)->and($old->operaciones()->count())->toBe(0);
});
