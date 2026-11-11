<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { formatRupiah, formatDate } from '@/lib/utils';
import { Printer, ShoppingBag, Store, ArrowLeft } from 'lucide-vue-next';
import type { Transaksi } from '@/types';

defineProps<{
    transaksi: Transaksi;
}>();

const printReceipt = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Nota Penjualan POS - ${transaksi.kode_transaksi}`" />

    <div class="min-h-screen bg-slate-100 py-8 px-4 flex flex-col items-center">
        <!-- Action Buttons (Hidden on Print) -->
        <div class="no-print w-full max-w-sm flex items-center justify-between mb-6">
            <Link
                href="/kasir/pos"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition shadow-xs"
            >
                <ArrowLeft class="w-4 h-4" />
                <span>Transaksi Baru</span>
            </Link>

            <button
                @click="printReceipt"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/30 transition"
            >
                <Printer class="w-4 h-4" />
                <span>Cetak Struk (80mm)</span>
            </button>
        </div>

        <!-- Thermal Receipt (80mm) -->
        <div class="receipt-card bg-white p-6 shadow-xl rounded-2xl border border-slate-200 w-full max-w-sm text-slate-900 font-mono text-xs">
            <!-- Header Brand -->
            <div class="text-center pb-4 border-b border-dashed border-slate-300">
                <div class="flex items-center justify-center gap-2 mb-1">
                    <Store class="w-5 h-5 text-emerald-600" />
                    <span class="text-base font-black tracking-tight text-slate-900">MYSHOP</span>
                </div>
                <p class="text-[10px] text-slate-500 font-sans uppercase">Point of Sale & Toko Online</p>
                <p class="text-[11px] font-bold text-slate-800 mt-1">
                    {{ transaksi.cabang?.nama_cabang || 'Cabang Utama' }}
                </p>
                <p class="text-[10px] text-slate-500">
                    {{ transaksi.cabang?.alamat || 'Jl. Diponegoro No. 10' }}, {{ transaksi.cabang?.kota || 'Ponorogo' }}
                </p>
                <p v-if="transaksi.cabang?.telepon" class="text-[10px] text-slate-500">
                    Telp: {{ transaksi.cabang?.telepon }}
                </p>
            </div>

            <!-- Transaction Metadata -->
            <div class="py-3 border-b border-dashed border-slate-300 space-y-1 text-[11px]">
                <div class="flex justify-between">
                    <span class="text-slate-500">No. Nota:</span>
                    <span class="font-bold text-slate-900">{{ transaksi.kode_transaksi }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Tanggal:</span>
                    <span>{{ formatDate(transaksi.waktu_transaksi) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Kasir:</span>
                    <span>{{ transaksi.kasir?.nama_lengkap || '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Pelanggan:</span>
                    <span class="font-semibold">{{ transaksi.pelanggan?.nama_pelanggan || 'Pelanggan Umum' }}</span>
                </div>
            </div>

            <!-- Items Snapshot List -->
            <div class="py-3 border-b border-dashed border-slate-300">
                <div v-if="transaksi.detail_transaksi && transaksi.detail_transaksi.length > 0" class="space-y-2">
                    <div v-for="item in transaksi.detail_transaksi" :key="item.id_detail">
                        <div class="font-bold text-slate-900">{{ item.nama_produk_snapshot }}</div>
                        <div class="flex justify-between text-[11px] text-slate-600 pl-2">
                            <span>{{ item.jumlah }} x {{ formatRupiah(item.harga_satuan) }}</span>
                            <span class="font-bold text-slate-900">{{ formatRupiah(item.subtotal) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subtotal, Diskon, Pajak, Total -->
            <div class="py-3 border-b border-dashed border-slate-300 space-y-1 text-[11px]">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span>{{ formatRupiah(transaksi.subtotal) }}</span>
                </div>
                <div v-if="Number(transaksi.diskon) > 0" class="flex justify-between text-emerald-600">
                    <span>Diskon:</span>
                    <span>-{{ formatRupiah(transaksi.diskon) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Pajak (PPN):</span>
                    <span>{{ formatRupiah(transaksi.pajak) }}</span>
                </div>
                <div class="flex justify-between text-sm font-black text-slate-900 pt-1 border-t border-slate-200">
                    <span>TOTAL:</span>
                    <span class="text-emerald-700">{{ formatRupiah(transaksi.total_belanja) }}</span>
                </div>
            </div>

            <!-- Payment Breakdown -->
            <div class="py-3 border-b border-dashed border-slate-300 text-[11px] space-y-1">
                <div class="flex justify-between">
                    <span class="text-slate-500">Metode Pembayaran:</span>
                    <span class="font-bold">{{ transaksi.pembayaran_terbaru?.metode || 'TUNAI' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Status Pembayaran:</span>
                    <span class="font-bold text-emerald-700">LUNAS (BERHASIL)</span>
                </div>
            </div>

            <!-- Footer Message -->
            <div class="pt-4 text-center space-y-1 text-[10px] text-slate-500 font-sans">
                <p class="font-bold text-slate-800">*** TERIMA KASIH ATAS KUNJUNGAN ANDA ***</p>
                <p>Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.</p>
                <p class="text-[9px] text-slate-400">MyShop &bull; Belanja Mudah, Cepat, dan Nyaman</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    body, html {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .receipt-card {
        border: none !important;
        box-shadow: none !important;
        max-width: 100% !important;
        width: 80mm !important;
        padding: 4mm !important;
        margin: 0 auto !important;
    }
}
</style>

