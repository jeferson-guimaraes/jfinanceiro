
export const formatBRL = (value: string | number): string => {
    const stringValue = String(value);
    // 1. Remove tudo que não for dígito
    let numericValue = stringValue.replace(/\D/g, '');

    // If no numbers are left, treat it as 0
    if (!numericValue) {
        numericValue = '0';
    }

    // 2. Converte para número e divide por 100 para ter as casas decimais
    const amount = parseFloat(numericValue) / 100;

    // 3. Formata para o padrão BRL
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
