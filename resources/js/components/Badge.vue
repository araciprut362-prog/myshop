<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'primary';
        size?: 'sm' | 'md';
    }>(),
    {
        variant: 'neutral',
        size: 'sm',
    }
);
</script>

<template>
    <span
        class="inline-flex items-center font-medium rounded-full select-none"
        :class="[
            size === 'sm' ? 'px-2.5 py-0.5 text-xs' : 'px-3 py-1 text-sm',
            variant === 'success' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20' : '',
            variant === 'warning' ? 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20' : '',
            variant === 'danger' ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20' : '',
            variant === 'info' ? 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20' : '',
            variant === 'primary' ? 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20' : '',
            variant === 'neutral' ? 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-600/10' : '',
        ]"
    >
        <slot />
    </span>
</template>

