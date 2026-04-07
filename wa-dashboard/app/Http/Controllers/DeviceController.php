<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    // Menampilkan daftar semua WA milik user yang sedang login
    public function index()
    {
        $devices = Auth::user()->devices()->latest()->get();
        return view('devices.index', compact('devices'));
    }

    // Form tambah nomor WA baru
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:50']);

        Auth::user()->devices()->create([
            'name' => $request->name,
            'status' => 'disconnected'
        ]);

        return back()->with('success', 'Device berhasil ditambahkan!');
    }

    // Halaman Scan QR untuk Device spesifik
    public function show(Device $device)
    {
        // Security check: Pastikan user hanya bisa buka device miliknya
        if ($device->user_id !== Auth::id()) abort(403);

        return view('devices.show', compact('device'));
    }

    public function logout(Device $device)
    {
        if ($device->user_id !== Auth::id()) abort(403);

        // Ubah status di DB jadi disconnected
        // Node.js akan memantau perubahan ini atau kita picu via Socket
        $device->update(['status' => 'disconnected']);

        return back()->with('success', 'Perintah Logout dikirim ke Engine.');
    }

    // 2. Fungsi Delete (Hanya boleh jika status sudah disconnected)
    public function destroy(Device $device)
    {
        if ($device->user_id !== Auth::id()) abort(403);

        if ($device->status === 'connected') {
            return back()->with('error', 'Logout dulu sebelum menghapus device!');
        }

        $device->delete();
        return redirect()->route('devices.index')->with('success', 'Device berhasil dihapus.');
    }
}
