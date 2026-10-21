interface GrecaptchaV3 {
  ready(callback: () => void): void;
  execute(siteKey: string, options: { action: string }): Promise<string>;
}

declare global {
  interface Window {
    grecaptcha?: GrecaptchaV3;
  }
}

const TEMPO_LIMITE_MS = 10000;

let carregamento: Promise<GrecaptchaV3> | null = null;

/**
 * Carrega o script do reCAPTCHA v3 uma única vez por página e resolve quando ele está pronto.
 */
function carregarScript(siteKey: string): Promise<GrecaptchaV3> {
  if (carregamento) {
    return carregamento;
  }

  carregamento = new Promise<GrecaptchaV3>((resolve, reject) => {
    const script = document.createElement('script');

    /**
     * Remove a tag do script ao falhar, para que uma nova tentativa não carregue o reCAPTCHA
     * duas vezes caso o script antigo termine de carregar depois.
     */
    const falhar = (mensagem: string) => {
      window.clearTimeout(limite);
      script.remove();
      reject(new Error(mensagem));
    };

    const limite = window.setTimeout(() => falhar('Tempo esgotado ao carregar o reCAPTCHA.'), TEMPO_LIMITE_MS);

    script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}`;
    script.async = true;
    script.defer = true;
    script.onload = () => {
      window.grecaptcha?.ready(() => {
        window.clearTimeout(limite);
        resolve(window.grecaptcha as GrecaptchaV3);
      });
    };
    script.onerror = () => falhar('Não foi possível carregar o reCAPTCHA.');

    document.head.appendChild(script);
  }).catch((erro: unknown) => {
    carregamento = null;
    throw erro;
  });

  return carregamento;
}

/**
 * Protege um formulário com o reCAPTCHA v3 invisível.
 *
 * Sem site key (ambiente local), nenhum script é carregado e o token sai vazio; o backend só
 * aceita isso nos ambientes local e de testes.
 */
export function useRecaptcha(siteKey: string | null | undefined) {
  const habilitado = Boolean(siteKey);

  function preparar(): void {
    if (habilitado) {
      carregarScript(siteKey as string).catch(() => {
        // O erro reaparece em gerarToken, no momento do envio.
      });
    }
  }

  /**
   * Gera um token novo para a ação informada. Deve ser chamado no envio, porque o token
   * expira em dois minutos.
   */
  async function gerarToken(acao: string): Promise<string> {
    if (!habilitado) {
      return '';
    }

    const grecaptcha = await carregarScript(siteKey as string);

    return grecaptcha.execute(siteKey as string, { action: acao });
  }

  return { habilitado, preparar, gerarToken };
}
