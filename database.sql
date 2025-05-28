-- Δημιουργία πίνακα χρηστών
CREATE TABLE users (
                       user_id INT AUTO_INCREMENT PRIMARY KEY,
                       username VARCHAR(50) UNIQUE NOT NULL,
                       email VARCHAR(100) UNIQUE NOT NULL,
                       password_hash VARCHAR(255) NOT NULL,
                       profile_picture_path VARCHAR(255) NULL,
                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                       updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Δημιουργία πίνακα συνταγών
CREATE TABLE recipes (
                         recipe_id INT AUTO_INCREMENT PRIMARY KEY,
                         user_id INT NOT NULL,
                         title VARCHAR(255) NOT NULL,
                         description TEXT NOT NULL,
                         prep_time VARCHAR(50) NULL,
                         cook_time VARCHAR(50) NULL,
                         servings VARCHAR(50) NULL,
                         main_image_path VARCHAR(255) NULL,
                         created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                         updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                         FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Δημιουργία πίνακα συστατικών (κύρια λίστα)
CREATE TABLE ingredients (
                             ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
                             ingredient_name VARCHAR(100) UNIQUE NOT NULL
);

-- Δημιουργία πίνακα σύνδεσης συνταγών-συστατικών (πολλών-προς-πολλών)
CREATE TABLE recipe_ingredients (
                                    recipe_ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
                                    recipe_id INT NOT NULL,
                                    ingredient_id INT NOT NULL,
                                    quantity VARCHAR(50) NOT NULL,
                                    unit VARCHAR(50) NULL,
                                    FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
                                    FOREIGN KEY (ingredient_id) REFERENCES ingredients(ingredient_id) ON DELETE CASCADE,
                                    UNIQUE (recipe_id, ingredient_id) -- Αποτρέπει διπλά συστατικά στην ίδια συνταγή
);

-- Δημιουργία πίνακα οδηγιών
CREATE TABLE directions (
                            direction_id INT AUTO_INCREMENT PRIMARY KEY,
                            recipe_id INT NOT NULL,
                            step_number INT NOT NULL,
                            description TEXT NOT NULL,
                            FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
                            UNIQUE (recipe_id, step_number) -- Διασφαλίζει μοναδική σειρά βημάτων ανά συνταγή
);

-- Δημιουργία πίνακα σχολίων
CREATE TABLE comments (
                          comment_id INT AUTO_INCREMENT PRIMARY KEY,
                          recipe_id INT NOT NULL,
                          user_id INT NOT NULL,
                          comment_text TEXT NOT NULL,
                          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
                          FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Δημιουργία πίνακα likes
CREATE TABLE likes (
                       like_id INT AUTO_INCREMENT PRIMARY KEY,
                       recipe_id INT NOT NULL,
                       user_id INT NOT NULL,
                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                       FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
                       FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                       UNIQUE (recipe_id, user_id) -- Διασφαλίζει ότι ένας χρήστης μπορεί να κάνει like μόνο μία φορά σε μια συνταγή [1]
);

-- Προσθήκη ευρετηρίων για βελτιστοποίηση απόδοσης [2, 3, 4, 5, 6]
CREATE INDEX idx_recipes_user_id ON recipes (user_id);
CREATE INDEX idx_comments_recipe_id ON comments (recipe_id);
CREATE INDEX idx_comments_user_id ON comments (user_id);
CREATE INDEX idx_likes_recipe_id ON likes (recipe_id);
CREATE INDEX idx_likes_user_id ON likes (user_id);
CREATE INDEX idx_recipe_ingredients_recipe_id ON recipe_ingredients (recipe_id);
CREATE INDEX idx_recipe_ingredients_ingredient_id ON recipe_ingredients (ingredient_id);
CREATE INDEX idx_directions_recipe_id ON directions (recipe_id);