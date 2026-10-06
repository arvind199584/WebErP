# ModPyPhp Smart Hybrid Android App (`apimobile`)

A high-performance **Kotlin & Jetpack Compose Smart Hybrid** Android application built specifically for **ModPyPhp** to inherit 100% of all features, modules, HTMX forms, modals, and reports directly from the cloud backend (`https://modpyphp-erp.onrender.com`) or local office server.

---

## 🛠️ Features & Architecture
- **Full Cloud Backend Parity:** Automatically inherits all modules (Workshop, Fleet, Works, Finance, HR, Store, Admin, HTMX modals, turf machinery service kit, etc.) without needing APK updates for each feature.
- **Server Switcher Dialog:** Easily switch between the official Cloud Backend (`https://modpyphp-erp.onrender.com`), Local Wi-Fi Server (`http://192.168.x.x:8000`), or custom server IP at any time via the top bar ⚙️ button.
- **Session & Cookie Persistence:** Keeps users logged in across app restarts using native Android `CookieManager`.
- **Hardware Camera & File Uploads:** Supports `<input type="file">` for bill receipts, machine photos, and document attachments via `WebChromeClient.onShowFileChooser`.
- **Native PDF & Report Downloads:** Automatic background file download handling via Android's `DownloadManager`.
- **Hardware Back Navigation:** Seamlessly navigates back through pages or double-taps back to exit.
- **Offline / Error Handling:** Friendly connection problem screen with instant Retry and Change URL actions.

---

## 🚀 How to Build & Run

```bash
cd apimobile
./gradlew assembleDebug
```
The compiled APK is placed at:
`apimobile/app/build/outputs/apk/debug/app-debug.apk` and copied to `EnterpriseERP.apk` in the repository root.
