-- TypeForge migration 002: retire achievements without deleting grant history.
-- New achievements are active by default; deactivation hides them from the
-- grant pipeline and player shelf while preserving earned rows.
ALTER TABLE achievements ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;
