<?php

use App\Models\InventarioMovimiento;
use App\Models\LoteStock;
use App\Models\MovimientoStock;
use App\Models\Operacion;
use App\Models\Participante;
use App\Models\Producto;
use App\Models\ProductoNuevo;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::create(['name' => 'Operador', 'email' => 'productos@example.test', 'password' => bcrypt('test-password')]));
    $this->buyer = Participante::create(['nombre' => 'Comprador']);
    $this->legacy = Producto::create(['nombre' => 'Producto anterior', 'marca' => 'Anterior', 'precio' => 100, 'cantidad_disponible' => 3]);
    $this->productData = ['nombre' => 'Funda', 'marca' => 'Acme'];
    $this->entryData = ['clave' => (string) Str::uuid(), 'cantidad' => 3, 'costo' => '9000.25', 'comprador_id' => $this->buyer->id, 'proveedor' => 'Distribuidora', 'tipo' => 'compra', 'fecha' => \App\Services\Comercio\FechaComercial::hoy(), 'porcentaje_ganancia' => 40];
});

test('the new catalog is independent and may start without stock', function () {
    expect(Schema::hasTable('producto'))->toBeTrue()->and(Schema::hasTable('productos_nuevo'))->toBeTrue()->and(Schema::hasTable('articulos'))->toBeFalse();
    $this->post('/productos', $this->productData)->assertRedirect('/productos')->assertSessionHasNoErrors();
    expect(ProductoNuevo::count())->toBe(1)->and(Producto::count())->toBe(1)->and(LoteStock::count())->toBe(0);
    $this->get('/productos')->assertInertia(fn (Assert $page) => $page->component('Productos/Index')->has('data.data', 1)
        ->where('data.data.0.nombre', 'Funda')->where('data.data.0.cantidad_disponible', 0)->where('data.data.0.costo_unitario_centavos', null));
});

test('a stock entry saves cost buyer and movement atomically', function () {
    createProductWithEntry($this);
    $lot = LoteStock::firstOrFail();
    expect($lot->cantidad_disponible)->toBe(3)->and($lot->costo_unitario_centavos)->toBe(900025)
        ->and($lot->comprador_id)->toBe($this->buyer->id)->and(InventarioMovimiento::count())->toBe(1)
        ->and(Proveedor::first()->nombre)->toBe('Distribuidora')->and($this->legacy->fresh()->cantidad_disponible)->toBe(3);
});

test('invalid entry leaves the catalog product without partial stock or purchases', function () {
    $this->post('/productos', $this->productData)->assertSessionHasNoErrors();
    $product = ProductoNuevo::first();
    $data = array_merge($this->entryData, ['comprador_id' => 999]);
    $this->post("/productos/$product->id/ingresos", $data)->assertSessionHasErrors('comprador_id');
    expect(ProductoNuevo::count())->toBe(1)->and(LoteStock::count())->toBe(0)->and(InventarioMovimiento::count())->toBe(0)->and(Proveedor::count())->toBe(0);
});

test('restocking keeps each buyer and cost and retries do not add stock twice', function () {
    createProductWithEntry($this);
    $product = ProductoNuevo::firstOrFail();
    $other = Participante::create(['nombre' => 'Otra compradora']);
    $entry = array_merge($this->entryData, ['clave' => (string) Str::uuid(), 'comprador_id' => $other->id, 'cantidad' => 2, 'costo' => '9500', 'porcentaje_ganancia' => 30, 'proveedor' => 'DISTRIBUIDORA']);
    foreach ([1, 2] as $attempt) {
        $this->post("/productos/$product->id/ingresos", $entry)->assertRedirect('/productos')->assertSessionHasNoErrors();
    }
    expect($product->lotes()->sum('cantidad_disponible'))->toBe(5)->and(LoteStock::count())->toBe(2)->and(InventarioMovimiento::count())->toBe(2)
        ->and(Proveedor::count())->toBe(1)->and($product->fresh()->precio_venta_centavos)->toBe(1235000)
        ->and(LoteStock::first()->costo_unitario_centavos)->toBe(900025)->and(LoteStock::first()->comprador_id)->toBe($this->buyer->id)
        ->and(LoteStock::latest('id')->first()->comprador_id)->toBe($other->id);
    $this->get('/productos?search=ACME&sort_by=cantidad_disponible')->assertInertia(fn (Assert $page) => $page->has('data.data', 1)
        ->where('data.data.0.cantidad_disponible', 5)->where('data.data.0.costo_unitario_centavos', 950000));
});

test('duplicate names and brands lead to restocking instead of silently changing a product', function () {
    $this->post('/productos', $this->productData)->assertSessionHasNoErrors();
    $this->post('/productos', array_merge($this->productData, ['nombre' => ' FUNDA ', 'marca' => 'acme', 'precio_venta' => 1]))->assertSessionHasErrors('nombre');
    expect(ProductoNuevo::count())->toBe(1)->and(ProductoNuevo::first()->precio_venta_centavos)->toBe(0);
    $other = ProductoNuevo::create(['nombre' => 'Otro', 'marca' => 'Acme', 'precio_venta_centavos' => 100]);
    $this->put("/productos/$other->id", $this->productData)->assertSessionHasErrors('nombre');
});

test('stock correction adjusts remaining quantity and preserves sold amounts', function () {
    createProductWithEntry($this);
    $lot = LoteStock::firstOrFail();
    $this->post('/comercio/ventas', ['clave' => (string) Str::uuid(), 'fecha' => \App\Services\Comercio\FechaComercial::hoy(), 'medio_pago' => 'Efectivo', 'items' => [
        ['tipo' => 'stock', 'lote_id' => $lot->id, 'cantidad' => 2, 'precio' => $lot->articulo->precio_venta_centavos / 100, 'reparto' => [['participante_id' => $this->buyer->id, 'porcentaje' => 100]]],
    ]])->assertSessionHasNoErrors();
    $before = Operacion::first()->items->first()->toArray();
    $this->put("/movimientos-stock/ingresos/$lot->id", ['cantidad' => 5, 'precio' => 9200])->assertSessionHasNoErrors();
    expect($lot->fresh()->cantidad_disponible)->toBe(3)->and($lot->fresh()->cantidad_inicial)->toBe(5)
        ->and(InventarioMovimiento::where('motivo', 'compra')->first()->cantidad)->toBe(5)
        ->and(Operacion::first()->items->first()->toArray())->toBe($before);
    $this->put("/movimientos-stock/ingresos/$lot->id", ['cantidad' => 1, 'precio' => 100])->assertSessionHasErrors('cantidad');
    expect($lot->fresh()->cantidad_disponible)->toBe(3)->and($lot->fresh()->costo_unitario_centavos)->toBe(920000);
    $this->get('/productos/'.$lot->articulo_id.'/ingresos/create')->assertInertia(fn (Assert $page) => $page->where('ultimoCosto', 920000));
});

test('one movement list contains old and new purchases and separates sales', function () {
    $old = MovimientoStock::create(['producto_id' => $this->legacy->id, 'tipo_movimiento' => 'entrada', 'cantidad' => 3, 'precio' => 100, 'fecha' => \App\Services\Comercio\FechaComercial::hoy()]);
    createProductWithEntry($this);
    $this->get('/movimientos-stock?tipo=entradas')->assertInertia(fn (Assert $page) => $page->has('data.data', 2)
        ->where('data.data.0.id', 'legacy:'.$old->id)->where('data.data.0.edit_url', route('movimientos-stock.edit', $old->id))
        ->where('data.data.1.clase', 'Producto')->where('data.data.1.edit_url', route('movimientos-stock.ingresos.edit', LoteStock::first()->id)));
    $this->get('/movimientos-stock?tipo=salidas')->assertInertia(fn (Assert $page) => $page->has('data.data', 0));
    $this->get('/movimientos-stock?search=DISTRIBUIDORA')->assertInertia(fn (Assert $page) => $page->has('data.data', 1)->where('data.data.0.precio', 9000.25));
});

test('deleted products cannot receive more stock but their entries remain available', function () {
    createProductWithEntry($this);
    $product = ProductoNuevo::first();
    $this->delete("/productos/$product->id")->assertRedirect('/productos');
    $this->get('/productos')->assertInertia(fn (Assert $page) => $page->has('data.data', 0));
    $this->post("/productos/$product->id/ingresos", array_merge($this->entryData, ['clave' => (string) Str::uuid()]))->assertSessionHasErrors('cantidad');
    expect(LoteStock::count())->toBe(1)->and($product->lotes()->sum('cantidad_disponible'))->toBe(3);
    $this->get('/movimientos-stock')->assertInertia(fn (Assert $page) => $page->has('data.data', 1)->where('data.data.0.producto.nombre', 'Funda'));
});

test('catalog creation and editing do not record stock or set prices', function () {
    $this->post('/productos', array_merge($this->productData, $this->entryData, ['precio_venta' => 1]))->assertSessionHasNoErrors();
    $product = ProductoNuevo::first();
    expect($product->precio_venta_centavos)->toBe(0)->and($product->porcentaje_ganancia)->toBeNull()->and(LoteStock::count())->toBe(0);
    $this->post("/productos/$product->id/ingresos", array_merge($this->entryData, ['precio_venta' => 1]))->assertSessionHasNoErrors();
    $before = $product->fresh()->toArray();
    $this->put("/productos/$product->id", ['nombre' => 'Funda corregida', 'marca' => 'Acme', 'costo' => 1, 'porcentaje_ganancia' => 1, 'precio_venta' => 1])->assertSessionHasNoErrors();
    expect($product->fresh()->precio_venta_centavos)->toBe(1260035)
        ->and($product->fresh()->porcentaje_ganancia)->toBe($before['porcentaje_ganancia'])
        ->and($product->fresh()->costo_referencia_centavos)->toBe($before['costo_referencia_centavos'])
        ->and(LoteStock::count())->toBe(1)->and(LoteStock::first()->cantidad_disponible)->toBe(3);
});

function createProductWithEntry($test): ProductoNuevo
{
    $test->post('/productos', $test->productData)->assertSessionHasNoErrors();
    $product = ProductoNuevo::firstOrFail();
    $test->post("/productos/$product->id/ingresos", $test->entryData)->assertRedirect('/productos')->assertSessionHasNoErrors();

    return $product;
}

function bulkStockPayload($test, $first, $second): array
{
    return [
        'clave' => (string) Str::uuid(), 'comprador_id' => $test->buyer->id, 'proveedor' => 'Distribuidora', 'fecha' => \App\Services\Comercio\FechaComercial::hoy(), 'tipo' => 'compra',
        'items' => [
            ['producto_id' => $first->id, 'cantidad' => 2, 'costo' => '9000', 'porcentaje_ganancia' => 40],
            ['producto_id' => $second->id, 'cantidad' => 3, 'costo' => '1500', 'porcentaje_ganancia' => '25.5'],
        ],
    ];
}

test('one bulk entry saves each product and its price once even after retrying', function () {
    $first = ProductoNuevo::create(['nombre' => 'Funda', 'marca' => 'Acme', 'precio_venta_centavos' => 100]);
    $second = ProductoNuevo::create(['nombre' => 'Mouse', 'marca' => 'Acme', 'precio_venta_centavos' => 100]);
    $payload = bulkStockPayload($this, $first, $second);
    foreach ([1, 2] as $attempt) {
        $this->post('/productos/ingresos', $payload)->assertRedirect('/productos')->assertSessionHasNoErrors();
    }
    expect(LoteStock::count())->toBe(2)->and(InventarioMovimiento::count())->toBe(2)
        ->and($first->lotes()->sum('cantidad_disponible'))->toBe(2)->and($second->lotes()->sum('cantidad_disponible'))->toBe(3)
        ->and($first->fresh()->precio_venta_centavos)->toBe(1260000)->and($second->fresh()->precio_venta_centavos)->toBe(188250)
        ->and(LoteStock::distinct()->count('ingreso_stock_id'))->toBe(1)->and(Proveedor::count())->toBe(1);
    $this->get('/productos/ingresos/create')->assertInertia(fn (Assert $page) => $page->component('Productos/IngresoMultiple')->has('participantes', 2));
    $this->get('/movimientos-stock')->assertInertia(fn (Assert $page) => $page->has('data.data', 2));
});

test('a calculation failure rolls back all rows of the bulk entry', function () {
    $first = ProductoNuevo::create(['nombre' => 'Funda', 'precio_venta_centavos' => 100]);
    $second = ProductoNuevo::create(['nombre' => 'Mouse', 'precio_venta_centavos' => 100]);
    $payload = bulkStockPayload($this, $first, $second);
    $payload['items'][1]['costo'] = '999999999';
    $payload['items'][1]['porcentaje_ganancia'] = 10000;
    $this->post('/productos/ingresos', $payload)->assertSessionHasErrors('items.1.porcentaje_ganancia');
    expect(LoteStock::count())->toBe(0)->and(InventarioMovimiento::count())->toBe(0)->and(Proveedor::count())->toBe(0)
        ->and(\Illuminate\Support\Facades\DB::table('ingresos_stock')->count())->toBe(0)->and($first->fresh()->precio_venta_centavos)->toBe(100);
    $payload['items'][1]['costo'] = '1500';
    $payload['items'][1]['porcentaje_ganancia'] = 40;
    $this->post('/productos/ingresos', $payload)->assertSessionHasNoErrors();
    expect(LoteStock::count())->toBe(2);
});

test('bulk entries reject repeated or inactive products without partial stock', function () {
    $first = ProductoNuevo::create(['nombre' => 'Funda', 'precio_venta_centavos' => 100]);
    $second = ProductoNuevo::create(['nombre' => 'Mouse', 'precio_venta_centavos' => 100, 'activo' => false]);
    $payload = bulkStockPayload($this, $first, $second);
    $this->post('/productos/ingresos', $payload)->assertSessionHasErrors('items.1.producto_id');
    $payload['items'][1]['producto_id'] = $first->id;
    $this->post('/productos/ingresos', $payload)->assertSessionHasErrors('items.1.producto_id');
    expect(LoteStock::count())->toBe(0)->and($first->fresh()->precio_venta_centavos)->toBe(100);
});

test('the inline product dialog creates only catalog data and returns the created product', function () {
    $response = $this->postJson('/productos', $this->productData)->assertCreated()->assertJsonPath('producto.nombre', 'Funda');
    $id = $response->json('producto.id');
    expect(ProductoNuevo::find($id)->precio_venta_centavos)->toBe(0)->and(LoteStock::count())->toBe(0)->and(InventarioMovimiento::count())->toBe(0);
    $this->postJson('/productos', ['nombre' => 'funda', 'marca' => 'ACME'])->assertUnprocessable()->assertJsonValidationErrors('nombre');
    expect(ProductoNuevo::count())->toBe(1);
    $this->post("/productos/$id/ingresos", $this->entryData)->assertSessionHasNoErrors();
    expect(ProductoNuevo::find($id)->lotes()->sum('cantidad_disponible'))->toBe(3);
});

test('product search matches name and brand and excludes legacy or inactive products', function () {
    $product = createProductWithEntry($this);
    ProductoNuevo::create(['nombre' => 'Funda oculta', 'marca' => 'Acme', 'precio_venta_centavos' => 0, 'activo' => false]);
    $this->getJson('/productos/buscar?'.http_build_query(['q' => 'FUNDA acme']))->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $product->id)->assertJsonPath('data.0.cantidad_disponible', 3)->assertJsonPath('data.0.costo_unitario_centavos', 900025);
    $this->getJson('/productos/buscar?q=Anterior')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/productos/buscar?q=inexistente')->assertOk()->assertJsonCount(0, 'data');
});

test('search results are bounded and support catalogs larger than the displayed list', function () {
    foreach (range(1, 25) as $index) {
        ProductoNuevo::create(['nombre' => sprintf('Producto %02d', $index), 'marca' => 'Prueba', 'precio_venta_centavos' => 0]);
    }
    $this->getJson('/productos/buscar')->assertOk()->assertExactJson(['data' => [], 'has_more' => false]);
    $this->getJson('/productos/buscar?q=%20%20')->assertOk()->assertExactJson(['data' => [], 'has_more' => false]);
    $this->getJson('/productos/buscar?q=Producto')->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('has_more', true);
    $this->getJson('/productos/buscar?q=25')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nombre', 'Producto 25')->assertJsonPath('has_more', false);
});

test('selected products prepare an entry in selection order with current costs without recording anything', function () {
    $first = createProductWithEntry($this);
    $second = ProductoNuevo::create(['nombre' => 'Mouse', 'marca' => 'Acme', 'precio_venta_centavos' => 0]);
    LoteStock::first()->update(['costo_unitario_centavos' => 950000]);
    $selection = ['productos' => [$second->id, $first->id]];
    $this->get('/productos/ingresos/create?'.http_build_query($selection))->assertInertia(fn (Assert $page) => $page
        ->component('Productos/IngresoMultiple')->has('productosIniciales', 2)
        ->where('productosIniciales.0.id', $second->id)->where('productosIniciales.0.costo_unitario_centavos', null)
        ->where('productosIniciales.1.id', $first->id)->where('productosIniciales.1.costo_unitario_centavos', 950000));
    $this->get('/productos/ingresos/create')->assertInertia(fn (Assert $page) => $page->has('productosIniciales', 0));
    $first->update(['precio_venta_centavos' => 1600000]);
    $this->getJson('/productos/seleccion?'.http_build_query($selection))->assertOk()->assertJsonCount(2, 'data')
        ->assertJsonPath('data.1.precio_venta_centavos', 1600000);
    $this->getJson('/productos/seleccion')->assertOk()->assertJsonCount(0, 'data');
    expect(LoteStock::count())->toBe(1)->and(InventarioMovimiento::count())->toBe(1);
});

test('product selection rejects unavailable duplicate legacy-only and excessive ids', function () {
    $product = ProductoNuevo::create(['nombre' => 'Mouse', 'precio_venta_centavos' => 0, 'activo' => false]);
    Producto::forceCreate(['id' => 9000, 'nombre' => 'Solo anterior', 'marca' => 'Anterior', 'precio' => 100, 'cantidad_disponible' => 1]);
    foreach ([[$product->id], [9000], [99999], [1, 1], range(1, 101)] as $ids) {
        $this->getJson('/productos/seleccion?'.http_build_query(['productos' => $ids]))->assertUnprocessable();
        $this->getJson('/productos/ingresos/create?'.http_build_query(['productos' => $ids]))->assertUnprocessable();
    }
    expect(LoteStock::count())->toBe(0);
});

test('bulk price increases change only current catalog prices and preserve sales budgets receipts and legacy', function () {
    $first = createProductWithEntry($this);
    $second = ProductoNuevo::create(['nombre' => 'Mouse', 'marca' => 'Acme', 'precio_venta_centavos' => 1005]);
    $lot = LoteStock::first();
    $split = [['participante_id' => $this->buyer->id, 'porcentaje' => 100]];
    $item = ['tipo' => 'stock', 'lote_id' => $lot->id, 'cantidad' => 1, 'precio' => '12600.35', 'reparto' => $split];
    $this->post('/comercio/ventas', ['clave' => (string) Str::uuid(), 'fecha' => \App\Services\Comercio\FechaComercial::hoy(), 'medio_pago' => 'Efectivo', 'items' => [$item]])->assertSessionHasNoErrors();
    $draft = Operacion::create(['clave' => (string) Str::uuid(), 'tipo' => 'reparacion', 'fecha' => \App\Services\Comercio\FechaComercial::hoy()]);
    app(\App\Services\Comercio\Operaciones::class)->guardarItems($draft, [$item]);
    $rows = \App\Models\OperacionItem::orderBy('id')->get()->toArray();
    $operations = Operacion::orderBy('id')->get()->toArray();
    $lots = LoteStock::orderBy('id')->get()->toArray();
    $movements = InventarioMovimiento::orderBy('id')->get()->toArray();
    $payload = ['porcentaje' => '10', 'items' => [
        ['producto_id' => $first->id, 'precio_actual_centavos' => $first->fresh()->precio_venta_centavos],
        ['producto_id' => $second->id, 'precio_actual_centavos' => 1005],
    ]];
    $this->from('/productos?search=Acme')->post('/productos/precios', $payload)->assertRedirect('/productos?search=Acme')->assertSessionHasNoErrors();
    expect($first->fresh()->precio_venta_centavos)->toBe(1386039)->and($second->fresh()->precio_venta_centavos)->toBe(1106)
        ->and($first->fresh()->porcentaje_ganancia)->toBe(40.0)
        ->and(\App\Models\OperacionItem::orderBy('id')->get()->toArray())->toBe($rows)
        ->and(Operacion::orderBy('id')->get()->toArray())->toBe($operations)
        ->and(LoteStock::orderBy('id')->get()->toArray())->toBe($lots)
        ->and(InventarioMovimiento::orderBy('id')->get()->toArray())->toBe($movements)
        ->and($this->legacy->fresh()->precio)->toEqual(100);
    $this->post('/productos/precios', $payload)->assertSessionHasErrors('items.0.producto_id');
    expect($first->fresh()->precio_venta_centavos)->toBe(1386039)->and($second->fresh()->precio_venta_centavos)->toBe(1106);
});

test('a changed or unavailable product cancels the entire price increase', function (string $case) {
    $first = ProductoNuevo::create(['nombre' => 'Primero', 'precio_venta_centavos' => 10000]);
    $second = ProductoNuevo::create(['nombre' => 'Segundo', 'precio_venta_centavos' => 20000]);
    $payload = ['porcentaje' => 10, 'items' => [
        ['producto_id' => $first->id, 'precio_actual_centavos' => 10000],
        ['producto_id' => $second->id, 'precio_actual_centavos' => 20000],
    ]];
    if ($case === 'changed') {
        $second->update(['precio_venta_centavos' => 21000]);
    }
    if ($case === 'archived') {
        $second->update(['activo' => false]);
    }
    if ($case === 'missing') {
        $payload['items'][1]['producto_id'] = 99999;
    }
    if ($case === 'zero') {
        $second->update(['precio_venta_centavos' => 0]);
        $payload['items'][1]['precio_actual_centavos'] = 0;
    }
    if ($case === 'overflow') {
        $second->update(['precio_venta_centavos' => 99999999900]);
        $payload['items'][1]['precio_actual_centavos'] = 99999999900;
    }
    if ($case === 'rounding') {
        $second->update(['precio_venta_centavos' => 1]);
        $payload['items'][1]['precio_actual_centavos'] = 1;
    }
    $before = $second->fresh()->precio_venta_centavos;
    $this->post('/productos/precios', $payload)->assertSessionHasErrors('items.1.producto_id');
    expect($first->fresh()->precio_venta_centavos)->toBe(10000)->and($second->fresh()->precio_venta_centavos)->toBe($before);
})->with(['changed', 'archived', 'missing', 'zero', 'overflow', 'rounding']);

test('price increases require a positive bounded percentage and unique explicit products', function () {
    $product = ProductoNuevo::create(['nombre' => 'Mouse', 'precio_venta_centavos' => 10000]);
    $item = ['producto_id' => $product->id, 'precio_actual_centavos' => 10000];
    foreach ([-5, 0, '0.001', 10001] as $percentage) {
        $this->post('/productos/precios', ['porcentaje' => $percentage, 'items' => [$item]])->assertSessionHasErrors('porcentaje');
    }
    $this->post('/productos/precios', ['porcentaje' => 10, 'items' => [$item, $item]])->assertSessionHasErrors('items.1.producto_id');
    $this->post('/productos/precios', ['porcentaje' => 10, 'items' => []])->assertSessionHasErrors('items');
    $this->post('/productos/precios', ['porcentaje' => 10, 'items' => array_fill(0, 101, $item)])->assertSessionHasErrors('items');
    expect($product->fresh()->precio_venta_centavos)->toBe(10000);
});
