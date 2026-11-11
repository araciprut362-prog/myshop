<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import Modal from '@/components/Modal.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah } from '@/lib/utils';
import {
    Plus,
    Search,
    Edit2,
    Trash2,
    Power,
    Package,
    Image as ImageIcon,
    Filter
} from 'lucide-vue-next';
import type { Produk, Kategori, Cabang, Paginated } from '@/types';

const props = defineProps<{
    produk: Paginated<Produk>;
    kategori: Kategori[];
    cabang: Cabang[];
    filters: {
        search?: string;
        kategori?: string;
        status?: string;
    };
}>();

const search = ref(props.filters.search || '');
const selectedKategori = ref(props.filters.kategori || '');
const selectedStatus = ref(props.filters.status || '');

const filterData = () => {
    router.get('/admin/produk', {
        search: search.value || undefined,
        kategori: selectedKategori.value || undefined,
        status: selectedStatus.value || undefined,
    }, { preserveState: true, replace: true });
};

// Modal State
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    sku: '',
    nama_produk: '',
    id_kategori: '',
    harga: 0,
    deskripsi: '',
    status: 'AKTIF',
    gambar: null as File | null,
});

const openAddModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.status = 'AKTIF';
    showModal.value = true;
};

const openEditModal = (p: Produk) => {
    isEditing.value = true;
    editingId.value = p.id_produk;
    form.clearErrors();
    form.sku = p.sku;
    form.nama_produk = p.nama_produk;
    form.id_kategori = String(p.id_kategori);
    form.harga = Number(p.harga);
    form.deskripsi = p.deskripsi || '';
    form.status = p.status;
    form.gambar = null;
    showModal.value = true;
};

const handleImageChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.gambar = target.files[0];
    }
};

const submitForm = () => {
    if (isEditing.value && editingId.value) {
        form.post(`/admin/produk/${editingId.value}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/admin/produk', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const toggleStatus = (id: number) => {
    router.patch(`/admin/produk/${id}/toggle-status`, {}, { preserveScroll: true });
};

const deleteProduct = (p: Produk) => {
    if (confirm(`Yakin ingin menghapus atau menonaktifkan produk "${p.nama_produk}"?`)) {
        router.delete(`/admin/produk/${p.id_produk}`, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Manajemen Produk" />

    <AdminLayout title="Manajemen Produk">
        <div class="space-y-6">
            <!-- Header Controls & Filters -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                            v-model="search"
                            @keyup.enter="filterData"
                            type="text"
                            placeholder="Cari SKU atau nama..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-sky-500"
                        />
                    </div>

                    <!-- Category Filter -->
                    <select
                        v-model="selectedKategori"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Kategori</option>
                        <option v-for="kat in kategori" :key="kat.id_kategori" :value="kat.id_kategori">
                            {{ kat.nama_kategori }}
                        </option>
                    </select>

                    <!-- Status Filter -->
                    <select
                        v-model="selectedStatus"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Status</option>
                        <option value="AKTIF">AKTIF</option>
                        <option value="NONAKTIF">NONAKTIF</option>
                    </select>

                    <button
                        @click="filterData"
                        class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                    >
                        Filter
                    </button>
                </div>

                <!-- Add Button -->
                <button
                    @click="openAddModal"
                    class="w-full md:w-auto flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-sm shadow-sky-600/30 transition"
                >
                    <Plus class="w-4 h-4" />
                    <span>Tambah Produk</span>
                </button>
            </div>

            <!-- Products Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">Produk</th>
                                <th class="py-3 px-4">SKU</th>
                                <th class="py-3 px-4">Kategori</th>
                                <th class="py-3 px-4 text-right">Harga</th>
                                <th class="py-3 px-4 text-center">Stok Total</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-if="produk.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    Tidak ada produk yang sesuai filter.
                                </td>
                            </tr>
                            <tr
                                v-for="item in produk.data"
                                :key="item.id_produk"
                                class="hover:bg-slate-50/60 transition"
                            >
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0 overflow-hidden">
                                            <img
                                                v-if="item.gambar"
                                                :src="item.gambar.startsWith('http') ? item.gambar : `/${item.gambar}`"
                                                :alt="item.nama_produk"
                                                class="w-full h-full object-cover"
                                            />
                                            <ImageIcon v-else class="w-5 h-5 text-slate-400" />
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block">{{ item.nama_produk }}</span>
                                            <span class="text-[11px] text-slate-400 line-clamp-1 max-w-xs">{{ item.deskripsi || '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-700">{{ item.sku }}</td>
                                <td class="py-3 px-4">{{ item.kategori?.nama_kategori }}</td>
                                <td class="py-3 px-4 text-right font-extrabold text-slate-900">{{ formatRupiah(item.harga) }}</td>
                                <td class="py-3 px-4 text-center font-bold">
                                    {{ item.stok_cabang?.reduce((acc, curr) => acc + curr.stok, 0) ?? 0 }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Badge :variant="item.status === 'AKTIF' ? 'success' : 'neutral'">
                                        {{ item.status }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            @click="openEditModal(item)"
                                            title="Edit Produk"
                                            class="p-1.5 rounded-lg text-slate-600 hover:text-sky-600 hover:bg-sky-50 transition"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="toggleStatus(item.id_produk)"
                                            :title="item.status === 'AKTIF' ? 'Nonaktifkan' : 'Aktifkan'"
                                            class="p-1.5 rounded-lg text-slate-600 hover:text-amber-600 hover:bg-amber-50 transition"
                                        >
                                            <Power class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteProduct(item)"
                                            title="Hapus / Nonaktifkan"
                                            class="p-1.5 rounded-lg text-slate-600 hover:text-rose-600 hover:bg-rose-50 transition"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Menampilkan {{ produk.from || 0 }} - {{ produk.to || 0 }} dari {{ produk.total }} produk</span>
                    <Pagination :links="produk.links" />
                </div>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Modal :show="showModal" :title="isEditing ? 'Edit Data Produk' : 'Tambah Produk Baru'" @close="showModal = false">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">SKU Produk *</label>
                        <input
                            v-model="form.sku"
                            type="text"
                            required
                            placeholder="MKS001"
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono uppercase focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                        />
                        <p v-if="form.errors.sku" class="text-rose-600 text-[10px] mt-1">{{ form.errors.sku }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Kategori *</label>
                        <select
                            v-model="form.id_kategori"
                            required
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        >
                            <option value="" disabled>Pilih Kategori</option>
                            <option v-for="k in kategori" :key="k.id_kategori" :value="k.id_kategori">
                                {{ k.nama_kategori }}
                            </option>
                        </select>
                        <p v-if="form.errors.id_kategori" class="text-rose-600 text-[10px] mt-1">{{ form.errors.id_kategori }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700">Nama Produk *</label>
                    <input
                        v-model="form.nama_produk"
                        type="text"
                        required
                        placeholder="Contoh: Kopi Arabika 200g"
                        class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                    />
                    <p v-if="form.errors.nama_produk" class="text-rose-600 text-[10px] mt-1">{{ form.errors.nama_produk }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Harga Satuan (Rp) *</label>
                        <input
                            v-model="form.harga"
                            type="number"
                            step="500"
                            min="0"
                            required
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        />
                        <p v-if="form.errors.harga" class="text-rose-600 text-[10px] mt-1">{{ form.errors.harga }}</p>
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
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700">Foto / Gambar Produk</label>
                    <input
                        type="file"
                        accept="image/*"
                        @change="handleImageChange"
                        class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100"
                    />
                    <p v-if="form.errors.gambar" class="text-rose-600 text-[10px] mt-1">{{ form.errors.gambar }}</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700">Deskripsi Produk</label>
                    <textarea
                        v-model="form.deskripsi"
                        rows="3"
                        placeholder="Deskripsi keunggulan, isi kemasan, dll."
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
                        {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Produk') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>

