package com.modpyphp.mobile

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.modpyphp.mobile.data.api.ModPyPhpApiService
import com.modpyphp.mobile.data.models.*
import com.modpyphp.mobile.ui.theme.ModPyPhpTheme
import kotlinx.coroutines.launch

enum class Screen { LOGIN, DASHBOARD, FINANCE, HR, WORKS, ADMIN }

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

@Composable
fun MainAppScreen() {
    var serverUrl by remember { mutableStateOf("http://192.168.1.3:8000") }
    var currentUser by remember { mutableStateOf<User?>(null) }
    var currentScreen by remember { mutableStateOf(Screen.LOGIN) }
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
                bottomBar = {
                    NavigationBar(
                        containerColor = MaterialTheme.colorScheme.surface,
                        tonalElevation = 8.dp
                    ) {
                        val navItems = listOf(
                            NavOption("Dashboard", "📊", Screen.DASHBOARD),
                            NavOption("Finance", "💰", Screen.FINANCE),
                            NavOption("HR", "👥", Screen.HR),
                            NavOption("Works", "🛠️", Screen.WORKS),
                            NavOption("Admin", "⚙️", Screen.ADMIN)
                        )
                        navItems.forEach { item ->
                            NavigationBarItem(
                                icon = { Text(item.icon, fontSize = 20.sp) },
                                label = { Text(item.label) },
                                selected = currentScreen == item.screen,
                                onClick = { currentScreen = item.screen }
                            )
                        }
                    }
                }
            ) { padding ->
                Box(modifier = Modifier.padding(padding)) {
                    when (currentScreen) {
                        Screen.DASHBOARD -> DashboardContent(apiService!!, currentUser!!) {
                            currentUser = null
                            apiService = null
                            currentScreen = Screen.LOGIN
                        }
                        Screen.FINANCE -> FinanceModuleContent(apiService!!)
                        Screen.HR -> HRModuleContent(apiService!!)
                        Screen.WORKS -> WorksModuleContent(apiService!!)
                        Screen.ADMIN -> AdminModuleContent(apiService!!)
                        Screen.LOGIN -> {}
                    }
                }
            }
        }
    }
}

data class NavOption(val label: String, val icon: String, val screen: Screen)

@Composable
fun LoginScreen(
    defaultUrl: String,
    onLoginSuccess: (User, String, ModPyPhpApiService) -> Unit
) {
    var serverUrl by remember { mutableStateOf(defaultUrl) }
    var username by remember { mutableStateOf("admin") }
    var password by remember { mutableStateOf("admin123") }
    var isLoading by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Card(
            shape = RoundedCornerShape(16.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer),
            modifier = Modifier.padding(bottom = 24.dp)
        ) {
            Text(
                text = "🏛️",
                fontSize = 48.sp,
                modifier = Modifier.padding(20.dp)
            )
        }

        Text(
            text = "ModPyPhp Office",
            fontSize = 28.sp,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onBackground
        )
        Text(
            text = "Integrated Management System",
            fontSize = 14.sp,
            color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.7f),
            modifier = Modifier.padding(bottom = 28.dp)
        )

        OutlinedTextField(
            value = serverUrl,
            onValueChange = { serverUrl = it },
            label = { Text("PHP Backend Server URL") },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true
        )

        Spacer(modifier = Modifier.height(12.dp))

        OutlinedTextField(
            value = username,
            onValueChange = { username = it },
            label = { Text("Username") },
            modifier = Modifier.fillMaxWidth(),
            singleLine = true
        )

        Spacer(modifier = Modifier.height(12.dp))

        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text("Password") },
            visualTransformation = PasswordVisualTransformation(),
            modifier = Modifier.fillMaxWidth(),
            singleLine = true
        )

        errorMessage?.let {
            Spacer(modifier = Modifier.height(12.dp))
            Text(text = it, color = MaterialTheme.colorScheme.error, fontSize = 14.sp)
        }

        Spacer(modifier = Modifier.height(24.dp))

        Button(
            onClick = {
                scope.launch {
                    isLoading = true
                    errorMessage = null
                    try {
                        val api = ModPyPhpApiService.create(serverUrl)
                        val res = api.login(mapOf("username" to username, "password" to password))
                        if (res.success && res.user != null) {
                            onLoginSuccess(res.user, serverUrl, api)
                        } else {
                            errorMessage = res.error ?: "Invalid credentials"
                        }
                    } catch (e: Exception) {
                        errorMessage = "Connection Failed: ${e.localizedMessage}"
                    } finally {
                        isLoading = false
                    }
                }
            },
            modifier = Modifier
                .fillMaxWidth()
                .height(50.dp),
            enabled = !isLoading
        ) {
            if (isLoading) {
                CircularProgressIndicator(modifier = Modifier.size(24.dp), color = Color.White)
            } else {
                Text("Login", fontSize = 16.sp, fontWeight = FontWeight.Bold)
            }
        }
    }
}

@Composable
fun DashboardContent(
    apiService: ModPyPhpApiService,
    user: User,
    onLogout: () -> Unit
) {
    var stats by remember { mutableStateOf<SystemStats?>(null) }
    var isLoading by remember { mutableStateOf(true) }
    var errorMessage by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            val res = apiService.getDashboardStats()
            if (res.success && res.data != null) {
                stats = res.data
            } else {
                errorMessage = res.error
            }
        } catch (e: Exception) {
            errorMessage = e.localizedMessage
        } finally {
            isLoading = false
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp)
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column {
                Text(
                    text = "Welcome, ${user.firstName ?: user.username}",
                    fontSize = 22.sp,
                    fontWeight = FontWeight.Bold
                )
                Text(text = "Role: ${user.role ?: "User"}", fontSize = 14.sp, color = Color.Gray)
            }
            TextButton(onClick = onLogout) {
                Text("Logout", color = MaterialTheme.colorScheme.error, fontWeight = FontWeight.Bold)
            }
        }

        Spacer(modifier = Modifier.height(16.dp))

        if (isLoading) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
        } else if (errorMessage != null) {
            Text(text = "Error: $errorMessage", color = MaterialTheme.colorScheme.error)
        } else if (stats != null) {
            Text(text = "System Overview", fontSize = 18.sp, fontWeight = FontWeight.SemiBold)
            Spacer(modifier = Modifier.height(12.dp))

            val items = listOf(
                StatCardItem("Employees", stats!!.employees.toString(), "👥", Color(0xFF3B82F6)),
                StatCardItem("Offices", stats!!.offices.toString(), "🏢", Color(0xFF10B981)),
                StatCardItem("Agreements", stats!!.agreements.toString(), "📄", Color(0xFF8B5CF6)),
                StatCardItem("Bills", stats!!.bills.toString(), "🧾", Color(0xFFF59E0B)),
                StatCardItem("Budgets", stats!!.budgets.toString(), "💰", Color(0xFFEC4899)),
                StatCardItem("Users", stats!!.users.toString(), "👤", Color(0xFF6366F1))
            )

            LazyVerticalGrid(
                columns = GridCells.Fixed(2),
                horizontalArrangement = Arrangement.spacedBy(12.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                items(items) { item ->
                    Card(
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = item.color.copy(alpha = 0.15f))
                    ) {
                        Column(
                            modifier = Modifier.padding(16.dp),
                            verticalArrangement = Arrangement.Center
                        ) {
                            Text(item.icon, fontSize = 24.sp)
                            Spacer(modifier = Modifier.height(8.dp))
                            Text(text = item.value, fontSize = 24.sp, fontWeight = FontWeight.Bold, color = item.color)
                            Text(text = item.title, fontSize = 14.sp, color = MaterialTheme.colorScheme.onBackground)
                        }
                    }
                }
            }
        }
    }
}

data class StatCardItem(val title: String, val value: String, val icon: String, val color: Color)

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

    ModuleScreenLayout(title = "Finance Hub", isLoading = isLoading, errorMsg = errorMsg) {
        LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
            item { Text("Budgets", fontSize = 18.sp, fontWeight = FontWeight.Bold) }
            items(data?.budgets ?: emptyList()) { budget ->
                ListItemCard(
                    title = "${budget.head ?: "Budget"} - ${budget.subHead ?: ""}",
                    subtitle = "Allocated: ₹${budget.allocatedAmount ?: 0.0} | FY: ${budget.financialYear ?: "N/A"}",
                    badge = budget.officeName ?: "Office"
                )
            }
            item {
                Spacer(modifier = Modifier.height(12.dp))
                Text("Bills", fontSize = 18.sp, fontWeight = FontWeight.Bold)
            }
            items(data?.bills ?: emptyList()) { bill ->
                ListItemCard(
                    title = "Bill #${bill.billNo ?: bill.id}",
                    subtitle = "Date: ${bill.billDate ?: "N/A"} | Net Amount: ₹${bill.netAmount ?: 0.0}",
                    badge = bill.status ?: "Pending"
                )
            }
        }
    }
}

@Composable
fun HRModuleContent(apiService: ModPyPhpApiService) {
    var data by remember { mutableStateOf<HRData?>(null) }
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

    ModuleScreenLayout(title = "HR & Attendance Hub", isLoading = isLoading, errorMsg = errorMsg) {
        LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
            item {
                Card(
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(
                        text = "Today Attendance Count: ${data?.todayAttendanceCount ?: 0}",
                        modifier = Modifier.padding(16.dp),
                        fontWeight = FontWeight.Bold
                    )
                }
                Spacer(modifier = Modifier.height(12.dp))
                Text("Employee Roster", fontSize = 18.sp, fontWeight = FontWeight.Bold)
            }
            items(data?.employees ?: emptyList()) { emp ->
                ListItemCard(
                    title = emp.fullName ?: "Employee #${emp.id}",
                    subtitle = "${emp.designation ?: "Staff"} | Mobile: ${emp.mobile ?: "N/A"}",
                    badge = emp.officeName ?: "Office"
                )
            }
        }
    }
}

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

    ModuleScreenLayout(title = "Works & Engineering", isLoading = isLoading, errorMsg = errorMsg) {
        LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
            item { Text("Agreements", fontSize = 18.sp, fontWeight = FontWeight.Bold) }
            items(data?.agreements ?: emptyList()) { agr ->
                ListItemCard(
                    title = "Agreement #${agr.agreementNo ?: agr.id}",
                    subtitle = "Agency: ${agr.agencyName ?: "N/A"} | Tendered: ₹${agr.tenderedAmount ?: 0.0}",
                    badge = agr.status ?: "Active"
                )
            }
            item {
                Spacer(modifier = Modifier.height(12.dp))
                Text("Work Orders", fontSize = 18.sp, fontWeight = FontWeight.Bold)
            }
            items(data?.workOrders ?: emptyList()) { wo ->
                ListItemCard(
                    title = "Work Order #${wo.workOrderNo ?: wo.id}",
                    subtitle = "Issued: ${wo.issueDate ?: "N/A"} | Amount: ₹${wo.amount ?: 0.0}",
                    badge = wo.status ?: "Issued"
                )
            }
        }
    }
}

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

    ModuleScreenLayout(title = "System Administration", isLoading = isLoading, errorMsg = errorMsg) {
        LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
            item { Text("Offices", fontSize = 18.sp, fontWeight = FontWeight.Bold) }
            items(data?.offices ?: emptyList()) { office ->
                ListItemCard(
                    title = office.name ?: "Office #${office.id}",
                    subtitle = "Location: ${office.location ?: "N/A"}",
                    badge = office.code ?: "CODE"
                )
            }
            item {
                Spacer(modifier = Modifier.height(12.dp))
                Text("Agencies", fontSize = 18.sp, fontWeight = FontWeight.Bold)
            }
            items(data?.agencies ?: emptyList()) { agency ->
                ListItemCard(
                    title = agency.name ?: "Agency #${agency.id}",
                    subtitle = "Contact: ${agency.contactPerson ?: "N/A"} | Email: ${agency.email ?: "N/A"}",
                    badge = agency.gstNo ?: "GST"
                )
            }
        }
    }
}

@Composable
fun ModuleScreenLayout(
    title: String,
    isLoading: Boolean,
    errorMsg: String?,
    content: @Composable () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp)
    ) {
        Text(text = title, fontSize = 22.sp, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(12.dp))

        if (isLoading) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
        } else if (errorMsg != null) {
            Text(text = "Error: $errorMsg", color = MaterialTheme.colorScheme.error)
        } else {
            content()
        }
    }
}

@Composable
fun ListItemCard(title: String, subtitle: String, badge: String) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(10.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(text = title, fontWeight = FontWeight.Bold, fontSize = 16.sp)
                Spacer(modifier = Modifier.height(4.dp))
                Text(text = subtitle, fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
            Spacer(modifier = Modifier.width(8.dp))
            Surface(
                shape = RoundedCornerShape(6.dp),
                color = MaterialTheme.colorScheme.primaryContainer
            ) {
                Text(
                    text = badge,
                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                    fontSize = 12.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onPrimaryContainer
                )
            }
        }
    }
}
