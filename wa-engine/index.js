const { Client, LocalAuth } = require("whatsapp-web.js");
const QRCode = require("qrcode");
const mysql = require("mysql");
const express = require("express");
const http = require("http");
const { Server } = require("socket.io");

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

// 1. RAK KUNCI (Session Storage)
// Kita simpan semua instance client WA di sini agar tidak hilang
const sessions = new Map();

// 2. KONEKSI DATABASE
const db = mysql.createConnection({
  host: "localhost",
  user: "root",
  password: "",
  database: "wa_gateway",
});

db.connect((err) => {
  if (err) throw err;
  console.log("Database Connected!");
  // Saat server Node.js nyala, otomatis hidupkan semua WA yang statusnya 'connected'
  initAllDevices();
});

/**
 * FUNGSI: Menjalankan robot WA berdasarkan Device ID
 * @param {string} deviceId - ID dari tabel devices
 */
const initDevice = (deviceId) => {
  console.log(`Memulai inisialisasi Device ID: ${deviceId}`);

  // Kita buat instance baru khusus untuk device ini
  const client = new Client({
    // Folder auth dibedakan per device: .wwebjs_auth/session-device_1
    authStrategy: new LocalAuth({ clientId: `device_${deviceId}` }),
    puppeteer: {
      headless: true,
      args: ["--no-sandbox", "--disable-setuid-sandbox"],
    },
  });

  // Simpan ke rak kunci
  sessions.set(deviceId, { client: client, ready: false });

  client.on("qr", (qr) => {
    QRCode.toDataURL(qr, (err, url) => {
      // Kirim QR hanya ke 'Room' milik device ini agar tidak tertukar
      io.to(`device_${deviceId}`).emit("qr_code", url);
    });
  });

  client.on("ready", () => {
    console.log(`Device ${deviceId} is Ready!`);
    // Update status di Map
    const session = sessions.get(deviceId);
    if (session) session.ready = true;

    const info = client.info;
    const connectedNumber = info.wid.user; // Ambil nomor WA-nya

    // Update status di Database
    db.query(
      "UPDATE devices SET status = 'connected', number = ? WHERE id = ?",
      [connectedNumber, deviceId],
    );

    io.to(`device_${deviceId}`).emit("status", "connected");
  });

  client.on("disconnected", () => {
    console.log(`Device ${deviceId} Disconnected!`);
    sessions.delete(deviceId);
    db.query("UPDATE devices SET status = 'disconnected' WHERE id = ?", [
      deviceId,
    ]);
    io.to(`device_${deviceId}`).emit("status", "disconnected");
  });

  client.initialize();
};

/**
 * FUNGSI: Menghidupkan ulang semua session yang aktif di DB
 */
const initAllDevices = () => {
  db.query(
    "SELECT id FROM devices WHERE status = 'connected'",
    (err, results) => {
      if (err) return;
      results.forEach((row) => initDevice(row.id.toString()));
    },
  );
};

// 3. SOCKET LOGIC: Pemisahan Kamar (Rooms)
io.on("connection", (socket) => {
  const deviceId = socket.handshake.query.deviceId;

  if (deviceId) {
    // User masuk ke kamar khusus device miliknya
    socket.join(`device_${deviceId}`);
    console.log(`User memantau Device: ${deviceId}`);

    // Jika device belum ada di memori, kita buatkan instance-nya
    if (!sessions.has(deviceId)) {
      initDevice(deviceId);
    }
  }
});

/**
 * FUNGSI: Patroli Pesan (The Queue Consumer)
 */
const checkAndSend = () => {
  // Kita ambil pesan yang pending
  db.query(
    "SELECT * FROM messages WHERE status = 'pending' LIMIT 10",
    (err, results) => {
      if (err) return;

      results.forEach((msg) => {
        const session = sessions.get(msg.device_id.toString());

        // Validasi: Apakah robot pengirimnya sudah Ready?
        if (session && session.ready) {
          // Update ke processing agar tidak diambil robot lain
          db.query("UPDATE messages SET status = 'processing' WHERE id = ?", [
            msg.id,
          ]);

          session.client
            .sendMessage(`${msg.receiver}@c.us`, msg.message)
            .then(() => {
              db.query("UPDATE messages SET status = 'sent' WHERE id = ?", [
                msg.id,
              ]);
            })
            .catch((err) => {
              db.query(
                "UPDATE messages SET status = 'failed', error_log = ? WHERE id = ?",
                [err.message, msg.id],
              );
            });
        }
      });
    },
  );
};

setInterval(checkAndSend, 5000);

const checkLogoutCommand = () => {
  // Cari device yang di memori (Map) berstatus aktif, tapi di DB diminta 'disconnected'
  db.query(
    "SELECT id FROM devices WHERE status = 'disconnected'",
    (err, results) => {
      if (err) return;

      results.forEach((row) => {
        const session = sessions.get(row.id.toString());
        if (session) {
          console.log(
            `Mematikan Engine Device ${row.id} karena permintaan Logout...`,
          );
          session.client.destroy(); // Matikan Puppeteer
          sessions.delete(row.id.toString()); // Hapus dari Map RAM
        }
      });
    },
  );
};

// Jalankan patroli logout setiap 5 detik
setInterval(checkLogoutCommand, 5000);

server.listen(3000, () => console.log("Engine running on port 3000"));
