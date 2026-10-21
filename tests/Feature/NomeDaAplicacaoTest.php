<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O título da aba é montado no navegador com a prop "name". Ela precisa refletir o APP_NAME
 * do ambiente em tempo de execução, porque o build do frontend é feito no CI, sem .env.
 */
class NomeDaAplicacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginas_recebem_o_nome_da_aplicacao_configurado_no_ambiente(): void
    {
        config(['app.name' => 'Nome Do Ambiente']);

        $this->get(route('home'))
            ->assertInertia(fn ($page) => $page->where('name', 'Nome Do Ambiente'));

        $this->get(route('avaliacoes.create'))
            ->assertInertia(fn ($page) => $page->where('name', 'Nome Do Ambiente'));
    }
}
