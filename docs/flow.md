# Fluxos do Sistema

## Criar movimentação

1. Usuário preenche:
   - tipo
   - data
   - categoria
   - descrição
   - valor

2. Se GASTO_FUTURO:
   - informa número de parcelas
   - frontend calcula parcelas

3. Backend:
   - valida via FormRequest
   - cria movimentação
   - cria parcelas (se necessário)

---

## Pagamento de parcelas

### Individual

1. Usuário seleciona movimentação
2. Escolhe quantidade de parcelas a pagar
3. Backend:
   - marca parcelas como pagas
   - cria movimentação do tipo GASTO

---

### Múltiplas movimentações

1. Usuário seleciona várias movimentações
2. Escolhe quantidade de parcelas
   - limitado pela menor quantidade disponível

3. Backend:
   - paga parcelas
   - cria movimentações de gasto

---

## Listagem

Abas:
- Todos
- Gastos
- Ganhos
- Futuros

Filtros:
- Movimentações → data range
- Futuros → mês/ano