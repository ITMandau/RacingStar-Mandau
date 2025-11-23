<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateSerpoTotalStarSeeder extends Seeder
{
    /**
     * Hitung ulang total_star serpo berdasarkan activity_results dan penguranganstars.
     */
    public function run(): void
    {
        DB::transaction(function () {
            DB::table('serpos')->update(['total_star' => 0]);

            $activityTotals = DB::table('activity_results as ar')
                ->join('checklists as c', 'c.id', '=', 'ar.checklist_id')
                ->select('c.id_serpo', DB::raw('SUM(ar.point_earned) as total_star'))
                ->whereNotNull('c.id_serpo')
                ->whereNull('ar.deleted_at')
                ->where('ar.is_approval', true)
                ->groupBy('c.id_serpo')
                ->pluck('total_star', 'c.id_serpo');

            $deductions = DB::table('penguranganstars')
                ->select('id_serpo', DB::raw('SUM(jumlah_pengurangan) as total_pengurangan'))
                ->groupBy('id_serpo')
                ->pluck('total_pengurangan', 'id_serpo');

            $serpoIds = $activityTotals->keys()
                ->merge($deductions->keys())
                ->unique();

            foreach ($serpoIds as $serpoId) {
                $earned = (int) $activityTotals->get($serpoId, 0);
                $deducted = (int) $deductions->get($serpoId, 0);

                if ($earned > 0) {
                    $final = max(0, $earned - $deducted);

                    DB::table('serpos')
                        ->where('id_serpo', $serpoId)
                        ->update(['total_star' => $final]);
                } else {
                    if ($deducted > 0) {
                        DB::table('penguranganstars')
                            ->where('id_serpo', $serpoId)
                            ->delete();
                    }

                    DB::table('serpos')
                        ->where('id_serpo', $serpoId)
                        ->update(['total_star' => 0]);
                }
            }
        });
    }
}
