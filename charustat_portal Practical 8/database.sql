CREATE DATABASE IF NOT EXISTS charusat_portal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE charusat_portal;

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  event_date DATE NOT NULL,
  category VARCHAR(50) NOT NULL,
  description TEXT NOT NULL,
  image VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS event_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone CHAR(10) NOT NULL,
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_event_email (event_id, email),
  CONSTRAINT fk_registration_event FOREIGN KEY (event_id)
    REFERENCES events(id) ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO events (title, event_date, category, description, image) VALUES
('Annual Tech Fest', '2026-08-15', 'Technical',
 'Join us for coding contests, robotics displays and guest lectures.', 'images/technical.svg'),
('National Level Hackathon 2026', '2026-09-22', 'Technical',
 'A coding sprint to build innovative solutions for real-world problems.', 'images/hackathon.svg'),
('Spandan: Annual Cultural Night', '2026-10-05', 'Cultural',
 'Enjoy music, stage plays, talent showcases and group dance performances.', 'images/cultural.svg'),
('Inter College Cricket Tournament', '2026-10-18', 'Sports',
 'Participate in the annual inter-college sports tournament and represent CHARUSAT.', 'images/sports.svg'),
('AI and Machine Learning Workshop', '2026-11-02', 'Workshop',
 'Learn artificial intelligence and machine learning through practical demonstrations.', 'images/workshop.svg'),
('Entrepreneurship Summit 2026', '2026-11-15', 'Seminar',
 'Meet entrepreneurs, startup founders and industry experts.', 'images/summit.svg'),
('Annual Science Exhibition', '2026-12-01', 'Academic',
 'Explore student projects, scientific models and research demonstrations.', 'images/science.svg'),
('CHARUSAT Alumni Meet', '2026-12-20', 'Alumni',
 'Reconnect with alumni through networking and interactive sessions.', 'images/alumni.svg');
