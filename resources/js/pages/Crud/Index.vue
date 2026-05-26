<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import TableBuilder from '@table-builder/components/TableBuilder.vue'
import type { TableData } from '@table-builder/types/table-builder'
import { useTranslations } from '@table-builder/composables/useTranslations'

defineProps<{
    table: TableData
    title: string
    createRoute: string
}>()

const { t } = useTranslations('vue_crud_builder_translations')
</script>

<template>
    <Head :title="title" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">{{ title }}</h1>
            <Link :href="createRoute" class="btn btn-success btn-fit">{{ t('create') }}</Link>
        </div>

        <div class="rounded-lg border bg-white p-4 dark:bg-gray-900">
            <TableBuilder :table="table" />
        </div>
    </div>
</template>
