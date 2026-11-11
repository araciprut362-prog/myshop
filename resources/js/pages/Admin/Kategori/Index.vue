<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Modal from '@/components/Modal.vue';
import Badge from '@/components/Badge.vue';
import { Plus, Edit2, Trash2, Power, FolderTree } from 'lucide-vue-next';
import type { Kategori } from '@/types';

defineProps<{
    kategori: (Kategori & { produk_count: number })[];
}>();

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    nama_kategori: '',
    deskripsi: '',
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

const openEditModal = (kat: Kategori) => {
    isEditing.value = true;
    editingId.value = kat.id_kategori;
    form.clearErrors();
    form.nama_kategori = kat.nama_kategori;
    form.deskripsi = kat.deskripsi || '';
    form.status = kat.status;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value && editingId.value) {
        form.put(`/admin/kategori/${editingId.value}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/admin/kategori', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const toggleStatus = (id: number) => {
    router.patch(`/admin/kategori/${id}/toggle-status`, {}, { preserveScroll: true });
};

const deleteKategori = (kat: Kategori) => {
    if (confirm(`Yakin ingin menghapus kategori "${kat.nama_kategori}"?`)) {
        router.delete(`/admin/kategori/${kat.id_kategori}`, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Manajemen Kategori Produk" />

    <AdminLayout title="Manajemen Kategori">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Daftar Kategori Produk</h2>
                    <p class="text-xs text-slate-500">Kelompokkan produk toko Anda untuk memudahkan pencarian</p>
                </div>
                <button
                    @click="openAddModal"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-xs transition"
                >
                    <Plus class="w-4 h-4" />
                    <span>Tambah Kategori</span>
                </button>
            </div>

            <!-- Category Grid Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="item in kategori"
                    :key="item.id_kategori"
                    class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-sky-300 transition flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                                <FolderTree class="w-5 h-5" />
                            </div>
                            <Badge :variant="item.status === 'AKTIF' ? 'success' : 'neutral'">
                                {{ item.status }}
                            </Badge>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">{{ item.nama_kategori }}</h3>
                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ item.deskripsi || 'Tidak ada deskripsi.' }}</p>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-600">
                            {{ item.produk_count }} Produk
                        </span>
                        <div class="flex items-center gap-1">
                            <button
                                @click="openEditModal(item)"
                                title="Edit"
                                class="p-1.5 rounded-lg text-slate-600 hover:text-sky-600 hover:bg-sky-50 transition"
                            >
                                <Edit2 class="w-4 h-4" />
                            </button>
                            <button
                                @click="toggleStatus(item.id_kategori)"
                                :title="item.status === 'AKTIF' ? 'Nonaktifkan' : 'Aktifkan'"
                                class="p-1.5 rounded-lg text-slate-600 hover:text-amber-600 hover:bg-amber-50 transition"
                            >
                                <Power class="w-4 h-4" />
                            </button>
                            <button
                                @click="deleteKategori(item)"
                                title="Hapus"
                                class="p-1.5 rounded-lg text-slate-600 hover:text-rose-600 hover:bg-rose-50 transition"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Modal :show="showModal" :title="isEditing ? 'Edit Kategori' : 'Tambah Kategori Baru'" @close="showModal = false">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Nama Kategori *</label>
                    <input
                        v-model="form.nama_kategori"
                        type="text"
                        required
                        placeholder="Contoh: Sembako / Minuman"
                        class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                    />
                    <p v-if="form.errors.nama_kategori" class="text-rose-600 text-[10px] mt-1">{{ form.errors.nama_kategori }}</p>
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

                <div>
                    <label class="block text-xs font-semibold text-slate-700">Deskripsi Kategori</label>
                    <textarea
                        v-model="form.deskripsi"
                        rows="3"
                        placeholder="Keterangan kategori..."
                        class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                    ></textarea>
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
                        {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Kategori') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>

