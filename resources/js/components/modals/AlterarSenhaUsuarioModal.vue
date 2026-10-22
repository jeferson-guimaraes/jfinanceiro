<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import InputSenha from '@/components/InputSenha.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { update as atualizarSenha } from '@/routes/usuarios/senha';
import { useForm } from '@inertiajs/vue3';
import { KeyRound, LoaderCircle } from 'lucide-vue-next';
import { watch } from 'vue';

const props = defineProps<{
  open: boolean;
  usuario: { id: number; name: string; email: string } | null;
}>();

const emit = defineEmits<{
  (e: 'update:open', valor: boolean): void;
}>();

const form = useForm({
  password: '',
  password_confirmation: '',
  senha_administrador: '',
});

/**
 * Sempre começa limpo ao abrir, para nunca reaproveitar senhas digitadas para outro usuário.
 */
watch(
  () => props.open,
  (aberto) => {
    if (aberto) {
      form.reset();
      form.clearErrors();
    }
  },
);

function fechar() {
  emit('update:open', false);
}

function salvar() {
  if (!props.usuario) {
    return;
  }

  form.patch(atualizarSenha({ usuario: props.usuario.id }).url, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      fechar();
    },
    onError: () => form.reset('senha_administrador'),
  });
}
</script>

<template>
  <Dialog :open="open" @update:open="(valor) => emit('update:open', valor)">
    <DialogContent class="flex flex-col gap-0 overflow-hidden border-none p-0 shadow-2xl sm:max-w-[440px]">
      <div class="relative shrink-0 bg-blue-600 p-6 text-white">
        <div class="absolute top-4 right-4 opacity-10">
          <KeyRound class="h-20 w-20" />
        </div>
        <DialogHeader>
          <DialogTitle class="pr-8 text-2xl font-bold text-white">Alterar senha</DialogTitle>
          <DialogDescription class="text-blue-100">
            Nova senha para <strong class="font-semibold text-white">{{ usuario?.name }}</strong>
            <span class="block text-xs text-blue-100/80">{{ usuario?.email }}</span>
          </DialogDescription>
        </DialogHeader>
      </div>

      <form class="flex min-h-0 flex-col gap-5 overflow-y-auto bg-white p-6 dark:bg-sidebar" @submit.prevent="salvar">
        <div class="flex flex-col gap-2">
          <Label for="nova_senha">Nova senha</Label>
          <InputSenha
            id="nova_senha"
            v-model="form.password"
            autocomplete="new-password"
            class="h-11"
            :aria-invalid="Boolean(form.errors.password) || undefined"
          />
          <InputError :message="form.errors.password" />
        </div>

        <div class="flex flex-col gap-2">
          <Label for="confirmacao_nova_senha">Confirmar nova senha</Label>
          <InputSenha
            id="confirmacao_nova_senha"
            v-model="form.password_confirmation"
            autocomplete="new-password"
            class="h-11"
          />
        </div>

        <div class="flex flex-col gap-2 border-t border-gray-100 pt-5 dark:border-gray-800">
          <Label for="senha_administrador">Sua senha</Label>
          <InputSenha
            id="senha_administrador"
            v-model="form.senha_administrador"
            autocomplete="current-password"
            class="h-11"
            :aria-invalid="Boolean(form.errors.senha_administrador) || undefined"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400">Confirme com a sua senha de administrador.</p>
          <InputError :message="form.errors.senha_administrador" />
        </div>

        <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
          O usuário será desconectado de todos os dispositivos e precisará entrar com a nova senha.
        </p>

        <DialogFooter class="flex flex-col-reverse gap-3 sm:flex-row">
          <Button type="button" variant="ghost" class="h-11 flex-1" @click="fechar">Cancelar</Button>
          <Button type="submit" class="h-11 flex-1 bg-blue-600 text-white hover:bg-blue-700" :disabled="form.processing">
            <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
            Alterar senha
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
