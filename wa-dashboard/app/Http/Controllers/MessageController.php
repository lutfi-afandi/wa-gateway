<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'receiver' => 'required',
            'message' => 'required'
        ]);

        // Simpan ke database (otomatis statusnya 'pending')
        Message::create([
            'receiver' => $request->receiver,
            'message' => $request->message,
            'status' => 'pending'
        ]);

        return response()->json(['status' => 'success', 'msg' => 'Pesan masuk antrean!']);
    }

    public function getMessages()
    {
        // Ambil 10 pesan terbaru
        $messages = Message::orderBy('id', 'desc')->limit(10)->get();
        return response()->json($messages);
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'receivers' => 'required',
            'message' => 'required'
        ]);

        // Pecah input berdasarkan baris baru (\n)
        $numbers = explode("\n", str_replace("\r", "", $request->receivers));
        $count = 0;

        foreach ($numbers as $num) {
            $num = trim($num); // Bersihkan spasi
            if (!empty($num)) {
                Message::create([
                    'receiver' => $num,
                    'message' => $request->message,
                    'status' => 'pending'
                ]);
                $count++;
            }
        }

        return response()->json(['status' => 'success', 'msg' => "$count pesan berhasil diantrekan!"]);
    }
}
