<?php

use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\LoteStock;
use App\Models\MovimientoStock;
use App\Models\Operacion;
use App\Models\Participante;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\Producto;
use App\Models\ProductoSeleccionado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::create(['name' => 'Operador', 'email' => 'pedidos-legacy@example.test', 'password' => bcrypt('test-password')]));
    foreach (['En revisión', 'Pendiente', 'Aprobado', 'Finalizado', 'Entregado'] as $index => $name) {
        DB::table('estado')->insert(['id' => $index + 1, 'nombre' => $name]);
    }
    $client = Cliente::create(['nombre' => 'Cliente', 'apellido' => 'Anterior', 'dni' => '123', 'mail' => 'cliente@example.test', 'telefono' => '123', 'direccion' => 'Local']);
    $this->product = Producto::create(['nombre' => 'Repuesto anterior', 'marca' => 'Acme', 'precio' => 999, 'cantidad_disponible' => 5]);
    $this->order = Pedido::create([
        'cliente_id' => $client->id, 'comercio_version' => 1, 'estadoActual_id' => 2,
        'equipo' => 'Notebook', 'cargador' => true, 'fecha_ingreso' => '2026-08-01',
        'estado_ingreso' => 'No enciende', 'trabajo_realizar' => 'Reparar',
        'costo' => 200, 'presupuesto' => 350, 'ganancia' => 150, 'costo_mano_obra' => 60,
    ]);
    $this->order->generarCodigo();
    PedidoEstado::create(['pedido_id' => $this->order->id, 'estado_id' => 1]);
    PedidoEstado::create(['pedido_id' => $this->order->id, 'estado_id' => 2]);
    $this->item = ProductoSeleccionado::create(['pedido_id' => $this->order->id, 'producto_id' => $this->product->id, 'cantidad' => 2, 'precio' => 100]);
    $this->item->forceFill(['precio_venta' => 145])->save();
    $buyer = Participante::create(['nombre' => 'Comprador']);
    $article = Articulo::create(['nombre' => 'Catálogo nuevo', 'precio_venta_centavos' => 10000]);
    $this->lot = LoteStock::create(['articulo_id' => $article->id, 'comprador_id' => $buyer->id, 'cantidad_inicial' => 7, 'cantidad_disponible' => 7, 'costo_unitario_centavos' => 9000, 'tipo' => 'inicial', 'fecha' => now()->toDateString()]);
    $this->payload = [
        'cliente_id' => $client->id, 'equipo' => 'Notebook corregida', 'estado_ingreso' => 'No enciende',
        'cargador' => true, 'trabajo_realizar' => 'Reparar', 'costo_mano_obra' => '60.00',
        'productos' => [['id' => $this->product->id, 'cantidad' => 2]],
    ];
});

test('legacy orders remain editable in every existing state without new commerce configuration', function (int $state) {
    $this->order->update(['estadoActual_id' => $state]);
    $this->get('/Pedido/'.$this->order->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Pedidos/EditLegacy')->where('pedido.comercio_version', 1)
        ->has('pedido.productos_seleccionados', 1)->missing('repartoMercaderia'));
    $line = $this->item->fresh()->toArray();
    // Extra display values cannot replace the prices recorded on the server.
    $this->payload['productos'][0] += ['precio' => 1, 'precio_venta' => 2, 'producto' => ['nombre' => 'Otro']];
    $this->put('/Pedido/'.$this->order->id, $this->payload)->assertSessionHasNoErrors()->assertRedirect('/Pedido/'.$this->order->id);
    expect($this->order->fresh()->equipo)->toBe('Notebook corregida')
        ->and((float) $this->order->fresh()->presupuesto)->toBe(350.0)
        ->and((float) $this->order->fresh()->costo)->toBe(200.0)
        ->and((float) $this->order->fresh()->ganancia)->toBe(150.0)
        ->and($this->item->fresh()->toArray())->toBe($line)
        ->and($this->product->fresh()->cantidad_disponible)->toBe(5)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(7)
        ->and(Operacion::count())->toBe(0);
})->with([1, 2, 3, 4, 5]);

test('editing only initial data retains every previous line and amount', function () {
    $this->put('/Pedido/'.$this->order->id, collect($this->payload)->only(['cliente_id', 'equipo', 'estado_ingreso', 'cargador'])->all())
        ->assertSessionHasNoErrors()->assertRedirect();
    expect($this->item->fresh()->cantidad)->toBe(2)
        ->and((float) $this->order->fresh()->presupuesto)->toBe(350.0);
});

test('legacy approval reversal and reapproval affect only old stock once', function () {
    $url = '/Pedido/'.$this->order->id;
    $this->post($url.'/actualizarEstado/3')->assertSessionHasNoErrors()->assertRedirect();
    $this->post($url.'/actualizarEstado/3')->assertSessionHasNoErrors()->assertRedirect();
    expect($this->product->fresh()->cantidad_disponible)->toBe(3)->and(MovimientoStock::count())->toBe(1);
    $state = PedidoEstado::where('pedido_id', $this->order->id)->latest('id')->firstOrFail();
    $this->delete($url.'/estados/'.$state->id)->assertSessionHasNoErrors()->assertRedirect();
    $this->delete($url.'/estados/'.$state->id)->assertStatus(422);
    expect($this->product->fresh()->cantidad_disponible)->toBe(5)->and(MovimientoStock::count())->toBe(0);
    $this->post($url.'/actualizarEstado/3')->assertSessionHasNoErrors()->assertRedirect();
    $this->post($url.'/actualizarEstado/4')->assertSessionHasNoErrors()->assertRedirect();
    $this->post($url.'/actualizarEstado/5')->assertSessionHasNoErrors()->assertRedirect();
    expect($this->order->fresh()->fecha_pago)->not->toBeNull()
        ->and($this->product->fresh()->cantidad_disponible)->toBe(3)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(7)
        ->and((float) $this->order->fresh()->presupuesto)->toBe(350.0)
        ->and(Operacion::count())->toBe(0);
    $state = PedidoEstado::where('pedido_id', $this->order->id)->latest('id')->firstOrFail();
    $this->delete($url.'/estados/'.$state->id)->assertSessionHasNoErrors()->assertRedirect();
    expect($this->order->fresh()->estadoActual_id)->toBe(4)
        ->and($this->order->fresh()->fecha_pago)->toBeNull()
        ->and($this->product->fresh()->cantidad_disponible)->toBe(3);
});

test('legacy approvals do not introduce the new inventory restrictions', function () {
    $this->product->update(['cantidad_disponible' => 0]);
    $this->post('/Pedido/'.$this->order->id.'/actualizarEstado/3')->assertSessionHasNoErrors()->assertRedirect();
    expect($this->product->fresh()->cantidad_disponible)->toBe(-2)->and($this->lot->fresh()->cantidad_disponible)->toBe(7);
});

test('changing an approved legacy quantity uses recorded prices and adjusts its own stock', function () {
    $url = '/Pedido/'.$this->order->id;
    $this->post($url.'/actualizarEstado/3')->assertRedirect();
    $this->payload['productos'][0]['cantidad'] = 3;
    $this->put($url, $this->payload)->assertSessionHasNoErrors()->assertRedirect();
    $item = $this->order->productosSeleccionados()->firstOrFail();
    expect($item->cantidad)->toBe(3)->and((float) $item->precio)->toBe(100.0)
        ->and((float) $item->precio_venta)->toBe(145.0)
        ->and((float) $this->order->fresh()->presupuesto)->toBe(495.0)
        ->and((float) $this->order->fresh()->ganancia)->toBe(195.0)
        ->and($this->product->fresh()->cantidad_disponible)->toBe(2)
        ->and(MovimientoStock::where('pedido_id', $this->order->id)->sum('cantidad'))->toBe(3)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(7);
});

test('changing labor does not recreate legacy product lines or movements', function () {
    $this->post('/Pedido/'.$this->order->id.'/actualizarEstado/3')->assertRedirect();
    $movement = MovimientoStock::first()->toArray();
    $this->payload['costo_mano_obra'] = 90;
    $this->put('/Pedido/'.$this->order->id, $this->payload)->assertSessionHasNoErrors()->assertRedirect();
    expect($this->item->fresh())->not->toBeNull()
        ->and(MovimientoStock::first()->toArray())->toBe($movement)
        ->and((float) $this->order->fresh()->presupuesto)->toBe(380.0)
        ->and((float) $this->order->fresh()->costo)->toBe(200.0);
});

test('old quote links accept the old payload and advance to pending without new fields', function () {
    $this->order->productosSeleccionados()->delete();
    $this->order->update(['estadoActual_id' => 1, 'costo' => 0, 'ganancia' => 0, 'presupuesto' => 0, 'costo_mano_obra' => null]);
    PedidoEstado::where('pedido_id', $this->order->id)->where('estado_id', 2)->delete();
    $this->put('/Pedido/'.$this->order->id.'/genera_presupuesto', [
        'trabajo_realizar' => 'Reparar', 'costo_mano_obra' => 0,
        'productos' => [['id' => $this->product->id, 'cantidad' => 1]],
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($this->order->fresh()->estadoActual_id)->toBe(2)
        ->and((float) $this->order->fresh()->presupuesto)->toBe(1298.7)
        ->and((float) $this->order->productosSeleccionados()->first()->precio_venta)->toBe(1298.7)
        ->and($this->product->fresh()->cantidad_disponible)->toBe(5);
});

test('legacy edit links still resolve to the working editor', function (string $action) {
    $this->get('/Pedido/'.$this->order->id.'/'.$action)->assertRedirect('/Pedido/'.$this->order->id.'/edit');
})->with(['editI', 'editF']);

test('deleting a legacy order preserves the former soft deletion and inventory behavior', function (int $state) {
    $this->order->update(['estadoActual_id' => $state]);
    $this->delete('/Pedido/'.$this->order->id)->assertSessionHasNoErrors()->assertRedirect('/Pedido');
    $this->assertSoftDeleted('pedido', ['id' => $this->order->id]);
    expect($this->item->fresh())->not->toBeNull()
        ->and($this->product->fresh()->cantidad_disponible)->toBe(5)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(7);
})->with([1, 3, 5]);

test('invalid changes roll back stock and the previous legacy quote', function () {
    $url = '/Pedido/'.$this->order->id;
    $this->post($url.'/actualizarEstado/3')->assertRedirect();
    $movement = MovimientoStock::first()->toArray();
    $expensive = Producto::create(['nombre' => 'Caro', 'marca' => 'Acme', 'precio' => 99999999, 'cantidad_disponible' => 10]);
    $this->payload['productos'][] = ['id' => $expensive->id, 'cantidad' => 2];
    $this->put($url, $this->payload)->assertSessionHasErrors('productos');
    expect($this->item->fresh())->not->toBeNull()
        ->and((float) $this->order->fresh()->presupuesto)->toBe(350.0)
        ->and($this->product->fresh()->cantidad_disponible)->toBe(3)
        ->and($expensive->fresh()->cantidad_disponible)->toBe(10)
        ->and(MovimientoStock::first()->toArray())->toBe($movement);
});

test('removing legacy products from an approved quote returns their units once', function () {
    $url = '/Pedido/'.$this->order->id;
    $this->post($url.'/actualizarEstado/3')->assertRedirect();
    $this->payload['productos'] = [];
    $this->put($url, $this->payload)->assertSessionHasNoErrors()->assertRedirect();
    $this->put($url, $this->payload)->assertSessionHasNoErrors()->assertRedirect();
    expect($this->order->productosSeleccionados()->count())->toBe(0)
        ->and($this->product->fresh()->cantidad_disponible)->toBe(5)
        ->and(MovimientoStock::count())->toBe(0)
        ->and((float) $this->order->fresh()->presupuesto)->toBe(60.0);
});
