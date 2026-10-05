# ModPyPhp Native Android App (`apimobile`)

A pure **Kotlin & Jetpack Compose** native Android application built specifically for the **ModPyPhp** PHP & PostgreSQL Integrated Office Management Backend without any `npm` or JavaScript dependencies.

---

## 🛠️ Tech Stack & Architecture
- **Language:** Kotlin 2.1.0
- **UI Framework:** Jetpack Compose (Material3)
- **Networking:** Retrofit 2.11.0 + OkHttp 4.12.0 + Gson
- **Async Execution:** Kotlin Coroutines (`kotlinx.coroutines`)
- **Min SDK:** 24 (Android 7.0+)
- **Target SDK:** 35 (Android 15+)
- **Build System:** Gradle (Kotlin DSL `build.gradle.kts` + Version Catalog `libs.versions.toml`)

---

## 📁 Directory Structure

```
apimobile/
├── build.gradle.kts           # Root Gradle configuration
├── settings.gradle.kts        # Root Gradle module inclusions
├── gradle/
│   └── libs.versions.toml     # Centralized Version Catalog
└── app/
    ├── build.gradle.kts       # App module build script
    └── src/
        └── main/
            ├── AndroidManifest.xml
            └── java/com/modpyphp/mobile/
                ├── MainActivity.kt               # Jetpack Compose Navigation & Screens
                ├── data/
                │   ├── api/
                │   │   └── ModPyPhpApiService.kt # Retrofit REST Interface to api/index.php
                │   └── models/
                │       └── ApiModels.kt          # Kotlin Data Classes (User, Stats, Modules)
                └── ui/
                    └── theme/
                        └── Theme.kt              # ModPyPhp Material3 Color Scheme
```

---

## 🚀 How to Build & Run

### Option 1: Open in Android Studio
1. Launch **Android Studio**.
2. Select **Open** and choose the `apimobile` directory (`F:\Research\ModPyPhp\apimobile`).
3. Allow Gradle sync to download Kotlin and Android dependencies.
4. Select an Android Emulator or connected USB Android device.
5. Click **Run 'app'** (`Shift + F10`).

### Option 2: Command Line (Gradle)
```bash
cd apimobile
./gradlew assembleDebug
```
The compiled APK will be output to:
`apimobile/app/build/outputs/apk/debug/app-debug.apk`

---

## 🌐 Connecting to the PHP Backend
1. Start your local PHP server using `start-server.bat` or `php -S 0.0.0.0:8000 -t .`.
2. On the **Login Screen**, enter your local computer's IP address (e.g., `http://192.168.1.3:8000`).
3. Log in with your standard ModPyPhp user credentials (`admin` / `admin123`).
