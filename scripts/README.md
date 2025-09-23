# Migration Cleanup Script

Script untuk membersihkan dan merapikan file migrations Laravel yang berantakan.

## 🎯 Apa yang dilakukan script ini?

1. **Backup** semua migration files yang ada ke folder `database/migrations_backup/`
2. **Generate** migration files baru yang bersih dengan:
   - ✅ Urutan timestamp yang benar
   - ✅ Dependencies yang proper (foreign keys)
   - ✅ Naming convention yang konsisten
   - ✅ Performance indexes yang optimal

## 🚀 Cara menjalankan

### Local Development

```bash
# Pastikan di root project
cd /path/to/noobz-movie

# Jalankan script
php scripts/cleanup-migrations.php
```

### Production (Laravel Forge)

```bash
# Via Laravel Forge Commands tab
php scripts/cleanup-migrations.php

# Atau via SSH
ssh forge@your-server
cd /home/forge/noobz.space
php scripts/cleanup-migrations.php
```

## 📁 File Structure Setelah Cleanup

```
database/migrations/
├── 2024_01_01_000001_create_users_table.php
├── 2024_01_01_000002_create_cache_table.php
├── 2024_01_01_000003_create_jobs_table.php
├── 2024_01_01_000004_create_personal_access_tokens_table.php
├── 2024_01_01_000005_create_roles_table.php
├── 2024_01_01_000006_create_permissions_table.php
├── 2024_01_01_000007_create_permission_role_table.php
├── 2024_01_01_000008_modify_users_table.php
├── 2024_01_01_000009_create_genres_table.php
├── 2024_01_01_000010_create_movies_table.php
├── 2024_01_01_000011_create_movie_genres_table.php
├── 2024_01_01_000012_create_movie_sources_table.php
├── 2024_01_01_000013_create_series_table.php
├── 2024_01_01_000014_create_series_genres_table.php
├── 2024_01_01_000015_create_series_seasons_table.php
├── 2024_01_01_000016_create_series_episodes_table.php
├── 2024_01_01_000017_create_watchlists_table.php
├── 2024_01_01_000018_create_movie_views_table.php
├── 2024_01_01_000019_create_series_views_table.php
├── 2024_01_01_000020_create_series_episode_views_table.php
├── 2024_01_01_000021_create_invite_codes_table.php
├── 2024_01_01_000022_create_user_registrations_table.php
├── 2024_01_01_000023_create_search_histories_table.php
├── 2024_01_01_000024_create_broken_link_reports_table.php
├── 2024_01_01_000025_create_user_action_logs_table.php
├── 2024_01_01_000026_create_admin_action_logs_table.php
├── 2024_01_01_000027_create_audit_logs_table.php
├── 2024_01_01_000028_create_user_activities_table.php
└── 2024_01_01_000029_add_performance_indexes.php
```

## 🛡️ Safety Features

- **Auto Backup**: File asli di-backup ke `database/migrations_backup/`
- **Dependency Order**: Foreign keys dibuat setelah referenced tables
- **Performance Optimized**: Indexes ditambah di akhir untuk speed

## 📋 Setelah Script Jalan

1. **Check backup folder**:
   ```bash
   ls -la database/migrations_backup/
   ```

2. **Reset database dan run migrations**:
   ```bash
   php artisan db:wipe --force
   php artisan migrate --force
   php artisan db:seed --force
   ```

3. **Test website**:
   - Akses https://noobz.space
   - Test semua fitur (login, search, movies, series)

## ⚠️ Important Notes

- **BACKUP DATABASE** sebelum run di production!
- Script ini akan **menghapus** semua migration files lama
- Migration baru akan punya timestamp beda, jadi `migrate:rollback` gak akan work
- Untuk production, sebaiknya test di staging dulu

## 🔄 Rollback (jika ada masalah)

Kalau ada masalah, bisa restore file asli:

```bash
# Hapus migrations baru
rm database/migrations/*.php

# Restore dari backup
cp database/migrations_backup/*.php database/migrations/

# Reset database
php artisan db:wipe --force
php artisan migrate --force
```

## 📞 Troubleshooting

### Error: "Foreign key constraint fails"
- Cek urutan migrations
- Pastikan referenced table dibuat duluan

### Error: "Column not found"
- Cek nama kolom di migration
- Pastikan tidak ada typo

### Error: "Duplicate column name"
- Ada duplikasi kolom di migration files
- Check generated migrations

---

**Created by:** Claude Code Assistant
**Date:** 2025-09-24