<?php

use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\InventarioMovimiento;
use App\Models\LoteStock;
use App\Models\Operacion;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Comercio\FechaComercial;
use App\Services\Comercio\Repartos;
use App\Services\Comercio\ResumenComercial;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
    config(['comercio.zona_horaria' => 'America/Argentina/Cordoba']);
    $this->travelTo(Carbon::parse('2026-10-04 00:16:48', 'UTC'));
    $this->actingAs(User::create(['name' => 'Operador', 'email' => 'fechas@example.test', 'password' => bcrypt('password')]));
    foreach (['Revisión', 'Pendiente', 'En proceso', 'Finalizado', 'Entregado'] as $i => $name) {
        DB::table('estado')->insert(['id' => $i + 1, 'nombre' => $name]);
    }
    $owner = app(Repartos::class)->titular();
    $client = Cliente::create(['nombre' => 'Prueba', 'apellido' => 'Fechas']);
    $product = Articulo::create(['nombre' => 'Mouse', 'precio_venta_centavos' => 150000]);
    $this->lot = LoteStock::create(['articulo_id' => $product->id, 'comprador_id' => $owner->id, 'cantidad_inicial' => 10, 'cantidad_disponible' => 10, 'costo_unitario_centavos' => 100000, 'tipo' => 'inicial', 'fecha' => '2026-09-01']);
    $this->item = ['tipo' => 'stock', 'producto_id' => $product->id, 'comprador_id' => $owner->id, 'cantidad' => 1];
    $this->data = ['cliente_id' => $client->id, 'equipo' => 'Notebook', 'estado_ingreso' => 'Prueba', 'cargador' => false, 'trabajo_realizar' => 'Instalación', 'costo_mano_obra' => 100,
        'items' => [$this->item, ['tipo' => 'repuesto', 'descripcion' => 'SSD', 'cantidad' => 1, 'costo' => 100, 'porcentaje_ganancia' => 30]]];
});

afterEach(fn () => $this->travelBack());

test('orders created after UTC midnight can be collected on the local business day', function ($instant, $localDay) {
    $this->travelTo(Carbon::parse($instant, 'UTC'));
    expect(FechaComercial::hoy())->toBe($localDay)->and(config('app.timezone'))->toBe('UTC');
    $this->post('/Pedido', $this->data)->assertSessionHasNoErrors();
    $order = Pedido::firstOrFail();
    expect($order->operaciones()->pluck('fecha')->unique()->all())->toBe([$localDay]);
    $this->get('/Pedido/'.$order->id)->assertInertia(fn ($page) => $page->where('zonaHorariaComercial', 'America/Argentina/Cordoba'));
    foreach ([3, 4] as $state) {
        $this->post("/Pedido/$order->id/actualizarEstado/$state")->assertSessionHasNoErrors();
    }
    $this->post("/Pedido/$order->id/actualizarEstado/5", ['fecha_cobro' => $localDay, 'medio_pago' => 'Efectivo', 'total_esperado' => 173000])->assertSessionHasNoErrors();
    expect($order->operaciones()->pluck('fecha_cobro')->unique()->all())->toBe([$localDay]);
    $date = Carbon::parse($localDay);
    expect(app(ResumenComercial::class)->mes($date->year, $date->month)['cobros_centavos'])->toBe(163000);
    $this->get('/rendimientos')->assertInertia(fn ($page) => $page->where('selectedYear', $date->year)->where('selectedMonth', $date->month));
})->with([
    ['2026-10-04 00:16:48', '2026-10-03'],
    ['2026-10-01 00:16:48', '2026-09-30'],
    ['2027-01-01 00:16:48', '2026-12-31'],
]);

test('sales and collections reject tomorrow even when it is already tomorrow in UTC', function () {
    $sale = ['clave' => (string) Str::uuid(), 'fecha' => '2026-10-04', 'items' => [$this->item]];
    $this->post('/comercio/ventas', $sale)->assertSessionHasErrors('fecha');
    expect(Operacion::count())->toBe(0);
    $sale['fecha'] = '2026-10-03';
    $this->post('/comercio/ventas', $sale)->assertSessionHasNoErrors();
    $op = Operacion::firstOrFail();
    $this->post("/comercio/operaciones/$op->id/cobrar", ['fecha_cobro' => '2026-10-04', 'medio_pago' => 'Efectivo', 'total_esperado' => 150000])->assertSessionHasErrors('fecha_cobro');
    expect($op->fresh()->fecha_cobro)->toBeNull();
    $this->post("/comercio/operaciones/$op->id/cobrar", ['fecha_cobro' => '2026-10-03', 'medio_pago' => 'Efectivo', 'total_esperado' => 150000])->assertSessionHasNoErrors();
});

test('date correction only repairs automatic unpaid dates without modifying snapshots or recorded collections', function () {
    $this->post('/Pedido', $this->data)->assertSessionHasNoErrors();
    $order = Pedido::firstOrFail();
    $order->operaciones()->update(['fecha' => '2026-10-04']);
    $repair = $order->operaciones()->where('tipo', 'reparacion')->firstOrFail();
    $paid = $repair->replicate();
    $paid->forceFill(['clave' => (string) Str::uuid(), 'fecha_cobro' => '2026-10-04', 'estado' => 'confirmada'])->save();
    $manual = $repair->replicate();
    $manual->forceFill(['clave' => (string) Str::uuid(), 'tipo' => 'venta', 'fecha' => '2026-10-02'])->save();
    $legacy = $order->replicate();
    $legacy->forceFill(['codigo' => null, 'comercio_version' => 1])->save();
    $oldOperation = $repair->replicate();
    $oldOperation->forceFill(['clave' => (string) Str::uuid(), 'pedido_id' => $legacy->id])->save();
    $unchanged = [$paid->id, $manual->id, $oldOperation->id];
    $originals = DB::table('operaciones')->whereIn('id', $unchanged)->get()->toJson();
    $items = DB::table('operacion_items')->get()->toJson();
    $migration = require database_path('migrations/2026_10_03_000001_correct_pending_order_business_dates.php');
    $migration->up();
    $migration->up();
    expect($order->operaciones()->whereNotIn('id', $unchanged)->pluck('fecha')->unique()->all())->toBe(['2026-10-03'])
        ->and(DB::table('operaciones')->whereIn('id', $unchanged)->get()->toJson())->toBe($originals)
        ->and(DB::table('operacion_items')->get()->toJson())->toBe($items)
        ->and($this->lot->fresh()->cantidad_disponible)->toBe(10)
        ->and(InventarioMovimiento::count())->toBe(0);
});
