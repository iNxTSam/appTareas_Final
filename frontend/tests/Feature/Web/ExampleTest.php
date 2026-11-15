<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_pagina_inicial_muestra_la_landing(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('App Tareas');
    }
}
