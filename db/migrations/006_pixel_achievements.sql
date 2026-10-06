-- TypeForge migration 006: pixel-arcade achievements (score-based criteria).
ALTER TABLE achievements
    MODIFY COLUMN criteria_type ENUM('tests_count','wpm_reach','accuracy_reach','streak_days','invaders_score','blocks_score') NOT NULL;
INSERT IGNORE INTO achievements (code, title, description, icon, criteria_type, criteria_value, points, is_active) VALUES
('INVADER_SLAYER', 'Invader Slayer', 'Score 500 in a single Pixel Invaders run.', 'fa-rocket', 'invaders_score', 500.00, 70, 1),
('BLOCK_SMASHER', 'Block Smasher', 'Score 300 in a single Block Drop run.', 'fa-cubes', 'blocks_score', 300.00, 70, 1);
