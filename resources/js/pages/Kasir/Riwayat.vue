<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import KasirLayout from '@/layouts/KasirLayout.vue';
import Pagination from '@/components/Pagination.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import { Search, Printer, History } from 'lucide-vue-next';
import type { Transaksi, Paginated } from '@/types';

const props = defineProps<{
    transaksi: Paginated<Transaksi>;
    filters: {
        search?: string;
        tanggal?: string;
    };
}>();

const search = ref(props.filters.search || '');
const selectedTanggal = ref(props.filters.tanggal || '');

const filterData = () => {
    router.get('/kasir/riwayat', {
        search: search.value || undefined,
        tanggal: selectedTanggal.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Riwayat Transaksi Kasir" />

    <KasirLayout title="Riwayat Transaksi Anda">
        <div class="max-w-7xl mx-auto w-full p-4 sm:p-6 lg:p-8 space-y-6">
            <!-- Filter Bar -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="relative w-64">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                            v-model="search"
                            @keyup.enter="filterData"
                            type="text"
                            placeholder="Cari nomor nota transaksi..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-emerald-500"
                        />
                    </div>

                    <input
                        v-model="selectedTanggal"
                        type="date"
                        @change="filterData"
                        class="py-2 px-3 rounded-xl border border-slate-200 text-xs bg-white"
                    />

                    <button
                        @click="filterData"
                        class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">No. Transaksi</th>
                                <th class="py-3 px-4">Tanggal & Waktu</th>
                                <th class="py-3 px-4">Pelanggan</th>
                                <th class="py-3 px-4 text-right">Total Belanja</th>
                                <th class="py-3 px-4 text-center">Status Pembayaran</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="transaksi.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    Tidak ada riwayat transaksi yang sesuai.
                                </td>
                            </tr>
                            <tr v-for="t in transaksi.data" :key="t.id_transaksi" class="hover:bg-slate-50/60">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ t.kode_transaksi }}</td>
                                <td class="py-3 px-4 text-slate-500">{{ formatDate(t.waktu_transaksi) }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-800">{{ t.pelanggan?.nama_pelanggan || 'Pelanggan Umum' }}</td>
                                <td class="py-3 px-4 text-right font-extrabold text-slate-900">{{ formatRupiah(t.total_belanja) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <Badge :variant="t.status_pembayaran === 'BERHASIL' ? 'success' : 'warning'">
                                        {{ t.status_pembayaran }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Link
                                        :href="`/kasir/pos/nota/${t.id_transaksi}`"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition"
                                    >
                                        <Printer class="w-3.5 h-3.5" />
                                        <span>Cetak Ulang Nota</span>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Menampilkan {{ transaksi.from || 0 }} - {{ transaksi.to || 0 }} dari {{ transaksi.total }} transaksi</span>
                    <Pagination :links="transaksi.links" />
                </div>
            </div>
        </div>
    </KasirLayout>
</template>

