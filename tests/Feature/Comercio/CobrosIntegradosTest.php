<?php

use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\InventarioMovimiento;
use App\Models\LoteStock;
use App\Models\Operacion;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Comercio\Operaciones;
use App\Services\Comercio\Repartos;
use App\Services\Comercio\ResumenComercial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(now()->setDate(2026, 10, 2)->setTime(12, 0));
    $this->actingAs(User::create(['name' => 'Operador', 'email' => 'cobros@example.test', 'password' => bcrypt('password')]));
    foreach (['En revisión', 'Pendiente', 'En proceso', 'Finalizado', 'Entregado'] as $i => $name) {
        DB::table('estado')->insert(['id' => $i + 1, 'nombre' => $name]);
    }
    $this->owner = app(Repartos::class)->titular();
    $this->client = Cliente::create(['nombre' => 'Cliente', 'apellido' => 'Cobros']);
    $this->article = Articulo::create(['nombre' => 'Mouse', 'precio_venta_centavos' => 150000]);
    $this->lot = LoteStock::create(['articulo_id' => $this->article->id, 'comprador_id' => $this->owner->id, 'cantidad_inicial' => 10, 'cantidad_disponible' => 10, 'costo_unitario_centavos' => 100000, 'tipo' => 'compra', 'fecha' => '2026-09-29']);
    $this->item = ['tipo' => 'stock', 'producto_id' => $this->article->id, 'comprador_id' => $this->owner->id, 'cantidad' => 1, 'precio' => 1500];
    $this->orderData = ['cliente_id' => $this->client->id, 'equipo' => 'Notebook', 'estado_ingreso' => 'Lenta', 'cargador' => false, 'trabajo_realizar' => 'Instalación', 'costo_mano_obra' => 50000, 'items' => []];
});

afterEach(fn () => $this->travelBack());

test('linked products are a real unpaid sale and separate collection leaves only the remaining balance', function () {
    $this->orderData['items'] = [$this->item];
    $this->post('/Pedido', $this->orderData)->assertSessionHasNoErrors();
    $order = Pedido::firstOrFail();
    $sale = $order->operaciones()->where('tipo', 'venta')->firstOrFail();
    expect($sale->es_presupuesto)->toBeTrue()->and($sale->fecha_cobro)->toBeNull()
        ->and($order->operaciones()->where('tipo', 'reparacion')->first()->items()->where('tipo', 'stock')->exists())->toBeFalse()
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(10);
    $this->post('/comercio/operaciones/'.$sale->id.'/cobrar', ['fecha_cobro' => '2026-10-02', 'medio_pago' => 'Efectivo', 'total_esperado' => 150000])->assertSessionHasNoErrors();
    expect($this->lot->fresh()->cantidad_disponible)->toBe(9);
    foreach ([3, 4] as $state) {
        $this->post("/Pedido/$order->id/actualizarEstado/$state")->assertSessionHasNoErrors();
    }
    $this->post("/Pedido/$order->id/actualizarEstado/5", ['fecha_cobro' => '2026-10-02', 'medio_pago' => 'Transferencia', 'total_esperado' => 5150000])->assertSessionHasErrors('total_esperado');
    expect($order->fresh()->estadoActual_id)->toBe(4);
    $this->post("/Pedido/$order->id/actualizarEstado/5", ['fecha_cobro' => '2026-10-02', 'medio_pago' => 'Transferencia', 'total_esperado' => 5000000])->assertSessionHasNoErrors();
    expect($sale->fresh()->medio_pago)->toBe('Efectivo')->and(InventarioMovimiento::count())->toBe(1)
        ->and($order->operaciones()->whereNull('fecha_cobro')->count())->toBe(0);
    $this->post('/comercio/operaciones/'.$sale->id.'/cobrar', ['fecha_cobro' => '2026-10-02', 'medio_pago' => 'Otro', 'total_esperado' => 150000])->assertSessionHasNoErrors();
    expect($sale->fresh()->medio_pago)->toBe('Efectivo')->and(InventarioMovimiento::count())->toBe(1);
    $entry = \App\Models\PedidoEstado::where('pedido_id', $order->id)->latest('id')->first();
    $this->delete("/Pedido/$order->id/estados/$entry->id")->assertSessionHasNoErrors();
    $this->travelTo(now()->setDate(2026, 10, 3)->setTime(12, 0));
    $this->post("/Pedido/$order->id/actualizarEstado/5", ['fecha_cobro' => '2026-10-03', 'medio_pago' => 'Otro', 'total_esperado' => 0])->assertSessionHasNoErrors();
    expect($order->fresh()->fecha_pago)->toStartWith('2026-10-02')
        ->and($order->operaciones()->pluck('fecha_cobro')->unique()->all())->toBe(['2026-10-02'])
        ->and(InventarioMovimiento::count())->toBe(1);
});

test('standalone sales explicitly choose pending or paid and order modal sales always remain unpaid', function () {
    $payload = ['clave' => (string) Str::uuid(), 'fecha' => '2026-10-02', 'items' => [$this->item]];
    $this->post('/comercio/ventas', $payload)->assertSessionHasNoErrors();
    expect(Operacion::first()->fecha_cobro)->toBeNull()->and($this->lot->fresh()->cantidad_disponible)->toBe(9);
    $payload['clave'] = (string) Str::uuid();
    $this->post('/comercio/ventas', [...$payload, 'cobrar' => true, 'fecha_cobro' => '2026-10-02', 'medio_pago' => 'Efectivo'])->assertSessionHasNoErrors();
    expect(Operacion::latest('id')->first()->fecha_cobro)->toBe('2026-10-02');
    $this->post('/Pedido', $this->orderData)->assertSessionHasNoErrors();
    $payload['clave'] = (string) Str::uuid();
    $this->post('/comercio/ventas', [...$payload, 'pedido_id' => Pedido::first()->id, 'cobrar' => true, 'fecha_cobro' => '2026-10-02', 'medio_pago' => 'Efectivo'])->assertSessionHasNoErrors();
    expect(Operacion::latest('id')->first()->fecha_cobro)->toBeNull()->and($this->lot->fresh()->cantidad_disponible)->toBe(8);
});

test('september parts contribute only collected margin in october while inventory purchases stay expenses', function () {
    $this->travelTo(now()->setDate(2026, 9, 29)->setTime(12, 0));
    $this->orderData['items'] = [['tipo' => 'repuesto', 'descripcion' => 'SSD', 'cantidad' => 1, 'costo' => 100000, 'porcentaje_ganancia' => 30, 'fecha_compra' => '2026-09-29']];
    $this->post('/Pedido', $this->orderData)->assertSessionHasNoErrors();
    $order = Pedido::firstOrFail();
    $report = app(ResumenComercial::class)->mes(2026, 9);
    expect($report['cobros_centavos'])->toBe(0)->and($report['gastos_centavos'])->toBe(1000000);
    foreach ([3, 4] as $state) {
        $this->post("/Pedido/$order->id/actualizarEstado/$state")->assertSessionHasNoErrors();
    }
    $this->travelTo(now()->setDate(2026, 10, 2)->setTime(12, 0));
    $this->post("/Pedido/$order->id/actualizarEstado/5", ['fecha_cobro' => '2026-10-02', 'medio_pago' => 'Efectivo', 'total_esperado' => 18000000])->assertSessionHasNoErrors();
    $report = app(ResumenComercial::class)->mes(2026, 10);
    expect($report['cobros_centavos'])->toBe(8000000)->and($report['gastos_centavos'])->toBe(0)
        ->and($report['cobros_totales_centavos'])->toBe(18000000)->and($report['ganancia_centavos'])->toBe(8000000)
        ->and(InventarioMovimiento::count())->toBe(0)
        ->and(app(ResumenComercial::class)->mes(2026, 9)['cobros_centavos'])->toBe(0);
});

test('migration separates existing product snapshots without changing inventory payments or legacy orders', function () {
    $this->post('/Pedido', $this->orderData)->assertSessionHasNoErrors();
    $order = Pedido::firstOrFail();
    $repair = $order->operaciones()->firstOrFail();
    app(Operaciones::class)->guardarItems($repair, [$this->item, ['tipo' => 'mano_obra', 'cantidad' => 1, 'precio' => 50000]]);
    app(Operaciones::class)->confirmar($repair);
    $repair->update(['fecha_cobro' => '2026-10-02', 'medio_pago' => 'Efectivo']);
    $item = DB::table('operacion_items')->where('operacion_id', $repair->id)->where('tipo', 'stock')->first();
    $legacy = Pedido::create(['cliente_id' => $this->client->id, 'equipo' => 'Viejo', 'fecha_ingreso' => now(), 'cargador' => false, 'estado_ingreso' => 'Viejo', 'estadoActual_id' => 1, 'comercio_version' => 1, 'presupuesto' => 1234]);
    $legacyBefore = $legacy->fresh()->getRawOriginal();
    $migration = require database_path('migrations/2026_09_29_000001_separate_order_sales_and_payments.php');
    $migration->separateOrderSales();
    $sale = $order->operaciones()->where('tipo', 'venta')->firstOrFail();
    $after = (array) DB::table('operacion_items')->find($item->id);
    $after['operacion_id'] = $item->operacion_id;
    expect($after)->toBe((array) $item)
        ->and($sale->fecha_cobro)->toBe('2026-10-02')->and($sale->medio_pago)->toBe('Efectivo')
        ->and($sale->total_centavos + $repair->fresh()->total_centavos)->toBe(5150000)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(9)
        ->and(InventarioMovimiento::count())->toBe(1)
        ->and(InventarioMovimiento::first()->operacion_id)->toBe($sale->id)
        ->and($legacy->fresh()->getRawOriginal())->toBe($legacyBefore);
});
