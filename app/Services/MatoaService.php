<?php

namespace App\Services;

use App\Models\Region;
use App\Models\Serpo;
use App\Models\UserBestrising;
use Hash;
use Illuminate\Support\Facades\Http;
use Str;
use Log;

class MatoaService
{
    const ROLE_MAP = [
        'admin' => 1,
        'team' => 3,
        'NOC' => 2
    ];
    public function login($email, $password)
    {
        $response = Http::post(config('services.matoa.base_url') . '/api/auth/login', [
            'username' => $email,
            'password' => $password,
        ]);

        $json = $response->json();
        Log::info('matoa', ['res' => $json]);
        if (!$response->successful() || empty($json['user'])) {
            return null;
        }
        $user = $json['user'];
        $regions = $user['area'] ?? [];
        $serpo = $user['team'] ?? null;
        return $this->checkAccount($user, $regions, $password, $serpo);
    }

    private function checkAccount($user, $regions = [], $password = null, $serpo = null)
    {
        $role = self::ROLE_MAP[$user['roles']['name'] ?? 'team'];
        $category = Str::contains(strtolower($role), ['admin']) ? self::ROLE_MAP['admin'] : $role;
        $team_id = null;
        $region_id = null;
        if (!empty($serpo)) {
            $team = Serpo::where('nama_serpo', $serpo['name'])->first();
            $team_id = $team->id_serpo;
            $region_id = $team->id_region;
        }
        $cek = UserBestrising::where('email', $user['email'])->first();

        if (empty($cek)) {
            if (empty($region_id)) {
                $region_name = collect($regions)->pluck('name')->first();
                $region_id = Region::where('nama_region', $region_name)->first();
            }
            return UserBestrising::create([
                'email' => $user['email'],
                'password' => Hash::make($password),
                'nik' => $user['username'],
                'nama' => $user['name'],
                'kategori_user_id' => $category,
                'id_region' => $region_id,
            ]);
        }
        return $cek;
    }
}
