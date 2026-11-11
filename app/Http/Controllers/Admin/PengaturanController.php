<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\LogAktivitas;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PengaturanController extends Controller
{
    public function index(): Response
    {
        $pengaturan = Pengaturan::all()->pluck('nilai_pengaturan', 'nama_pengaturan')->toArray();
        $cabangList = Cabang::where('status', 'AKTIF')->get();

        return Inertia::render('Admin/Pengaturan/Index', [
            'pengaturan' => [
                'nama_website' => $pengaturan['nama_website'] ?? 'MyShop',
                'nama_toko' => $pengaturan['nama_toko'] ?? 'MyShop',
                'tagline' => $pengaturan['tagline'] ?? 'Belanja Mudah, Cepat, dan Nyaman.',
                'alamat_toko' => $pengaturan['alamat_toko'] ?? 'Jl. Diponegoro No. 10, Ponorogo',
                'telepon_toko' => $pengaturan['telepon_toko'] ?? '0352-123456',
                'email_toko' => $pengaturan['email_toko'] ?? 'support@myshop.test',
                'pajak_simulasi_persen' => $pengaturan['pajak_simulasi_persen'] ?? '11',
                'cabang_online_default' => $pengaturan['cabang_online_default'] ?? '1',
                'informasi_footer' => $pengaturan['informasi_footer'] ?? '© 2026 MyShop. Seluruh hak cipta dilindungi.',
            ],
            'cabangList' => $cabangList,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_website' => ['required', 'string', 'max:80'],
            'nama_toko' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'alamat_toko' => ['nullable', 'string', 'max:255'],
            'telepon_toko' => ['nullable', 'string', 'max:30'],
            'email_toko' => ['nullable', 'email', 'max:100'],
            'pajak_simulasi_persen' => ['required', 'numeric', 'min:0', 'max:100'],
            'cabang_online_default' => ['required', 'exists:tbl_cabang,id_cabang'],
            'informasi_footer' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated as $key => $val) {
            Pengaturan::set($key, (string) $val);
        }

        LogAktivitas::catat(
            Auth::id(),
            'Update pengaturan website',
            'tbl_pengaturan',
            null,
            'Pengaturan toko dan website diperbarui'
        );

        return redirect()->back()->with('success', 'Pengaturan website berhasil disimpan.');
    }
}
