<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PelangganLayout from '@/layouts/PelangganLayout.vue';
import { formatRupiah } from '@/lib/utils';
import {
    ShoppingBag,
    ShoppingCart,
    ArrowRight,
    Star,
    Sparkles,
    CheckCircle2,
    Truck,
    ShieldCheck,
    Store,
    Image as ImageIcon
} from 'lucide-vue-next';
import type { Produk, Kategori } from '@/types';

defineProps<{
    kategoriList: (Kategori & { produk_count: number })[];
    produkTerbaru: (Produk & { stok: number })[];
    produkUnggulan: (Produk & { stok: number })[];
}>();

const addToCart = (idProduk: number) => {
    router.post('/pelanggan/keranjang', {
        id_produk: idProduk,
        jumlah: 1,
    }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Toko Online Kebutuhan Harian" />

    <PelangganLayout>
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-r from-sky-900 via-sky-800 to-slate-900 text-white overflow-hidden py-16 sm:py-24">
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:16px_16px]"></div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col lg:flex-row items-center justify-between gap-12">
                <div class="max-w-2xl text-center lg:text-left space-y-5">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-sky-500/20 text-sky-300 text-xs font-semibold border border-sky-400/30">
                        <Sparkles class="w-4 h-4 text-sky-400" />
                        <span>Sistem Belanja Modern & Terpercaya</span>
                    </div>

                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight">
                        Belanja Mudah, <br class="hidden sm:inline" />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-400 to-teal-300">Cepat & Nyaman</span>
                    </h1>

                    <p class="text-sm sm:text-base text-sky-100 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                        Penuhi kebutuhan sembako, minuman, makanan ringan, dan perlengkapan rumah tangga langsung dari cabang terdekat dengan pembayaran aman Midtrans Snap.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 pt-2">
                        <Link
                            href="/katalog"
                            class="w-full sm:w-auto flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-extrabold text-sm shadow-lg shadow-sky-500/25 transition transform hover:-translate-y-0.5"
                        >
                            <ShoppingBag class="w-4 h-4" />
                            <span>Mulai Belanja</span>
                        </Link>
                        
                        <Link
                            href="/login"
                            class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-sm border border-white/20 transition"
                        >
                            <span>Masuk / Akses POS</span>
                        </Link>
                    </div>
                </div>

                <!-- Hero Graphic Card -->
                <div class="w-full max-w-sm bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/20 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Store class="w-5 h-5 text-sky-400" />
                            <span class="font-bold text-sm text-white">MyShop Fulfillment</span>
                        </div>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            Stok Real-Time
                        </span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900/60 border border-white/10 space-y-2 text-xs">
                        <p class="text-slate-300 flex items-center justify-between">
                            <span>Sistem E-Commerce:</span>
                            <span class="font-bold text-white">Laravel + Vue + Inertia</span>
                        </p>
                        <p class="text-slate-300 flex items-center justify-between">
                            <span>Point of Sale (POS):</span>
                            <span class="font-bold text-emerald-400">Kasir Aktif Multi-Cabang</span>
                        </p>
                        <p class="text-slate-300 flex items-center justify-between">
                            <span>Payment Sandbox:</span>
                            <span class="font-bold text-sky-400">Midtrans Snap Ready</span>
                        </p>
                    </div>

                    <div class="pt-2 flex items-center justify-between text-xs text-slate-300">
                        <span class="flex items-center gap-1"><Truck class="w-3.5 h-3.5 text-sky-400" /> Cabang Utama Ponorogo</span>
                        <span class="font-semibold text-white">4 Cabang Aktif</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Category Showcase -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Kategori Belanja Pilihan</h2>
                    <p class="text-xs text-slate-500">Temukan barang kebutuhan Anda berdasarkan kelompok kategori</p>
                </div>
                <Link href="/katalog" class="text-xs font-bold text-sky-600 hover:text-sky-700 flex items-center gap-1">
                    Lihat Semua <ArrowRight class="w-3.5 h-3.5" />
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <Link
                    v-for="kat in kategoriList"
                    :key="kat.id_kategori"
                    :href="`/katalog?kategori=${kat.id_kategori}`"
                    class="bg-white p-4 rounded-2xl border border-slate-200/80 hover:border-sky-500 hover:shadow-md transition text-center group"
                >
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                        <Store class="w-6 h-6" />
                    </div>
                    <h3 class="text-xs font-bold text-slate-900 group-hover:text-sky-600 transition">{{ kat.nama_kategori }}</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ kat.produk_count }} Produk</p>
                </Link>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Produk Paling Populer</h2>
                    <p class="text-xs text-slate-500">Paling banyak dicari pelanggan MyShop minggu ini</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <div
                    v-for="p in produkUnggulan"
                    :key="p.id_produk"
                    class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs hover:border-sky-500 hover:shadow-lg transition flex flex-col justify-between group"
                >
                    <div>
                        <!-- Product Image -->
                        <Link :href="`/produk/${p.slug}`" class="block relative h-44 bg-slate-100 overflow-hidden">
                            <img
                                v-if="p.gambar"
                                :src="p.gambar.startsWith('http') ? p.gambar : `/${p.gambar}`"
                                :alt="p.nama_produk"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                <ImageIcon class="w-10 h-10" />
                            </div>

                            <span
                                class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded-full text-[10px] font-bold shadow-xs"
                                :class="p.stok <= 0 ? 'bg-rose-500 text-white' : 'bg-slate-900/80 text-white'"
                            >
                                {{ p.stok <= 0 ? 'Stok Habis' : `Stok: ${p.stok}` }}
                            </span>
                        </Link>

                        <div class="p-4">
                            <span class="text-[10px] font-semibold text-sky-600 uppercase tracking-wider block mb-0.5">
                                {{ p.kategori?.nama_kategori }}
                            </span>
                            <Link :href="`/produk/${p.slug}`">
                                <h3 class="text-sm font-bold text-slate-900 hover:text-sky-600 transition line-clamp-1">
                                    {{ p.nama_produk }}
                                </h3>
                            </Link>
                            <p class="text-xs text-slate-400 line-clamp-1 mt-1">{{ p.deskripsi || '-' }}</p>
                        </div>
                    </div>

                    <div class="p-4 pt-0 flex items-center justify-between border-t border-slate-100 mt-2">
                        <div>
                            <span class="text-[10px] text-slate-400 block">Harga</span>
                            <span class="text-sm font-black text-slate-900">{{ formatRupiah(p.harga) }}</span>
                        </div>

                        <button
                            @click="addToCart(p.id_produk)"
                            :disabled="p.stok <= 0"
                            class="p-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white shadow-xs transition disabled:opacity-40 disabled:cursor-not-allowed"
                            title="Tambah ke Keranjang"
                        >
                            <ShoppingCart class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- New Arrivals Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Produk Terbaru</h2>
                    <p class="text-xs text-slate-500">Pasokan segar kebutuhan harian baru masuk gudang</p>
                </div>
                <Link href="/katalog" class="text-xs font-bold text-sky-600 hover:text-sky-700 flex items-center gap-1">
                    Lihat Semua Katalog <ArrowRight class="w-3.5 h-3.5" />
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <div
                    v-for="p in produkTerbaru"
                    :key="p.id_produk"
                    class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs hover:border-sky-500 hover:shadow-lg transition flex flex-col justify-between group"
                >
                    <div>
                        <Link :href="`/produk/${p.slug}`" class="block relative h-44 bg-slate-100 overflow-hidden">
                            <img
                                v-if="p.gambar"
                                :src="p.gambar.startsWith('http') ? p.gambar : `/${p.gambar}`"
                                :alt="p.nama_produk"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                <ImageIcon class="w-10 h-10" />
                            </div>

                            <span
                                class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded-full text-[10px] font-bold shadow-xs"
                                :class="p.stok <= 0 ? 'bg-rose-500 text-white' : 'bg-slate-900/80 text-white'"
                            >
                                {{ p.stok <= 0 ? 'Habis' : `Stok: ${p.stok}` }}
                            </span>
                        </Link>

                        <div class="p-4">
                            <span class="text-[10px] font-semibold text-sky-600 uppercase tracking-wider block mb-0.5">
                                {{ p.kategori?.nama_kategori }}
                            </span>
                            <Link :href="`/produk/${p.slug}`">
                                <h3 class="text-sm font-bold text-slate-900 hover:text-sky-600 transition line-clamp-1">
                                    {{ p.nama_produk }}
                                </h3>
                            </Link>
                            <p class="text-xs text-slate-400 line-clamp-1 mt-1">{{ p.deskripsi || '-' }}</p>
                        </div>
                    </div>

                    <div class="p-4 pt-0 flex items-center justify-between border-t border-slate-100 mt-2">
                        <div>
                            <span class="text-[10px] text-slate-400 block">Harga</span>
                            <span class="text-sm font-black text-slate-900">{{ formatRupiah(p.harga) }}</span>
                        </div>

                        <button
                            @click="addToCart(p.id_produk)"
                            :disabled="p.stok <= 0"
                            class="p-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white shadow-xs transition disabled:opacity-40 disabled:cursor-not-allowed"
                            title="Tambah ke Keranjang"
                        >
                            <ShoppingCart class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </PelangganLayout>
</template>

