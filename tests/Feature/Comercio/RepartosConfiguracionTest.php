<?php

use App\Models\ConfiguracionComercial;
use App\Models\Participante;
use App\Models\User;
use App\Services\Comercio\Repartos;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::create(['name' => 'Operador', 'email' => 'config@example.test', 'password' => bcrypt('test-password')]));
    $this->a = app(Repartos::class)->titular();
    $this->b = Participante::create(['nombre' => 'Hermana']);
    $this->config = ['reparto_mercaderia' => [['participante_id' => $this->a->id, 'porcentaje' => 50], ['participante_id' => $this->b->id, 'porcentaje' => 50]]];
});

test('only products are shared while parts and labor belong entirely to the fixed owner', function () {
    $this->put('/settings/repartos', [...$this->config, 'titular_id' => $this->b->id, 'reparto_repuestos' => [['participante_id' => $this->b->id, 'porcentaje' => 100]]])->assertSessionHasNoErrors();
    $config = ConfiguracionComercial::find(1);
    expect($config->titular_id)->toBe($this->a->id)->and($config->hermana_id)->toBe($this->b->id);
    foreach (['reparto_repuestos', 'reparto_mano_obra'] as $field) {
        expect($config->$field)->toHaveCount(1)->and($config->$field[0]['participante_id'])->toBe($this->a->id)->and($config->$field[0]['porcentaje'])->toBe(100);
    }
    foreach (['/settings/repartos', '/Pedido/create', '/comercio/ventas/crear'] as $path) {
        $this->get($path)->assertOk()->assertInertia(fn (Assert $page) => $page->has('repartoMercaderia', 2)->where('titular.id', $this->a->id)->where('hermanaId', $this->b->id));
    }
});

test('invalid distribution does not partially overwrite settings', function () {
    $this->put('/settings/repartos', $this->config)->assertSessionHasNoErrors();
    $before = ConfiguracionComercial::find(1)->toArray();
    $this->config['reparto_mercaderia'][0]['porcentaje'] = 75;
    $this->put('/settings/repartos', $this->config)->assertSessionHasErrors('reparto_mercaderia');
    expect(ConfiguracionComercial::find(1)->toArray())->toBe($before);
});

test('the principal participant cannot be deactivated but can be renamed', function () {
    $this->put('/comercio/participantes/'.$this->a->id, ['nombre' => 'Titular renombrado', 'activo' => false])->assertSessionHasErrors('activo');
    expect($this->a->fresh()->activo)->toBeTrue();
    $this->put('/comercio/participantes/'.$this->a->id, ['nombre' => 'Titular renombrado', 'activo' => true])->assertSessionHasNoErrors();
    expect(app(Repartos::class)->titular()->id)->toBe($this->a->id)->and($this->a->fresh()->nombre)->toBe('Titular renombrado');
});
