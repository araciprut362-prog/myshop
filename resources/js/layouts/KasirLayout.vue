<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    Store,
    LayoutDashboard,
    ShoppingBag,
    History,
    LogOut,
    CheckCircle2,
    AlertCircle,
    Info,
    MapPin,
    Clock
} from 'lucide-vue-next';
import type { SharedProps } from '@/types';

defineProps<{
    title?: string;
}>();

const page = usePage<SharedProps>();

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col font-sans">
        <!-- Top Cashier Header -->
        <header class="bg-slate-900 text-white sticky top-0 z-40 border-b border-slate-800 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Brand & Branch Info -->
                <div class="flex items-center gap-6">
                    <Link href="/kasir/dashboard" class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/20">
                            <Store class="w-5 h-5" />
                        </div>
                        <div>
                            <span class="text-lg font-bold tracking-tight text-white">MyShop</span>
                            <span class="block text-[10px] font-semibold uppercase tracking-widest text-emerald-400">Point of Sale</span>
                        </div>
                    </Link>

                    <!-- Assigned Branch Badge -->
                    <div v-if="page.props.auth.user?.cabang" class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800 text-slate-200 text-xs border border-slate-700">
                        <MapPin class="w-3.5 h-3.5 text-emerald-400" />
                        <span class="font-medium text-white">{{ page.props.auth.user.cabang.nama_cabang }}</span>
                        <span class="text-slate-400 text-[11px]">({{ page.props.auth.user.cabang.kota }})</span>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <nav class="flex items-center gap-1 sm:gap-2">
                    <Link
                        href="/kasir/pos"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition"
                        :class="[
                            $page.url.startsWith('/kasir/pos')
                                ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30'
                                : 'text-slate-300 hover:text-white hover:bg-slate-800'
                        ]"
                    >
                        <ShoppingBag class="w-4 h-4" />
                        <span>Kasir (POS)</span>
                    </Link>

                    <Link
                        href="/kasir/riwayat"
                        class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition"
                        :class="[
                            $page.url.startsWith('/kasir/riwayat')
                                ? 'bg-slate-800 text-white font-semibold'
                                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                        ]"
                    >
                        <History class="w-4 h-4" />
                        <span class="hidden sm:inline">Riwayat Transaksi</span>
                    </Link>

                    <Link
                        href="/kasir/dashboard"
                        class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition"
                        :class="[
                            $page.url === '/kasir/dashboard'
                                ? 'bg-slate-800 text-white font-semibold'
                                : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                        ]"
                    >
                        <LayoutDashboard class="w-4 h-4" />
                        <span class="hidden sm:inline">Dashboard</span>
                    </Link>
                </nav>

                <!-- Cashier Profile & Logout -->
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-semibold text-white">{{ page.props.auth.user?.nama_lengkap }}</p>
                        <p class="text-[10px] text-emerald-400 font-medium">Kasir Aktif</p>
                    </div>

                    <button
                        @click="logout"
                        class="flex items-center gap-1.5 text-xs font-semibold text-rose-300 hover:text-white bg-rose-500/10 hover:bg-rose-500/30 border border-rose-500/20 px-3 py-1.5 rounded-lg transition"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Flash Notifications -->
        <div v-if="page.props.flash.success || page.props.flash.error || page.props.flash.warning" class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4">
            <div
                v-if="page.props.flash.success"
                class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3 shadow-xs"
            >
                <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0" />
                <span>{{ page.props.flash.success }}</span>
            </div>
            <div
                v-if="page.props.flash.error"
                class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3 shadow-xs"
            >
                <AlertCircle class="w-5 h-5 text-rose-600 shrink-0" />
                <span>{{ page.props.flash.error }}</span>
            </div>
            <div
                v-if="page.props.flash.warning"
                class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-center gap-3 shadow-xs"
            >
                <Info class="w-5 h-5 text-amber-600 shrink-0" />
                <span>{{ page.props.flash.warning }}</span>
            </div>
        </div>

        <!-- Main Body -->
        <main class="flex-1 flex flex-col">
            <slot />
        </main>
    </div>
</template>

