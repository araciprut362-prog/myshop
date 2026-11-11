<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PelangganLayout from '@/layouts/PelangganLayout.vue';
import Pagination from '@/components/Pagination.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import { ShoppingBag, Eye, CreditCard, Search, ArrowRight } from 'lucide-vue-next';
import type { Transaksi, Paginated } from '@/types';

const props = defineProps<{
    pesanan: Paginated<Transaksi>;
    filters: {
        search?: string;
        status?: string;
    };
}>();

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');

const filterData = () => {
    router.get('/pelanggan/pesanan', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Pesanan Saya" />

    <PelangganLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900">Riwayat Pesanan Saya</h1>
                    <p class="text-xs text-slate-500">Pantau status pembayaran dan pengiriman pesanan Anda</p>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-60">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                            v-model="search"
                            @keyup.enter="filterData"
                            type="text"
                            placeholder="Cari kode pesanan..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-sky-500"
                        />
                    </div>

                    <select
                        v-model="selectedStatus"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Status</option>
                        <option value="MENUNGGU_PEMBAYARAN">Menunggu Pembayaran</option>
                        <option value="DIPROSES">Diproses</option>
                        <option value="DIKIRIM">Dikirim</option>
                        <option value="SELESAI">Selesai</option>
                        <option value="DIBATALKAN">Dibatalkan</option>
                    </select>
                </div>
            </div>

            <!-- Orders List -->
            <div v-if="pesanan.data.length === 0" class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center max-w-md mx-auto shadow-xs space-y-3">
                <ShoppingBag class="w-12 h-12 mx-auto text-slate-300" />
                <h3 class="text-sm font-bold text-slate-900">Belum ada pesanan</h3>
                <p class="text-xs text-slate-400">Anda belum pernah melakukan pemesanan online di MyShop.</p>
                <Link
                    href="/katalog"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-sky-600 text-white text-xs font-bold shadow-xs hover:bg-sky-700 transition"
                >
                    <span>Mulai Belanja</span>
                    <ArrowRight class="w-4 h-4" />
                </Link>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="order in pesanan.data"
                    :key="order.id_transaksi"
                    class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:border-sky-300 transition space-y-4"
                >
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-3 border-b border-slate-100 gap-2">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-mono font-bold text-slate-900">{{ order.kode_transaksi }}</span>
                            <span class="text-xs text-slate-400">&bull; {{ formatDate(order.waktu_transaksi) }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <Badge :variant="order.status_pembayaran === 'BERHASIL' ? 'success' : order.status_pembayaran === 'PENDING' ? 'warning' : 'danger'">
                                {{ order.status_pembayaran }}
                            </Badge>
                            <Badge :variant="order.status_transaksi === 'SELESAI' ? 'success' : order.status_transaksi === 'DIBATALKAN' ? 'danger' : 'info'">
                                {{ order.status_transaksi }}
                            </Badge>
                        </div>
                    </div>

                    <!-- Items Summary -->
                    <div class="space-y-1.5 text-xs text-slate-600">
                        <div v-for="item in order.detail_transaksi?.slice(0, 3)" :key="item.id_detail" class="flex justify-between">
                            <span>{{ item.nama_produk_snapshot }} (x{{ item.jumlah }})</span>
                            <span class="font-semibold text-slate-900">{{ formatRupiah(item.subtotal) }}</span>
                        </div>
                        <div v-if="(order.detail_transaksi?.length || 0) > 3" class="text-[11px] text-slate-400 italic">
                            + {{ (order.detail_transaksi?.length || 0) - 3 }} produk lainnya
                        </div>
                    </div>

                    <!-- Footer Total & Actions -->
                    <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <span class="text-[11px] text-slate-400 block">Total Tagihan</span>
                            <span class="text-base font-black text-slate-900">{{ formatRupiah(order.total_belanja) }}</span>
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <!-- Continue payment button if pending -->
                            <Link
                                v-if="order.status_pembayaran === 'PENDING' && order.status_transaksi !== 'DIBATALKAN'"
                                :href="`/pelanggan/pesanan/${order.id_transaksi}/bayar`"
                                class="flex-1 sm:flex-none px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold transition shadow-xs text-center"
                            >
                                Bayar Sekarang
                            </Link>

                            <Link
                                :href="`/pelanggan/pesanan/${order.id_transaksi}`"
                                class="flex-1 sm:flex-none px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition text-center"
                            >
                                Detail Pesanan
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-between text-xs text-slate-500">
                    <span>Menampilkan {{ pesanan.from || 0 }} - {{ pesanan.to || 0 }} dari {{ pesanan.total }} pesanan</span>
                    <Pagination :links="pesanan.links" />
                </div>
            </div>
        </div>
    </PelangganLayout>
</template>

