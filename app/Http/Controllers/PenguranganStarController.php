<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{PenguranganStar, Serpo};
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\{DB, Storage};

class PenguranganStarController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // DataTables utama: daftar pengurangan
            $q = PenguranganStar::with('serpo')->select('penguranganstars.*');

            if ($request->filled('id_serpo')) {
                $q->where('id_serpo', (int) $request->id_serpo);
            }

            return DataTables::of($q)
                ->addIndexColumn()
                ->addColumn('nama_serpo', function ($r) {
                    return $r->serpo->nama_serpo ?? '—';
                })
                ->addColumn('foto', function ($r) {
                    if (!$r->foto) return '—';
                    $url = Storage::disk('public')->url($r->foto);
                    return '<a href="'.$url.'" target="_blank">Lihat</a>';
                })
                ->addColumn('action', function ($r) {
                    return '
                        <button class="btn btn-sm btn-warning btn-edit"
                            data-id="'.$r->id_penguranganstar.'"
                            data-id_serpo="'.$r->id_serpo.'"
                            data-jumlah="'.$r->jumlah_pengurangan.'"
                            data-alasan="'.e($r->alasan).'">Edit</button>
                        <button class="btn btn-sm btn-danger btn-delete"
                            data-id="'.$r->id_penguranganstar.'">Hapus</button>
                    ';
                })
                ->rawColumns(['foto','action'])
                ->make(true);
        }

        // Untuk dropdown serpo di form
        $serpos = Serpo::orderBy('nama_serpo')->get(['id_serpo','nama_serpo','total_star']);
        return view('bestRising.admin.penguranganStar.index', compact('serpos'));
    }

    // DataTables ringkasan Serpo + total_star
    public function serpoTotals(Request $request)
    {
        $q = Serpo::query()->with('region')->select('serpos.*');

        if ($request->filled('id_region')) {
            $q->where('id_region', (int) $request->id_region);
        }

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->addColumn('nama_region', fn($r) => $r->region->nama_region ?? '—')
            ->addColumn('total_star_fmt', fn($r) => number_format((int)$r->total_star))
            ->make(true);
    }

    // Load satu row untuk modal edit
    public function show($id)
    {
        $row = PenguranganStar::with('serpo')->findOrFail($id);
        $payload = $row->toArray();
        $payload['foto_url'] = $row->foto ? Storage::disk('public')->url($row->foto) : null;
        return response()->json($payload);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_serpo'           => ['required','integer','exists:serpos,id_serpo'],
            'jumlah_pengurangan' => ['required','integer','min:1'],
            'alasan'             => ['nullable','string','max:255'],
            'foto'               => ['nullable','file','image','max:2048'],
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('foto')) {
                $data['foto'] = $request->file('foto')->store('pengurangan_foto', 'public');
            }

            $row = PenguranganStar::create($data);

            // Kurangi total_star serpo (incremental, anti minus)
            Serpo::where('id_serpo', $data['id_serpo'])
                ->update([
                    'total_star' => DB::raw('GREATEST(total_star - '.(int)$data['jumlah_pengurangan'].', 0)')
                ]);

            DB::commit();
            return response()->json([
                'ok'      => true,
                'message' => 'Pengurangan star berhasil ditambahkan.',
                'data'    => $row,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $row = PenguranganStar::findOrFail($id);

        $data = $request->validate([
            'id_serpo'           => ['required','integer','exists:serpos,id_serpo'],
            'jumlah_pengurangan' => ['required','integer','min:1'],
            'alasan'             => ['nullable','string','max:255'],
            'foto'               => ['nullable','file','image','max:2048'],
        ]);

        DB::beginTransaction();
        try {
            $oldSerpo  = (int) $row->id_serpo;
            $oldJumlah = (int) $row->jumlah_pengurangan;

            if ($request->hasFile('foto')) {
                if ($row->foto && Storage::disk('public')->exists($row->foto)) {
                    Storage::disk('public')->delete($row->foto);
                }
                $data['foto'] = $request->file('foto')->store('pengurangan_foto', 'public');
            }

            // Update record
            $row->update($data);

            $newSerpo  = (int) $row->id_serpo;
            $newJumlah = (int) $row->jumlah_pengurangan;

            // Sesuaikan total_star secara incremental:
            if ($oldSerpo === $newSerpo) {
                $selisih = $newJumlah - $oldJumlah;
                if ($selisih > 0) {
                    // pengurangan bertambah -> kurangi lagi total_star
                    Serpo::where('id_serpo', $newSerpo)
                        ->update(['total_star' => DB::raw('GREATEST(total_star - '.abs($selisih).', 0)')]);
                } elseif ($selisih < 0) {
                    // pengurangan berkurang -> kembalikan selisih
                    Serpo::where('id_serpo', $newSerpo)
                        ->update(['total_star' => DB::raw('GREATEST(total_star + '.abs($selisih).', 0)')]);
                }
            } else {
                // pindah serpo -> kembalikan ke serpo lama, terapkan ke serpo baru
                Serpo::where('id_serpo', $oldSerpo)
                    ->update(['total_star' => DB::raw('GREATEST(total_star + '.$oldJumlah.', 0)')]);

                Serpo::where('id_serpo', $newSerpo)
                    ->update(['total_star' => DB::raw('GREATEST(total_star - '.$newJumlah.', 0)')]);
            }

            DB::commit();
            return response()->json([
                'ok'      => true,
                'message' => 'Pengurangan star berhasil diperbarui.',
                'data'    => $row,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $row = PenguranganStar::findOrFail($id);

        DB::beginTransaction();
        try {
            $idSerpo = (int) $row->id_serpo;
            $jumlah  = (int) $row->jumlah_pengurangan;

            if ($row->foto && Storage::disk('public')->exists($row->foto)) {
                Storage::disk('public')->delete($row->foto);
            }

            $row->delete();

            // Hapus pengurangan -> kembalikan total_star sebesar jumlahnya
            Serpo::where('id_serpo', $idSerpo)
                ->update(['total_star' => DB::raw('GREATEST(total_star + '.$jumlah.', 0)')]);

            DB::commit();
            return response()->json(['ok'=>true,'message'=>'Pengurangan star berhasil dihapus.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['ok'=>false,'message'=>$e->getMessage()], 422);
        }
    }
}
