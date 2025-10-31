@extends('layouts.appBestRising')
@section('main')

<script src="{{ asset('adminLTE/plugins/jquery/jquery.min.js') }}"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  .serpo-card.gold{
    background: linear-gradient(135deg,#fffbe6,#fff1b8);
    border-color:#fef616;
    position:relative;
  }
  .serpo-card.silver{
    background: linear-gradient(135deg,#f8fafc,#e2e8f0);
    border-color:#94a3b8;
    position:relative;
  }
  .serpo-card.bronze{
    background: linear-gradient(135deg,#fff7ed,#fed7aa);
    border-color:#ab5d1d;
    position:relative;
  }
  .rank-tag{
    position:absolute;top:6px;right:10px;
    font-size:.8rem;font-weight:800;color:#0f172a;
    opacity:.8;
  }


  :root{
    --brand:#16a34a; --ink:#0f172a; --muted:#64748b; --line:#e5e7eb;
  }
  .page-head{display:flex;align-items:center;justify-content:space-between;margin:4px 0 14px}
  .page-title{display:flex;align-items:center;gap:.6rem}
  .page-title .dot{width:12px;height:12px;border-radius:999px;background:var(--brand)}
  .page-title h4{margin:0;font-weight:800;color:var(--ink)}
  .chip{background:#e8f7ee;color:#166534;border-radius:999px;padding:.25rem .6rem;font-weight:700;font-size:.75rem}

  .card{border:0;border-radius:16px;box-shadow:0 10px 28px rgba(2,6,23,.06)}
  .card-header{border:0;border-top-left-radius:16px;border-top-right-radius:16px;background:linear-gradient(140deg,#f8fafc,#f1f5f9)}
  .card-body{padding:16px 18px}

  /* Tabs (Bootstrap 4) */
  .nav-tabs{border-bottom:1px solid var(--line)}
  .nav-tabs .nav-link{border:0;border-bottom:2px solid transparent;font-weight:700;color:#475569}
  .nav-tabs .nav-link.active{color:#111827;border-color:var(--brand);background:transparent}

  /* Table */
  .table-wrap{border:1px solid var(--line);border-radius:12px;overflow:hidden}
  table.dataTable thead th{background:#f8fafc;border-bottom:1px solid var(--line)}
  .th-min{width:72px}
  .text-end{text-align:right !important;}

  /* Grid Ringkasan */
  .serpo-grid{display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:12px}
  @media(min-width:576px){.serpo-grid{grid-template-columns:repeat(2,1fr)}}
  @media(min-width:992px){.serpo-grid{grid-template-columns:repeat(3,1fr)}}
  .serpo-card{border:1px solid var(--line);border-radius:14px;background:#fff;padding:14px;
    display:flex;flex-direction:column;gap:8px;transition:.15s}
  .serpo-card:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(2,6,23,.08)}
  .serpo-name{font-weight:800;color:#111827;margin:0}
  .serpo-meta{display:flex;justify-content:space-between;align-items:center}
  .badge-region{background:#f1f5f9;border-radius:999px;padding:.25rem .55rem;color:#334155;font-weight:700;font-size:.75rem}
  .stars{font-weight:900;color:#111827}
  .stars i{color:#f59e0b;margin-right:4px}

  .toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
  .btn-brand{background:var(--brand);border:0;color:#fff;border-radius:12px;padding:.55rem .9rem}
  .btn-soft{background:#f8fafc;border:1px solid var(--line);border-radius:12px;padding:.45rem .8rem}
</style>

<div class="content-wrapper">
  <section class="content p-3">

    <!-- Header -->
    <div class="page-head">
      <div class="page-title">
        <span class="dot"></span><h4>Pengurangan Star</h4>
        <span class="chip">Realtime</span>
      </div>
      <div class="toolbar">
        <button class="btn btn-brand btn-sm" id="btnAdd"><i class="fas fa-plus me-1"></i>Tambah</button>
      </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="tabWrap" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="tab-pengurangan" data-toggle="tab"
           href="#pane-pengurangan" role="tab" aria-controls="pane-pengurangan"
           aria-selected="true">Pengurangan</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-ringkasan" data-toggle="tab"
           href="#pane-ringkasan" role="tab" aria-controls="pane-ringkasan"
           aria-selected="false">Ringkasan Serpo</a>
      </li>
    </ul>

    <div class="tab-content">
      <!-- Tab: Pengurangan -->
      <div class="tab-pane fade show active" id="pane-pengurangan" role="tabpanel" aria-labelledby="tab-pengurangan">
        <div class="card">
          <div class="card-header d-flex align-items-center justify-content-between py-3 px-3 px-lg-4">
            <h6 class="m-0 fw-bold">Data Pengurangan Star</h6>
            <span class="text-muted" style="font-size:.9rem">Kelola pengurangan per serpo</span>
          </div>
          <div class="card-body">
            <div class="table-wrap">
              <table id="table-pengurangan-star" class="table table-striped w-100">
                <thead>
                  <tr>
                    <th class="th-min">NO</th>
                    <th>Serpo</th>
                    <th class="th-min">Jumlah</th>
                    <th>Alasan</th>
                    <th class="th-min">Foto</th>
                    <th class="th-min">Action</th>
                  </tr>
                </thead>
              </table>
            </div>
            <div class="mt-2 text-muted" style="font-size:.85rem">
              <i class="fas fa-info-circle me-1"></i>Hapus = total star dikembalikan sebesar jumlah pengurangan.
            </div>
          </div>
        </div>
      </div>

      <!-- Tab: Ringkasan -->
      <div class="tab-pane fade" id="pane-ringkasan" role="tabpanel" aria-labelledby="tab-ringkasan">
        <div class="card">
          <div class="card-header py-3 px-3 px-lg-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="m-0 fw-bold">Ringkasan Total Star per Serpo</h6>
            <div class="toolbar d-flex align-items-center gap-2 flex-wrap">
              <div class="d-flex align-items-center gap-2">
                <input type="text" id="searchSerpo" class="form-control form-control-sm" placeholder="Cari serpo..." style="min-width:180px">
                <select id="filterRegion" class="form-control form-control-sm" style="min-width:160px">
                  <option value="">Semua Region</option>
                  @php
                    $regions = \App\Models\Region::orderBy('nama_region')->get(['id_region','nama_region']);
                  @endphp
                  @foreach($regions as $r)
                    <option value="{{ $r->nama_region }}">{{ $r->nama_region }}</option>
                  @endforeach
                </select>
              </div>
              <div class="d-flex align-items-center gap-2">
                <button class="btn btn-soft btn-sm" id="btnGrid"><i class="fas fa-th-large me-1"></i>Grid</button>
                <button class="btn btn-soft btn-sm" id="btnTable"><i class="fas fa-table me-1"></i>Tabel</button>
              </div>
            </div>
          </div>

          <div class="card-body">
            <!-- GRID -->
            <div id="gridWrap" class="serpo-grid"></div>

            <!-- TABLE -->
            <div id="tableWrap" class="table-wrap mt-2" style="display:none;">
              <table id="table-serpo-totals" class="table table-striped w-100">
                <thead>
                  <tr>
                    <th class="th-min">NO</th>
                    <th>Serpo</th>
                    <th>Region</th>
                    <th class="text-end th-min">Total Star</th>
                  </tr>
                </thead>
              </table>
            </div>

            <div class="text-muted mt-2" style="font-size:.85rem">
              Gunakan kolom pencarian dan filter region untuk mempermudah navigasi serpo.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Create/Edit -->
    <div class="modal fade" id="formModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <form id="modalForm" enctype="multipart/form-data">
          @csrf
          <input type="hidden" id="mode" value="create">
          <input type="hidden" id="row_id">
          <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#f8fafc;border-bottom:1px solid var(--line);border-top-left-radius:16px;border-top-right-radius:16px">
              <h5 class="modal-title" id="modalTitle">Tambah Pengurangan</h5>
              <button type="button" class="btn-close" data-dismiss="modal">✕</button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label>Serpo</label>
                <select name="id_serpo" id="id_serpo" class="form-control" required>
                  <option value="">— Pilih —</option>
                  @foreach($serpos as $s)
                    <option value="{{ $s->id_serpo }}">{{ $s->nama_serpo }} (total: {{ $s->total_star }})</option>
                  @endforeach
                </select>
              </div>
              <div class="mb-3">
                <label>Jumlah Pengurangan</label>
                <input type="number" min="1" name="jumlah_pengurangan" id="jumlah_pengurangan" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Alasan (opsional)</label>
                <input type="text" name="alasan" id="alasan" class="form-control" placeholder="Opsional">
              </div>
              <div class="mb-1">
                <label>Foto (opsional)</label>
                <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
                <div id="previewFoto" class="mt-2"></div>
              </div>
              <div id="formAlert" class="alert alert-danger d-none mt-3"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--line)">
              <button type="button" class="btn btn-soft" data-dismiss="modal">Tutup</button>
              <button type="submit" class="btn btn-brand" id="btnSubmit">Simpan</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Delete -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <form id="formDelete">
          @csrf
          @method('DELETE')
          <input type="hidden" id="delete_id">
          <div class="modal-content" style="border-radius:16px">
            <div class="modal-header" style="background:#fff7ed;border-bottom:1px solid #fde68a;border-top-left-radius:16px;border-top-right-radius:16px">
              <h5 class="modal-title">Hapus Pengurangan</h5>
              <button type="button" class="btn-close" data-dismiss="modal">✕</button>
            </div>
            <div class="modal-body">
              Yakin ingin menghapus data ini?
              <div class="text-muted mt-1" style="font-size:.85rem">
                Total star serpo akan <b>dikembalikan</b> sebesar jumlah pengurangan ini.
              </div>
              <div id="deleteAlert" class="alert alert-danger d-none mt-3"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #fde68a">
              <button type="button" class="btn btn-soft" data-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-danger">Hapus</button>
            </div>
          </div>
        </form>
      </div>
    </div>

  </section>
</div>

<script>
$(function () {
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  const ROUTES = {
    index       : "{{ route('admin.pengurangan-star.index') }}",
    store       : "{{ route('admin.pengurangan-star.store') }}",
    show        : "{{ route('admin.pengurangan-star.show', ':id') }}",
    update      : "{{ route('admin.pengurangan-star.update', ':id') }}",
    destroy     : "{{ route('admin.pengurangan-star.destroy', ':id') }}",
    serpoTotals : "{{ route('admin.pengurangan-star.serpo-totals') }}",
  };
  const urlShow    = id => ROUTES.show.replace(':id', id);
  const urlUpdate  = id => ROUTES.update.replace(':id', id);

  // Tabel pengurangan
  let table = $('#table-pengurangan-star').DataTable({
    processing:true, serverSide:true, ajax:ROUTES.index,
    columns:[
      {data:'DT_RowIndex',orderable:false,searchable:false},
      {data:'nama_serpo'},
      {data:'jumlah_pengurangan'},
      {data:'alasan'},
      {data:'foto',orderable:false,searchable:false},
      {data:'action',orderable:false,searchable:false}
    ], order:[[1,'asc']]
  });

  const $gridWrap = $('#gridWrap');
  const $tableWrap = $('#tableWrap');

  let serpoTable = $('#table-serpo-totals').DataTable({
    processing:true, serverSide:true, ajax:{url:ROUTES.serpoTotals},
    columns:[
      {data:'DT_RowIndex',orderable:false,searchable:false},
      {data:'nama_serpo'},
      {data:'nama_region'},
      {data:'total_star_fmt',className:'text-end'}
    ], order:[[1,'asc']]
  });

  function renderSerpoGrid(){
    const keyword = $('#searchSerpo').val().toLowerCase();
    const region  = $('#filterRegion').val();

    $.get(ROUTES.serpoTotals,{draw:1,length:1000},function(res){
      const arr = res.data || [];
      if(!arr.length){
        $gridWrap.html('<div class="text-muted">Tidak ada serpo ditemukan.</div>');
        return;
      }

      // urutkan berdasarkan total_star
      const sorted = [...arr].sort((a,b)=>(b.total_star ?? 0)-(a.total_star ?? 0));

      let html = '';
      sorted
        .filter(i=>{
          const matchKeyword = !keyword || (i.nama_serpo && i.nama_serpo.toLowerCase().includes(keyword));
          const matchRegion  = !region || (i.nama_region === region);
          return matchKeyword && matchRegion;
        })
        .forEach((i,idx)=>{
          // tentuin peringkat top 3
          let medalClass = '', medalIcon = '';
          if(idx === 0){ medalClass='gold'; medalIcon='<i class="fas fa-crown"></i>'; }
          else if(idx === 1){ medalClass='silver'; medalIcon='<i class="fas fa-medal"></i>'; }
          else if(idx === 2){ medalClass='bronze'; medalIcon='<i class="fas fa-medal"></i>'; }

          html += `
            <div class="serpo-card ${medalClass}">
              <div class="serpo-meta">
                <span class="badge-region">${i.nama_region||'—'}</span>
                <div class="stars">${medalIcon}<i class="fas fa-star ms-1"></i>${i.total_star_fmt||0}</div>
              </div>
              <h5 class="serpo-name">${i.nama_serpo||'—'}</h5>
              ${medalClass ? `<div class="rank-tag">#${idx+1}</div>` : ''}
            </div>`;
        });

      $gridWrap.html(html);
    }).fail(()=> $gridWrap.html('<div class="text-danger">Gagal memuat data.</div>'));
  }
  renderSerpoGrid();

  $('#searchSerpo').on('input', renderSerpoGrid);
  $('#filterRegion').on('change', renderSerpoGrid);

  $('#btnGrid').on('click',()=>{$tableWrap.hide();$gridWrap.show();renderSerpoGrid();});
  $('#btnTable').on('click',()=>{$gridWrap.hide();$tableWrap.show();serpoTable.ajax.reload(null,false);});

  function reloadAll(){
    table.ajax.reload(null,false);
    if($tableWrap.is(':visible')) serpoTable.ajax.reload(null,false);
    else renderSerpoGrid();
  }

  // CRUD event
  $('#btnAdd').on('click',()=>{$('#mode').val('create');$('#row_id').val('');
    $('#modalTitle').text('Tambah Pengurangan');$('#id_serpo').val('');
    $('#jumlah_pengurangan').val('');$('#alasan').val('');$('#foto').val(null);
    $('#previewFoto').html('');$('#formAlert').addClass('d-none').text('');
    $('#formModal').modal('show');
  });

  $('#modalForm').on('submit',function(e){
    e.preventDefault();
    const mode=$('#mode').val(),id=$('#row_id').val();
    const fd=new FormData(this);
    let url=(mode==='create'?ROUTES.store:urlUpdate(id));
    if(mode!=='create')fd.append('_method','PUT');
    $('#btnSubmit').prop('disabled',true).text('Menyimpan...');
    $.ajax({url,type:'POST',data:fd,processData:false,contentType:false})
    .done(res=>{
      $('#btnSubmit').prop('disabled',false).text('Simpan');
      $('#formModal').modal('hide');reloadAll();
      Swal.fire('Sukses',res.message||'Berhasil disimpan','success');
    })
    .fail(xhr=>{
      $('#btnSubmit').prop('disabled',false).text('Simpan');
      $('#formAlert').removeClass('d-none').text(xhr.responseJSON?.message||'Gagal');
    });
  });

  $('body').on('click','.btn-edit',function(){
    const id=$(this).data('id');$('#mode').val('edit');$('#row_id').val(id);
    $('#modalTitle').text('Edit Pengurangan');$('#formAlert').addClass('d-none').text('');
    $('#foto').val(null);$('#previewFoto').html('');
    $.get(urlShow(id),function(res){
      $('#id_serpo').val(res.id_serpo);$('#jumlah_pengurangan').val(res.jumlah_pengurangan);
      $('#alasan').val(res.alasan||'');
      if(res.foto_url)$('#previewFoto').html(`<a href="${res.foto_url}" target="_blank">Lihat foto lama</a>`);
      $('#formModal').modal('show');
    }).fail(xhr=>Swal.fire('Gagal',xhr.responseJSON?.message||'Tidak bisa memuat data','error'));
  });

  $('body').on('click','.btn-delete',function(){
    $('#delete_id').val($(this).data('id'));$('#deleteAlert').addClass('d-none').text('');
    $('#deleteModal').modal('show');
  });

  $('#formDelete').on('submit',function(e){
    e.preventDefault();
    const id=$('#delete_id').val();
    $.ajax({url:ROUTES.destroy.replace(':id',id),type:'POST',data:{_method:'DELETE'}})
    .done(res=>{
      $('#deleteModal').modal('hide');reloadAll();
      Swal.fire('Sukses',res.message||'Berhasil dihapus','success');
    })
    .fail(xhr=>{
      const msg=xhr.responseJSON?.message||'Gagal menghapus.';
      $('#deleteAlert').removeClass('d-none').text(msg);
    });
  });
});
</script>
@endsection
