<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import { Search, Eye, ShoppingBag, Clock, CheckCircle2 } from 'lucide-vue-next';
import type { Transaksi, Paginated } from '@/types';

const props = defineProps<{
    pesanan: Paginated<Transaksi>;
    filters: {
        search?: string;
        status_transaksi?: string;
        status_pembayaran?: string;
    };
}>();

const search = ref(props.filters.search || '');
const selectedTrxStatus = ref(props.filters.status_transaksi || '');
const selectedPayStatus = ref(props.filters.status_pembayaran || '');

const filterData = () => {
    router.get('/admin/pesanan', {
        search: search.value || undefined,
        status_transaksi: selectedTrxStatus.value || undefined,
        status_pembayaran: selectedPayStatus.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Manajemen Pesanan Online" />

    <AdminLayout title="Manajemen Pesanan Toko Online">
        <div class="space-y-6">
            <!-- Filter Controls -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative w-64">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                            v-model="search"
                            @keyup.enter="filterData"
                            type="text"
                            placeholder="Cari kode atau nama pemesan..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-sky-500"
                        />
                    </div>

                    <select
                        v-model="selectedTrxStatus"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Status Pesanan</option>
                        <option value="MENUNGGU_PEMBAYARAN">MENUNGGU PEMBAYARAN</option>
                        <option value="DIPROSES">DIPROSES</option>
                        <option value="DIKIRIM">DIKIRIM</option>
                        <option value="SELESAI">SELESAI</option>
                        <option value="DIBATALKAN">DIBATALKAN</option>
                    </select>

                    <select
                        v-model="selectedPayStatus"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Status Pembayaran</option>
                        <option value="PENDING">PENDING</option>
                        <option value="BERHASIL">BERHASIL</option>
                        <option value="GAGAL">GAGAL</option>
                        <option value="KEDALUWARSA">KEDALUWARSA</option>
                        <option value="DIBATALKAN">DIBATALKAN</option>
                    </select>

                    <button
                        @click="filterData"
                        class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">No. Pesanan</th>
                                <th class="py-3 px-4">Waktu Pesan</th>
                                <th class="py-3 px-4">Pelanggan</th>
                                <th class="py-3 px-4 text-right">Total Belanja</th>
                                <th class="py-3 px-4 text-center">Status Pembayaran</th>
                                <th class="py-3 px-4 text-center">Status Pesanan</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-if="pesanan.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    Tidak ada data pesanan online yang sesuai filter.
                                </td>
                            </tr>
                            <tr v-for="order in pesanan.data" :key="order.id_transaksi" class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ order.kode_transaksi }}</td>
                                <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ formatDate(order.waktu_transaksi) }}</td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-900 block">{{ order.pelanggan?.nama_pelanggan || '-' }}</span>
                                    <span class="text-[11px] text-slate-400">{{ order.pelanggan?.kota || '-' }}</span>
                                </td>
                                <td class="py-3 px-4 text-right font-extrabold text-slate-900">{{ formatRupiah(order.total_belanja) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <Badge :variant="order.status_pembayaran === 'BERHASIL' ? 'success' : order.status_pembayaran === 'PENDING' ? 'warning' : 'danger'">
                                        {{ order.status_pembayaran }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Badge :variant="order.status_transaksi === 'SELESAI' ? 'success' : order.status_transaksi === 'DIKIRIM' || order.status_transaksi === 'DIPROSES' ? 'info' : order.status_transaksi === 'DIBATALKAN' ? 'danger' : 'neutral'">
                                        {{ order.status_transaksi }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Link
                                        :href="`/admin/pesanan/${order.id_transaksi}`"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 transition"
                                    >
                                        <Eye class="w-3.5 h-3.5" />
                                        <span>Kelola</span>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Menampilkan {{ pesanan.from || 0 }} - {{ pesanan.to || 0 }} dari {{ pesanan.total }} pesanan</span>
                    <Pagination :links="pesanan.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

