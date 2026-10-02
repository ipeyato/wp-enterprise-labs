# Standardisasi Lingkungan Pengembangan

## Inti Utama

Tujuan standardisasi lingkungan pengembangan adalah memastikan semua developer bekerja dengan **konfigurasi, dependensi, dan versi software yang konsisten**, sehingga menghindari masalah *"works on my machine"*.

## 1. Standardisasi Konfigurasi

- Lingkungan dibedakan berdasarkan konteks:
  - `local`
  - `development`
  - `staging`
  - `production`
- WordPress menyediakan:
  - `WP_ENVIRONMENT_TYPE`
  - `wp_get_environment_type()`
- Konfigurasi dapat dibuat berbeda berdasarkan environment, misalnya:
  - Development → `WP_DEBUG` aktif dan cache dapat dimatikan.
  - Production → debug dimatikan dan cache diaktifkan.
- Konfigurasi sebaiknya dapat direproduksi dan dikelola melalui version control.

## 2. Gunakan Docker untuk Konsistensi

Docker mengemas seluruh stack development sehingga setiap developer menggunakan lingkungan yang sama.

Umumnya terdiri dari:

- WordPress/PHP + Apache/NGINX
- MySQL/MariaDB
- Opsional: Redis, Elasticsearch, dan layanan lainnya

**Docker Compose** digunakan untuk mengatur beberapa container tersebut.

## 3. `wp-env` untuk WordPress

`wp-env` adalah cara sederhana untuk membuat environment WordPress berbasis Docker.

Konfigurasi dapat disimpan di `.wp-env.json` dan digunakan untuk menentukan:

- Versi WordPress
- Plugin
- Theme
- Pengaturan PHP

Tujuannya adalah agar developer dapat memperoleh environment yang konsisten dengan mudah.

## 4. Standardisasi Format Kode

Gunakan **EditorConfig** agar format kode konsisten di berbagai editor dan IDE.

Contohnya:

- Indentasi
- Line ending
- Character encoding

## 5. Jangan Simpan Secret di Source Code

Jangan hardcode informasi sensitif seperti:

- Password database
- API key
- Authentication token
- Credential lainnya

Gunakan **environment variables**, misalnya melalui file `.env`.

`vlucas/phpdotenv` dapat digunakan untuk membaca `.env` di PHP.

Untuk production, gunakan secret manager seperti **AWS Secrets Manager**.

## 6. Konsistenkan Versi PHP & MySQL

Semua developer sebaiknya menggunakan versi PHP dan MySQL yang sama.

Contoh:

- PHP `8.1`
- MySQL `8.0.32`

Konsistensi juga mencakup:

- PHP extensions
- Database drivers
- System libraries
- Dependency lainnya

Update versi harus dilakukan secara terkoordinasi dan diuji terlebih dahulu.

## Ringkasan Hafalan

> **Standardisasi environment = konfigurasi konsisten + container konsisten + secret terpisah + versi software konsisten.**

Alur sederhananya:

**Developer → Docker/wp-env → WordPress + PHP + MySQL → konfigurasi via environment variables → versi dikunci → hasil development konsisten**

## Istilah Kunci

`WP_ENVIRONMENT_TYPE` · `wp_get_environment_type()` · Docker · Docker Compose · `wp-env` · `.wp-env.json` · `.env` · `phpdotenv` · Secrets Manager · version pinning
