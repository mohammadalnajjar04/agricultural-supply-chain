# Agri Supply Chain (Jordan) — Final Submission
## AI-Based Agricultural Supply Chain Management System

A web-based platform connecting farmers, transporters, and store owners with AI-assisted decision support.  
Helps optimize supply chain operations using AI recommendations.

---

## Setup (XAMPP / WAMP)
1. Copy the folder `agri_supply_chain` into `htdocs`.
2. Open phpMyAdmin and **Import**:
   - `database_final.sql`
3. Update DB credentials if needed:
   - `config/db.php`
4. Open:
   - `http://localhost/agri_supply_chain/index.php`

---

## Roles
- Farmer: add/manage products, create transport requests, view AI recommendations.
- Transporter: browse/accept requests, update status (accepted → in_progress → delivered), view AI recommendations.
- Store: browse products, place orders (with quantity), rate after delivery, view AI recommendations.

---

## Trusted Sign Up (No Fake Accounts)
New accounts are created with **status = pending**.  
They cannot sign in until an admin approves them.  
Sign up requires a simple proof document (PDF/JPG/PNG).

---

## Admin (Approve / Reject)
- Admin login page: `auth/login.php?role=admin` (or `admin/login.php`)
- Default admin:
  - Email: `admin@agri.local`
  - Password: `Admin@123`
- Admin dashboard:
  - `admin/dashboard.php`

---

## Uploads
Uploaded verification documents are stored in:
- `uploads/verification/`

---

## Python AI Microservice (Required by report)
The AI component is implemented as a **Python (Flask) microservice**.

### Run on Windows (recommended for XAMPP)
1. Open the folder: `agri_supply_chain/ai_service`
2. Double click: `run_ai.bat`
3. Keep the terminal open.

### Run on macOS/Linux
```bash
cd agri_supply_chain/ai_service
chmod +x run_ai.sh
./run_ai.sh
