<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import {
    BarChart3,
    Download,
    Calendar,
    TrendingUp,
    Store,
    UserCheck,
    Receipt,
    Filter
} from 'lucide-vue-next';
import type { Cabang, User, Transaksi, Paginated } from '@/types';

const props = defineProps<{
    ringkasan: {
        total_omzet: number;
        jumlah_transaksi: number;
        rata_rata: number;
        total_diskon: number;
        total_pajak: number;
    };
    produkTerlaris: any[];
    penjualanCabang: any[];
    penjualanKasir: any[];
    perbandinganJenis: {
        pos: { count: number; total: number };
        online: { count: number; total: number };
    };
    transaksiList: Paginated<Transaksi>;
    cabangList: Cabang[];
    kasirList: User[];
    filters: {
        periode: string;
        cabang?: string;
        jenis?: string;
        kasir?: string;
        tanggal_mulai?: string;
        tanggal_akhir?: string;
    };
}>();

const selectedPeriode = ref(props.filters.periode || 'bulan_ini');
const selectedCabang = ref(props.filters.cabang || '');
const selectedJenis = ref(props.filters.jenis || '');
const selectedKasir = ref(props.filters.kasir || '');
const tglMulai = ref(props.filters.tanggal_mulai || '');
const tglAkhir = ref(props.filters.tanggal_akhir || '');

const setPeriode = (val: string) => {
    selectedPeriode.value = val;
    filterData();
};

const filterData = () => {
    router.get('/admin/laporan', {
        periode: selectedPeriode.value,
        cabang: selectedCabang.value || undefined,
        jenis: selectedJenis.value || undefined,
        kasir: selectedKasir.value || undefined,
        tanggal_mulai: selectedPeriode.value === 'custom' ? tglMulai.value : undefined,
        tanggal_akhir: selectedPeriode.value === 'custom' ? tglAkhir.value : undefined,
    }, { preserveState: true, replace: true });
};

const exportCsv = () => {
    const params = new URLSearchParams({
        tanggal_mulai: tglMulai.value,
        tanggal_akhir: tglAkhir.value,
        cabang: selectedCabang.value,
        jenis: selectedJenis.value,
    });
    window.location.href = `/admin/laporan/export-csv?${params.toString()}`;
};
</script>

<template>
    <Head title="Laporan Penjualan Toko" />

    <AdminLayout title="Laporan Penjualan Toko (POS & Online)">
        <div class="space-y-6">
            <!-- Filter Bar & Export -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <!-- Period Filter Buttons -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button
                            v-for="btn in [
                                { label: 'Hari Ini', val: 'hari_ini' },
                                { label: 'Kemarin', val: 'kemarin' },
                                { label: '7 Hari', val: '7_hari' },
                                { label: '30 Hari', val: '30_hari' },
                                { label: 'Bulan Ini', val: 'bulan_ini' },
                                { label: 'Tahun Ini', val: 'tahun_ini' },
                                { label: 'Kustom', val: 'custom' },
                            ]"
                            :key="btn.val"
                            @click="setPeriode(btn.val)"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition"
                            :class="selectedPeriode === btn.val ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        >
                            {{ btn.label }}
                        </button>
                    </div>

                    <!-- Export CSV Button -->
                    <button
                        @click="exportCsv"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-slate-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition shadow-xs"
                    >
                        <Download class="w-4 h-4 text-emerald-600" />
                        <span>Ekspor Laporan (CSV)</span>
                    </button>
                </div>

                <!-- Secondary Filters -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-3 border-t border-slate-100">
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
                        v-model="selectedJenis"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Jenis Transaksi</option>
                        <option value="POS">POS Kasir</option>
                        <option value="ONLINE">Pesanan Online</option>
                    </select>

                    <select
                        v-model="selectedKasir"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Kasir</option>
                        <option v-for="k in kasirList" :key="k.id_user" :value="k.id_user">
                            {{ k.nama_lengkap }}
                        </option>
                    </select>

                    <div v-if="selectedPeriode === 'custom'" class="flex items-center gap-2">
                        <input
                            v-model="tglMulai"
                            type="date"
                            @change="filterData"
                            class="py-1 px-2.5 rounded-lg border border-slate-200 text-xs w-full"
                        />
                        <span class="text-xs text-slate-400">-</span>
                        <input
                            v-model="tglAkhir"
                            type="date"
                            @change="filterData"
                            class="py-1 px-2.5 rounded-lg border border-slate-200 text-xs w-full"
                        />
                    </div>
                </div>
            </div>

            <!-- Summary KPI Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Omzet Berhasil</p>
                    <h3 class="text-xl font-black text-slate-900 mt-1">{{ formatRupiah(ringkasan.total_omzet) }}</h3>
                    <p class="text-[10px] text-emerald-600 mt-1 font-semibold">Transaksi terbayar</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Jumlah Transaksi</p>
                    <h3 class="text-xl font-black text-slate-900 mt-1">{{ ringkasan.jumlah_transaksi }}</h3>
                    <p class="text-[10px] text-slate-500 mt-1">Order selesai</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Rata-Rata Nilai Order</p>
                    <h3 class="text-xl font-black text-slate-900 mt-1">{{ formatRupiah(ringkasan.rata_rata) }}</h3>
                    <p class="text-[10px] text-slate-500 mt-1">Average order value</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Diskon Diberikan</p>
                    <h3 class="text-xl font-black text-emerald-600 mt-1">{{ formatRupiah(ringkasan.total_diskon) }}</h3>
                    <p class="text-[10px] text-slate-500 mt-1">Potongan harga</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Pajak (PPN)</p>
                    <h3 class="text-xl font-black text-slate-900 mt-1">{{ formatRupiah(ringkasan.total_pajak) }}</h3>
                    <p class="text-[10px] text-slate-500 mt-1">Simulasi pajak 11%</p>
                </div>
            </div>

            <!-- Channel POS vs Online & Branch Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Channel Comparison -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <h3 class="text-sm font-bold text-slate-900 mb-4">Perbandingan POS Kasir vs Toko Online</h3>
                    
                    <div class="space-y-4">
                        <div class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-100 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-emerald-900 block">Point of Sale (Kasir)</span>
                                <span class="text-[11px] text-emerald-700">{{ perbandinganJenis.pos.count }} Transaksi</span>
                            </div>
                            <span class="text-sm font-extrabold text-emerald-900">{{ formatRupiah(perbandinganJenis.pos.total) }}</span>
                        </div>

                        <div class="p-4 rounded-xl bg-sky-50/60 border border-sky-100 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-sky-900 block">Pesanan Toko Online</span>
                                <span class="text-[11px] text-sky-700">{{ perbandinganJenis.online.count }} Transaksi</span>
                            </div>
                            <span class="text-sm font-extrabold text-sky-900">{{ formatRupiah(perbandinganJenis.online.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Sales Per Branch -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <h3 class="text-sm font-bold text-slate-900 mb-4">Penjualan Per Cabang</h3>
                    <div class="divide-y divide-slate-100 text-xs">
                        <div v-for="c in penjualanCabang" :key="c.nama_cabang" class="py-2.5 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-800">{{ c.nama_cabang }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ c.kota }} &bull; {{ c.total_trx }} Trx</span>
                            </div>
                            <span class="font-extrabold text-slate-900">{{ formatRupiah(c.total_omzet) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Sales Per Cashier -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <h3 class="text-sm font-bold text-slate-900 mb-4">Performa Kasir</h3>
                    <div class="divide-y divide-slate-100 text-xs">
                        <div v-for="k in penjualanKasir" :key="k.nama_kasir" class="py-2.5 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-800">{{ k.nama_kasir }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ k.nama_cabang || '-' }} &bull; {{ k.total_trx }} Trx</span>
                            </div>
                            <span class="font-extrabold text-slate-900">{{ formatRupiah(k.total_omzet) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products in Report Period -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <h3 class="text-sm font-bold text-slate-900 mb-4">Produk Paling Banyak Terjual Pada Periode Ini</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase border-b border-slate-200">
                                <th class="py-2.5 px-4">Nama Produk</th>
                                <th class="py-2.5 px-4">SKU</th>
                                <th class="py-2.5 px-4 text-center">Qty Terjual</th>
                                <th class="py-2.5 px-4 text-right">Total Nilai Penjualan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="produkTerlaris.length === 0">
                                <td colspan="4" class="py-8 text-center text-slate-400">Tidak ada produk terjual pada periode ini.</td>
                            </tr>
                            <tr v-for="p in produkTerlaris" :key="p.sku" class="hover:bg-slate-50/60">
                                <td class="py-2.5 px-4 font-bold text-slate-800">{{ p.nama_produk }}</td>
                                <td class="py-2.5 px-4 font-mono text-slate-500">{{ p.sku }}</td>
                                <td class="py-2.5 px-4 text-center font-extrabold">{{ p.total_qty }}</td>
                                <td class="py-2.5 px-4 text-right font-bold text-slate-900">{{ formatRupiah(p.total_omzet) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

