<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class PasswordRecoveryInterfaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_la_pantalla_de_recuperacion_muestra_el_formulario_y_el_estado_del_servicio(): void
    {
        $this->get(route('password.recovery'))
            ->assertOk()
            ->assertSee('Recuperar cuenta')
            ->assertSee('name="email"', false)
            ->assertSee('Enviar instrucciones')
            ->assertSee('El envío de instrucciones estará disponible cuando se conecte el servicio de recuperación.');
    }

    public function test_el_login_enlaza_a_la_pantalla_de_recuperacion(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.recovery'));
    }
}
