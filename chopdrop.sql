-- =============================================
--  ChopDrop Database — XAMPP / MySQL
-- =============================================
CREATE DATABASE IF NOT EXISTS chopdrop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chopdrop;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') DEFAULT 'customer',
  address TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS restaurants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  cuisine VARCHAR(120),
  address TEXT,
  phone VARCHAR(20),
  image VARCHAR(500) DEFAULT '',
  logo VARCHAR(500) DEFAULT '',
  rating DECIMAL(2,1) DEFAULT 4.5,
  delivery_fee INT DEFAULT 500,
  delivery_time VARCHAR(20) DEFAULT '20-35',
  is_open TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS foods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price INT NOT NULL,
  category VARCHAR(80),
  image VARCHAR(500) DEFAULT '',
  is_available TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  restaurant_id INT NOT NULL,
  total_amount INT NOT NULL,
  delivery_fee INT DEFAULT 500,
  delivery_address TEXT,
  payment_method ENUM('momo','orange','card','cash') DEFAULT 'cash',
  status ENUM('pending','confirmed','preparing','ready','in_transit','delivered','cancelled') DEFAULT 'pending',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  food_id INT NOT NULL,
  name VARCHAR(150),
  price INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (food_id) REFERENCES foods(id)
);

CREATE TABLE IF NOT EXISTS cart (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  food_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  UNIQUE KEY unique_cart (user_id, food_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  restaurant_id INT NOT NULL,
  order_id INT,
  rating TINYINT NOT NULL DEFAULT 5,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
);

-- Admin (password: admin123)
INSERT INTO users (name,email,phone,password,role) VALUES
('Admin ChopDrop','admin@chopdrop.cm','+237600000000','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin');

INSERT INTO restaurants (name,description,cuisine,address,phone,image,rating,delivery_fee,delivery_time,is_open) VALUES
('Mama Africa Kitchen','Authentic Cameroonian dishes made with love, tradition and the finest local spices.','African, Local','Akwa, Douala','+237655001001','https://images.unsplash.com/photo-1604329760661-e71dc83f8f26?w=800&q=80',4.8,500,'20-30',1),
('La Piazza Douala','Wood-fired Neapolitan pizzas crafted by a Naples-trained chef.','Pizza, Italian','Bonanjo, Douala','+237655001002','https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&q=80',4.6,600,'25-35',1),
('Burger Empire','Gourmet stacked burgers with fresh locally-sourced Cameroonian beef.','Burgers, American','Bonapriso, Douala','+237655001003','https://images.unsplash.com/photo-1571091718767-18b5b1457add?w=800&q=80',4.5,500,'15-25',1),
('Le Poulet Doré','The finest rotisserie and grilled chicken in Douala, marinated 24 hours.','Chicken, Grilled','Bali, Douala','+237655001004','https://images.unsplash.com/photo-1598103442097-8b74394b95c8?w=800&q=80',4.7,500,'20-35',0),
('Sushi Prestige','Premium Japanese omakase — the only authentic sushi bar in Yaoundé.','Sushi, Japanese','Bastos, Yaoundé','+237655001005','https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=800&q=80',4.9,700,'30-45',1),
('Green Bowl Café','Nourishing bowls, vibrant salads and cold-pressed juices.','Healthy, Salads','Santa Barbara, Yaoundé','+237655001006','https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800&q=80',4.8,400,'15-20',1),
('Spice of India','Aromatic Northern Indian curries, tandoor breads and fragrant biryanis.','Indian, Curry','Akwa, Douala','+237655001007','https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=800&q=80',4.6,600,'30-40',1),
('Le Bistro Français','Classic French brasserie — steaks, crêpes and the best wine list in Douala.','French, European','Bonanjo, Douala','+237655001008','https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&q=80',4.7,800,'35-50',1);

INSERT INTO foods (restaurant_id,name,description,price,category,image) VALUES
(1,'Ndolé Royal','Traditional Cameroonian bitterleaf stew with beef, jumbo shrimp and fried plantain',3500,'Main Dish','https://images.unsplash.com/photo-1604329760661-e71dc83f8f26?w=600&q=80'),
(1,'Jollof Rice Special','Party-style jollof rice with fried plantain, coleslaw and grilled tilapia',3200,'Main Dish','https://images.unsplash.com/photo-1574484284002-952d92456975?w=600&q=80'),
(1,'Pepper Soup','Spicy catfish pepper soup with aromatic traditional spices',2800,'Starter','https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600&q=80'),
(2,'Pepperoni Royale','San Marzano tomato, extra mozzarella and premium pepperoni, 12-inch',5500,'Pizza','https://images.unsplash.com/photo-1628840042765-356cda07504e?w=600&q=80'),
(2,'Margherita Classica','Buffalo mozzarella, San Marzano tomato, fresh basil, extra virgin olive oil',4800,'Pizza','https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=600&q=80'),
(2,'Truffle Funghi','Wild mushrooms, black truffle oil, fontina and fresh thyme',6500,'Pizza','https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&q=80'),
(3,'Empire Double Smash','Two smashed beef patties, aged cheddar, caramelised onion, secret sauce',4800,'Burger','https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&q=80'),
(3,'Crispy Chicken Deluxe','Buttermilk fried chicken, jalapeño slaw, pickles, honey mustard, brioche bun',4200,'Burger','https://images.unsplash.com/photo-1606755962773-d324e0a13086?w=600&q=80'),
(3,'Truffle Loaded Fries','Golden fries, black truffle aioli, crispy bacon bits, parmesan shavings',2500,'Sides','https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=600&q=80'),
(4,'Poulet Rôti Entier','Full rotisserie chicken marinated 24h in citrus, garlic and fine herbs',6500,'Main Dish','https://images.unsplash.com/photo-1598103442097-8b74394b95c8?w=600&q=80'),
(4,'Wings x10 Peri-Peri','10 crispy wings with fiery peri-peri glaze and blue cheese dip',3800,'Starter','https://images.unsplash.com/photo-1527477396000-e27163b481c2?w=600&q=80'),
(5,'Salmon Omakase Roll','8-piece premium salmon maki with tobiko, cucumber and yuzu mayo',5500,'Sushi','https://images.unsplash.com/photo-1617196034183-421b4040ed20?w=600&q=80'),
(5,'Tuna Sashimi Premium','6 slices bluefin tuna, wasabi, pickled ginger, ponzu dipping sauce',6200,'Sashimi','https://images.unsplash.com/photo-1534482421-64566f976cfa?w=600&q=80'),
(5,'Wagyu Gyoza','Pan-fried wagyu beef dumplings with ginger soy dipping sauce',4500,'Starter','https://images.unsplash.com/photo-1496116218417-1a781b1c416c?w=600&q=80'),
(6,'Avocado Power Bowl','Quinoa, smashed avocado, cherry tomatoes, feta, lemon tahini dressing',3500,'Bowl','https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600&q=80'),
(6,'Caesar Supreme','Crispy romaine, anchovy-parmesan dressing, sourdough croutons, soft egg',2800,'Salad','https://images.unsplash.com/photo-1546793665-c74683f339c1?w=600&q=80'),
(6,'Green Detox Smoothie','Spinach, cucumber, green apple, ginger, lemon — cold-pressed daily',1800,'Drinks','https://images.unsplash.com/photo-1610970881699-44a5587cabec?w=600&q=80'),
(7,'Butter Chicken','Tender chicken in a rich tomato-cream sauce with fragrant basmati rice',4500,'Curry','https://images.unsplash.com/photo-1603894584373-5ac82b2ae398?w=600&q=80'),
(7,'Lamb Biryani','Slow-cooked tender lamb, saffron-infused basmati, raita and pickle',5200,'Rice','https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=600&q=80'),
(7,'Garlic Naan Basket','4 freshly baked garlic naan breads from our clay tandoor oven',1500,'Bread','https://images.unsplash.com/photo-1601050690597-df0568f70950?w=600&q=80'),
(8,'Entrecôte Grillée','250g prime rib-eye, béarnaise sauce, pommes frites and garden salad',8500,'Main Dish','https://images.unsplash.com/photo-1558030006-450675393462?w=600&q=80'),
(8,'Crêpes Suzette','Flambéed crêpes in orange-butter sauce — a timeless French classic',3200,'Dessert','https://images.unsplash.com/photo-1519676867240-f03562e64548?w=600&q=80'),
(8,'French Onion Soup','Rich beef broth, caramelised onions, gruyère-topped crouton, piping hot',2800,'Starter','https://images.unsplash.com/photo-1547592166-23ac45744acd?w=600&q=80');

SELECT 'ChopDrop ready!' AS message;
