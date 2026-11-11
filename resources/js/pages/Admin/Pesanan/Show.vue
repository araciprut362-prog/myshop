<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import { ArrowLeft, Package, User, MapPin, Truck, CheckCircle, XCircle, CreditCard, Clock } from 'lucide-vue-next';
import type { Transaksi } from '@/types';

const props = defineProps<{
    pesanan: Transaksi;
}>();

const form = useForm({
    status_transaksi: props.pesanan.status_transaksi,
});

const updateStatus = (newStatus: string) => {
    if (newStatus === 'DIBATALKAN' && !confirm('Yakin ingin membatalkan pesanan ini? Stok barang akan dikembalikan ke cabang pemenuhan.')) {
        return;
    }

    form.status_transaksi = newStatus as any;
    form.patch(`/admin/pesanan/${props.pesanan.id_transaksi}/status`);
};
</script>

<template>
    <Head :title="`Kelola Pesanan - ${pesanan.kode_transaksi}`" />

    <AdminLayout :title="`Kelola Pesanan: ${pesanan.kode_transaksi}`">
        <div class="space-y-6 max-w-5xl">
            <div class="flex items-center justify-between">
                <Link href="/admin/pesanan" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                    <ArrowLeft class="w-4 h-4" />
                    <span>Kembali ke Daftar Pesanan</span>
                </Link>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Status Saat Ini:</span>
                    <Badge :variant="pesanan.status_transaksi === 'SELESAI' ? 'success' : pesanan.status_transaksi === 'DIBATALKAN' ? 'danger' : 'info'">
                        {{ pesanan.status_transaksi }}
                    </Badge>
                </div>
            </div>

            <!-- Header & Workflow Action Bar -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-mono font-bold text-slate-400">Order ID: {{ pesanan.kode_transaksi }}</span>
                    <h2 class="text-xl font-extrabold text-slate-900 mt-0.5">{{ formatRupiah(pesanan.total_belanja) }}</h2>
                    <p class="text-xs text-slate-500 mt-1">Dipesan pada: {{ formatDate(pesanan.waktu_transaksi) }}</p>
                </div>

                <!-- Status Update Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-if="pesanan.status_pembayaran === 'BERHASIL' && pesanan.status_transaksi === 'MENUNGGU_PEMBAYARAN'"
                        @click="updateStatus('DIPROSES')"
                        :disabled="form.processing"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 shadow-xs transition"
                    >
                        Proses Pesanan
                    </button>

                    <button
                        v-if="pesanan.status_transaksi === 'DIPROSES'"
                        @click="updateStatus('DIKIRIM')"
                        :disabled="form.processing"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition"
                    >
                        Tandai Dikirim
                    </button>

                    <button
                        v-if="pesanan.status_transaksi === 'DIKIRIM'"
                        @click="updateStatus('SELESAI')"
                        :disabled="form.processing"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs transition"
                    >
                        Selesaikan Pesanan
                    </button>

                    <button
                        v-if="pesanan.status_transaksi !== 'SELESAI' && pesanan.status_transaksi !== 'DIBATALKAN'"
                        @click="updateStatus('DIBATALKAN')"
                        :disabled="form.processing"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 transition"
                    >
                        Batalkan Pesanan
                    </button>
                </div>
            </div>

            <!-- Customer & Shipping Card -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center gap-2 mb-3 text-sky-600 font-bold text-xs uppercase tracking-wider">
                        <User class="w-4 h-4" />
                        <span>Data Pemesan & Penerima</span>
                    </div>
                    <p class="text-sm font-bold text-slate-900">{{ pesanan.pelanggan?.nama_pelanggan }}</p>
                    <p class="text-xs text-slate-500 mt-1">No. WhatsApp/HP: <span class="font-semibold text-slate-800">{{ pesanan.pelanggan?.no_hp || '-' }}</span></p>
                    <p class="text-xs text-slate-500 mt-1">Kota: <span class="font-semibold text-slate-800">{{ pesanan.pelanggan?.kota || '-' }}</span></p>
                    <div class="mt-3 p-3 rounded-xl bg-slate-50 text-xs text-slate-700 border border-slate-100">
                        <strong class="block text-slate-900 mb-0.5">Alamat Pengiriman:</strong>
                        {{ pesanan.alamat_pengiriman || pesanan.pelanggan?.alamat || '-' }}
                    </div>
                    <p v-if="pesanan.catatan" class="mt-2 text-xs text-amber-800 bg-amber-50 p-2 rounded-lg border border-amber-100">
                        <strong>Catatan Pesanan:</strong> {{ pesanan.catatan }}
                    </p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="flex items-center gap-2 mb-3 text-purple-600 font-bold text-xs uppercase tracking-wider">
                        <CreditCard class="w-4 h-4" />
                        <span>Status Pembayaran Midtrans</span>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between items-center py-1 border-b border-slate-100">
                            <span class="text-slate-500">Status Pembayaran:</span>
                            <Badge :variant="pesanan.status_pembayaran === 'BERHASIL' ? 'success' : pesanan.status_pembayaran === 'PENDING' ? 'warning' : 'danger'">
                                {{ pesanan.status_pembayaran }}
                            </Badge>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-slate-100">
                            <span class="text-slate-500">Metode:</span>
                            <span class="font-bold text-slate-900">MIDTRANS (Sandbox)</span>
                        </div>
                        <div class="flex justify-between items-center py-1 border-b border-slate-100">
                            <span class="text-slate-500">Status Reservasi Stok:</span>
                            <span class="font-semibold text-slate-800">{{ pesanan.status_stok }}</span>
                        </div>
                        <div v-if="pesanan.pembayaran && pesanan.pembayaran.length > 0">
                            <div v-for="pay in pesanan.pembayaran" :key="pay.id_pembayaran" class="pt-2 text-[11px] text-slate-500 space-y-1">
                                <p v-if="pay.midtrans_transaction_id"><strong>Midtrans ID:</strong> <span class="font-mono text-slate-800">{{ pay.midtrans_transaction_id }}</span></p>
                                <p v-if="pay.midtrans_payment_type"><strong>Tipe Pembayaran:</strong> {{ pay.midtrans_payment_type }}</p>
                                <p v-if="pay.waktu_pembayaran"><strong>Waktu Bayar:</strong> {{ formatDate(pay.waktu_pembayaran) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-800">Daftar Produk Pesanan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">Nama Produk</th>
                                <th class="py-3 px-4">SKU</th>
                                <th class="py-3 px-4 text-right">Harga Satuan</th>
                                <th class="py-3 px-4 text-center">Jumlah</th>
                                <th class="py-3 px-4 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in pesanan.detail_transaksi" :key="item.id_detail">
                                <td class="py-3 px-4 font-bold text-slate-900">{{ item.nama_produk_snapshot }}</td>
                                <td class="py-3 px-4 font-mono text-slate-500">{{ item.sku_snapshot }}</td>
                                <td class="py-3 px-4 text-right">{{ formatRupiah(item.harga_satuan) }}</td>
                                <td class="py-3 px-4 text-center font-bold">{{ item.jumlah }}</td>
                                <td class="py-3 px-4 text-right font-extrabold text-slate-900">{{ formatRupiah(item.subtotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="p-5 bg-slate-50/60 border-t border-slate-100 flex flex-col items-end text-xs space-y-1.5">
                    <div class="flex justify-between w-64 text-slate-600">
                        <span>Subtotal:</span>
                        <span class="font-bold text-slate-800">{{ formatRupiah(pesanan.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between w-64 text-slate-600">
                        <span>Pajak (PPN):</span>
                        <span class="font-bold text-slate-800">{{ formatRupiah(pesanan.pajak) }}</span>
                    </div>
                    <div class="flex justify-between w-64 text-slate-600">
                        <span>Ongkir:</span>
                        <span class="font-bold text-slate-800">{{ formatRupiah(pesanan.ongkir) }}</span>
                    </div>
                    <div class="flex justify-between w-64 pt-2 border-t border-slate-200 text-sm font-black text-slate-900">
                        <span>Total Pembayaran:</span>
                        <span class="text-sky-600">{{ formatRupiah(pesanan.total_belanja) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

