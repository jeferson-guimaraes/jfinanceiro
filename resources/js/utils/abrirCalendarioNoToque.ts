/**
 * Em telas de toque, abre o calendário nativo ao tocar em qualquer parte de um campo de data.
 *
 * Alguns navegadores móveis só abrem o calendário pelo ícone do campo, e tocar no dia, mês ou
 * ano apenas foca aquele trecho sem abrir o teclado. Em telas com mouse nada muda, para que
 * continue sendo possível digitar a data.
 */
export function abrirCalendarioNoToque(event: MouseEvent): void {
  const campo = event.currentTarget;

  if (!(campo instanceof HTMLInputElement) || typeof campo.showPicker !== 'function') {
    return;
  }

  if (!window.matchMedia('(pointer: coarse)').matches) {
    return;
  }

  try {
    campo.showPicker();
  } catch {
    // O navegador recusou abrir o calendário; o campo continua editável como antes.
  }
}
