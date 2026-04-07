<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WA Gateway Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body {
            background: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .nav-pills .nav-link {
            color: #6c757d;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-pills .nav-link.active {
            background-color: #198754;
            /* Warna hijau WA */
            box-shadow: 0 4px 10px rgba(25, 135, 84, 0.3);
        }

        .tab-pane {
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <h2 class="mb-4 text-center">📱 WA Gateway Dashboard</h2>
        <div class="row">
            <div class="col-md-6">
                <div class="card p-4 mb-4 text-center">
                    <h5>Scan WhatsApp</h5>
                    <div id="qrcode-container" class="my-3">
                        <p id="status-text" class="text-muted">Menunggu koneksi...</p>
                        <img id="qrcode-img" src="" style="display:none; width: 200px; margin: 0 auto;">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card p-4">
                    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="pill"
                                data-bs-target="#single">Single</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#bulk">Bulk (Masal)</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="single">
                            <form id="sendForm">
                                <div class="mb-3">
                                    <label>Nomor Tujuan</label>
                                    <input type="text" id="receiver" class="form-control" placeholder="628xxx">
                                </div>
                                <div class="mb-3">
                                    <label>Pesan</label>
                                    <textarea id="message" class="form-control" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-100">Kirim</button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="bulk">
                            <form id="bulkForm">
                                <div class="mb-3">
                                    <label>Daftar Nomor (Pisahkan dengan baris baru)</label>
                                    <textarea id="bulk_receivers" class="form-control" rows="5" placeholder="62812xxx&#10;62856xxx&#10;62877xxx"></textarea>
                                    <small class="text-muted">Satu nomor per baris.</small>
                                </div>
                                <div class="mb-3">
                                    <label>Pesan Masal</label>
                                    <textarea id="bulk_message" class="form-control" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Kirim Masal</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card p-4">
                    <h5>Log Antrean Pesan</h5>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-hover mt-2">
                            <thead>
                                <tr>
                                    <th>Penerima</th>
                                    <th>Pesan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="messageLog">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script>
        // Logika AJAX akan kita tulis di sini pada tahap selanjutnya
        $(document).ready(function() {
            // 1. Fungsi Kirim Pesan via AJAX
            $('#sendForm').on('submit', function(e) {
                e.preventDefault();

                let data = {
                    receiver: $('#receiver').val(),
                    message: $('#message').val(),
                    _token: "{{ csrf_token() }}" // Laravel butuh ini untuk keamanan
                };

                $.post('/send-message', data, function(response) {
                    alert(response.msg);
                    $('#receiver').val('');
                    $('#message').val('');
                    loadMessages(); // Refresh tabel setelah kirim
                });
            });

            // 2. Fungsi Load Data ke Tabel
            function loadMessages() {
                $.get('/get-messages', function(data) {
                    let rows = '';
                    data.forEach(function(msg) {
                        let badgeClass = 'bg-secondary';
                        if (msg.status == 'sent') badgeClass = 'bg-success';
                        if (msg.status == 'pending') badgeClass = 'bg-warning text-dark';
                        if (msg.status == 'failed') badgeClass = 'bg-danger';
                        if (msg.status == 'sending') badgeClass = 'bg-info text-dark';

                        rows += `<tr>
                    <td>${msg.receiver}</td>
                    <td>${msg.message}</td>
                    <td><span class="badge ${badgeClass} status-badge">${msg.status.toUpperCase()}</span></td>
                </tr>`;
                    });
                    $('#messageLog').html(rows);
                });
            }

            // 3. Auto Refresh Tabel tiap 3 detik
            setInterval(loadMessages, 3000);
            loadMessages(); // Load pertama kali saat halaman dibuka

            $('#bulkForm').on('submit', function(e) {
                e.preventDefault();
                let btn = $(this).find('button');
                btn.prop('disabled', true).text('Processing...');

                $.post('/send-bulk', {
                    receivers: $('#bulk_receivers').val(),
                    message: $('#bulk_message').val(),
                    _token: "{{ csrf_token() }}"
                }, function(response) {
                    alert(response.msg);
                    $('#bulk_receivers').val('');
                    $('#bulk_message').val('');
                    btn.prop('disabled', false).text('Kirim Masal');
                    loadMessages();
                });
            });

            // hubungkan ke nodejs socket server untuk update QR code
            const socket = io('http://localhost:3000');

            // tangkap event QR code dari server
            socket.on('qr_code', function(url) {
                $('#qrcode-img').attr('src', url).show();
                $('#status-text').text('Scan QR Code dengan WhatsApp Anda');
            })

            // tangkap status pesan dari server
            socket.on('message', function(msg) {
                $('#status-text').text(msg); // refresh tabel saat ada update status
            });

            // tangkap status koneksi dari server
            // 3. Tangkap status koneksi (Sesuaikan nama event 'status')
            socket.on('status', function(status) {
                console.log("Status Koneksi:", status);
                if (status == 'connected') {
                    $('#status-text').html('<span class="text-success">✔ WhatsApp terhubung!</span>');
                    $('#qrcode-img').hide();
                } else if (status == 'disconnected') {
                    $('#status-text').text('Koneksi terputus. Silakan scan ulang.');
                    $('#qrcode-img').hide();
                }
            });

        });
    </script>

</body>

</html>
