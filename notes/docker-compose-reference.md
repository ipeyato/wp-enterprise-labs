# Referensi: Docker Compose untuk WordPress

Catatan pribadi dari Lab 01, challenge 4. File yang dibahas: `labs/01-dev-environment/docker-compose.yml`.

## Istilah dasar

| Istilah | Artinya |
|---|---|
| **Image** | "Installer" yang sudah jadi, misalnya `mysql:8.0.32`. Bagian setelah `:` disebut **tag**, yaitu versinya. Di sinilah version pinning terjadi. |
| **Container** | Image yang sedang berjalan. Image = file installer, container = aplikasi yang sedang dibuka. |
| **Volume** | Tempat menyimpan data supaya tidak hilang saat container dimatikan, misalnya isi database. |
| **Service** | Satu entri di bagian `services:` pada file Compose. Satu service = satu jenis container. |
| **Docker Compose** | Satu file `docker-compose.yml` berisi daftar container yang dibutuhkan dan cara menghubungkannya. Satu command, semuanya nyala. |

wp-env juga memakai Docker Compose di belakang layar (`~/.wp-env/<project>/docker-compose.yml`). Bedanya, file itu dibuat otomatis, jadi versi database-nya tidak bisa diatur sendiri.

## File lengkap dengan penjelasan

```yaml
name: lab01-compose              # nama grup container di Docker Desktop

services:                        # daftar container yang dibutuhkan

  db:                            # container 1: database
    image: mysql:8.0.32          # ← versi MySQL dikunci di sini
    environment:                 # setting awal MySQL: buat database dan user
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - db_data:/var/lib/mysql   # data database disimpan di volume, tidak hilang saat stop
    healthcheck:                 # cara Docker mengecek "MySQL sudah siap?"
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-proot"]
      interval: 5s
      retries: 20

  wordpress:                     # container 2: Apache + PHP + WordPress
    image: wordpress:7.1.2-php8.3-apache   # ← versi WordPress dan PHP dikunci di sini
    depends_on:
      db:
        condition: service_healthy         # tunggu MySQL siap, baru nyala
    ports:
      - "8080:80"                # buka di browser: http://localhost:8080
    environment: &wp_env         # &wp_env = beri nama blok ini supaya bisa dipakai ulang
      WORDPRESS_DB_HOST: db      # "db" = nama service di atas. Container saling memanggil lewat nama.
      WORDPRESS_DB_NAME: wordpress
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_CONFIG_EXTRA: |  # baris tambahan untuk wp-config.php
        define( 'WP_ENVIRONMENT_TYPE', 'production' );
        define( 'WP_DEBUG_DISPLAY', false );
        define( 'LAB01_APP_DIR', '/var/www/lab01' );
      WEATHER_API_KEY: ${WEATHER_API_KEY}  # nilai diambil dari .env di folder yang sama
      MAPS_API_KEY: ${MAPS_API_KEY}
    volumes: &wp_volumes
      - wp_data:/var/www/html                              # file WordPress core
      - ./mu-plugins:/var/www/html/wp-content/mu-plugins   # kode kita (sama seperti wp-env)
      - .:/var/www/lab01                                   # folder lab, di luar web root

  cli:                           # container 3: WP-CLI, hanya nyala saat dipanggil
    image: wordpress:cli-2.12.0-php8.3
    user: "33:33"                # pakai user yang sama dengan Apache supaya bisa menulis file
    depends_on:
      - wordpress
    environment: *wp_env         # *wp_env = pakai ulang blok environment di atas
    volumes: *wp_volumes
    profiles: ["tools"]          # tidak ikut nyala saat "docker compose up"

volumes:                         # daftar volume yang dipakai di atas
  db_data:
  wp_data:
```

### Pola yang perlu diingat

- **Format volume `sumber:tujuan`.** Kiri = di laptop (atau nama volume), kanan = path di dalam container. `./mu-plugins` (diawali `./`) adalah folder di laptop. `db_data` (tanpa `./`) adalah named volume yang dikelola Docker.
- **Format port `laptop:container`.** `"8080:80"` artinya port 80 di dalam container dibuka sebagai port 8080 di laptop.
- **Nama service = hostname.** Container `wordpress` menghubungi database lewat alamat `db`, bukan `localhost`.
- **`&nama` dan `*nama`** adalah fitur YAML untuk menyalin satu blok supaya tidak ditulis dua kali. Container `cli` butuh setting yang sama persis dengan `wordpress`.
- **`${VAR}`** diisi oleh Docker Compose dari file `.env` yang ada di folder yang sama dengan `docker-compose.yml`. Pengisian ini terjadi saat container **dibuat**, bukan saat container berjalan.
- **Kenapa `user: "33:33"`?** Image `wordpress` (Debian) menjalankan Apache sebagai `www-data` dengan uid 33. Image `wordpress:cli` (Alpine) punya `www-data` dengan uid 82. Kalau tidak disamakan, WP-CLI tidak bisa menulis ke `wp-content` dan muncul warning *"Unable to create directory wp-content/uploads"*.

## Command sehari-hari

```bash
docker compose up -d                       # nyalakan semua (di background)
docker compose ps                          # lihat container mana yang jalan
docker compose logs -f wordpress           # lihat log Apache/PHP (Ctrl+C untuk keluar)
docker compose run --rm cli wp <command>   # jalankan WP-CLI, container dihapus setelah selesai
docker compose exec wordpress bash         # masuk ke dalam container yang sedang jalan
docker compose down                        # matikan, data tetap tersimpan
docker compose down -v                     # matikan DAN hapus volume (database ikut hilang)
```

Install WordPress pertama kali:

```bash
docker compose run --rm cli wp core install --url=http://localhost:8080 \
  --title="Lab 01 Compose" --admin_user=admin --admin_password=password \
  --admin_email=admin@example.com --skip-email
```

## Mengubah secret atau setting

| Yang diubah | Command | Kenapa |
|---|---|---|
| Nilai di `.env` atau isi `docker-compose.yml` | `docker compose up -d` | Compose melihat ada perubahan, lalu **membuat ulang** container yang terpengaruh. |
| Kode PHP di `src/` atau `mu-plugins/` | Tidak perlu apa-apa | Folder di-mount, jadi perubahan langsung terbaca. |

`docker compose restart` **tidak** membaca ulang `.env` atau `docker-compose.yml`. Command itu hanya mematikan lalu menyalakan container yang sama dengan setting lama.

Docker Desktop atau Docker daemon tidak perlu di-restart. Yang dibuat ulang hanya container-nya.

## Secret di production

**Jangan buat `.env.production` di repo.** Apa pun yang masuk Git akan ikut ter-deploy dan bisa dibaca semua orang yang punya akses ke repo.

Secret di production di-set di luar repo, dan cara pastinya tergantung hosting:

| Hosting | Cara set secret | Kapan berlaku |
|---|---|---|
| WordPress VIP | `vip @app.production config envvar set MAPS_API_KEY`, lalu dibaca dengan `vip_get_env_var()` | Saat set, bisa dipilih langsung berlaku. Kalau tidak, berlaku pada deployment berikutnya. |
| AWS, dengan Secrets Manager | Disimpan di Secrets Manager, lalu dimasukkan ke container sebagai environment variable saat deploy | Saat container dibuat ulang (deploy berikutnya). |
| Server sendiri dengan Docker Compose | File `.env` dibuat **langsung di server** (tidak pernah di-commit), hak akses `chmod 600` | Setelah `docker compose up -d`. |

Kode di `Lab01\Secrets::get()` sudah menyesuaikan dengan semua cara di atas. Pertama dicek `vip_get_env_var()`, lalu environment variable sungguhan, dan file `.env` hanya dibaca kalau environment-nya `local` atau `development`.
