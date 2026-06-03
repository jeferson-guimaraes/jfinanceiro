export const formatBRL = (value: string | number): string => {
    let amount: number;
    
    if (typeof value === 'number') {
        amount = value;
    } else {
        const numericValue = value.replace(/\D/g, '');
        amount = numericValue ? parseFloat(numericValue) / 100 : 0;
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 2,
    }).format(amount);
};

export const unformatBRL = (value: string): number => {
    if (!value) return 0;
    const numericValue = value.replace(/\D/g, '');
    if (!numericValue) return 0;
    return parseFloat(numericValue) / 100;
};

export function handleValorKeydown(event: KeyboardEvent) {
    const target = event.target as HTMLInputElement;
    if (target.value == 'R$ 0,00' && (event.key === 'Backspace' || event.key === 'Delete')) {
        event.preventDefault();
    }
}