<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardTeknisiController extends Controller
{
    public function index()
    {
        // --- Ambil user dari session manual_auth atau dari guard
        $sess = session('auth_user');
        $auth = auth()->user();

        if (is_array($sess)) {
            $userArr = $sess;
        } elseif (is_object($sess)) {
            $userArr = (array) $sess;
        } elseif ($auth) {
            $userArr = [
                'id_userBestrising' => $auth->id_userBestrising ?? null,
                'id'        => $auth->id ?? null,
                'nama'      => $auth->nama ?? $auth->name ?? '',
                'id_region' => $auth->id_region ?? null,
                'id_serpo'  => $auth->id_serpo ?? null,
            ];
        } else {
            return redirect()->route('login')->with('error', 'Session berakhir, silakan login ulang.');
        }

        $uid     = $userArr['id_userBestrising'] ?? $userArr['id'] ?? null;
        $serpoId = $userArr['id_serpo'] ?? null;

        if (!$uid) {
            return redirect()->route('login')->with('error', 'ID user tidak ditemukan di sesi.');
        }
        if (!$serpoId) {
            return redirect()->route('login')->with('error', 'User belum terikat ke Serpo.');
        }

        // 1) TOTAL STAR NET
        //    Ambil langsung nilai net dari serpos.total_star (sudah bertambah saat approve & berkurang saat pengurangan)
        $totalPoints = (int) (DB::table('serpos')->where('id_serpo', $serpoId)->value('total_star') ?? 0);

        // 2) Rank sederhana
        $rankName      = 'Rookie';
        $nextRankName  = 'Bronze';
        $pointsToNext  = max(0, 50 - $totalPoints);
        $percentToNext = min(100, ($totalPoints / 50) * 100);

        if ($totalPoints >= 50 && $totalPoints < 100) {
            $rankName = 'Bronze';
            $nextRankName = 'Silver';
            $pointsToNext  = 100 - $totalPoints;
            $percentToNext = max(0, min(100, ($totalPoints - 50) * 2));
        } elseif ($totalPoints >= 100 && $totalPoints < 200) {
            $rankName = 'Silver';
            $nextRankName = 'Gold';
            $pointsToNext  = 200 - $totalPoints;
            $percentToNext = max(0, min(100, ($totalPoints - 100) / 1));
        } elseif ($totalPoints >= 200 && $totalPoints < 500) {
            $rankName = 'Gold';
            $nextRankName = 'Master';
            $pointsToNext  = 500 - $totalPoints;
            $percentToNext = max(0, min(100, ($totalPoints - 200) / 3));
        } elseif ($totalPoints >= 500) {
            $rankName = 'Master';
            $nextRankName = '-';
            $pointsToNext  = 0;
            $percentToNext = 100;
        }


        // 3) Statistik bulan ini
        $now = Carbon::now();

        $completedThisMonth = DB::table('checklists')
            ->where('user_id', $uid)
            ->whereMonth('submitted_at', $now->month)
            ->whereYear('submitted_at', $now->year)
            ->count();

        // Catatan: ini SUM point_earned bulan ini (bruto per aktivitas)
        $starThisMonth = DB::table('activity_results')
            ->where('user_id', $uid)
            ->where('is_approval', 1)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('point_earned');

        // 4) RANKING SERPO (pakai nilai net langsung dari serpos.total_star)
        $rankedSerpos = DB::table('serpos')
            ->select('id_serpo', 'total_star')
            ->orderByDesc('total_star')
            ->get();

        $teamRank   = 1;
        $totalSerpo = $rankedSerpos->count();
        foreach ($rankedSerpos as $i => $r) {
            if ((int)$r->id_serpo === (int)$serpoId) { $teamRank = $i + 1; break; }
        }

        // 5) Riwayat pengurangan (untuk modal)
        $penguranganList = DB::table('penguranganstars')
            ->where('id_serpo', $serpoId)
            ->orderByDesc('created_at')
            ->get(['jumlah_pengurangan','alasan','foto','created_at']);

        // 6) Leaderboard top 5 (NET, berdasarkan total_star serpo)
        $top5 = DB::table('serpos as s')
            ->select(
                's.id_serpo',
                DB::raw('s.nama_serpo AS name'),
                DB::raw('s.total_star AS points') // NET (sudah setelah pengurangan)
            )
            ->orderByDesc('s.total_star')
            ->limit(5)
            ->get();

        // 7) Aktivitas terakhir user — JOIN ke activities + filter approved
        $recent5 = DB::table('activity_results as ar')
            ->leftJoin('activities as a', 'a.id', '=', 'ar.activity_id')
            ->where('ar.user_id', $uid)
            ->where('ar.is_approval', 1)
            ->orderByDesc('ar.created_at')
            ->limit(5)
            ->get([
                DB::raw('COALESCE(a.name, CONCAT("Activity #", ar.activity_id)) AS title'),
                DB::raw('ar.point_earned AS point'),
                'ar.created_at',
            ]);

        return view('bestRising.user.dashboardTeknisi', [
            'user'             => (object) $userArr,
            'rank'             => (object) ['name' => $rankName],
            'next_rank'        => (object) ['name' => $nextRankName],
            'points'           => $totalPoints,          // ← ini NET dari serpos.total_star
            'points_to_next'   => $pointsToNext,
            'percent_to_next'  => $percentToNext,
            'completed_month'  => $completedThisMonth,
            'star_month'       => $starThisMonth,
            'leaderboardTop'   => $top5,
            'recentActivities' => $recent5,
            'team_rank'        => $teamRank,
            'total_serpo'      => $totalSerpo,
            'penguranganList'  => $penguranganList,
        ]);
    }
}
