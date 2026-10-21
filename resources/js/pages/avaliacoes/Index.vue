<script setup lang="ts">
import NotaEstrelas from '@/components/avaliacoes/NotaEstrelas.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import avaliacoesRoutes from '@/routes/avaliacoes';
import type { BreadcrumbItem, Paginated } from '@/types';
import { formatDate } from '@/utils/formatDate';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface AvaliacaoModeracao {
  id: number;
  nome: string;
  nota: number;
  mensagem: string;
  publicacao_permitida: boolean;
  aprovada_em: string | null;
  created_at: string;
  user: { id: number; name: string; email: string } | null;
}

defineProps<{
  avaliacoes: Paginated<AvaliacaoModeracao>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Avaliações', href: avaliacoesRoutes.index().url }];

const emAndamento = ref<number | null>(null);

function situacao(avaliacao: AvaliacaoModeracao): { rotulo: string; classe: string } {
  if (!avaliacao.publicacao_permitida) {
    return { rotulo: 'Não permitiu publicar', classe: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' };
  }

  if (avaliacao.aprovada_em) {
    return { rotulo: 'No site', classe: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' };
  }

  return { rotulo: 'Aguardando aprovação', classe: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' };
}

function alternarAprovacao(avaliacao: AvaliacaoModeracao) {
  const rota = avaliacao.aprovada_em
    ? avaliacoesRoutes.reprovar({ avaliacao: avaliacao.id })
    : avaliacoesRoutes.aprovar({ avaliacao: avaliacao.id });

  emAndamento.value = avaliacao.id;
  router.patch(rota.url, {}, {
    preserveScroll: true,
    onFinish: () => {
      emAndamento.value = null;
    },
  });
}
</script>

<template>
  <Head title="Avaliações" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl px-4 py-8 pb-32 sm:px-6 sm:pb-8 lg:px-8">
      <div class="flex flex-col gap-8">
        <div class="space-y-1">
          <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-gray-100">Avaliações</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Aprove as avaliações que podem aparecer no site. A seção da página inicial aparece a partir de 2 avaliações aprovadas.
          </p>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-800">
          <Table>
            <TableHeader class="bg-gray-50/50 dark:bg-gray-900/50">
              <TableRow>
                <TableHead class="text-xs">Autor</TableHead>
                <TableHead class="text-xs">Nota</TableHead>
                <TableHead class="min-w-72 text-xs">Mensagem</TableHead>
                <TableHead class="text-xs">Situação</TableHead>
                <TableHead class="text-xs">Recebida em</TableHead>
                <TableHead class="text-right text-xs">Ação</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="avaliacoes.data.length === 0">
                <TableCell colspan="6" class="py-8 text-center text-sm text-gray-500">Nenhuma avaliação recebida ainda.</TableCell>
              </TableRow>
              <TableRow v-for="avaliacao in avaliacoes.data" :key="avaliacao.id" class="align-top">
                <TableCell>
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ avaliacao.nome }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ avaliacao.user ? avaliacao.user.email : 'Visitante sem conta' }}
                  </p>
                </TableCell>
                <TableCell>
                  <NotaEstrelas :model-value="avaliacao.nota" readonly tamanho="sm" />
                </TableCell>
                <TableCell class="text-sm whitespace-pre-line text-gray-600 dark:text-gray-300">{{ avaliacao.mensagem }}</TableCell>
                <TableCell>
                  <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap" :class="situacao(avaliacao).classe">
                    {{ situacao(avaliacao).rotulo }}
                  </span>
                </TableCell>
                <TableCell class="text-sm whitespace-nowrap text-gray-600 dark:text-gray-300">{{ formatDate(avaliacao.created_at) }}</TableCell>
                <TableCell class="text-right">
                  <Button
                    v-if="avaliacao.publicacao_permitida"
                    size="sm"
                    :variant="avaliacao.aprovada_em ? 'outline' : 'default'"
                    class="h-8 text-xs"
                    :disabled="emAndamento === avaliacao.id"
                    @click="alternarAprovacao(avaliacao)"
                  >
                    {{ avaliacao.aprovada_em ? 'Retirar do site' : 'Aprovar' }}
                  </Button>
                  <span v-else class="text-xs text-gray-400">—</span>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <div v-if="avaliacoes.last_page > 1" class="flex items-center justify-between gap-4 text-xs text-gray-500">
          <span>{{ avaliacoes.from }} - {{ avaliacoes.to }} de {{ avaliacoes.total }} avaliações</span>
          <div class="flex gap-2">
            <Link
              v-for="(link, indice) in [avaliacoes.links[0], avaliacoes.links[avaliacoes.links.length - 1]]"
              :key="indice"
              :href="link.url || '#'"
              preserve-scroll
              class="rounded-md border px-3 py-1.5 font-semibold"
              :class="link.url ? 'border-gray-200 hover:border-blue-300 dark:border-gray-800' : 'pointer-events-none opacity-40'"
            >
              {{ indice === 0 ? '« Anterior' : 'Próxima »' }}
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
