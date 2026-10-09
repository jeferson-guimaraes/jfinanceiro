export interface FiltrosMovimentacoes {
  tipo: string;
  data_inicio: string;
  data_fim: string;
  mes: string;
  ano: string;
  busca: string;
  per_page: number | string;
}

export interface EstadoMovimentacoes {
  versao: 2;
  salvoEm: string;
  filtros: FiltrosMovimentacoes;
  selecionados: number[];
}

const PREFIXO_CHAVE = 'jfinanceiro:movimentacoes:';

const TIPOS_VALIDOS = ['todos', 'ganho', 'gasto', 'gasto futuro'];

const chaveDoUsuario = (userId: number) => `${PREFIXO_CHAVE}${userId}`;

const isTexto = (valor: unknown): valor is string => typeof valor === 'string';

const isFiltrosValidos = (filtros: any): filtros is FiltrosMovimentacoes =>
  filtros !== null &&
  typeof filtros === 'object' &&
  TIPOS_VALIDOS.includes(filtros.tipo) &&
  ['data_inicio', 'data_fim', 'mes', 'ano', 'busca'].every(campo => isTexto(filtros[campo])) &&
  (typeof filtros.per_page === 'number' || isTexto(filtros.per_page));

/**
 * Data local no formato YYYY-MM-DD, usada para expirar o estado na virada do dia.
 */
const hoje = (): string => {
  const agora = new Date();
  const mes = String(agora.getMonth() + 1).padStart(2, '0');
  const dia = String(agora.getDate()).padStart(2, '0');

  return `${agora.getFullYear()}-${mes}-${dia}`;
};

/**
 * Lê o estado da tela de movimentações guardado na sessão do navegador.
 * Retorna null quando não há estado, quando ele é de outro dia, quando está corrompido ou
 * quando o armazenamento não está disponível (SSR, navegação privada com storage bloqueado).
 */
export function lerEstadoMovimentacoes(userId: number): EstadoMovimentacoes | null {
  if (typeof window === 'undefined') {
    return null;
  }

  try {
    const bruto = window.sessionStorage.getItem(chaveDoUsuario(userId));
    if (!bruto) {
      return null;
    }

    const estado = JSON.parse(bruto);
    if (
      estado?.versao !== 2 ||
      estado.salvoEm !== hoje() ||
      !isFiltrosValidos(estado.filtros) ||
      !Array.isArray(estado.selecionados)
    ) {
      return null;
    }

    return {
      versao: 2,
      salvoEm: estado.salvoEm,
      filtros: estado.filtros,
      selecionados: estado.selecionados.filter((id: unknown) => Number.isInteger(id)),
    };
  } catch {
    return null;
  }
}

/**
 * Grava o estado da tela de movimentações na sessão do navegador. A sessão dura enquanto a
 * aba existir, e o navegador pode restaurá-la ao reabrir abas, por isso o estado carrega a
 * data em que foi salvo e é descartado na leitura a partir do dia seguinte. Da seleção, só
 * os IDs são guardados: os dados das movimentações sempre vêm do servidor.
 */
export function salvarEstadoMovimentacoes(
  userId: number,
  estado: Omit<EstadoMovimentacoes, 'versao' | 'salvoEm'>,
): void {
  if (typeof window === 'undefined') {
    return;
  }

  try {
    const completo: EstadoMovimentacoes = { versao: 2, salvoEm: hoje(), ...estado };
    window.sessionStorage.setItem(chaveDoUsuario(userId), JSON.stringify(completo));
  } catch {
    // Storage cheio ou bloqueado: a tela continua funcionando, só não restaura ao voltar.
  }
}

/**
 * Remove o estado salvo de todos os usuários desta aba. Chamado no logout.
 */
export function limparEstadosMovimentacoes(): void {
  if (typeof window === 'undefined') {
    return;
  }

  try {
    Object.keys(window.sessionStorage)
      .filter(chave => chave.startsWith(PREFIXO_CHAVE))
      .forEach(chave => window.sessionStorage.removeItem(chave));
  } catch {
    // Storage bloqueado: não há nada a limpar.
  }
}

/**
 * Completa período e mês/ano vazios com o mês atual. É o mesmo padrão que o backend aplica
 * quando esses filtros não são enviados. Se um dos campos de um par estiver vazio, os dois
 * são preenchidos, para nunca formar um período inválido.
 */
export function comPeriodoPadrao(filtros: FiltrosMovimentacoes): FiltrosMovimentacoes {
  const agora = new Date();
  const completo = { ...filtros };

  if (!completo.data_inicio || !completo.data_fim) {
    completo.data_inicio = new Date(agora.getFullYear(), agora.getMonth(), 1).toISOString().split('T')[0];
    completo.data_fim = new Date(agora.getFullYear(), agora.getMonth() + 1, 0).toISOString().split('T')[0];
  }

  if (!completo.mes || !completo.ano) {
    completo.mes = String(agora.getMonth() + 1);
    completo.ano = String(agora.getFullYear());
  }

  return completo;
}

/**
 * Indica se dois conjuntos de filtros mostram o mesmo recorte de dados (mesma aba e mesmo
 * período). Períodos vazios equivalem ao mês atual, como no backend. Busca e itens por página
 * não mudam o recorte, então não invalidam a seleção.
 */
export function isMesmoRecorte(a: FiltrosMovimentacoes, b: FiltrosMovimentacoes): boolean {
  const filtrosA = comPeriodoPadrao(a);
  const filtrosB = comPeriodoPadrao(b);

  if (filtrosA.tipo !== filtrosB.tipo) {
    return false;
  }

  if (filtrosA.tipo === 'gasto futuro') {
    return filtrosA.mes === filtrosB.mes && filtrosA.ano === filtrosB.ano;
  }

  return filtrosA.data_inicio === filtrosB.data_inicio && filtrosA.data_fim === filtrosB.data_fim;
}
