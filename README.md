# JFinanceiro 💰

Sistema web para **controle financeiro pessoal**, desenvolvido com foco em **organização, clareza de domínio e boas práticas de desenvolvimento backend e full stack**.

Este projeto é a **reescrita completa** de um sistema antigo criado inicialmente em PHP puro, agora utilizando uma stack moderna com **Laravel, Vue.js e Inertia.js**, aplicando conceitos atuais de arquitetura, modelagem de dados e experiência do usuário.

---

## 🚀 Objetivo do Projeto

O JFinanceiro tem como objetivo permitir que usuários:

- Registrem ganhos e despesas
- Registrem gastos futuros
- Organizem movimentações por categorias
- Acompanhem saldo e histórico financeiro
- Tenham uma visão clara e simples da sua vida financeira

Além do produto em si, o projeto também serve como **laboratório prático** para aplicar boas práticas de desenvolvimento web moderno.

---

## 🧱 Stack Tecnológica

### Backend
- **PHP 8+**
- **Laravel**
- Eloquent ORM
- Migrations e Seeders
- Validações e regras de negócio no backend

### Frontend
- **Vue.js 3**
- **Inertia.js** (arquitetura monolítica moderna, sem API separada)
- Componentização
- Formulários reativos e validações

### Banco de Dados
- **MySQL / SQLite** (ambiente de desenvolvimento)
- Modelagem relacional
- Controle de integridade dos dados

### Testes
- PHPUnit

### Ferramentas e Outros
- Git e GitHub
- Composer
- NPM
- Vite
- Padrão MVC
- Boas práticas de organização de código

---

## 🧠 Arquitetura

O projeto utiliza uma abordagem **monolítica moderna**, com Laravel no backend e Vue.js no frontend, integrados via **Inertia.js**, evitando a complexidade de uma API REST separada quando não necessária.

Essa decisão traz benefícios como:
- Menor complexidade arquitetural
- Melhor produtividade
- Código mais coeso
- Facilidade de manutenção

---

## 📌 Funcionalidades (em desenvolvimento)

- [x] Autenticação de usuários
- [x] Cadastro de categorias
- [x] Registro de movimentações financeiras
- [x] Classificação entre ganhos e despesas
- [x] Cálculo de saldo
- [x] Testes automatizados
- [ ] Listagem de movimentações financeiras
- [ ] Edição de movimentações financeiras
- [ ] Exclusão de movimentações financeiras
- [ ] Filtros por período
- [ ] Dashboard com resumo financeiro
- [ ] Notificação de contas próximas ao vencimento

---

## 📷 Demonstração

> *(Em breve: screenshots ou GIFs do sistema em funcionamento)*

---

## 🛠️ Como rodar o projeto localmente

```bash
# Clonar o repositório
git clone https://github.com/seu-usuario/jfinanceiro.git

# Entrar no projeto
cd jfinanceiro

# Instalar dependências do backend
composer install

# Instalar dependências do frontend
npm install

# Configurar variáveis de ambiente
cp .env.example .env
php artisan key:generate

# Rodar migrations
php artisan migrate

# Iniciar o projeto
npm run dev
php artisan serve
```

## 🧪 Testes Automatizados

O projeto possui **testes automatizados utilizando o framework de testes do Laravel**, com foco nas **regras de negócio**, garantindo maior confiabilidade e segurança na evolução do sistema.

Os testes atuais cobrem:
- Criação de movimentações financeiras (ganhos, despesas e despesas futuras)
- Validação de dados
- Cálculo de saldo

### Executando os testes

```bash
php artisan test
```
## 👨‍💻 Sobre o desenvolvimento

Este projeto está em evolução contínua. Ele reflete meu processo de aprendizado e amadurecimento como desenvolvedor, aplicando conceitos de:

- Organização de código
- Separação de responsabilidades
- Regras de negócio no backend
- Integração eficiente entre frontend e backend

## 📫 Contato

- Portfólio: https://jeferson-guimaraes.github.io/portfolio/

- GitHub: https://github.com/jeferson-guimaraes