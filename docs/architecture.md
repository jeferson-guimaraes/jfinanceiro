# Arquitetura

## Backend

- Controllers:
  - Apenas recebem request
  - Chamam services
  - Retornam resposta

- Services:
  - Contêm regras de negócio
  - Manipulam models

- FormRequest:
  - Validação de entrada
  - Funcionam como DTO

---

## Frontend

Estrutura:

- pages/
- components/
- utils/

---

## Utils disponíveis

- formataDinheiro.ts
- formataData.ts
- masks.ts
- validaData.ts

Padrão:
- Formatação brasileira (pt-BR)