<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_pagina_inicial_redirige_al_login(): void
    {
        $this->withoutVite();

        $this->get('/')->assertRedirect(route('login'));
    }
}
