<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Modal from '@/components/Modal.vue';
import Badge from '@/components/Badge.vue';
import { formatRupiah } from '@/lib/utils';
import { Plus, Edit2, Power, UserCheck, Store, Mail, Phone } from 'lucide-vue-next';
import type { User, Cabang } from '@/types';

interface KasirUser extends User {
    total_transaksi?: number;
    total_omzet?: number;
}

const props = defineProps<{
    kasir: KasirUser[];
    cabang: Cabang[];
}>();

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    nama_lengkap: '',
    email: '',
    password: '',
    id_cabang: '',
    no_hp: '',
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

const openEditModal = (u: KasirUser) => {
    isEditing.value = true;
    editingId.value = u.id_user;
    form.clearErrors();
    form.nama_lengkap = u.nama_lengkap;
    form.email = u.email;
    form.password = '';
    form.id_cabang = String(u.id_cabang || '');
    form.no_hp = u.no_hp || '';
    form.status = u.status;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value && editingId.value) {
        form.put(`/admin/kasir/${editingId.value}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/admin/kasir', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const toggleStatus = (id: number) => {
    router.patch(`/admin/kasir/${id}/toggle-status`, {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Manajemen Akun Kasir" />

    <AdminLayout title="Manajemen Kasir & Penempatan Cabang">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Akun Kasir Toko</h2>
                    <p class="text-xs text-slate-500">Kelola akun kasir dan penugasan cabang operasional</p>
                </div>
                <button
                    @click="openAddModal"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-xs transition"
                >
                    <Plus class="w-4 h-4" />
                    <span>Tambah Kasir</span>
                </button>
            </div>

            <!-- Cashiers Table -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">Nama Kasir</th>
                                <th class="py-3 px-4">Penempatan Cabang</th>
                                <th class="py-3 px-4">Kontak</th>
                                <th class="py-3 px-4 text-center">Performa Penjualan</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-for="k in kasir" :key="k.id_user" class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-xs shrink-0">
                                            {{ k.nama_lengkap.charAt(0) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block">{{ k.nama_lengkap }}</span>
                                            <span class="text-[11px] text-slate-400">{{ k.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span v-if="k.cabang" class="font-semibold text-slate-800 flex items-center gap-1.5">
                                        <Store class="w-3.5 h-3.5 text-sky-500" />
                                        {{ k.cabang.nama_cabang }} ({{ k.cabang.kota }})
                                    </span>
                                    <span v-else class="text-rose-500 text-xs italic">Belum ditempatkan</span>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ k.no_hp || '-' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="font-bold text-slate-900 block">{{ k.total_transaksi || 0 }} Transaksi</span>
                                    <span class="text-[11px] text-emerald-600 font-semibold">{{ formatRupiah(k.total_omzet || 0) }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Badge :variant="k.status === 'AKTIF' ? 'success' : 'neutral'">
                                        {{ k.status }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            @click="openEditModal(k)"
                                            title="Edit Kasir"
                                            class="p-1.5 rounded-lg text-slate-600 hover:text-sky-600 hover:bg-sky-50 transition"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="toggleStatus(k.id_user)"
                                            :title="k.status === 'AKTIF' ? 'Nonaktifkan' : 'Aktifkan'"
                                            class="p-1.5 rounded-lg text-slate-600 hover:text-amber-600 hover:bg-amber-50 transition"
                                        >
                                            <Power class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <Modal :show="showModal" :title="isEditing ? 'Edit Akun Kasir' : 'Tambah Akun Kasir Baru'" @close="showModal = false">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Nama Lengkap *</label>
                    <input
                        v-model="form.nama_lengkap"
                        type="text"
                        required
                        class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                    />
                    <p v-if="form.errors.nama_lengkap" class="text-rose-600 text-[10px] mt-1">{{ form.errors.nama_lengkap }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Email Login *</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        />
                        <p v-if="form.errors.email" class="text-rose-600 text-[10px] mt-1">{{ form.errors.email }}</p>
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Penempatan Cabang *</label>
                        <select
                            v-model="form.id_cabang"
                            required
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                        >
                            <option value="" disabled>Pilih Cabang</option>
                            <option v-for="c in cabang" :key="c.id_cabang" :value="c.id_cabang">
                                {{ c.nama_cabang }} ({{ c.kota }})
                            </option>
                        </select>
                        <p v-if="form.errors.id_cabang" class="text-rose-600 text-[10px] mt-1">{{ form.errors.id_cabang }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Status Akun *</label>
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
                    <label class="block text-xs font-semibold text-slate-700">
                        Password {{ isEditing ? '(Biarkan kosong jika tidak ingin mengubah)' : '*' }}
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        :required="!isEditing"
                        placeholder="••••••••"
                        class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:border-sky-500"
                    />
                    <p v-if="form.errors.password" class="text-rose-600 text-[10px] mt-1">{{ form.errors.password }}</p>
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
                        {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Akun Kasir') }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>

