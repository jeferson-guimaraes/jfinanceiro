<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Violação de regra de domínio em movimentações ou parcelas (ex.: pagar sem parcela pendente).
 *
 * A mensagem é amigável e pode ser exibida ao usuário. Os controllers traduzem a exceção para a resposta
 * adequada (erro de validação no web, 409 na API do assistente).
 */
class MovimentacaoInvalidaException extends RuntimeException {}
