-- Create sports table
CREATE TABLE IF NOT EXISTS sports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sport_name VARCHAR(100) NOT NULL,
    score_type ENUM('points', 'binary') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default sports
INSERT INTO sports (sport_name, score_type) VALUES
('Basketball', 'points'),
('Mobile Legends', 'binary'),
('Volleyball', 'points'),
('DOTA 2', 'binary'),
('Valorant', 'binary'),
('Football', 'points');

-- Add sport_id column to tournaments table
ALTER TABLE tournaments ADD COLUMN sport_id INT AFTER tournament_name;

-- Set default sport (Basketball) for existing tournaments
UPDATE tournaments SET sport_id = (SELECT id FROM sports WHERE sport_name = 'Basketball');

-- Add foreign key constraint
ALTER TABLE tournaments ADD CONSTRAINT fk_tournament_sport FOREIGN KEY (sport_id) REFERENCES sports(id); 