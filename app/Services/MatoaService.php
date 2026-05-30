<?php

namespace App\Services;

use App\Models\Region;
use App\Models\Serpo;
use App\Models\UserBestrising;
use App\Models\UserMatoa;
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
        if (!$response->successful() || empty($json['user'])) {
            Log::info('matoa-error', ['res' => $json, 'email' => $email]);
            return UserBestrising::where('email', $email)->first();
        }
        Log::info('matoa', ['res' => $json, 'email' => $email]);
        $user = $json['user'];
        $regions = $user['area'] ?? [];
        $serpo = $user['team'] ?? null;
        return $this->checkAccount($user, $regions, $password, $serpo);
    }
    public function verifyToken($token){
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . base64_decode($token),
            ])->get(config('services.matoa.base_url') . '/api/user');
            if (!$response->successful()) {
                Log::info('matoa-error', ['res' => $response->json(), 'token' => $token]);
                return null;
            }
            $user = $response->json();
            $regions = $user['area'] ?? [];
            $serpo = $user['team'] ?? null;
            $password = UserMatoa::where('id', $user['id'])->pluck('password')->first();
            return $this->checkAccount($user, $regions, $password, $serpo, true);
        } catch (\Throwable $th) {
            Log::info('matoa-error', ['message' => $th->getMessage(), 'token' => $token]);
            return null;
        }
    }
    private function checkAccount($user, $regions = [], $password = null, $serpo = null, $encrypted = false)
    {
        $roleName = $user['roles']['name'] ?? 'team';
        $role = self::ROLE_MAP[$roleName] ?? 3;
        $category = Str::contains(strtolower($roleName), ['admin'])
            ? self::ROLE_MAP['admin']
            : $role;
        $team_id = null;
        $region_id = null;
        $region_name = collect($regions)->pluck('name')->first();
        if (!empty($serpo)) {
            $region = Region::firstOrCreate([
                'nama_region' => $serpo['city']['name'] ?? ($region_name ?? 'Uncategorized'),
            ]);
            $team = Serpo::firstOrCreate(
                ['nama_serpo' => $serpo['name']],
                ['id_region' => $region->id_region]
            );
            $team_id = $team?->id_serpo;
            $region_id = $team?->id_region;
        }
        $cek = UserBestrising::where('email', $user['email'])->first();

        if (empty($cek)) {
            if (empty($region_id)) {
                $region_id = Region::where('nama_region', $region_name)->first();
            }
            return UserBestrising::create([
                'email' => $user['email'],
                'password' => $encrypted ? $password : Hash::make($password),
                'nik' => $user['username'],
                'nama' => $user['name'],
                'kategori_user_id' => $category,
                'id_region' => $region_id,
                'id_serpo' => $team_id,
            ]);
        }
        return $cek;
    }
}
