<?php

namespace Database\Seeders;

use App\Http\Controllers\PedidoComercioController;
use App\Http\Requests\Comercio\PedidoRequest;
use App\Models\Cliente;
use App\Models\ConfiguracionComercial;
use App\Models\Operacion;
use App\Models\Participante;
use App\Models\Pedido;
use App\Models\ProductoNuevo;
use App\Models\User;
use App\Services\Comercio\Operaciones;
use App\Services\Comercio\Repartos;
use App\Services\Comercio\ResumenComercial;
use App\Services\Comercio\StockProductos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/** Explicit, repeatable demo data. Never called by DatabaseSeeder. */
class DemoSeptiembre2026Seeder extends Seeder
{
    private array $products = [];

    private array $orders = [];

    private array $sales = [];

    private Participante $owner;

    private Participante $partner;

    private Operaciones $operations;

    public function run(): void
    {
        if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'scomputacion_demo') {
            throw new RuntimeException('Este seeder sólo puede ejecutarse en la base local scomputacion_demo.');
        }
        try {
            $report = DB::transaction(function () {
                DB::select('SELECT pg_advisory_xact_lock(2026092901)');
                if (Cliente::withTrashed()->where('mail', 'demo-septiembre-2026-ana@example.test')->exists()) {
                    $this->command?->info('Los casos de septiembre ya existen; no se duplicaron ni se modificaron.');

                    return null;
                }
                $actor = User::where('email', 'demo@example.test')->firstOrFail();
                Auth::login($actor);
                $this->operations = app(Operaciones::class);
                $this->owner = app(Repartos::class)->titular();
                $split = ConfiguracionComercial::findOrFail(1)->reparto_mercaderia;
                $other = collect($split)->firstWhere('participante_id', '!=', $this->owner->id);
                if (count($split) !== 2 || ! $other || collect($split)->contains(fn ($r) => (float) $r['porcentaje'] !== 50.0)) {
                    throw new RuntimeException('Estos ejemplos verifican el reparto 50/50 existente. No se modificó la configuración.');
                }
                $this->partner = Participante::where('activo', true)->findOrFail($other['participante_id']);
                $before = app(ResumenComercial::class)->mes(2026, 9);
                $this->at(1);
                foreach (['funda' => 'Funda notebook 15,6', 'mouse' => 'Mouse inalámbrico', 'ssd' => 'SSD 480 GB', 'ram' => 'Memoria RAM 8 GB', 'teclado' => 'Teclado USB'] as $key => $name) {
                    $this->products[$key] = ProductoNuevo::create(['nombre' => 'DEMO Septiembre · '.$name, 'marca' => 'Prueba septiembre', 'precio_venta_centavos' => 0]);
                }
                foreach ([
                    ['funda', $this->owner, 10, 9000, 40, 1],
                    ['funda', $this->partner, 8, 9000, 40, 2],
                    ['mouse', $this->owner, 6, 5000, 50, 3],
                    ['mouse', $this->partner, 6, 5000, 50, 4],
                    ['ssd', $this->owner, 8, 20000, 30, 3],
                    ['ram', $this->partner, 6, 12000, 50, 4],
                    ['teclado', $this->owner, 6, 8000, 40, 2],
                ] as [$product, $buyer, $quantity, $cost, $markup, $day]) {
                    $this->at($day);
                    app(StockProductos::class)->ingresar($this->products[$product], [
                        'clave' => $this->key('ingreso-'.$product.'-'.$buyer->id), 'comprador_id' => $buyer->id,
                        'cantidad' => $quantity, 'costo' => $cost, 'porcentaje_ganancia' => $markup,
                        'tipo' => 'compra', 'fecha' => now()->toDateString(), 'proveedor' => 'DEMO Septiembre · Distribuidora',
                    ]);
                }

                $ana = $this->order('ana', 'Ana', 'Notebook Lenovo', 'Productos pagados por el titular + dos repuestos + venta adicional', 5, 15000, [
                    $this->stock('funda', $this->owner), $this->part('Batería notebook', 25000, 20, 5), $this->part('Pasta térmica', 5000, 30, 5),
                ]);
                $this->advance($ana, [3 => 6, 4 => 8]);
                $this->sale('adicional-ana', 9, [$this->stock('mouse', $this->partner)], $ana);
                $this->advance($ana, [5 => 10]);

                $bruno = $this->order('bruno', 'Bruno', 'Notebook HP', 'Dos fundas pagadas por el otro participante + repuesto', 7, 12000, [
                    $this->stock('funda', $this->partner, 2), $this->part('Cargador notebook', 8000, 25, 7),
                ]);
                $this->advance($bruno, [3 => 8, 4 => 10, 5 => 12]);

                $carla = $this->order('carla', 'Carla', 'PC de escritorio', 'Misma funda con dos compradores distintos + RAM', 9, 8000, [
                    $this->stock('funda', $this->owner), $this->stock('funda', $this->partner), $this->stock('ram', $this->partner),
                ]);
                $this->advance($carla, [3 => 10, 4 => 12, 5 => 14]);

                $diego = $this->order('diego', 'Diego', 'Notebook Asus', 'Sólo repuestos y mano de obra: todo corresponde al titular', 11, 18000, [
                    $this->part('Pantalla notebook', 40000, 25, 11), $this->part('Flex de pantalla', 4000, 50, 11),
                ]);
                $this->advance($diego, [3 => 12, 4 => 14, 5 => 16]);

                $elena = $this->order('elena', 'Elena', 'PC de oficina', 'Sólo mano de obra: sin stock ni reparto de productos', 15, 22000, []);
                $this->advance($elena, [3 => 16, 4 => 17, 5 => 18]);

                $felipe = $this->order('felipe', 'Felipe', 'Notebook Dell', 'Finalizado sin entregar: compra registrada, cobro pendiente', 18, 14000, [
                    $this->stock('ssd', $this->owner), $this->part('Teclado interno notebook', 15000, 20, 18),
                ]);
                $this->advance($felipe, [3 => 19, 4 => 21]);

                $this->order('gabriela', 'Gabriela', 'Notebook Acer', 'Pendiente de aprobación: repuesto todavía sin comprar', 22, 10000, [
                    $this->stock('funda', $this->partner), $this->part('Batería Acer', 18000, 25),
                ]);

                $hugo = $this->order('hugo', 'Hugo', 'Notebook Samsung', 'Reparación en proceso y venta adicional ya cobrada', 23, 10000, [
                    $this->stock('mouse', $this->partner), $this->part('Ventilador notebook', 6000, 50, 23),
                ]);
                $this->advance($hugo, [3 => 24]);
                $this->sale('adicional-hugo', 25, [$this->stock('teclado', $this->owner)], $hugo);

                $this->order('iris', 'Iris', 'Notebook para diagnóstico', 'Ingreso sin presupuesto', 26, 0, [], false);
                $this->sale('mostrador-titular', 19, [$this->stock('ssd', $this->owner), $this->stock('funda', $this->owner)]);
                $this->sale('mostrador-participante', 20, [$this->stock('ram', $this->partner, 2), $this->stock('mouse', $this->partner)]);
                $cancelled = $this->sale('anulada', 21, [$this->stock('teclado', $this->owner)]);
                $this->at(22);
                $this->operations->devolver($cancelled, true);

                $after = app(ResumenComercial::class)->mes(2026, 9);
                foreach (['cobros_centavos' => 17740000, 'gastos_centavos' => 32800000, 'ganancia_centavos' => 12240000, 'cobros_totales_centavos' => 35930000] as $field => $expected) {
                    if ($expected !== $after[$field] - $before[$field]) {
                        throw new RuntimeException('La verificación de '.$field.' no coincide. Se revierte la carga completa.');
                    }
                }
                $beforeOther = collect($before['distribucion'])->firstWhere('participante_id', $this->partner->id)['total_centavos'] ?? 0;
                $afterOther = collect($after['distribucion'])->firstWhere('participante_id', $this->partner->id)['total_centavos'] ?? 0;
                if ($afterOther - $beforeOther !== 9990000) {
                    throw new RuntimeException('El reparto del segundo participante no coincide.');
                }
                $remaining = [];
                foreach (['funda' => [7, 5], 'mouse' => [6, 3], 'ssd' => [6, 0], 'ram' => [0, 3], 'teclado' => [5, 0]] as $key => [$ownerQty, $partnerQty]) {
                    $lots = $this->products[$key]->lotes()->get();
                    if ((int) $lots->where('comprador_id', $this->owner->id)->sum('cantidad_disponible') !== $ownerQty || (int) $lots->where('comprador_id', $this->partner->id)->sum('cantidad_disponible') !== $partnerQty) {
                        throw new RuntimeException('El stock de '.$key.' no coincide.');
                    }
                    $remaining[$key] = ['producto_id' => $this->products[$key]->id, 'titular' => $ownerQty, 'participante' => $partnerQty];
                }

                return ['mes' => '2026-09', 'titular' => $this->owner->only(['id', 'nombre']), 'participante' => $this->partner->only(['id', 'nombre']),
                    'pedidos' => $this->orders, 'ventas' => $this->sales, 'stock_restante' => $remaining,
                    'antes' => $this->summary($before), 'despues' => $this->summary($after)];
            });
            if ($report !== null) {
                file_put_contents(storage_path('app/demo-septiembre-2026.json'), json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->command?->info('Cargados 9 pedidos, 5 ventas (1 anulada), 5 productos y 7 ingresos. Importes y saldos verificados.');
            }
        } finally {
            Date::setTestNow();
            Auth::forgetGuards();
        }
    }

    private function at(int $day): void
    {
        Date::setTestNow(Carbon::create(2026, 9, $day, 15, 0, 0, config('app.timezone')));
    }

    private function key(string $name): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'scomputacion-demo-septiembre-2026/'.$name)->toString();
    }

    private function stock(string $key, Participante $buyer, int $quantity = 1): array
    {
        return ['grupo' => (string) Uuid::uuid4(), 'tipo' => 'stock', 'producto_id' => $this->products[$key]->id, 'comprador_id' => $buyer->id, 'cantidad' => $quantity];
    }

    private function part(string $name, int $cost, int $markup, ?int $day = null): array
    {
        return ['tipo' => 'repuesto', 'descripcion' => $name, 'cantidad' => 1, 'costo' => $cost, 'porcentaje_ganancia' => $markup,
            'proveedor' => 'DEMO Septiembre · Repuestos', 'fecha_compra' => $day ? sprintf('2026-09-%02d', $day) : null];
    }

    private function order(string $key, string $name, string $equipment, string $scenario, int $day, int $labor, array $items, bool $budget = true): Pedido
    {
        $this->at($day);
        $customer = Cliente::create(['nombre' => $name, 'apellido' => 'DEMO Septiembre', 'mail' => 'demo-septiembre-2026-'.$key.'@example.test']);
        $data = ['cliente_id' => $customer->id, 'equipo' => $equipment, 'estado_ingreso' => '[DEMO Septiembre] '.$scenario, 'cargador' => true,
            'trabajo_realizar' => $budget ? $scenario : null, 'costo_mano_obra' => $labor, 'items' => $items];
        $request = new PedidoRequest($data);
        $request->setValidator(Validator::make($data, $request->rules()));
        app(PedidoComercioController::class)->store($request, $this->operations);
        $order = Pedido::where('cliente_id', $customer->id)->firstOrFail();
        $this->orders[$key] = ['id' => $order->id, 'codigo' => $order->codigo, 'nombre' => $name, 'caso' => $scenario];

        return $order;
    }

    private function advance(Pedido $order, array $days): void
    {
        foreach ($days as $state => $day) {
            $this->at($day);
            $data = ['fecha_cobro' => now()->toDateString(), 'medio_pago' => 'Efectivo',
                'total_esperado' => $order->operaciones()->where('estado', '!=', 'anulada')->whereNull('fecha_cobro')->sum('total_centavos')];
            app(PedidoComercioController::class)->actualizarEstado(
                \Illuminate\Http\Request::create('/', 'POST', $data), $order->id, $state, $this->operations, app(\App\Services\Comercio\Cobros::class)
            );
        }
    }

    private function sale(string $key, int $day, array $items, ?Pedido $order = null): Operacion
    {
        $this->at($day);
        $sale = $this->operations->vender(['clave' => $this->key('venta-'.$key), 'pedido_id' => $order?->id, 'fecha' => now()->toDateString(),
            'medio_pago' => $day % 2 ? 'Efectivo' : 'Transferencia', 'items' => $items]);
        app(\App\Services\Comercio\Cobros::class)->venta($sale, ['fecha_cobro' => now()->toDateString(),
            'medio_pago' => $day % 2 ? 'Efectivo' : 'Transferencia', 'total_esperado' => $sale->total_centavos]);
        $sale->refresh();
        $this->sales[$key] = ['id' => $sale->id, 'pedido_id' => $order?->id];

        return $sale;
    }

    private function summary(array $summary): array
    {
        return collect($summary)->only(['cobros_centavos', 'gastos_centavos', 'ganancia_centavos', 'costo_recuperado_centavos', 'cobros_totales_centavos', 'distribucion', 'ventas_por_participante'])->all();
    }
}
