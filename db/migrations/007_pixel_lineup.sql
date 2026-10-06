-- TypeForge migration 007: pixel-only arcade lineup.
-- Retires sprint / survival / memory / boss; adds meteors.
-- Run ONLY after wiping those modes (backup first: db\backup-pre-arcade-wipe.sql).
ALTER TABLE typing_sessions
    MODIFY COLUMN mode ENUM('time','words','quote','custom','invaders','blocks','meteors') NOT NULL;
