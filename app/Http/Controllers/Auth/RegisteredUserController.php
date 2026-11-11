<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Keranjang;
use App\Models\LogAktivitas;
use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:tbl_user,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'no_hp' => ['required', 'string', 'max:25'],
            'alamat' => ['required', 'string', 'max:255'],
            'kota' => ['required', 'string', 'max:50'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
        ]);

        $user = DB::transaction(function () use ($request) {
            // 1. Buat profil pelanggan
            // Trigger after_insert_pelanggan otomatis mencatat ke log_pelanggan
            $pelanggan = Pelanggan::create([
                'nama_pelanggan' => $request->nama_lengkap,
                'kota' => $request->kota,
                'no_hp' => $request->no_hp,
                'alamat' => $request->alamat,
                'kode_pos' => $request->kode_pos,
            ]);

            // 2. Buat akun user dengan role pelanggan SAJA
            $user = User::create([
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'id_cabang' => null,
                'nama_lengkap' => $request->nama_lengkap,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'role' => 'pelanggan',
                'no_hp' => $request->no_hp,
                'status' => 'AKTIF',
                'terakhir_login' => now(),
            ]);

            // 3. Buat keranjang belanja aktif
            Keranjang::create([
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'id_cabang' => 1,
                'status' => 'AKTIF',
            ]);

            LogAktivitas::catat(
                $user->id_user,
                'Registrasi pelanggan baru',
                'tbl_user',
                $user->id_user,
                "Nama: {$user->nama_lengkap}, Email: {$user->email}"
            );

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Selamat datang di MyShop! Akun Anda berhasil didaftarkan.');
    }
}
