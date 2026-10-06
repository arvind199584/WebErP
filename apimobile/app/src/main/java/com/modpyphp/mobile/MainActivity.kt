package com.modpyphp.mobile

import android.annotation.SuppressLint
import android.app.Activity
import android.app.DownloadManager
import android.content.Context
import android.content.Intent
import android.content.SharedPreferences
import android.graphics.Bitmap
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Environment
import android.view.ViewGroup
import android.webkit.*
import android.widget.Toast
import androidx.activity.ComponentActivity
import androidx.activity.OnBackPressedCallback
import androidx.activity.compose.setContent
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.animation.*
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView

class MainActivity : ComponentActivity() {

    companion object {
        const val PREFS_NAME = "EnterpriseERP_Prefs"
        const val KEY_SERVER_URL = "server_url"
        const val DEFAULT_SERVER_URL = "https://modpyphp-erp.onrender.com"
        const val LOCAL_DEV_URL = "http://192.168.1.3:8000"
        const val EMULATOR_URL = "http://10.0.2.2:8000"
    }

    private lateinit var prefs: SharedPreferences
    private var webViewInstance: WebView? = null
    private var fileChooserCallback: ValueCallback<Array<Uri>>? = null
    private var lastBackPressTime = 0L

    // Launcher for handling HTML <input type="file"> uploads
    private val filePickerLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        if (fileChooserCallback != null) {
            val results: Array<Uri>? = if (result.resultCode == Activity.RESULT_OK && result.data != null) {
                val data = result.data!!
                if (data.clipData != null) {
                    val count = data.clipData!!.itemCount
                    Array(count) { i -> data.clipData!!.getItemAt(i).uri }
                } else if (data.data != null) {
                    arrayOf(data.data!!)
                } else null
            } else null

            fileChooserCallback?.onReceiveValue(results)
            fileChooserCallback = null
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        prefs = getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)

        // Native back navigation: navigate history within WebView or double-tap to exit
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                val webView = webViewInstance
                if (webView != null && webView.canGoBack()) {
                    webView.goBack()
                } else {
                    val now = System.currentTimeMillis()
                    if (now - lastBackPressTime < 2000L) {
                        finish()
                    } else {
                        lastBackPressTime = now
                        Toast.makeText(this@MainActivity, "Press back again to exit", Toast.LENGTH_SHORT).show()
                    }
                }
            }
        })

        setContent {
            HybridAppScreen(
                initialServerUrl = getServerUrl(),
                onSaveServerUrl = { newUrl ->
                    saveServerUrl(newUrl)
                },
                onDownloadFile = { url, ua, cd, mime ->
                    handleDownload(url, ua, cd, mime)
                },
                onOpenFileChooser = { callback, intent ->
                    fileChooserCallback = callback
                    filePickerLauncher.launch(intent)
                },
                onBindWebView = { wv ->
                    webViewInstance = wv
                }
            )
        }
    }

    private fun getServerUrl(): String {
        return prefs.getString(KEY_SERVER_URL, DEFAULT_SERVER_URL) ?: DEFAULT_SERVER_URL
    }

    private fun saveServerUrl(url: String) {
        val cleanUrl = url.trim().trimEnd('/')
        prefs.edit().putString(KEY_SERVER_URL, cleanUrl).apply()
    }

    private fun handleDownload(url: String, userAgent: String?, contentDisposition: String?, mimeType: String?) {
        try {
            val filename = URLUtil.guessFileName(url, contentDisposition, mimeType)
            val request = DownloadManager.Request(Uri.parse(url)).apply {
                setMimeType(mimeType)
                val cookies = CookieManager.getInstance().getCookie(url)
                if (!cookies.isNullOrEmpty()) {
                    addRequestHeader("cookie", cookies)
                }
                if (!userAgent.isNullOrEmpty()) {
                    addRequestHeader("User-Agent", userAgent)
                }
                setDescription("Downloading $filename...")
                setTitle(filename)
                setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, filename)
            }
            val dm = getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
            dm.enqueue(request)
            Toast.makeText(this, "Downloading $filename...", Toast.LENGTH_SHORT).show()
        } catch (e: Exception) {
            try {
                val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                startActivity(intent)
            } catch (ex: Exception) {
                Toast.makeText(this, "Download error: ${e.message}", Toast.LENGTH_LONG).show()
            }
        }
    }

    override fun onDestroy() {
        webViewInstance?.destroy()
        webViewInstance = null
        super.onDestroy()
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@SuppressLint("SetJavaScriptEnabled")
@Composable
fun HybridAppScreen(
    initialServerUrl: String,
    onSaveServerUrl: (String) -> Unit,
    onDownloadFile: (url: String, ua: String?, cd: String?, mime: String?) -> Unit,
    onOpenFileChooser: (ValueCallback<Array<Uri>>?, Intent) -> Unit,
    onBindWebView: (WebView) -> Unit
) {
    var serverUrl by remember { mutableStateOf(initialServerUrl) }
    var webViewRef by remember { mutableStateOf<WebView?>(null) }
    var pageTitle by remember { mutableStateOf("Enterprise ERP") }
    var isLoading by remember { mutableStateOf(true) }
    var loadProgress by remember { mutableIntStateOf(0) }
    var canGoBack by remember { mutableStateOf(false) }
    var hasError by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf("") }
    var showServerDialog by remember { mutableStateOf(false) }

    val isCloudBackend = serverUrl.contains("onrender.com", ignoreCase = true)

    Scaffold(
        topBar = {
            Column {
                TopAppBar(
                    colors = TopAppBarDefaults.topAppBarColors(
                        containerColor = Color(0xFF0F172A),
                        titleContentColor = Color.White,
                        actionIconContentColor = Color.White
                    ),
                    title = {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Surface(
                                shape = RoundedCornerShape(8.dp),
                                color = Color(0xFF0D6EFD).copy(alpha = 0.25f),
                                modifier = Modifier.size(34.dp)
                            ) {
                                Box(contentAlignment = Alignment.Center) {
                                    Text("🏛️", fontSize = 18.sp)
                                }
                            }
                            Spacer(modifier = Modifier.width(10.dp))
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = pageTitle.ifEmpty { "Enterprise ERP" },
                                    fontSize = 15.sp,
                                    fontWeight = FontWeight.Bold,
                                    maxLines = 1,
                                    overflow = TextOverflow.Ellipsis,
                                    color = Color.White
                                )
                                Row(
                                    verticalAlignment = Alignment.CenterVertically,
                                    modifier = Modifier
                                        .clip(RoundedCornerShape(4.dp))
                                        .clickable { showServerDialog = true }
                                ) {
                                    Box(
                                        modifier = Modifier
                                            .size(7.dp)
                                            .background(
                                                if (hasError) Color(0xFFEF4444) else Color(0xFF10B981),
                                                CircleShape
                                            )
                                    )
                                    Spacer(modifier = Modifier.width(5.dp))
                                    Text(
                                        text = if (isCloudBackend) "Cloud Backend" else "Custom Server",
                                        fontSize = 11.sp,
                                        fontWeight = FontWeight.Medium,
                                        color = if (hasError) Color(0xFFFCA5A5) else Color(0xFF94A3B8)
                                    )
                                }
                            }
                        }
                    },
                    actions = {
                        // Back navigation button
                        IconButton(
                            onClick = { webViewRef?.goBack() },
                            enabled = canGoBack
                        ) {
                            Text(
                                text = "◀",
                                fontSize = 16.sp,
                                color = if (canGoBack) Color.White else Color(0xFF475569)
                            )
                        }

                        // Reload button
                        IconButton(onClick = {
                            hasError = false
                            webViewRef?.reload()
                        }) {
                            Text("🔄", fontSize = 15.sp)
                        }

                        // Home button
                        IconButton(onClick = {
                            hasError = false
                            webViewRef?.loadUrl(serverUrl)
                        }) {
                            Text("🏠", fontSize = 16.sp)
                        }

                        // Server settings button
                        IconButton(onClick = { showServerDialog = true }) {
                            Text("⚙️", fontSize = 16.sp)
                        }
                    }
                )

                // Loading progress indicator
                AnimatedVisibility(
                    visible = isLoading && loadProgress < 100,
                    enter = fadeIn(),
                    exit = fadeOut()
                ) {
                    LinearProgressIndicator(
                        progress = { loadProgress / 100f },
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(3.dp),
                        color = Color(0xFF0D6EFD),
                        trackColor = Color(0xFF1E293B)
                    )
                }
            }
        }
    ) { innerPadding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
                .background(Color(0xFFF8FAFC))
        ) {
            // High-Performance Native Android WebView
            AndroidView(
                factory = { context ->
                    WebView(context).apply {
                        layoutParams = ViewGroup.LayoutParams(
                            ViewGroup.LayoutParams.MATCH_PARENT,
                            ViewGroup.LayoutParams.MATCH_PARENT
                        )

                        settings.apply {
                            javaScriptEnabled = true
                            domStorageEnabled = true
                            databaseEnabled = true
                            allowFileAccess = true
                            allowContentAccess = true
                            useWideViewPort = true
                            loadWithOverviewMode = true
                            setSupportZoom(true)
                            builtInZoomControls = true
                            displayZoomControls = false
                            cacheMode = WebSettings.LOAD_DEFAULT
                            mixedContentMode = WebSettings.MIXED_CONTENT_ALWAYS_ALLOW
                            userAgentString = "$userAgentString ModPyPhp-Android-Hybrid/2.0"
                        }

                        val cookieManager = CookieManager.getInstance()
                        cookieManager.setAcceptCookie(true)
                        cookieManager.setAcceptThirdPartyCookies(this, true)

                        webChromeClient = object : WebChromeClient() {
                            override fun onProgressChanged(view: WebView?, newProgress: Int) {
                                loadProgress = newProgress
                                isLoading = newProgress < 100
                            }

                            override fun onReceivedTitle(view: WebView?, title: String?) {
                                if (!title.isNullOrBlank()) {
                                    pageTitle = title
                                }
                            }

                            override fun onShowFileChooser(
                                webView: WebView?,
                                filePathCallback: ValueCallback<Array<Uri>>?,
                                fileChooserParams: FileChooserParams?
                            ): Boolean {
                                val intent = fileChooserParams?.createIntent() ?: Intent(Intent.ACTION_GET_CONTENT).apply {
                                    type = "*/*"
                                    addCategory(Intent.CATEGORY_OPENABLE)
                                }
                                onOpenFileChooser(filePathCallback, intent)
                                return true
                            }
                        }

                        webViewClient = object : WebViewClient() {
                            override fun shouldOverrideUrlLoading(view: WebView?, request: WebResourceRequest?): Boolean {
                                val uri = request?.url ?: return false
                                val scheme = uri.scheme ?: ""
                                if (scheme == "tel" || scheme == "mailto" || scheme == "sms" || scheme == "whatsapp") {
                                    try {
                                        val intent = Intent(Intent.ACTION_VIEW, uri)
                                        context.startActivity(intent)
                                        return true
                                    } catch (e: Exception) {
                                        return false
                                    }
                                }
                                return false
                            }

                            override fun onPageStarted(view: WebView?, url: String?, favicon: Bitmap?) {
                                isLoading = true
                                hasError = false
                            }

                            override fun onPageFinished(view: WebView?, url: String?) {
                                isLoading = false
                                canGoBack = view?.canGoBack() == true
                                CookieManager.getInstance().flush()
                            }

                            override fun onReceivedError(
                                view: WebView?,
                                request: WebResourceRequest?,
                                error: WebResourceError?
                            ) {
                                if (request?.isForMainFrame == true) {
                                    hasError = true
                                    errorMessage = error?.description?.toString() ?: "Failed to connect to backend server."
                                }
                            }
                        }

                        setDownloadListener { url, userAgent, contentDisposition, mimetype, _ ->
                            onDownloadFile(url, userAgent, contentDisposition, mimetype)
                        }

                        webViewRef = this
                        onBindWebView(this)
                        loadUrl(serverUrl)
                    }
                },
                modifier = Modifier.fillMaxSize()
            )

            // Sleek Offline / Error Screen
            if (hasError) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .background(Color(0xFF0F172A).copy(alpha = 0.95f))
                        .padding(24.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Card(
                        shape = RoundedCornerShape(20.dp),
                        colors = CardDefaults.cardColors(containerColor = Color(0xFF1E293B)),
                        elevation = CardDefaults.cardElevation(defaultElevation = 8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(
                            modifier = Modifier.padding(24.dp),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            Surface(
                                shape = CircleShape,
                                color = Color(0xFFEF4444).copy(alpha = 0.2f),
                                modifier = Modifier.size(60.dp)
                            ) {
                                Box(contentAlignment = Alignment.Center) {
                                    Text("📡", fontSize = 28.sp)
                                }
                            }
                            Spacer(modifier = Modifier.height(16.dp))
                            Text(
                                text = "Unable to Reach Server",
                                fontSize = 18.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color.White
                            )
                            Spacer(modifier = Modifier.height(8.dp))
                            Text(
                                text = errorMessage.ifEmpty { "Connection timed out or host is unreachable." },
                                fontSize = 13.sp,
                                color = Color(0xFF94A3B8),
                                textAlign = androidx.compose.ui.text.style.TextAlign.Center
                            )
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                text = serverUrl,
                                fontSize = 12.sp,
                                color = Color(0xFF38BDF8),
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
                            Spacer(modifier = Modifier.height(24.dp))
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                OutlinedButton(
                                    onClick = { showServerDialog = true },
                                    modifier = Modifier.weight(1f),
                                    colors = ButtonDefaults.outlinedButtonColors(contentColor = Color.White)
                                ) {
                                    Text("⚙️ Change URL", fontSize = 13.sp)
                                }
                                Button(
                                    onClick = {
                                        hasError = false
                                        webViewRef?.reload()
                                    },
                                    modifier = Modifier.weight(1f),
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF0D6EFD))
                                ) {
                                    Text("🔄 Retry", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Server Switcher / Settings Dialog
    if (showServerDialog) {
        var inputUrl by remember { mutableStateOf(serverUrl) }

        AlertDialog(
            onDismissRequest = { showServerDialog = false },
            title = {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text("⚙️ Server Configuration", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                }
            },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Text(
                        text = "Choose your target backend or enter a custom address:",
                        fontSize = 13.sp,
                        color = Color(0xFF64748B)
                    )

                    // Preset buttons
                    Surface(
                        shape = RoundedCornerShape(10.dp),
                        color = Color(0xFFF1F5F9),
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { inputUrl = MainActivity.DEFAULT_SERVER_URL }
                    ) {
                        Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                            Text("☁️", fontSize = 18.sp)
                            Spacer(modifier = Modifier.width(10.dp))
                            Column {
                                Text("Official Cloud Backend", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                                Text("modpyphp-erp.onrender.com", fontSize = 11.sp, color = Color(0xFF64748B))
                            }
                        }
                    }

                    Surface(
                        shape = RoundedCornerShape(10.dp),
                        color = Color(0xFFF1F5F9),
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { inputUrl = MainActivity.LOCAL_DEV_URL }
                    ) {
                        Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                            Text("🏠", fontSize = 18.sp)
                            Spacer(modifier = Modifier.width(10.dp))
                            Column {
                                Text("Local Wi-Fi Server", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                                Text("http://192.168.1.3:8000", fontSize = 11.sp, color = Color(0xFF64748B))
                            }
                        }
                    }

                    OutlinedTextField(
                        value = inputUrl,
                        onValueChange = { inputUrl = it },
                        label = { Text("Server URL or IP") },
                        placeholder = { Text("https://example.com or http://192.168.x.x:8000") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        val cleaned = inputUrl.trim().trimEnd('/')
                        if (cleaned.isNotEmpty()) {
                            serverUrl = cleaned
                            onSaveServerUrl(cleaned)
                            showServerDialog = false
                            hasError = false
                            webViewRef?.loadUrl(cleaned)
                        }
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF0D6EFD))
                ) {
                    Text("Save & Connect")
                }
            },
            dismissButton = {
                TextButton(onClick = { showServerDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }
}
