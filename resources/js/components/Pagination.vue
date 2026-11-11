<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}>();
</script>

<template>
    <div v-if="links && links.length > 3" class="flex flex-wrap items-center justify-center gap-1 mt-4">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="px-3 py-1.5 text-xs text-slate-400 bg-slate-100 rounded border border-slate-200 cursor-not-allowed select-none"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="px-3 py-1.5 text-xs rounded border transition-colors font-medium select-none"
                :class="[
                    link.active
                        ? 'bg-sky-600 text-white border-sky-600 shadow-sm'
                        : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-200'
                ]"
                v-html="link.label"
                preserve-scroll
            />
        </template>
    </div>
</template>

