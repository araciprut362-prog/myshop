<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import { ArrowLeft, Printer, AlertCircle, CheckCircle2, Clock, Store, User, CreditCard } from 'lucide-vue-next';
import type { Transaksi } from '@/types';

defineProps<{
    transaksi: Transaksi;
}>();
</script>

<template>
    <Head :title="`Detail Transaksi - ${transaksi.kode_transaksi}`" />

    <AdminLayout :title="`Transaksi: ${transaksi.kode_transaksi}`">
        <div class="space-y-6 max-w-5xl">
            <div class="flex items-center justify-between">
                <Link href="/admin/transaksi" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                    <ArrowLeft class="w-4 h-4" />
                    <span>Kembali ke Semua Transaksi</span>
                </Link>

                <Link
                    :href="`/admin/transaksi/${transaksi.id_transaksi}/nota`"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-xs transition"
                >
                    <Printer class="w-4 h-4 text-emerald-400" />
                    <span>Cetak Nota Transaksi</span>
                </Link>
            </div>

            <!-- Legacy Notice Banner -->
            <div
                v-if="transaksi.jenis_transaksi === 'LEGACY'"
                class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-3"
            >
                <AlertCircle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                <div>
                    <h4 class="font-bold">Transaksi Historis (LEGACY)</h4>
                    <p class="mt-0.5 text-amber-800">
                        Transaksi ini merupakan data migrasi sistem lama (db_toko1). Informasi rincian item produk, cabang, dan kasir tidak tersedia pada database awal dan sengaja dibiarkan apa adanya tanpa membuat data rincian fiktif.
                    </p>
                </div>
            </div>

            <!-- Transaction Header Summary Card -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kode Transaksi</span>
                    <span class="text-lg font-mono font-black text-slate-900 block mt-0.5">{{ transaksi.kode_transaksi }}</span>
                    <Badge class="mt-2" :variant="transaksi.jenis_transaksi === 'POS' ? 'success' : transaksi.jenis_transaksi === 'ONLINE' ? 'info' : 'neutral'">
                        {{ transaksi.jenis_transaksi }}
                    </Badge>
                </div>

                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Waktu Transaksi</span>
                    <span class="text-xs font-semibold text-slate-800 block mt-1">{{ formatDate(transaksi.waktu_transaksi) }}</span>
                    <span class="text-[11px] text-slate-400 mt-0.5 block">Tanggal: {{ transaksi.tanggal }}</span>
                </div>

                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Pembayaran</span>
                    <Badge class="mt-1" :variant="transaksi.status_pembayaran === 'BERHASIL' ? 'success' : transaksi.status_pembayaran === 'PENDING' ? 'warning' : 'danger'">
                        {{ transaksi.status_pembayaran }}
                    </Badge>
                    <span class="text-[11px] text-slate-500 block mt-1">Stok: {{ transaksi.status_stok }}</span>
                </div>

                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Belanja</span>
                    <span class="text-xl font-black text-slate-900 block mt-0.5">{{ formatRupiah(transaksi.total_belanja) }}</span>
                    <span class="text-[11px] text-emerald-600 font-semibold mt-1 block">Status: {{ transaksi.status_transaksi }}</span>
                </div>
            </div>

            <!-- Party Details: Pelanggan, Kasir, Cabang -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center gap-2 mb-3 text-sky-600 font-bold text-xs uppercase tracking-wider">
                        <User class="w-4 h-4" />
                        <span>Pelanggan</span>
                    </div>
                    <p class="text-sm font-bold text-slate-900">{{ transaksi.pelanggan?.nama_pelanggan || 'Pelanggan Umum' }}</p>
                    <p class="text-xs text-slate-500 mt-1">Kota: {{ transaksi.pelanggan?.kota || '-' }}</p>
                    <p class="text-xs text-slate-500">No. HP: {{ transaksi.pelanggan?.no_hp || '-' }}</p>
                    <p v-if="transaksi.alamat_pengiriman" class="text-xs text-slate-600 mt-2 bg-slate-50 p-2 rounded-lg">
                        <strong>Alamat Kirim:</strong> {{ transaksi.alamat_pengiriman }}
                    </p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center gap-2 mb-3 text-emerald-600 font-bold text-xs uppercase tracking-wider">
                        <Store class="w-4 h-4" />
                        <span>Cabang Pemenuhan</span>
                    </div>
                    <p class="text-sm font-bold text-slate-900">{{ transaksi.cabang?.nama_cabang || '-' }}</p>
                    <p class="text-xs text-slate-500 mt-1">Kota: {{ transaksi.cabang?.kota || '-' }}</p>
                    <p class="text-xs text-slate-500">Alamat: {{ transaksi.cabang?.alamat || '-' }}</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center gap-2 mb-3 text-purple-600 font-bold text-xs uppercase tracking-wider">
                        <CreditCard class="w-4 h-4" />
                        <span>Kasir & Pembayaran</span>
                    </div>
                    <p class="text-xs text-slate-700"><strong>Kasir:</strong> {{ transaksi.kasir?.nama_lengkap || 'Sistem Online / Self' }}</p>
                    <div class="mt-2 pt-2 border-t border-slate-100 text-xs" v-if="transaksi.pembayaran && transaksi.pembayaran.length > 0">
                        <div v-for="pay in transaksi.pembayaran" :key="pay.id_pembayaran" class="space-y-0.5">
                            <p><strong>Metode:</strong> {{ pay.metode }}</p>
                            <p><strong>Status Bayar:</strong> {{ pay.status }}</p>
                            <p v-if="pay.midtrans_transaction_id" class="text-[10px] text-slate-400 font-mono">Midtrans ID: {{ pay.midtrans_transaction_id }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Snapshot Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-800">Rincian Barang yang Dibeli</h3>
                </div>

                <div v-if="transaksi.detail_transaksi && transaksi.detail_transaksi.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">Nama Produk (Snapshot)</th>
                                <th class="py-3 px-4">SKU</th>
                                <th class="py-3 px-4 text-right">Harga Satuan</th>
                                <th class="py-3 px-4 text-center">Jumlah</th>
                                <th class="py-3 px-4 text-right">Diskon Item</th>
                                <th class="py-3 px-4 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-for="item in transaksi.detail_transaksi" :key="item.id_detail">
                                <td class="py-3 px-4 font-bold text-slate-900">{{ item.nama_produk_snapshot }}</td>
                                <td class="py-3 px-4 font-mono text-slate-500">{{ item.sku_snapshot || '-' }}</td>
                                <td class="py-3 px-4 text-right">{{ formatRupiah(item.harga_satuan) }}</td>
                                <td class="py-3 px-4 text-center font-bold">{{ item.jumlah }}</td>
                                <td class="py-3 px-4 text-right text-slate-500">{{ formatRupiah(item.diskon_item) }}</td>
                                <td class="py-3 px-4 text-right font-extrabold text-slate-900">{{ formatRupiah(item.subtotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-8 text-center text-slate-400 text-xs">
                    Rincian barang tidak tersedia untuk transaksi ini.
                </div>

                <!-- Totals Calculation Summary -->
                <div class="p-6 bg-slate-50/60 border-t border-slate-100 flex flex-col items-end text-xs space-y-1.5">
                    <div class="flex justify-between w-64 text-slate-600">
                        <span>Subtotal Produk:</span>
                        <span class="font-bold text-slate-800">{{ formatRupiah(transaksi.subtotal) }}</span>
                    </div>
                    <div v-if="Number(transaksi.diskon) > 0" class="flex justify-between w-64 text-slate-600">
                        <span>Diskon:</span>
                        <span class="font-bold text-emerald-600">-{{ formatRupiah(transaksi.diskon) }}</span>
                    </div>
                    <div class="flex justify-between w-64 text-slate-600">
                        <span>Pajak (PPN):</span>
                        <span class="font-bold text-slate-800">{{ formatRupiah(transaksi.pajak) }}</span>
                    </div>
                    <div v-if="Number(transaksi.ongkir) > 0" class="flex justify-between w-64 text-slate-600">
                        <span>Ongkos Kirim:</span>
                        <span class="font-bold text-slate-800">{{ formatRupiah(transaksi.ongkir) }}</span>
                    </div>
                    <div class="flex justify-between w-64 pt-2 border-t border-slate-200 text-sm font-black text-slate-900">
                        <span>Total Akhir:</span>
                        <span class="text-sky-600">{{ formatRupiah(transaksi.total_belanja) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

