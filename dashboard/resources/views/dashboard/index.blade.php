@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('topbar-right')
  @php $status = $stats['bot_status'] @endphp
  <span class="status-badge {{ $status }}">
    <span class="status-dot"></span>
    Bot {{ ucfirst($status) }}
  </span>
@endsection

@section('content')
<div class="stat-grid" style="margin-bottom:1.25rem">
  <div class="stat-card">
    <div class="num">{{ number_format($stats['total_messages']) }}</div>
    <div class="lbl">Total Pesan</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ number_format($stats['today_messages']) }}</div>
    <div class="lbl">Pesan Hari Ini</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ number_format($stats['total_replied']) }}</div>
    <div class="lbl">Sudah Dibalas</div>
  </div>
  <div class="stat-card">
    <div class="num">{{ $stats['active_numbers'] }}</div>
    <div class="lbl">Nomor Aktif</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:1rem">
  <div class="card">
    <div style="font-weight:600;margin-bottom:1rem">📈 Pesan 7 Hari Terakhir</div>
    <canvas id="dailyChart" height="100"></canvas>
  </div>

  <div class="card">
    <div style="font-weight:600;margin-bottom:1rem">⏱️ Pesan Terbaru</div>
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>Nomor</th><th>Pesan</th><th>Dibalas</th><th>Waktu</th></tr></thead>
        <tbody>
          @forelse($recentLogs as $log)
          <tr>
            <td style="font-family:monospace;font-size:.8rem">{{ $log->from_number }}</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $log->message_text ?? '-' }}</td>
            <td><span class="badge {{ $log->replied ? 'badge-success' : 'badge-danger' }}">{{ $log->replied ? '✓' : '✗' }}</span></td>
            <td style="font-size:.8rem;color:#8b949e">{{ $log->received_at?->diffForHumans() }}</td>
          </tr>
          @empty
          <tr><td colspan="4" style="text-align:center;color:#8b949e;padding:2rem">Belum ada pesan masuk</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('dailyChart');
const labels = @json($daily->pluck('date'));
const data   = @json($daily->pluck('total'));
new Chart(ctx, {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Pesan',
      data,
      backgroundColor: 'rgba(37,211,102,0.3)',
      borderColor: '#25d366',
      borderWidth: 2,
      borderRadius: 4,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: '#30363d' }, ticks: { color: '#8b949e' } },
      y: { grid: { color: '#30363d' }, ticks: { color: '#8b949e', stepSize: 1 } },
    }
  }
});
</script>
@endpush
@endsection
