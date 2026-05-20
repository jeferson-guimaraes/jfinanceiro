# Domínio — JFinanceiro

## Movimentação

Uma movimentação representa uma transação financeira.

Tipos:
- GANHO
- GASTO
- GASTO_FUTURO

Regras:
- Sempre pertence a um usuário
- Sempre possui pelo menos 1 parcela
- Apenas GASTO_FUTURO pode ter múltiplas parcelas

---

## Parcela

Uma parcela representa uma fração de uma movimentação.

Regras:
- Pertence a uma movimentação
- Possui valor fixo
- Soma das parcelas = valor total da movimentação
- Não existem parcelas variáveis
- A data de vencimento da primeira parcela não pode ser anterior a data da compra

Campo importante:
- `pago` (boolean)

---

## Relação entre entidades

Movimentação (1) -> (N) Parcelas

---

## Regras importantes

- GANHO e GASTO:
  - Sempre possuem 1 parcela
  - Sempre são considerados pagos automaticamente

- GASTO_FUTURO:
  - Possui múltiplas parcelas
  - Controle de pagamento é feito por parcela

---

## Datas

- Movimentação → `data`
- Parcela → `data_vencimento`

---

## Cálculos financeiros

### Resta pagar

Resta pagar =

(soma de parcelas de GASTO_FUTURO)
-
(soma de parcelas pagas)

---

## Regra de exibição

- Movimentações:
  - Filtradas por data (range)
  - Default: mês atual

- Gastos futuros:
  - Filtrados por mês/ano

  ## Categoria

Toda movimentação deve possuir uma categoria.

Regras:
- Categoria é obrigatória
- Categoria padrão: "Outros"
- Usuário pode criar, editar e remover categorias

Uso:
- Organização de movimentações
- Base para filtros futuros