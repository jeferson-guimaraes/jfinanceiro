# Anti-patterns

## ❌ NÃO FAZER

- Criar lógica de parcelas fora do fluxo padrão
- Tratar movimentação como se fosse parcela
- Ignorar o campo `pago` nas parcelas
- Calcular saldo sem considerar regras de domínio
- Misturar filtros de data com mês/ano

---

## ❌ Erros comuns

- Considerar GASTO_FUTURO como pago automaticamente
- Somar movimentações em vez de parcelas para cálculos futuros
- Criar múltiplas fontes de verdade

---

## ❌ Frontend

- Fazer requisições antes de dados válidos
- Validar datas apenas com `Date`
- Duplicar regras de backend

---

## ✔ Sempre fazer

- Usar Services no backend
- Validar domínio antes de processar
- Respeitar separação:
  - Movimentação = transação
  - Parcela = execução financeira

## ❌ Categorias

- Permitir movimentação sem categoria
- Hardcode de categorias no frontend
- Ignorar categoria em listagens