package com.modpyphp.mobile

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
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
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.modpyphp.mobile.data.api.ModPyPhpApiService
import com.modpyphp.mobile.data.models.*
import com.modpyphp.mobile.ui.theme.*
import kotlinx.coroutines.launch
import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.*

enum class Screen(val title: String, val icon: String) {
    DASHBOARD("Dashboard", "📊"),
    WORKSHOP("Workshop", "🚜"),
    STORE("Store", "📦"),
    FINANCE("Finance", "💰"),
    HR("HR", "👥"),
    WORKS("Works", "🛠️"),
    ADMIN("Admin", "⚙️")
}

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            ModPyPhpTheme {
                MainAppScreen()
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainAppScreen() {
    var serverUrl by remember { mutableStateOf(ModPyPhpApiService.DEFAULT_BASE_URL) }
    var currentUser by remember { mutableStateOf<User?>(null) }
    var currentScreen by remember { mutableStateOf(Screen.DASHBOARD) }
    var apiService by remember { mutableStateOf<ModPyPhpApiService?>(null) }

    Surface(
        modifier = Modifier.fillMaxSize(),
        color = MaterialTheme.colorScheme.background
    ) {
        if (currentUser == null || apiService == null) {
            LoginScreen(
                defaultUrl = serverUrl,
                onLoginSuccess = { user, url, service ->
                    currentUser = user
                    serverUrl = url
                    apiService = service
                    currentScreen = Screen.DASHBOARD
                }
            )
        } else {
            Scaffold(
                topBar = {
                    TopAppBar(
                        title = {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Surface(
                                    shape = RoundedCornerShape(8.dp),
                                    color = Color(0xFF0D6EFD).copy(alpha = 0.2f),
                                    modifier = Modifier.size(36.dp)
                                ) {
                                    Box(contentAlignment = Alignment.Center) {
                                        Text("🏛️", fontSize = 20.sp)
                                    }
                                }
                                Spacer(modifier = Modifier.width(10.dp))
                                Column {
                                    Text(
                                        text = "Enterprise ERP",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 17.sp,
                                        color = Color.White
                                    )
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Box(
                                            modifier = Modifier
                                                .size(7.dp)
                                                .clip(CircleShape)
                                                .background(Color(0xFF10B981))
                                        )
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text(
                                            text = "Render Cloud • Neon DB",
                                            fontSize = 11.sp,
                                            color = Color(0xFF94A3B8)
                                        )
                                    }
                                }
                            }
                        },
                        actions = {
                            // User role chip
                            Surface(
                                shape = RoundedCornerShape(12.dp),
                                color = Color(0xFF334155),
                                modifier = Modifier.padding(end = 6.dp)
                            ) {
                                Row(
                                    verticalAlignment = Alignment.CenterVertically,
                                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                                ) {
                                    Text("👤", fontSize = 12.sp)
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text(
                                        text = currentUser?.username ?: "User",
                                        fontSize = 12.sp,
                                        fontWeight = FontWeight.SemiBold,
                                        color = Color.White
                                    )
                                }
                            }
                            // Logout button
                            IconButton(onClick = {
                                currentUser = null
                                apiService = null
                            }) {
                                Text("🚪", fontSize = 18.sp)
                            }
                        },
                        colors = TopAppBarDefaults.topAppBarColors(
                            containerColor = ErpDeepNavy,
                            titleContentColor = Color.White,
                            actionIconContentColor = Color.White
                        )
                    )
                },
                bottomBar = {
                    // Scrollable bottom bar for all ERP modules
                    Surface(
                        tonalElevation = 8.dp,
                        color = ErpSurface,
                        border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder)
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .horizontalScroll(rememberScrollState())
                                .padding(vertical = 4.dp, horizontal = 6.dp),
                            horizontalArrangement = Arrangement.spacedBy(4.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Screen.values().forEach { screen ->
                                val isSelected = currentScreen == screen
                                Surface(
                                    shape = RoundedCornerShape(10.dp),
                                    color = if (isSelected) ErpPrimary.copy(alpha = 0.15f) else Color.Transparent,
                                    modifier = Modifier.clickable { currentScreen = screen }
                                ) {
                                    Column(
                                        horizontalAlignment = Alignment.CenterHorizontally,
                                        modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp)
                                    ) {
                                        Text(screen.icon, fontSize = 20.sp)
                                        Spacer(modifier = Modifier.height(2.dp))
                                        Text(
                                            text = screen.title,
                                            fontSize = 11.sp,
                                            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                                            color = if (isSelected) ErpPrimary else ErpTextMuted
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            ) { padding ->
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(padding)
                        .background(MaterialTheme.colorScheme.background)
                ) {
                    when (currentScreen) {
                        Screen.DASHBOARD -> DashboardContent(
                            apiService = apiService!!,
                            user = currentUser!!,
                            onNavigateToScreen = { currentScreen = it }
                        )
                        Screen.WORKSHOP -> WorkshopModuleContent(apiService!!)
                        Screen.STORE -> StoreModuleContent(apiService!!)
                        Screen.FINANCE -> FinanceModuleContent(apiService!!)
                        Screen.HR -> HRModuleContent(apiService!!)
                        Screen.WORKS -> WorksModuleContent(apiService!!)
                        Screen.ADMIN -> AdminModuleContent(apiService!!)
                    }
                }
            }
        }
    }
}

/* =====================================================================
 * LOGIN SCREEN - Styled matching ModPyPhp index.php & login.php
 * ===================================================================== */
@Composable
fun LoginScreen(
    defaultUrl: String,
    onLoginSuccess: (User, String, ModPyPhpApiService) -> Unit
) {
    var serverUrl by remember { mutableStateOf(defaultUrl) }
    var username by remember { mutableStateOf("daredevil") }
    var password by remember { mutableStateOf("admin123") }
    var isLoading by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(
                Brush.verticalGradient(
                    colors = listOf(ErpDeepNavy, Color(0xFF1E293B), ErpBackground)
                )
            ),
        contentAlignment = Alignment.Center
    ) {
        Card(
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(containerColor = Color.White),
            elevation = CardDefaults.cardElevation(defaultElevation = 8.dp),
            modifier = Modifier
                .fillMaxWidth(0.92f)
                .padding(vertical = 24.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(24.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                // Enterprise Header Brand
                Surface(
                    shape = RoundedCornerShape(14.dp),
                    color = ErpPrimary.copy(alpha = 0.1f),
                    modifier = Modifier.size(64.dp)
                ) {
                    Box(contentAlignment = Alignment.Center) {
                        Text("🏛️", fontSize = 36.sp)
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))

                Text(
                    text = "Enterprise ERP",
                    fontSize = 24.sp,
                    fontWeight = FontWeight.Bold,
                    color = ErpTextMain
                )
                Text(
                    text = "Integrated Operations & Governance System",
                    fontSize = 12.sp,
                    color = ErpTextMuted
                )

                Spacer(modifier = Modifier.height(20.dp))

                // Server URL input
                OutlinedTextField(
                    value = serverUrl,
                    onValueChange = { serverUrl = it },
                    label = { Text("Backend Server URL") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    shape = RoundedCornerShape(10.dp)
                )

                Spacer(modifier = Modifier.height(12.dp))

                // Username input
                OutlinedTextField(
                    value = username,
                    onValueChange = { username = it },
                    label = { Text("Username") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    shape = RoundedCornerShape(10.dp)
                )

                Spacer(modifier = Modifier.height(12.dp))

                // Password input
                OutlinedTextField(
                    value = password,
                    onValueChange = { password = it },
                    label = { Text("Password") },
                    visualTransformation = PasswordVisualTransformation(),
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    shape = RoundedCornerShape(10.dp)
                )

                Spacer(modifier = Modifier.height(14.dp))

                // Quick Preset Accounts for One-Tap Testing
                Text(
                    text = "Quick Demo Accounts:",
                    fontSize = 12.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = ErpTextMuted,
                    modifier = Modifier.align(Alignment.Start)
                )
                Spacer(modifier = Modifier.height(6.dp))
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    AssistChip(
                        onClick = {
                            username = "daredevil"
                            password = "admin123"
                        },
                        label = { Text("👑 Superuser (daredevil)", fontSize = 11.sp) }
                    )
                    AssistChip(
                        onClick = {
                            username = "hemant"
                            password = "admin123"
                        },
                        label = { Text("👷 Staff (hemant)", fontSize = 11.sp) }
                    )
                    AssistChip(
                        onClick = {
                            username = "sopan"
                            password = "admin123"
                        },
                        label = { Text("👔 Manager (sopan)", fontSize = 11.sp) }
                    )
                }

                errorMessage?.let {
                    Spacer(modifier = Modifier.height(12.dp))
                    Surface(
                        shape = RoundedCornerShape(8.dp),
                        color = Color(0xFFFEE2E2),
                        border = androidx.compose.foundation.BorderStroke(1.dp, Color(0xFFFCA5A5)),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text(
                            text = it,
                            color = Color(0xFFB91C1C),
                            fontSize = 13.sp,
                            modifier = Modifier.padding(10.dp)
                        )
                    }
                }

                Spacer(modifier = Modifier.height(20.dp))

                // Login Submit Button
                Button(
                    onClick = {
                        scope.launch {
                            isLoading = true
                            errorMessage = null
                            try {
                                val api = ModPyPhpApiService.create(serverUrl)
                                val res = api.login(mapOf("username" to username.trim(), "password" to password.trim()))
                                if (res.success && res.user != null) {
                                    onLoginSuccess(res.user, serverUrl, api)
                                } else {
                                    errorMessage = res.error ?: "Invalid credentials"
                                }
                            } catch (e: Exception) {
                                errorMessage = "Connection failed: ${e.localizedMessage}"
                            } finally {
                                isLoading = false
                            }
                        }
                    },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(48.dp),
                    shape = RoundedCornerShape(10.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = ErpPrimary),
                    enabled = !isLoading
                ) {
                    if (isLoading) {
                        CircularProgressIndicator(modifier = Modifier.size(22.dp), color = Color.White, strokeWidth = 2.dp)
                    } else {
                        Text("Log In to Enterprise ERP", fontSize = 15.sp, fontWeight = FontWeight.Bold)
                    }
                }

                Spacer(modifier = Modifier.height(10.dp))

                Text(
                    text = "Connected to Render Cloud Backend",
                    fontSize = 11.sp,
                    color = ErpTextMuted
                )
            }
        }
    }
}

/* =====================================================================
 * DASHBOARD CONTENT - Direct mirror of index.php & dashboard.php
 * ===================================================================== */
@Composable
fun DashboardContent(
    apiService: ModPyPhpApiService,
    user: User,
    onNavigateToScreen: (Screen) -> Unit
) {
    var stats by remember { mutableStateOf<SystemStats?>(null) }
    var isLoading by remember { mutableStateOf(true) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    val refreshDashboard = {
        scope.launch {
            isLoading = true
            errorMessage = null
            try {
                val res = apiService.getDashboardStats()
                if (res.success && res.data != null) {
                    stats = res.data
                } else {
                    errorMessage = res.error ?: "Failed to load dashboard data"
                }
            } catch (e: Exception) {
                errorMessage = e.localizedMessage
            } finally {
                isLoading = false
            }
        }
    }

    LaunchedEffect(Unit) {
        refreshDashboard()
    }

    LazyColumn(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        // 1. Welcome Banner Header (Matching dashboard.php)
        item {
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = Color.White),
                border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder)
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.Top
                    ) {
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                text = "Enterprise ERP Dashboard",
                                fontSize = 20.sp,
                                fontWeight = FontWeight.Bold,
                                color = ErpTextMain
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "Welcome, ${user.firstName ?: user.username} • Office ID: ${user.officeId ?: 1}",
                                fontSize = 13.sp,
                                color = ErpTextMuted
                            )
                        }
                        Surface(
                            shape = RoundedCornerShape(8.dp),
                            color = ErpPrimary.copy(alpha = 0.1f),
                            border = androidx.compose.foundation.BorderStroke(1.dp, ErpPrimary.copy(alpha = 0.3f))
                        ) {
                            Text(
                                text = "ROLE: ${(user.role ?: "USER").uppercase()}",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold,
                                color = ErpPrimary,
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                            )
                        }
                    }
                    Spacer(modifier = Modifier.height(6.dp))
                    val dateStr = SimpleDateFormat("EEE, dd MMM yyyy", Locale.getDefault()).format(Date())
                    Text(
                        text = "📅 $dateStr • Integrated Operations & Governance System",
                        fontSize = 12.sp,
                        color = ErpTextMuted
                    )
                }
            }
        }

        // 2. Alert / Fast Action Banner: Machine Maintenance Tracker (Matching dashboard.php)
        item {
            Card(
                shape = RoundedCornerShape(12.dp),
                colors = CardDefaults.cardColors(containerColor = Color(0xFFFEF3C7)),
                border = androidx.compose.foundation.BorderStroke(1.dp, Color(0xFFFCD34D)),
                modifier = Modifier.clickable { onNavigateToScreen(Screen.WORKSHOP) }
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(14.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Surface(
                        shape = CircleShape,
                        color = Color(0xFFF59E0B),
                        modifier = Modifier.size(40.dp)
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Text("🚜", fontSize = 20.sp)
                        }
                    }
                    Spacer(modifier = Modifier.width(12.dp))
                    Column(modifier = Modifier.weight(1f)) {
                        Text(
                            text = "Machinery Maintenance & Service Tracker",
                            fontWeight = FontWeight.Bold,
                            fontSize = 14.sp,
                            color = Color(0xFF78350F)
                        )
                        Text(
                            text = "Tap to view machine fleet status, engine hours & repair logs.",
                            fontSize = 12.sp,
                            color = Color(0xFF92400E)
                        )
                    }
                    Text("›", fontSize = 24.sp, color = Color(0xFF78350F))
                }
            }
        }

        // Loading or Error State
        if (isLoading) {
            item {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(200.dp),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator(color = ErpPrimary)
                }
            }
        } else if (errorMessage != null) {
            item {
                Card(
                    colors = CardDefaults.cardColors(containerColor = Color(0xFFFEE2E2)),
                    border = androidx.compose.foundation.BorderStroke(1.dp, Color(0xFFFCA5A5)),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text("Connection Alert", fontWeight = FontWeight.Bold, color = Color(0xFFB91C1C))
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(errorMessage ?: "", fontSize = 13.sp, color = Color(0xFF7F1D1D))
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(
                            onClick = { refreshDashboard() },
                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
                        ) {
                            Text("Retry Connection")
                        }
                    }
                }
            }
        } else if (stats != null) {
            // 3. Executive KPI Overview Cards Grid (Includes Machines & Inventory)
            item {
                Text(
                    text = "Executive KPI Overview",
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = ErpTextMain
                )
            }

            item {
                val statCards = listOf(
                    ErpStatItem("Workshop Fleet", (stats!!.machines ?: 50).toString(), "🚜", ErpWarning, Screen.WORKSHOP),
                    ErpStatItem("Store Catalog", (stats!!.inventory ?: 85).toString(), "📦", ErpInfo, Screen.STORE),
                    ErpStatItem("Employees", stats!!.employees.toString(), "👥", ErpPrimary, Screen.HR),
                    ErpStatItem("Offices", stats!!.offices.toString(), "🏢", ErpSuccess, Screen.ADMIN),
                    ErpStatItem("Agreements", stats!!.agreements.toString(), "📄", ErpPurple, Screen.WORKS),
                    ErpStatItem("Bills", stats!!.bills.toString(), "🧾", ErpDanger, Screen.FINANCE)
                )

                LazyVerticalGrid(
                    columns = GridCells.Fixed(2),
                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp),
                    modifier = Modifier.height(260.dp)
                ) {
                    items(statCards) { card ->
                        ErpKpiCard(item = card) {
                            onNavigateToScreen(card.targetScreen)
                        }
                    }
                }
            }

            // 4. ERP Operational Hub Modules (Grid mirroring dashboard.php sections)
            item {
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "Operational Modules",
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = ErpTextMain
                )
            }

            item {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    ErpModuleCard(
                        title = "Workshop & Machinery",
                        subtitle = "Machine fleet, operational status, servicing & run duration",
                        icon = "🚜",
                        accentColor = ErpWarning,
                        onClick = { onNavigateToScreen(Screen.WORKSHOP) }
                    )
                    ErpModuleCard(
                        title = "Store & Inventory",
                        subtitle = "Inventory items, materials catalog, fuel, units & categories",
                        icon = "📦",
                        accentColor = ErpInfo,
                        onClick = { onNavigateToScreen(Screen.STORE) }
                    )
                    ErpModuleCard(
                        title = "Human Resource Master",
                        subtitle = "Employee directory, attendance logs & staff assignments",
                        icon = "👥",
                        accentColor = ErpPrimary,
                        onClick = { onNavigateToScreen(Screen.HR) }
                    )
                    ErpModuleCard(
                        title = "Works & Engineering",
                        subtitle = "Agreements registry, work orders & contract sanctions",
                        icon = "🛠️",
                        accentColor = ErpPurple,
                        onClick = { onNavigateToScreen(Screen.WORKS) }
                    )
                    ErpModuleCard(
                        title = "Finance & Budgeting",
                        subtitle = "Financial year allocations, bill vouchers & expenditures",
                        icon = "💰",
                        accentColor = ErpSuccess,
                        onClick = { onNavigateToScreen(Screen.FINANCE) }
                    )
                    ErpModuleCard(
                        title = "Administration & Master Data",
                        subtitle = "Offices directory, contractor agencies & cloud status",
                        icon = "🏢",
                        accentColor = Color(0xFF6366F1),
                        onClick = { onNavigateToScreen(Screen.ADMIN) }
                    )
                }
            }

            // 5. Recent Activity Logs Timeline (from activity_logs table)
            if (!stats!!.recentActivity.isNullOrEmpty()) {
                item {
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        text = "Recent System Activity",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = ErpTextMain
                    )
                }

                items(stats!!.recentActivity!!) { log ->
                    Card(
                        shape = RoundedCornerShape(10.dp),
                        colors = CardDefaults.cardColors(containerColor = Color.White),
                        border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(12.dp),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = "Table: ${log.tableName?.uppercase() ?: "SYSTEM"}",
                                    fontWeight = FontWeight.SemiBold,
                                    fontSize = 14.sp,
                                    color = ErpTextMain
                                )
                                Text(
                                    text = log.changedAt ?: "Recent",
                                    fontSize = 11.sp,
                                    color = ErpTextMuted
                                )
                            }
                            Surface(
                                shape = RoundedCornerShape(6.dp),
                                color = when (log.action?.uppercase()) {
                                    "INSERT" -> Color(0xFFD1FAE5)
                                    "UPDATE" -> Color(0xFFFEF3C7)
                                    "DELETE" -> Color(0xFFFEE2E2)
                                    else -> Color(0xFFE2E8F0)
                                }
                            ) {
                                Text(
                                    text = log.action?.uppercase() ?: "ACTION",
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = when (log.action?.uppercase()) {
                                        "INSERT" -> Color(0xFF065F46)
                                        "UPDATE" -> Color(0xFF92400E)
                                        "DELETE" -> Color(0xFF991B1B)
                                        else -> Color(0xFF475569)
                                    },
                                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

data class ErpStatItem(
    val title: String,
    val value: String,
    val icon: String,
    val color: Color,
    val targetScreen: Screen
)

@Composable
fun ErpKpiCard(item: ErpStatItem, onClick: () -> Unit) {
    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onClick() }
    ) {
        Row(modifier = Modifier.fillMaxSize()) {
            Box(
                modifier = Modifier
                    .width(5.dp)
                    .fillMaxHeight()
                    .background(item.color)
            )
            Column(
                modifier = Modifier
                    .padding(14.dp)
                    .fillMaxWidth()
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = item.title.uppercase(),
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold,
                        color = item.color
                    )
                    Text(item.icon, fontSize = 18.sp)
                }
                Spacer(modifier = Modifier.height(6.dp))
                Text(
                    text = item.value,
                    fontSize = 24.sp,
                    fontWeight = FontWeight.Bold,
                    color = ErpTextMain
                )
            }
        }
    }
}

@Composable
fun ErpModuleCard(
    title: String,
    subtitle: String,
    icon: String,
    accentColor: Color,
    onClick: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onClick() }
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Surface(
                shape = CircleShape,
                color = accentColor.copy(alpha = 0.12f),
                modifier = Modifier.size(44.dp)
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Text(icon, fontSize = 22.sp)
                }
            }
            Spacer(modifier = Modifier.width(14.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = title,
                    fontWeight = FontWeight.Bold,
                    fontSize = 15.sp,
                    color = ErpTextMain
                )
                Text(
                    text = subtitle,
                    fontSize = 12.sp,
                    color = ErpTextMuted,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }
            Text("›", fontSize = 24.sp, color = ErpTextMuted)
        }
    }
}

/* =====================================================================
 * WORKSHOP & MACHINERY MODULE SCREEN
 * Tabs: Fleet, Run Logs, Service Logs, Water Logs
 * ===================================================================== */
enum class WorkshopTab(val title: String, val icon: String) {
    FLEET("Fleet", "🚜"),
    RUN_LOGS("Run Logs", "⏱️"),
    SERVICES("Service Logs", "🔧"),
    WATER_LOGS("Water Logs", "💧")
}

@Composable
fun WorkshopModuleContent(apiService: ModPyPhpApiService) {
    var data by remember { mutableStateOf<WorkshopData?>(null) }
    var selectedTab by remember { mutableStateOf(WorkshopTab.FLEET) }
    var searchQuery by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(true) }
    var errorMsg by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            val res = apiService.getWorkshopData()
            if (res.success) data = res.data else errorMsg = res.error
        } catch (e: Exception) {
            errorMsg = e.localizedMessage
        } finally {
            isLoading = false
        }
    }

    ModuleScreenLayout(
        title = "Workshop & Machinery",
        subtitle = "Fleet inventory, daily run logs, servicing & water consumption",
        isLoading = isLoading,
        errorMsg = errorMsg
    ) {
        Column(modifier = Modifier.fillMaxSize()) {
            // Tab Selector Chips
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                WorkshopTab.values().forEach { tab ->
                    val isSelected = selectedTab == tab
                    FilterChip(
                        selected = isSelected,
                        onClick = { selectedTab = tab },
                        label = { Text("${tab.icon} ${tab.title}", fontSize = 12.sp, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium) },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = ErpWarning.copy(alpha = 0.2f),
                            selectedLabelColor = Color(0xFF78350F)
                        )
                    )
                }
            }

            Spacer(modifier = Modifier.height(10.dp))

            // Search Bar
            OutlinedTextField(
                value = searchQuery,
                onValueChange = { searchQuery = it },
                placeholder = {
                    Text(
                        when (selectedTab) {
                            WorkshopTab.FLEET -> "Search machine fleet by name or office..."
                            WorkshopTab.RUN_LOGS -> "Search run logs by machine name..."
                            WorkshopTab.SERVICES -> "Search service logs by machine or type..."
                            WorkshopTab.WATER_LOGS -> "Search water logs by date..."
                        }
                    )
                },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(10.dp),
                singleLine = true,
                leadingIcon = { Text("🔍") }
            )

            Spacer(modifier = Modifier.height(12.dp))

            when (selectedTab) {
                // 1. MACHINE FLEET TAB
                WorkshopTab.FLEET -> {
                    val filteredMachines = remember(data?.machines, searchQuery) {
                        val list = data?.machines ?: emptyList()
                        if (searchQuery.isBlank()) list
                        else list.filter {
                            (it.name ?: "").contains(searchQuery, ignoreCase = true) ||
                            (it.officeName ?: "").contains(searchQuery, ignoreCase = true) ||
                            (it.status ?: "").contains(searchQuery, ignoreCase = true)
                        }
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Machine Fleet", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
                        Text("Total: ${filteredMachines.size}", fontSize = 12.sp, color = ErpTextMuted)
                    }

                    Spacer(modifier = Modifier.height(8.dp))

                    LazyColumn(
                        verticalArrangement = Arrangement.spacedBy(10.dp),
                        modifier = Modifier.weight(1f)
                    ) {
                        items(filteredMachines) { machine ->
                            Card(
                                shape = RoundedCornerShape(10.dp),
                                colors = CardDefaults.cardColors(containerColor = Color.White),
                                border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(14.dp),
                                    verticalAlignment = Alignment.CenterVertically,
                                    horizontalArrangement = Arrangement.SpaceBetween
                                ) {
                                    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.weight(1f)) {
                                        Surface(
                                            shape = CircleShape,
                                            color = ErpWarning.copy(alpha = 0.15f),
                                            modifier = Modifier.size(40.dp)
                                        ) {
                                            Box(contentAlignment = Alignment.Center) {
                                                Text("🚜", fontSize = 20.sp)
                                            }
                                        }
                                        Spacer(modifier = Modifier.width(12.dp))
                                        Column {
                                            Text(
                                                text = machine.name ?: "Machine #${machine.id}",
                                                fontWeight = FontWeight.Bold,
                                                fontSize = 15.sp,
                                                color = ErpTextMain
                                            )
                                            Text(
                                                text = "Make: ${if (!machine.make.isNullOrBlank()) machine.make else "Standard"}",
                                                fontSize = 12.sp,
                                                color = ErpTextMuted
                                            )
                                            Text(
                                                text = "🏢 ${machine.officeName ?: "Main Complex"}",
                                                fontSize = 11.sp,
                                                color = ErpTextMuted
                                            )
                                        }
                                    }
                                    Surface(
                                        shape = RoundedCornerShape(6.dp),
                                        color = when (machine.status?.lowercase()) {
                                            "working", "active" -> Color(0xFFD1FAE5)
                                            "under repair", "repair" -> Color(0xFFFEF3C7)
                                            else -> Color(0xFFF1F5F9)
                                        }
                                    ) {
                                        Text(
                                            text = machine.status ?: "Working",
                                            fontSize = 11.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = when (machine.status?.lowercase()) {
                                                "working", "active" -> Color(0xFF065F46)
                                                "under repair", "repair" -> Color(0xFF92400E)
                                                else -> Color(0xFF475569)
                                            },
                                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                                        )
                                    }
                                }
                            }
                        }
                    }
                }

                // 2. DAILY RUN LOGS TAB
                WorkshopTab.RUN_LOGS -> {
                    val filteredLogs = remember(data?.runLogs, searchQuery) {
                        val list = data?.runLogs ?: emptyList()
                        if (searchQuery.isBlank()) list
                        else list.filter {
                            (it.machineName ?: "").contains(searchQuery, ignoreCase = true) ||
                            (it.logDate ?: "").contains(searchQuery, ignoreCase = true)
                        }
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Daily Run & Fuel Logs", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
                        Text("Showing: ${filteredLogs.size}", fontSize = 12.sp, color = ErpTextMuted)
                    }

                    Spacer(modifier = Modifier.height(8.dp))

                    if (filteredLogs.isEmpty()) {
                        Text("No run logs found.", fontSize = 13.sp, color = ErpTextMuted)
                    } else {
                        LazyColumn(
                            verticalArrangement = Arrangement.spacedBy(10.dp),
                            modifier = Modifier.weight(1f)
                        ) {
                            items(filteredLogs) { log ->
                                Card(
                                    shape = RoundedCornerShape(10.dp),
                                    colors = CardDefaults.cardColors(containerColor = Color.White),
                                    border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Column(modifier = Modifier.padding(14.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Text(
                                                text = log.machineName ?: "Machine Log",
                                                fontWeight = FontWeight.Bold,
                                                fontSize = 15.sp,
                                                color = ErpTextMain
                                            )
                                            Surface(
                                                shape = RoundedCornerShape(6.dp),
                                                color = Color(0xFFDBEAFE)
                                            ) {
                                                Text(
                                                    text = "📅 ${log.logDate ?: "Date"}",
                                                    fontSize = 11.sp,
                                                    fontWeight = FontWeight.SemiBold,
                                                    color = Color(0xFF1E40AF),
                                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                                )
                                            }
                                        }
                                        Spacer(modifier = Modifier.height(6.dp))
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Text(
                                                text = "⏱️ Hours: ${log.runningHours ?: 0.0} hrs",
                                                fontSize = 13.sp,
                                                fontWeight = FontWeight.SemiBold,
                                                color = Color(0xFF0F172A)
                                            )
                                            Text(
                                                text = "⛽ Fuel: ${log.fuelConsumedQty ?: 0.0} L",
                                                fontSize = 13.sp,
                                                fontWeight = FontWeight.SemiBold,
                                                color = Color(0xFFD97706)
                                            )
                                        }
                                        if (!log.recordedBy.isNullOrBlank()) {
                                            Spacer(modifier = Modifier.height(4.dp))
                                            Text(
                                                text = "Recorded by: ${log.recordedBy}",
                                                fontSize = 11.sp,
                                                color = ErpTextMuted
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // 3. SERVICE & MAINTENANCE LOGS TAB
                WorkshopTab.SERVICES -> {
                    val filteredServices = remember(data?.serviceLogs, searchQuery) {
                        val list = data?.serviceLogs ?: emptyList()
                        if (searchQuery.isBlank()) list
                        else list.filter {
                            (it.machineName ?: "").contains(searchQuery, ignoreCase = true) ||
                            (it.serviceType ?: "").contains(searchQuery, ignoreCase = true) ||
                            (it.servicedBy ?: "").contains(searchQuery, ignoreCase = true)
                        }
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Machine Servicing & Job Cards", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
                        Text("Total: ${filteredServices.size}", fontSize = 12.sp, color = ErpTextMuted)
                    }

                    Spacer(modifier = Modifier.height(8.dp))

                    if (filteredServices.isEmpty()) {
                        Text("No service logs recorded.", fontSize = 13.sp, color = ErpTextMuted)
                    } else {
                        LazyColumn(
                            verticalArrangement = Arrangement.spacedBy(10.dp),
                            modifier = Modifier.weight(1f)
                        ) {
                            items(filteredServices) { sLog ->
                                Card(
                                    shape = RoundedCornerShape(10.dp),
                                    colors = CardDefaults.cardColors(containerColor = Color.White),
                                    border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Column(modifier = Modifier.padding(14.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Text(
                                                text = sLog.machineName ?: "Machine Service",
                                                fontWeight = FontWeight.Bold,
                                                fontSize = 15.sp,
                                                color = ErpTextMain
                                            )
                                            Surface(
                                                shape = RoundedCornerShape(6.dp),
                                                color = Color(0xFFFEF3C7)
                                            ) {
                                                Text(
                                                    text = sLog.serviceType ?: "Service",
                                                    fontSize = 11.sp,
                                                    fontWeight = FontWeight.Bold,
                                                    color = Color(0xFF92400E),
                                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                                )
                                            }
                                        }
                                        Spacer(modifier = Modifier.height(4.dp))
                                        Text(
                                            text = "📅 Date: ${sLog.serviceDate ?: "N/A"} • Serviced at: ${sLog.hoursAtService ?: 0.0} hrs",
                                            fontSize = 12.sp,
                                            color = ErpTextMuted
                                        )
                                        if (sLog.nextServiceDue != null && sLog.nextServiceDue > 0) {
                                            Text(
                                                text = "🔔 Next Service Due: ${sLog.nextServiceDue} hrs",
                                                fontSize = 12.sp,
                                                fontWeight = FontWeight.SemiBold,
                                                color = Color(0xFFDC2626)
                                            )
                                        }
                                        if (!sLog.jobCardNo.isNullOrBlank()) {
                                            Text(
                                                text = "Job Card: #${sLog.jobCardNo}",
                                                fontSize = 11.sp,
                                                color = ErpPrimary
                                            )
                                        }
                                        if (!sLog.remarks.isNullOrBlank()) {
                                            Spacer(modifier = Modifier.height(4.dp))
                                            Text(
                                                text = "Remarks: ${sLog.remarks}",
                                                fontSize = 12.sp,
                                                color = ErpTextMain
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // 4. WATER LOGS TAB
                WorkshopTab.WATER_LOGS -> {
                    val filteredWaterLogs = remember(data?.waterLogs, searchQuery) {
                        val list = data?.waterLogs ?: emptyList()
                        if (searchQuery.isBlank()) list
                        else list.filter {
                            (it.date ?: "").contains(searchQuery, ignoreCase = true)
                        }
                    }

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Daily Water Run Logs", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
                        Text("Total: ${filteredWaterLogs.size}", fontSize = 12.sp, color = ErpTextMuted)
                    }

                    Spacer(modifier = Modifier.height(8.dp))

                    if (filteredWaterLogs.isEmpty()) {
                        Text("No water logs recorded.", fontSize = 13.sp, color = ErpTextMuted)
                    } else {
                        LazyColumn(
                            verticalArrangement = Arrangement.spacedBy(10.dp),
                            modifier = Modifier.weight(1f)
                        ) {
                            items(filteredWaterLogs) { wl ->
                                Card(
                                    shape = RoundedCornerShape(10.dp),
                                    colors = CardDefaults.cardColors(containerColor = Color.White),
                                    border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Column(modifier = Modifier.padding(14.dp)) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Text(
                                                text = "💧 Water Log",
                                                fontWeight = FontWeight.Bold,
                                                fontSize = 15.sp,
                                                color = Color(0xFF0369A1)
                                            )
                                            Surface(
                                                shape = RoundedCornerShape(6.dp),
                                                color = Color(0xFFE0F2FE)
                                            ) {
                                                Text(
                                                    text = "📅 ${wl.date ?: "N/A"}",
                                                    fontSize = 11.sp,
                                                    fontWeight = FontWeight.SemiBold,
                                                    color = Color(0xFF0284C7),
                                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                                )
                                            }
                                        }
                                        Spacer(modifier = Modifier.height(8.dp))
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween
                                        ) {
                                            Column {
                                                Text("🌅 Morning Meter:", fontSize = 11.sp, color = ErpTextMuted)
                                                Text("${wl.morningOpening ?: 0.0} ➔ ${wl.morningClosing ?: 0.0}", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                                            }
                                            Column(horizontalAlignment = Alignment.End) {
                                                Text("🌆 Evening Meter:", fontSize = 11.sp, color = ErpTextMuted)
                                                Text("${wl.eveningOpening ?: 0.0} ➔ ${wl.eveningClosing ?: 0.0}", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}


/* =====================================================================
 * STORE & INVENTORY MODULE SCREEN
 * ===================================================================== */
@Composable
fun StoreModuleContent(apiService: ModPyPhpApiService) {
    var data by remember { mutableStateOf<StoreData?>(null) }
    var searchQuery by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(true) }
    var errorMsg by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            val res = apiService.getStoreData()
            if (res.success) data = res.data else errorMsg = res.error
        } catch (e: Exception) {
            errorMsg = e.localizedMessage
        } finally {
            isLoading = false
        }
    }

    ModuleScreenLayout(
        title = "Store & Inventory",
        subtitle = "Materials catalog, spare parts, fuels & lubricants",
        isLoading = isLoading,
        errorMsg = errorMsg
    ) {
        val filteredItems = remember(data?.items, searchQuery) {
            val list = data?.items ?: emptyList()
            if (searchQuery.isBlank()) list
            else list.filter {
                (it.description ?: "").contains(searchQuery, ignoreCase = true) ||
                (it.categoryName ?: "").contains(searchQuery, ignoreCase = true)
            }
        }

        LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
            // Search Bar
            item {
                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { searchQuery = it },
                    placeholder = { Text("Search inventory items or categories...") },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(10.dp),
                    singleLine = true,
                    leadingIcon = { Text("🔍") }
                )
            }

            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Store Items Catalog", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
                    Text("Total: ${filteredItems.size}", fontSize = 12.sp, color = ErpTextMuted)
                }
            }

            items(filteredItems) { item ->
                Card(
                    shape = RoundedCornerShape(10.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.White),
                    border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(14.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.weight(1f)) {
                            Surface(
                                shape = CircleShape,
                                color = ErpInfo.copy(alpha = 0.12f),
                                modifier = Modifier.size(40.dp)
                            ) {
                                Box(contentAlignment = Alignment.Center) {
                                    Text("📦", fontSize = 20.sp)
                                }
                            }
                            Spacer(modifier = Modifier.width(12.dp))
                            Column {
                                Text(
                                    text = item.description ?: "Item #${item.id}",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 15.sp,
                                    color = ErpTextMain
                                )
                                Text(
                                    text = "Category: ${item.categoryName ?: "General"}",
                                    fontSize = 12.sp,
                                    color = ErpTextMuted
                                )
                            }
                        }
                        Surface(
                            shape = RoundedCornerShape(6.dp),
                            color = Color(0xFFE0F2FE)
                        ) {
                            Text(
                                text = "Unit: ${item.acUnit ?: "Pcs"}",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.SemiBold,
                                color = Color(0xFF0369A1),
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                            )
                        }
                    }
                }
            }
        }
    }
}

/* =====================================================================
 * FINANCE MODULE SCREEN
 * ===================================================================== */
@Composable
fun FinanceModuleContent(apiService: ModPyPhpApiService) {
    var data by remember { mutableStateOf<FinanceData?>(null) }
    var isLoading by remember { mutableStateOf(true) }
    var errorMsg by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            val res = apiService.getFinanceData()
            if (res.success) data = res.data else errorMsg = res.error
        } catch (e: Exception) {
            errorMsg = e.localizedMessage
        } finally {
            isLoading = false
        }
    }

    ModuleScreenLayout(
        title = "Finance & Accounts",
        subtitle = "Budget allocations, bill vouchers & revenue tracking",
        isLoading = isLoading,
        errorMsg = errorMsg
    ) {
        LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
            // Budgets Section
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Budgets & Allocations", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
                    Text("Total: ${data?.budgets?.size ?: 0}", fontSize = 12.sp, color = ErpTextMuted)
                }
            }

            if (data?.budgets.isNullOrEmpty()) {
                item {
                    Text("No budget records found.", fontSize = 13.sp, color = ErpTextMuted)
                }
            } else {
                items(data!!.budgets!!) { budget ->
                    Card(
                        shape = RoundedCornerShape(10.dp),
                        colors = CardDefaults.cardColors(containerColor = Color.White),
                        border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(14.dp)) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Text(
                                    text = budget.head ?: "Budget Code",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 15.sp,
                                    color = ErpTextMain
                                )
                                Surface(
                                    shape = RoundedCornerShape(6.dp),
                                    color = Color(0xFFDBEAFE)
                                ) {
                                    Text(
                                        text = "FY ${budget.financialYear ?: "N/A"}",
                                        fontSize = 11.sp,
                                        fontWeight = FontWeight.SemiBold,
                                        color = Color(0xFF1E40AF),
                                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                    )
                                }
                            }
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = budget.subHead ?: "General Work",
                                fontSize = 13.sp,
                                color = ErpTextMuted
                            )
                            Spacer(modifier = Modifier.height(8.dp))
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Text(
                                    text = "🏢 ${budget.officeName ?: "Head Office"}",
                                    fontSize = 12.sp,
                                    color = ErpTextMuted
                                )
                                Text(
                                    text = "₹${NumberFormat.getNumberInstance(Locale.US).format(budget.allocatedAmount ?: 0.0)}",
                                    fontSize = 15.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = ErpSuccess
                                )
                            }
                        }
                    }
                }
            }

            // Bills Section
            item {
                Spacer(modifier = Modifier.height(10.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Bills Register", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
                    Text("Total: ${data?.bills?.size ?: 0}", fontSize = 12.sp, color = ErpTextMuted)
                }
            }

            if (data?.bills.isNullOrEmpty()) {
                item {
                    Text("No bills registered.", fontSize = 13.sp, color = ErpTextMuted)
                }
            } else {
                items(data!!.bills!!) { bill ->
                    Card(
                        shape = RoundedCornerShape(10.dp),
                        colors = CardDefaults.cardColors(containerColor = Color.White),
                        border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(14.dp)) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Text(
                                    text = "Bill #${bill.billNo ?: bill.id}",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 15.sp,
                                    color = ErpTextMain
                                )
                                Surface(
                                    shape = RoundedCornerShape(6.dp),
                                    color = when (bill.status?.lowercase()) {
                                        "paid" -> Color(0xFFD1FAE5)
                                        "approved" -> Color(0xFFDBEAFE)
                                        else -> Color(0xFFFEF3C7)
                                    }
                                ) {
                                    Text(
                                        text = bill.status ?: "Draft",
                                        fontSize = 11.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = when (bill.status?.lowercase()) {
                                            "paid" -> Color(0xFF065F46)
                                            "approved" -> Color(0xFF1E40AF)
                                            else -> Color(0xFF92400E)
                                        },
                                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                    )
                                }
                            }
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "🏢 ${bill.officeName ?: "Office"} • Date: ${bill.billDate ?: "N/A"}",
                                fontSize = 12.sp,
                                color = ErpTextMuted
                            )
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                text = "Net Amount: ₹${NumberFormat.getNumberInstance(Locale.US).format(bill.netAmount ?: 0.0)}",
                                fontSize = 14.sp,
                                fontWeight = FontWeight.Bold,
                                color = ErpTextMain
                            )
                        }
                    }
                }
            }
        }
    }
}

/* =====================================================================
 * HR MODULE SCREEN
 * ===================================================================== */
@Composable
fun HRModuleContent(apiService: ModPyPhpApiService) {
    var data by remember { mutableStateOf<HRData?>(null) }
    var searchQuery by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(true) }
    var errorMsg by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            val res = apiService.getHRData()
            if (res.success) data = res.data else errorMsg = res.error
        } catch (e: Exception) {
            errorMsg = e.localizedMessage
        } finally {
            isLoading = false
        }
    }

    ModuleScreenLayout(
        title = "Human Resource",
        subtitle = "Employee master roster, attendance & designations",
        isLoading = isLoading,
        errorMsg = errorMsg
    ) {
        val filteredEmployees = remember(data?.employees, searchQuery) {
            val list = data?.employees ?: emptyList()
            if (searchQuery.isBlank()) list
            else list.filter {
                (it.fullName ?: "").contains(searchQuery, ignoreCase = true) ||
                (it.designation ?: "").contains(searchQuery, ignoreCase = true) ||
                (it.officeName ?: "").contains(searchQuery, ignoreCase = true)
            }
        }

        LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
            // Today Attendance Counter Banner
            item {
                Card(
                    shape = RoundedCornerShape(10.dp),
                    colors = CardDefaults.cardColors(containerColor = Color(0xFFD1FAE5)),
                    border = androidx.compose.foundation.BorderStroke(1.dp, Color(0xFFA7F3D0)),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(14.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("📅", fontSize = 24.sp)
                        Spacer(modifier = Modifier.width(12.dp))
                        Column {
                            Text(
                                text = "Today's Attendance Counter",
                                fontWeight = FontWeight.Bold,
                                fontSize = 14.sp,
                                color = Color(0xFF065F46)
                            )
                            Text(
                                text = "${data?.todayAttendanceCount ?: 0} marked present today",
                                fontSize = 12.sp,
                                color = Color(0xFF047857)
                            )
                        }
                    }
                }
            }

            // Search Bar
            item {
                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { searchQuery = it },
                    placeholder = { Text("Search employees by name or role...") },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(10.dp),
                    singleLine = true,
                    leadingIcon = { Text("🔍") }
                )
            }

            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Employee Roster", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
                    Text("Showing: ${filteredEmployees.size}", fontSize = 12.sp, color = ErpTextMuted)
                }
            }

            items(filteredEmployees) { emp ->
                Card(
                    shape = RoundedCornerShape(10.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.White),
                    border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(14.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.weight(1f)) {
                            Surface(
                                shape = CircleShape,
                                color = ErpPrimary.copy(alpha = 0.12f),
                                modifier = Modifier.size(40.dp)
                            ) {
                                Box(contentAlignment = Alignment.Center) {
                                    Text(
                                        text = (emp.fullName?.take(1) ?: "E").uppercase(),
                                        fontWeight = FontWeight.Bold,
                                        color = ErpPrimary
                                    )
                                }
                            }
                            Spacer(modifier = Modifier.width(12.dp))
                            Column {
                                Text(
                                    text = emp.fullName ?: "Employee #${emp.id}",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 15.sp,
                                    color = ErpTextMain
                                )
                                Text(
                                    text = emp.designation ?: "Staff",
                                    fontSize = 13.sp,
                                    color = ErpPrimary
                                )
                                Text(
                                    text = "🏢 ${emp.officeName ?: "Head Office"}",
                                    fontSize = 11.sp,
                                    color = ErpTextMuted
                                )
                            }
                        }
                        Surface(
                            shape = RoundedCornerShape(6.dp),
                            color = Color(0xFFD1FAE5)
                        ) {
                            Text(
                                text = emp.status ?: "Active",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.SemiBold,
                                color = Color(0xFF065F46),
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                            )
                        }
                    }
                }
            }
        }
    }
}

/* =====================================================================
 * WORKS MODULE SCREEN
 * ===================================================================== */
@Composable
fun WorksModuleContent(apiService: ModPyPhpApiService) {
    var data by remember { mutableStateOf<WorksData?>(null) }
    var isLoading by remember { mutableStateOf(true) }
    var errorMsg by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            val res = apiService.getWorksData()
            if (res.success) data = res.data else errorMsg = res.error
        } catch (e: Exception) {
            errorMsg = e.localizedMessage
        } finally {
            isLoading = false
        }
    }

    ModuleScreenLayout(
        title = "Works & Engineering",
        subtitle = "Agreements registry, work orders & contract sanctions",
        isLoading = isLoading,
        errorMsg = errorMsg
    ) {
        LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Agreements Registry", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
                    Text("Total: ${data?.agreements?.size ?: 0}", fontSize = 12.sp, color = ErpTextMuted)
                }
            }

            if (data?.agreements.isNullOrEmpty()) {
                item {
                    Text("No agreements recorded.", fontSize = 13.sp, color = ErpTextMuted)
                }
            } else {
                items(data!!.agreements!!) { agr ->
                    Card(
                        shape = RoundedCornerShape(10.dp),
                        colors = CardDefaults.cardColors(containerColor = Color.White),
                        border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(14.dp)) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Text(
                                    text = agr.agreementNo ?: "Agr #${agr.id}",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 15.sp,
                                    color = ErpTextMain
                                )
                                Surface(
                                    shape = RoundedCornerShape(6.dp),
                                    color = Color(0xFFD1FAE5)
                                ) {
                                    Text(
                                        text = agr.status ?: "Active",
                                        fontSize = 11.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = Color(0xFF065F46),
                                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                    )
                                }
                            }
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "Agency: ${agr.agencyName ?: "Contractor"}",
                                fontSize = 13.sp,
                                color = ErpTextMuted
                            )
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                text = "Tendered: ₹${NumberFormat.getNumberInstance(Locale.US).format(agr.tenderedAmount ?: 0.0)}",
                                fontSize = 14.sp,
                                fontWeight = FontWeight.Bold,
                                color = ErpPrimary
                            )
                        }
                    }
                }
            }

            item {
                Spacer(modifier = Modifier.height(10.dp))
                Text("Work Orders", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
            }

            if (data?.workOrders.isNullOrEmpty()) {
                item {
                    Text("No work orders pending.", fontSize = 13.sp, color = ErpTextMuted)
                }
            } else {
                items(data!!.workOrders!!) { wo ->
                    Card(
                        shape = RoundedCornerShape(10.dp),
                        colors = CardDefaults.cardColors(containerColor = Color.White),
                        border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(14.dp)) {
                            Text(text = "WO #${wo.workOrderNo ?: wo.id}", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                            Text(text = "Date: ${wo.issueDate ?: "N/A"}", fontSize = 12.sp, color = ErpTextMuted)
                            Text(text = "Amount: ₹${wo.amount ?: 0.0}", fontWeight = FontWeight.SemiBold, color = ErpSuccess)
                        }
                    }
                }
            }
        }
    }
}

/* =====================================================================
 * ADMIN MODULE SCREEN
 * ===================================================================== */
@Composable
fun AdminModuleContent(apiService: ModPyPhpApiService) {
    var data by remember { mutableStateOf<AdminData?>(null) }
    var isLoading by remember { mutableStateOf(true) }
    var errorMsg by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            val res = apiService.getAdminData()
            if (res.success) data = res.data else errorMsg = res.error
        } catch (e: Exception) {
            errorMsg = e.localizedMessage
        } finally {
            isLoading = false
        }
    }

    ModuleScreenLayout(
        title = "Administration & Master Data",
        subtitle = "Offices directory, agencies master & cloud database connectivity",
        isLoading = isLoading,
        errorMsg = errorMsg
    ) {
        LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
            // Cloud Database Status Card
            item {
                Card(
                    shape = RoundedCornerShape(10.dp),
                    colors = CardDefaults.cardColors(containerColor = ErpDeepNavy),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(14.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Box(
                                modifier = Modifier
                                    .size(8.dp)
                                    .clip(CircleShape)
                                    .background(Color(0xFF10B981))
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Render Cloud API Live", color = Color.White, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                        }
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = "Backend: https://modpyphp-erp.onrender.com\nDatabase: Neon Cloud PostgreSQL (us-east-2)",
                            color = Color(0xFF94A3B8),
                            fontSize = 11.sp
                        )
                    }
                }
            }

            // Offices Section
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Offices Master", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
                    Text("Total: ${data?.offices?.size ?: 0}", fontSize = 12.sp, color = ErpTextMuted)
                }
            }

            items(data?.offices ?: emptyList()) { office ->
                Card(
                    shape = RoundedCornerShape(10.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.White),
                    border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(14.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column(modifier = Modifier.weight(1f)) {
                            Text(
                                text = office.name ?: "Office #${office.id}",
                                fontWeight = FontWeight.Bold,
                                fontSize = 15.sp,
                                color = ErpTextMain
                            )
                            Text(
                                text = if (!office.location.isNullOrBlank()) office.location!! else "Main Complex",
                                fontSize = 12.sp,
                                color = ErpTextMuted
                            )
                        }
                        Surface(
                            shape = RoundedCornerShape(6.dp),
                            color = Color(0xFFE0E7FF)
                        ) {
                            Text(
                                text = office.code ?: "CODE",
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF3730A3),
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp)
                            )
                        }
                    }
                }
            }

            // Agencies Section
            item {
                Spacer(modifier = Modifier.height(10.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text("Agencies & Contractors", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
                    Text("Total: ${data?.agencies?.size ?: 0}", fontSize = 12.sp, color = ErpTextMuted)
                }
            }

            items(data?.agencies ?: emptyList()) { agency ->
                Card(
                    shape = RoundedCornerShape(10.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.White),
                    border = androidx.compose.foundation.BorderStroke(1.dp, ErpBorder),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(14.dp)) {
                        Text(
                            text = agency.name ?: "Agency #${agency.id}",
                            fontWeight = FontWeight.Bold,
                            fontSize = 15.sp,
                            color = ErpTextMain
                        )
                        if (!agency.gstNo.isNullOrBlank()) {
                            Text(text = "GST: ${agency.gstNo}", fontSize = 12.sp, color = ErpTextMuted)
                        }
                    }
                }
            }
        }
    }
}

/* =====================================================================
 * COMMON MODULE SCREEN LAYOUT
 * ===================================================================== */
@Composable
fun ModuleScreenLayout(
    title: String,
    subtitle: String,
    isLoading: Boolean,
    errorMsg: String?,
    content: @Composable () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp)
    ) {
        Text(text = title, fontSize = 20.sp, fontWeight = FontWeight.Bold, color = ErpTextMain)
        Text(text = subtitle, fontSize = 12.sp, color = ErpTextMuted)
        Spacer(modifier = Modifier.height(14.dp))

        if (isLoading) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator(color = ErpPrimary)
            }
        } else if (errorMsg != null) {
            Card(
                colors = CardDefaults.cardColors(containerColor = Color(0xFFFEE2E2)),
                border = androidx.compose.foundation.BorderStroke(1.dp, Color(0xFFFCA5A5)),
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text("Failed to load module data", fontWeight = FontWeight.Bold, color = Color(0xFFB91C1C))
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(errorMsg, fontSize = 13.sp, color = Color(0xFF7F1D1D))
                }
            }
        } else {
            content()
        }
    }
}
