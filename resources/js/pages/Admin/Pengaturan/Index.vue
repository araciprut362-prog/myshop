<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Settings, Save, Store } from 'lucide-vue-next';
import type { Cabang } from '@/types';

const props = defineProps<{
    pengaturan: {
        nama_website: string;
        nama_toko: string;
        tagline: string;
        alamat_toko: string;
        telepon_toko: string;
        email_toko: string;
        pajak_simulasi_persen: string;
        cabang_online_default: string;
        informasi_footer: string;
    };
    cabangList: Cabang[];
}>();

const form = useForm({
    nama_website: props.pengaturan.nama_website,
    nama_toko: props.pengaturan.nama_toko,
    tagline: props.pengaturan.tagline,
    alamat_toko: props.pengaturan.alamat_toko,
    telepon_toko: props.pengaturan.telepon_toko,
    email_toko: props.pengaturan.email_toko,
    pajak_simulasi_persen: props.pengaturan.pajak_simulasi_persen,
    cabang_online_default: props.pengaturan.cabang_online_default,
    informasi_footer: props.pengaturan.informasi_footer,
});

const submit = () => {
    form.post('/admin/pengaturan');
};
</script>

<template>
    <Head title="Pengaturan Website & Toko" />

    <AdminLayout title="Pengaturan Sistem & Toko">
        <div class="max-w-4xl space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex items-center gap-3 pb-4 mb-6 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <Settings class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Konfigurasi Toko & Branding</h2>
                        <p class="text-xs text-slate-500">Sesuaikan identitas toko, simulasi pajak, dan pengaturan cabang online</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Nama Website *</label>
                            <input
                                v-model="form.nama_website"
                                type="text"
                                required
                                class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Nama Toko *</label>
                            <input
                                v-model="form.nama_toko"
                                type="text"
                                required
                                class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Tagline Toko</label>
                        <input
                            v-model="form.tagline"
                            type="text"
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Nomor Telepon Toko</label>
                            <input
                                v-model="form.telepon_toko"
                                type="text"
                                class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Email Customer Support</label>
                            <input
                                v-model="form.email_toko"
                                type="email"
                                class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Alamat Kantor / Toko Pusat</label>
                        <textarea
                            v-model="form.alamat_toko"
                            rows="2"
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Persentase Pajak Simulasi (%) *</label>
                            <input
                                v-model="form.pajak_simulasi_persen"
                                type="number"
                                step="0.5"
                                min="0"
                                max="100"
                                required
                                class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Nilai simulasi pembelajaran (default 11%).</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Cabang Default Pemenuhan Online *</label>
                            <select
                                v-model="form.cabang_online_default"
                                required
                                class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                            >
                                <option v-for="c in cabangList" :key="c.id_cabang" :value="c.id_cabang">
                                    {{ c.nama_cabang }} ({{ c.kota }})
                                </option>
                            </select>
                            <p class="text-[10px] text-slate-400 mt-1">Stok cabang ini yang dipotong untuk pesanan e-commerce.</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Teks Footer Hak Cipta</label>
                        <input
                            v-model="form.informasi_footer"
                            type="text"
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        />
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-xs transition"
                        >
                            <Save class="w-4 h-4" />
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

