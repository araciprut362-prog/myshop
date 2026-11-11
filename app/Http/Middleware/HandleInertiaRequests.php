<?php

namespace App\Http\Middleware;

use App\Models\Keranjang;
use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $cartCount = 0;

        if ($user && $user->role === 'pelanggan' && $user->id_pelanggan) {
            $keranjang = Keranjang::where('id_pelanggan', $user->id_pelanggan)
                ->where('status', 'AKTIF')
                ->with('details')
                ->first();

            if ($keranjang) {
                $cartCount = (int) $keranjang->details->sum('jumlah');
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id_user' => $user->id_user,
                    'nama_lengkap' => $user->nama_lengkap,
                    'email' => $user->email,
                    'role' => $user->role,
                    'no_hp' => $user->no_hp,
                    'status' => $user->status,
                    'id_cabang' => $user->id_cabang,
                    'id_pelanggan' => $user->id_pelanggan,
                    'cabang' => $user->cabang ? [
                        'id_cabang' => $user->cabang->id_cabang,
                        'nama_cabang' => $user->cabang->nama_cabang,
                        'kode_cabang' => $user->cabang->kode_cabang,
                        'kota' => $user->cabang->kota,
                    ] : null,
                    'pelanggan' => $user->pelanggan ? [
                        'id_pelanggan' => $user->pelanggan->id_pelanggan,
                        'nama_pelanggan' => $user->pelanggan->nama_pelanggan,
                        'kota' => $user->pelanggan->kota,
                        'no_hp' => $user->pelanggan->no_hp,
                        'alamat' => $user->pelanggan->alamat,
                        'kode_pos' => $user->pelanggan->kode_pos,
                    ] : null,
                ] : null,
            ],
            'cartCount' => $cartCount,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'settings' => [
                'nama_website' => Pengaturan::get('nama_website', 'MyShop'),
                'nama_toko' => Pengaturan::get('nama_toko', 'MyShop'),
                'tagline' => Pengaturan::get('tagline', 'Belanja Mudah, Cepat, dan Nyaman.'),
                'pajak_simulasi_persen' => (float) Pengaturan::get('pajak_simulasi_persen', '11'),
                'midtrans_client_key' => config('services.midtrans.client_key'),
            ],
        ];
    }
}
