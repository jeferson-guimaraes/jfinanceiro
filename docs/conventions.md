# Convenções

## Backend

- Controllers não devem conter regras de negócio
- Sempre usar Services
- Sempre validar com FormRequest

---

## Frontend

- Usar utils para:
  - moeda
  - data
  - validação

- Não duplicar lógica do backend

---

## Datas

- Formato padrão: YYYY-MM-DD
- Validar ano realista (>= 2000)

---

## Valores

- Sempre trabalhar com número no backend
- Frontend pode formatar

---

## Parcelas

- Nunca calcular parcelas no backend baseado em lógica duplicada
- Sempre confiar nos dados enviados validados

## Categorias

- Nunca criar movimentação sem categoria
- Sempre fallback para "Outros" se não informado