<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import Form from '@form-builder/components/Form.vue'
import type { FormSchema } from '@form-builder/types/form-builder'

defineProps<{
    form: FormSchema
    title: string
}>()
</script>

<template>
    <Head :title="title" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <h1 class="mb-4 text-2xl font-semibold">{{ title }}</h1>

        <div class="relative rounded-lg border bg-white p-4 dark:bg-gray-900">
            <Form :schema="form" />
        </div>
    </div>
</template>
