<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import KasirLayout from '@/layouts/KasirLayout.vue';
import { formatRupiah } from '@/lib/utils';
import {
    Search,
    Plus,
    Minus,
    Trash2,
    ShoppingBag,
    CreditCard,
    DollarSign,
    QrCode,
    User,
    Store,
    Printer,
    CheckCircle2,
    RotateCcw,
    X,
    Image as ImageIcon
} from 'lucide-vue-next';
import type { Produk, Kategori, Pelanggan, Cabang } from '@/types';

const props = defineProps<{
    cabang: Cabang;
    produkList: (Produk & { stok: number; stok_minimum: number })[];
    kategoriList: Kategori[];
    pelangganList: Pelanggan[];
    pajakPersen: number;
}>();

// Filter & Search
const searchQuery = ref('');
const selectedKategori = ref<number | null>(null);

const filteredProduk = computed(() => {
    return props.produkList.filter(p => {
        const matchSearch = !searchQuery.value ||
            p.nama_produk.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            p.sku.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchKat = !selectedKategori.value || p.id_kategori === selectedKategori.value;
        return matchSearch && matchKat;
    });
});

// Cart State
interface CartItem {
    id_produk: number;
    nama_produk: string;
    sku: string;
    harga: number;
    stok_tersedia: number;
    jumlah: number;
}

const cart = ref<CartItem[]>([]);
const selectedPelangganId = ref<number | null>(null);
const diskon = ref<number>(0);
const metodePembayaran = ref<'TUNAI' | 'TRANSFER_BANK' | 'QRIS'>('TUNAI');
const uangDiterima = ref<number>(0);
const catatan = ref('');
const isSubmitting = ref(false);
const errorMessage = ref('');

// Calculations
const subtotal = computed(() => {
    return cart.value.reduce((acc, item) => acc + (item.harga * item.jumlah), 0);
});

const nilaiDiskon = computed(() => {
    return Math.max(0, Number(diskon.value) || 0);
});

const nilaiPajak = computed(() => {
    const taxable = Math.max(0, subtotal.value - nilaiDiskon.value);
    return Math.round((taxable * props.pajakPersen) / 100);
});

const totalBelanja = computed(() => {
    return Math.max(0, subtotal.value - nilaiDiskon.value + nilaiPajak.value);
});

const kembalian = computed(() => {
    if (metodePembayaran.value !== 'TUNAI') return 0;
    return Math.max(0, (uangDiterima.value || 0) - totalBelanja.value);
});

// Cart Actions
const addToCart = (p: Produk & { stok: number }) => {
    if (p.stok <= 0) return;

    const existing = cart.value.find(item => item.id_produk === p.id_produk);
    if (existing) {
        if (existing.jumlah < p.stok) {
            existing.jumlah++;
        }
    } else {
        cart.value.push({
            id_produk: p.id_produk,
            nama_produk: p.nama_produk,
            sku: p.sku,
            harga: Number(p.harga),
            stok_tersedia: p.stok,
            jumlah: 1,
        });
    }

    if (metodePembayaran.value === 'TUNAI' && uangDiterima.value < totalBelanja.value) {
        uangDiterima.value = totalBelanja.value;
    }
};

const incrementItem = (item: CartItem) => {
    if (item.jumlah < item.stok_tersedia) {
        item.jumlah++;
    }
};

const decrementItem = (item: CartItem) => {
    if (item.jumlah > 1) {
        item.jumlah--;
    } else {
        removeFromCart(item.id_produk);
    }
};

const removeFromCart = (idProduk: number) => {
    cart.value = cart.value.filter(i => i.id_produk !== idProduk);
};

const clearCart = () => {
    cart.value = [];
    diskon.value = 0;
    uangDiterima.value = 0;
};

const setQuickCash = (amount: number) => {
    uangDiterima.value = amount;
};

// Submit POS Transaction
const submitTransaction = () => {
    errorMessage.value = '';

    if (cart.value.length === 0) {
        errorMessage.value = 'Keranjang transaksi masih kosong.';
        return;
    }

    if (metodePembayaran.value === 'TUNAI' && (uangDiterima.value || 0) < totalBelanja.value) {
        errorMessage.value = 'Uang yang diterima kurang dari total belanja.';
        return;
    }

    isSubmitting.value = true;

    router.post('/kasir/pos', {
        items: cart.value.map(i => ({ id_produk: i.id_produk, jumlah: i.jumlah })),
        metode_pembayaran: metodePembayaran.value,
        id_pelanggan: selectedPelangganId.value || undefined,
        uang_diterima: metodePembayaran.value === 'TUNAI' ? uangDiterima.value : totalBelanja.value,
        diskon: nilaiDiskon.value,
        catatan: catatan.value || undefined,
    }, {
        onError: (errors) => {
            errorMessage.value = Object.values(errors)[0] as string || 'Terjadi kesalahan saat memproses transaksi.';
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <Head title="Point of Sale (POS Kasir)" />

    <KasirLayout title="Point of Sale (POS)">
        <div class="flex-1 flex flex-col lg:flex-row h-[calc(100vh-4rem)] overflow-hidden">
            <!-- Left Side: Products Catalog & Search -->
            <div class="flex-1 flex flex-col p-4 sm:p-6 overflow-hidden bg-slate-100">
                <!-- Search & Category Filters -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs mb-4 space-y-3 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Scan barcode / cari nama produk atau SKU..."
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-3 top-3 text-slate-400 hover:text-slate-600"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Category Chips -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-full">
                        <button
                            @click="selectedKategori = null"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold shrink-0 transition"
                            :class="selectedKategori === null ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        >
                            Semua Kategori
                        </button>
                        <button
                            v-for="kat in kategoriList"
                            :key="kat.id_kategori"
                            @click="selectedKategori = kat.id_kategori"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold shrink-0 transition"
                            :class="selectedKategori === kat.id_kategori ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        >
                            {{ kat.nama_kategori }}
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="flex-1 overflow-y-auto pr-1">
                    <div v-if="filteredProduk.length === 0" class="h-64 flex flex-col items-center justify-center text-slate-400 text-center">
                        <ShoppingBag class="w-12 h-12 text-slate-300 mb-2" />
                        <p class="text-sm font-semibold">Tidak ada produk yang ditemukan.</p>
                        <p class="text-xs">Coba kata kunci pencarian lain atau pilih kategori berbeda.</p>
                    </div>

                    <div v-else class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                        <button
                            v-for="prod in filteredProduk"
                            :key="prod.id_produk"
                            @click="addToCart(prod)"
                            :disabled="prod.stok <= 0"
                            class="bg-white p-3.5 rounded-2xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-md transition text-left flex flex-col justify-between group disabled:opacity-50 disabled:cursor-not-allowed select-none"
                        >
                            <div>
                                <!-- Image Preview & Stock Badge -->
                                <div class="relative w-full h-28 rounded-xl bg-slate-100 border border-slate-100 mb-2.5 overflow-hidden flex items-center justify-center">
                                    <img
                                        v-if="prod.gambar"
                                        :src="prod.gambar.startsWith('http') ? prod.gambar : `/${prod.gambar}`"
                                        :alt="prod.nama_produk"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                    <ImageIcon v-else class="w-8 h-8 text-slate-300" />

                                    <span
                                        class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-bold shadow-xs"
                                        :class="prod.stok <= 0 ? 'bg-rose-500 text-white' : prod.stok <= 5 ? 'bg-amber-500 text-white' : 'bg-slate-900/80 text-white'"
                                    >
                                        {{ prod.stok <= 0 ? 'Habis' : `Stok: ${prod.stok}` }}
                                    </span>
                                </div>

                                <span class="text-[10px] font-mono text-slate-400 block">{{ prod.sku }}</span>
                                <h4 class="text-xs font-bold text-slate-900 line-clamp-2 leading-tight group-hover:text-emerald-700 transition">
                                    {{ prod.nama_produk }}
                                </h4>
                            </div>

                            <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-xs font-extrabold text-emerald-600">
                                    {{ formatRupiah(prod.harga) }}
                                </span>
                                <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition">
                                    <Plus class="w-3.5 h-3.5" />
                                </span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Side: POS Transaction Cart Drawer -->
            <div class="w-full lg:w-96 xl:w-[420px] bg-white border-l border-slate-200 flex flex-col h-full shadow-lg shrink-0">
                <!-- Cart Header -->
                <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                            <ShoppingBag class="w-4 h-4 text-emerald-600" />
                            <span>Keranjang Transaksi</span>
                        </h3>
                        <p class="text-[11px] text-slate-500">{{ cabang.nama_cabang }} &bull; {{ cart.length }} Item</p>
                    </div>
                    <button
                        v-if="cart.length > 0"
                        @click="clearCart"
                        class="text-xs text-rose-600 hover:text-rose-700 font-semibold p-1.5 hover:bg-rose-50 rounded-lg transition"
                        title="Kosongkan Keranjang"
                    >
                        Reset
                    </button>
                </div>

                <!-- Customer Selection -->
                <div class="p-3 border-b border-slate-100 bg-slate-50/30">
                    <label class="block text-[11px] font-bold text-slate-600 mb-1 flex items-center gap-1">
                        <User class="w-3.5 h-3.5 text-slate-400" />
                        <span>Pelanggan</span>
                    </label>
                    <select
                        v-model="selectedPelangganId"
                        class="w-full text-xs py-1.5 px-2.5 rounded-xl border border-slate-200 bg-white"
                    >
                        <option :value="null">-- Pelanggan Umum (Tanpa Akun) --</option>
                        <option v-for="pel in pelangganList" :key="pel.id_pelanggan" :value="pel.id_pelanggan">
                            {{ pel.nama_pelanggan }} ({{ pel.kota || 'Umum' }})
                        </option>
                    </select>
                </div>

                <!-- Cart Items List (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-3 space-y-2">
                    <div v-if="cart.length === 0" class="h-48 flex flex-col items-center justify-center text-slate-400 text-center">
                        <ShoppingBag class="w-8 h-8 text-slate-300 mb-2" />
                        <p class="text-xs font-semibold">Keranjang masih kosong</p>
                        <p class="text-[10px]">Klik produk di sebelah kiri untuk menambahkan</p>
                    </div>

                    <div
                        v-for="item in cart"
                        :key="item.id_produk"
                        class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/50 flex items-center justify-between gap-2"
                    >
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-800 truncate">{{ item.nama_produk }}</p>
                            <p class="text-[10px] text-slate-500 font-mono">{{ formatRupiah(item.harga) }} / unit</p>
                            <p class="text-xs font-extrabold text-emerald-600 mt-0.5">{{ formatRupiah(item.harga * item.jumlah) }}</p>
                        </div>

                        <!-- Qty Controls -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button
                                @click="decrementItem(item)"
                                class="w-6 h-6 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600"
                            >
                                <Minus class="w-3 h-3" />
                            </button>
                            <span class="w-7 text-center font-bold text-xs text-slate-900">{{ item.jumlah }}</span>
                            <button
                                @click="incrementItem(item)"
                                :disabled="item.jumlah >= item.stok_tersedia"
                                class="w-6 h-6 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600 disabled:opacity-30"
                            >
                                <Plus class="w-3 h-3" />
                            </button>
                            <button
                                @click="removeFromCart(item.id_produk)"
                                class="w-6 h-6 rounded-lg text-rose-500 hover:bg-rose-50 flex items-center justify-center ml-1"
                            >
                                <Trash2 class="w-3 h-3" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cart Calculations & Payment Area -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-3 shrink-0 text-xs">
                    <!-- Error notification if any -->
                    <p v-if="errorMessage" class="text-rose-600 text-xs font-semibold bg-rose-50 p-2 rounded-lg border border-rose-200">
                        {{ errorMessage }}
                    </p>

                    <!-- Subtotal, Diskon, Pajak -->
                    <div class="space-y-1.5 text-slate-600">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span class="font-bold text-slate-900">{{ formatRupiah(subtotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Diskon (Rp):</span>
                            <input
                                v-model.number="diskon"
                                type="number"
                                min="0"
                                class="w-24 text-right px-2 py-0.5 rounded-lg border border-slate-200 bg-white text-xs font-bold"
                            />
                        </div>
                        <div class="flex justify-between">
                            <span>Pajak Simulasi ({{ pajakPersen }}%):</span>
                            <span class="font-bold text-slate-900">{{ formatRupiah(nilaiPajak) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-black text-slate-900 pt-2 border-t border-slate-200">
                            <span>TOTAL:</span>
                            <span class="text-emerald-700">{{ formatRupiah(totalBelanja) }}</span>
                        </div>
                    </div>

                    <!-- Payment Method Tabs -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Metode Pembayaran</label>
                        <div class="grid grid-cols-3 gap-1.5">
                            <button
                                type="button"
                                @click="metodePembayaran = 'TUNAI'; uangDiterima = totalBelanja"
                                class="py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1"
                                :class="metodePembayaran === 'TUNAI' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200'"
                            >
                                <DollarSign class="w-3.5 h-3.5" />
                                <span>Tunai</span>
                            </button>
                            <button
                                type="button"
                                @click="metodePembayaran = 'TRANSFER_BANK'"
                                class="py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1"
                                :class="metodePembayaran === 'TRANSFER_BANK' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200'"
                            >
                                <CreditCard class="w-3.5 h-3.5" />
                                <span>Transfer</span>
                            </button>
                            <button
                                type="button"
                                @click="metodePembayaran = 'QRIS'"
                                class="py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1"
                                :class="metodePembayaran === 'QRIS' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200'"
                            >
                                <QrCode class="w-3.5 h-3.5" />
                                <span>QRIS</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tender & Change for Cash (TUNAI) -->
                    <div v-if="metodePembayaran === 'TUNAI'" class="space-y-2 pt-1">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-[11px] font-bold text-slate-600 shrink-0">Uang Diterima:</label>
                            <input
                                v-model.number="uangDiterima"
                                type="number"
                                min="0"
                                step="1000"
                                class="flex-1 text-right px-3 py-1.5 rounded-xl border border-slate-300 text-sm font-extrabold text-slate-900 bg-white"
                            />
                        </div>

                        <!-- Quick Cash Shortcuts -->
                        <div class="grid grid-cols-4 gap-1">
                            <button
                                type="button"
                                @click="setQuickCash(totalBelanja)"
                                class="py-1 px-1 text-[10px] font-bold rounded-lg bg-white border border-slate-200 hover:bg-slate-100"
                            >
                                Pas
                            </button>
                            <button
                                type="button"
                                @click="setQuickCash(50000)"
                                class="py-1 px-1 text-[10px] font-bold rounded-lg bg-white border border-slate-200 hover:bg-slate-100"
                            >
                                50k
                            </button>
                            <button
                                type="button"
                                @click="setQuickCash(100000)"
                                class="py-1 px-1 text-[10px] font-bold rounded-lg bg-white border border-slate-200 hover:bg-slate-100"
                            >
                                100k
                            </button>
                            <button
                                type="button"
                                @click="setQuickCash(200000)"
                                class="py-1 px-1 text-[10px] font-bold rounded-lg bg-white border border-slate-200 hover:bg-slate-100"
                            >
                                200k
                            </button>
                        </div>

                        <div class="flex items-center justify-between text-xs font-bold pt-1">
                            <span class="text-slate-500">Uang Kembalian:</span>
                            <span class="text-sm font-black" :class="kembalian > 0 ? 'text-emerald-700' : 'text-slate-800'">
                                {{ formatRupiah(kembalian) }}
                            </span>
                        </div>
                    </div>

                    <!-- Submit Transaction Button -->
                    <button
                        @click="submitTransaction"
                        :disabled="isSubmitting || cart.length === 0"
                        class="w-full py-3.5 px-4 rounded-xl text-sm font-extrabold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/30 transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <Printer class="w-4 h-4" />
                        <span>{{ isSubmitting ? 'Memproses Transaksi...' : 'Bayar & Cetak Nota' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </KasirLayout>
</template>

