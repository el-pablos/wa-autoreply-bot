@extends('layouts.app')
@section('title', 'Allow-List')
@section('page-title', 'Allow-List')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem">
  <a href="{{ route('allowlist.create') }}" class="btn btn-primary">+ Tambah Nomor</a>
  <form style="display:flex;gap:.5rem;flex-wrap:wrap">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor / label..." class="form-control" style="width:180px">
    <select name="status" class="form-control" style="width:130px">
      <option value="">Semua Status</option>
      <option value="active"   {{ request('status')=='active'   ? 'selected':'' }}>Aktif</option>
      <option value="inactive" {{ request('status')=='inactive' ? 'selected':'' }}>Nonaktif</option>
    </select>
    <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
  </form>
</div>

<div class="card" style="overflow-x:auto">
  <table>
    <thead>
      <tr><th>Nomor</th><th>Label</th><th>Status</th><th>Ditambah</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      @forelse($numbers as $n)
      <tr>
        <td style="font-family:monospace">{{ $n->phone_number }}</td>
        <td>{{ $n->label ?? '<span style="color:#8b949e">-</span>' }}</td>
        <td>
          <span class="badge {{ $n->is_active ? 'badge-success' : 'badge-danger' }}">
            {{ $n->is_active ? 'Aktif' : 'Nonaktif' }}
          </span>
        </td>
        <td style="font-size:.8rem;color:#8b949e">{{ $n->created_at->format('d/m/Y') }}</td>
        <td>
          <div style="display:flex;gap:.4rem;flex-wrap:wrap">
            <a href="{{ route('allowlist.edit', $n) }}" class="btn btn-ghost btn-sm">Edit</a>
            <form action="{{ route('allowlist.toggle', $n) }}" method="POST" style="display:inline">
              @csrf @method('PATCH')
              <button type="submit" class="btn btn-ghost btn-sm">{{ $n->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>
            <form action="{{ route('allowlist.destroy', $n) }}" method="POST" style="display:inline"
                  onsubmit="return confirm('Hapus nomor {{ $n->phone_number }}?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;color:#8b949e;padding:2rem">Belum ada nomor di allow-list</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div style="margin-top:1rem">{{ $numbers->links() }}</div>
@endsection
