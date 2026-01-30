<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import movimentacoes from '@/routes/movimentacoes';
import { useForm } from '@inertiajs/vue3';

defineProps({
    open: Boolean,
});

const emit = defineEmits(['close', 'category-created']);
const { success, error } = useToast();

const form = useForm({
    nome: '',
    tipo: 'gasto',
    origem: 'modal',
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
}
</script>

<template>
    <Dialog :open="open" @update:open="closeModal">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Cadastrar Nova Categoria</DialogTitle>
                <DialogDescription>
                    Crie uma nova categoria para suas movimentações.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submit">
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="nome" class="text-right"> Nome </Label>
                        <Input id="nome" v-model="form.nome" class="col-span-3" />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="tipo" class="text-right"> Tipo </Label>
                        <Select v-model="form.tipo">
                            <SelectTrigger class="col-span-3">
                                <SelectValue placeholder="Selecione o tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ganho"> Ganho </SelectItem>
                                <SelectItem value="gasto"> Gasto </SelectItem>
                                <SelectItem value="gasto futuro"> Gasto Futuro </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeModal"> Cancelar </Button>
                    <Button type="submit" :disabled="form.processing" class="btn-primary"> Salvar </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
