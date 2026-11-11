<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PelangganLayout from '@/layouts/PelangganLayout.vue';
import { formatRupiah } from '@/lib/utils';
import {
    ShoppingCart,
    Trash2,
    Plus,
    Minus,
    ArrowRight,
    ShoppingBag,
    ShieldCheck,
    Truck
} from 'lucide-vue-next';
import type { Produk } from '@/types';

interface CartItemData {
    id_detail_keranjang: number;
    id_produk: number;
    jumlah: number;
    produk: Produk;
    stok_tersedia: number;
    subtotal: number;
}

const props = defineProps<{
    items: CartItemData[];
    ringkasan: {
        subtotal: number;
        pajak: number;
        pajak_persen: number;
        ongkir: number;
        total: number;
    };
}>();

const updateQty = (idDetail: number, newQty: number) => {
    if (newQty <= 0) {
        removeItem(idDetail);
        return;
    }
    router.patch(`/pelanggan/keranjang/${idDetail}`, {
        jumlah: newQty,
    }, { preserveScroll: true });
};

const removeItem = (idDetail: number) => {
    router.delete(`/pelanggan/keranjang/${idDetail}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Keranjang Belanja" />

    <PelangganLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <h1 class="text-2xl font-extrabold text-slate-900 flex items-center gap-2">
                <ShoppingCart class="w-6 h-6 text-sky-600" />
                <span>Keranjang Belanja Anda</span>
            </h1>

            <div v-if="items.length === 0" class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center max-w-lg mx-auto shadow-xs space-y-4">
                <div class="w-16 h-16 rounded-3xl bg-sky-50 text-sky-600 flex items-center justify-center mx-auto">
                    <ShoppingBag class="w-8 h-8" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Keranjang Anda masih kosong</h3>
                    <p class="text-xs text-slate-500 mt-1">Jelajahi produk berkualitas kami dan temukan kebutuhan Anda.</p>
                </div>
                <Link
                    href="/katalog"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-sky-600 text-white text-xs font-bold shadow-md shadow-sky-600/30 hover:bg-sky-700 transition"
                >
                    <span>Mulai Belanja</span>
                    <ArrowRight class="w-4 h-4" />
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items List -->
                <div class="lg:col-span-2 space-y-3">
                    <div
                        v-for="item in items"
                        :key="item.id_detail_keranjang"
                        class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                    >
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-xl bg-slate-100 border border-slate-100 overflow-hidden shrink-0 flex items-center justify-center">
                                <img
                                    v-if="item.produk.gambar"
                                    :src="item.produk.gambar.startsWith('http') ? item.produk.gambar : `/${item.produk.gambar}`"
                                    :alt="item.produk.nama_produk"
                                    class="w-full h-full object-cover"
                                />
                                <ShoppingBag v-else class="w-6 h-6 text-slate-300" />
                            </div>

                            <div>
                                <Link :href="`/produk/${item.produk.slug}`" class="text-sm font-bold text-slate-900 hover:text-sky-600 line-clamp-1">
                                    {{ item.produk.nama_produk }}
                                </Link>
                                <span class="text-xs font-mono text-slate-400 block mt-0.5">{{ item.produk.sku }}</span>
                                <span class="text-xs font-semibold text-slate-600 mt-1 block">{{ formatRupiah(item.produk.harga) }}</span>
                            </div>
                        </div>

                        <!-- Quantity & Subtotal Controls -->
                        <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                            <!-- Qty buttons -->
                            <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                                <button
                                    @click="updateQty(item.id_detail_keranjang, item.jumlah - 1)"
                                    class="p-1.5 hover:bg-slate-200 text-slate-600 transition"
                                >
                                    <Minus class="w-3.5 h-3.5" />
                                </button>
                                <span class="w-8 text-center text-xs font-bold text-slate-900">{{ item.jumlah }}</span>
                                <button
                                    @click="updateQty(item.id_detail_keranjang, item.jumlah + 1)"
                                    :disabled="item.jumlah >= item.stok_tersedia"
                                    class="p-1.5 hover:bg-slate-200 text-slate-600 transition disabled:opacity-30"
                                >
                                    <Plus class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <div class="text-right">
                                <span class="text-sm font-black text-slate-900 block">{{ formatRupiah(item.subtotal) }}</span>
                                <button
                                    @click="removeItem(item.id_detail_keranjang)"
                                    class="text-[11px] text-rose-500 hover:text-rose-700 font-medium inline-flex items-center gap-1 mt-0.5"
                                >
                                    <Trash2 class="w-3 h-3" />
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary & Proceed to Checkout -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs h-fit space-y-4">
                    <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Ringkasan Belanja</h3>

                    <div class="space-y-2 text-xs text-slate-600">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span class="font-bold text-slate-900">{{ formatRupiah(ringkasan.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>PPN Simulasi ({{ ringkasan.pajak_persen }}%):</span>
                            <span class="font-bold text-slate-900">{{ formatRupiah(ringkasan.pajak) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Ongkos Kirim Standard:</span>
                            <span class="font-bold text-slate-900">{{ formatRupiah(ringkasan.ongkir) }}</span>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex justify-between items-center text-sm font-black text-slate-900">
                            <span>Total Pembayaran:</span>
                            <span class="text-lg text-sky-600">{{ formatRupiah(ringkasan.total) }}</span>
                        </div>
                    </div>

                    <Link
                        href="/pelanggan/checkout"
                        class="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white font-extrabold text-sm shadow-md shadow-sky-600/30 transition transform hover:-translate-y-0.5"
                    >
                        <span>Lanjut ke Checkout</span>
                        <ArrowRight class="w-4 h-4" />
                    </Link>

                    <div class="pt-2 text-[11px] text-slate-400 space-y-1">
                        <p class="flex items-center gap-1.5"><ShieldCheck class="w-3.5 h-3.5 text-emerald-500" /> Pembayaran Aman Midtrans Sandbox</p>
                        <p class="flex items-center gap-1.5"><Truck class="w-3.5 h-3.5 text-sky-500" /> Dikirim dari Cabang Utama Ponorogo</p>
                    </div>
                </div>
            </div>
        </div>
    </PelangganLayout>
</template>

