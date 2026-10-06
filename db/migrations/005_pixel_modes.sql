-- TypeForge migration 005: pixel-arcade game modes (canvas games reuse game_score).
ALTER TABLE typing_sessions
    MODIFY COLUMN mode ENUM('time','words','quote','custom','sprint','survival','memory','boss','invaders','blocks') NOT NULL;
