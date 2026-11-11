<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import KasirLayout from '@/layouts/KasirLayout.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import {
    ShoppingBag,
    TrendingUp,
    Calendar,
    Receipt,
    Store,
    ArrowRight,
    Printer,
    History
} from 'lucide-vue-next';
import type { Cabang, Transaksi } from '@/types';

defineProps<{
    cabang: Cabang;
    stats: {
        transaksi_hari_ini: number;
        pendapatan_hari_ini: number;
        transaksi_bulan_ini: number;
        pendapatan_bulan_ini: number;
    };
    transaksiTerbaru: Transaksi[];
}>();
</script>

<template>
    <Head title="Dashboard Kasir" />

    <KasirLayout title="Dashboard Kasir">
        <div class="max-w-7xl mx-auto w-full p-4 sm:p-6 lg:p-8 space-y-6">
            <!-- Welcome Hero & Quick Action -->
            <div class="bg-gradient-to-r from-emerald-800 to-teal-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-700/60 text-emerald-200 text-xs font-semibold mb-2">
                        <Store class="w-3.5 h-3.5" />
                        <span>{{ cabang.nama_cabang }} &bull; {{ cabang.kota }}</span>
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Selamat Bertugas, {{ $page.props.auth.user?.nama_lengkap }}!
                    </h1>
                    <p class="text-sm text-emerald-200 mt-1 max-w-xl">
                        Sistem kasir Point of Sale (POS) siap digunakan untuk melayani transaksi langsung di cabang toko Anda.
                    </p>
                </div>

                <Link
                    href="/kasir/pos"
                    class="shrink-0 flex items-center gap-3 px-6 py-4 rounded-2xl bg-white text-emerald-900 hover:bg-emerald-50 text-sm font-extrabold shadow-lg transition transform hover:-translate-y-0.5"
                >
                    <ShoppingBag class="w-5 h-5 text-emerald-600" />
                    <span>Buka Kasir (POS)</span>
                    <ArrowRight class="w-4 h-4 ml-1 text-emerald-600" />
                </Link>
            </div>

            <!-- Stats Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Transaksi Hari Ini</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ stats.transaksi_hari_ini }}</h3>
                    <p class="text-xs text-emerald-600 mt-1 font-medium">Transaksi berhasil diselesaikan</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pendapatan Hari Ini</p>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ formatRupiah(stats.pendapatan_hari_ini) }}</h3>
                    <p class="text-xs text-slate-500 mt-1 font-medium">Omzet kasir Anda hari ini</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Transaksi Bulan Ini</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ stats.transaksi_bulan_ini }}</h3>
                    <p class="text-xs text-slate-500 mt-1 font-medium">Total transaksi kasir</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pendapatan Bulan Ini</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ formatRupiah(stats.pendapatan_bulan_ini) }}</h3>
                    <p class="text-xs text-slate-500 mt-1 font-medium">Performa kasir bulan berjalan</p>
                </div>
            </div>

            <!-- Recent Transactions Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Transaksi Terakhir yang Anda Layani</h3>
                        <p class="text-xs text-slate-500">Daftar transaksi kasir terbaru</p>
                    </div>
                    <Link href="/kasir/riwayat" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                        <span>Lihat Semua Riwayat</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">No. Transaksi</th>
                                <th class="py-3 px-4">Waktu</th>
                                <th class="py-3 px-4">Pelanggan</th>
                                <th class="py-3 px-4 text-right">Total Belanja</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Nota</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="transaksiTerbaru.length === 0">
                                <td colspan="6" class="py-10 text-center text-slate-400">
                                    Belum ada transaksi kasir yang dicatat hari ini.
                                </td>
                            </tr>
                            <tr v-for="t in transaksiTerbaru" :key="t.id_transaksi" class="hover:bg-slate-50/60">
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
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-slate-700 bg-slate-100 hover:bg-slate-200 text-xs font-semibold transition"
                                    >
                                        <Printer class="w-3.5 h-3.5 text-slate-500" />
                                        <span>Cetak</span>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </KasirLayout>
</template>

