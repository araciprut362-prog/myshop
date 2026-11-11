<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    Store,
    ShoppingBag,
    ShoppingCart,
    Search,
    User,
    LogOut,
    CheckCircle2,
    AlertCircle,
    Info,
    Menu,
    X,
    Phone,
    Mail,
    MapPin,
    ShieldCheck,
    Truck,
    RefreshCw,
    CreditCard
} from 'lucide-vue-next';
import type { SharedProps } from '@/types';

defineProps<{
    title?: string;
}>();

const page = usePage<SharedProps>();
const mobileMenuOpen = ref(false);
const searchQuery = ref('');

const search = () => {
    if (searchQuery.value.trim()) {
        router.get('/katalog', { search: searchQuery.value.trim() });
    }
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800">
        <!-- Top Announcement Bar -->
        <div class="bg-sky-950 text-sky-200 text-xs py-2 px-4 border-b border-sky-900/50">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-1 text-center sm:text-left">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sky-600 text-white uppercase tracking-wider">Info</span>
                    <span>Toko Online & POS Terintegrasi — Midtrans Payment Sandbox Aktif</span>
                </div>
                <div class="flex items-center gap-4 text-[11px] text-sky-300">
                    <span class="flex items-center gap-1"><Phone class="w-3 h-3 text-sky-400" /> 0352-123456</span>
                    <span class="flex items-center gap-1"><Mail class="w-3 h-3 text-sky-400" /> support@myshop.test</span>
                </div>
            </div>
        </div>

        <!-- Main Header / Navbar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="h-20 flex items-center justify-between gap-4">
                    <!-- Brand Logo -->
                    <Link href="/" class="flex items-center gap-3 shrink-0">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-sky-600 to-blue-700 flex items-center justify-center text-white shadow-md shadow-sky-600/20">
                            <Store class="w-6 h-6" />
                        </div>
                        <div>
                            <span class="text-2xl font-extrabold tracking-tight text-slate-900">MyShop</span>
                            <span class="block text-[10px] font-semibold text-sky-600 tracking-wider uppercase">Belanja Mudah & Cepat</span>
                        </div>
                    </Link>

                    <!-- Search Input (Desktop) -->
                    <div class="hidden md:flex flex-1 max-w-md mx-6">
                        <form @submit.prevent="search" class="relative w-full">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari beras, minyak, kopi, sabun..."
                                class="w-full pl-10 pr-24 py-2.5 rounded-full border border-slate-200 focus:outline-hidden focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm transition"
                            />
                            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
                            <button
                                type="submit"
                                class="absolute right-1.5 top-1.5 px-4 py-1.5 rounded-full bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold transition"
                            >
                                Cari
                            </button>
                        </form>
                    </div>

                    <!-- Navigation Links & User Actions -->
                    <div class="flex items-center gap-3">
                        <nav class="hidden lg:flex items-center gap-6 mr-2 text-sm font-medium">
                            <Link href="/" class="text-slate-600 hover:text-sky-600 transition" :class="{ 'text-sky-600 font-bold': $page.url === '/' }">Beranda</Link>
                            <Link href="/katalog" class="text-slate-600 hover:text-sky-600 transition" :class="{ 'text-sky-600 font-bold': $page.url.startsWith('/katalog') }">Semua Produk</Link>
                        </nav>

                        <!-- Cart Button -->
                        <Link
                            href="/pelanggan/keranjang"
                            class="relative p-2.5 text-slate-700 hover:text-sky-600 hover:bg-sky-50 rounded-full transition flex items-center justify-center"
                            title="Keranjang Belanja"
                        >
                            <ShoppingCart class="w-6 h-6" />
                            <span
                                v-if="page.props.cartCount > 0"
                                class="absolute -top-1 -right-1 bg-rose-500 text-white text-[11px] font-bold h-5 min-w-5 px-1.5 rounded-full flex items-center justify-center shadow-xs"
                            >
                                {{ page.props.cartCount }}
                            </span>
                        </Link>

                        <!-- User Auth Menu -->
                        <template v-if="page.props.auth.user">
                            <div class="hidden sm:flex items-center gap-2 pl-2 border-l border-slate-200">
                                <!-- Role Shortcut -->
                                <Link
                                    v-if="page.props.auth.user.role === 'admin'"
                                    href="/admin/dashboard"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100 transition"
                                >
                                    Panel Admin
                                </Link>
                                <Link
                                    v-else-if="page.props.auth.user.role === 'kasir'"
                                    href="/kasir/pos"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition"
                                >
                                    POS Kasir
                                </Link>
                                <Link
                                    v-else
                                    href="/pelanggan/pesanan"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition"
                                >
                                    Pesanan Saya
                                </Link>

                                <button
                                    @click="logout"
                                    title="Logout"
                                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                >
                                    <LogOut class="w-4 h-4" />
                                </button>
                            </div>
                        </template>
                        <template v-else>
                            <div class="hidden sm:flex items-center gap-2 pl-2 border-l border-slate-200">
                                <Link
                                    href="/login"
                                    class="px-4 py-2 text-xs font-semibold text-slate-700 hover:text-sky-600 transition"
                                >
                                    Masuk
                                </Link>
                                <Link
                                    href="/register"
                                    class="px-4 py-2 text-xs font-semibold bg-sky-600 hover:bg-sky-700 text-white rounded-xl shadow-xs transition"
                                >
                                    Daftar
                                </Link>
                            </div>
                        </template>

                        <!-- Mobile Menu Toggle -->
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="lg:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100"
                        >
                            <Menu v-if="!mobileMenuOpen" class="w-6 h-6" />
                            <X v-else class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <!-- Mobile Search & Menu -->
                <div v-show="mobileMenuOpen" class="lg:hidden py-4 border-t border-slate-100 space-y-3">
                    <form @submit.prevent="search" class="relative w-full">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari produk..."
                            class="w-full pl-10 pr-20 py-2 rounded-xl border border-slate-200 text-sm"
                        />
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                        <button
                            type="submit"
                            class="absolute right-1 top-1 px-3 py-1.5 rounded-lg bg-sky-600 text-white text-xs font-semibold"
                        >
                            Cari
                        </button>
                    </form>

                    <nav class="flex flex-col space-y-1 pt-2">
                        <Link href="/" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">Beranda</Link>
                        <Link href="/katalog" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">Semua Produk</Link>
                        
                        <template v-if="page.props.auth.user">
                            <Link v-if="page.props.auth.user.role === 'admin'" href="/admin/dashboard" class="px-3 py-2 rounded-lg text-sm font-medium text-sky-600 bg-sky-50">Panel Admin</Link>
                            <Link v-else-if="page.props.auth.user.role === 'kasir'" href="/kasir/pos" class="px-3 py-2 rounded-lg text-sm font-medium text-emerald-600 bg-emerald-50">POS Kasir</Link>
                            <Link v-else href="/pelanggan/pesanan" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100">Pesanan Saya</Link>
                            <button @click="logout" class="text-left px-3 py-2 rounded-lg text-sm font-medium text-rose-600 hover:bg-rose-50">Logout</button>
                        </template>
                        <template v-else>
                            <div class="pt-2 flex gap-2">
                                <Link href="/login" class="flex-1 py-2 text-center text-xs font-semibold text-slate-700 border border-slate-200 rounded-lg">Masuk</Link>
                                <Link href="/register" class="flex-1 py-2 text-center text-xs font-semibold bg-sky-600 text-white rounded-lg">Daftar</Link>
                            </div>
                        </template>
                    </nav>
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

        <!-- Main Content Slot -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-slate-900 text-slate-300 mt-16 border-t border-slate-800">
            <!-- Store Perks / Guarantees -->
            <div class="border-b border-slate-800 py-8 bg-slate-950/40">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-sky-500/10 text-sky-400 flex items-center justify-center shrink-0 border border-sky-500/20">
                            <Truck class="w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-white">Pengiriman Cepat</h4>
                            <p class="text-xs text-slate-400">Pengiriman langsung dari cabang toko terdekat.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/20">
                            <ShieldCheck class="w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-white">Produk Asli & Segar</h4>
                            <p class="text-xs text-slate-400">Jaminan mutu terbaik kebutuhan pokok Anda.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-500/20">
                            <CreditCard class="w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-white">Midtrans Snap Sandbox</h4>
                            <p class="text-xs text-slate-400">Simulasi pembayaran QRIS, Virtual Account & Bank.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center shrink-0 border border-amber-500/20">
                            <Store class="w-6 h-6" />
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-white">4 Cabang Toko</h4>
                            <p class="text-xs text-slate-400">Ponorogo, Madiun, Jakarta, dan Bandung.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Columns -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand Info -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-sky-600 flex items-center justify-center text-white">
                            <Store class="w-5 h-5" />
                        </div>
                        <span class="text-xl font-bold text-white">MyShop</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Sistem informasi penjualan toko modern yang mengintegrasikan pesanan toko online dan sistem Point of Sale (POS) kasir secara real-time.
                    </p>
                    <div class="text-xs text-slate-400 space-y-1">
                        <p class="flex items-center gap-2"><MapPin class="w-3.5 h-3.5 text-sky-400" /> Jl. Diponegoro No. 10, Ponorogo</p>
                        <p class="flex items-center gap-2"><Phone class="w-3.5 h-3.5 text-sky-400" /> 0352-123456</p>
                    </div>
                </div>

                <!-- Fast Links -->
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Navigasi</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><Link href="/" class="hover:text-white transition">Beranda</Link></li>
                        <li><Link href="/katalog" class="hover:text-white transition">Katalog Produk</Link></li>
                        <li><Link href="/pelanggan/keranjang" class="hover:text-white transition">Keranjang Belanja</Link></li>
                        <li><Link href="/login" class="hover:text-white transition">Login Akun</Link></li>
                        <li><Link href="/register" class="hover:text-white transition">Registrasi Pelanggan</Link></li>
                    </ul>
                </div>

                <!-- Cabang Toko -->
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Cabang Toko Kami</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><strong>Cabang Utama:</strong> Ponorogo</li>
                        <li><strong>Cabang Madiun:</strong> Jl. Pahlawan No. 21</li>
                        <li><strong>Cabang Jakarta:</strong> Jl. Sudirman No. 15</li>
                        <li><strong>Cabang Bandung:</strong> Jl. Asia Afrika No. 8</li>
                    </ul>
                </div>

                <!-- Role Access -->
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Akses Sistem</h4>
                    <p class="text-xs text-slate-400 mb-3">
                        Gunakan akun demo untuk menguji ketiga role aplikasi:
                    </p>
                    <div class="space-y-2 text-xs">
                        <div class="bg-slate-800 p-2 rounded-lg border border-slate-700">
                            <span class="text-sky-400 font-bold block">Admin:</span>
                            <span class="text-slate-300">admin@myshop.test / password</span>
                        </div>
                        <div class="bg-slate-800 p-2 rounded-lg border border-slate-700">
                            <span class="text-emerald-400 font-bold block">Kasir:</span>
                            <span class="text-slate-300">kasir.utama@myshop.test / password</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright -->
            <div class="border-t border-slate-800 py-6 text-center text-xs text-slate-500">
                <p>&copy; 2026 MyShop. Seluruh hak cipta dilindungi. Sistem Informasi Penjualan Toko Laravel + Vue + MySQL.</p>
            </div>
        </footer>
    </div>
</template>

