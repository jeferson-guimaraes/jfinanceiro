const NOME_PADRAO = 'JFinanceiro';

/**
 * Nome da aplicação vindo do servidor (config('app.name'), compartilhado pelo
 * HandleInertiaRequests). É lido em tempo de execução, e não no build, para que o
 * APP_NAME do .env de cada ambiente seja respeitado mesmo com o build feito no CI.
 */
export function nomeDaAplicacao(props: { name?: unknown } | undefined): string {
    const nome = props?.name;

    return typeof nome === 'string' && nome.trim() !== '' ? nome : NOME_PADRAO;
}

/**
 * Título da aba no formato "<Página> - <Aplicação>", ou só o nome da aplicação.
 */
export function montarTitulo(titulo: string, nomeAplicacao: string): string {
    return titulo ? `${titulo} - ${nomeAplicacao}` : nomeAplicacao;
}
