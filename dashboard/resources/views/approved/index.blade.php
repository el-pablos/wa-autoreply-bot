@extends('layouts.app')
@section('title', 'Approved Sessions')
@section('page-title', 'Approved Sessions')

@section('content')
@if(session('success'))
  <div class="alert alert-success">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">❌ {{ session('error') }}</div>
@endif

<div class="card" style="margin-bottom:1rem;overflow-x:auto">
  <h2 style="font-size:1rem;margin-bottom:.85rem">Sesi Aktif</h2>
  <table>
    <thead>
      <tr>
        <th>Nomor</th>
        <th>Disetujui Oleh</th>
        <th>Approved At</th>
        <th>Last Activity</th>
        <th>Expires At</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @forelse($activeSessions as $session)
      <tr>
        <td style="font-family:monospace">{{ $session->phone_number }}</td>
        <td style="font-family:monospace">{{ $session->approved_by }}</td>
        <td style="white-space:nowrap">{{ $session->approved_at?->format('d/m/Y H:i') }}</td>
        <td style="white-space:nowrap">{{ $session->last_activity_at?->format('d/m/Y H:i') }}</td>
        <td style="white-space:nowrap">{{ $session->expires_at?->format('d/m/Y H:i') }}</td>
        <td>
          <form action="{{ route('approved.revoke', $session->id) }}" method="POST" style="display:inline"
                onsubmit="return confirm('Cabut sesi untuk {{ $session->phone_number }}?')">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm">Revoke</button>
          </form>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;color:#8b949e;padding:1.5rem">Belum ada sesi aktif</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div style="margin-bottom:1.25rem">{{ $activeSessions->links() }}</div>

<div class="card" style="overflow-x:auto">
  <h2 style="font-size:1rem;margin-bottom:.85rem">Riwayat Sesi</h2>
  <table>
    <thead>
      <tr>
        <th>Nomor</th>
        <th>Disetujui Oleh</th>
        <th>Approved At</th>
        <th>Expires At</th>
        <th>Revoked At</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($historySessions as $session)
      <tr>
        <td style="font-family:monospace">{{ $session->phone_number }}</td>
        <td style="font-family:monospace">{{ $session->approved_by }}</td>
        <td style="white-space:nowrap">{{ $session->approved_at?->format('d/m/Y H:i') }}</td>
        <td style="white-space:nowrap">{{ $session->expires_at?->format('d/m/Y H:i') }}</td>
        <td style="white-space:nowrap">{{ $session->revoked_at?->format('d/m/Y H:i') ?? '-' }}</td>
        <td>
          <span class="badge {{ $session->revoked_at ? 'badge-danger' : 'badge-info' }}">
            {{ $session->revoked_at ? 'Revoked' : 'Expired' }}
          </span>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;color:#8b949e;padding:1.5rem">Belum ada riwayat sesi</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div style="margin-top:1rem">{{ $historySessions->links() }}</div>
@endsection
