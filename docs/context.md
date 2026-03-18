# JFinanceiro — Contexto Geral

JFinanceiro é um SaaS de gestão financeira pessoal.

## Objetivo
Permitir que usuários registrem:
- Ganhos
- Gastos
- Gastos futuros (parcelados)

## Stack
- Backend: Laravel 12
- Frontend: Vue + Inertia
- Estilo: TailwindCSS

## Arquitetura
- Controllers são finos (apenas orquestração)
- Regras de negócio ficam em Services
- Validação via FormRequest (DTO)

## Princípios
- Simplicidade > Complexidade
- Regras financeiras explícitas
- Backend é a fonte da verdade

## Diretriz de Interface

O sistema é **mobile-first**.

Regras:
- Mobile: exibição em cards
- Desktop (>= md): exibição em tabela

A UI deve sempre priorizar:
- Leitura rápida
- Ações claras (ex: pagar, filtrar)