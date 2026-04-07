@extends('layouts.master')
@section('title', 'Scan Device - ' . $device->name)

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <div class="d-flex align-items-center mb-4">
                    <a href="{{ route('devices.index') }}" class="btn btn-light rounded-circle me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h3 class="fw-bold mb-0">Dashboard {{ $device->name }}</h3>
                </div>

                <div class="text-center py-5 border rounded-4 bg-light mb-4">
                    <div id="qrcode-container">
                        <p id="status-text" class="mb-3 fw-bold text-muted">Menghubungkan ke Engine...</p>
                        <img id="qrcode-img" src="" class="img-fluid shadow-sm rounded-3"
                            style="display:none; max-width: 250px; margin: 0 auto;">

                        <div id="loader" class="spinner-border text-primary" role="status" style="display:none;"></div>
                    </div>
                </div>

                <div class="row text-center">
                    <div class="col-6 border-end">
                        <p class="text-muted small mb-1">Status Koneksi</p>
                        <h5 id="conn-status" class="fw-bold text-capitalize text-danger">{{ $device->status }}</h5>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small mb-1">Nomor Terhubung</p>
                        <h5 id="conn-number" class="fw-bold">{{ $device->number ?? '-' }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script>
        const deviceId = "{{ $device->id }}";
        // Hubungkan ke Node.js dengan menyertakan ID Device
        const socket = io('http://localhost:3000', {
            query: {
                deviceId: deviceId
            }
        });

        socket.on('connect', () => {
            $('#status-text').text('Menunggu QR Code...');
            $('#loader').show();
        });

        socket.on('qr_code', function(url) {
            $('#loader').hide();
            $('#qrcode-img').attr('src', url).fadeIn();
            $('#status-text').text('Silakan scan menggunakan WhatsApp Anda');
        });

        socket.on('status', function(status) {
            if (status === 'connected') {
                $('#qrcode-img').hide();
                $('#loader').hide();
                $('#status-text').html(
                    '<h4 class="text-success fw-bold"><i class="fas fa-check-circle me-2"></i>WhatsApp Terhubung!</h4>'
                );
                $('#conn-status').text('Connected').removeClass('text-danger').addClass('text-success');
                // : Redirect otomatis ke halaman index setelah 2 detik
                setTimeout(() => {
                    window.location.href = "{{ route('devices.index') }}";
                }, 2000);
            }
        });

        socket.on('message', function(msg) {
            $('#status-text').text(msg);
        });
    </script>
@endsection
