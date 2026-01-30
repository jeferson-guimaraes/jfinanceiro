<script setup lang="ts">
import Button from '../ui/button/Button.vue'
import Dialog from '../ui/dialog/Dialog.vue'
import DialogContent from '../ui/dialog/DialogContent.vue'
import DialogDescription from '../ui/dialog/DialogDescription.vue'
import DialogFooter from '../ui/dialog/DialogFooter.vue'
import DialogHeader from '../ui/dialog/DialogHeader.vue'
import DialogTitle from '../ui/dialog/DialogTitle.vue'

defineProps<{
	open: boolean
	title?: string
	message?: string
	description?: string
	confirmText?: string
	cancelText?: string
}>()

const emit = defineEmits<{
	(e: 'confirm'): void
	(e: 'cancel'): void
	(e: 'update:open', value: boolean): void
}>()
</script>

<template>
	<Dialog :open="open" @update:open="(v) => emit('update:open', v)">
		<DialogContent class="sm:max-w-[425px]">
			<DialogHeader>
				<DialogTitle>{{ title ?? 'Confirmar ação' }}</DialogTitle>
			</DialogHeader>

			<p>{{ message ?? 'Deseja continuar?' }}</p>

			<DialogDescription>
				{{description ?? ''}}
			</DialogDescription>

			<DialogFooter>
				<Button variant="outline" @click="emit('cancel')">
					{{ cancelText ?? 'Cancelar' }}
				</Button>

				<Button variant="destructive" @click="emit('confirm')">
					{{ confirmText ?? 'Confirmar' }}
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
