import http.client
import json
from lib import AdContext, Mumble, Offline, checker, run_ad_checker

def http_get(context: AdContext, path: str) -> tuple[int, str]:
    try:
        conn = http.client.HTTPConnection(context.target_ip, context.target_port, timeout=4)
        conn.request("GET", path, headers={"Connection": "close", "User-Agent": "VTS-SLA-Checker/2.0"})
        res = conn.getresponse()
        body = res.read(8192).decode("utf-8", errors="ignore").strip()
        conn.close()
        return res.status, body
    except (OSError, TimeoutError) as e:
        raise Offline(f"Layanan target mati atau menolak koneksi pada {path}") from e

@checker
def check_health_endpoint(context: AdContext):
    status, body = http_get(context, "/health.php")
    if status != 200:
        raise Mumble(f"health.php mengembalikan HTTP {status}")
    try:
        data = json.loads(body)
        if data.get("status") != "healthy":
            errors = data.get("errors", [])
            raise Mumble(f"Status VTS tidak sehat: {', '.join(errors)}")
        if data.get("checks", {}).get("database", {}).get("status") != "ok":
            raise Mumble("Basis data kapal SQLite tidak merespons dengan benar")
    except json.JSONDecodeError:
        raise Mumble("Format respons JSON dari health.php tidak valid")

@checker
def check_vts_dashboard(context: AdContext):
    status, body = http_get(context, "/index.php")
    if status != 200:
        raise Mumble(f"Halaman utama index.php mengembalikan HTTP {status}")
    if "TANJUNG PERAK" not in body or "Armada Aktif" not in body:
        raise Mumble("Elemen navigasi atau metrik kapal hilang dari dashboard utama")
    if "KM Dharma Rencana" not in body:
        raise Mumble("Tabel pergerakan kapal tidak memuat data kapal yang valid")

@checker
def check_berth_schedule(context: AdContext):
    status, body = http_get(context, "/berth_schedule.php")
    if status != 200:
        raise Mumble(f"Halaman berth_schedule.php mengembalikan HTTP {status}")
    if "Alokasi Tambatan" not in body or "Dermaga Jamrud" not in body:
        raise Mumble("Tabel jadwal tambatan kade dermaga gagal dimuat")

@checker
def check_hydro_meteo(context: AdContext):
    status, body = http_get(context, "/hydro_meteo.php")
    if status != 200:
        raise Mumble(f"Halaman hydro_meteo.php mengembalikan HTTP {status}")
    if "Karang Jamuang" not in body or "Pasang Surut" not in body:
        raise Mumble("Data sensor oseanografi stasiun Karang Jamuang gagal dimuat")

@checker
def check_navwarn(context: AdContext):
    status, body = http_get(context, "/navwarn.php")
    if status != 200:
        raise Mumble(f"Halaman navwarn.php mengembalikan HTTP {status}")
    if "NOTMAR" not in body or "Bahaya" not in body:
        raise Mumble("Siaran maklumat keselamatan pelayaran gagal dimuat")

@checker
def check_manifest_log_viewer(context: AdContext):
    status, body = http_get(context, "/manifest_logs.php?source=apbs_traffic_202611.log")
    if status != 200:
        raise Mumble(f"Modul manifest_logs.php mengembalikan HTTP {status}")
    if "[APBS-RADAR]" not in body:
        raise Mumble("Arsip log telemetri APBS gagal dimuat dari direktori logs")

@checker
def check_operator_console_interface(context: AdContext):
    status, body = http_get(context, "/console.php")
    if status != 200:
        raise Mumble(f"Halaman console.php mengembalikan HTTP {status}")
    if "Otentikasi Petugas Jaga" not in body and "Konsol Telemetri" not in body:
        raise Mumble("Antarmuka konsol operator stasiun VTS rusak")

if __name__ == "__main__":
    raise SystemExit(run_ad_checker())
