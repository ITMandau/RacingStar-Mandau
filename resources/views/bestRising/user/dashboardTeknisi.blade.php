@extends('layouts.userapp')
@section('main')

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  /* ===== Palette */
  :root{
    --ink:#0f172a; --muted:#64748b; --line:#e5e7eb; --card:#ffffff;
    --g1:#16a34a; --g2:#22c55e;
    --b1:#2563eb; --b2:#1d4ed8;
    --a1:#f59e0b; --a2:#c2410c;
    --p1:#7c3aed; --p2:#6d28d9;
    --sky:#06b6d4; --vio:#8b5cf6;
  }

  /* Prevent horizontal scroll */
  html,body,.content-wrapper{max-width:100%;overflow-x:hidden}

  /* ===== Layout: mobile-first */
  .dash-grid{display:grid;gap:14px}
  .stats-row{
    display:grid; gap:12px;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  }
  .lower-grid{
    display:grid; gap:12px;
    grid-template-columns: 1fr; /* HP = 1 kolom */
  }
  @media (min-width: 992px){
    .lower-grid{ grid-template-columns: 1fr 2fr }  /* Desktop = 2 kolom */
  }

  /* ===== Card */
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 10px 24px rgba(2,6,23,.06);transition:transform .25s ease, box-shadow .25s ease}
  .card:hover{transform:translateY(-2px)}
  .card-h{
    padding:14px 16px;border-bottom:1px solid var(--line);
    background:linear-gradient(135deg,#f8fafc 0%,#eef2ff 40%, #e6fffb 100%);
    background-size:200% 200%; animation:headerGlow 16s ease infinite;
    border-top-left-radius:16px;border-top-right-radius:16px
  }
  @keyframes headerGlow{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
  .card-b{padding:14px 16px}
  .title{margin:0;font-weight:800;color:var(--ink)}

  /* ===== Profile header */
  .profile{display:flex;align-items:center;gap:12px}
  .avatar{
    width:52px;height:52px;border-radius:12px;background:#ecfeff;display:flex;align-items:center;justify-content:center;
    font-weight:800;color:var(--ink);border:1px solid var(--line)
  }
  .chip{background:#e8f7ee;color:#166534;border-radius:999px;padding:.28rem .6rem;font-weight:700;font-size:.75rem}

  /* ===== Stat cards (lebih colorful, animasi) */
  .stat{display:flex;align-items:center;justify-content:space-between;padding:16px;border-radius:16px;color:#fff;border:0;min-width:0}
  .stat .k{font-size:1.35rem;font-weight:900}
  .stat .l{font-size:.8rem;opacity:.95}
  .stat .i{font-size:18px;padding:10px;border-radius:12px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.22)}
  .grad{background-size:180% 180%; animation:gradShift 14s ease-in-out infinite}
  .g-green{background-image:linear-gradient(135deg,var(--g1),var(--g2))}
  .g-blue {background-image:linear-gradient(135deg,var(--b1),var(--b2))}
  .g-amber{background-image:linear-gradient(135deg,var(--a1),var(--a2))}
  .g-purple{background-image:linear-gradient(135deg,var(--p1),var(--p2))}
  @keyframes gradShift{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}

  /* ===== Progress bar + glow tip */
  .progress{height:10px;background:#eef2f7;border-radius:999px;overflow:hidden}
  .progress>div{height:100%;background:linear-gradient(90deg,var(--g2),var(--g1));position:relative}
  .progress>div::after{
    content:""; position:absolute; right:-6px; top:50%; transform:translateY(-50%);
    width:10px; height:10px; border-radius:50%;
    background:radial-gradient(circle, rgba(34,197,94,.9) 0%, rgba(34,197,94,0) 70%); filter:blur(1px);
  }

  /* ===== Lists (2 box bawah) – zebra + left accent + responsive text */
  .list{list-style:none;margin:0;padding:0}
  .list li{
    display:flex;align-items:flex-start;justify-content:space-between;gap:10px;
    padding:12px 12px;border-bottom:1px dashed var(--line);
    background:linear-gradient(90deg, rgba(37,99,235,.06), transparent);
  }
  .list li:nth-child(even){background:linear-gradient(90deg, rgba(16,185,129,.07), transparent)}
  .list li:hover{background:linear-gradient(90deg, rgba(124,58,237,.08), transparent)}
  .list li:last-child{border-bottom:0}
  .left-accent{position:relative;padding-left:10px;min-width:0}
  .left-accent::before{
    content:"";position:absolute;left:0;top:12px;bottom:12px;width:4px;border-radius:8px;
    background:linear-gradient(var(--sky), var(--vio));
  }

  /* TEXT CONTAINERS: allow shrink on flex to avoid overflow */
  .flex-text{min-width:0;flex:1 1 auto}
  .item-title{font-weight:700;color:var(--ink);overflow-wrap:anywhere}
  .truncate-line{
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
  }
  .small-muted{font-size:.78rem;color:var(--muted)}

  /* Right meta (points), keep compact; can wrap under on very small screens */
  .item-meta{flex:0 0 auto;white-space:nowrap}
  @media (max-width: 480px){
    .list li{flex-wrap:wrap}
    .item-meta{width:100%;margin-top:4px}
  }

  /* Rank badge & slot flip */
  .rank-badge{display:inline-flex;align-items:center;gap:6px;padding:.25rem .55rem;border-radius:999px;border:1px solid var(--line);background:#fff}
  .slot-badge{gap:4px}
  .slot-badge .hash{color:var(--ink);font-weight:800}
  .slot{display:inline-block; perspective:600px; height:22px}
  .slot-face{
    display:inline-block; min-width:18px; padding:0 6px; line-height:22px;
    background:linear-gradient(180deg,#111827,#0b1020); color:#fff; border-radius:6px;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.06), 0 3px 8px rgba(2,6,23,.25);
    transform-origin:50% 100%; transform:rotateX(90deg); animation:slotFlip .6s ease forwards;
  }
  @keyframes slotFlip{0%{transform:rotateX(90deg);}100%{transform:rotateX(0deg);}}

  /* Badges kecil */
  .badge-soft{display:inline-block;padding:.18rem .5rem;border-radius:999px;font-weight:700;font-size:.72rem}
  .soft-emerald{background:rgba(16,185,129,.12);color:#065f46;border:1px solid rgba(16,185,129,.25)}
  .soft-blue{background:rgba(37,99,235,.12);color:#1e3a8a;border:1px solid rgba(37,99,235,.25)}

  /* Buttons ripple */
  .btn,[data-ripple]{position:relative;overflow:hidden}
  .ripple{position:absolute;border-radius:50%;transform:scale(0);opacity:.35;background:#fff;pointer-events:none;animation:ripple .6s ease-out}
  @keyframes ripple{to{transform:scale(12);opacity:0}}

  /* Reduced motion */
  @media (prefers-reduced-motion: reduce){
    *{animation:none !important; transition:none !important}
  }
</style>

<div class="content-wrapper">
  <section class="content p-3">
    @php
      $meName = $user->nama ?? $user->name ?? 'User';
      $initial = strtoupper(substr($meName,0,2));
      $pct = (int)($percent_to_next ?? 0);
      $nextName = $next_rank->name ?? '—';
      $remain = (int)($points_to_next ?? 0);
    @endphp

    <div class="dash-grid">

      {{-- Header --}}
      <div class="card">
        <div class="card-h"><h5 class="title">Dashboard Teknisi</h5></div>
        <div class="card-b d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="profile">
            <div class="avatar">{{ $initial }}</div>
            <div>
              <div style="font-weight:900">{{ $meName }}</div>
              <div class="text-muted small">Team SERPO</div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <span class="rank-badge"><i class="fas fa-trophy text-warning"></i> {{ $rank->name ?? 'Rookie' }}</span>
            <span class="chip" data-ripple>Realtime</span>
          </div>
        </div>
      </div>

      {{-- Stat cards (4 kotak atas) --}}
      <div class="stats-row">
        <div class="stat grad g-green">
          <div class="flex-text">
            <div class="k mono js-count">{{ $points }}</div>
            <div class="l">Star (setelah dikurangi)</div>
          </div>
          <div class="i"><i class="fas fa-star"></i></div>
        </div>

        <div class="stat grad g-blue">
          <div class="flex-text">
            <div class="k mono js-count">{{ $completed_month }}</div>
            <div class="l">Checklist Completed</div>
          </div>
          <div class="i"><i class="fas fa-check-circle"></i></div>
        </div>

        <div class="stat grad g-amber">
          <div class="flex-text">
            <div class="k mono js-count">{{ $star_month }}</div>
            <div class="l">Star (bulan ini)</div>
          </div>
          <div class="i"><i class="fas fa-bolt"></i></div>
        </div>

        <div class="stat grad g-purple">
          <div class="flex-text">
            <div class="k mono">#{{ $team_rank ?? '-' }}</div>
            <div class="l">Peringkat Serpo dari {{ $total_serpo ?? 0 }}</div>
          </div>
          <div class="i"><i class="fas fa-crown"></i></div>
        </div>
      </div>

      {{-- Progress Rank --}}
      <div class="card">
        <div class="card-h"><h6 class="title">Progres ke Rank Selanjutnya</h6></div>
        <div class="card-b">
          <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
              <div class="fw-bold text-dark">Menuju: {{ $nextName }}</div>
              <div class="text-muted small">Butuh <b>{{ $remain }}</b> point lagi</div>
            </div>
            <div class="badge-soft soft-emerald"><i class="fas fa-signal"></i> {{ $pct }}%</div>
          </div>
          <div class="divider" style="height:1px;background:var(--line);margin:10px 0"></div>
          <div class="progress"><div style="width:{{ $pct }}%"></div></div>

          <div class="mt-2 small-muted">
            Team kamu saat ini peringkat <b>#{{ $team_rank }}</b> dari {{ $total_serpo }} Serpo
          </div>

          <div class="d-flex justify-content-end mt-2 gap-2">
            <button class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#modalPenguranganStar" data-ripple>
              Lihat Pengurangan Star
            </button>
            <a href="{{ route('checklists.table-ceklis') }}" class="btn btn-sm btn-outline-secondary" data-ripple>Lihat Aktivitas</a>
            <a href="{{ route('teknisi.index') }}" class="btn btn-sm btn-success text-white" data-ripple>Mulai Checklist</a>
          </div>
        </div>
      </div>

      {{-- Dua box bawah (leaderboard + aktivitas) --}}
      <div class="lower-grid">

        {{-- Leaderboard --}}
        <div class="card">
          <div class="card-h"><h6 class="title">Leaderboard Top 5</h6></div>
          <div class="card-b">
            <ul class="list">
              @forelse($leaderboardTop as $idx => $row)
                <li class="left-accent">
                  <div class="d-flex align-items-center gap-2 flex-text">
                    <span class="rank-badge slot-badge">
                      <span class="hash">#</span>
                      <span class="slot"><span class="slot-face">{{ $idx+1 }}</span></span>
                    </span>
                    <div class="flex-text">
                      <div class="item-title truncate-line">{{ $row->name }}</div>
                      <div class="small-muted">Stars: <span class="badge-soft soft-blue">{{ $row->points }}</span></div>
                    </div>
                  </div>
                </li>
              @empty
                <li class="text-muted">Belum ada data leaderboard.</li>
              @endforelse
            </ul>

            <div class="mt-3 p-3" style="background:linear-gradient(135deg, rgba(6,182,212,.08), rgba(139,92,246,.08)); border:1px dashed var(--line); border-radius:12px;">
              <div class="small-muted mb-1">Posisi tim kamu:</div>
              <div class="fw-bold">Rank #{{ $team_rank }} dari {{ $total_serpo }} serpo</div>
            </div>
          </div>
        </div>

        {{-- Aktivitas Terakhir --}}
        <div class="card">
          <div class="card-h"><h6 class="title">Aktivitas Terakhir</h6></div>
          <div class="card-b">
            <ul class="list">
              @forelse($recentActivities as $a)
                <li class="left-accent">
                  <div class="flex-text">
                    <div class="item-title truncate-line">{{ $a->title ?? 'Checklist' }}</div>
                    <div class="small-muted">{{ \Carbon\Carbon::parse($a->created_at)->format('d M Y H:i') }}</div>
                  </div>
                  <div class="item-meta">
                    <span class="mono fw-bold badge-soft soft-emerald">+{{ $a->point }}</span>
                  </div>
                </li>
              @empty
                <li class="text-muted">Belum ada aktivitas.</li>
              @endforelse
            </ul>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>

<!-- Modal Pengurangan Star (BS4) -->
<div class="modal fade" id="modalPenguranganStar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Riwayat Pengurangan Star</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
      </div>
      <div class="modal-body">
        @if($penguranganList->isEmpty())
          <div class="text-center text-muted">Belum ada data pengurangan star.</div>
        @else
          <div class="row g-3">
            @foreach($penguranganList as $p)
              <div class="col-12 col-md-6 col-lg-4">
                <div class="border rounded p-2 h-100 d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-danger">-{{ $p->jumlah_pengurangan }} ★</span>
                    <small class="text-muted">{{ \Carbon\Carbon::parse($p->created_at)->format('d M Y H:i') }}</small>
                  </div>
                  <div class="fw-bold text-dark mb-1">{{ $p->alasan ?? '-' }}</div>
                  @if($p->foto)
                    <div class="mt-auto text-center">
                      <a href="{{ asset('storage/'.$p->foto) }}" target="_blank" class="d-block">
                        <img src="{{ asset('storage/'.$p->foto) }}" class="img-fluid rounded"
                             style="max-height:180px;object-fit:cover;border:1px solid #e5e7eb">
                      </a>
                    </div>
                  @else
                    <div class="mt-auto text-center text-muted small">Tidak ada foto</div>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-ripple>Tutup</button>
      </div>
    </div>
  </div>
</div>

{{-- Micro-interaction JS (ringan) --}}
<script>
// Counter angka
(function(){
  const ease = t => 1 - Math.pow(1 - t, 3);
  const dur = 900;
  const els = Array.from(document.querySelectorAll('.js-count'))
    .filter(el=> /^\d+$/.test((el.textContent||'').trim()));
  els.forEach(el=>{
    const target = parseInt(el.textContent.trim(),10);
    let start=null;
    function step(ts){
      if(!start) start=ts;
      const p = Math.min(1,(ts-start)/dur);
      el.textContent = Math.round(ease(p)*target);
      if(p<1) requestAnimationFrame(step);
    }
    el.textContent='0'; requestAnimationFrame(step);
  });
})();

// Progress animate width
(function(){
  document.querySelectorAll('.progress > div').forEach(bar=>{
    const w = bar.style.width || '0%';
    bar.style.width='0%';
    setTimeout(()=>{ bar.style.transition='width .9s ease'; bar.style.width=w; }, 60);
  });
})();

// Ripple effect
(function(){
  function addRipple(e){
    const el = e.currentTarget;
    const r  = document.createElement('span');
    const rect = el.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    r.className = 'ripple';
    r.style.width = r.style.height = size + 'px';
    r.style.left = (e.clientX - rect.left - size/2) + 'px';
    r.style.top  = (e.clientY - rect.top  - size/2) + 'px';
    el.appendChild(r);
    r.addEventListener('animationend', ()=> r.remove());
  }
  document.querySelectorAll('.btn,[data-ripple]').forEach(el=> el.addEventListener('click', addRipple));
})();
</script>
@endsection
