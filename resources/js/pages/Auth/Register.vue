<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3';
import { Store, User, Mail, Phone, MapPin, Lock, ArrowRight } from 'lucide-vue-next';

const form = useForm({
    nama_lengkap: '',
    email: '',
    no_hp: '',
    alamat: '',
    kota: '',
    kode_pos: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Daftar Akun Pelanggan" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-sky-950 to-slate-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link href="/" class="inline-flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-sky-500/30">
                    <Store class="w-7 h-7" />
                </div>
                <div class="text-left">
                    <span class="text-2xl font-black tracking-tight text-white">MyShop</span>
                    <span class="block text-[11px] font-semibold text-sky-400 uppercase tracking-widest">Registrasi Pelanggan</span>
                </div>
            </Link>
            <h2 class="mt-4 text-xl font-bold tracking-tight text-white">
                Buat Akun Pelanggan Baru
            </h2>
            <p class="mt-1 text-xs text-slate-400">
                Belanja kebutuhan harian lebih cepat dengan akun MyShop
            </p>
        </div>

        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-lg px-4 sm:px-0">
            <div class="bg-white py-8 px-6 shadow-2xl rounded-2xl sm:px-10 border border-slate-100">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Nama Lengkap</label>
                        <div class="mt-1 relative rounded-xl shadow-xs">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <User class="h-4 w-4" />
                            </div>
                            <input
                                v-model="form.nama_lengkap"
                                type="text"
                                required
                                autofocus
                                placeholder="Contoh: Rian Pratama"
                                class="block w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                                :class="{ 'border-rose-500': form.errors.nama_lengkap }"
                            />
                        </div>
                        <p v-if="form.errors.nama_lengkap" class="mt-1 text-xs text-rose-600">{{ form.errors.nama_lengkap }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Email</label>
                            <div class="mt-1 relative rounded-xl shadow-xs">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <Mail class="h-4 w-4" />
                                </div>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="rian@example.com"
                                    class="block w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                                    :class="{ 'border-rose-500': form.errors.email }"
                                />
                            </div>
                            <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Nomor WhatsApp / HP</label>
                            <div class="mt-1 relative rounded-xl shadow-xs">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <Phone class="h-4 w-4" />
                                </div>
                                <input
                                    v-model="form.no_hp"
                                    type="tel"
                                    required
                                    placeholder="08123456789"
                                    class="block w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                                    :class="{ 'border-rose-500': form.errors.no_hp }"
                                />
                            </div>
                            <p v-if="form.errors.no_hp" class="mt-1 text-xs text-rose-600">{{ form.errors.no_hp }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Kota Domisili</label>
                            <div class="mt-1 relative rounded-xl shadow-xs">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <MapPin class="h-4 w-4" />
                                </div>
                                <input
                                    v-model="form.kota"
                                    type="text"
                                    required
                                    placeholder="Contoh: Ponorogo / Madiun"
                                    class="block w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                                    :class="{ 'border-rose-500': form.errors.kota }"
                                />
                            </div>
                            <p v-if="form.errors.kota" class="mt-1 text-xs text-rose-600">{{ form.errors.kota }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Kode Pos</label>
                            <input
                                v-model="form.kode_pos"
                                type="text"
                                placeholder="63411"
                                class="mt-1 block w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Alamat Lengkap Pengiriman</label>
                        <textarea
                            v-model="form.alamat"
                            required
                            rows="2"
                            placeholder="Jl. Nama Jalan No. Rumah, RT/RW, Kelurahan, Kecamatan"
                            class="mt-1 block w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                            :class="{ 'border-rose-500': form.errors.alamat }"
                        ></textarea>
                        <p v-if="form.errors.alamat" class="mt-1 text-xs text-rose-600">{{ form.errors.alamat }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Password</label>
                            <div class="mt-1 relative rounded-xl shadow-xs">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <Lock class="h-4 w-4" />
                                </div>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    required
                                    placeholder="Minimal 8 karakter"
                                    class="block w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                                    :class="{ 'border-rose-500': form.errors.password }"
                                />
                            </div>
                            <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Konfirmasi Password</label>
                            <div class="mt-1 relative rounded-xl shadow-xs">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <Lock class="h-4 w-4" />
                                </div>
                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    required
                                    placeholder="Ulangi password"
                                    class="block w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                                />
                            </div>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full mt-4 flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-sky-600 hover:bg-sky-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 shadow-md shadow-sky-600/30 transition disabled:opacity-50"
                    >
                        <span>{{ form.processing ? 'Mendaftarkan Akun...' : 'Daftar Sebagai Pelanggan' }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-600">
                        Sudah memiliki akun?
                        <Link href="/login" class="font-bold text-sky-600 hover:text-sky-700 ml-1">
                            Masuk di sini
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

