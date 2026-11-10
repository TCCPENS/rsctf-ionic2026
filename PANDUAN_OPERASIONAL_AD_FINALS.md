# Panduan Lengkap Operasional Attack-Defense: Deployment Challenge, Integrasi RSCTF, Ticking Flag & Sinkronisasi SSH Key (IONIC 2026)

Dokumen ini adalah panduan teknis operasional (SOP) end-to-end untuk panitia dan administrator infrastruktur **IONIC 2026 Attack-Defense Finals**.  
Arsitektur menggunakan integrasi **RSCTF Platform (Web, Scoreboard, SLA Checker, Live Matrix)** + **Incus KVM MicroVM Cluster 2-Node (`zoan2` & `zoan3`)** + **L4 Full SNAT Cisco Firepower**.

---

## 1. Topologi & Ringkasan Alur Kerja

```text
┌────────────────────────────────────────────────────────────────────────┐
│                   RSCTF Platform (VM 192.168.95.5)                     │
│  ├── Web Platform: https://finals.tccpens.id (Caddy SSL Reverse Proxy) │
│  ├── Database: PostgreSQL 18 (Port 5432)                               │
│  ├── Tick Engine: Rotasi ronde 60s & generate flag di tabel AdFlags    │
│  └── SLA Checker Bot: Tembak HTTP/TCP ke 10.2.<Team>.<Chall>           │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│            rsctf-incus-bridge (Daemon Python di zoan2)                 │
│  ├── Memantau AdFlags baru tiap 2s -> incus exec tulis /root/flag.txt  │
│  └── Memantau AdSshKeys baru tiap 2s -> incus exec inject SSH key      │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
          ┌────────────────────────┴────────────────────────┐
          ▼                                                 ▼
┌─────────────────────────────────┐   ┌─────────────────────────────────┐
│     Node 1: zoan2 (Leader)      │   │    Node 2: zoan3 (Standby)      │
│  ├── Physical Trunk: br-data    │   │  ├── Physical Trunk: br-data    │
│  └── MicroVM Tim 1 s/d Tim 5    │   │  └── MicroVM Tim 6 s/d Tim 10   │
└─────────────────────────────────┘   └─────────────────────────────────┘
```

---

## 2. Skema Penomoran IP & Penempatan MicroVM

Sesuai aturan kompetisi, subnet server challenge menggunakan physical VLAN 101–110 di atas bridge **`br-data`** (`enp23s0f1`).

* **Format IP Vulnbox**: `10.2.<Team_ID>.<1 + Chall_ID>/28`
* **Gateway Firewall**: `10.2.<Team_ID>.1`
* **DNS Internal Wajib**: `202.9.85.4`, `202.9.85.3` (1.1.1.1 tidak dapat diakses!)

| Tim | VLAN | Subnet Server (`/28`) | Gateway Firepower | Chall 1 | Chall 2 | Chall 3 | Chall 4 | Node Baremetal |
| :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **Tim 1** | **101** | `10.2.1.0/28` | `10.2.1.1` | `10.2.1.2` | `10.2.1.3` | `10.2.1.4` | `10.2.1.5` | **zoan2** |
| **Tim 2** | **102** | `10.2.2.0/28` | `10.2.2.1` | `10.2.2.2` | `10.2.2.3` | `10.2.2.4` | `10.2.2.5` | **zoan2** |
| **Tim 3** | **103** | `10.2.3.0/28` | `10.2.3.1` | `10.2.3.2` | `10.2.3.3` | `10.2.3.4` | `10.2.3.5` | **zoan2** |
| **Tim 4** | **104** | `10.2.4.0/28` | `10.2.4.1` | `10.2.4.2` | `10.2.4.3` | `10.2.4.4` | `10.2.4.5` | **zoan2** |
| **Tim 5** | **105** | `10.2.5.0/28` | `10.2.5.1` | `10.2.5.2` | `10.2.5.3` | `10.2.5.4` | `10.2.5.5` | **zoan2** |
| **Tim 6** | **106** | `10.2.6.0/28` | `10.2.6.1` | `10.2.6.2` | `10.2.6.3` | `10.2.6.4` | `10.2.6.5` | **zoan3** |
| **Tim 7** | **107** | `10.2.7.0/28` | `10.2.7.1` | `10.2.7.2` | `10.2.7.3` | `10.2.7.4` | `10.2.7.5` | **zoan3** |
| **Tim 8** | **108** | `10.2.8.0/28` | `10.2.8.1` | `10.2.8.1` | `10.2.8.4` | `10.2.8.5` | **zoan3** |
| **Tim 9** | **109** | `10.2.9.0/28` | `10.2.9.1` | `10.2.9.2` | `10.2.9.3` | `10.2.9.4` | `10.2.9.5` | **zoan3** |
| **Tim 10**| **110** | `10.2.10.0/28`| `10.2.10.1`| `10.2.10.2`| `10.2.10.3`| `10.2.10.4`| `10.2.10.5`| **zoan3** |

---

## 3. Langkah 1: Registrasi Tim & Pembuatan Soal di Web RSCTF

Langkah ini dilakukan oleh panitia melalui browser di alamat:  
**`https://finals.tccpens.id/`** (Login sebagai Admin).

### A. Menerima (Accept) 10 Tim Finalis
1. Buka menu **Admin $\rightarrow$ Events $\rightarrow$ Games**.
2. Pilih game aktif, lalu buka tab **Review / Participations**.
3. Pastikan ke-10 tim peserta sudah berstatus **`Accepted`** (berurutan dari Tim 1 s/d Tim 10).
   * Status accepted akan menghasilkan ID partisipasi resmi di database RSCTF.

### B. Membuat 4 Challenge A&D di Web
Buka menu **Admin $\rightarrow$ Challenges $\rightarrow$ Add Challenge**:
Isi formulir dengan parameter berikut untuk masing-masing soal:

| Field | Pengaturan | Keterangan |
| :--- | :--- | :--- |
| **Title** | Nama Soal (misal: `VTS Tanjung Perak`) | Bebas |
| **Category** | `Web` / `Pwn` / `Reverse` | Sesuai jenis soal |
| **Type** | **`AttackDefense`** | Wajib |
| **Self-hosted / BYOC** | **[v] Centang (TRUE)** | **Wajib dicentang** agar RSCTF tidak membuat container Docker lokal |
| **Expose Port** | Port service soal (misal: `80` atau `8080` atau `9001`) | Port yang akan diuji SLA-nya oleh bot RSCTF |
| **Container Image** | **Kosongkan** | Tidak digunakan karena berjalan di Incus MicroVM |
| **Checker** | Kosongkan (default TCP probe) atau upload folder checker | Opsional |

Simpan keempat challenge hingga berstatus **Active**.

---

## 4. Langkah 2: Pembuatan Golden Image Challenge dari Template

Setiap challenge disiapkan dari base image Debian 13 MicroVM yang sudah terhubung ke repository kampus (`kebo.pens.ac.id`).

Jalankan perintah ini di baremetal **`zoan2`**:

```bash
# 1. Jalankan base template VM (sudah include mirror kebo.pens.ac.id)
incus init debian13-cloud template-c1 --vm \
  -p default \
  -p chall-spec-micro \
  -p mgmt-net

incus start template-c1

# 2. Masuk ke dalam VM template
incus exec template-c1 -- bash
```

Di dalam VM `template-c1`:
```bash
# Install paket kebutuhan soal (contoh web PHP/Apache/SQLite)
apt-get update
apt-get install -y apache2 php libapache2-mod-php sqlite3 php-sqlite3 openssh-server

# Pastikan SSH Server port 22 aktif
systemctl enable --now ssh

# Masukkan kode/biner soal problem setter
# (Bisa di-push via incus file push dari host)
```

Keluar dari VM, matikan, dan ubah menjadi **Golden Image Cluster**:
```bash
# Matikan instance template
incus stop template-c1

# Publish menjadi image cluster (tersedia otomatis di zoan2 dan zoan3)
incus publish template-c1 --alias golden-c1
```

---

## 5. Langkah 3: Deploy Massal 40 MicroVM ke Cluster Incus

Setelah golden image `golden-c1` siap (lakukan hal yang sama untuk `golden-c2`, `golden-c3`, `golden-c4`), deploy 40 MicroVM ke dua node baremetal menggunakan script otomatis.

Jalankan script bash ini di **`zoan2`**:

```bash
#!/bin/bash
set -e

# Chall index: 1, port offset 10.2.<t>.<1 + c>
CHALL_NUM=1
IMAGE_NAME="golden-c1"

echo "=== MEMULAI DEPLOYMENT MASSAL CHALLENGE ${CHALL_NUM} UNTUK 10 TIM ==="

for t in $(seq 1 10); do
  # Pembagian beban: Tim 1-5 di zoan2 (Leader), Tim 6-10 di zoan3 (Standby)
  TARGET_NODE="zoan2"
  [ "$t" -gt 5 ] && TARGET_NODE="zoan3"

  VM_NAME="t${t}-c${CHALL_NUM}"
  IP_ADDR="10.2.${t}.$((1 + CHALL_NUM))"
  GATEWAY="10.2.${t}.1"

  echo "[*] [Tim ${t}] Deploying ${VM_NAME} ke ${TARGET_NODE} (IP: ${IP_ADDR})..."

  # 1. Inisialisasi MicroVM
  incus init "${IMAGE_NAME}" "${VM_NAME}" --vm \
    -p default \
    -p chall-spec-micro \
    -p "team${t}-net" \
    --target "${TARGET_NODE}"

  # 2. Start VM
  incus start "${VM_NAME}"

  # Tunggu VM boot & agent siap (sekitar 5-8 detik)
  until incus exec "${VM_NAME}" -- id >/dev/null 2>&1; do
    sleep 1
  done

  # 3. Suntikkan konfigurasi IP Statis & DNS Internal (systemd-networkd)
  incus exec "${VM_NAME}" -- bash -c "
    mkdir -p /etc/cloud/cloud.cfg.d
    echo 'network: {config: disabled}' > /etc/cloud/cloud.cfg.d/99-disable-network.cfg
    
    mkdir -p /etc/systemd/network
    rm -f /etc/systemd/network/10-cloud-init*.network /etc/systemd/network/10-eth.network
    
    cat << 'NET' > /etc/systemd/network/10-static-enp5s0.network
[Match]
Name=en*

[Network]
Address=${IP_ADDR}/28
Gateway=${GATEWAY}
DNS=202.9.85.4
DNS=202.9.85.3
NET

    systemctl restart systemd-networkd
  "

  # 4. Buat snapshot awal 'clean' (Baseline untuk fitur tombol reset jika vulnbox dirusak tim)
  incus snapshot create "${VM_NAME}" clean

  echo "[+] ${VM_NAME} aktif dan terisolasi!"
done

echo "=== DEPLOYMENT 10 TIM SUKSES ==="
```

---

## 6. Langkah 4: Registrasi IP Vulnbox ke Database RSCTF

Agar bot SLA Checker dan engine ronde RSCTF mengetahui target IP masing-masing tim, daftarkan 40 endpoint tersebut ke tabel `AdTeamServices`.

Jalankan script helper resmi di **`zoan2`**:

```bash
python3 /home/execute/ionic26-infra/FINALS-INFRA/scripts/register_ad_endpoints.py --game-id 1
```

Script akan mencocokkan seluruh tim accepted dan soal A&D aktif, lalu menghasilkan output seperti berikut:
```text
[*] Menemukan 10 tim dan 4 challenge.
  [+] Tim 1 - Chall 1: 10.2.1.2:80
  [+] Tim 1 - Chall 2: 10.2.1.3:8080
  ...
  [+] Tim 10 - Chall 4: 10.2.10.5:9002

[OK] Sukses mendaftarkan 40 endpoint AdTeamServices ke RSCTF!
```

---

## 7. Cara Kerja Ticking Engine & Automasi Flag Injection

Siklus penanaman flag berjalan **sepenuhnya otomatis** setiap ronde (tick 60 detik) tanpa intervensi manusia:

```text
1. Engine RSCTF membuat Ronde baru di database (tabel "AdRounds")
2. Engine RSCTF men-generate 40 flag unik acak format `flag{32_hex}` di tabel "AdFlags"
3. Service "rsctf-incus-bridge" di zoan2 mendeteksi flag baru via cursor SQL (polling 2s)
4. Daemon mencocokkan target IP -> menemukan nama VM Incus target
5. Daemon mengeksekusi:
     incus exec <vm_name> -- sh -c "printf '%s\n' '<flag>' > /root/flag.txt && chmod 400 /root/flag.txt"
6. File /root/flag.txt di dalam VM ter-update seketika
7. Bot SLA Checker RSCTF menembak port service VM via Cisco Firepower L4 SNAT
8. Status SLA ter-update (Ok/Mumble/Offline) -> Scoreboard & Heatmap Matrix diperbarui
```

### Memeriksa Log Injeksi Flag:
Untuk memantau aktivitas injeksi flag secara real-time di `zoan2`:
```bash
sudo journalctl -u rsctf-incus-bridge -f
```
Output log normal:
```text
[INFO] rsctf-bridge: Round 45 flag 180 for service 1 -> dispatching to VM t1-c1 (10.2.1.2)
[INFO] rsctf-bridge: Injected flag into VM t1-c1 (len=38)
```

---

## 8. Cara Kerja Sinkronisasi SSH Key Peserta

Peserta membutuhkan akses SSH ke vulnbox mereka sendiri untuk membaca source code, memasang firewall lokal, dan melakukan patching.

1. **Upload Key**: Kapten atau anggota tim login ke web `https://finals.tccpens.id/`, lalu memasukkan public key SSH (`ssh-ed25519 ...` atau `ssh-rsa ...`) di menu profil/pengaturan tim.
2. **Deteksi Perubahan**: Daemon `rsctf-incus-bridge` mendeteksi adanya key baru di tabel `AdSshKeys`.
3. **Injeksi Otomatis**: Daemon langsung menyuntikkan key tersebut ke seluruh VM milik tim yang bersangkutan (Chall 1 s/d Chall 4):
   ```bash
   incus exec t<team>-c<chall> -- sh -c "mkdir -p /root/.ssh && cat << 'EOF' > /root/.ssh/authorized_keys ... && chmod 600 /root/.ssh/authorized_keys"
   ```
4. **Peserta Melakukan Login**:
   Peserta membuka terminal dari laptop masing-masing (yang terhubung ke port switch VLAN 11–20):
   ```bash
   # Tim 1 login ke mesin Chall 1 miliknya:
   ssh root@10.2.1.2
   
   # Tim 1 login ke mesin Chall 2 miliknya:
   ssh root@10.2.1.3
   ```
   * *Akses SSH antar-tim secara otomatis diblokir oleh Rule 13 Cisco Firepower (hanya subnet laptop tim X yang diizinkan SSH ke vulnbox tim X).*

---

## 9. Penanganan Insiden & Emergency Rollback (Fitur Reset)

Jika sebuah tim merusak sistem operasi mereka sendiri (misalnya salah konfigurasi permission `/etc`, merusak biner Apache, atau terkunci keluar):

### Rollback Instan (< 1 Detik)
Gunakan snapshot baseline `clean` yang dibuat saat deployment awal:

```bash
# Rollback VM Tim 3 Chall 1 ke kondisi pabrik:
incus snapshot restore t3-c1 clean

# Service langsung kembali hidup, IP statis tetap aman, dan ronde berikutnya flag langsung disuntikkan kembali secara normal.
```

---

## 10. Checklist Sebelum Memulai Pertandingan (Go-Live)

1. [ ] **RSCTF Platform**: Web `https://finals.tccpens.id/` dapat diakses dan Caddy reverse proxy aktif di `192.168.95.5`.
2. [ ] **Firepower SNAT**: L4 Full SNAT `Destination Interface IP` aktif di subinterface `Port-channel7.101` s/d `.110`.
3. [ ] **Physical Trunk `br-data`**: Tes ping dari node `zoan2` dan `zoan3` ke semua gateway `10.2.X.1` menghasilkan 0% packet loss.
4. [ ] **Bridge Service**: `systemctl status rsctf-incus-bridge` berstatus `active (running)`.
5. [ ] **40 MicroVMs**: Seluruh 40 VM berstatus `RUNNING` di `incus list` dan memiliki snapshot `clean`.
6. [ ] **Endpoint Mapping**: `register_ad_endpoints.py` sudah dieksekusi dan 40 endpoint terdaftar di tabel `AdTeamServices`.
7. [ ] **Start Game**: Klik tombol **Start** pada menu Game Administration di web RSCTF.
8. [ ] **Monitor Matrix**: Buka layar proyektor panggung di URL `https://finals.tccpens.id/games/1/matrix`.
