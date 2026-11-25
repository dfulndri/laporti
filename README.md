# LAPOR TI

Layanan Aspirasi dan Pengaduan berbasis HESK 3.7.10 (PHP) dengan tema custom `LaporTi`.

## Struktur folder

| Lokasi | Fungsi |
|---|---|
| `src/` | Seluruh aplikasi HESK. Jangan memindahkan file di dalamnya (path relatif) |
| `src/theme/LaporTi/` | Tema custom (kustomisasi tampilan dilakukan di sini) |
| `src/language/id/` | Terjemahan Bahasa Indonesia |
| `src/021lm3dntgmirqrs/` | Panel admin |
| `src/hesk_settings.inc.php.example` | Template konfigurasi (tanpa rahasia) |
| `docker/` | Dockerfile, entrypoint, dan folder `initdb/` untuk dump SQL |
| `docker-compose.yml` | Definisi layanan app + database |
| `.env` | Kredensial lokal (TIDAK masuk git, salin dari `.env.example`) |

## Menjalankan di lokal

1. Salin env dan isi password:

        cp .env.example .env

2. Taruh dump database di `docker/initdb/laporti.sql` (tidak ada di repo).
3. Jalankan:

        docker compose up -d --build

4. Buka:
   - Warga: http://localhost:8080
   - Admin: http://localhost:8080/021lm3dntgmirqrs/

`src/hesk_settings.inc.php` dibuat otomatis dari template + `.env` saat container pertama kali jalan.

## Database dan Navicat

Host `127.0.0.1`, port `3307`, user dan password sesuai `.env`, database `laporti`.

Backup:

        set -a; . ./.env; set +a
        docker exec laporti_db mariadb-dump -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" > backup_$(date +%F).sql

Restore (database harus sudah ada):

        docker exec -i laporti_db mariadb -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < backup.sql

## Aturan

- Jangan commit: `.env`, `src/hesk_settings.inc.php`, dump `.sql`, isi `src/attachments/`.
- Deploy ke hosting: upload isi folder `src/` saja (bukan root repo). Konfigurasi produksi diedit langsung di server.
- Kustomisasi hanya di `src/theme/LaporTi/` dan `src/language/id/`.
