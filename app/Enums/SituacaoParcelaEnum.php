<?php

namespace App\Enums;

/**
 * Filtro de situação das parcelas na aba de movimentações futuras.
 */
enum SituacaoParcelaEnum: string
{
    case TODAS = 'todas';
    case PAGAS = 'pagas';
    case NAO_PAGAS = 'nao_pagas';

    /**
     * Interpreta o valor recebido na URL, caindo em "todas" quando vier vazio ou inválido.
     */
    public static function daRequisicao(mixed $valor): self
    {
        return is_string($valor) ? (self::tryFrom($valor) ?? self::TODAS) : self::TODAS;
    }
}
