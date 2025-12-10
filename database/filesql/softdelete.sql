-- === UP: Tambah kolom untuk fitur Trash ===

ALTER TABLE `certificates`
  ADD COLUMN `trashed_pdf_path` VARCHAR(255) NULL AFTER `generated_pdf_path`,
  ADD COLUMN `deleted_at` TIMESTAMP NULL AFTER `trashed_pdf_path`;


-- === DOWN: Rollback (hapus kolom) ===

ALTER TABLE `certificates`
  DROP COLUMN `deleted_at`,
  DROP COLUMN `trashed_pdf_path`;