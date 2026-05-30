<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';
import { Form, Head } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const showPassword = ref(false);
</script>

<template>
    <AuthLayout
        title="Confirme sua Senha"
        description="Essa é uma área segura da aplicação, confirme sua senha antes de continuar."
    >
        <Head title="Confirme a Senha" />

        <Form
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="space-y-6">
                <div class="grid gap-2">
                    <Label htmlFor="password">Senha</Label>
                    <div class="relative">
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            class="mt-1 block w-full pr-10"
                            required
                            autocomplete="current-password"
                            autofocus
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground"
                            @click="showPassword = !showPassword"
                            tabindex="-1"
                        >
                            <Eye v-if="!showPassword" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center">
                    <Button
                        class="w-full btn-primary"
                        :disabled="processing"
                        data-test="confirm-password-button"
                    >
                        <LoaderCircle
                            v-if="processing"
                            class="h-4 w-4 animate-spin"
                        />
                        Confirmar Senha
                    </Button>
                </div>
            </div>
        </Form>
    </AuthLayout>
</template>

