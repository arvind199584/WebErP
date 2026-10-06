# Deploying ModPyPhp on Render via Git

This guide explains how to deploy the ModPyPhp Enterprise ERP system to **[Render.com](https://render.com)** using your Git repository (`arvind199584/WebErP`).

---

## Architecture Overview on Render

- **Multi-Runtime Container:** Render builds the unified container from [`Dockerfile`](Dockerfile) running **Supervisor**:
  1. **PHP 8.3 CLI (Port 8000 / `$PORT`):** The public web server. Render routes all incoming public HTTPS traffic (port 443) directly to this process.
  2. **Python 3.11 AI Brain (Port 5000):** Internal NLP microservice running on `127.0.0.1:5000`.
  3. **Python 3.11 Attendance Service (Port 5001):** Internal attendance microservice running on `127.0.0.1:5001`.
- **How the 3 Ports Communicate:**
  - Cloud platforms like Render expose **one public port** per web service.
  - Ports 5000 and 5001 run as private, high-speed internal microservices inside the same container.
  - PHP calls them securely via container loopback (`https://127.0.0.1:5000` and `http://127.0.0.1:5001`).
  - Frontend browser calls (such as `ai_entry.php`) route through the PHP API proxy (`/api/index.php?action=nlp-action`), so all 3 services work seamlessly without needing multiple exposed public URLs!
- **Database:** Connects to any cloud PostgreSQL provider (such as [Neon.tech](https://neon.tech), [Supabase](https://supabase.com), or Render PostgreSQL) via the standard `DATABASE_URL` connection string.

---

## Option 1: Automatic Deployment with Render Blueprint (Recommended)

Render provides Infrastructure-as-Code via the included [`render.yaml`](render.yaml) file.

1. **Push your code to GitHub:**
   ```bash
   git add .
   git commit -m "feat(render): add render.yaml blueprint and cloud database compatibility"
   git push origin main
   ```

2. **Deploy on Render:**
   - Log in to your [Render Dashboard](https://dashboard.render.com).
   - Click **New +** in the top right corner and select **Blueprint**.
   - Connect your GitHub repository: `arvind199584/WebErP`.
   - Render will detect `render.yaml` and configure the Web Service (`modpyphp-erp`).
   - In the **Environment Variables** prompt, paste your **`DATABASE_URL`** (from Neon or Supabase).
   - Click **Apply**. Render will automatically build the Docker image, run the health check, and publish your live URL!

---

## Option 2: Manual Deployment via Render Dashboard

If you prefer configuring the Web Service manually:

1. In Render Dashboard, click **New +** -> **Web Service**.
2. Select your repository: `arvind199584/WebErP`.
3. Configure the settings:
   - **Name:** `modpyphp-erp`
   - **Region:** Choose the region closest to your database (e.g. `Oregon (US West)` or `Frankfurt (EU)`).
   - **Branch:** `main`
   - **Runtime:** `Docker`
   - **Dockerfile Path:** `./Dockerfile`
   - **Instance Type:** `Free` or `Starter`
4. Under **Advanced / Environment Variables**, add:
   - `DATABASE_URL` = `postgresql://user:password@ep-xyz.neon.tech/neondb?sslmode=require`
   - `DB_SSLMODE` = `require`
   - `APP_ENV` = `production`
5. Under **Health Check Path**, enter:
   - `/`
6. Click **Create Web Service**.

---

## Database Setup (Neon or Supabase)

If you are using a new cloud database instance:

1. Obtain your connection string:
   - **Neon:** In your project dashboard, copy the pooled or direct connection string (`postgresql://user:pass@ep-xyz.aws.neon.tech/neondb?sslmode=require`).
   - **Supabase:** Go to Project Settings -> Database -> Connection URI (use Transaction Pooler or Direct mode).
2. Seed your database:
   You can restore your schema and initial seed data using `psql`:
   ```bash
   psql "<YOUR_DATABASE_URL>" -f database/dumps/modpyphp_clean.sql
   ```
   Or load the DDL scripts in `schema/`.

---

## Verifying the Deployment

Once Render finishes building:
1. Open your assigned Render URL (e.g. `https://modpyphp-erp.onrender.com`).
2. You will be redirected to the secure login page.
3. Log in with your Superuser credentials.
