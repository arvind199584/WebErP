package com.modpyphp.mobile.updater

import android.app.DownloadManager
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.net.Uri
import android.os.Build
import android.os.Environment
import android.provider.Settings
import android.widget.Toast
import androidx.core.content.FileProvider
import com.google.gson.Gson
import com.google.gson.annotations.SerializedName
import com.modpyphp.mobile.BuildConfig
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.OkHttpClient
import okhttp3.Request
import java.io.File
import java.util.concurrent.TimeUnit

data class UpdateInfo(
    @SerializedName("versionName") val versionName: String = "",
    @SerializedName("versionCode") val versionCode: Int = 0,
    @SerializedName("apkUrl") val apkUrl: String = "",
    @SerializedName("releaseNotes") val releaseNotes: String? = null
)

object AppUpdateManager {

    private val client = OkHttpClient.Builder()
        .connectTimeout(10, TimeUnit.SECONDS)
        .readTimeout(10, TimeUnit.SECONDS)
        .build()

    private const val GITHUB_VERSION_URL =
        "https://raw.githubusercontent.com/arvind199584/WebErP/main/version.json"

    suspend fun checkUpdate(serverBaseUrl: String): UpdateInfo? = withContext(Dispatchers.IO) {
        val urlsToTry = mutableListOf<String>()
        val cleanServer = serverBaseUrl.trim().trimEnd('/')
        if (cleanServer.isNotEmpty()) {
            urlsToTry.add("$cleanServer/version.json")
        }
        urlsToTry.add(GITHUB_VERSION_URL)

        for (url in urlsToTry) {
            try {
                val request = Request.Builder()
                    .url(url)
                    .header("Cache-Control", "no-cache")
                    .build()
                val response = client.newCall(request).execute()
                if (response.isSuccessful) {
                    val bodyString = response.body?.string() ?: continue
                    val updateInfo = Gson().fromJson(bodyString, UpdateInfo::class.java)
                    if (updateInfo != null && updateInfo.versionCode > BuildConfig.VERSION_CODE) {
                        return@withContext updateInfo
                    }
                }
            } catch (e: Exception) {
                // Continue to fallback url
            }
        }
        null
    }

    fun startDownloadAndInstall(context: Context, updateInfo: UpdateInfo, onStatus: (String) -> Unit) {
        try {
            val apkUrl = updateInfo.apkUrl
            val filename = "Daredevil-${updateInfo.versionName}.apk"

            val downloadUri = Uri.parse(apkUrl)
            val request = DownloadManager.Request(downloadUri).apply {
                setTitle("Daredevil Update ${updateInfo.versionName}")
                setDescription("Downloading update package...")
                setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                setMimeType("application/vnd.android.package-archive")
                setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, filename)
            }

            val dm = context.getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
            val downloadId = dm.enqueue(request)
            onStatus("Downloading update (${updateInfo.versionName})...")

            val onCompleteReceiver = object : BroadcastReceiver() {
                override fun onReceive(ctxt: Context, intent: Intent) {
                    val id = intent.getLongExtra(DownloadManager.EXTRA_DOWNLOAD_ID, -1L)
                    if (id == downloadId) {
                        try {
                            ctxt.unregisterReceiver(this)
                        } catch (_: Exception) {}

                        val query = DownloadManager.Query().setFilterById(downloadId)
                        val cursor = dm.query(query)
                        if (cursor != null && cursor.moveToFirst()) {
                            val statusIndex = cursor.getColumnIndex(DownloadManager.COLUMN_STATUS)
                            if (statusIndex != -1 && cursor.getInt(statusIndex) == DownloadManager.STATUS_SUCCESSFUL) {
                                val uriStringIndex = cursor.getColumnIndex(DownloadManager.COLUMN_LOCAL_URI)
                                val localUriString = if (uriStringIndex != -1) cursor.getString(uriStringIndex) else null
                                cursor.close()

                                if (localUriString != null) {
                                    val localFile = File(Uri.parse(localUriString).path ?: "")
                                    installApk(ctxt, localFile)
                                } else {
                                    val fallbackFile = File(
                                        Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS),
                                        filename
                                    )
                                    installApk(ctxt, fallbackFile)
                                }
                            } else {
                                cursor.close()
                                Toast.makeText(ctxt, "Download was interrupted or cancelled", Toast.LENGTH_SHORT).show()
                            }
                        }
                    }
                }
            }

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                context.registerReceiver(
                    onCompleteReceiver,
                    IntentFilter(DownloadManager.ACTION_DOWNLOAD_COMPLETE),
                    Context.RECEIVER_EXPORTED
                )
            } else {
                context.registerReceiver(
                    onCompleteReceiver,
                    IntentFilter(DownloadManager.ACTION_DOWNLOAD_COMPLETE)
                )
            }

        } catch (e: Exception) {
            Toast.makeText(context, "Error starting download: ${e.message}", Toast.LENGTH_LONG).show()
        }
    }

    fun installApk(context: Context, file: File) {
        try {
            if (!file.exists()) {
                Toast.makeText(context, "Downloaded installer file not found", Toast.LENGTH_LONG).show()
                return
            }

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                if (!context.packageManager.canRequestPackageInstalls()) {
                    Toast.makeText(
                        context,
                        "Please allow 'Install unknown apps' permission to install the Daredevil update",
                        Toast.LENGTH_LONG
                    ).show()
                    val permissionIntent = Intent(Settings.ACTION_MANAGE_UNKNOWN_APP_SOURCES).apply {
                        data = Uri.parse("package:${context.packageName}")
                        flags = Intent.FLAG_ACTIVITY_NEW_TASK
                    }
                    context.startActivity(permissionIntent)
                    return
                }
            }

            val apkUri: Uri = FileProvider.getUriForFile(
                context,
                "${context.packageName}.fileprovider",
                file
            )

            val installIntent = Intent(Intent.ACTION_VIEW).apply {
                setDataAndType(apkUri, "application/vnd.android.package-archive")
                flags = Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_ACTIVITY_NEW_TASK
            }

            context.startActivity(installIntent)

        } catch (e: Exception) {
            Toast.makeText(context, "Installation error: ${e.message}", Toast.LENGTH_LONG).show()
        }
    }
}
