-- Jalankan setelah db_rentalmobil_supabase.sql, melalui Supabase SQL Editor.
-- Bucket private: akses file hanya melalui bukti_file.php setelah pemeriksaan login.
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES ('bukti-bayar', 'bukti-bayar', false, 4194304,
  ARRAY['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
ON CONFLICT (id) DO UPDATE SET public = false,
  file_size_limit = EXCLUDED.file_size_limit, allowed_mime_types = EXCLUDED.allowed_mime_types;
