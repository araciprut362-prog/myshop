<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class KasirController extends Controller
{
    public function index(): Response
    {
        $kasir = User::where('role', 'kasir')
            ->with('cabang')
            ->withCount(['transaksiKasir as total_transaksi'])
            ->withSum(['transaksiKasir as total_omzet' => function ($query) {
                $query->where('status_pembayaran', 'BERHASIL');
            }], 'total_belanja')
            ->get();

        $cabang = Cabang::where('status', 'AKTIF')->get();

        return Inertia::render('Admin/Kasir/Index', [
            'kasir' => $kasir,
            'cabang' => $cabang,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:tbl_user,email'],
            'password' => ['required', Rules\Password::defaults()],
            'id_cabang' => ['required', 'exists:tbl_cabang,id_cabang'],
            'no_hp' => ['nullable', 'string', 'max:25'],
            'status' => ['required', 'in:AKTIF,NONAKTIF'],
        ]);

        $user = User::create([
            'id_pelanggan' => null,
            'id_cabang' => $validated['id_cabang'],
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'role' => 'kasir',
            'no_hp' => $validated['no_hp'] ?? null,
            'status' => $validated['status'],
        ]);

        LogAktivitas::catat(
            Auth::id(),
            'Tambah akun kasir baru',
            'tbl_user',
            $user->id_user,
            "Kasir: {$user->nama_lengkap}, Cabang: {$user->cabang?->nama_cabang}"
        );

        return redirect()->route('admin.kasir.index')->with('success', 'Akun kasir berhasil dibuat.');
    }

    public function update(Request $request, User $kasir): RedirectResponse
    {
        if ($kasir->role !== 'kasir') {
            abort(403);
        }

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:tbl_user,email,'.$kasir->id_user.',id_user'],
            'password' => ['nullable', Rules\Password::defaults()],
            'id_cabang' => ['required', 'exists:tbl_cabang,id_cabang'],
            'no_hp' => ['nullable', 'string', 'max:25'],
            'status' => ['required', 'in:AKTIF,NONAKTIF'],
        ]);

        $kasir->nama_lengkap = $validated['nama_lengkap'];
        $kasir->email = $validated['email'];
        $kasir->id_cabang = $validated['id_cabang'];
        $kasir->no_hp = $validated['no_hp'] ?? null;
        $kasir->status = $validated['status'];

        if (! empty($validated['password'])) {
            $kasir->password_hash = Hash::make($validated['password']);
        }

        $kasir->save();

        LogAktivitas::catat(
            Auth::id(),
            'Update akun kasir',
            'tbl_user',
            $kasir->id_user,
            "Kasir: {$kasir->nama_lengkap}"
        );

        return redirect()->route('admin.kasir.index')->with('success', 'Akun kasir berhasil diperbarui.');
    }

    public function toggleStatus(User $kasir): RedirectResponse
    {
        if ($kasir->role !== 'kasir') {
            abort(403);
        }

        $newStatus = $kasir->status === 'AKTIF' ? 'NONAKTIF' : 'AKTIF';
        $kasir->status = $newStatus;
        $kasir->save();

        LogAktivitas::catat(
            Auth::id(),
            "Ubah status kasir menjadi {$newStatus}",
            'tbl_user',
            $kasir->id_user,
            "Kasir: {$kasir->nama_lengkap}"
        );

        return redirect()->back()->with('success', "Status akun kasir berhasil diubah menjadi {$newStatus}.");
    }
}
