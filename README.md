# Reservation & Billing System

A web-based Reservation and Billing System built with **HTML, PHP (PDO), MySQL, and Bootstrap 5**.

---

## Features

### Module 1 – Item Management
- Add, Edit, Delete, and Search items
- Fields: Item Code (auto-number), Item Description, Discountable (toggle), Quantity by Order, Price

### Module 2 – Reservation & Billing
- Accept customer number, list of items (items may be added multiple times), expected payment date, and mode of payment (CASH / CREDIT)
- Dynamic item-order builder with live discount and amount-due calculation
- Discount tiers based on **total item units** ordered (quantity × qty-by-order):

| Total Units | CASH  | CREDIT |
|-------------|-------|--------|
| ≥ 100       | 10%   | 8%     |
| 50 – 99     | 8%    | 5%     |
| 25 – 49     | 5%    | 3%     |
| < 25        | 0%    | 0%     |

> Discounts apply **only** to items flagged as *Discountable*.

---

## Setup

### Requirements
- PHP 7.4+ with PDO & PDO_MySQL
- MySQL 5.7+ / MariaDB 10.3+
- A web server (Apache / Nginx) or `php -S localhost:8000`

### Steps

1. **Import the database**
   ```bash
   mysql -u root -p < sql/setup.sql
   ```

2. **Configure the connection** – edit `db.php` and update `DB_HOST`, `DB_USER`, `DB_PASS` if needed.

3. **Serve the application**
   ```bash
   # From the project folder:
   php -S localhost:8000
   ```
   Then open [http://localhost:8000](http://localhost:8000).

---

## File Structure

```
reservation-system/
├── sql/
│   └── setup.sql          # Database schema + sample data
├── css/
│   └── style.css          # Custom styles
├── items/
│   ├── index.php          # List & search items
│   ├── add.php            # Add item
│   ├── edit.php           # Edit item
│   └── delete.php         # Delete item
├── reservations/
│   ├── index.php          # List reservations
│   ├── add.php            # Create reservation (dynamic items + live calculation)
│   ├── view.php           # Receipt / detail view
│   └── delete.php         # Delete reservation
├── db.php                 # PDO database connection helper
├── header.php             # Shared HTML header / nav
├── footer.php             # Shared HTML footer
└── index.php              # Dashboard
```

---

## Sample Data

Six items are pre-loaded by `setup.sql`:

| Code | Item Description               | Qty/Order | Price          |
|------|-------------------------------|-----------|----------------|
| 0001 | Honda Civic 2009              | 1         | ₱450,230.00    |
| 0002 | Dining Table                  | 5         | ₱1,500.00      |
| 0003 | Conference Room               | 1         | ₱25,000.00     |
| 0004 | Dinner Package (50pax)        | 1         | ₱35,000.00     |
| 0005 | Floral Arrangement            | 10        | ₱15,000.00     |
| 0006 | Wine and Liquor Package (10pax)| 15       | ₱100,000.00    |

### Verification – Sample 1

- Customer: **RNZ** | Payment: **CREDIT**
- Items: Wine ×3, Wine ×1, Wine ×1, Dining Table ×2, Floral ×1  
  *(3+2+1 order entries)*
- Total units: 3×15 + 2×5 + 1×10 = **65** → CREDIT 5% discount
- Subtotal: 3×100,000 + 2×1,500 + 1×15,000 = 318,000
- Discount: 318,000 × 5% = 15,900
- **Amount Due: ₱302,100.00** ✓

### Verification – Sample 2

- Customer: **RNZ** | Payment: **CASH**
- Items: Conference Room ×1, Dinner Package ×1, Dining Table ×1
- Total units: 1×1 + 1×1 + 1×5 = **7** → CASH 0% discount
- **Amount Due: ₱61,500.00** ✓
