<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import Form from '@form-builder/components/Form.vue'
import type { FormSchema } from '@form-builder/types/form-builder'
import { useTranslations } from '@table-builder/composables/useTranslations'

const props = defineProps<{
    form: FormSchema
    title: string
    destroyRoute?: string
    destroyLabel?: string
    destroyConfirm?: string
}>()

const { t } = useTranslations('vue_crud_builder_translations')

function handleDestroy() {
    const message = props.destroyConfirm ?? t('delete_confirm')
    if (confirm(message)) {
        router.delete(props.destroyRoute!)
    }
}
</script>

<template>
    <Head :title="title" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <h1 class="mb-4 text-2xl font-semibold">{{ title }}</h1>

        <div class="relative rounded-lg border bg-white p-4 dark:bg-gray-900">
            <Form :schema="form" />
            <div v-if="destroyRoute" class="absolute bottom-4 right-4">
                <button type="button" class="btn btn-danger" @click="handleDestroy">
                    {{ destroyLabel ?? t('delete') }}
                </button>
            </div>
        </div>
    </div>
</template>
