<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PelangganLayout from '@/layouts/PelangganLayout.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah, formatDate } from '@/lib/utils';
import {
    CreditCard,
    CheckCircle2,
    Clock,
    AlertCircle,
    ArrowLeft,
    RefreshCw,
    Sparkles,
    ShieldCheck
} from 'lucide-vue-next';
import type { Transaksi } from '@/types';

declare global {
    interface Window {
        snap: any;
    }
}

const props = defineProps<{
    pesanan: Transaksi;
    clientKey: string;
}>();

const snapToken = props.pesanan.pembayaran_terbaru?.snap_token;
const isSimulating = ref(false);
const isChecking = ref(false);

const checkStatus = () => {
    isChecking.value = true;
    router.post(`/pelanggan/pesanan/${props.pesanan.id_transaksi}/sync`, {}, {
        preserveScroll: true,
        onFinish: () => {
            isChecking.value = false;
        }
    });
};

const openSnap = () => {
    if (snapToken && typeof window.snap !== 'undefined') {
        window.snap.pay(snapToken, {
            onSuccess: function (result: any) {
                checkStatus();
            },
            onPending: function (result: any) {
                checkStatus();
            },
            onError: function (result: any) {
                checkStatus();
            },
            onClose: function () {
                checkStatus();
            }
        });
    } else {
        alert('Midtrans Snap SDK sedang memuat atau menggunakan token simulasi.');
    }
};

const simulatePayment = (status: 'settlement' | 'expire' | 'cancel') => {
    isSimulating.value = true;
    router.post('/payment/midtrans/simulate', {
        order_id: props.pesanan.kode_transaksi,
        status: status,
    }, {
        onFinish: () => {
            isSimulating.value = false;
        }
    });
};

onMounted(() => {
    // Jika masih pending dan belum pernah bayar, otomatis pemicu snap bila token tersedia
    if (props.pesanan.status_pembayaran === 'PENDING' && snapToken && typeof window.snap !== 'undefined' && !snapToken.startsWith('SANDBOX-TOKEN-')) {
        // window.snap.pay(snapToken, ...);
    }
});
</script>

<template>
    <Head :title="`Pembayaran Pesanan - ${pesanan.kode_transaksi}`" />

    <PelangganLayout>
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
            <Link href="/pelanggan/pesanan" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                <ArrowLeft class="w-4 h-4" />
                <span>Kembali ke Daftar Pesanan</span>
            </Link>

            <!-- Success State -->
            <div
                v-if="pesanan.status_pembayaran === 'BERHASIL'"
                class="bg-white p-8 rounded-3xl border border-emerald-200 shadow-xl text-center space-y-4"
            >
                <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
                    <CheckCircle2 class="w-10 h-10" />
                </div>

                <div>
                    <h2 class="text-2xl font-black text-slate-900">Pembayaran Berhasil!</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Terima kasih, pembayaran pesanan <strong>{{ pesanan.kode_transaksi }}</strong> telah kami terima.
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-left space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Total Terbayar:</span>
                        <span class="font-extrabold text-slate-900">{{ formatRupiah(pesanan.total_belanja) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Status Pesanan:</span>
                        <Badge variant="success">{{ pesanan.status_transaksi }}</Badge>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-center gap-3">
                    <Link
                        :href="`/pelanggan/pesanan/${pesanan.id_transaksi}/nota`"
                        class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition shadow-xs"
                    >
                        Cetak Bukti Pembayaran
                    </Link>
                    <Link
                        href="/pelanggan/pesanan"
                        class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition"
                    >
                        Lihat Semua Pesanan
                    </Link>
                </div>
            </div>

            <!-- Pending Payment State -->
            <div
                v-else-if="pesanan.status_pembayaran === 'PENDING' && pesanan.status_transaksi !== 'DIBATALKAN'"
                class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-xl space-y-6"
            >
                <div class="text-center space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center mx-auto">
                        <CreditCard class="w-6 h-6" />
                    </div>
                    <h2 class="text-xl font-extrabold text-slate-900">Selesaikan Pembayaran Anda</h2>
                    <p class="text-xs text-slate-500">
                        Order ID: <span class="font-mono font-bold text-slate-800">{{ pesanan.kode_transaksi }}</span>
                    </p>
                </div>

                <!-- Total Amount Banner -->
                <div class="p-5 rounded-2xl bg-gradient-to-r from-sky-900 to-slate-900 text-white text-center">
                    <span class="text-xs text-sky-200 block">Total Tagihan:</span>
                    <span class="text-3xl font-black block mt-1">{{ formatRupiah(pesanan.total_belanja) }}</span>
                    <span class="text-[11px] text-sky-300 mt-1 block">Termasuk PPN & Ongkos Kirim</span>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-2">
                    <button
                        @click="openSnap"
                        class="w-full py-4 px-6 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white text-sm font-extrabold shadow-lg shadow-sky-600/30 transition transform hover:-translate-y-0.5 flex items-center justify-center gap-2"
                    >
                        <CreditCard class="w-4 h-4" />
                        <span>Buka Jendela Midtrans Snap</span>
                    </button>

                    <button
                        type="button"
                        @click="checkStatus"
                        :disabled="isChecking"
                        class="w-full py-3 px-6 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isChecking }" />
                        <span>{{ isChecking ? 'Sedang Memeriksa Status ke Midtrans...' : 'Cek Status Pembayaran (Sinkronkan dari Midtrans)' }}</span>
                    </button>
                </div>

                <!-- Local Sandbox Simulation Controls -->
                <div class="pt-6 border-t border-slate-100 space-y-3">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                        <Sparkles class="w-4 h-4 text-amber-500" />
                        <span>Simulasi Pembayaran (Midtrans Sandbox Simulator)</span>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Tombol simulasi ini memproses webhook resmi ke backend untuk menguji sinkronisasi status dan perubahan stok tanpa perlu menunggu tunnel eksternal.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                        <button
                            type="button"
                            @click="simulatePayment('settlement')"
                            :disabled="isSimulating"
                            class="py-2.5 px-3 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 text-xs font-bold transition disabled:opacity-50 text-center"
                        >
                            ✓ Simulasikan Berhasil
                        </button>
                        <button
                            type="button"
                            @click="simulatePayment('expire')"
                            :disabled="isSimulating"
                            class="py-2.5 px-3 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-800 text-xs font-bold transition disabled:opacity-50 text-center"
                        >
                            ⏱ Simulasikan Kedaluwarsa
                        </button>
                        <button
                            type="button"
                            @click="simulatePayment('cancel')"
                            :disabled="isSimulating"
                            class="py-2.5 px-3 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-800 text-xs font-bold transition disabled:opacity-50 text-center"
                        >
                            ✕ Simulasikan Dibatalkan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Expired / Canceled State -->
            <div
                v-else
                class="bg-white p-8 rounded-3xl border border-rose-200 shadow-xl text-center space-y-4"
            >
                <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
                    <AlertCircle class="w-10 h-10" />
                </div>

                <div>
                    <h2 class="text-xl font-bold text-slate-900">Pesanan Telah {{ pesanan.status_pembayaran }}</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Pesanan ini tidak dapat dibayar lagi. Reservasi stok barang telah otomatis dikembalikan ke sistem.
                    </p>
                </div>

                <div class="pt-4">
                    <Link
                        href="/katalog"
                        class="px-5 py-2.5 rounded-xl bg-sky-600 text-white text-xs font-bold hover:bg-sky-700 transition shadow-xs"
                    >
                        Buat Pesanan Baru
                    </Link>
                </div>
            </div>
        </div>
    </PelangganLayout>
</template>

