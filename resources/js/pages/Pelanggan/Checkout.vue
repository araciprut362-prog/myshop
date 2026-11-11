<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import PelangganLayout from '@/layouts/PelangganLayout.vue';
import { formatRupiah } from '@/lib/utils';
import {
    CreditCard,
    ShieldCheck,
    Truck,
    MapPin,
    User,
    Phone,
    ArrowRight,
    ArrowLeft,
    CheckCircle2
} from 'lucide-vue-next';
import type { Pelanggan } from '@/types';

declare global {
    interface Window {
        snap: any;
    }
}

const props = defineProps<{
    pelanggan: Pelanggan;
    items: {
        id_produk: number;
        nama_produk: string;
        sku: string;
        harga: number;
        jumlah: number;
        subtotal: number;
    }[];
    ringkasan: {
        subtotal: number;
        pajak: number;
        pajak_persen: number;
        ongkir: number;
        total: number;
    };
    clientKey: string;
}>();

const form = useForm({
    nama_penerima: props.pelanggan.nama_pelanggan || '',
    no_hp: props.pelanggan.no_hp || '',
    alamat_pengiriman: props.pelanggan.alamat || '',
    kota: props.pelanggan.kota || '',
    kode_pos: props.pelanggan.kode_pos || '',
    catatan: '',
});

const isProcessing = ref(false);
const errorMessage = ref('');

const submitCheckout = () => {
    isProcessing.value = true;
    errorMessage.value = '';

    // Kirim request checkout ke backend
    fetch('/pelanggan/checkout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(form.data()),
    })
    .then(async (res) => {
        const data = await res.json();
        if (!res.ok) {
            throw new Error(data.message || 'Terjadi kesalahan saat memproses pesanan.');
        }
        return data;
    })
    .then((data) => {
        // Jika token Snap berhasil dibuat dan script snap tersedia di browser
        if (data.snap_token && typeof window.snap !== 'undefined') {
            window.snap.pay(data.snap_token, {
                onSuccess: function (result: any) {
                    router.visit(`/pelanggan/pesanan/${data.id_transaksi}/bayar`);
                },
                onPending: function (result: any) {
                    router.visit(`/pelanggan/pesanan/${data.id_transaksi}/bayar`);
                },
                onError: function (result: any) {
                    router.visit(`/pelanggan/pesanan/${data.id_transaksi}/bayar`);
                },
                onClose: function () {
                    // Pengguna menutup popup pembayaran sebelum selesai
                    router.visit(`/pelanggan/pesanan/${data.id_transaksi}/bayar`);
                }
            });
        } else {
            // Fallback direct redirect ke halaman detail pembayaran pesanan
            router.visit(`/pelanggan/pesanan/${data.id_transaksi}/bayar`);
        }
    })
    .catch((err) => {
        errorMessage.value = err.message;
        isProcessing.value = false;
    });
};
</script>

<template>
    <Head title="Checkout Pesanan Online" />

    <PelangganLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div class="flex items-center gap-2">
                <Link href="/pelanggan/keranjang" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1">
                    <ArrowLeft class="w-4 h-4" />
                    <span>Kembali ke Keranjang</span>
                </Link>
            </div>

            <h1 class="text-2xl font-extrabold text-slate-900">Checkout Pesanan</h1>

            <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                {{ errorMessage }}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Shipping Address Form -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-100 text-sm font-bold text-slate-900">
                            <MapPin class="w-4 h-4 text-sky-600" />
                            <span>Alamat Pengiriman & Penerima</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700">Nama Penerima *</label>
                                <input
                                    v-model="form.nama_penerima"
                                    type="text"
                                    required
                                    class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700">Nomor WhatsApp / HP *</label>
                                <input
                                    v-model="form.no_hp"
                                    type="tel"
                                    required
                                    class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700">Kota Tujuan *</label>
                                <input
                                    v-model="form.kota"
                                    type="text"
                                    required
                                    class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700">Kode Pos</label>
                                <input
                                    v-model="form.kode_pos"
                                    type="text"
                                    class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Alamat Lengkap *</label>
                            <textarea
                                v-model="form.alamat_pengiriman"
                                rows="2"
                                required
                                class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Catatan Khusus Pengiriman (Opsional)</label>
                            <input
                                v-model="form.catatan"
                                type="text"
                                placeholder="Contoh: Titipkan di pos satpam bila rumah kosong"
                                class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                            />
                        </div>
                    </div>

                    <!-- Ordered Items Preview -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Rincian Barang yang Dipesan</h3>
                        <div class="divide-y divide-slate-100 text-xs">
                            <div v-for="item in items" :key="item.id_produk" class="py-2.5 flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-slate-900">{{ item.nama_produk }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ item.jumlah }} x {{ formatRupiah(item.harga) }}</span>
                                </div>
                                <span class="font-extrabold text-slate-900">{{ formatRupiah(item.subtotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Total & Midtrans Payment Button -->
                <div class="space-y-4">
                    <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Ringkasan Biaya</h3>

                        <div class="space-y-2 text-xs text-slate-600">
                            <div class="flex justify-between">
                                <span>Subtotal:</span>
                                <span class="font-bold text-slate-900">{{ formatRupiah(ringkasan.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>PPN Simulasi ({{ ringkasan.pajak_persen }}%):</span>
                                <span class="font-bold text-slate-900">{{ formatRupiah(ringkasan.pajak) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Ongkos Kirim:</span>
                                <span class="font-bold text-slate-900">{{ formatRupiah(ringkasan.ongkir) }}</span>
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex justify-between items-center text-sm font-black text-slate-900">
                                <span>Total Bayar:</span>
                                <span class="text-xl text-sky-600">{{ formatRupiah(ringkasan.total) }}</span>
                            </div>
                        </div>

                        <!-- Midtrans Payment Info -->
                        <div class="p-3.5 rounded-2xl bg-sky-50 border border-sky-100 text-xs space-y-1">
                            <div class="flex items-center gap-1.5 text-sky-900 font-bold">
                                <CreditCard class="w-4 h-4 text-sky-600" />
                                <span>Midtrans Snap Sandbox</span>
                            </div>
                            <p class="text-[11px] text-sky-700">
                                Transaksi akan membuka jendela pembayaran Midtrans. Anda dapat memilih simulasi QRIS, Virtual Account, atau Kartu Kredit.
                            </p>
                        </div>

                        <button
                            @click="submitCheckout"
                            :disabled="isProcessing"
                            class="w-full flex items-center justify-center gap-2 py-4 px-4 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white font-extrabold text-sm shadow-lg shadow-sky-600/30 transition transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span>{{ isProcessing ? 'Memproses Pesanan...' : 'Bayar Sekarang' }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </PelangganLayout>
</template>

