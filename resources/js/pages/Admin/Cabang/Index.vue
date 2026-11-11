<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Modal from '@/components/Modal.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah } from '@/lib/utils';
import { Plus, Edit2, Power, Store, MapPin, Phone } from 'lucide-vue-next';
import type { Cabang } from '@/types';

interface CabangWithSales extends Cabang {
    jumlah_transaksi?: number;
    total_penjualan?: number;
}

const props = defineProps<{
    cabang: CabangWithSales[];
}>();

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    kode_cabang: '',
    nama_cabang: '',
    kota: '',
    alamat: '',
    telepon: '',
    status: 'AKTIF',
});

const openAddModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.status = 'AKTIF';
    showModal.value = true;
};

const openEditModal = (c: CabangWithSales) => {
    isEditing.value = true;
    editingId.value = c.id_cabang;
    form.clearErrors();
    form.kode_cabang = c.kode_cabang;
    form.nama_cabang = c.nama_cabang;
    form.kota = c.kota;
    form.alamat = c.alamat || '';
    form.telepon = c.telepon || '';
    form.status = c.status;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value && editingId.value) {
        form.put(`/admin/cabang/${editingId.value}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/admin/cabang', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const toggleStatus = (id: number) => {
    router.patch(`/admin/cabang/${id}/toggle-status`, {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Manajemen Cabang Toko" />

    <AdminLayout title="Manajemen Cabang Toko">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Daftar Cabang Toko MyShop</h2>
                    <p class="text-xs text-slate-500">Kelola lokasi fisik toko dan data omzet setiap cabang</p>
                </div>
                <button
                    @click="openAddModal"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-xs transition"
                >
                    <Plus class="w-4 h-4" />
                    <span>Tambah Cabang</span>
                </button>
            </div>

            <!-- Branch Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="c in cabang"
                    :key="c.id_cabang"
                    class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-sky-300 transition flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-mono font-bold text-sky-600 bg-sky-50 px-2 py-0.5 rounded border border-sky-100">
                                {{ c.kode_cabang }}
                            </span>
                            <Badge :variant="c.status === 'AKTIF' ? 'success' : 'neutral'">
                                {{ c.status }}
                            </Badge>
                        </div>

                        <h3 class="text-base font-bold text-slate-900">{{ c.nama_cabang }}</h3>
                        <p class="text-xs text-slate-500 flex items-center gap-1 mt-1">
                            <MapPin class="w-3.5 h-3.5 text-slate-400" /> {{ c.kota }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-2 line-clamp-2">{{ c.alamat || '-' }}</p>
                        <p v-if="c.telepon" class="text-[11px] text-slate-500 flex items-center gap-1 mt-1">
                            <Phone class="w-3 h-3 text-slate-400" /> {{ c.telepon }}
                        </p>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <div class="mb-3">
                            <span class="text-[10px] text-slate-400 block uppercase font-semibold">Total Omzet Penjualan</span>
                            <span class="text-sm font-extrabold text-slate-900">{{ formatRupiah(c.total_penjualan || 0) }}</span>
                            <span class="text-[10px] text-slate-500 block">({{ c.jumlah_transaksi || 0 }} Transaksi)</span>
                        </div>

                        <div class="flex items-center justify-end gap-1">
                            <button
                                @click="openEditModal(c)"
                                title="Edit Cabang"
                                class="p-1.5 rounded-lg text-slate-600 hover:text-sky-600 hover:bg-sky-50 transition"
                            >
                                <Edit2 class="w-4 h-4" />
                            </button>
                            <button
                                @click="toggleStatus(c.id_cabang)"
                                :title="c.status === 'AKTIF' ? 'Nonaktifkan' : 'Aktifkan'"
                                class="p-1.5 rounded-lg text-slate-600 hover:text-amber-600 hover:bg-amber-50 transition"
                            >
                                <Power class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Modal :show="showModal" :title="isEditing ? 'Edit Cabang' : 'Tambah Cabang Baru'" @close="showModal = false">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Kode Cabang *</label>
                        <input
                            v-model="form.kode_cabang"
                            type="text"
                            required
                            placeholder="CBG005"
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono uppercase focus:border-sky-500"
                        />
                        <p v-if="form.errors.kode_cabang" class="text-rose-600 text-[10px] mt-1">{{ form.errors.kode_cabang }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Nama Cabang *</label>
                        <input
                            v-model="form.nama_cabang"
                            type="text"
                            required
                            placeholder="Cabang Surabaya"
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        />
                        <p v-if="form.errors.nama_cabang" class="text-rose-600 text-[10px] mt-1">{{ form.errors.nama_cabang }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Kota *</label>
                        <input
                            v-model="form.kota"
                            type="text"
                            required
                            placeholder="Surabaya"
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        />
                        <p v-if="form.errors.kota" class="text-rose-600 text-[10px] mt-1">{{ form.errors.kota }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Nomor Telepon</label>
                        <input
                            v-model="form.telepon"
                            type="text"
                            placeholder="031-1234567"
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700">Alamat Lengkap</label>
                    <textarea
                        v-model="form.alamat"
                        rows="2"
                        placeholder="Jl. Raya No. 1..."
                        class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700">Status *</label>
                    <select
                        v-model="form.status"
                        required
                        class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                    >
                        <option value="AKTIF">AKTIF</option>
                        <option value="NONAKTIF">NONAKTIF</option>
                    </select>
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
                        {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Cabang') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>

