# Agri Supply Chain – Setup (Final)

## 1) Import Database (FINAL)
1. Open **phpMyAdmin**.
2. Create database (or it will be created automatically): **agri_supply_chain**
3. Go to **Import** → choose:
   `database/agri_supply_chain_FINAL.sql`
4. Click **Go**.

> The file includes the full schema + sample accounts + **market_prices dataset (300 rows)** used by AI pages.

## 2) Update DB Password (if needed)
Edit:
`config/db.php`
and set the correct MySQL password for your XAMPP.

## 3) Default Accounts
### Admin
- Email: **admin@agri.local**
- Password: **admin123**

### Farmers / Stores / Transporters
All demo accounts use:
- Password: **123456**

Examples:
- Farmer: farmer1@agri.local
- Store: store1@agri.local
- Transporter: trans1@agri.local

## 4) Signup – Required Inputs
All signup fields are required (for credibility) + proof document upload is required.



## 5) AI Microservice (Flask) – ML Recommendations (2024–2026)
This project includes a **Python Flask AI service** that provides:
- `/predict` price prediction
- `/recommend` best market to sell/buy for a product (uses ML model trained on 2024–2026 dataset)

### Run (Windows)
Open CMD inside:
`ai_service/`
Then run:
- `run_ai.bat`

### Run (Linux/Mac)
Inside:
`ai_service/`
Run:
- `bash run_ai.sh`

### Test
Open in browser:
- `http://127.0.0.1:5000/health`

> The ML dataset file is included here:
`ai_service/data/agri_ai_dataset_2024_2026.csv`

## 6) Optional: Import ML dataset into MySQL
If you want the same dataset inside MySQL too, import:
`database/market_prices_ml_2024_2026.sql`
It will create table: `market_prices_ml`
