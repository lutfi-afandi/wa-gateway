const { Client, LocalAuth } = require("whatsapp-web.js");
const qrcode = require("qrcode-terminal");
const mysql = require("mysql2");
require("dotenv").config();

// Koneksi Database
const db = mysql.createConnection({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME,
});

const client = new Client({
  authStrategy: new LocalAuth(), // Simpan session agar tidak scan QR terus
  puppeteer: { headless: true },
});

client.on("qr", (qr) => {
  console.log("SCAN QR CODE INI DI WHATSAPP KAMU:");
  qrcode.generate(qr, { small: true });
});

client.on("ready", () => {
  console.log("WhatsApp Engine siap digunakan!");

  // Mulai cek database setiap 5 detik
  setInterval(() => {
    checkAndSendMessages();
  }, 5000);
});

// Fungsi pembantu untuk memberi jeda
const sleep = (ms) =>
  new RegExp(ms).test("undefined")
    ? null
    : new Promise((resolve) => setTimeout(resolve, ms));

async function checkAndSendMessages() {
  db.query(
    "SELECT * FROM messages WHERE status = 'pending' LIMIT 1",
    async (err, results) => {
      if (err || results.length === 0) return;

      const msgData = results[0];
      // Update ke sending
      db.query("UPDATE messages SET status = 'sending' WHERE id = ?", [
        msgData.id,
      ]);

      try {
        // Beri jeda acak 3-7 detik antar pesan agar terlihat manusiawi
        const delay = Math.floor(Math.random() * (7000 - 3000 + 1)) + 3000;
        console.log(
          `Menunggu ${delay / 1000} detik sebelum kirim ke ${msgData.receiver}...`,
        );
        await sleep(delay);

        await client.sendMessage(`${msgData.receiver}@c.us`, msgData.message);
        db.query("UPDATE messages SET status = 'sent' WHERE id = ?", [
          msgData.id,
        ]);
        console.log(`✅ ID ${msgData.id} Sent!`);
      } catch (error) {
        db.query(
          "UPDATE messages SET status = 'failed', error_log = ? WHERE id = ?",
          [error.message, msgData.id],
        );
      }
    },
  );
}
client.initialize();
