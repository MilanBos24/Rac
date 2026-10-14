-- RAC: doplnění hlaviček a předmětu kontaktního e-mailu
-- Spustit jednou nad existující databází po 007_contact_form.sql
ALTER TABLE contact_form_settings
  ADD COLUMN sender_email VARCHAR(254) NULL DEFAULT NULL,
  ADD COLUMN sender_name VARCHAR(120) NOT NULL DEFAULT 'RAC – kontaktní formulář',
  ADD COLUMN email_subject VARCHAR(180) NOT NULL DEFAULT 'Zpráva z kontaktního formuláře';
