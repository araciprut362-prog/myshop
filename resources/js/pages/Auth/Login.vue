<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import { Store, Lock, Mail, Eye, EyeOff, ShieldCheck, UserCheck, ShoppingBag, ArrowRight } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const fillDemo = (email: string) => {
    form.email = email;
    form.password = 'password';
};

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk Akun" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-sky-950 to-slate-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link href="/" class="inline-flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-sky-500/30">
                    <Store class="w-7 h-7" />
                </div>
                <div class="text-left">
                    <span class="text-2xl font-black tracking-tight text-white">MyShop</span>
                    <span class="block text-[11px] font-semibold text-sky-400 uppercase tracking-widest">Sistem Penjualan Toko</span>
                </div>
            </Link>
            <h2 class="mt-6 text-xl font-bold tracking-tight text-white">
                Masuk ke Akun Anda
            </h2>
            <p class="mt-2 text-xs text-slate-400">
                Pilih role atau masukkan kredensial akun Anda di bawah ini
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
            <!-- Quick Demo Login Buttons -->
            <div class="mb-6 p-4 rounded-2xl bg-slate-800/80 border border-slate-700/80 backdrop-blur-sm">
                <p class="text-xs font-semibold text-sky-400 mb-2 flex items-center gap-1.5">
                    <span>⚡ Quick Fill Demo Accounts (Password: password)</span>
                </p>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        type="button"
                        @click="fillDemo('admin@myshop.test')"
                        class="p-2 rounded-xl text-left bg-slate-900/80 hover:bg-sky-900/40 border border-slate-700 hover:border-sky-500 transition group"
                    >
                        <ShieldCheck class="w-4 h-4 text-sky-400 mb-1" />
                        <p class="text-xs font-bold text-white group-hover:text-sky-300">Admin</p>
                        <p class="text-[10px] text-slate-400 truncate">Semua Cabang</p>
                    </button>

                    <button
                        type="button"
                        @click="fillDemo('kasir.utama@myshop.test')"
                        class="p-2 rounded-xl text-left bg-slate-900/80 hover:bg-emerald-900/40 border border-slate-700 hover:border-emerald-500 transition group"
                    >
                        <UserCheck class="w-4 h-4 text-emerald-400 mb-1" />
                        <p class="text-xs font-bold text-white group-hover:text-emerald-300">Kasir</p>
                        <p class="text-[10px] text-slate-400 truncate">Cabang Utama</p>
                    </button>

                    <button
                        type="button"
                        @click="fillDemo('andi@myshop.test')"
                        class="p-2 rounded-xl text-left bg-slate-900/80 hover:bg-purple-900/40 border border-slate-700 hover:border-purple-500 transition group"
                    >
                        <ShoppingBag class="w-4 h-4 text-purple-400 mb-1" />
                        <p class="text-xs font-bold text-white group-hover:text-purple-300">Pelanggan</p>
                        <p class="text-[10px] text-slate-400 truncate">Toko Online</p>
                    </button>
                </div>
            </div>

            <!-- Login Card -->
            <div class="bg-white py-8 px-6 shadow-2xl rounded-2xl sm:px-10 border border-slate-100">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700">Email</label>
                        <div class="mt-1 relative rounded-xl shadow-xs">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <Mail class="h-4 w-4" />
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@myshop.test"
                                class="block w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-hidden focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                                :class="{ 'border-rose-500': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700">Password</label>
                        <div class="mt-1 relative rounded-xl shadow-xs">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <Lock class="h-4 w-4" />
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="block w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-hidden focus:border-sky-500 focus:ring-2 focus:ring-sky-100 transition"
                                :class="{ 'border-rose-500': form.errors.password }"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center text-xs text-slate-600 cursor-pointer">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded border-slate-300 text-sky-600 shadow-xs focus:ring-sky-500 h-4 w-4"
                            />
                            <span class="ml-2">Ingat saya</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-sky-600 hover:bg-sky-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 shadow-md shadow-sky-600/30 transition disabled:opacity-50"
                    >
                        <span>{{ form.processing ? 'Memproses...' : 'Masuk Sekarang' }}</span>
                        <ArrowRight class="w-4 h-4" />
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-600">
                        Belum memiliki akun pelanggan?
                        <Link href="/register" class="font-bold text-sky-600 hover:text-sky-700 ml-1">
                            Daftar di sini
                        </Link>
                    </p>
                </div>
            </div>

            <div class="mt-6 text-center">
                <Link href="/" class="text-xs text-slate-400 hover:text-white transition">
                    &larr; Kembali ke Beranda Toko
                </Link>
            </div>
        </div>
    </div>
</template>

