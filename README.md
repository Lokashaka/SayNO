# SayNO - Katakan tidak pada penipu

Sistem peringatan dini penipuan digital lewat WhatsApp. Teruskan link, file APK, atau pesan mencurigakan ke bot SayNO, lalu dapatkan status **Aman / Waspada / Bahaya** beserta alasannya dalam bahasa sehari-hari. Dibuat untuk .id DeveloperDay 2026 (Business Services – Security Solutions).

| | |
| --- | --- |
| Web | [sayno.lokashaka.id](https://sayno.lokashaka.id) |
| Bot WhatsApp | [+62 851-1141-2715](https://wa.me/6285111412715) |
| Video demo | [youtu.be/My3CTzoWnyk](https://youtu.be/My3CTzoWnyk) |
| **Dokumentasi lengkap** | [Notion: Dokumentasi SayNO](https://weak-pumpkin-2ce.notion.site/Dokumentasi-Sayno-3fd43572e6d9803eb285dc60134672bb) |

Fitur, arsitektur (diagram C4), skor risiko, keamanan, deploy, dan rencana pengembangan ada di dokumentasi Notion.

## Menjalankan di lokal

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Test: `php artisan test`. Format kode: `./vendor/bin/pint`.

## Tim

Moh Dhiyaulhaq Ulumuddin, Mohammad Nugroho Candy Pratama, dan Izaz Saputra — Universitas Negeri Malang.
