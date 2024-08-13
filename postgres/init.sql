CREATE ROLE client WITH LOGIN PASSWORD 'client';
CREATE DATABASE event_booking_system WITH OWNER client;
\c event_booking_system;

CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(16) NOT NULL UNIQUE,
    user_phone VARCHAR(10) UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_organizer BOOLEAN DEFAULT FALSE,
    is_admin BOOLEAN DEFAULT FALSE
);

CREATE TABLE events (
    event_id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT,
    organizer_id INT REFERENCES users(user_id) ON DELETE SET NULL,
    capacity INT,
    ticket_price DECIMAL(10, 2) NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE
);

CREATE TABLE event_categories (
    category_id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE event_category_mapping (
    event_id INT REFERENCES events(event_id) ON DELETE CASCADE,
    category_id INT REFERENCES event_categories(category_id) ON DELETE CASCADE,
    PRIMARY KEY (event_id, category_id)
);

CREATE TABLE user_event_attendance (
    user_id INT REFERENCES users(user_id) ON DELETE SET DEFAULT,
    event_id INT REFERENCES events(event_id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'confirmed',
    PRIMARY KEY (user_id, event_id)
);

CREATE TABLE event_images (
    image_id SERIAL PRIMARY KEY,
    event_id INT REFERENCES events(event_id) ON DELETE CASCADE,
    image_url VARCHAR(255) NOT NULL,
    image_type VARCHAR(20) NOT NULL
);

CREATE TABLE event_dates (
    event_date_id SERIAL PRIMARY KEY,
    event_id INT REFERENCES events(event_id) ON DELETE CASCADE,
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL
);

CREATE TABLE ticket_sales (
    sale_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(user_id) ON DELETE SET NULL,
    event_id INT REFERENCES events(event_id) ON DELETE CASCADE,
    quantity INT NOT NULL CHECK (quantity > 0),
    total_price DECIMAL(10, 2) NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DO $$
DECLARE
    deleted_user_id INT;
BEGIN
    INSERT INTO users (username, user_phone, email, password_hash, is_organizer, is_admin)
    VALUES ('deleted_user', NULL, 'deleted_user@example.com', 'default_password_hash', FALSE, FALSE)
    ON CONFLICT (username) DO NOTHING;
    SELECT user_id INTO deleted_user_id FROM users WHERE username = 'deleted_user';
    EXECUTE 'ALTER TABLE user_event_attendance ALTER COLUMN user_id SET DEFAULT ' || deleted_user_id;
END
$$;
