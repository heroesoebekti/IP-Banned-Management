## SLiMS IP Banned Management

This plugin enhances SLiMS security by tracking and managing suspicious login attempts, allowing administrators to permanently ban (Jail) or permanently trust (Whitelist) specific IP addresses directly from the administrative panel.

### Key Features
This tool provides a centralized administration system for managing network access based on user behavior:
* IP Log Tracking: Records consecutive failed login attempts, enabling the system to temporarily block malicious IPs based on configurable thresholds (stored in the banned_ip_settings table).
* IP Jail Management: Allows administrators to permanently ban an IP address. Jailed IPs are stored separately and prevent any access attempt.
* IP Whitelist Management: Allows administrators to permanently trust an IP address. Whitelisted IPs bypass temporary security checks

### Installation & Integration
1. Place the ipbanned folder into your SLiMS plugins/ directory.
1. Activate the plugin through the SLiMS Administration Panel (System > Plugins).
1. Once activated and integrated, the new management menu items will appear under the System menu.

---

## Manajemen Pemblokiran IP

Plugin ini meningkatkan keamanan SLiMS dengan melacak dan mengelola upaya login mencurigakan, memungkinkan administrator untuk memblokir secara permanen (Jail) atau mempercayai secara permanen (Whitelist) alamat IP tertentu langsung dari panel administrasi.

#### Fitur Utama

Alat ini menyediakan sistem administrasi terpusat untuk mengelola akses jaringan berdasarkan perilaku pengguna:

1. Pelacakan Log IP (IP Log Tracking): Mencatat upaya login gagal berturut-turut, memungkinkan sistem untuk memblokir IP berbahaya secara sementara berdasarkan batas yang dapat dikonfigurasi (disimpan dalam tabel banned_ip_settings).
2. Manajemen IP Jail: Memungkinkan administrator untuk memblokir secara permanen (ban) alamat IP. IP yang di-jail disimpan secara terpisah dan mencegah setiap upaya akses.
3. Manajemen IP Whitelist: Memungkinkan administrator untuk mempercayai secara permanen alamat IP. IP yang di-Whitelist melewati pemeriksaan keamanan sementara
4. Integrasi Callback Aksi: Mengintegrasikan tombol aksi (Jail, Whitelist) langsung ke dalam daftar Log IP untuk respons administrasi yang cepat.

### Instalasi & Integrasi
1. Tempatkan folder ipbanned ke dalam direktori plugins/ SLiMS Anda.
1. Aktifkan plugin melalui Panel Administrasi SLiMS (Sistem > Plugins).
1. Setelah diaktifkan dan diintegrasikan, item menu manajemen baru akan muncul di bawah menu Sistem.
