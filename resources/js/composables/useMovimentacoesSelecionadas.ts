import { computed, ref, watch, type Ref } from 'vue';
import type { Movimentacao, ParcelaComMovimentacao } from '@/types';
import { canPayMovimentacoesInBulk } from '@/utils/movimentacoes';

interface ItemSelecionado {
  movimentacao: Movimentacao;
  valor: number;
}

interface Options {
  selectedMovimentacoes: Ref<number[]>;
  movimentacoes: Ref<Movimentacao[]>;
  parcelas: Ref<ParcelaComMovimentacao[]>;
  activeTab: Ref<string>;
}

/**
 * Mantém um cache dos itens selecionados (movimentação e valor) enquanto eles estão visíveis
 * na lista, para que total e ações em massa não mudem quando a busca esconde temporariamente
 * as linhas selecionadas.
 */
export function useMovimentacoesSelecionadas({ selectedMovimentacoes, movimentacoes, parcelas, activeTab }: Options) {
  const itensSelecionados = ref(new Map<number, ItemSelecionado>());

  const coletaItensVisiveis = (): Map<number, ItemSelecionado> => {
    const visiveis = new Map<number, ItemSelecionado>();

    if (activeTab.value === 'gasto futuro') {
      parcelas.value.forEach(parcela => {
        const atual = visiveis.get(parcela.movimentacao.id);
        visiveis.set(parcela.movimentacao.id, {
          movimentacao: parcela.movimentacao,
          valor: (atual?.valor ?? 0) + Number(parcela.valor),
        });
      });
    } else {
      movimentacoes.value.forEach(movimentacao => {
        visiveis.set(movimentacao.id, { movimentacao, valor: Number(movimentacao.valor) });
      });
    }

    return visiveis;
  };

  watch(
    [selectedMovimentacoes, movimentacoes, parcelas, activeTab],
    () => {
      const visiveis = coletaItensVisiveis();
      const atualizado = new Map<number, ItemSelecionado>();

      selectedMovimentacoes.value.forEach(id => {
        const item = visiveis.get(id) ?? itensSelecionados.value.get(id);
        if (item) {
          atualizado.set(id, item);
        }
      });

      itensSelecionados.value = atualizado;
    },
    { immediate: true, deep: true },
  );

  const totalSelecionado = computed(() => {
    let total = 0;
    itensSelecionados.value.forEach(item => {
      total += item.valor;
    });
    return total;
  });

  const canPaySelected = computed(() => {
    if (selectedMovimentacoes.value.length === 0) return false;

    return canPayMovimentacoesInBulk(Array.from(itensSelecionados.value.values()).map(item => item.movimentacao));
  });

  return { totalSelecionado, canPaySelected };
}
