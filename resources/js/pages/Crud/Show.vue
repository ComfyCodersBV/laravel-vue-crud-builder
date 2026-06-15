<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { useTranslations } from '@table-builder/composables/useTranslations'

defineProps<{
    record: Record<string, unknown>
    title: string
    editRoute: string
    indexRoute: string
}>()

const { t } = useTranslations('vue_crud_builder_translations')
</script>

<template>
    <Head :title="title" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">{{ title }}</h1>
            <div class="flex gap-2">
                <Link :href="indexRoute" class="btn btn-secondary btn-fit">{{ t('back') }}</Link>
                <Link :href="editRoute" class="btn btn-primary btn-fit">{{ t('edit') }}</Link>
            </div>
        </div>

        <div class="divide-y rounded-md border bg-white dark:bg-gray-900">
            <div
                v-for="(value, key) in record"
                :key="key"
                class="flex px-4 py-3"
            >
                <span class="w-48 font-medium capitalize text-muted-foreground">
                    {{ String(key).replace(/_/g, ' ') }}
                </span>
                <span>{{ value ?? '-' }}</span>
            </div>
        </div>
    </div>
</template>
