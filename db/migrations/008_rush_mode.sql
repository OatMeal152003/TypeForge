-- TypeForge migration 008: Neon Rush ghost-race mode (new ENUM value only).
-- Zero risk to existing rows: purely additive.
ALTER TABLE typing_sessions
    MODIFY COLUMN mode ENUM('time','words','quote','custom','invaders','blocks','meteors','rush') NOT NULL;
