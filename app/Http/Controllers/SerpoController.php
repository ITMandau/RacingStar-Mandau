<?php

namespace App\Http\Controllers;

use App\Models\Serpo;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SerpoExport;

class SerpoController extends Controller
{
    public function export(Request $request)
    {
        // gunakan session same as blade
        $u = session('auth_user') ?? null;
        $isSuper = isset($u['email']) && $u['email'] === 'superadmin@mandau.id';
        $userRegion = $u['region_id'] ?? $u['id_region'] ?? null;

        // Jika user bukan super dan punya region -> paksa export hanya region itu
        $filters = [
            'id_region' => ($isSuper ? $request->input('id_region') : $userRegion),
            'q'         => $request->input('q'),
        ];

        return Excel::download(new SerpoExport($filters), 'data_serpo.xlsx');
    }

    public function index(Request $request)
    {
        // jika ajax -> DataTables response
        if ($request->ajax()) {
            $u = session('auth_user') ?? null;
            $isSuper = isset($u['email']) && $u['email'] === 'superadmin@mandau.id';
            $userRegion = $u['region_id'] ?? $u['id_region'] ?? null;

            $query = Serpo::with('region')->select('serpos.*');

            // enforce server-side filter: kalau user punya region & bukan super, paksa
            if (!$isSuper && $userRegion) {
                $query->where('id_region', $userRegion);
            } else {
                if ($request->filled('id_region')) {
                    $query->where('id_region', $request->id_region);
                }
            }

            // global search
            if ($request->has('search') && !empty($request->input('search.value'))) {
                $s = $request->input('search.value');
                $query->where(function ($q) use ($s) {
                    $q->where('nama_serpo', 'like', "%{$s}%")
                      ->orWhereHas('region', fn($r) => $r->where('nama_region','like',"%{$s}%"));
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('region', fn($row) => $row->region?->nama_region ?? '-')
                ->addColumn('action', function ($row) use ($u, $isSuper) {
                    $userRegion = $u['region_id'] ?? $u['id_region'] ?? null;
                    $canManage = $isSuper || is_null($userRegion) || ($userRegion == $row->id_region);

                    $btns = '';
                    if ($canManage) {
                        $btns .= '<button class="btn btn-warning btn-sm btn-edit"
                            data-id="'. $row->id_serpo .'"
                            data-nama="'. e($row->nama_serpo) .'"
                            data-region="'. $row->id_region .'">Edit</button> ';
                        $btns .= '<button class="btn btn-danger btn-sm btn-delete"
                            data-id="'. $row->id_serpo .'"
                            data-region="'. $row->id_region .'">Hapus</button>';
                    } else {
                        $btns = '<span class="text-muted small">No actions</span>';
                    }
                    return $btns;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        // non-ajax: render view, kirim regions & session info
        return view('bestRising.admin.serpo.index', [
            'regions' => Region::orderBy('nama_region')->get(),
            // follow same names used di blade
            'userRegion' => session('auth_user')['region_id'] ?? session('auth_user')['id_region'] ?? null,
            'isSuper'    => isset(session('auth_user')['email']) && session('auth_user')['email'] === 'superadmin@mandau.id',
        ]);
    }

    public function store(Request $request)
    {
        $u = session('auth_user') ?? null;
        $isSuper = isset($u['email']) && $u['email'] === 'superadmin@mandau.id';
        $userRegion = $u['region_id'] ?? $u['id_region'] ?? null;

        $request->validate([
            'id_region'   => 'required|exists:regions,id_region',
            'nama_serpo'  => [
                'required','string','max:100',
                Rule::unique('serpos','nama_serpo')->where(fn($q) => $q->where('id_region', $request->id_region)),
            ],
        ]);

        // backend enforcement: kalau bukan super dan punya region, tidak boleh menambah di region lain
        if (!$isSuper && $userRegion && ($userRegion != $request->input('id_region'))) {
            return response()->json(['message' => 'Anda tidak memiliki izin menambah serpo di region ini.'], 403);
        }

        Serpo::create($request->only('id_region','nama_serpo'));

        return response()->json(['success' => true, 'message' => 'Serpo berhasil ditambahkan']);
    }

    public function update(Request $request, $id)
    {
        $u = session('auth_user') ?? null;
        $isSuper = isset($u['email']) && $u['email'] === 'superadmin@mandau.id';
        $userRegion = $u['region_id'] ?? $u['id_region'] ?? null;

        $serpo = Serpo::findOrFail($id);

        $request->validate([
            'id_region'   => 'required|exists:regions,id_region',
            'nama_serpo'  => [
                'required','string','max:100',
                Rule::unique('serpos','nama_serpo')
                    ->where(fn($q) => $q->where('id_region', $request->id_region))
                    ->ignore($serpo->id_serpo, 'id_serpo'),
            ],
        ]);

        // backend checks:
        if (!$isSuper && $userRegion) {
            // tidak boleh edit serpo di luar region nya
            if ($userRegion != $serpo->id_region) {
                return response()->json(['message' => 'Anda tidak memiliki izin mengubah serpo ini.'], 403);
            }
            // tidak boleh memindahkan ke region lain
            if ($userRegion != $request->input('id_region')) {
                return response()->json(['message' => 'Anda tidak dapat memindahkan serpo ke region lain.'], 403);
            }
        }

        $serpo->update($request->only('id_region','nama_serpo'));

        return response()->json(['success' => true, 'message' => 'Serpo berhasil diupdate']);
    }

    public function destroy($id)
    {
        $u = session('auth_user') ?? null;
        $isSuper = isset($u['email']) && $u['email'] === 'superadmin@mandau.id';
        $userRegion = $u['region_id'] ?? $u['id_region'] ?? null;

        $serpo = Serpo::findOrFail($id);

        if (!$isSuper && $userRegion && ($userRegion != $serpo->id_region)) {
            return response()->json(['message' => 'Anda tidak memiliki izin menghapus serpo ini.'], 403);
        }

        $serpo->delete();

        return response()->json(['success' => true, 'message' => 'Serpo berhasil dihapus']);
    }

    // ==== Endpoint bantu untuk dependent dropdown ====
    public function byRegion($id_region)
    {
        $items = Serpo::where('id_region', $id_region)
            ->orderBy('nama_serpo')
            ->get(['id_serpo as id','nama_serpo as text']);

        return response()->json($items);
    }
}
