<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import Modal from '@/components/Modal.vue';
import Badge from '@/components/Badge.vue';
import {
    Boxes,
    Search,
    History,
    Edit3,
    AlertTriangle,
    CheckCircle2,
    Store
} from 'lucide-vue-next';
import type { StokCabang, Cabang, Kategori, Paginated } from '@/types';

const props = defineProps<{
    stokList: Paginated<StokCabang>;
    cabangList: Cabang[];
    kategoriList: Kategori[];
    selectedCabang: number;
    filters: {
        search?: string;
        cabang?: string;
        kategori?: string;
        menipis?: boolean;
    };
}>();

const search = ref(props.filters.search || '');
const currentCabang = ref(props.selectedCabang);
const selectedKategori = ref(props.filters.kategori || '');
const onlyMenipis = ref(Boolean(props.filters.menipis));

const filterData = () => {
    router.get('/admin/stok', {
        search: search.value || undefined,
        cabang: currentCabang.value,
        kategori: selectedKategori.value || undefined,
        menipis: onlyMenipis.value ? '1' : undefined,
    }, { preserveState: true, replace: true });
};

// Adjust Modal
const showModal = ref(false);
const selectedProduct = ref<{ id_produk: number; nama_produk: string; sku: string; stok_lama: number } | null>(null);

const form = useForm({
    id_cabang: props.selectedCabang,
    id_produk: 0,
    stok_baru: 0,
    keterangan: '',
});

const openAdjustModal = (item: StokCabang) => {
    selectedProduct.value = {
        id_produk: item.id_produk,
        nama_produk: item.produk?.nama_produk || '',
        sku: item.produk?.sku || '',
        stok_lama: item.stok,
    };
    form.clearErrors();
    form.id_cabang = item.id_cabang;
    form.id_produk = item.id_produk;
    form.stok_baru = item.stok;
    form.keterangan = 'Penyesuaian stok opname';
    showModal.value = true;
};

const submitAdjust = () => {
    form.post('/admin/stok/sesuaikan', {
        onSuccess: () => {
            showModal.value = false;
        },
    });
};
</script>

<template>
    <Head title="Manajemen Stok Cabang" />

    <AdminLayout title="Manajemen Stok Produk Per Cabang">
        <div class="space-y-6">
            <!-- Branch Selector Tabs -->
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 max-w-full">
                    <button
                        v-for="cbg in cabangList"
                        :key="cbg.id_cabang"
                        @click="currentCabang = cbg.id_cabang; filterData()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold shrink-0 transition flex items-center gap-2"
                        :class="[
                            currentCabang === cbg.id_cabang
                                ? 'bg-sky-600 text-white shadow-xs'
                                : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'
                        ]"
                    >
                        <Store class="w-3.5 h-3.5" />
                        <span>{{ cbg.nama_cabang }} ({{ cbg.kota }})</span>
                    </button>
                </div>

                <Link
                    href="/admin/stok/mutasi"
                    class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800 transition shadow-xs"
                >
                    <History class="w-4 h-4 text-sky-400" />
                    <span>Riwayat Mutasi Stok</span>
                </Link>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative w-64">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                        <input
                            v-model="search"
                            @keyup.enter="filterData"
                            type="text"
                            placeholder="Cari SKU atau produk..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-sky-500"
                        />
                    </div>

                    <select
                        v-model="selectedKategori"
                        @change="filterData"
                        class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                    >
                        <option value="">Semua Kategori</option>
                        <option v-for="kat in kategoriList" :key="kat.id_kategori" :value="kat.id_kategori">
                            {{ kat.nama_kategori }}
                        </option>
                    </select>

                    <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none">
                        <input
                            v-model="onlyMenipis"
                            @change="filterData"
                            type="checkbox"
                            class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 h-4 w-4"
                        />
                        <span>Hanya Stok Menipis (&le; Min)</span>
                    </label>

                    <button
                        @click="filterData"
                        class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Stock Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">Produk</th>
                                <th class="py-3 px-4">SKU</th>
                                <th class="py-3 px-4">Kategori</th>
                                <th class="py-3 px-4 text-center">Stok Saat Ini</th>
                                <th class="py-3 px-4 text-center">Stok Minimum</th>
                                <th class="py-3 px-4 text-center">Kondisi</th>
                                <th class="py-3 px-4 text-center">Aksi Penyesuaian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-if="stokList.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    Tidak ada data stok di cabang ini yang sesuai filter.
                                </td>
                            </tr>
                            <tr v-for="item in stokList.data" :key="item.id_stok" class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ item.produk?.nama_produk }}
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-600">{{ item.produk?.sku }}</td>
                                <td class="py-3 px-4">{{ item.produk?.kategori?.nama_kategori }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="text-sm font-extrabold" :class="item.stok <= item.stok_minimum ? 'text-rose-600' : 'text-slate-900'">
                                        {{ item.stok }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center text-slate-500">{{ item.stok_minimum }}</td>
                                <td class="py-3 px-4 text-center">
                                    <Badge :variant="item.stok <= item.stok_minimum ? 'danger' : 'success'">
                                        {{ item.stok <= item.stok_minimum ? 'Stok Menipis' : 'Stok Aman' }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button
                                        @click="openAdjustModal(item)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 transition"
                                    >
                                        <Edit3 class="w-3.5 h-3.5" />
                                        <span>Sesuaikan Stok</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Menampilkan {{ stokList.from || 0 }} - {{ stokList.to || 0 }} dari {{ stokList.total }} produk</span>
                    <Pagination :links="stokList.links" />
                </div>
            </div>
        </div>

        <!-- Adjust Stock Modal -->
        <Modal :show="showModal" title="Penyesuaian Stok Produk" @close="showModal = false">
            <form @submit.prevent="submitAdjust" class="space-y-4">
                <div v-if="selectedProduct" class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <p class="font-bold text-slate-900">{{ selectedProduct.nama_produk }}</p>
                    <p class="text-slate-500">SKU: {{ selectedProduct.sku }} &bull; Stok Saat Ini: <span class="font-bold text-slate-800">{{ selectedProduct.stok_lama }}</span></p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700">Stok Baru yang Tersedia *</label>
                    <input
                        v-model="form.stok_baru"
                        type="number"
                        min="0"
                        required
                        class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500 text-base font-bold"
                    />
                    <p class="text-[10px] text-slate-400 mt-1">
                        Selisih perubahan: {{ form.stok_baru - (selectedProduct?.stok_lama || 0) }} unit.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700">Keterangan / Alasan Mutasi *</label>
                    <input
                        v-model="form.keterangan"
                        type="text"
                        required
                        placeholder="Contoh: Stok opname bulanan, barang rusak, penerimaan stok baru..."
                        class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                    />
                    <p class="text-[10px] text-slate-400 mt-1">Perubahan akan langsung dicatat ke tabel tbl_mutasi_stok.</p>
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
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Mutasi Stok' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>

