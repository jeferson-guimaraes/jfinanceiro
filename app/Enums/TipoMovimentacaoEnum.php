<?php

namespace App\Enums;

enum TipoMovimentacaoEnum: string
{
    case GANHO = 'ganho';
    case GASTO = 'gasto';
    case GASTO_FUTURO = 'gasto futuro';
}