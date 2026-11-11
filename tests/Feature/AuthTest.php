<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_admin_can_authenticate_and_is_redirected_to_admin_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@myshop.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_kasir_can_authenticate_and_is_redirected_to_kasir_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'kasir.utama@myshop.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('kasir.dashboard'));
    }

    public function test_pelanggan_can_authenticate_and_is_redirected_to_home(): void
    {
        $response = $this->post('/login', [
            'email' => 'andi@myshop.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home'));
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::where('status', 'AKTIF')->where('role', 'pelanggan')->first();
        if ($user) {
            $user->update(['status' => 'NONAKTIF']);

            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $this->assertGuest();
            $response->assertSessionHasErrors('email');
        } else {
            $this->markTestSkipped('No user found');
        }
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@myshop.test',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_customer_can_register_and_is_assigned_pelanggan_role_only(): void
    {
        $email = 'newcustomer_'.time().'@example.test';

        $response = $this->post('/register', [
            'nama_lengkap' => 'Pelanggan Baru Testing',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'no_hp' => '081299998888',
            'kota' => 'Surabaya',
            'alamat' => 'Jl. Pahlawan No. 10',
            // Attempt privilege escalation to admin
            'role' => 'admin',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        // Ensure role forced to pelanggan
        $this->assertEquals('pelanggan', $user->role);
        $this->assertEquals('AKTIF', $user->status);

        // Verify tbl_pelanggan record created
        $pelanggan = Pelanggan::find($user->id_pelanggan);
        $this->assertNotNull($pelanggan);
        $this->assertEquals('Pelanggan Baru Testing', $pelanggan->nama_pelanggan);
        $this->assertEquals('Surabaya', $pelanggan->kota);
    }

    public function test_pelanggan_cannot_access_admin_dashboard(): void
    {
        $user = User::where('role', 'pelanggan')->first();
        $response = $this->actingAs($user)->get('/admin/dashboard');

        // Middleware redirects unauthorized web users to their appropriate page
        $response->assertRedirect(route('home'));
    }

    public function test_pelanggan_api_forbidden_on_admin_routes(): void
    {
        $user = User::where('role', 'pelanggan')->first();
        $response = $this->actingAs($user)->getJson('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_kasir_cannot_access_admin_dashboard(): void
    {
        $user = User::where('role', 'kasir')->first();
        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertRedirect(route('kasir.dashboard'));
    }
}
