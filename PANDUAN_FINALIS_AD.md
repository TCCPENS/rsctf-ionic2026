# Panduan Teknis & Mekanisme Finalis Attack-Defense CTF (IONIC 2026)
**Diselenggarakan oleh:** Teknik Komputer & Jaringan, Politeknik Elektronika Negeri Surabaya (PENS)  
**Dokumen untuk:** Finalis Babak Final IONIC 2026  

---

## 1. Deskripsi & Format Kompetisi

Babak Final IONIC 2026 mengadopsi format **Attack & Defense (A&D) berbasis Boot2Root**.

Berbeda dengan format CTF Jeopardy (di mana peserta hanya mencari flag di server panitia):
* Setiap tim akan diberikan **mesin/layanan server sendiri** yang memiliki celah keamanan (*vulnerabilities*).
* Setiap tim memiliki dua tugas utama yang berjalan secara simultan:
  1. **DEFENSE (Bertahan):** Menganalisis kelemahan, memantau log sistem, dan menambal (*patching*) celah keamanan pada mesin server milik tim sendiri agar tidak dibobol tim lawan.
  2. **ATTACK (Menyerang):** Menganalisis celah yang sama pada mesin server milik tim-tim lawan, lalu mengeksploitasinya untuk mencuri flag mereka.
* **Sistem Ronde & Flag Dinamis:**
  * Pertandingan berjalan dalam sistem **Ronde/Tick** (berdurasi 60–90 detik per ronde).
  * Di setiap pergantian ronde, sistem platform akan **menanam flag baru secara otomatis** di seluruh server tim.
  * Flag yang dicuri dari ronde sebelumnya memiliki masa aktif terbatas dan akan kedaluwarsa (*expired*).

---

## 2. Konsep Tantangan Boot2Root (User & Root)

Tantangan di babak final dirancang dengan alur eksploitasi bertingkat (*killchain*) yang dibagi menjadi 2 objektif:

1. **User Foothold Flag:**
   * Diperoleh dengan mengeksploitasi celah pada aplikasi layanan (misal: Web RCE, File Upload Bypass, SQL Injection, Template Injection).
   * Flag dapat dibaca oleh pengguna non-root (`www-data` atau user aplikasi).
2. **Root Escalation Flag:**
   * Diperoleh dengan melakukan *Privilege Escalation* lokal di dalam sistem operasi Linux server hingga memperoleh hak akses `root`.
   * Flag berada di direktori `/root/flag.txt` dan hanya dapat dibaca dengan hak akses `root`.

---

## 3. Skema Penilaian (Scoring System)

Skor total masing-masing tim dihitung berdasarkan 3 pilar:

$$\text{Total Score} = \text{Attack Score} + \text{Defense Score} + \text{SLA Score}$$

1. **Attack Score (40%):**
   * Poin yang diperoleh setiap kali tim berhasil mencuri dan mengirimkan (*submit*) flag milik tim lawan yang valid pada ronde berjalan.
2. **Defense Score (40%):**
   * Poin yang diperoleh jika flag pada server tim Anda **tidak berhasil dicuri** oleh tim lain pada ronde tersebut.
3. **SLA / Uptime Score (20%):**
   * Poin ketersediaan dan fungsionalitas layanan.
   * Bot penguji otomatis (*SLA Checker*) akan memeriksa layanan tim Anda di setiap ronde dari luar.
   * Layanan harus tetap hidup dan fungsi normalnya tidak boleh rusak akibat proses patching pertahanan.
   * **Status Checker SLA:**
     * 🟢 **OK (SLA 100%):** Layanan hidup normal dan seluruh fungsi pengujian lulus.
     * 🟡 **MUMBLE (SLA Penalty):** Layanan merespons, namun fiturnya rusak atau mengembalikan error HTTP 500 / data korup.
     * 🔴 **OFFLINE (SLA 0%):** Layanan mati, port tertutup, connection refused, atau timeout.

---

## 4. Akses ke Server Tim & Dashboard Peserta

### A. Akses Server Tim Sendiri (Untuk Bertahan & Patching)
Untuk memeriksa source code, membaca log, dan menambal celah pada mesin server tim Anda sendiri, peserta mengakses server menggunakan **Remote SSH Bastion (Port 2222)**:

1. Buka menu **A&D Toolkit** di platform web lomba (`https://taniahacker.tccpens.id`).
2. Unggah SSH Public Key Anda (`id_ed25519.pub` atau `id_rsa.pub`), atau klik **Generate Key** untuk mengunduh private key otomatis (`.key`).
3. Lakukan koneksi SSH langsung dari terminal laptop peserta:
   ```bash
   ssh -i /path/ke/private_key -p 2222 <challenge_id>@<IP_SERVER>
   ```
   *(Contoh untuk Challenge 6: `ssh -i ~/.ssh/id_ed25519 -p 2222 6@103.148.19.64`)*.
4. Anda akan langsung masuk sebagai user `root` di dalam kontainer server tim Anda sendiri untuk memeriksa kode dan menerapkan patch. Tim lawan tidak bisa login ke server Anda karena sistem otentikasi kunci kriptografis per-tim.

### B. Fitur Diagnostik & Reset Layanan
* **Checker Diagnostics:** Jika layanan Anda berstatus *Mumble* atau *Offline*, panel tantangan akan menampilkan cuplikan pesan error dari bot checker (misal: `checker exit 1: expected HTTP 200 on /health, got 500`), sehingga Anda dapat mengetahui kesalahan kode saat patching.
* **Reset to Baseline:** Jika server tim Anda mengalami kerusakan fatal atau crash akibat kesalahan konfigurasi, tim dapat menekan tombol **Reset** untuk membangun ulang kontainer ke kondisi awal (catatan: proses reset membutuhkan waktu beberapa detik dan mempengaruhi nilai SLA ronde tersebut).

### C. Pengiriman Flag (Flag Submission)
Peserta dapat mengirimkan flag hasil penyerangan dengan 2 cara:
1. **Melalui Web GUI:** Menggunakan tombol *Submit Flag* di menu status bar (mendukung pengiriman 1 flag atau *Batch Submit* banyak flag sekaligus).
2. **Melalui Script Automasi (API):** Menggunakan API Token tim dan script Python / cURL untuk mengirimkan flag secara otomatis di setiap ronde.

---

## 5. Perlengkapan & Kebutuhan Teknis Peserta (Tools Checklist)

Peserta diwajibkan menyiapkan laptop dengan spesifikasi dan perangkat lunak berikut sebelum hari pelaksanaan lomba:

### A. Sistem Operasi yang Direkomendasikan
* **Linux (Ubuntu / Kali / Debian / Fedora)**, atau
* **Windows dengan WSL2 (Windows Subsystem for Linux)** terinstal.  
*(Penggunaan Linux murni atau WSL2 sangat disarankan untuk kelancaran scripting, networking, dan kompilasi exploit).*

### B. Software & Tools yang Wajib Diinstal
1. **Python 3 & PIP:**
   * Beserta library pendukung automasi penyerangan:
     ```bash
     pip install requests urllib3 pwntools httpx
     ```
2. **Konektivitas Jaringan & Remote Shell:**
   * **OpenSSH Client:** Untuk koneksi SSH ke server tim.
   * **WireGuard VPN Client:** Wajib terinstal di laptop (`wireguard` GUI atau paket `wireguard-tools` CLI) sebagai media penghubung ke subnet kompetisi jika sistem VPN diaktifkan.
   * **Netcat (`nc`) & cURL:** Untuk pengujian koneksi dan payload transfer.
3. **Web Pentest & Intercept Tools:**
   * **Burp Suite (Community/Pro), Caido, atau OWASP ZAP** untuk menganalisis traffic HTTP.
4. **Network & Forensics Analyzer:**
   * **Wireshark / TShark:** Untuk menganalisis file pcap dan mendeteksi payload serangan yang dilempar oleh musuh ke server Anda.
5. **Editor Teks / IDE:**
   * **VS Code, Sublime Text, Vim, atau Nano** untuk editing kode dan pembuatan script secara cepat.
6. **Terminal Multiplexer (Opsional namun Disarankan):**
   * `tmux` atau `terminator` untuk membagi layar terminal menjadi banyak sesi (monitoring log, SSH server, listener netcat, dan running script exploit).

### C. Kebutuhan Fisik (On-Site di Kampus PENS)
* Laptop dengan kondisi baterai sehat beserta charger/adaptor asli.
* Kabel LAN UTP (kategori Cat 5e / Cat 6) dengan panjang minimal 2–3 meter.
* USB-to-LAN / Type-C-to-LAN Adapter (jika laptop tidak memiliki port Ethernet RJ-45 bawaan).
* Stop kontak / kabel ekstensi colokan listrik cadangan per tim.

---

## 6. Rules of Engagement (Aturan Main & Batasan)

Seluruh peserta wajib menjunjung tinggi etika, sportivitas, dan integritas kompetisi.

### ✅ Yang Diperbolehkan (Do's):
1. Menambal celah keamanan (*patching*) pada level kode aplikasi web, logic, database, atau konfigurasi permission Linux di server milik tim sendiri.
2. Mengotomasi proses penyerangan dan pengiriman flag ke semua tim lawan menggunakan script (Python, Bash, cURL).
3. Memantau access log dan network traffic pada server tim sendiri untuk mempelajari teknik serangan tim lawan (*replay attack*).
4. Melakukan reset kontainer milik tim sendiri via dashboard jika terjadi kerusakan layanan yang tidak dapat diperbaiki manual.

### ❌ Yang Dilarang Keras (Don'ts):
1. **Dilarang melakukan serangan DoS/DDoS:** Dilarang melakukan flooding traffic atau request berlebihan dengan sengaja yang bertujuan melumpuhkan platform web scoring, router jaringan, atau switch fisik kompetisi.
2. **Dilarang mematikan layanan atau menutup port secara curang:** Dilarang mematikan service web, menutup port yang dilombakan via `iptables`, atau menghapus file sistem untuk menghindari serangan musuh (akan langsung diganjar penalti SLA 0%).
3. **Dilarang melakukan serangan destruktif permanen pada server lawan:** Dilarang mengeksekusi perintah perusak seperti `rm -rf /*`, forkbomb, merusak kernel, atau menghapus binary bawaan Linux yang membuat server lawan tidak dapat diakses secara permanen.
4. **Dilarang melakukan kerja sama atau aliansi antar-tim:** Dilarang keras membagikan flag, saling bertukar script exploit, atau berkolusi menyerang tim tertentu secara bersama-sama.
5. **Dilarang menyerang infrastruktur panitia:** Segala bentuk serangan ke IP platform web scoring, server database, atau router panitia di luar IP target tantangan yang ditentukan adalah pelanggaran berat yang berakibat **Diskualifikasi Langsung**.
