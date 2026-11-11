<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import { User, MapPin, Phone, Mail, ArrowLeft, Receipt, Eye } from 'lucide-vue-next';
import type { Pelanggan, Transaksi, Paginated } from '@/types';

defineProps<{
    pelanggan: Pelanggan;
    kategoriBelanja: string;
    riwayatTransaksi: Paginated<Transaksi>;
}>();
</script>

<template>
    <Head :title="`Detail Pelanggan - ${pelanggan.nama_pelanggan}`" />

    <AdminLayout :title="`Detail Pelanggan: ${pelanggan.nama_pelanggan}`">
        <div class="space-y-6">
            <Link href="/admin/pelanggan" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                <ArrowLeft class="w-4 h-4" />
                <span>Kembali ke Daftar Pelanggan</span>
            </Link>

            <!-- Customer Profile Card -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-sky-100 text-sky-700 font-bold flex items-center justify-center text-2xl shadow-xs">
                        {{ pelanggan.nama_pelanggan.charAt(0) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-slate-900">{{ pelanggan.nama_pelanggan }}</h2>
                            <Badge :variant="kategoriBelanja.includes('Gold') ? 'warning' : kategoriBelanja.includes('Silver') ? 'info' : 'neutral'">
                                {{ kategoriBelanja }}
                            </Badge>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-4">
                            <span class="flex items-center gap-1"><MapPin class="w-3.5 h-3.5 text-slate-400" /> {{ pelanggan.kota || '-' }}</span>
                            <span class="flex items-center gap-1"><Phone class="w-3.5 h-3.5 text-slate-400" /> {{ pelanggan.no_hp || '-' }}</span>
                            <span v-if="pelanggan.user?.email" class="flex items-center gap-1"><Mail class="w-3.5 h-3.5 text-slate-400" /> {{ pelanggan.user.email }}</span>
                        </p>
                    </div>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60 text-right w-full md:w-auto">
                    <p class="text-[11px] font-medium text-slate-500">Total Transaksi Selesai</p>
                    <p class="text-lg font-extrabold text-slate-900">{{ riwayatTransaksi.total }} Pesanan</p>
                </div>
            </div>

            <!-- Transaction History Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-800">Riwayat Pembelian & Pesanan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">Kode Transaksi</th>
                                <th class="py-3 px-4">Tanggal & Waktu</th>
                                <th class="py-3 px-4">Jenis</th>
                                <th class="py-3 px-4">Cabang</th>
                                <th class="py-3 px-4 text-right">Total Belanja</th>
                                <th class="py-3 px-4 text-center">Status Bayar</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-if="riwayatTransaksi.data.length === 0">
                                <td colspan="7" class="py-10 text-center text-slate-400">
                                    Belum ada riwayat transaksi untuk pelanggan ini.
                                </td>
                            </tr>
                            <tr v-for="trx in riwayatTransaksi.data" :key="trx.id_transaksi" class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 font-bold text-slate-900">{{ trx.kode_transaksi }}</td>
                                <td class="py-3 px-4 text-slate-500">{{ formatDate(trx.waktu_transaksi) }}</td>
                                <td class="py-3 px-4">
                                    <Badge :variant="trx.jenis_transaksi === 'POS' ? 'success' : trx.jenis_transaksi === 'ONLINE' ? 'info' : 'neutral'">
                                        {{ trx.jenis_transaksi }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4">{{ trx.cabang?.nama_cabang || '-' }}</td>
                                <td class="py-3 px-4 text-right font-extrabold text-slate-900">{{ formatRupiah(trx.total_belanja) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <Badge :variant="trx.status_pembayaran === 'BERHASIL' ? 'success' : trx.status_pembayaran === 'PENDING' ? 'warning' : 'danger'">
                                        {{ trx.status_pembayaran }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Link
                                        :href="`/admin/transaksi/${trx.id_transaksi}`"
                                        class="p-1.5 rounded-lg text-slate-600 hover:text-sky-600 hover:bg-sky-50 transition inline-flex"
                                    >
                                        <Eye class="w-4 h-4" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Menampilkan {{ riwayatTransaksi.from || 0 }} - {{ riwayatTransaksi.to || 0 }} dari {{ riwayatTransaksi.total }} transaksi</span>
                    <Pagination :links="riwayatTransaksi.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

