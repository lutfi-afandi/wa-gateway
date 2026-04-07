const { Client, LocalAuth } = require("whatsapp-web.js");
const QRCode = require("qrcode"); // GUNAKAN INI, bukan qrcode-terminal
const mysql = require("mysql");
const express = require("express");
const http = require("http");
const { Server } = require("socket.io");

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
  cors: { origin: "*" },
});

const db = mysql.createConnection({
  host: "localhost",
  user: "root",
  password: "",
  database: "wa_gateway", // Pastikan nama database sudah benar
});

db.connect((err) => {
  if (err) {
    console.error("Gagal koneksi ke MySQL:", err);
    return;
  }
  console.log("MySQL Terhubung!");
});

// Inisiasi WhatsApp Client
const client = new Client({
  authStrategy: new LocalAuth(),
  puppeteer: {
    headless: true, // Set true agar berjalan di background
    args: ["--no-sandbox", "--disable-setuid-sandbox"],
  },
});

// VARIABEL PENYIMPAN QR TERAKHIR
let lastQR = "";

// PINDAHKAN EVENT LISTENER KE LUAR io.on
client.on("qr", (qr) => {
  console.log("QR Diterima, mengonversi ke Base64...");
  QRCode.toDataURL(qr, (err, url) => {
    lastQR = url; // Simpan QR terakhir
    io.emit("qr_code", url);
    io.emit("message", "QR Code diterima, silakan scan!");
  });
});

let isReady = false; // Variabel penanda
client.on("ready", () => {
  console.log("WhatsApp is Ready!");
  isReady = true;
  lastQR = ""; // Hapus cache QR karena sudah login
  io.emit("message", "WhatsApp sudah terhubung!");
  io.emit("status", "connected");
});

client.on("authenticated", () => {
  console.log("Authenticated!");
});

client.on("disconnected", () => {
  isReady = false; // Reset status
  io.emit("message", "WhatsApp terputus, silakan scan ulang!");
  io.emit("status", "disconnected");
});

// SOCKET IO CONNECTION
io.on("connection", (socket) => {
  console.log("Dashboard terhubung ke socket");

  // JIKA SUDAH ADA QR, LANGSUNG KIRIM KE USER YANG BARU CONNECT
  if (lastQR) {
    socket.emit("qr_code", lastQR);
    socket.emit("message", "Silakan scan QR Code yang tersedia");
  }
});

// Jalankan client & server
client.initialize();
server.listen(3000, () => {
  console.log("Server berjalan di http://localhost:3000");
});

// Fungsi untuk mengecek antrean di database
const checkAndSend = () => {
  // CEK DISINI: Jika belum ready, jangan lakukan apa-apa
  if (!isReady) {
    console.log("Menunggu WhatsApp Ready sebelum cek antrean...");
    return;
  }

  // Cari pesan yang statusnya 'pending'
  db.query(
    "SELECT * FROM messages WHERE status = 'pending' LIMIT 5",
    (err, results) => {
      if (err) throw err;

      results.forEach((data) => {
        const number = data.receiver + "@c.us"; // Format WhatsApp
        const message = data.message;

        console.log(`Mengirim pesan ke ${data.receiver}...`);

        // Ubah status jadi 'sending' agar tidak terkirim ganda
        db.query("UPDATE messages SET status = 'sending' WHERE id = ?", [
          data.id,
        ]);

        client
          .sendMessage(number, message)
          .then((response) => {
            // Jika berhasil terkirim
            db.query("UPDATE messages SET status = 'sent' WHERE id = ?", [
              data.id,
            ]);
            console.log(`Pesan ke ${data.receiver} BERHASIL.`);
          })
          .catch((err) => {
            // Jika gagal
            db.query("UPDATE messages SET status = 'failed' WHERE id = ?", [
              data.id,
            ]);
            console.log(`Pesan ke ${data.receiver} GAGAL:`, err);
          });
      });
    },
  );
};

// Jalankan pengecekan setiap 5 detik
setInterval(checkAndSend, 5000);
