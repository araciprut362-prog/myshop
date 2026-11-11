export interface User {
    id_user: number;
    id_pelanggan: number | null;
    id_cabang: number | null;
    nama_lengkap: string;
    email: string;
    role: 'admin' | 'kasir' | 'pelanggan';
    no_hp: string | null;
    status: 'AKTIF' | 'NONAKTIF';
    cabang?: Cabang | null;
    pelanggan?: Pelanggan | null;
}

export interface Cabang {
    id_cabang: number;
    kode_cabang: string;
    nama_cabang: string;
    alamat: string | null;
    kota: string;
    telepon: string | null;
    status: 'AKTIF' | 'NONAKTIF';
}

export interface Pelanggan {
    id_pelanggan: number;
    nama_pelanggan: string;
    kota: string | null;
    no_hp: string | null;
    alamat: string | null;
    kode_pos: string | null;
    user?: User;
}

export interface Kategori {
    id_kategori: number;
    nama_kategori: string;
    deskripsi: string | null;
    status: 'AKTIF' | 'NONAKTIF';
    produk_count?: number;
}

export interface Produk {
    id_produk: number;
    id_kategori: number;
    sku: string;
    nama_produk: string;
    slug: string | null;
    deskripsi: string | null;
    harga: number;
    gambar: string | null;
    status: 'AKTIF' | 'NONAKTIF';
    kategori?: Kategori;
    stok_cabang?: StokCabang[];
    stok?: number;
    stok_minimum?: number;
}

export interface StokCabang {
    id_stok: number;
    id_cabang: number;
    id_produk: number;
    stok: number;
    stok_minimum: number;
    cabang?: Cabang;
    produk?: Produk;
}

export interface DetailTransaksi {
    id_detail: number;
    id_transaksi: number;
    id_produk: number;
    nama_produk_snapshot: string;
    sku_snapshot: string | null;
    harga_satuan: number;
    jumlah: number;
    diskon_item: number;
    subtotal: number;
    produk?: Produk;
}

export interface Pembayaran {
    id_pembayaran: number;
    id_transaksi: number;
    metode: 'TUNAI' | 'TRANSFER_BANK' | 'QRIS' | 'MIDTRANS';
    status: 'PENDING' | 'BERHASIL' | 'GAGAL' | 'KEDALUWARSA' | 'DIBATALKAN' | 'DIKEMBALIKAN';
    jumlah_bayar: number;
    provider_order_id: string | null;
    midtrans_transaction_id: string | null;
    midtrans_payment_type: string | null;
    snap_token: string | null;
    redirect_url: string | null;
    waktu_pembayaran: string | null;
}

export interface Transaksi {
    id_transaksi: number;
    kode_transaksi: string;
    tanggal: string;
    waktu_transaksi: string;
    id_pelanggan: number | null;
    id_kasir: number | null;
    id_cabang: number | null;
    jenis_transaksi: 'POS' | 'ONLINE' | 'LEGACY';
    status_transaksi: 'MENUNGGU_PEMBAYARAN' | 'DIPROSES' | 'DIKIRIM' | 'SELESAI' | 'DIBATALKAN';
    status_pembayaran: 'PENDING' | 'BERHASIL' | 'GAGAL' | 'KEDALUWARSA' | 'DIBATALKAN' | 'DIKEMBALIKAN' | 'TIDAK_DIKETAHUI';
    status_stok: 'BELUM_DIPROSES' | 'DIRESERVASI' | 'DIKURANGI' | 'DIKEMBALIKAN';
    subtotal: number;
    diskon: number;
    pajak: number;
    ongkir: number;
    total_belanja: number;
    alamat_pengiriman: string | null;
    catatan: string | null;
    pelanggan?: Pelanggan | null;
    kasir?: User | null;
    cabang?: Cabang | null;
    detail_transaksi?: DetailTransaksi[];
    pembayaran?: Pembayaran[];
    pembayaran_terbaru?: Pembayaran | null;
}

export interface DetailKeranjang {
    id_detail_keranjang: number;
    id_keranjang: number;
    id_produk: number;
    jumlah: number;
    produk: Produk;
}

export interface Keranjang {
    id_keranjang: number;
    id_pelanggan: number;
    id_cabang: number | null;
    status: 'AKTIF' | 'CHECKOUT' | 'KADALUWARSA';
    details: DetailKeranjang[];
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export interface SharedProps {
    auth: {
        user: User | null;
    };
    cartCount: number;
    flash: {
        success?: string;
        error?: string;
        info?: string;
        warning?: string;
    };
    settings: {
        nama_website: string;
        nama_toko: string;
        tagline: string;
        pajak_simulasi_persen: number;
        midtrans_client_key: string;
    };
    errors: Record<string, string>;
}

