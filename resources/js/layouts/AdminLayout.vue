<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Package,
    FolderTree,
    Boxes,
    Receipt,
    ShoppingBag,
    Users,
    UserCheck,
    Store,
    BarChart3,
    Settings,
    LogOut,
    Menu,
    X,
    Bell,
    CheckCircle2,
    AlertCircle,
    Info,
    Store as StoreIcon
} from 'lucide-vue-next';
import type { SharedProps } from '@/types';

defineProps<{
    title?: string;
}>();

const page = usePage<SharedProps>();
const sidebarOpen = ref(false);

const navigation = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { name: 'Produk', href: '/admin/produk', icon: Package },
    { name: 'Kategori', href: '/admin/kategori', icon: FolderTree },
    { name: 'Stok Cabang', href: '/admin/stok', icon: Boxes },
    { name: 'Transaksi', href: '/admin/transaksi', icon: Receipt },
    { name: 'Pesanan Online', href: '/admin/pesanan', icon: ShoppingBag },
    { name: 'Pelanggan', href: '/admin/pelanggan', icon: Users },
    { name: 'Kasir', href: '/admin/kasir', icon: UserCheck },
    { name: 'Cabang Toko', href: '/admin/cabang', icon: Store },
    { name: 'Laporan Penjualan', href: '/admin/laporan', icon: BarChart3 },
    { name: 'Pengaturan', href: '/admin/pengaturan', icon: Settings },
];

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col lg:flex-row">
        <!-- Mobile Sidebar Backdrop -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Left Sidebar Navigation -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 text-slate-300 flex flex-col transition-transform duration-300 ease-in-out lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Logo & Brand Header -->
            <div class="h-16 flex items-center justify-between px-6 bg-slate-950/60 border-b border-slate-800">
                <Link href="/admin/dashboard" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-500 to-blue-600 flex items-center justify-center text-white shadow-md shadow-sky-500/20">
                        <StoreIcon class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-lg font-bold tracking-tight text-white">MyShop</span>
                        <span class="block text-[10px] font-medium uppercase tracking-widest text-sky-400">Admin Panel</span>
                    </div>
                </Link>
                <button
                    class="lg:hidden text-slate-400 hover:text-white p-1"
                    @click="sidebarOpen = false"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-colors"
                    :class="[
                        $page.url.startsWith(item.href)
                            ? 'bg-sky-600 text-white font-semibold shadow-sm shadow-sky-600/30'
                            : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                    ]"
                >
                    <component :is="item.icon" class="w-5 h-5 shrink-0" />
                    <span>{{ item.name }}</span>
                </Link>
            </nav>

            <!-- Bottom User & Storefront Links -->
            <div class="p-4 border-t border-slate-800 bg-slate-950/40 space-y-2">
                <Link
                    href="/"
                    target="_blank"
                    class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-lg text-xs font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition"
                >
                    <Store class="w-4 h-4 text-sky-400" />
                    <span>Buka Toko Online</span>
                </Link>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center font-bold text-xs text-sky-400">
                            {{ page.props.auth.user?.nama_lengkap?.charAt(0) || 'A' }}
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-medium text-white truncate">{{ page.props.auth.user?.nama_lengkap }}</p>
                            <p class="text-[10px] text-slate-400 truncate">{{ page.props.auth.user?.email }}</p>
                        </div>
                    </div>
                    <button
                        @click="logout"
                        title="Keluar"
                        class="text-slate-400 hover:text-rose-400 p-1.5 rounded-lg hover:bg-slate-800 transition"
                    >
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <button
                        class="lg:hidden text-slate-600 hover:text-slate-900 p-2 rounded-lg hover:bg-slate-100"
                        @click="sidebarOpen = true"
                    >
                        <Menu class="w-6 h-6" />
                    </button>
                    <h1 class="text-lg font-bold text-slate-800">{{ title || 'Admin Dashboard' }}</h1>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded-full bg-sky-50 text-sky-700 text-xs font-medium border border-sky-100">
                        <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                        <span>Mode Administrasi</span>
                    </div>

                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-semibold text-slate-800">{{ page.props.auth.user?.nama_lengkap }}</p>
                        <p class="text-[11px] text-slate-500 capitalize">{{ page.props.auth.user?.role }}</p>
                    </div>

                    <button
                        @click="logout"
                        class="flex items-center gap-1.5 text-xs font-medium text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </div>
            </header>

            <!-- Flash Notifications -->
            <div v-if="page.props.flash.success || page.props.flash.error || page.props.flash.warning" class="px-4 lg:px-8 pt-4">
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

            <!-- Page Body -->
            <main class="flex-1 p-4 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>

