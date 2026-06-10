import type { Movimentacao } from '@/types';

/**
 * Verifica se um conjunto de movimentações pode ser pago em massa.
 * Regras:
 * 1. Deve haver pelo menos uma movimentação selecionada.
 * 2. Todas as movimentações selecionadas devem ser do tipo 'gasto futuro'.
 * 3. Todas as movimentações selecionadas devem ter parcelas pendentes (pagas < total).
 */
export const canPayMovimentacoesInBulk = (movimentacoes: Movimentacao[]): boolean => {
  if (movimentacoes.length === 0) return false;
  
  return movimentacoes.every(
    movimentacao => movimentacao.tipo === 'gasto futuro' && (movimentacao.parcelas_pagas || 0) < movimentacao.parcelas
  );
};
