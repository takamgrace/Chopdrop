# 🍷 ChopDrop — Premium Food Delivery Platform

ChopDrop is a luxury, multi-role food delivery ecosystem designed specifically for the modern Cameroonian market (starting in Douala & Yaoundé). It features a high-end **Glassmorphism** interface and a robust procedural PHP architecture.

![ChopDrop Banner](https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1400&q=85)

## ✨ Features

- **💎 Luxury UI/UX**: A stunning, responsive design inspired by modern glassmorphism aesthetics, using vibrant colors and premium typography.
- **🔐 Multi-Role System**:
  - **Platform Admin**: Global oversight of restaurants, vendors, and delivery logistics.
  - **Vendor**: Full autonomy over their restaurant profile, menu (dishes), incoming orders, and dedicated riders.
  - **Rider**: Streamlined delivery hub to claim, track, and fulfill orders assigned to their restaurant.
  - **Customer**: Seamless browsing, AJAX-powered "Add to Cart," and unified multi-restaurant checkout.
- **🛒 Dynamic Unified Checkout**: Order from multiple restaurants simultaneously with independent, restaurant-specific delivery fees calculated in real-time.
- **🚀 Real-time Experience**: No-reload ordering via AJAX and instant status updates for riders.
- **📈 Vendor Analytics**: Role-aware dashboards showing revenue and order volume specific to each vendor.

## 🛠️ Tech Stack

- **Backend**: PHP 8.x (Procedural)
- **Database**: MySQL / MariaDB (XAMPP Ready)
- **Frontend**: HTML5, Vanilla JavaScript (ES6+), CSS3 with Tailwind CSS (CDN)
- **Aesthetics**: Glassmorphism, CSS Animations, Adaptive Layouts

## ⚙️ Installation

1. **Clone the Project**:
   ```bash
   git clone [repository-url] c:/XAMPP/htdocs/chopdrop
   ```
2. **Database Setup**:
   - Open XAMPP Control Panel and start **Apache** and **MySQL**.
   - Go to `http://localhost/phpmyadmin`.
   - Create a new database named `chopdrop`.
   - Import the `chopdrop.sql` file provided in the root directory.
3. **Configuration**:
   - Ensure `includes/config.php` has your database credentials (default is `root` with no password).
4. **Access**:
   - Visit `http://localhost/chopdrop` to view the platform.

## 🔑 Access Credentials

### 🌍 Global Administrator
- **Email**: `admin@chopdrop.cm`
- **Password**: `admin123`

---

### 🏪 Restaurant-Specific Accounts (Vendor & Riders)

| Restaurant | Role | Email | Password |
| :--- | :--- | :--- | :--- |
| **Mama Africa Kitchen** | Vendor | `vendor_mama_africa_kitchen@chopdrop.cm` | `KQzDerNZ` |
| | Rider 1 | `rider1_mama_africa_kitchen@chopdrop.cm` | `0t8a25yN` |
| | Rider 2 | `rider2_mama_africa_kitchen@chopdrop.cm` | `5pmCtuSd` |
| **La Piazza Douala** | Vendor | `vendor_la_piazza_douala@chopdrop.cm` | `XvVYMqgW` |
| | Rider 1 | `rider1_la_piazza_douala@chopdrop.cm` | `4j2WUTLt` |
| | Rider 2 | `rider2_la_piazza_douala@chopdrop.cm` | `2cDa4ds8` |
| **Food Burger** | Vendor | `vendor_food_burger@chopdrop.cm` | `ArPtwh7I` |
| | Rider 1 | `rider1_food_burger@chopdrop.cm` | `0gWFxQam` |
| | Rider 2 | `rider2_food_burger@chopdrop.cm` | `UlbtQSAN` |
| **Le Poulet Dore** | Vendor | `vendor_le_poulet_dore@chopdrop.cm` | `iZqtu5Es` |
| | Rider 1 | `rider1_le_poulet_dore@chopdrop.cm` | `mYwNAW0r` |
| | Rider 2 | `rider2_le_poulet_dore@chopdrop.cm` | `uMZYya5h` |
| **Cmer Food** | Vendor | `vendor_cmer_food@chopdrop.cm` | `Z4ODRjwN` |
| | Rider 1 | `rider1_cmer_food@chopdrop.cm` | `dyLiq0gW` |
| | Rider 2 | `rider2_cmer_food@chopdrop.cm` | `GRct2Dh8` |
| **KNC** | Vendor | `vendor_knc@chopdrop.cm` | `wijXxSNC` |
| | Rider 1 | `rider1_knc@chopdrop.cm` | `UetrwAf0` |
| | Rider 2 | `rider2_knc@chopdrop.cm` | `0HICmi6k` |
| **Chicken Burger** | Vendor | `vendor_chicken_burger@chopdrop.cm` | `YKHeGmUS` |
| | Rider 1 | `rider1_chicken_burger@chopdrop.cm" | `1SQmGFwv` |
| | Rider 2 | `rider2_chicken_burger@chopdrop.cm" | `1SHOm8vT` |
| **Kamer Dishes** | Vendor | `vendor_kamer__dishes@chopdrop.cm` | `edzTmFMy` |
| | Rider 1 | `rider1_kamer__dishes@chopdrop.cm" | `EK790PtD` |
| | Rider 2 | `rider2_kamer__dishes@chopdrop.cm" | `jIPZCqiz` |

---

## 📂 Project Structure

- `/admin`: Management dashboard for both Platform Admins and Vendors.
- `/rider`: Dedicated workflow for delivery riders.
- `/includes`: Core configuration, database helpers, and UI partials.
- `index.php`: The main gourmet marketplace.
- `cart.php`: AJAX-enabled shopping cart handler.
- `checkout.php`: Unified multi-order processing engine.

---
*Built with ❤️ by the ChopDrop Team.*
