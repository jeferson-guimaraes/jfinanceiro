<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import movimentacoes from '@/routes/movimentacoes';
import { useForm } from '@inertiajs/vue3';
import { Tag, PlusCircle, Wallet, Calendar, CreditCard } from 'lucide-vue-next';
import { computed, watch, type PropType } from 'vue';

type TipoCategoria = 'ganho' | 'gasto' | 'gasto futuro';

const props = defineProps({
    open: Boolean,
    defaultTipo: {
        type: String as PropType<TipoCategoria>,
        default: 'gasto',
    },
    lockTipo: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'category-created']);
const { success, error } = useToast();

const form = useForm({
    nome: '',
    tipo: props.defaultTipo,
    origem: 'modal',
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            form.tipo = props.defaultTipo;
            form.clearErrors();
        }
    },
);

watch(
    () => props.defaultTipo,
    (tipo) => {
        if (props.open && props.lockTipo) {
            form.tipo = tipo;
        }
    },
);

const variantClasses = computed(() => {
    switch (form.tipo) {
        case 'ganho': return 'bg-emerald-600';
        case 'gasto': return 'bg-red-600';
        case 'gasto futuro': return 'bg-amber-600';
        default: return 'bg-blue-600';
    }
});

const formIcon = computed(() => {
    switch (form.tipo) {
        case 'ganho': return Wallet;
        case 'gasto': return CreditCard;
        case 'gasto futuro': return Calendar;
        default: return Tag;
    }
});

function submit() {
    form.post(movimentacoes.categorias.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            emit('category-created');
            closeModal();
            success('Categoria criada com sucesso!');
        },
        onError: () => {
            error('Ocorreu um erro ao criar a categoria.');
        },
    });
}

function closeModal() {
    emit('close');
    form.reset();
    form.tipo = props.defaultTipo;
}
</script>

<template>
    <Dialog :open="open" @update:open="closeModal">
        <DialogContent class="sm:max-w-[450px] p-0 overflow-hidden border-none shadow-2xl">
            <div :class="[variantClasses, 'p-6 text-white relative transition-colors duration-300']">
                <div class="absolute top-4 right-4 opacity-10">
                    <component :is="formIcon" class="h-20 w-20" />
                </div>
                <DialogHeader>
                    <DialogTitle class="text-2xl font-bold text-white">Nova Categoria</DialogTitle>
                    <DialogDescription class="text-white/80">
                        Crie uma nova categoria para organizar suas movimentações.
                    </DialogDescription>
                </DialogHeader>
            </div>

            <form @submit.prevent="submit" class="p-6 space-y-6 bg-white dark:bg-sidebar">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="nome" class="text-sm font-semibold flex items-center gap-2">
                            <Tag class="h-4 w-4 text-muted-foreground" />
                            Nome
                        </Label>
                        <Input id="nome" v-model="form.nome" placeholder="Ex: Alimentação, Lazer..." class="h-11 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700" />
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="tipo" class="text-sm font-semibold flex items-center gap-2">
                            <PlusCircle class="h-4 w-4 text-muted-foreground" />
                            Tipo
                        </Label>
                        <Select v-model="form.tipo" :disabled="lockTipo">
                            <SelectTrigger class="h-11 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                <SelectValue placeholder="Selecione o tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ganho"> Ganho </SelectItem>
                                <SelectItem value="gasto"> Despesa </SelectItem>
                                <SelectItem value="gasto futuro"> Despesa Futura </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <DialogFooter class="pt-2">
                    <Button type="button" variant="ghost" @click="closeModal" class="h-11"> Cancelar </Button>
                    <Button type="submit" :disabled="form.processing" class="h-11 px-6 bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/20"> Salvar Categoria </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>

