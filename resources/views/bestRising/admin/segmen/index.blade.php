@extends('layouts.appBestRising')

@section('main')
@php
  $u = session('auth_user') ?? null;
  $isSuper = isset($u['email']) && $u['email'] === 'superadmin@mandau.id';
  $lockedRegionId = $u['region_id'] ?? $u['id_region'] ?? null;
@endphp

<div class="content-wrapper">

    <div class="card">
        {{-- Header: judul + tombol di kanan --}}
        <div class="card-header d-flex align-items-center">
            <h3 class="mb-0">Data Segmen</h3>
            <div class="ms-auto ml-auto d-flex gap-2">
              <button class="btn btn-outline-secondary mr-2" id="btnExport">Export</button>
              <button class="btn btn-outline-primary mr-2" id="btnAddBulk">Tambah Banyak</button>
              <button class="btn btn-primary" id="btnAdd">Tambah Segmen</button>
            </div>
        </div>

        <div class="card-body">
            {{-- Template filter (disembunyikan, nanti dipindah ke toolbar DataTables) --}}
            <div id="dt-filters-template" style="display:none;">
                <div id="dt-filters" class="d-inline-flex align-items-center" style="gap:8px;margin-left:12px;">
                    @if(!$lockedRegionId || $isSuper)
                    <select id="filter_region" class="form-control form-control-sm" style="min-width:200px;">
                        <option value="">Semua Region</option>
                        @foreach($regions as $r)
                            <option value="{{ $r->id_region }}">{{ $r->nama_region }}</option>
                        @endforeach
                    </select>
                    @else
                        {{-- tampilkan badge region jika locked --}}
                        @php $lockedRegionObj = $regions->firstWhere('id_region', $lockedRegionId); @endphp
                        <div class="d-flex align-items-center">
                            <label class="me-2 mb-0">Region: </label>
                            <span class="badge bg-secondary">{{ $lockedRegionObj?->nama_region ?? 'Region' }}</span>
                        </div>
                    @endif

                    <select id="filter_serpo" class="form-control form-control-sm" style="min-width:200px;">
                        <option value="">Semua Serpo</option>
                        @foreach($serpos as $s)
                            @if(!$lockedRegionId || $isSuper || $s->id_region == $lockedRegionId)
                                <option value="{{ $s->id_serpo }}" data-region="{{ $s->id_region }}">{{ $s->nama_serpo }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            <table id="table-segmen" class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Region</th>
                        <th>Serpo</th>
                        <th>Nama Segmen</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Modal single --}}
<div class="modal fade" id="modalSegmen" tabindex="-1">
    <div class="modal-dialog">
        <form id="formSegmen">@csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Segmen</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_segmen">
                    <div class="form-group">
                        <label>Region</label>
                        @if(!$isSuper && $lockedRegionId)
                            @php $lockedRegionObj = $regions->firstWhere('id_region', $lockedRegionId); @endphp
                            <input type="hidden" id="form_region" value="{{ $lockedRegionId }}">
                            <input type="text" class="form-control" value="{{ $lockedRegionObj?->nama_region ?? 'Region' }}" readonly>
                        @else
                            <select id="form_region" class="form-control" required>
                                <option value="">-- Pilih Region --</option>
                                @foreach($regions as $r)
                                    <option value="{{ $r->id_region }}">{{ $r->nama_region }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Serpo</label>
                        <select name="id_serpo" id="form_serpo" class="form-control" required>
                            <option value="">-- Pilih Serpo --</option>
                            {{-- akan diisi dinamis --}}
                            @foreach($serpos as $s)
                                @if($isSuper || !$lockedRegionId || $s->id_region == $lockedRegionId)
                                    <option value="{{ $s->id_serpo }}" data-region="{{ $s->id_region }}">{{ $s->nama_serpo }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Segmen</label>
                        <input type="text" name="nama_segmen" id="nama_segmen" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSave">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal bulk --}}
<div class="modal fade" id="modalBulkSegmen" tabindex="-1">
  <div class="modal-dialog">
    <form id="formSegmenBulk">@csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Banyak Segmen</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Region</label>
            @if(!$isSuper && $lockedRegionId)
              @php $lockedRegionObj = $regions->firstWhere('id_region', $lockedRegionId); @endphp
              <input type="hidden" id="bulk_region" value="{{ $lockedRegionId }}">
              <input type="text" class="form-control" value="{{ $lockedRegionObj?->nama_region ?? 'Region' }}" readonly>
            @else
              <select id="bulk_region" class="form-control" required>
                <option value="">-- Pilih Region --</option>
                @foreach($regions as $r)
                  <option value="{{ $r->id_region }}">{{ $r->nama_region }}</option>
                @endforeach
              </select>
            @endif
          </div>
          <div class="form-group">
            <label>Serpo</label>
            <select id="bulk_serpo" name="id_serpo" class="form-control" required>
              <option value="">-- Pilih Serpo --</option>
              @foreach($serpos as $s)
                @if($isSuper || !$lockedRegionId || $s->id_region == $lockedRegionId)
                  <option value="{{ $s->id_serpo }}" data-region="{{ $s->id_region }}">{{ $s->nama_serpo }}</option>
                @endif
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Daftar Nama Segmen</label>
            <textarea id="bulk_names" name="names" class="form-control" rows="8" placeholder="Satu nama segmen per baris"></textarea>
            <small class="text-muted">Bisa paste langsung dari Excel (kolom nama).</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="btnBulkSave">Import</button>
        </div>
      </div>
    </form>
  </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
$(function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  const u = {!! json_encode($userSession ?? null) !!};
  const IS_SUPER = {!! json_encode($isSuper ? true : false) !!};
  const USER_REGION = {!! json_encode((!$isSuper && $lockedRegionId) ? $lockedRegionId : null) !!};

  // SweetAlert helpers
  const swalSuccess = (text='Berhasil diproses') =>
      Swal.fire({title:'Sukses', text, icon:'success', confirmButtonText:'OK'});
  const swalError = (text='Terjadi kesalahan') =>
      Swal.fire({title:'Gagal', text, icon:'error', confirmButtonText:'OK'});
  const swalConfirm = (text='Hapus data ini?') =>
      Swal.fire({title:'Yakin?', text, icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'});

  // --- route helper dari Blade (pakai placeholder utk id) ---
  const ROUTES = {
    serpoByRegion   : "{{ route('admin.serpo.byRegion', ['id_region' => 'RID']) }}",
    segmenIndex     : "{{ route('admin.segmen.index') }}",
    segmenStore     : "{{ route('admin.segmen.store') }}",
    segmenUpdate    : "{{ route('admin.segmen.update', ':id') }}",
    segmenDestroy   : "{{ route('admin.segmen.destroy', ':id') }}",
    segmenBulkStore : "{{ route('admin.segmen.bulkStore') }}",
    segmenExport    : "{{ route('admin.segmen.export') }}",
  };
  const urlUpdate   = id => ROUTES.segmenUpdate.replace(':id', id);
  const urlDestroy  = id => ROUTES.segmenDestroy.replace(':id', id);
  const urlByRegion = rid => ROUTES.serpoByRegion.replace('RID', rid);

  function loadSerpoByRegion(regionId, $select, selected = '') {
      $select.prop('disabled', true).empty().append('<option value="">Memuat Serpo...</option>');

      if (!regionId) {
          $select.prop('disabled', false).empty().append('<option value="">-- Pilih Serpo --</option>');
          return $.Deferred().resolve().promise();
      }

      // jika USER_REGION locked, override regionId
      if (USER_REGION) regionId = USER_REGION;

      return $.get(urlByRegion(regionId))
          .then(items => {
            $select.empty().append('<option value="">-- Pilih Serpo --</option>');
            items.forEach(it => {
                // buat option dengan data-region agar client-side check bekerja
                $('<option>')
                  .val(it.id)
                  .text(it.text)
                  .attr('data-region', regionId)
                  .appendTo($select);
            });
            if (selected) $select.val(String(selected));
          })
          .always(() => $select.prop('disabled', false));
  }

  // DataTable
  const table = $('#table-segmen').DataTable({
    processing:true, serverSide:true,
    dom: '<"row align-items-center"<"col-sm-6 d-flex"l<"dt-extra-filters d-inline-flex align-items-center ms-2 ml-2">><"col-sm-6"f>>rt<"row"<"col-sm-5"i><"col-sm-7"p>>',
    ajax: {
      url: ROUTES.segmenIndex,
      data: d => {
        if (USER_REGION) {
          d.id_region = USER_REGION;
          const sel = $('#filter_serpo').val();
          if (sel) d.id_serpo = sel;
        } else {
          d.id_region = $('#filter_region').val();
          d.id_serpo  = $('#filter_serpo').val();
        }
      }
    },
    columns: [
      {data:'DT_RowIndex', name:'DT_RowIndex', orderable:false, searchable:false},
      {data:'region', name:'region', orderable:false, searchable:false},
      {data:'serpo',  name:'serpo',  orderable:false, searchable:false},
      {data:'nama_segmen', name:'nama_segmen'},
      {data:'action', name:'action', orderable:false, searchable:false},
    ]
  });

  // Tempatkan filter ke toolbar
  const $filters = $('#dt-filters-template #dt-filters');
  $('.dt-extra-filters').append($filters);
  $('#dt-filters-template').remove();

  // Jika USER_REGION locked, set filter_serpo ke options yang sesuai
  if (USER_REGION) {
    $('#filter_serpo option').each(function(){
      if ($(this).data('region') && String($(this).data('region')) !== String(USER_REGION)) $(this).remove();
    });
  }

  // Toolbar: filter serpo by region
  $('#filter_region').on('change', function(){
    const rid = $(this).val();
    const $serpo = $('#filter_serpo').empty().append('<option value="">Semua Serpo</option>');
    if(!rid){ return table.ajax.reload(null,true); }
    $.get(urlByRegion(rid)).done(items => {
      $serpo.empty().append('<option value="">Semua Serpo</option>');
      items.forEach(it => {
        $('<option>').val(it.id).text(it.text).attr('data-region', rid).appendTo($serpo);
      });
      table.ajax.reload(null,true);
    });
  });

  $('#filter_serpo').on('change', ()=> table.ajax.reload(null,true));

  // Add single
  $('#btnAdd').on('click', function(){
    $('#modalTitle').text('Tambah Segmen');
    $('#formSegmen')[0].reset();
    $('#id_segmen').val('');
    if (USER_REGION) {
      // set region & load serpo for that region
      $('#form_region').val(USER_REGION);
      loadSerpoByRegion(USER_REGION, $('#form_serpo')).then(() => {
        $('#modalSegmen').modal('show');
      });
    } else {
      $('#form_serpo').empty().append('<option value="">-- Pilih Serpo --</option>');
      $('#modalSegmen').modal('show');
    }
  });

  $('#form_region').on('change', function () {
    loadSerpoByRegion(this.value, $('#form_serpo'));
  });

  // Edit single
  $(document).on('click','.btn-edit', function(){
    $('#modalTitle').text('Edit Segmen');
    $('#formSegmen')[0].reset();

    const id    = $(this).data('id');
    const nama  = $(this).data('nama');
    const regionId = $(this).data('region');
    const serpoId  = $(this).data('serpo');

    // client-side check
    if (!IS_SUPER && USER_REGION && (String(USER_REGION) !== String(regionId))) {
      swalError('Anda tidak diizinkan mengedit segmen di region lain.');
      return;
    }

    $('#id_segmen').val(id);
    $('#nama_segmen').val(nama);

    if (USER_REGION) {
      $('#form_region').val(USER_REGION);
      loadSerpoByRegion(USER_REGION, $('#form_serpo'), serpoId)
        .then(() => $('#modalSegmen').modal('show'))
        .fail(() => { $('#modalSegmen').modal('show'); });
    } else {
      $('#form_region').val(String(regionId));
      loadSerpoByRegion(regionId, $('#form_serpo'), serpoId)
        .then(() => $('#modalSegmen').modal('show'))
        .fail(() => { $('#modalSegmen').modal('show'); });
    }
  });

  // Submit (create/update)
  $('#formSegmen').on('submit', function(e){
    e.preventDefault();
    const id   = $('#id_segmen').val();
    const url  = id ? urlUpdate(id) : ROUTES.segmenStore;

    // before submit: extra client-side enforcement
    const selSerpo = $('#form_serpo').val();
    if (!selSerpo) { swalError('Pilih Serpo dulu'); return; }

    // dapatkan region dari option terpilih; fallback ke form_region
    let selOptionRegion = $('#form_serpo option:selected').data('region');
    if (selOptionRegion === undefined || selOptionRegion === null) {
      selOptionRegion = $('#form_region').val() || $('#bulk_region').val() || null;
    }

    // jika user locked -> verify selected serpo belongs to region
    if (USER_REGION && String(selOptionRegion) !== String(USER_REGION)) {
      swalError('Pilih serpo yang sesuai region Anda.'); return;
    }

    const data = $(this).serialize() + (id ? '&_method=PUT' : '');

    $('#btnSave').prop('disabled', true).text('Menyimpan...');

    $.post(url, data)
      .done(res => {
        $('#modalSegmen').modal('hide');
        table.ajax.reload(null,false);
        swalSuccess(res.message ?? (id ? 'Data berhasil diperbarui!' : 'Data berhasil ditambahkan!'));
      })
      .fail(xhr => {
        let msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
        if(xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
        swalError(msg);
      })
      .always(() => {
        $('#btnSave').prop('disabled', false).text('Simpan');
      });
  });

  // Delete + konfirmasi
  $(document).on('click','.btn-delete', function(){
    const id = $(this).data('id');
    const regionId = $(this).data('region');

    // client-side check
    if (!IS_SUPER && USER_REGION && (String(USER_REGION) !== String(regionId))) {
      swalError('Anda tidak diizinkan menghapus segmen di region lain.');
      return;
    }

    swalConfirm('Segmen ini akan dihapus.')
    .then(r => {
      if(!r.isConfirmed) return;
      $.post(urlDestroy(id), {_method:'DELETE'})
        .done(res => {
          table.ajax.reload(null,false);
          swalSuccess(res.message ?? 'Berhasil dihapus');
        })
        .fail(xhr => {
          let msg = xhr.responseJSON?.message || 'Gagal menghapus data';
          if(xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
          swalError(msg);
        });
    });
  });

  // ===== BULK =====
  $('#btnAddBulk').on('click', function(){
    $('#formSegmenBulk')[0].reset();
    if (USER_REGION) {
      $('#bulk_region').val(USER_REGION);
      loadSerpoByRegion(USER_REGION, $('#bulk_serpo'));
    } else {
      $('#bulk_serpo').empty().append('<option value="">-- Pilih Serpo --</option>');
    }
    $('#modalBulkSegmen').modal('show');
  });

  $('#bulk_region').on('change', function(){
    loadSerpoByRegion(this.value, $('#bulk_serpo'));
  });

  $('#formSegmenBulk').on('submit', function(e){
    e.preventDefault();
    const data = $(this).serialize();

    // client-side check: ensure serpo selected belongs to locked region if any
    if (USER_REGION) {
      const sel = $('#bulk_serpo').val();
      if (!sel) { swalError('Pilih Serpo untuk import'); return; }
      const selRegion = $('#bulk_serpo option:selected').data('region');
      if (String(selRegion) !== String(USER_REGION)) { swalError('Pilih serpo yang sesuai region Anda.'); return; }
    }

    $('#btnBulkSave').prop('disabled', true).text('Mengimpor...');
    $.post(ROUTES.segmenBulkStore, data)
      .done(res => {
        $('#modalBulkSegmen').modal('hide');
        table.ajax.reload(null, false);
        const msg = `Import selesai.
- Total input: ${res.total_in}
- Dibuat: ${res.created}
- Duplikat/terlewat: ${res.skipped}`;
        swalSuccess(msg);
      })
      .fail(xhr => {
        let msg = xhr.responseJSON?.message || 'Gagal import';
        if(xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
        swalError(msg);
      })
      .always(() => {
        $('#btnBulkSave').prop('disabled', false).text('Import');
      });
  });

  // ===== EXPORT =====
  $('#btnExport').on('click', function(){
    const id_region = USER_REGION ? USER_REGION : ($('#filter_region').val() || '');
    const id_serpo  = $('#filter_serpo').val() || '';
    const q = $('div.dataTables_filter input[type=search]').val() || '';
    const url = new URL(ROUTES.segmenExport, window.location.origin);
    if (id_region) url.searchParams.set('id_region', id_region);
    if (id_serpo)  url.searchParams.set('id_serpo',  id_serpo);
    if (q)         url.searchParams.set('q',         q);
    window.location.href = url.toString();
  });

});
</script>
@endsection
