<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import usuariosRoutes from '@/routes/usuarios';
import type { BreadcrumbItem, Paginated, User } from '@/types';
import { formatDate } from '@/utils/formatDate';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash-es/debounce';
import { computed, ref, watch } from 'vue';

type UsuarioListItem = Pick<User, 'id' | 'name' | 'email' | 'role' | 'status' | 'created_at'>;

type UsuarioFilters = {
  search: string;
  role: string;
  status: string;
  per_page: number;
};

const props = defineProps<{
  usuarios: Paginated<UsuarioListItem>;
  filters: UsuarioFilters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Usuários',
    href: usuariosRoutes.index().url,
  },
];

const localSearch = ref(props.filters.search || '');
const localRole = ref(props.filters.role || 'all');
const localStatus = ref(props.filters.status || 'all');
const localPerPage = ref(String(props.filters.per_page || 30));

const roleLabel = (role: string): string => {
  return role === 'admin' ? 'Administrador' : 'Usuário';
};

const statusLabel = (status: string): string => {
  return status === 'ativo' ? 'Ativo' : 'Inativo';
};

const searchData = (): void => {
  router.get(usuariosRoutes.index().url, {
    search: localSearch.value || undefined,
    role: localRole.value === 'all' ? undefined : localRole.value,
    status: localStatus.value === 'all' ? undefined : localStatus.value,
    per_page: Number(localPerPage.value),
  }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  });
};

const debouncedSearch = debounce(searchData, 500);

watch(localSearch, () => {
  debouncedSearch();
});

watch([localRole, localStatus, localPerPage], () => {
  searchData();
});

const paginasVisiveis = computed(() => {
  const total = props.usuarios.last_page;
  const atual = props.usuarios.current_page;
  const delta = 1;
  const range: (number | string)[] = [];

  for (let i = 1; i <= total; i++) {
    if (
      i === 1
      || i === total
      || (i >= atual - delta && i <= atual + delta)
    ) {
      range.push(i);
    } else if (
      i === atual - delta - 1
      || i === atual + delta + 1
    ) {
      range.push('...');
    }
  }

  return range.filter((item, pos) => range.indexOf(item) === pos);
});

const getPageUrl = (page: number | string): string => {
  if (page === '...') {
    return '#';
  }

  const url = new URL(props.usuarios.path, window.location.origin);
  const params = new URLSearchParams(window.location.search);
  params.set('page', String(page));

  if (localSearch.value) {
    params.set('search', localSearch.value);
  } else {
    params.delete('search');
  }

  if (localRole.value !== 'all') {
    params.set('role', localRole.value);
  } else {
    params.delete('role');
  }

  if (localStatus.value !== 'all') {
    params.set('status', localStatus.value);
  } else {
    params.delete('status');
  }

  params.set('per_page', localPerPage.value);

  return `${url.pathname}?${params.toString()}`;
};
</script>

<template>
  <Head title="Usuários" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl px-4 py-8 pb-32 sm:px-6 lg:px-8 sm:pb-8">
      <div class="flex flex-col gap-8">
        <div class="space-y-1">
          <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-3xl">
            Usuários
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Consulte os usuários cadastrados no sistema.
          </p>
        </div>

        <div class="space-y-6">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex-1">
              <Input
                v-model="localSearch"
                placeholder="Buscar por nome ou e-mail..."
                class="h-9 text-xs"
              />
            </div>

            <Select v-model="localRole">
              <SelectTrigger class="h-9 text-xs w-full sm:w-44">
                <SelectValue placeholder="Perfil" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todos os perfis</SelectItem>
                <SelectItem value="admin">Administrador</SelectItem>
                <SelectItem value="user">Usuário</SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="localStatus">
              <SelectTrigger class="h-9 text-xs w-full sm:w-40">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todos os status</SelectItem>
                <SelectItem value="ativo">Ativo</SelectItem>
                <SelectItem value="inativo">Inativo</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="overflow-hidden rounded-lg border border-gray-100 dark:border-gray-800">
            <Table>
              <TableHeader class="bg-gray-50/50 dark:bg-gray-900/50">
                <TableRow>
                  <TableHead class="text-xs">Nome</TableHead>
                  <TableHead class="text-xs">E-mail</TableHead>
                  <TableHead class="text-xs">Perfil</TableHead>
                  <TableHead class="text-xs">Status</TableHead>
                  <TableHead class="text-xs">Cadastro</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-if="usuarios.data.length === 0">
                  <TableCell colspan="5" class="py-8 text-center text-sm text-gray-500">
                    Nenhum usuário encontrado.
                  </TableCell>
                </TableRow>
                <TableRow v-for="usuario in usuarios.data" :key="usuario.id">
                  <TableCell class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ usuario.name }}
                  </TableCell>
                  <TableCell class="text-sm text-gray-600 dark:text-gray-300">
                    {{ usuario.email }}
                  </TableCell>
                  <TableCell class="text-sm text-gray-600 dark:text-gray-300">
                    {{ roleLabel(usuario.role) }}
                  </TableCell>
                  <TableCell>
                    <span
                      class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                      :class="usuario.status === 'ativo'
                        ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                    >
                      {{ statusLabel(usuario.status) }}
                    </span>
                  </TableCell>
                  <TableCell class="text-sm text-gray-600 dark:text-gray-300">
                    {{ formatDate(usuario.created_at) }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 border-t border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between w-full sm:w-auto gap-6">
              <div class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">
                {{ usuarios.from || 0 }} - {{ usuarios.to || 0 }} de {{ usuarios.total || 0 }} registros
              </div>

              <div class="flex items-center gap-2">
                <span class="text-[10px] text-gray-400 uppercase font-bold hidden sm:inline">Exibir:</span>
                <Select v-model="localPerPage">
                  <SelectTrigger class="h-8 text-[10px] w-16 bg-transparent border-gray-200 dark:border-gray-800">
                    <SelectValue :placeholder="localPerPage" />
                  </SelectTrigger>
                    <SelectContent>
                    <SelectItem value="10">10</SelectItem>
                    <SelectItem value="25">25</SelectItem>
                    <SelectItem value="30">30</SelectItem>
                    <SelectItem value="50">50</SelectItem>
                    <SelectItem value="100">100</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="flex items-center justify-center w-full sm:w-auto gap-1">
              <Link
                :href="usuarios.links[0]?.url || '#'"
                preserve-scroll
                preserve-state
                class="h-9 flex items-center justify-center px-2 text-[10px] font-bold uppercase border rounded-md transition-all"
                :class="usuarios.links[0]?.url
                  ? 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm hover:border-blue-300'
                  : 'text-gray-300 dark:text-gray-700 border-gray-100 dark:border-gray-900 opacity-50 cursor-not-allowed'"
              >
                <span class="text-[7pt] sm:inline">« Anterior</span>
              </Link>

              <div class="flex items-center gap-1">
                <template v-for="(page, index) in paginasVisiveis" :key="index">
                  <span v-if="page === '...'" class="px-1 text-gray-400 text-xs font-bold">...</span>
                  <Link
                    v-else
                    :href="getPageUrl(page)"
                    preserve-scroll
                    preserve-state
                    class="min-w-[32px] sm:min-w-[36px] h-9 flex items-center justify-center px-2 text-[10px] font-black border rounded-md transition-all duration-200"
                    :class="{
                      'bg-blue-600 text-white border-blue-600 shadow-sm z-10': page === usuarios.current_page,
                      'text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:border-blue-300': page !== usuarios.current_page,
                    }"
                  >
                    {{ page }}
                  </Link>
                </template>
              </div>

              <Link
                :href="usuarios.links[usuarios.links.length - 1]?.url || '#'"
                preserve-scroll
                preserve-state
                class="h-9 flex items-center justify-center px-2 text-[10px] font-bold uppercase border rounded-md transition-all"
                :class="usuarios.links[usuarios.links.length - 1]?.url
                  ? 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm hover:border-blue-300'
                  : 'text-gray-300 dark:text-gray-700 border-gray-100 dark:border-gray-900 opacity-50 cursor-not-allowed'"
              >
                <span class="text-[7pt] sm:inline">Próximo »</span>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
