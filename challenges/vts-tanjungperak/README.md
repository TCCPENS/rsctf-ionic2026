# VTS Tanjung Perak: Selat Madura Gate (A&D Boot2Root Challenge)

Tantangan Attack-Defense resmi untuk **Final IONIC 2026** (Teknik Komputer & Jaringan, PENS).

---

## 1. Ringkasan Skenario
Stasiun Pemantauan Lalu Lintas Kapal (*Vessel Traffic Service* / VTS) Tanjung Perak bertugas memantau alur pelayaran kapal peti kemas di Alur Pelayaran Barat Surabaya (APBS) dan Selat Madura. Web internal portal pelabuhan terekspos dengan beberapa celah keamanan berjenjang dari web foothold hingga root.

* **Kategori:** Web / Boot2Root
* **Port Layanan:** `8080` (HTTP)
* **Kebutuhan RAM:** ~50 MB (Sangat aman untuk Kata MicroVM)
* **Pengguna Sistem:**
  * `www-data`: User web server Apache & PHP.
  * `vts_operator`: Petugas menara kontrol pantai (Pass: `JangkarPerak2026!Madura`).
  * `root`: Administrator sistem pelayaran.

---

## 2. Rantai Eksploitasi (Killchain)

```text
[ Attacker ]
     │
     ▼  1. LFI / Stream Wrapper pada /manifest_logs.php
Membaca /opt/vts/conf/ais_station.conf
     │
     ▼  2. Ekstraksi Kredensial vts_operator
Menemukan username: vts_operator, password: JangkarPerak2026!Madura
     │
     ▼  3. Login ke /console.php & Eksekusi Perintah
Eksekusi command via console -> Cat /home/vts_operator/user.txt -> [USER FLAG]
     │
     ▼  4. Privilege Escalation via Cronjob Root
Injeksi payload ke /opt/vts/scripts/tide_depth_sync.sh (milik root:vts_operator, 775)
Tunggu cronjob root berputar (maks 60s) -> Cat /root/flag.txt -> [ROOT FLAG PWNED]
```

---

## 3. Titik Penambalan Pertahanan (Defense & Patching)

Peserta dapat mempertahankan servernya dengan melakukan penambalan di beberapa titik:
1. **Patching Web LFI (`/var/www/html/manifest_logs.php`):**
   * Gunakan `basename()` dan `realpath()` untuk mengunci pembacaan berkas hanya di folder `./logs/`.
   * Hapus *fallback* `file_get_contents($selected_source)`.
2. **Patching Kredensial Stasiun:**
   * Amankan izin berkas: `chmod 600 /opt/vts/conf/ais_station.conf`.
   * Ganti kata sandi `vts_operator` di berkas konfigurasi.
3. **Patching Privilege Escalation Linux:**
   * Amankan kepemilikan skrip sinkronisasi pasang surut:
     ```bash
     chown root:root /opt/vts/scripts/tide_depth_sync.sh
     chmod 755 /opt/vts/scripts/tide_depth_sync.sh
     ```

---

## 4. Struktur Direktori Soal

```text
chall-vts-tanjungperak/
├── challenge.yaml              # Konfigurasi manifest RSCTF
├── README.md                   # Dokumen panduan ini
├── checker/                    # Bot SLA Checker
│   ├── lib.py                  # Library helper RSCTF
│   └── run.py                  # Logika pengujian kesehatan layanan & web
├── solution/                   # Script exploit & patch
│   ├── exploit.py              # Full automated solver (User & Root)
│   └── patch.diff              # Solusi perbaikan
└── src/                        # Kontainer Soal
    ├── Dockerfile              # Manifest build Debian Bookworm
    ├── entrypoint.sh           # Script startup & sinkronisasi flag dinamis
    ├── conf/
    │   └── ais_station.conf    # Berkas konfigurasi stasiun radio VTS
    ├── scripts/
    │   └── tide_depth_sync.sh  # Skrip cronjob kalibrasi sensor (vulnerable 775)
    └── html/                   # Aplikasi Web VTS
        ├── index.php           # Dashboard radar & pergerakan kapal
        ├── manifest_logs.php   # Modul audit log (LFI vulnerable)
        ├── console.php         # Konsol operator telemetri (Command execution)
        ├── health.php          # SLA Healthcheck endpoint
        ├── db.php              # Inisialisasi database SQLite kapal
        ├── navbar.php          # Header & navigasi maritim
        ├── footer.php          # Footer MARSEC
        ├── assets/
        │   └── style.css       # Tema maritim modern
        └── logs/               # Arsip log manifest
```
