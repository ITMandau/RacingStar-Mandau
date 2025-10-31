<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TotalStarSerpoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset semua total_star jadi 0 dulu
        DB::table('serpos')->update(['total_star' => 0]);

        // Ambil total star dari checklist completed, per serpo
        $totals = DB::table('checklists')
            ->select('id_serpo', DB::raw('SUM(total_point) as total_star'))
            ->where('status', 'completed')
            ->whereNotNull('id_serpo')
            ->groupBy('id_serpo')
            ->get();

        // Loop hasil dan update ke tabel serpos
        foreach ($totals as $row) {
            DB::table('serpos')
                ->where('id_serpo', $row->id_serpo)
                ->update(['total_star' => $row->total_star]);
        }
    }
}
