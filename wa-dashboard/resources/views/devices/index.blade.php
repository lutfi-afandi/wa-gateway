@extends('layouts.master')
@section('title', 'My Devices')

@section('content')
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold">My WhatsApp Devices</h2>
            <p class="text-muted">Kelola semua nomor WhatsApp Anda dalam satu panel.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                <i class="fas fa-plus me-2"></i>Tambah Nomor Baru
            </button>
        </div>
    </div>

    <div class="row">
        @forelse($devices as $device)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="badge bg-light text-dark p-2 rounded">
                                <i class="fas fa-mobile-alt me-2"></i>ID: {{ $device->id }}
                            </div>
                            <div>
                                <span
                                    class="status-dot {{ $device->status == 'connected' ? 'dot-connected' : 'dot-disconnected' }}"></span>
                                <small class="fw-bold text-uppercase">{{ $device->status }}</small>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1">{{ $device->name }}</h4>
                        <p class="text-muted small mb-4">{{ $device->number ?? 'Not paired yet' }}</p>

                        <div class="d-grid gap-2">
                            @if ($device->status == 'connected')
                                <form action="{{ route('devices.logout', $device->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm w-100 text-white fw-bold">
                                        <i class="fas fa-power-off me-2"></i>Logout Session
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('devices.show', $device->id) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-qrcode me-2"></i>Scan Ulang
                                </a>

                                <form action="{{ route('devices.destroy', $device->id) }}" method="POST"
                                    onsubmit="return confirm('Hapus permanen data device ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="btn btn-link btn-sm text-danger w-100 text-decoration-none">
                                        <i class="fas fa-trash-alt me-2"></i>Hapus Permanen
                                    </button>
                                </form>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-robot fa-5x text-secondary opacity-25"></i>
                </div>
                <h5 class="fw-bold text-secondary">Belum ada Device WhatsApp</h5>
                <p class="text-muted">Tambahkan nomor WhatsApp pertama Anda untuk mulai mengirim pesan otomatis.</p>
                <button class="btn btn-outline-dark btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                    <i class="fas fa-plus me-2"></i>Buat Device Sekarang
                </button>
            </div>
        @endforelse
    </div>

    <div class="modal fade" id="addDeviceModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('devices.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Tambah Device Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Device</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Admin CS" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark">Simpan Device</button>
                </div>
            </form>
        </div>
    </div>
@endsection
