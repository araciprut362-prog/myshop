<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import Modal from '@/components/Modal.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah } from '@/lib/utils';
import { Search, Eye, Edit2, Users, MapPin, Phone, Mail } from 'lucide-vue-next';
import type { Paginated } from '@/types';

interface PelangganItem {
    id_pelanggan: number;
    nama_pelanggan: string;
    kota: string | null;
    no_hp: string | null;
    email: string | null;
    status_akun: string | null;
    jumlah_transaksi: number;
    total_belanja: number;
    kategori_belanja: string;
}

const props = defineProps<{
    pelanggan: Paginated<PelangganItem>;
    daftarKota: string[];
    filters: {
        search?: string;
        kota?: string;
    };
}>();

const search = ref(props.filters.search || '');
const selectedKota = ref(props.filters.kota || '');

const filterData = () => {
    router.get('/admin/pelanggan', {
        search: search.value || undefined,
        kota: selectedKota.value || undefined,
    }, { preserveState: true, replace: true });
};

// Edit Modal
const showModal = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    nama_pelanggan: '',
    kota: '',
    no_hp: '',
    alamat: '',
    kode_pos: '',
});

const openEditModal = (item: PelangganItem) => {
    editingId.value = item.id_pelanggan;
    form.clearErrors();
    form.nama_pelanggan = item.nama_pelanggan;
    form.kota = item.kota || '';
    form.no_hp = item.no_hp || '';
    form.alamat = '';
    form.kode_pos = '';
    showModal.value = true;
};

const submitEdit = () => {
    if (editingId.value) {
        form.put(`/admin/pelanggan/${editingId.value}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};
</script>

<template>
    <Head title="Manajemen Pelanggan" />

    <AdminLayout title="Manajemen Pelanggan">
        <div class="space-y-6">
            <!-- Filter Bar -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-64">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                            v-model="search"
                            @keyup.enter="filterData"
                            type="text"
                            placeholder="Cari nama, kota, email..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-sky-500"
                        />
                    </div>

                    <select
                        v-model="selectedKota"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Kota</option>
                        <option v-for="kota in daftarKota" :key="kota" :value="kota">
                            {{ kota }}
                        </option>
                    </select>

                    <button
                        @click="filterData"
                        class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">Nama Pelanggan</th>
                                <th class="py-3 px-4">Kontak & Kota</th>
                                <th class="py-3 px-4 text-center">Total Transaksi</th>
                                <th class="py-3 px-4 text-right">Total Belanja</th>
                                <th class="py-3 px-4 text-center">Kategori Belanja</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-if="pelanggan.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    Tidak ada data pelanggan yang sesuai filter.
                                </td>
                            </tr>
                            <tr
                                v-for="item in pelanggan.data"
                                :key="item.id_pelanggan"
                                class="hover:bg-slate-50/60 transition"
                            >
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-sky-100 text-sky-700 font-bold flex items-center justify-center shrink-0 text-xs">
                                            {{ item.nama_pelanggan.charAt(0) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block">{{ item.nama_pelanggan }}</span>
                                            <span v-if="item.email" class="text-[11px] text-slate-400 flex items-center gap-1">
                                                <Mail class="w-3 h-3 text-slate-400" /> {{ item.email }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="text-slate-800 flex items-center gap-1">
                                        <MapPin class="w-3.5 h-3.5 text-slate-400" /> {{ item.kota || '-' }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                                        <Phone class="w-3 h-3 text-slate-400" /> {{ item.no_hp || '-' }}
                                    </p>
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-slate-800">
                                    {{ item.jumlah_transaksi }}
                                </td>
                                <td class="py-3 px-4 text-right font-extrabold text-slate-900">
                                    {{ formatRupiah(item.total_belanja) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Badge :variant="item.kategori_belanja.includes('Gold') ? 'warning' : item.kategori_belanja.includes('Silver') ? 'info' : 'neutral'">
                                        {{ item.kategori_belanja }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <Link
                                            :href="`/admin/pelanggan/${item.id_pelanggan}`"
                                            title="Lihat Riwayat Transaksi"
                                            class="p-1.5 rounded-lg text-slate-600 hover:text-sky-600 hover:bg-sky-50 transition"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </Link>
                                        <button
                                            @click="openEditModal(item)"
                                            title="Edit Profil"
                                            class="p-1.5 rounded-lg text-slate-600 hover:text-amber-600 hover:bg-amber-50 transition"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Menampilkan {{ pelanggan.from || 0 }} - {{ pelanggan.to || 0 }} dari {{ pelanggan.total }} pelanggan</span>
                    <Pagination :links="pelanggan.links" />
                </div>
            </div>
        </div>

        <!-- Edit Pelanggan Modal -->
        <Modal :show="showModal" title="Edit Data Profil Pelanggan" @close="showModal = false">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Nama Pelanggan *</label>
                    <input
                        v-model="form.nama_pelanggan"
                        type="text"
                        required
                        class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                    />
                    <p class="text-[10px] text-slate-400 mt-1">Perubahan nama akan otomatis tercatat ke log perubahan pelanggan oleh trigger database.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Kota</label>
                        <input
                            v-model="form.kota"
                            type="text"
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Nomor WhatsApp / HP</label>
                        <input
                            v-model="form.no_hp"
                            type="tel"
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4">
                    <button
                        type="button"
                        @click="showModal = false"
                        class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-xs transition"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Data' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>

