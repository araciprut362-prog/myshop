<script setup lang="ts">
import { watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '4xl';
        title?: string;
    }>(),
    {
        show: false,
        maxWidth: '2xl',
    }
);

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

watch(
    () => props.show,
    (show) => {
        if (show) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);
</script>

<template>
    <teleport to="body">
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-show="show"
                class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs"
                @click="close"
            >
                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-show="show"
                        class="bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:w-full mx-auto border border-slate-100"
                        :class="[
                            maxWidth === 'sm' ? 'sm:max-w-sm' : '',
                            maxWidth === 'md' ? 'sm:max-w-md' : '',
                            maxWidth === 'lg' ? 'sm:max-w-lg' : '',
                            maxWidth === 'xl' ? 'sm:max-w-xl' : '',
                            maxWidth === '2xl' ? 'sm:max-w-2xl' : '',
                            maxWidth === '4xl' ? 'sm:max-w-4xl' : '',
                        ]"
                        @click.stop
                    >
                        <div v-if="title || $slots.header" class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                            <slot name="header">
                                <h3 class="text-base font-semibold text-slate-800">{{ title }}</h3>
                            </slot>
                            <button
                                @click="close"
                                type="button"
                                class="text-slate-400 hover:text-slate-600 rounded-lg p-1.5 hover:bg-slate-100 transition-colors"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>
                        
                        <div class="p-6">
                            <slot />
                        </div>

                        <div v-if="$slots.footer" class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end gap-3">
                            <slot name="footer" />
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>

