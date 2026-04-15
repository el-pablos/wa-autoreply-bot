@extends('layouts.app')
@section('title', 'Log Pesan')
@section('page-title', 'Log Pesan')

@section('content')
<div class="card" style="margin-bottom:1rem;padding:.875rem">
  <form style="display:flex;flex-wrap:wrap;gap:.65rem;align-items:flex-end">
    <div>
      <label style="display:block;font-size:.8rem;color:#8b949e;margin-bottom:.25rem">Nomor</label>
      <input type="text" name="number" value="{{ request('number') }}" placeholder="628..." class="form-control" style="width:160px">
    </div>
    <div>
      <label style="display:block;font-size:.8rem;color:#8b949e;margin-bottom:.25rem">Dibalas</label>
      <select name="replied" class="form-control" style="width:120px">
        <option value="">Semua</option>
        <option value="yes" {{ request('replied')=='yes' ? 'selected':'' }}>Ya</option>
        <option value="no"  {{ request('replied')=='no'  ? 'selected':'' }}>Tidak</option>
      </select>
    </div>
    <div>
      <label style="display:block;font-size:.8rem;color:#8b949e;margin-bottom:.25rem">Allow-list</label>
      <select name="is_allowed" class="form-control" style="width:120px">
        <option value="">Semua</option>
        <option value="yes" {{ request('is_allowed')=='yes' ? 'selected':'' }}>Ya</option>
        <option value="no"  {{ request('is_allowed')=='no'  ? 'selected':'' }}>Tidak</option>
      </select>
    </div>
    <div>
      <label style="display:block;font-size:.8rem;color:#8b949e;margin-bottom:.25rem">Dari Tanggal</label>
      <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" style="width:150px">
    </div>
    <div>
      <label style="display:block;font-size:.8rem;color:#8b949e;margin-bottom:.25rem">Sampai Tanggal</label>
      <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" style="width:150px">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="{{ route('logs.index') }}" class="btn btn-ghost btn-sm">Reset</a>
  </form>
</div>

<div class="card" style="overflow-x:auto">
  <table>
    <thead>
      <tr>
        <th>Nomor</th><th>Pesan</th><th>Tipe</th>
        <th>Allow-list</th><th>Dibalas</th><th>Waktu</th>
      </tr>
    </thead>
    <tbody>
      @forelse($logs as $log)
      <tr>
        <td style="font-family:monospace;font-size:.8rem">{{ $log->from_number }}</td>
        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.85rem">{{ $log->message_text ?? '-' }}</td>
        <td><span class="badge badge-info">{{ $log->message_type }}</span></td>
        <td><span class="badge {{ $log->is_allowed ? 'badge-success' : 'badge-danger' }}">{{ $log->is_allowed ? '✓' : '✗' }}</span></td>
        <td><span class="badge {{ $log->replied ? 'badge-success' : 'badge-danger' }}">{{ $log->replied ? '✓' : '✗' }}</span></td>
        <td style="font-size:.8rem;color:#8b949e;white-space:nowrap">{{ $log->received_at?->format('d/m H:i') }}</td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;color:#8b949e;padding:2rem">Belum ada log</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div style="margin-top:1rem">{{ $logs->links() }}</div>
@endsection
