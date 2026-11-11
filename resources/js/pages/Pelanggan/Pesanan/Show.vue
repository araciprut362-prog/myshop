<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PelangganLayout from '@/layouts/PelangganLayout.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import {
    ArrowLeft,
    Printer,
    CreditCard,
    MapPin,
    Truck,
    Package,
    CheckCircle2,
    Clock,
    AlertCircle
} from 'lucide-vue-next';
import type { Transaksi } from '@/types';

defineProps<{
    pesanan: Transaksi;
}>();
</script>

<template>
    <Head :title="`Detail Pesanan - ${pesanan.kode_transaksi}`" />

    <PelangganLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div class="flex items-center justify-between">
                <Link href="/pelanggan/pesanan" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                    <ArrowLeft class="w-4 h-4" />
                    <span>Kembali ke Pesanan Saya</span>
                </Link>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="pesanan.status_pembayaran === 'PENDING' && pesanan.status_transaksi !== 'DIBATALKAN'"
                        :href="`/pelanggan/pesanan/${pesanan.id_transaksi}/bayar`"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 shadow-xs transition"
                    >
                        Lanjutkan Pembayaran
                    </Link>

                    <Link
                        :href="`/pelanggan/pesanan/${pesanan.id_transaksi}/nota`"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition"
                    >
                        <Printer class="w-4 h-4 text-slate-500" />
                        <span>Cetak Bukti Pembelian</span>
                    </Link>
                </div>
            </div>

            <!-- Order Status Banner -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                    <div>
                        <span class="text-xs font-mono font-bold text-slate-400">Order ID: {{ pesanan.kode_transaksi }}</span>
                        <h2 class="text-xl font-extrabold text-slate-900 mt-0.5">{{ formatRupiah(pesanan.total_belanja) }}</h2>
                        <p class="text-xs text-slate-500 mt-1">Tanggal Pesanan: {{ formatDate(pesanan.waktu_transaksi) }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Badge :variant="pesanan.status_pembayaran === 'BERHASIL' ? 'success' : pesanan.status_pembayaran === 'PENDING' ? 'warning' : 'danger'">
                            Pembayaran: {{ pesanan.status_pembayaran }}
                        </Badge>
                        <Badge :variant="pesanan.status_transaksi === 'SELESAI' ? 'success' : pesanan.status_transaksi === 'DIBATALKAN' ? 'danger' : 'info'">
                            Status: {{ pesanan.status_transaksi }}
                        </Badge>
                    </div>
                </div>

                <!-- Shipping Address Card -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs space-y-1">
                    <span class="font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                        <MapPin class="w-3.5 h-3.5 text-sky-600" />
                        <span>Tujuan Pengiriman</span>
                    </span>
                    <p class="font-semibold text-slate-900">{{ pesanan.pelanggan?.nama_pelanggan }} ({{ pesanan.pelanggan?.no_hp }})</p>
                    <p class="text-slate-600">{{ pesanan.alamat_pengiriman || pesanan.pelanggan?.alamat }}</p>
                    <p v-if="pesanan.catatan" class="text-amber-800 pt-1">
                        <strong>Catatan:</strong> {{ pesanan.catatan }}
                    </p>
                </div>
            </div>

            <!-- Products List -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900">Rincian Barang yang Dipesan</h3>
                </div>

                <div class="divide-y divide-slate-100 text-xs">
                    <div
                        v-for="item in pesanan.detail_transaksi"
                        :key="item.id_detail"
                        class="p-4 flex items-center justify-between gap-4"
                    >
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm">{{ item.nama_produk_snapshot }}</h4>
                            <p class="text-[11px] text-slate-400 font-mono mt-0.5">SKU: {{ item.sku_snapshot }}</p>
                            <p class="text-slate-600 mt-1">{{ item.jumlah }} unit &times; {{ formatRupiah(item.harga_satuan) }}</p>
                        </div>
                        <span class="font-extrabold text-sm text-slate-900">{{ formatRupiah(item.subtotal) }}</span>
                    </div>
                </div>

                <!-- Totals Breakdown -->
                <div class="p-6 bg-slate-50/60 border-t border-slate-100 flex flex-col items-end text-xs space-y-1.5">
                    <div class="flex justify-between w-64 text-slate-600">
                        <span>Subtotal:</span>
                        <span class="font-bold text-slate-800">{{ formatRupiah(pesanan.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between w-64 text-slate-600">
                        <span>PPN (Simulasi):</span>
                        <span class="font-bold text-slate-800">{{ formatRupiah(pesanan.pajak) }}</span>
                    </div>
                    <div class="flex justify-between w-64 text-slate-600">
                        <span>Ongkos Kirim:</span>
                        <span class="font-bold text-slate-800">{{ formatRupiah(pesanan.ongkir) }}</span>
                    </div>
                    <div class="flex justify-between w-64 pt-2 border-t border-slate-200 text-base font-black text-slate-900">
                        <span>Total:</span>
                        <span class="text-sky-600">{{ formatRupiah(pesanan.total_belanja) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </PelangganLayout>
</template>

