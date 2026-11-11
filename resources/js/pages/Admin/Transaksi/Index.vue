<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import { Search, Eye, Printer, Receipt, Filter } from 'lucide-vue-next';
import type { Transaksi, Cabang, User, Paginated } from '@/types';

const props = defineProps<{
    transaksi: Paginated<Transaksi>;
    cabangList: Cabang[];
    kasirList: User[];
    filters: {
        search?: string;
        jenis?: string;
        cabang?: string;
        status_bayar?: string;
        tanggal_mulai?: string;
        tanggal_akhir?: string;
    };
}>();

const search = ref(props.filters.search || '');
const selectedJenis = ref(props.filters.jenis || '');
const selectedCabang = ref(props.filters.cabang || '');
const selectedStatusBayar = ref(props.filters.status_bayar || '');
const tglMulai = ref(props.filters.tanggal_mulai || '');
const tglAkhir = ref(props.filters.tanggal_akhir || '');

const filterData = () => {
    router.get('/admin/transaksi', {
        search: search.value || undefined,
        jenis: selectedJenis.value || undefined,
        cabang: selectedCabang.value || undefined,
        status_bayar: selectedStatusBayar.value || undefined,
        tanggal_mulai: tglMulai.value || undefined,
        tanggal_akhir: tglAkhir.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Semua Transaksi" />

    <AdminLayout title="Manajemen Transaksi (POS & Online)">
        <div class="space-y-6">
            <!-- Filter Controls -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="relative">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                            v-model="search"
                            @keyup.enter="filterData"
                            type="text"
                            placeholder="Cari kode atau pelanggan..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-sky-500"
                        />
                    </div>

                    <select
                        v-model="selectedJenis"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Jenis Transaksi</option>
                        <option value="POS">POS Kasir</option>
                        <option value="ONLINE">Pesanan Online</option>
                        <option value="LEGACY">Data Historis (Legacy)</option>
                    </select>

                    <select
                        v-model="selectedCabang"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Cabang</option>
                        <option v-for="c in cabangList" :key="c.id_cabang" :value="c.id_cabang">
                            {{ c.nama_cabang }}
                        </option>
                    </select>

                    <select
                        v-model="selectedStatusBayar"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Status Pembayaran</option>
                        <option value="BERHASIL">BERHASIL</option>
                        <option value="PENDING">PENDING</option>
                        <option value="GAGAL">GAGAL</option>
                        <option value="KEDALUWARSA">KEDALUWARSA</option>
                        <option value="DIBATALKAN">DIBATALKAN</option>
                    </select>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100 flex-wrap gap-3">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-slate-500 font-medium">Rentang Tanggal:</span>
                        <input
                            v-model="tglMulai"
                            type="date"
                            class="py-1 px-2.5 rounded-lg border border-slate-200 text-xs"
                        />
                        <span class="text-slate-400">s/d</span>
                        <input
                            v-model="tglAkhir"
                            type="date"
                            class="py-1 px-2.5 rounded-lg border border-slate-200 text-xs"
                        />
                    </div>

                    <button
                        @click="filterData"
                        class="px-4 py-1.5 text-xs font-semibold rounded-xl bg-sky-600 hover:bg-sky-700 text-white transition shadow-xs"
                    >
                        Terapkan Filter
                    </button>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">Kode Transaksi</th>
                                <th class="py-3 px-4">Tanggal & Waktu</th>
                                <th class="py-3 px-4 text-center">Jenis</th>
                                <th class="py-3 px-4">Pelanggan</th>
                                <th class="py-3 px-4">Cabang</th>
                                <th class="py-3 px-4 text-right">Total Belanja</th>
                                <th class="py-3 px-4 text-center">Status Bayar</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-if="transaksi.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    Tidak ada transaksi yang cocok dengan filter yang dipilih.
                                </td>
                            </tr>
                            <tr v-for="t in transaksi.data" :key="t.id_transaksi" class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ t.kode_transaksi }}</td>
                                <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ formatDate(t.waktu_transaksi) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <Badge :variant="t.jenis_transaksi === 'POS' ? 'success' : t.jenis_transaksi === 'ONLINE' ? 'info' : 'neutral'">
                                        {{ t.jenis_transaksi }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800">
                                    {{ t.pelanggan?.nama_pelanggan || 'Pelanggan Umum' }}
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ t.cabang?.nama_cabang || '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-extrabold text-slate-900">
                                    {{ formatRupiah(t.total_belanja) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Badge :variant="t.status_pembayaran === 'BERHASIL' ? 'success' : t.status_pembayaran === 'PENDING' ? 'warning' : 'danger'">
                                        {{ t.status_pembayaran }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <Link
                                            :href="`/admin/transaksi/${t.id_transaksi}`"
                                            title="Lihat Detail Transaksi"
                                            class="p-1.5 rounded-lg text-slate-600 hover:text-sky-600 hover:bg-sky-50 transition inline-flex"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </Link>
                                        <Link
                                            :href="`/admin/transaksi/${t.id_transaksi}/nota`"
                                            title="Cetak Nota"
                                            class="p-1.5 rounded-lg text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 transition inline-flex"
                                        >
                                            <Printer class="w-4 h-4" />
                                        </Link>
                                    </div>
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
    </AdminLayout>
</template>

