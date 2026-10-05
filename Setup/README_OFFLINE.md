# ModPyPhp - Offline System Installation Guide

This folder contains all the necessary installers, application bundles, packages, database backups, and scripts to install and run **ModPyPhp** on an offline (air-gapped) computer without an internet connection.

---

## 📁 Setup Directory Contents

```
Setup/
├── modpyphp_app.zip               # Complete bundled PHP application, vendor dependencies, assets & AI services
├── package_app.ps1                # Maintenance script to re-package application bundle & Setup.zip archive
├── Installers/
│   ├── postgresql-18-installer.exe# Official PostgreSQL 18 Offline Installer (~375 MB)
│   ├── python-3.11.9-amd64.exe   # Official Python 3.11 64-bit Offline Installer (~26 MB)
│   └── php-windows.zip           # Pre-configured PHP 8.x Zip Package with required extensions (~35 MB)
├── wheels/                       # Pre-downloaded Python `.whl` packages for offline pip installation
│   ├── Flask, Flask-Cors, psycopg2-binary, fuzzywuzzy, python-Levenshtein, pgvector, etc.
├── Database/
│   └── modpyphp_dump.sql         # Complete PostgreSQL Database Schema & Initial Data Dump (~1.77 MB)
├── setup.bat                     # Automated One-Click Offline Setup Script (6-Step Deployment)
└── README_OFFLINE.md             # Installation Instructions & Troubleshooting
```

---

## 🚀 Quick Start (Automated Offline Installation)

1. Copy the `Setup/` directory (or extract `Setup.zip`) to the target offline computer.
2. Double-click **`setup.bat`** (or `install.bat` from the root directory).
3. The script will automatically execute the full installer pipeline:
   - **[1/6] Application Files:** Extracts `modpyphp_app.zip` (PHP files, Vendor libs, AI engine, schemas, launchers) to the project directory if not already present.
   - **[2/6] Python Runtime:** Installs **Python 3.11** if missing.
   - **[3/6] PHP Runtime:** Extracts pre-configured **PHP 8.x** to `C:\php` if missing.
   - **[4/6] Python Virtual Environment:** Creates `.venv_uv` virtual environment using offline `wheels/`.
   - **[5/6] Database Restoration:** Launches **PostgreSQL 18** installer if missing, creates database `modpyphp`, and restores `Setup/Database/modpyphp_dump.sql`. *(Set password to `daredevil` when prompted by PostgreSQL)*.
   - **[6/6] Configuration:** Generates `.env` database connection file.
4. Once completed, start the server anytime by running:
   ```cmd
   start-server.bat
   ```

---

## 🛠️ Re-Packaging Application Code for Distribution

If you modify application files, update vendor dependencies, or add new features, re-create the offline application bundle and master `Setup.zip` archive by executing:

```powershell
powershell -ExecutionPolicy Bypass -File "Setup\package_app.ps1"
```

This will automatically refresh:
- `Setup\modpyphp_app.zip` (Application Code & Dependencies Archive)
- `Setup.zip` (Standalone Offline System Installer Package)

---

## 🌐 Server Access Endpoints

Once `start-server.bat` is running:
* **Web App (PHP):** http://localhost:8000
* **AI Engine (Python Flask):** https://localhost:5000
* **Attendance Engine (Python):** http://localhost:5001
