<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    
    public function test_login_page_renders_with_student_section(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('Portal Masuk Shuttle Kampus')
            ->assertSee('NIM atau Alamat Email')
            ->assertSee('Daftar Akun Baru');
    }

    
    public function test_registration_page_renders_properly(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200)
            ->assertSee('Pendaftaran Akun Mahasiswa')
            ->assertSee('Nomor Induk Mahasiswa (NIM)')
            ->assertSee('Daftarkan Akun Mahasiswa');
    }

    
    public function test_student_can_register_new_account(): void
    {
        $payload = [
            'name'                  => 'Budi Mahasiswa UNIPI',
            'nim'                   => '2024099',
            'email'                 => 'budi@mahasiswa.campus.ac.id',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name'  => 'Budi Mahasiswa UNIPI',
            'nim'   => '2024099',
            'email' => 'budi@mahasiswa.campus.ac.id',
            'role'  => 'mahasiswa',
        ]);
    }

    
    public function test_registration_rejects_duplicate_nim_or_email(): void
    {
        User::create([
            'name'     => 'Akun Eksis',
            'nim'      => '2024001',
            'email'    => 'eksis@campus.ac.id',
            'password' => bcrypt('password123'),
            'role'     => 'mahasiswa',
        ]);

        $duplicatePayload = [
            'name'                  => 'Mahasiswa Duplikat',
            'nim'                   => '2024001',
            'email'                 => 'eksis@campus.ac.id',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $duplicatePayload);
        $response->assertSessionHasErrors(['nim', 'email']);
    }
}
