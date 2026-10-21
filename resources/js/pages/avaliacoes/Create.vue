<script setup lang="ts">
import NotaEstrelas from '@/components/avaliacoes/NotaEstrelas.vue';
import InfoTooltip from '@/components/InfoTooltip.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useRecaptcha } from '@/composables/useRecaptcha';
import { home } from '@/routes';
import avaliacoesRoutes from '@/routes/avaliacoes';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, LoaderCircle, MessageSquareHeart } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{
  nomeSugerido: string | null;
  recaptchaSiteKey: string | null;
}>();

const LIMITE_MENSAGEM = 1000;
const MENSAGEM_TOOLTIP_PUBLICACAO =
  'Ao permitir, sua avaliação poderá ser exibida no site do JFinanceiro e no Instagram da CODX (codx.tech).';

const form = useForm({
  nome: props.nomeSugerido ?? '',
  nota: 0,
  mensagem: '',
  publicacao_permitida: null as boolean | null,
  site: '',
  recaptcha_token: '',
});

const recaptcha = useRecaptcha(props.recaptchaSiteKey);
const enviada = ref(false);
const gerandoToken = ref(false);

const enviando = computed(() => gerandoToken.value || form.processing);

/**
 * A publicação exige uma escolha explícita: nenhuma opção vem marcada e o envio só é
 * liberado depois que a pessoa escolhe.
 */
const publicacaoEscolhida = computed(() => form.publicacao_permitida !== null);
const caracteresRestantes = computed(() => LIMITE_MENSAGEM - form.mensagem.length);

/**
 * Erros que não pertencem a um campo visível: limite de envios, reCAPTCHA e campo-armadilha.
 */
const erroGeral = computed(() => {
  const erros = form.errors as Record<string, string | undefined>;

  return erros.envio || erros.recaptcha_token || erros.site;
});

onMounted(() => recaptcha.preparar());

/**
 * Apaga o erro de um campo assim que ele é corrigido, sem esperar o próximo envio.
 */
const camposEditaveis = ['nome', 'nota', 'mensagem', 'publicacao_permitida'] as const;
camposEditaveis.forEach((campo) => {
  watch(
    () => form[campo],
    () => {
      if (form.errors[campo]) {
        form.clearErrors(campo);
      }
    },
  );
});

async function enviar() {
  form.clearErrors();
  gerandoToken.value = true;

  try {
    form.recaptcha_token = await recaptcha.gerarToken('avaliacao');
  } catch {
    form.setError('recaptcha_token', 'Não foi possível carregar a verificação de segurança. Recarregue a página e tente novamente.');
    return;
  } finally {
    gerandoToken.value = false;
  }

  form.post(avaliacoesRoutes.store().url, {
    preserveScroll: true,
    onSuccess: () => {
      enviada.value = true;
      form.reset();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
  });
}

function avaliarNovamente() {
  enviada.value = false;
  form.reset();
  form.nome = props.nomeSugerido ?? '';
}
</script>

<template>
  <Head title="Avalie o JFinanceiro" />

  <div class="min-h-screen bg-background font-sans text-foreground">
    <header class="sticky top-0 z-50 w-full border-b border-border bg-background/80 backdrop-blur-md">
      <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
        <Link :href="home()" class="flex items-center gap-2">
          <img src="/images/logo_jfinanceiro.png" alt="Logo JFinanceiro" class="h-8 w-8" />
          <span class="text-xl font-bold tracking-tight text-foreground">JFinanceiro</span>
        </Link>
        <Link :href="home()" class="flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-primary">
          <ArrowLeft class="h-4 w-4" />
          Voltar ao site
        </Link>
      </div>
    </header>

    <main class="container mx-auto px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
      <div class="mx-auto max-w-xl">
        <div class="mb-8 text-center">
          <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
            <MessageSquareHeart class="h-6 w-6" />
          </div>
          <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Avalie o JFinanceiro</h1>
          <p class="mt-3 text-muted-foreground">Sua opinião nos ajuda a melhorar o sistema para todo mundo.</p>
        </div>

        <div
          v-if="enviada"
          class="rounded-3xl border border-border bg-card p-8 text-center shadow-sm"
          role="status"
        >
          <CheckCircle2 class="mx-auto h-12 w-12 text-green-600 dark:text-green-500" />
          <h2 class="mt-4 text-2xl font-bold">Obrigado pela sua avaliação!</h2>
          <p class="mt-2 text-muted-foreground">Recebemos sua mensagem e ela será lida com carinho pela nossa equipe.</p>
          <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Link
              :href="home()"
              class="inline-flex h-11 w-full items-center justify-center rounded-full bg-primary px-6 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 sm:w-auto"
            >
              Voltar ao site
            </Link>
            <Button variant="ghost" class="h-11 w-full rounded-full sm:w-auto" @click="avaliarNovamente">
              Enviar outra avaliação
            </Button>
          </div>
        </div>

        <form
          v-else
          class="relative flex flex-col gap-6 rounded-3xl border border-border bg-card p-6 shadow-sm sm:p-8"
          novalidate
          @submit.prevent="enviar"
        >
          <div class="flex flex-col gap-2">
            <Label for="nome">Nome completo</Label>
            <Input
              id="nome"
              v-model="form.nome"
              autocomplete="name"
              maxlength="120"
              placeholder="Ex.: Maria da Silva"
              :aria-invalid="Boolean(form.errors.nome) || undefined"
            />
            <InputError :message="form.errors.nome" />
          </div>

          <div class="flex flex-col gap-2">
            <Label as="span">Sua nota</Label>
            <NotaEstrelas v-model="form.nota" :invalido="Boolean(form.errors.nota)" />
            <InputError :message="form.errors.nota" />
          </div>

          <div class="flex flex-col gap-2">
            <Label for="mensagem">Mensagem</Label>
            <textarea
              id="mensagem"
              v-model="form.mensagem"
              rows="5"
              :maxlength="LIMITE_MENSAGEM"
              placeholder="Conte como o JFinanceiro tem ajudado você e o que podemos melhorar."
              :aria-invalid="Boolean(form.errors.mensagem) || undefined"
              aria-describedby="mensagem-contador"
              class="min-h-28 w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-primary/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
            />
            <div class="flex items-start justify-between gap-4">
              <InputError :message="form.errors.mensagem" />
              <span id="mensagem-contador" class="ml-auto shrink-0 text-xs text-muted-foreground">
                {{ caracteresRestantes }} caracteres restantes
              </span>
            </div>
          </div>

          <fieldset class="flex flex-col gap-3">
            <legend class="mb-3 flex items-center gap-1.5 text-sm font-medium">
              Publicação da avaliação
              <InfoTooltip :message="MENSAGEM_TOOLTIP_PUBLICACAO" />
            </legend>
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-border p-3 transition-colors has-checked:border-primary has-checked:bg-primary/5">
              <input
                v-model="form.publicacao_permitida"
                type="radio"
                name="publicacao_permitida"
                :value="true"
                class="h-4 w-4 accent-primary"
              />
              <span class="text-sm">Permito publicar minha avaliação</span>
            </label>
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-border p-3 transition-colors has-checked:border-primary has-checked:bg-primary/5">
              <input
                v-model="form.publicacao_permitida"
                type="radio"
                name="publicacao_permitida"
                :value="false"
                class="h-4 w-4 accent-primary"
              />
              <span class="text-sm">Não permito publicar</span>
            </label>
            <InputError :message="form.errors.publicacao_permitida" />
          </fieldset>

          <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
            <label for="site">Não preencha este campo</label>
            <input id="site" v-model="form.site" type="text" name="site" tabindex="-1" autocomplete="off" />
          </div>

          <div v-if="erroGeral" class="rounded-xl border border-destructive/30 bg-destructive/5 p-3" role="alert">
            <InputError :message="erroGeral" />
          </div>

          <div class="flex flex-col gap-2">
            <Button
              type="submit"
              class="h-12 rounded-full text-base font-semibold"
              :disabled="enviando || !publicacaoEscolhida"
              :aria-describedby="publicacaoEscolhida ? undefined : 'aviso-publicacao'"
            >
              <LoaderCircle v-if="enviando" class="mr-2 h-4 w-4 animate-spin" />
              Enviar avaliação
            </Button>
            <p v-if="!publicacaoEscolhida" id="aviso-publicacao" class="text-center text-xs text-muted-foreground">
              Escolha se permite ou não publicar sua avaliação para liberar o envio.
            </p>
          </div>

          <p v-if="recaptcha.habilitado" class="text-center text-xs text-muted-foreground">
            Protegido pelo reCAPTCHA. Aplicam-se a
            <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="underline hover:text-foreground">Política de Privacidade</a>
            e os
            <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer" class="underline hover:text-foreground">Termos de Serviço</a>
            do Google.
          </p>
        </form>
      </div>
    </main>
  </div>
</template>
