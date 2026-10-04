<?php

use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\CompraRepuesto;
use App\Models\InventarioMovimiento;
use App\Models\LoteStock;
use App\Models\Operacion;
use App\Models\Participante;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\User;
use App\Services\Comercio\ResumenComercial;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::create(['name' => 'Operador', 'email' => 'operador@example.test', 'password' => bcrypt('test-password')]));
    $this->a = app(\App\Services\Comercio\Repartos::class)->titular();
    $this->a->update(['nombre' => 'Persona A']);
    $this->b = Participante::create(['nombre' => 'Persona B']);
    $this->split = [['participante_id' => $this->a->id, 'porcentaje' => 50], ['participante_id' => $this->b->id, 'porcentaje' => 50]];
    $this->put('/settings/repartos', ['reparto_mercaderia' => $this->split])->assertSessionHasNoErrors();
    $this->article = Articulo::create(['nombre' => 'Funda', 'precio_venta_centavos' => 1000000]);
    $this->lot = LoteStock::create(['articulo_id' => $this->article->id, 'comprador_id' => $this->a->id, 'cantidad_inicial' => 3, 'cantidad_disponible' => 3, 'costo_unitario_centavos' => 900000, 'tipo' => 'inicial', 'fecha' => \App\Services\Comercio\FechaComercial::hoy()]);
    $this->item = ['tipo' => 'stock', 'lote_id' => $this->lot->id, 'cantidad' => 1, 'precio' => '10000', 'reparto' => $this->split];
    $this->sale = ['cobrar' => true, 'fecha_cobro' => \App\Services\Comercio\FechaComercial::hoy(), 'clave' => (string) Str::uuid(), 'fecha' => \App\Services\Comercio\FechaComercial::hoy(), 'medio_pago' => 'Efectivo', 'items' => [$this->item]];
});

function newCommerceOrderData($test): array
{
    foreach (['En revision', 'Pendiente aprobación', 'En proceso', 'Finalizado', 'Entregado'] as $index => $name) {
        \Illuminate\Support\Facades\DB::table('estado')->insert(['id' => $index + 1, 'nombre' => $name]);
    }
    $client = Cliente::create(['nombre' => 'Cliente', 'apellido' => 'Prueba', 'dni' => '123', 'mail' => 'test@example.test', 'telefono' => '123', 'direccion' => 'Local']);

    return ['cliente_id' => $client->id, 'equipo' => 'Notebook', 'estado_ingreso' => 'No enciende', 'cargador' => false, 'trabajo_realizar' => 'Reparar', 'costo_mano_obra' => '1000', 'reparto_mano_obra' => $test->split, 'items' => [$test->item]];
}

test('sale returns the cost to its buyer and distributes only the profit', function () {
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasNoErrors()->assertRedirect();
    $op = Operacion::with('items')->firstOrFail();
    expect($op->total_centavos)->toBe(1000000)->and($op->ganancia_centavos)->toBe(100000)
        ->and($op->items[0]->distribucion[0]['costo_centavos'])->toBe(900000)
        ->and($op->items[0]->distribucion[0]['ganancia_centavos'])->toBe(50000)
        ->and($op->items[0]->distribucion[1]['ganancia_centavos'])->toBe(50000)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(2);
});

test('the financing person may be outside the profit distribution', function () {
    $this->put('/settings/repartos', ['reparto_mercaderia' => [['participante_id' => $this->b->id, 'porcentaje' => 100]]])->assertSessionHasNoErrors();
    $this->post('/comercio/ventas', $this->sale)->assertRedirect()->assertSessionHasNoErrors();
    $shares = Operacion::first()->items->first()->distribucion;
    expect($shares[0]['ganancia_centavos'])->toBe(100000)->and($shares[0]['costo_centavos'])->toBe(0)
        ->and($shares[1]['participante_id'])->toBe($this->a->id)->and($shares[1]['costo_centavos'])->toBe(900000);
});

test('snapshot is not changed by later names prices or default percentages', function () {
    $this->post('/comercio/ventas', $this->sale)->assertRedirect()->assertSessionHasNoErrors();
    $before = Operacion::first()->items->first()->toArray();
    $this->a->update(['nombre' => 'Nombre nuevo']);
    $this->article->update(['precio_venta_centavos' => 5000000]);
    $this->put('/comercio/configuracion', ['reparto_mercaderia' => [['participante_id' => $this->b->id, 'porcentaje' => 100]], 'reparto_repuestos' => $this->split, 'reparto_mano_obra' => $this->split])->assertRedirect()->assertSessionHasNoErrors();
    expect(Operacion::first()->items->first()->toArray())->toBe($before);
});

test('invalid and duplicate configuration leaves no sale or stock movement', function (array $percentages) {
    $this->sale['items'][0]['reparto'] = array_map(fn ($percent) => ['participante_id' => $this->a->id, 'porcentaje' => $percent], $percentages);
    $this->put('/settings/repartos', ['reparto_mercaderia' => $this->sale['items'][0]['reparto']])->assertSessionHasErrors('reparto_mercaderia');
    expect(Operacion::count())->toBe(0)->and($this->lot->fresh()->cantidad_disponible)->toBe(3)->and(InventarioMovimiento::count())->toBe(0);
})->with([[[90]], [[50, 50]]]);

test('stock checks aggregate duplicate lines and roll back the whole operation', function () {
    $this->sale['items'] = [array_merge($this->item, ['cantidad' => 2]), array_merge($this->item, ['cantidad' => 2])];
    $this->post('/comercio/ventas', $this->sale)->assertSessionHasErrors('items');
    expect(Operacion::count())->toBe(0)->and($this->lot->fresh()->cantidad_disponible)->toBe(3);
});

test('repeated submission and repeated cancellation affect stock once', function () {
    $this->post('/comercio/ventas', $this->sale)->assertRedirect()->assertSessionHasNoErrors();
    $this->post('/comercio/ventas', $this->sale)->assertRedirect()->assertSessionHasNoErrors();
    expect(Operacion::count())->toBe(1)->and($this->lot->fresh()->cantidad_disponible)->toBe(2);
    $op = Operacion::first();
    $this->post("/comercio/operaciones/$op->id/anular")->assertRedirect()->assertSessionHasNoErrors();
    $this->post("/comercio/operaciones/$op->id/anular")->assertRedirect()->assertSessionHasNoErrors();
    expect($this->lot->fresh()->cantidad_disponible)->toBe(3)->and(InventarioMovimiento::count())->toBe(2);
    expect(app(ResumenComercial::class)->mes(now()->year, now()->month)['cobros_centavos'])->toBe(0);
});

test('a repair and an attached sale share inventory and are counted once each', function () {
    $data = newCommerceOrderData($this);
    $this->post('/Pedido', $data)->assertRedirect()->assertSessionHasNoErrors();
    $pedido = Pedido::first();
    expect($pedido->estadoActual_id)->toBe(2)->and($this->lot->fresh()->cantidad_disponible)->toBe(3);
    $this->sale['pedido_id'] = $pedido->id;
    $this->post('/comercio/ventas', $this->sale)->assertRedirect()->assertSessionHasNoErrors();
    $this->post("/Pedido/$pedido->id/actualizarEstado/3")->assertRedirect()->assertSessionHasNoErrors();
    $this->post("/Pedido/$pedido->id/actualizarEstado/3")->assertRedirect()->assertSessionHasNoErrors();
    expect($this->lot->fresh()->cantidad_disponible)->toBe(1);
    $this->post("/Pedido/$pedido->id/actualizarEstado/4")->assertRedirect()->assertSessionHasNoErrors();
    $this->post("/Pedido/$pedido->id/actualizarEstado/5", ['fecha_cobro' => \App\Services\Comercio\FechaComercial::hoy(), 'medio_pago' => 'Efectivo', 'total_esperado' => 2100000])->assertRedirect()->assertSessionHasNoErrors();
    $summary = app(ResumenComercial::class)->mes(now()->year, now()->month);
    expect($summary['cobros_centavos'])->toBe(2000000)->and($summary['gastos_centavos'])->toBe(0)
        ->and($summary['ganancia_centavos'])->toBe(200000);
    $this->get('/rendimientos')->assertOk()->assertInertia(fn ($page) => $page->where('gananciasPorMes.0.total_ganancias', 20000));
    $this->put("/Pedido/$pedido->id", $data)->assertRedirect()->assertSessionHasNoErrors();
    expect($this->lot->fresh()->cantidad_disponible)->toBe(1)
        ->and(app(ResumenComercial::class)->mes(now()->year, now()->month)['cobros_centavos'])->toBe(2000000);
});

test('returning an approved repair to pending restores stock only once', function () {
    $data = newCommerceOrderData($this);
    $this->post('/Pedido', $data)->assertRedirect()->assertSessionHasNoErrors();
    $pedido = Pedido::first();
    $this->post("/Pedido/$pedido->id/actualizarEstado/3")->assertRedirect()->assertSessionHasNoErrors();
    $entry = PedidoEstado::latest('id')->first();
    $this->delete("/Pedido/$pedido->id/estados/$entry->id")->assertRedirect()->assertSessionHasNoErrors();
    expect($this->lot->fresh()->cantidad_disponible)->toBe(3);
    $this->delete("/Pedido/$pedido->id/estados/$entry->id")->assertStatus(422);
    expect($this->lot->fresh()->cantidad_disponible)->toBe(3);
});

test('parts retain purchase history without subtracting it from monthly results', function () {
    $data = newCommerceOrderData($this);
    $data['items'] = [['tipo' => 'repuesto', 'descripcion' => 'Batería específica', 'comprador_id' => $this->b->id, 'cantidad' => 1, 'costo' => '7000', 'porcentaje_ganancia' => 20, 'precio' => '8400', 'fecha_compra' => \App\Services\Comercio\FechaComercial::hoy(), 'reparto' => $this->split]];
    $this->post('/Pedido', $data)->assertRedirect()->assertSessionHasNoErrors();
    $pedido = Pedido::first();
    expect($this->lot->fresh()->cantidad_disponible)->toBe(3)->and(CompraRepuesto::count())->toBe(1);
    $data['items'] = [];
    $this->put("/Pedido/$pedido->id", $data)->assertRedirect()->assertSessionHasNoErrors();
    expect(CompraRepuesto::count())->toBe(1)->and(app(ResumenComercial::class)->mes(now()->year, now()->month)['gastos_centavos'])->toBe(0);
    $this->get('/movimientos-stock?search=Batería')->assertInertia(fn ($page) => $page->has('data.data', 1)
        ->where('data.data.0.clase', 'Repuesto')->where('data.data.0.pedido_id', $pedido->id));
});

test('a quote does not count as a purchase until the part is purchased', function () {
    $data = newCommerceOrderData($this);
    $data['items'] = [['tipo' => 'repuesto', 'descripcion' => 'Batería', 'comprador_id' => $this->a->id, 'cantidad' => 1, 'costo' => '7000', 'porcentaje_ganancia' => 20, 'precio' => '8400', 'reparto' => $this->split]];
    $this->post('/Pedido', $data)->assertRedirect()->assertSessionHasNoErrors();
    expect(CompraRepuesto::count())->toBe(0);
    $this->get('/movimientos-stock?search=Batería')->assertInertia(fn ($page) => $page->has('data.data', 0));
    $item = Operacion::first()->items()->where('tipo', 'repuesto')->first();
    $this->post("/comercio/repuestos/$item->id/comprar", ['fecha_compra' => \App\Services\Comercio\FechaComercial::hoy()])->assertRedirect()->assertSessionHasNoErrors();
    $this->post("/comercio/repuestos/$item->id/comprar", ['fecha_compra' => \App\Services\Comercio\FechaComercial::hoy()])->assertRedirect()->assertSessionHasNoErrors();
    expect(CompraRepuesto::count())->toBe(1);
    $this->get('/movimientos-stock?search=Batería')->assertInertia(fn ($page) => $page->has('data.data', 1)
        ->where('data.data.0.clase', 'Repuesto')->where('data.data.0.cantidad', 1));
});

test('all commerce pages render for an authenticated user', function (string $path) {
    $this->get($path)->assertOk();
})->with(['/settings/repartos', '/productos', '/comercio/ventas', '/comercio/ventas/crear', '/movimientos-stock', '/Pedido/create']);

test('inventory entries distinguish opening balances from actual purchases', function () {
    $entry = ['comprador_id' => $this->a->id, 'cantidad' => 2, 'costo' => '9000', 'tipo' => 'inicial', 'fecha' => \App\Services\Comercio\FechaComercial::hoy()];
    $this->post("/comercio/articulos/{$this->article->id}/ingresos", $entry)->assertRedirect()->assertSessionHasNoErrors();
    expect(app(ResumenComercial::class)->mes(now()->year, now()->month)['gastos_centavos'])->toBe(0);
    $entry['tipo'] = 'compra';
    $this->post("/comercio/articulos/{$this->article->id}/ingresos", $entry)->assertRedirect()->assertSessionHasNoErrors();
    expect(app(ResumenComercial::class)->mes(now()->year, now()->month)['gastos_centavos'])->toBe(1800000);
});

test('stock cost and financier come from the chosen lot, not client input', function () {
    $this->lot->update(['comprador_id' => $this->b->id]);
    $this->sale['items'][0]['comprador_id'] = $this->a->id;
    $this->sale['items'][0]['costo'] = '1';
    $this->post('/comercio/ventas', $this->sale)->assertRedirect()->assertSessionHasNoErrors();
    $item = Operacion::first()->items->first();
    expect($item->comprador_id)->toBe($this->b->id)->and($item->costo_unitario_centavos)->toBe(900000)
        ->and($item->distribucion[1]['costo_centavos'])->toBe(900000);
});

test('editing a purchased part preserves its purchase and archived financier', function () {
    $data = newCommerceOrderData($this);
    $part = ['tipo' => 'repuesto', 'descripcion' => 'Batería', 'comprador_id' => $this->a->id, 'cantidad' => 1, 'costo' => '7000', 'porcentaje_ganancia' => 20, 'precio' => '8400', 'fecha_compra' => \App\Services\Comercio\FechaComercial::hoy(), 'reparto' => [['participante_id' => $this->b->id, 'porcentaje' => 100]]];
    $data['items'] = [$part];
    $this->post('/Pedido', $data)->assertRedirect()->assertSessionHasNoErrors();
    $pedido = Pedido::first();
    $this->a->update(['activo' => false]);
    $part['compra_id'] = CompraRepuesto::first()->id;
    $part['porcentaje_ganancia'] = 25;
    $data['items'] = [$part];
    $data['reparto_mano_obra'] = $part['reparto'];
    $this->put("/Pedido/$pedido->id", $data)->assertRedirect()->assertSessionHasNoErrors();
    expect(CompraRepuesto::count())->toBe(1);
    $data['items'][0]['costo'] = '6000';
    $this->put("/Pedido/$pedido->id", $data)->assertSessionHasErrors('items.0.costo');
    expect(CompraRepuesto::first()->costo_unitario_centavos)->toBe(700000);
});

test('guests cannot access commercial operations', function () {
    auth()->logout();
    $this->get('/comercio/configuracion')->assertRedirect('/login');
    $this->post('/comercio/ventas', $this->sale)->assertRedirect('/login');
    expect(Operacion::count())->toBe(0);
});

test('amounts exceeding the existing order schema are rejected without partial records', function () {
    $data = newCommerceOrderData($this);
    $data['costo_mano_obra'] = '100000000';
    $this->post('/Pedido', $data)->assertSessionHasErrors('items');
    expect(Pedido::count())->toBe(0)->and(Operacion::count())->toBe(0)->and($this->lot->fresh()->cantidad_disponible)->toBe(3);
});
