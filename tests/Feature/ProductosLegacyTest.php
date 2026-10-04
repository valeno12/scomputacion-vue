<?php

use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\LoteStock;
use App\Models\MovimientoStock;
use App\Models\Participante;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoSeleccionado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::create(['name' => 'Operador', 'email' => 'legacy@example.test', 'password' => bcrypt('test-password')]));
    $this->product = Producto::create(['nombre' => 'Batería de un pedido anterior', 'marca' => 'Acme', 'precio' => 100, 'cantidad_disponible' => 3]);
    $this->movement = MovimientoStock::create(['producto_id' => $this->product->id, 'tipo_movimiento' => 'entrada', 'cantidad' => 3, 'precio' => 100, 'fecha' => now()->toDateString()]);
    $buyer = Participante::create(['nombre' => 'Comprador']);
    $this->article = Articulo::create(['nombre' => 'Producto nuevo de la repisa', 'precio_venta_centavos' => 10000]);
    $this->lot = LoteStock::create(['articulo_id' => $this->article->id, 'comprador_id' => $buyer->id, 'cantidad_inicial' => 7, 'cantidad_disponible' => 7, 'costo_unitario_centavos' => 9000, 'tipo' => 'inicial', 'fecha' => now()->toDateString()]);
    DB::table('estado')->insert([['id' => 2, 'nombre' => 'Pendiente'], ['id' => 5, 'nombre' => 'Entregado']]);
    $client = Cliente::create(['nombre' => 'Cliente', 'apellido' => 'Anterior', 'dni' => '123', 'mail' => 'cliente@example.test', 'telefono' => '123', 'direccion' => 'Local']);
    $this->order = Pedido::create(['cliente_id' => $client->id, 'estadoActual_id' => 5, 'equipo' => 'Notebook', 'cargador' => false, 'fecha_ingreso' => now()->toDateString(), 'estado_ingreso' => 'No enciende', 'costo' => 100, 'presupuesto' => 130, 'ganancia' => 30, 'costo_mano_obra' => 0]);
    $this->item = ProductoSeleccionado::create(['pedido_id' => $this->order->id, 'producto_id' => $this->product->id, 'cantidad' => 1, 'precio' => 100]);
    $this->item->forceFill(['precio_venta' => 130])->save();
});

test('both legacy catalog addresses show only the original products', function (string $path) {
    $this->get($path.'?search=ANTERIOR')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('ProductosLegacy/Index')->has('data.data', 1)
        ->where('data.data.0.id', $this->product->id)->where('data.data.0.nombre', $this->product->nombre));
    $this->get($path.'/'.$this->product->id.'/edit')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('ProductosLegacy/Edit')->where('producto.id', $this->product->id));
})->with(['/productos_legacy', '/Producto']);

test('editing an old product preserves recorded orders and the new inventory', function (string $path) {
    $orderBefore = $this->order->fresh()->toArray();
    $itemBefore = $this->item->fresh()->toArray();
    $articleBefore = $this->article->fresh()->toArray();
    $lotBefore = $this->lot->fresh()->toArray();
    $this->put($path.'/'.$this->product->id, ['nombre' => 'Nombre corregido', 'marca' => 'Acme', 'precio' => '125.50', 'cantidad_disponible' => -1])
        ->assertSessionHasNoErrors()->assertRedirect('/productos_legacy');
    expect($this->product->fresh()->nombre)->toBe('Nombre corregido')
        ->and((float) $this->product->fresh()->precio)->toBe(125.5)
        ->and($this->product->fresh()->cantidad_disponible)->toBe(-1)
        ->and($this->order->fresh()->toArray())->toBe($orderBefore)
        ->and($this->item->fresh()->toArray())->toBe($itemBefore)
        ->and($this->article->fresh()->toArray())->toBe($articleBefore)
        ->and($this->lot->fresh()->toArray())->toBe($lotBefore);
})->with(['/productos_legacy', '/Producto']);

test('legacy deletion keeps the original product and movement soft deletion behavior', function () {
    $this->order->update(['estadoActual_id' => 2]);
    $this->delete('/productos_legacy/'.$this->product->id)->assertSessionHasNoErrors()->assertRedirect('/productos_legacy');
    $this->assertSoftDeleted('producto', ['id' => $this->product->id]);
    $this->assertSoftDeleted('movimiento_stock', ['id' => $this->movement->id]);
    expect($this->item->fresh()->producto->nombre)->toBe($this->product->nombre)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(7);
    $this->get('/productos_legacy')->assertInertia(fn (Assert $page) => $page->has('data.data', 0));
});

test('legacy entries are editable without changing new stock', function () {
    $this->get('/movimientos-stock/'.$this->movement->id.'/edit')->assertOk();
    $this->put('/movimientos-stock/'.$this->movement->id, ['precio' => 120, 'cantidad' => 4])->assertSessionHasNoErrors()->assertRedirect();
    expect((float) $this->movement->fresh()->precio)->toBe(120.0)
        ->and($this->movement->fresh()->cantidad)->toBe(4)
        ->and($this->product->fresh()->cantidad_disponible)->toBe(3)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(7);
});

test('legacy catalog does not accept new products and old search links still work', function () {
    $this->get('/Producto/create')->assertRedirect('/productos');
    $this->post('/Producto', ['nombre' => 'Nuevo'])->assertStatus(422);
    expect(Producto::count())->toBe(1);
    $this->getJson('/Producto/search?q=ACME')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $this->product->id);
    $this->getJson('/Producto/buscarNombre?q=ANTERIOR')->assertOk()->assertExactJson([$this->product->nombre]);
    $this->getJson('/Producto/buscarMarca?q=acme')->assertOk()->assertExactJson(['Acme']);
});
