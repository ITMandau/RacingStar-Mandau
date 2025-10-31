<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserBestrising;
use App\Models\Region;
use App\Models\Serpo;
use App\Models\Segmen;
use App\Models\Checklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminHomeController extends Controller
{
    private function currentQuarterRangeFromAnchor(Carbon $anchor, ?Carbon $now = null): array
    {
        $now = $now ?: now();
        $anchorStart = $anchor->copy()->startOfMonth();
        $nowStart    = $now->copy()->startOfMonth();

        if ($nowStart->lt($anchorStart)) {
            $start = $anchorStart->copy();
            $end   = $start->copy()->addMonthsNoOverflow(2)->endOfMonth();
        } else {
            $diffMonths = $anchorStart->diffInMonths($nowStart);
            $periodIdx  = intdiv($diffMonths, 3);
            $start      = $anchorStart->copy()->addMonths($periodIdx * 3);
            $end        = $start->copy()->addMonthsNoOverflow(2)->endOfMonth();
        }

        $fmt = fn (Carbon $d) => $d->translatedFormat('M Y');
        $shortMonth = fn (Carbon $d) => $d->translatedFormat('M');
        $label = $start->year === $end->year
            ? sprintf('%s–%s %d', $shortMonth($start), $shortMonth($end), $end->year)
            : sprintf('%s–%s', $fmt($start), $fmt($end));

        return [$start, $end, $label];
    }

    public function index(Request $request)
    {
        // ===== Determinasi Region Aktif =====
        $sessionUser   = auth()->user();
        $sessionRegion = $sessionUser->id_region ?? (session('auth_user.id_region') ?? null);
        $filterRegion  = $sessionRegion ? null : $request->query('region_id');
        $activeRegionId  = $sessionRegion ?: ($filterRegion ?: null);

        $regionOptions = $sessionRegion ? collect() : Region::query()
            ->orderBy('nama_region')
            ->get(['id_region','nama_region']);

        $applyRegionToSerpo = function ($q) use ($activeRegionId) {
            if ($activeRegionId) { $q->where('id_region', $activeRegionId); }
            return $q;
        };
        $applyRegionToSegmen = function ($q) use ($activeRegionId) {
            if ($activeRegionId) {
                $q->whereHas('serpo', function($qq) use ($activeRegionId){
                    $qq->where('id_region', $activeRegionId);
                });
            }
            return $q;
        };
        $applyRegionToUser = function ($q) use ($activeRegionId) {
            if ($activeRegionId) { $q->where('id_region', $activeRegionId); }
            return $q;
        };

        // ---- KPI counts (scoped) ----
        $counts = [
            'users'   => (int) UserBestrising::query()->tap($applyRegionToUser)->count(),
            'regions' => (int) ($activeRegionId ? 1 : Region::query()->count()),
            'serpos'  => (int) Serpo::query()->tap($applyRegionToSerpo)->count(),
            'segmens' => (int) Segmen::query()->tap($applyRegionToSegmen)->count(),
        ];

        // ---- STATUS CHECKLIST ----
        $statusCounts = Checklist::query()
            ->when($activeRegionId, function($q) use ($activeRegionId){
                $q->whereHas('serpo', function($qq) use ($activeRegionId){
                    $qq->where('id_region', $activeRegionId);
                });
            })
            ->selectRaw("
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as acc,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'review admin' THEN 1 ELSE 0 END) as review_admin,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")
            ->first();

        $statusStats = [
            'acc'      => (int) ($statusCounts->acc ?? 0),
            'pending'  => (int) ($statusCounts->pending ?? 0),
            'review_admin'  => (int) ($statusCounts->review_admin ?? 0),
            'rejected' => (int) ($statusCounts->rejected ?? 0),
        ];

        // ---- Top 5 Region by jumlah Serpo ----
        if ($activeRegionId) {
            $serpoByRegion = Region::query()
                ->where('id_region', $activeRegionId)
                ->withCount(['serpos' => fn($q) => $q->where('id_region', $activeRegionId)])
                ->get(['id_region','nama_region']);
        } else {
            $serpoByRegion = Region::query()
                ->withCount('serpos')
                ->orderByDesc('serpos_count')
                ->take(5)
                ->get(['id_region','nama_region']);
        }

        // ---- Top 5 Serpo by jumlah Segmen (scoped) ----
        $segmenBySerpo = Serpo::query()
            ->tap($applyRegionToSerpo)
            ->withCount('segmens')
            ->orderByDesc('segmens_count')
            ->take(5)
            ->get(['id_serpo','nama_serpo','id_region']);

        // ---- Latest data (scoped) ----
        $latestUsers = UserBestrising::query()
            ->tap($applyRegionToUser)
            ->with('kategoriUser:id_kategoriuser,nama_kategoriuser')
            ->latest('id_userBestrising')
            ->take(5)
            ->get(['id_userBestrising','nik','nama','email','kategori_user_id','id_region']);

        $latestSerpo = Serpo::query()
            ->tap($applyRegionToSerpo)
            ->with('region:id_region,nama_region')
            ->latest('id_serpo')
            ->take(5)
            ->get(['id_serpo','nama_serpo','id_region']);

        $latestSegmen = Segmen::query()
            ->tap($applyRegionToSegmen)
            ->with(['serpo:id_serpo,nama_serpo,id_region','serpo.region:id_region,nama_region'])
            ->latest('id_segmen')
            ->take(5)
            ->get(['id_segmen','nama_segmen','id_serpo']);

        // ==== Donut: total activity_result per activity ====
        $activityDonut = DB::table('activity_results as ar')
            ->leftJoin('activities as a', function($j){
                $j->on('a.id', '=', 'ar.activity_id');
            })
            ->when($activeRegionId, function($q) use ($activeRegionId) {
                $q->join('checklists as c', 'c.id', '=', 'ar.checklist_id')
                  ->join('serpos as sp', 'sp.id_serpo', '=', 'c.id_serpo')
                  ->where('sp.id_region', $activeRegionId);
            })
            ->selectRaw("COALESCE(a.name, a.name, 'Tanpa nama') as label, COUNT(ar.id) as value")
            ->groupByRaw("COALESCE(a.name, a.name, 'Tanpa nama')")
            ->orderByDesc('value')
            ->get();

        // Ringkas Top N
        $topN = 3;
        $top = $activityDonut->take($topN);
        $others = $activityDonut->slice($topN)->sum('value');
        if ($others > 0) {
            $top->push((object)['label' => 'Lainnya', 'value' => $others]);
        }

        // ---- Grafik: serpo per region (pakai hasil $serpoByRegion) ----
        $serpoPerRegion = collect($serpoByRegion ?? [])->map(fn ($r) => [
            'label' => $r->nama_region,
            'value' => (int) ($r->serpos_count ?? 0),
        ])->values();

        // ======= Leaderboard Points per Quarter dari Anchor =======
        $anchor = Carbon::create(2025, 9, 11);
        [$periodStart, $periodEnd, $periodLabel] = $this->currentQuarterRangeFromAnchor($anchor);

        $topSerpoPointsQuarter = Checklist::query()
            ->select('id_serpo', DB::raw('SUM(total_point) as points'))
            ->whereNotNull('id_serpo')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->when($activeRegionId, function($q) use ($activeRegionId) {
                $q->whereHas('serpo', function($qq) use ($activeRegionId){
                    $qq->where('id_region', $activeRegionId);
                });
            })
            ->groupBy('id_serpo')
            ->orderByDesc('points')
            ->with(['serpo:id_serpo,nama_serpo,id_region','serpo.region:id_region,nama_region'])
            ->take(7)
            ->get();

        $serpoPointsQuarter = $topSerpoPointsQuarter->map(fn ($r) => [
            'label' => $r->serpo->nama_serpo ?? ('Serpo #'.$r->id_serpo),
            'sub'   => $r->serpo && $r->serpo->region ? $r->serpo->region->nama_region : null,
            'value' => (int) $r->points,
        ])->values();

        // ---- NEW: Total star by Region (aggregate SUM of serpos.total_star grouped by serpos.id_region) ----
        // Menggunakan tabel serpos: ambil id_region dari serpos lalu jumlahkan total_star
        $totalStarByRegionQuery = DB::table('serpos as sp')
            ->select(
                'sp.id_region as id_region',
                DB::raw('COALESCE(r.nama_region, CONCAT("Region #", sp.id_region)) as nama_region'),
                DB::raw('COALESCE(SUM(sp.total_star),0) as total_star')
            )
            ->leftJoin('regions as r', 'r.id_region', '=', 'sp.id_region')
            ->when($activeRegionId, function($q) use ($activeRegionId){
                $q->where('sp.id_region', $activeRegionId);
            })
            ->groupBy('sp.id_region', 'r.nama_region')
            ->orderByDesc('total_star');

        $totalStarByRegion = $totalStarByRegionQuery->get();

        return view('bestRising.admin.index', [
            'counts'             => $counts,
            'stats'              => $counts,
            'serpoByRegion'      => $serpoByRegion,
            'segmenBySerpo'      => $segmenBySerpo,
            'latestUsers'        => $latestUsers,
            'latestSerpo'        => $latestSerpo,
            'latestSegmen'       => $latestSegmen,
            'serpoPerRegion'     => $serpoPerRegion,
            'activityDonut'      => $top,
            'periodStart'        => $periodStart,
            'periodEnd'          => $periodEnd,
            'periodLabel'        => $periodLabel,
            'serpoPointsQuarter' => $serpoPointsQuarter,
            'sessionRegionId'    => $sessionRegion,
            'activeRegionId'     => $activeRegionId,
            'regionOptions'      => $regionOptions,
            'statusStats'        => $statusStats,
            'totalStarByRegion'  => $totalStarByRegion,
        ]);
    }
}
