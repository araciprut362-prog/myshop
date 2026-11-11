<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import Badge from '@/components/Badge.vue';
import { formatDate } from '@/lib/utils';
import { History, ArrowLeft, Search, Filter } from 'lucide-vue-next';
import type { Cabang, Paginated } from '@/types';

interface MutasiItem {
    id_mutasi: number;
    id_cabang: number;
    id_produk: number;
    jenis_mutasi: 'MASUK' | 'KELUAR' | 'RESERVASI' | 'BATAL_RESERVASI' | 'PENYESUAIAN';
    jumlah: number;
    keterangan: string | null;
    created_at: string;
    cabang?: Cabang;
    produk?: { nama_produk: string; sku: string };
    user?: { nama_lengkap: string };
    transaksi?: { kode_transaksi: string };
}

const props = defineProps<{
    mutasi: Paginated<MutasiItem>;
    cabangList: Cabang[];
    filters: {
        cabang?: string;
        jenis?: string;
        search?: string;
    };
}>();

const selectedCabang = ref(props.filters.cabang || '');
const selectedJenis = ref(props.filters.jenis || '');
const search = ref(props.filters.search || '');

const filterData = () => {
    router.get('/admin/stok/mutasi', {
        cabang: selectedCabang.value || undefined,
        jenis: selectedJenis.value || undefined,
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Riwayat Mutasi Stok" />

    <AdminLayout title="Riwayat Mutasi Stok">
        <div class="space-y-6">
            <Link href="/admin/stok" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
                <ArrowLeft class="w-4 h-4" />
                <span>Kembali ke Stok Cabang</span>
            </Link>

            <!-- Filters Bar -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap items-center gap-3">
                <div class="relative w-64">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                    <input
                        v-model="search"
                        @keyup.enter="filterData"
                        type="text"
                        placeholder="Cari produk / SKU..."
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:border-sky-500"
                    />
                </div>

                <select
                    v-model="selectedCabang"
                    @change="filterData"
                    class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                >
                    <option value="">Semua Cabang</option>
                    <option v-for="c in cabangList" :key="c.id_cabang" :value="c.id_cabang">
                        {{ c.nama_cabang }}
                    </option>
                </select>

                <select
                    v-model="selectedJenis"
                    @change="filterData"
                    class="text-xs py-2 px-3 rounded-xl border border-slate-200 bg-white"
                >
                    <option value="">Semua Jenis Mutasi</option>
                    <option value="MASUK">MASUK</option>
                    <option value="KELUAR">KELUAR</option>
                    <option value="RESERVASI">RESERVASI</option>
                    <option value="BATAL_RESERVASI">BATAL_RESERVASI</option>
                    <option value="PENYESUAIAN">PENYESUAIAN</option>
                </select>

                <button
                    @click="filterData"
                    class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                >
                    Filter
                </button>
            </div>

            <!-- Mutasi Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">Waktu</th>
                                <th class="py-3 px-4">Cabang</th>
                                <th class="py-3 px-4">Produk</th>
                                <th class="py-3 px-4 text-center">Jenis Mutasi</th>
                                <th class="py-3 px-4 text-center">Jumlah</th>
                                <th class="py-3 px-4">Operator / Referensi</th>
                                <th class="py-3 px-4">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-if="mutasi.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    Tidak ada data mutasi stok.
                                </td>
                            </tr>
                            <tr v-for="m in mutasi.data" :key="m.id_mutasi" class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ formatDate(m.created_at) }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-800">{{ m.cabang?.nama_cabang }}</td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-900 block">{{ m.produk?.nama_produk }}</span>
                                    <span class="text-[10px] font-mono text-slate-400">{{ m.produk?.sku }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Badge
                                        :variant="m.jenis_mutasi === 'MASUK' ? 'success' : m.jenis_mutasi === 'KELUAR' ? 'danger' : m.jenis_mutasi === 'RESERVASI' ? 'warning' : 'info'"
                                    >
                                        {{ m.jenis_mutasi }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4 text-center font-extrabold text-sm" :class="m.jenis_mutasi === 'MASUK' || m.jenis_mutasi === 'BATAL_RESERVASI' ? 'text-emerald-600' : 'text-slate-900'">
                                    {{ m.jenis_mutasi === 'MASUK' || m.jenis_mutasi === 'BATAL_RESERVASI' ? '+' : '-' }}{{ m.jumlah }}
                                </td>
                                <td class="py-3 px-4">
                                    <span v-if="m.user" class="font-medium text-slate-800 block">{{ m.user.nama_lengkap }}</span>
                                    <span v-if="m.transaksi" class="text-[10px] text-sky-600 font-mono block">{{ m.transaksi.kode_transaksi }}</span>
                                    <span v-if="!m.user && !m.transaksi" class="text-slate-400 text-xs">-</span>
                                </td>
                                <td class="py-3 px-4 text-slate-600">{{ m.keterangan || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Menampilkan {{ mutasi.from || 0 }} - {{ mutasi.to || 0 }} dari {{ mutasi.total }} catatan mutasi</span>
                    <Pagination :links="mutasi.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

