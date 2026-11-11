<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PelangganLayout from '@/layouts/PelangganLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { formatRupiah } from '@/lib/utils';
import {
    Search,
    ShoppingCart,
    Filter,
    X,
    ShoppingBag,
    Image as ImageIcon
} from 'lucide-vue-next';
import type { Produk, Kategori, Paginated } from '@/types';

const props = defineProps<{
    produk: Paginated<Produk & { stok: number }>;
    kategoriList: Kategori[];
    filters: {
        search?: string;
        kategori?: string;
        sort?: string;
    };
}>();

const search = ref(props.filters.search || '');
const selectedKategori = ref(props.filters.kategori || '');
const selectedSort = ref(props.filters.sort || 'terbaru');

const filterData = () => {
    router.get('/katalog', {
        search: search.value || undefined,
        kategori: selectedKategori.value || undefined,
        sort: selectedSort.value,
    }, { preserveState: true, replace: true });
};

const addToCart = (idProduk: number) => {
    router.post('/pelanggan/keranjang', {
        id_produk: idProduk,
        jumlah: 1,
    }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Katalog Produk" />

    <PelangganLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <!-- Header Title & Filter Bar -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900">Katalog Produk</h1>
                    <p class="text-xs text-slate-500">Semua produk berkualitas yang tersedia di MyShop</p>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-60">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                            v-model="search"
                            @keyup.enter="filterData"
                            type="text"
                            placeholder="Cari produk..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-sky-500"
                        />
                    </div>

                    <!-- Category Filter -->
                    <select
                        v-model="selectedKategori"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Kategori</option>
                        <option v-for="kat in kategoriList" :key="kat.id_kategori" :value="kat.id_kategori">
                            {{ kat.nama_kategori }}
                        </option>
                    </select>

                    <!-- Sort -->
                    <select
                        v-model="selectedSort"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="terbaru">Terbaru</option>
                        <option value="harga_terendah">Harga Terendah</option>
                        <option value="harga_tertinggi">Harga Tertinggi</option>
                        <option value="nama_az">Nama A - Z</option>
                        <option value="nama_za">Nama Z - A</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <div v-if="produk.data.length === 0" class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center text-slate-400">
                <ShoppingBag class="w-12 h-12 mx-auto text-slate-300 mb-2" />
                <h3 class="text-sm font-bold text-slate-700">Tidak ada produk yang cocok</h3>
                <p class="text-xs mt-1">Silakan coba kata kunci atau filter kategori lainnya.</p>
            </div>

            <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <div
                    v-for="p in produk.data"
                    :key="p.id_produk"
                    class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs hover:border-sky-500 hover:shadow-lg transition flex flex-col justify-between group"
                >
                    <div>
                        <!-- Product Image -->
                        <Link :href="`/produk/${p.slug}`" class="block relative h-48 bg-slate-100 overflow-hidden">
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
                                <h3 class="text-sm font-bold text-slate-900 hover:text-sky-600 transition line-clamp-2">
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

            <!-- Pagination -->
            <div class="pt-4 flex items-center justify-between text-xs text-slate-500">
                <span>Menampilkan {{ produk.from || 0 }} - {{ produk.to || 0 }} dari {{ produk.total }} produk</span>
                <Pagination :links="produk.links" />
            </div>
        </div>
    </PelangganLayout>
</template>

