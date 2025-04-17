-- Migration script untuk mengubah struktur database media_app
-- Tanggal: 17 April 2025
-- Deskripsi: Migrasi dari struktur lama (structure_DB.sql) ke struktur baru (media_app.sql)

-- 1. Tabel isus: Mengubah kolom skala dari varchar menjadi int dan menambahkan foreign key
ALTER TABLE `isus` 
    MODIFY COLUMN `skala` int NOT NULL,
    ADD CONSTRAINT `fk_isus_skala` FOREIGN KEY (`skala`) REFERENCES `ref_skala`(`id`);

-- 2. Tabel ref_skala: Menghapus kolom kode dan deskripsi, menambahkan kolom warna
ALTER TABLE `ref_skala` 
    DROP COLUMN `kode`,
    DROP COLUMN `deskripsi`,
    ADD COLUMN `warna` varchar(256) NOT NULL AFTER `nama`;

-- 3. Tabel ref_tone: Menghapus kolom kode dan deskripsi
ALTER TABLE `ref_tone` 
    DROP COLUMN `kode`,
    DROP COLUMN `deskripsi`;

-- 4. Tabel trendings: Menambahkan kolom is_selected dan kolom display order
ALTER TABLE `trendings` 
    ADD COLUMN `is_selected` tinyint(1) NOT NULL DEFAULT '0',
    ADD COLUMN `display_order` int NOT NULL DEFAULT '0',
    ADD COLUMN `display_order_google` int NOT NULL DEFAULT '0',
    ADD COLUMN `display_order_x` int NOT NULL DEFAULT '0';

-- 5. Menambahkan index pada tabel trendings
ALTER TABLE `trendings` 
    ADD INDEX `idx_is_selected` (`is_selected`),
    ADD INDEX `idx_display_order` (`display_order`),
    ADD INDEX `idx_display_order_google` (`display_order_google`),
    ADD INDEX `idx_display_order_x` (`display_order_x`);

-- 6. Tabel kategoris: Menghapus kolom deskripsi
ALTER TABLE `kategoris` 
    DROP COLUMN `deskripsi`;

-- 7. Tabel isu_kategori: Menghapus kolom created_at dan updated_at
ALTER TABLE `isu_kategori` 
    DROP COLUMN `created_at`,
    DROP COLUMN `updated_at`;