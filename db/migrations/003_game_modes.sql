-- TypeForge migration 003: arcade game modes + per-game score.
-- mode gains sprint / survival / memory / boss; game_score stores the
-- game-native result (cleared / waves / recalls / boss score), ranked on leaderboards.
ALTER TABLE typing_sessions
    MODIFY COLUMN mode ENUM('time','words','quote','custom','sprint','survival','memory','boss') NOT NULL;
ALTER TABLE typing_sessions
    ADD COLUMN game_score INT UNSIGNED NULL DEFAULT NULL AFTER raw_wpm;
