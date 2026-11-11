<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PelangganLayout from '@/layouts/PelangganLayout.vue';
import { formatRupiah } from '@/lib/utils';
import {
    ShoppingCart,
    ShoppingBag,
    Plus,
    Minus,
    CheckCircle2,
    Truck,
    ShieldCheck,
    Store,
    ArrowLeft,
    Image as ImageIcon
} from 'lucide-vue-next';
import type { Produk } from '@/types';

const props = defineProps<{
    produk: Produk & { stok: number };
    produkTerkait: (Produk & { stok: number })[];
}>();

const jumlah = ref(1);

const increment = () => {
    if (jumlah.value < props.produk.stok) {
        jumlah.value++;
    }
};

const decrement = () => {
    if (jumlah.value > 1) {
        jumlah.value--;
    }
};

const addToCart = () => {
    router.post('/pelanggan/keranjang', {
        id_produk: props.produk.id_produk,
        jumlah: jumlah.value,
    }, { preserveScroll: true });
};

const buyNow = () => {
    router.post('/pelanggan/keranjang', {
        id_produk: props.produk.id_produk,
        jumlah: jumlah.value,
    }, {
        onSuccess: () => {
            router.visit('/pelanggan/keranjang');
        }
    });
};
</script>

<template>
    <Head :title="`${produk.nama_produk} - MyShop`" />

    <PelangganLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12">
            <!-- Breadcrumb Navigation -->
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <Link href="/" class="hover:text-slate-800">Beranda</Link>
                <span>/</span>
                <Link href="/katalog" class="hover:text-slate-800">Katalog</Link>
                <span>/</span>
                <Link :href="`/katalog?kategori=${produk.id_kategori}`" class="hover:text-slate-800">{{ produk.kategori?.nama_kategori }}</Link>
                <span>/</span>
                <span class="text-slate-900 font-semibold truncate">{{ produk.nama_produk }}</span>
            </div>

            <!-- Product Showcase Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                <!-- Large Image Display -->
                <div class="relative w-full h-80 sm:h-96 rounded-2xl bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center">
                    <img
                        v-if="produk.gambar"
                        :src="produk.gambar.startsWith('http') ? produk.gambar : `/${produk.gambar}`"
                        :alt="produk.nama_produk"
                        class="w-full h-full object-cover"
                    />
                    <ImageIcon v-else class="w-16 h-16 text-slate-300" />

                    <span
                        class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-bold shadow-xs"
                        :class="produk.stok <= 0 ? 'bg-rose-500 text-white' : 'bg-slate-900/80 text-white'"
                    >
                        {{ produk.stok <= 0 ? 'Stok Habis' : `Stok Tersedia: ${produk.stok}` }}
                    </span>
                </div>

                <!-- Product Information & Action Panel -->
                <div class="flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-100">
                            {{ produk.kategori?.nama_kategori }}
                        </span>

                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            {{ produk.nama_produk }}
                        </h1>

                        <p class="text-xs font-mono text-slate-400">SKU: {{ produk.sku }}</p>

                        <!-- Price Tag -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <span class="text-xs text-slate-500 block">Harga Spesial</span>
                            <span class="text-3xl font-black text-slate-900">{{ formatRupiah(produk.harga) }}</span>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Deskripsi Produk</h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                {{ produk.deskripsi || 'Tidak ada keterangan tambahan untuk produk ini.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Quantity & Add to Cart Controls -->
                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-bold text-slate-700">Jumlah Beli:</span>
                            <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                                <button
                                    @click="decrement"
                                    class="p-2.5 hover:bg-slate-200 text-slate-600 transition"
                                >
                                    <Minus class="w-4 h-4" />
                                </button>
                                <span class="w-12 text-center text-sm font-bold text-slate-900">{{ jumlah }}</span>
                                <button
                                    @click="increment"
                                    :disabled="jumlah >= produk.stok"
                                    class="p-2.5 hover:bg-slate-200 text-slate-600 transition disabled:opacity-40"
                                >
                                    <Plus class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <button
                                @click="addToCart"
                                :disabled="produk.stok <= 0"
                                class="w-full sm:flex-1 py-3.5 px-6 rounded-2xl border-2 border-sky-600 text-sky-600 hover:bg-sky-50 text-sm font-bold transition flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                <ShoppingCart class="w-4 h-4" />
                                <span>Tambah ke Keranjang</span>
                            </button>

                            <button
                                @click="buyNow"
                                :disabled="produk.stok <= 0"
                                class="w-full sm:flex-1 py-3.5 px-6 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white text-sm font-extrabold shadow-md shadow-sky-600/30 transition flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                <ShoppingBag class="w-4 h-4" />
                                <span>Beli Sekarang</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <div v-if="produkTerkait.length > 0" class="space-y-6">
                <h3 class="text-lg font-bold text-slate-900">Produk Terkait Lainnya</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div
                        v-for="p in produkTerkait"
                        :key="p.id_produk"
                        class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs hover:border-sky-500 transition group"
                    >
                        <Link :href="`/produk/${p.slug}`" class="block h-36 bg-slate-100 overflow-hidden">
                            <img
                                v-if="p.gambar"
                                :src="p.gambar.startsWith('http') ? p.gambar : `/${p.gambar}`"
                                :alt="p.nama_produk"
                                class="w-full h-full object-cover group-hover:scale-105 transition"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                <ImageIcon class="w-8 h-8" />
                            </div>
                        </Link>
                        <div class="p-3">
                            <Link :href="`/produk/${p.slug}`">
                                <h4 class="text-xs font-bold text-slate-900 line-clamp-1 hover:text-sky-600">{{ p.nama_produk }}</h4>
                            </Link>
                            <span class="text-xs font-black text-slate-900 block mt-1">{{ formatRupiah(p.harga) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PelangganLayout>
</template>

