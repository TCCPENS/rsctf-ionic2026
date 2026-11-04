# Panduan Lengkap Authoring Challenge Attack-Defense (A&D Boot2Root)
**Target Platform:** RSCTF / GZCTF A&D Engine  
**Panduan Khusus:** Problem Setter (Probset) & Author Challenge

---

## 1. Konsep Dasar Attack-Defense (Wajib Dipahami Probset)

Berbeda dengan CTF Jeopardy biasa (di mana ada 1 flag statis yang dimasukkan manual di web):
1. **Tidak Ada Flag Statis**: Flag di-generate otomatis oleh platform per-tim, per-ronde (setiap tick, misal 60 detik).
2. **Flag Injection**: Engine platform akan menimpa file flag di dalam container (default di path `/flag` atau via environment variable `$RSCTF_FLAG_FILE`).
3. **Mekanisme SLA Checker**: Setiap soal **wajib memiliki script checker**. Checker bertindak sebagai bot penguji yang memverifikasi apakah website/layanan tim masih hidup dan apakah flag ronde tersebut valid.
4. **Prinsip Boot2Root di Container**:
   - Web service berjalan sebagai user non-privilege (`www-data` atau `player`).
   - Celah Privilege Escalation disediakan di level OS Linux container (misal: SUID binary, sudoers permisif, cron root, capabilities).
   - Flag root `/root/flag.txt` hanya boleh dibaca oleh `root`.

---

---

## 2. Format Soal Boot2Root: Solusi 2 Kontainer Terpisah (Sangat Direkomendasikan)

Dalam kompetisi Attack-Defense tingkat final, untuk menyajikan alur Boot2Root (Foothold $\rightarrow$ Privilege Escalation) secara adil, stabil, dan transparan, **sangat direkomendasikan untuk memecah 1 skenario soal menjadi 2 challenge mandiri**:

```text
Skenario Soal (Contoh: CyberTech Portal)
├── 1. CyberTech - Foothold (User Flag)      -> Target: Celah Web/RCE (User www-data/budi)
└── 2. CyberTech - Escalation (Root Flag)    -> Target: Full Killchain Web -> Root Linux
```

### Mengapa Format 2 Kontainer Lebih Direkomendasikan?
1. **100% Native RSCTF Engine**:
   - Engine RSCTF menginjeksi flag dinamis secara independen ke masing-masing kontainer per ronde.
   - Tidak memerlukan script supervisor HMAC yang rumit untuk memverifikasi root flag secara tersembunyi.
2. **Scoring Adil & Berjenjang**:
   - **Tim Pemula**: Yang baru berhasil mengeksploitasi bug web sudah bisa memanen poin dari **Challenge User**.
   - **Tim Mahir**: Yang berhasil menuntaskan rantai eksploitasi hingga *privilege escalation* lokal Linux akan memanen poin ganda dari **Challenge User + Challenge Root**.
3. **Dinamika Pertahanan (Defense) Lebih Menarik**:
   - Di **Kontainer User**: Defender fokus mem-patch kode aplikasi web (sanitasi input, upload bypass, SQLi).
   - Di **Kontainer Root**: Defender memiliki dua lapis pertahanan: jika kode web-nya terlambat di-patch, mereka masih bisa bertahan dan selamat jika berhasil menambal izin SUID, konfigurasi `sudoers`, atau celah biner lokal Linux!

---

### Cara Mengemas 2 Paket Challenge Tersebut:

#### Paket A: `[Nama]-User.zip` (Tantangan Foothold)
* `challenge.yaml`:
  ```yaml
  name: "CyberTech - Foothold (User)"
  category: Web
  type: AttackDefense
  container:
    memoryLimit: 256
    exposePort: 8080
  ad:
    allowEgress: false
    allowSelfReset: true
    selfHosted: false
  ```
* **Peletakan Flag**: Flag dari platform ditaruh di `/flag` (dapat dibaca oleh user aplikasi web, misal `www-data` atau `budi`).
* **SLA Checker**: Cukup memeriksa apakah halaman web utama aktif (`HTTP 200`) dan flag user terbaca di web.

#### Paket B: `[Nama]-Root.zip` (Tantangan Root Escalation)
* `challenge.yaml`:
  ```yaml
  name: "CyberTech - Escalation (Root)"
  category: Web
  type: AttackDefense
  container:
    memoryLimit: 256
    exposePort: 8080
  ad:
    allowEgress: false
    allowSelfReset: true
    selfHosted: false
  ```
* **Aplikasi**: Aplikasi web sama persis dengan Paket A, tetapi celah privesc di Linux aktif.
* **Peletakan Flag**: Platform menaruh flag di `/root/flag.txt` dengan izin ketat:
  ```bash
  chmod 600 /root/flag.txt
  chown root:root /root/flag.txt
  ```
* **Objektif Peserta**: Peserta mengeksploitasi web untuk mendapatkan foothold, lalu memicu eksploit privesc Linux (SUID / Sudo / Kernel) untuk membaca `/root/flag.txt`.

---

## 3. Aturan Jaringan & Keamanan Penting (Wajib Dipahami Probset)

1. **Eksploitasi Berbasis RCE yang Fleksibel (Web Command Execution & Reverse Shell)**:
   * Celah foothold yang dibuat probset harus merupakan **Remote Code Execution (RCE)** umum (seperti file upload, command injection, SSTI, deserialization).
   * **Rekomendasi Utama untuk Peserta**: Alur pengambilan flag sebaiknya dapat dieksekusi melalui **Web Command Execution / Webshell HTTP** (payload dikirim via request dan flag kembali di response body), karena peserta A&D butuh melakukan automasi penyerangan serentak ke 15 tim lawan setiap ronde (tiap 60 detik).
   * Jika jaringan kompetisi di lokasi (LAN lokal on-site) mengizinkan koneksi balik, peserta tetap bebas menggunakan **Reverse Shell** interaktif ke terminal netcat laptop mereka saat tahap eksplorasi awal.
   * *Catatan Probset:* Jangan membuat soal yang memiliki ketergantungan wajib (*hard-coded dependency*) ke server pihak ketiga di internet publik, karena kontainer lomba selalu diisolasi dari internet luar.
2. **Port 22 Ditutup untuk Penyerang (Attacker)**:
   * Attacker hanya memiliki akses jaringan ke port yang di-expose di `challenge.yaml` (default: port `8080`).
   * Port 22 SSH tidak dibuka untuk tim lawan guna mencegah sabotase password (`passwd budi`) atau penguncian akun antar peserta.
3. **Defender Mengakses Mesin via Bastion 2222 atau Web Shell**:
   * Defender (pemilik kontainer) mengakses mesinnya sendiri menggunakan SSH Key melalui bastion port 2222 atau tombol **`[ >_ Web Shell ]`** langsung di web browser untuk melakukan patching.

---

## 4. Struktur Standar Direktori Challenge

Setiap challenge yang akan di-upload ke platform **wajib di-pack dalam format `.zip`** dengan hierarki folder berikut:

```text
nama-challenge/
├── challenge.yaml          <-- Manifest spesifikasi soal (Wajib)
├── README.md               # Dokumentasi alur soal & writeup panitia
├── src/                    <-- Direktori Container Mesin Soal (Wajib)
│   ├── Dockerfile          # Definisi image OS & environment soal
│   ├── supervisor.py       # Daemon root (sync flag & proof socket)
│   └── (source code web/binari soal Anda)
├── checker/                <-- Direktori Bot SLA Checker (Wajib)
│   ├── lib.py              # Library standar checker RSCTF (wajib ada)
│   └── run.py              # Script python logika pengecekan
└── solution/               # Script pengujian exploit & patch referensi
    ├── exploit.py
    └── patch.diff
```

---

## 5. Menulis File `challenge.yaml`

File ini adalah konfigurasi utama yang dibaca platform saat file `.zip` di-upload.

Contoh template resmi:
```yaml
name: "Judul Challenge Anda"
author: "Nama Author / Tim Probset"
description: |
  ## Deskripsi Tantangan
  Jelaskan skenario soal di sini (misal: Portal Web Karyawan).

  ## Alur Boot2Root:
  1. Dapatkan RCE via celah upload/injeksi sebagai user biasa.
  2. Enumerasi sistem & lakukan privilege escalation ke root.
  3. Ambil flag di `/root/flag.txt`.

  ## Catatan Peserta:
  - Dilarang mematikan port service atau menghapus file health check.
  - Patching wajib dilakukan pada level kode/izin file.
type: AttackDefense
category: Web           # Kategori: Web, Pwn, Crypto, Reverse, Misc
disableBloodBonus: true
container:
  memoryLimit: 256      # Batas RAM dalam MB (rekomendasi: 256 - 512 MB)
  cpuCount: 1           # Jatah CPU core
  exposePort: 8080      # Port utama yang dibuka ke peserta & lawan
ad:
  allowEgress: false    # Container tidak boleh akses internet luar (anti reverse-shell ke luar)
  allowSelfReset: true  # Peserta boleh reset container ke baseline jika rusak
  selfHosted: false     # Di-host otomatis oleh platform Docker
```

---

## 6. Konfigurasi Container (`src/Dockerfile`)

Probset bebas menggunakan base image Linux apa saja (Debian, Ubuntu, Alpine, Python, PHP, dll).

### Template `Dockerfile` Boot2Root Standar:
```dockerfile
FROM debian:bookworm-slim

# 1. Install paket yang dibutuhkan aplikasi & tools pendukung
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2 \
    php \
    python3 \
    gcc \
    libc6-dev \
    curl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# 2. Buat user biasa untuk foothold (misal: budi atau player)
RUN useradd -m -s /bin/bash budi \
    && echo "budi:PasswordUserBiasa123!" | chpasswd

# 3. Setup Port Layanan (Standar A&D: 8080)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# 4. Pasang Vektor Privilege Escalation (Contoh: SUID Binary)
COPY privesc_tool.c /tmp/privesc_tool.c
RUN gcc -O2 /tmp/privesc_tool.c -o /usr/local/bin/privesc_tool \
    && rm /tmp/privesc_tool.c \
    && chown root:root /usr/local/bin/privesc_tool \
    && chmod 4755 /usr/local/bin/privesc_tool

# 5. Salin Source Code Web / Aplikasi
COPY html/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 6. Salin & Setup Supervisor Script (Entrypoint Root)
COPY supervisor.py /app/supervisor.py
RUN chmod +x /app/supervisor.py

EXPOSE 8080
WORKDIR /app
CMD ["python3", "/app/supervisor.py"]
```

---

## 7. Opsi Alternatif: 1 Kontainer dengan Supervisor Daemon (`src/supervisor.py`)

Di A&D, platform akan menulis flag ronde ke `/flag`.  
Agar konsep Boot2Root bekerja (user biasa tidak bisa baca `/flag` secara ilegal, dan checker SLA tetap bisa memverifikasi integritas root tanpa merusak flag), **Gunakan template `supervisor.py` berikut**:

```python
#!/usr/bin/env python3
import hashlib
import hmac
import os
import socketserver
import subprocess
import sys
import threading
import time

ROOT_FLAG_DEST = "/root/flag.txt"
USER_FLAG_DEST = "/home/budi/user.txt"
SOCK_PATH = "/run/portal/root-proof.sock"

def get_current_raw_flag():
    flag_path = os.environ.get("RSCTF_FLAG_FILE", "/flag")
    if os.path.exists(flag_path):
        try:
            content = open(flag_path, "rb").read().strip()
            if content:
                return content
        except OSError:
            pass
    env_flag = os.environ.get("RSCTF_FLAG")
    if env_flag:
        return env_flag.strip().encode()
    return b"flag{default_offline_boot2root_flag}"

def sync_flags():
    """Sinkronisasi flag ronde setiap detik"""
    try:
        content = get_current_raw_flag()
        
        # 1. Root Flag: Hanya bisa dibaca oleh root (chmod 600)
        with open(ROOT_FLAG_DEST, "wb") as f:
            f.write(content + b"\n")
        os.chmod(ROOT_FLAG_DEST, 0o600)
        
        # 2. User Flag: Diberikan ke user biasa (budi)
        user_flag = b"flag{user_" + hashlib.sha256(content + b":user").hexdigest()[:20].encode() + b"}\n"
        with open(USER_FLAG_DEST, "wb") as f:
            f.write(user_flag)
        
        import pwd
        budi_uid = pwd.getpwnam("budi").pw_uid
        budi_gid = pwd.getpwnam("budi").pw_gid
        os.chown(USER_FLAG_DEST, budi_uid, budi_gid)
        os.chmod(USER_FLAG_DEST, 0o600)
    except Exception as e:
        sys.stderr.write(f"Flag sync error: {e}\n")

class ProofHandler(socketserver.StreamRequestHandler):
    """Merespons checker untuk membuktikan file /root/flag.txt tidak disabotase"""
    def handle(self):
        nonce = self.rfile.readline(80).strip()
        if not nonce or len(nonce) > 64:
            return
        flag = get_current_raw_flag()
        proof = hmac.new(flag, nonce, hashlib.sha256).hexdigest()
        self.wfile.write(proof.encode() + b"\n")

class ThreadedUnixServer(socketserver.ThreadingUnixStreamServer):
    allow_reuse_address = True

def run_proof_server():
    os.makedirs("/run/portal", exist_ok=True)
    try:
        os.unlink(SOCK_PATH)
    except FileNotFoundError:
        pass
    server = ThreadedUnixServer(SOCK_PATH, ProofHandler)
    os.chmod(SOCK_PATH, 0o666)
    server.serve_forever()

def flag_watcher_loop():
    while True:
        sync_flags()
        time.sleep(3)

if __name__ == "__main__":
    sync_flags()
    threading.Thread(target=run_proof_server, daemon=True).start()
    threading.Thread(target=flag_watcher_loop, daemon=True).start()

    # Jalankan service utama Anda (misal Apache atau Python Web)
    proc = subprocess.Popen(["apache2ctl", "-D", "FOREGROUND"])
    sys.exit(proc.wait())
```

---

## 8. Menyediakan Endpoint SLA di Web

Aplikasi web Anda **wajib menyediakan 2 endpoint khusus** untuk bot checker:

### 1. `/health.php` (Pengecekan Uptime Service)
```php
<?php
// Wajib return status HTTP 200 dan teks 'ok'
header('Content-Type: text/plain');
http_response_code(200);
echo "ok\n";
```

### 2. `/proof.php?nonce=...` (Verifikasi Integritas Root)
Endpoint ini bertugas menghubungi socket supervisor internal untuk membuktikan file `/root/flag.txt` masih asli dan service root tidak dimatikan peserta:
```php
<?php
$nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';
if (empty($nonce) || strlen($nonce) > 64) {
    http_response_code(400);
    die("nonce required\n");
}

$sockPath = '/run/portal/root-proof.sock';
$fp = @stream_socket_client("unix://" . $sockPath, $errno, $errstr, 3);
if (!$fp) {
    http_response_code(500);
    die("proof socket offline\n");
}

fwrite($fp, $nonce . "\n");
$proof = trim(fgets($fp, 128));
fclose($fp);

header('Content-Type: text/plain');
echo $proof . "\n";
```

---

## 9. Menulis Script Checker (`checker/run.py`)

Script ini dijalankan otomatis oleh platform setiap ronde untuk menguji container setiap tim.

```python
"""
SLA Checker Script untuk RSCTF
"""
import hashlib
import hmac
import http.client
import secrets
from lib import AdContext, Mumble, Offline, checker, run_ad_checker

def http_get(context: AdContext, path: str) -> tuple[int, str]:
    try:
        conn = http.client.HTTPConnection(context.target_ip, context.target_port, timeout=4)
        conn.request("GET", path, headers={"Connection": "close"})
        res = conn.getresponse()
        body = res.read(4096).decode("utf-8", errors="ignore").strip()
        conn.close()
        return res.status, body
    except (OSError, TimeoutError) as e:
        # Jika koneksi ditolak / port ditutup -> OFFLINE (SLA 0)
        raise Offline(f"Service target mati pada {path}") from e

@checker
def check_service_health(context: AdContext) -> None:
    status, body = http_get(context, "/health.php")
    if status != 200:
        raise Mumble(f"Endpoint health.php mengembalikan HTTP {status}")
    if "ok" not in body.lower():
        raise Mumble("Isi respons health.php tidak valid")

@checker
def check_web_ui(context: AdContext) -> None:
    status, body = http_get(context, "/index.php")
    if status != 200:
        raise Mumble(f"Halaman web index.php rusak (HTTP {status})")

@checker
def check_flag_integrity(context: AdContext) -> None:
    nonce = secrets.token_hex(16)
    expected_proof = hmac.new(context.flag.encode(), nonce.encode(), hashlib.sha256).hexdigest()
    
    status, proof = http_get(context, f"/proof.php?nonce={nonce}")
    if status != 200:
        raise Mumble(f"Endpoint proof.php error (HTTP {status})")
    if not hmac.compare_digest(proof.strip(), expected_proof):
        raise Mumble("Proof flag tidak cocok! Peserta mengubah atau merusak file flag.")

if __name__ == "__main__":
    raise SystemExit(run_ad_checker())
```
*(Catatan: Jangan ubah file `checker/lib.py`, gunakan file template bawaan).*

---

## 10. Checklist Pengujian Mandiri Sebelum Dikirim ke Panitia

Sebelum membuat file `.zip`, pastikan probset menguji hal berikut:
1. **Docker Build Sukses**:
   ```bash
   cd src/
   docker build -t test-chall .
   ```
2. **Container Jalan & Flag Sinkron**:
   ```bash
   docker run -d --name test -p 8080:8080 -e RSCTF_FLAG=flag{test123} test-chall
   docker exec test cat /root/flag.txt
   # Output harus: flag{test123}
   ```
3. **Uji Script Checker**:
   ```bash
   export RSCTF_TARGET_IP=127.0.0.1
   export RSCTF_TARGET_PORT=8080
   export RSCTF_ROUND_NUMBER=1
   export RSCTF_CHALLENGE_ID=1
   export RSCTF_TEAM_ID=1
   export RSCTF_FLAG="flag{test123}"
   python3 checker/run.py
   # Output exit code harus: 0 (OK)
   ```
4. **Uji Exploit**:
   Jalankan script penyerangan dari luar container, pastikan bisa mendapatkan flag root.
5. **Pack ZIP**:
   ```bash
   zip -r my-challenge.zip challenge.yaml src/ checker/ README.md
   ```

File `.zip` ini siap di-import langsung melalui tombol **`Import Challenge (.zip)`** di panel admin RSCTF.
