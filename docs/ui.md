# UI/UX — JFinanceiro

## Estratégia

- Mobile-first obrigatório
- Desktop é adaptação, não prioridade

---

## Exibição de dados

### Mobile (< md)

- Layout: Cards
- Cada movimentação deve ser um card
- Informações principais:
  - Valor
  - Categoria
  - Data
  - Tipo
  - Status (pago/pendente)

---

### Desktop (>= md)

- Layout: Tabela
- Colunas típicas:
  - Data
  - Categoria
  - Descrição
  - Tipo
  - Valor
  - Ações

---

## Ações importantes

- Pagar movimentação
- Selecionar múltiplas movimentações
- Filtrar por data
- Alternar abas (Todos, Gastos, Ganhos, Futuros)

---

## Feedback visual

- Valores:
  - Ganhos → positivo
  - Gastos → negativo

- Parcelas:
  - Pago → destaque visual (ex: verde)
  - Pendente → neutro ou alerta

---

## Responsividade

- Nunca esconder informação crítica
- Adaptar layout, não remover contexto