<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import Badge from '@/components/Badge.vue';
import {
    Users,
    Package,
    FolderTree,
    Store,
    Receipt,
    TrendingUp,
    Calendar,
    ShoppingBag,
    Clock,
    AlertTriangle,
    CreditCard,
    ArrowUpRight,
    ArrowRight,
    Activity
} from 'lucide-vue-next';

interface DashboardProps {
    stats: {
        total_pelanggan: number;
        total_produk_aktif: number;
        total_kategori: number;
        total_cabang: number;
        total_transaksi: number;
        total_penjualan_berhasil: number;
        pendapatan_hari_ini: number;
        pendapatan_bulan_ini: number;
        transaksi_pos: number;
        pesanan_online: number;
        pesanan_menunggu_pembayaran: number;
        produk_stok_menipis: number;
    };
    charts: {
        harian: { tanggal: string; total: number }[];
        cabang: { nama_cabang: string; total_penjualan: number; jumlah_transaksi: number }[];
        pos_count: number;
        online_count: number;
    };
    produkTerlaris: {
        nama_produk: string;
        sku: string;
        total_terjual: number;
        total_omzet: number;
    }[];
    transaksiTerbaru: any[];
    aktivitasTerbaru: any[];
}

const props = defineProps<DashboardProps>();

// Helper max omzet harian for bar chart scaling
const maxHarian = Math.max(...props.charts.harian.map(h => h.total), 1);
const maxCabang = Math.max(...props.charts.cabang.map(c => Number(c.total_penjualan)), 1);
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout title="Ringkasan Statistik & Operasional">
        <div class="space-y-6">
            <!-- 1. Top 4 Primary Revenue Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Omzet Berhasil</p>
                        <h3 class="text-xl font-extrabold text-slate-900 mt-1">{{ formatRupiah(stats.total_penjualan_berhasil) }}</h3>
                        <p class="text-[11px] text-emerald-600 font-medium mt-1 flex items-center gap-1">
                            <TrendingUp class="w-3.5 h-3.5" />
                            <span>{{ stats.total_transaksi }} Total Transaksi</span>
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <CreditCard class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pendapatan Hari Ini</p>
                        <h3 class="text-xl font-extrabold text-slate-900 mt-1">{{ formatRupiah(stats.pendapatan_hari_ini) }}</h3>
                        <p class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                            <Clock class="w-3.5 h-3.5" />
                            <span>Real-time hari ini</span>
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <TrendingUp class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pendapatan Bulan Ini</p>
                        <h3 class="text-xl font-extrabold text-slate-900 mt-1">{{ formatRupiah(stats.pendapatan_bulan_ini) }}</h3>
                        <p class="text-[11px] text-sky-600 font-medium mt-1 flex items-center gap-1">
                            <Calendar class="w-3.5 h-3.5" />
                            <span>Bulan berjalan</span>
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <Calendar class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Perlu Perhatian</p>
                        <h3 class="text-xl font-extrabold text-amber-600 mt-1">{{ stats.produk_stok_menipis }} Produk</h3>
                        <p class="text-[11px] text-amber-600 font-medium mt-1 flex items-center gap-1">
                            <AlertTriangle class="w-3.5 h-3.5" />
                            <span>Stok &le; Stok Minimum</span>
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- 2. Master Entity Counters (8 Secondary Stats) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                <Link href="/admin/pelanggan" class="bg-white p-3 rounded-xl border border-slate-200/80 hover:border-sky-500 transition text-center group">
                    <Users class="w-5 h-5 mx-auto text-sky-600 mb-1 group-hover:scale-110 transition-transform" />
                    <span class="text-base font-bold text-slate-900 block">{{ stats.total_pelanggan }}</span>
                    <span class="text-[10px] text-slate-500 font-medium uppercase">Pelanggan</span>
                </Link>

                <Link href="/admin/produk" class="bg-white p-3 rounded-xl border border-slate-200/80 hover:border-sky-500 transition text-center group">
                    <Package class="w-5 h-5 mx-auto text-emerald-600 mb-1 group-hover:scale-110 transition-transform" />
                    <span class="text-base font-bold text-slate-900 block">{{ stats.total_produk_aktif }}</span>
                    <span class="text-[10px] text-slate-500 font-medium uppercase">Produk Aktif</span>
                </Link>

                <Link href="/admin/kategori" class="bg-white p-3 rounded-xl border border-slate-200/80 hover:border-sky-500 transition text-center group">
                    <FolderTree class="w-5 h-5 mx-auto text-purple-600 mb-1 group-hover:scale-110 transition-transform" />
                    <span class="text-base font-bold text-slate-900 block">{{ stats.total_kategori }}</span>
                    <span class="text-[10px] text-slate-500 font-medium uppercase">Kategori</span>
                </Link>

                <Link href="/admin/cabang" class="bg-white p-3 rounded-xl border border-slate-200/80 hover:border-sky-500 transition text-center group">
                    <Store class="w-5 h-5 mx-auto text-indigo-600 mb-1 group-hover:scale-110 transition-transform" />
                    <span class="text-base font-bold text-slate-900 block">{{ stats.total_cabang }}</span>
                    <span class="text-[10px] text-slate-500 font-medium uppercase">Cabang</span>
                </Link>

                <Link href="/admin/transaksi?jenis=POS" class="bg-white p-3 rounded-xl border border-slate-200/80 hover:border-sky-500 transition text-center group">
                    <Receipt class="w-5 h-5 mx-auto text-teal-600 mb-1 group-hover:scale-110 transition-transform" />
                    <span class="text-base font-bold text-slate-900 block">{{ stats.transaksi_pos }}</span>
                    <span class="text-[10px] text-slate-500 font-medium uppercase">Trx POS</span>
                </Link>

                <Link href="/admin/pesanan" class="bg-white p-3 rounded-xl border border-slate-200/80 hover:border-sky-500 transition text-center group">
                    <ShoppingBag class="w-5 h-5 mx-auto text-blue-600 mb-1 group-hover:scale-110 transition-transform" />
                    <span class="text-base font-bold text-slate-900 block">{{ stats.pesanan_online }}</span>
                    <span class="text-[10px] text-slate-500 font-medium uppercase">Trx Online</span>
                </Link>

                <Link href="/admin/pesanan?status_pembayaran=PENDING" class="bg-white p-3 rounded-xl border border-slate-200/80 hover:border-sky-500 transition text-center group">
                    <Clock class="w-5 h-5 mx-auto text-amber-500 mb-1 group-hover:scale-110 transition-transform" />
                    <span class="text-base font-bold text-slate-900 block">{{ stats.pesanan_menunggu_pembayaran }}</span>
                    <span class="text-[10px] text-slate-500 font-medium uppercase">Pending Bayar</span>
                </Link>

                <Link href="/admin/stok?menipis=1" class="bg-white p-3 rounded-xl border border-slate-200/80 hover:border-sky-500 transition text-center group">
                    <AlertTriangle class="w-5 h-5 mx-auto text-rose-500 mb-1 group-hover:scale-110 transition-transform" />
                    <span class="text-base font-bold text-slate-900 block">{{ stats.produk_stok_menipis }}</span>
                    <span class="text-[10px] text-slate-500 font-medium uppercase">Stok Menipis</span>
                </Link>
            </div>

            <!-- 3. Charts & Analytics Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- 7 Days Trend Visual Bar Chart -->
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Grafik Penjualan 7 Hari Terakhir</h3>
                            <p class="text-xs text-slate-500">Omzet transaksi berstatus berhasil</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-sky-50 text-sky-700 border border-sky-100">
                            7 Hari Terakhir
                        </span>
                    </div>

                    <div class="h-56 flex items-end justify-between gap-3 pt-6 pb-2 border-b border-slate-100">
                        <div
                            v-for="bar in charts.harian"
                            :key="bar.tanggal"
                            class="flex-1 flex flex-col items-center gap-2 h-full justify-end group"
                        >
                            <!-- Tooltip On Hover -->
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity text-[10px] font-bold text-slate-800 bg-slate-100 px-1.5 py-0.5 rounded shadow-xs mb-1 whitespace-nowrap">
                                {{ formatRupiah(bar.total) }}
                            </div>

                            <!-- Bar Column -->
                            <div
                                class="w-full bg-gradient-to-t from-sky-600 to-sky-400 rounded-t-lg transition-all duration-500 group-hover:from-sky-500 group-hover:to-sky-300 min-h-[4px]"
                                :style="{ height: `${Math.max((bar.total / maxHarian) * 100, 3)}%` }"
                            />

                            <span class="text-[11px] font-medium text-slate-500 text-center truncate max-w-full">
                                {{ bar.tanggal }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Penjualan Per Cabang -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-900">Penjualan Per Cabang</h3>
                        <Link href="/admin/cabang" class="text-xs font-medium text-sky-600 hover:text-sky-700 flex items-center">
                            Lihat Semua <ArrowRight class="w-3 h-3 ml-0.5" />
                        </Link>
                    </div>

                    <div class="space-y-4 flex-1">
                        <div v-for="cbg in charts.cabang" :key="cbg.nama_cabang" class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-800">{{ cbg.nama_cabang }}</span>
                                <span class="font-bold text-slate-900">{{ formatRupiah(cbg.total_penjualan) }}</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                <div
                                    class="bg-sky-600 h-full rounded-full transition-all duration-500"
                                    :style="{ width: `${Math.max((Number(cbg.total_penjualan) / maxCabang) * 100, 2)}%` }"
                                />
                            </div>
                            <p class="text-[10px] text-slate-400 text-right">{{ cbg.jumlah_transaksi }} Transaksi</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Top Selling Products & Recent Transactions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top 5 Best Sellers -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Produk Terlaris</h3>
                            <p class="text-xs text-slate-500">Paling banyak terjual di semua channel</p>
                        </div>
                        <Link href="/admin/produk" class="text-xs font-medium text-sky-600 hover:text-sky-700 flex items-center">
                            Kelola Produk <ArrowRight class="w-3 h-3 ml-0.5" />
                        </Link>
                    </div>

                    <div v-if="produkTerlaris.length === 0" class="py-8 text-center text-xs text-slate-400">
                        Belum ada data transaksi berhasil.
                    </div>
                    <div v-else class="divide-y divide-slate-100">
                        <div
                            v-for="(item, idx) in produkTerlaris"
                            :key="item.sku"
                            class="py-3 flex items-center justify-between gap-4"
                        >
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 text-xs font-bold flex items-center justify-center shrink-0">
                                    {{ idx + 1 }}
                                </span>
                                <div>
                                    <p class="text-xs font-bold text-slate-900">{{ item.nama_produk }}</p>
                                    <p class="text-[10px] text-slate-400">SKU: {{ item.sku }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-slate-900 block">{{ item.total_terjual }} Terjual</span>
                                <span class="text-[10px] text-slate-500">{{ formatRupiah(item.total_omzet) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Latest Transactions -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Transaksi Terbaru</h3>
                            <p class="text-xs text-slate-500">POS, Online & Riwayat</p>
                        </div>
                        <Link href="/admin/transaksi" class="text-xs font-medium text-sky-600 hover:text-sky-700 flex items-center">
                            Lihat Semua <ArrowRight class="w-3 h-3 ml-0.5" />
                        </Link>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="trx in transaksiTerbaru"
                            :key="trx.id_transaksi"
                            class="p-3 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition flex items-center justify-between gap-3"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-900">{{ trx.kode_transaksi }}</span>
                                    <Badge :variant="trx.jenis_transaksi === 'POS' ? 'success' : trx.jenis_transaksi === 'ONLINE' ? 'info' : 'neutral'">
                                        {{ trx.jenis_transaksi }}
                                    </Badge>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    {{ trx.pelanggan?.nama_pelanggan || 'Pelanggan Umum' }} &bull; {{ formatDate(trx.waktu_transaksi) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-extrabold text-slate-900 block">{{ formatRupiah(trx.total_belanja) }}</span>
                                <Badge :variant="trx.status_pembayaran === 'BERHASIL' ? 'success' : trx.status_pembayaran === 'PENDING' ? 'warning' : 'danger'">
                                    {{ trx.status_pembayaran }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Recent System Activities (from tbl_log_aktivitas) -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <Activity class="w-5 h-5 text-sky-600" />
                        <h3 class="text-sm font-bold text-slate-900">Riwayat Aktivitas Sistem</h3>
                    </div>
                    <span class="text-xs text-slate-400">Database Log</span>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="log in aktivitasTerbaru"
                        :key="log.id_log"
                        class="text-xs py-2 border-b border-slate-100 last:border-b-0 flex items-start justify-between gap-4"
                    >
                        <div>
                            <span class="font-bold text-slate-800">{{ log.aktivitas }}</span>
                            <span v-if="log.keterangan" class="text-slate-500 block text-[11px] mt-0.5">{{ log.keterangan }}</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-slate-400 text-[10px] block">{{ formatDate(log.created_at) }}</span>
                            <span v-if="log.user" class="text-sky-600 font-medium text-[11px]">{{ log.user.nama_lengkap }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

