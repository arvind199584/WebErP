package com.modpyphp.mobile.data.models

import com.google.gson.annotations.SerializedName

data class User(
    val id: Int,
    val username: String,
    val firstName: String?,
    val lastName: String?,
    val email: String?,
    val role: String?,
    val officeId: Int?
)

data class LoginResponse(
    val success: Boolean,
    val user: User?,
    val error: String?
)

data class SystemStats(
    val employees: Int,
    val offices: Int,
    val agreements: Int,
    val bills: Int,
    val budgets: Int,
    val users: Int,
    val recentActivity: List<ActivityLog>?
)

data class ActivityLog(
    val id: Int,
    @SerializedName("table_name") val tableName: String?,
    val action: String?,
    @SerializedName("changed_at") val changedAt: String?
)

data class DashboardResponse(
    val success: Boolean,
    val data: SystemStats?,
    val error: String?
)

data class Budget(
    val id: Int,
    @SerializedName("office_name") val officeName: String?,
    val head: String?,
    @SerializedName("sub_head") val subHead: String?,
    @SerializedName("allocated_amount") val allocatedAmount: Double?,
    @SerializedName("financial_year") val financialYear: String?
)

data class Bill(
    val id: Int,
    @SerializedName("office_name") val officeName: String?,
    @SerializedName("bill_no") val billNo: String?,
    @SerializedName("bill_date") val billDate: String?,
    @SerializedName("net_amount") val netAmount: Double?,
    val status: String?
)

data class FinanceData(
    val budgets: List<Budget>?,
    val bills: List<Bill>?
)

data class FinanceResponse(
    val success: Boolean,
    val data: FinanceData?,
    val error: String?
)

data class Employee(
    val id: Int,
    @SerializedName("office_name") val officeName: String?,
    @SerializedName("full_name") val fullName: String?,
    val designation: String?,
    val mobile: String?,
    val status: String?
)

data class HRData(
    val employees: List<Employee>?,
    val todayAttendanceCount: Int?
)

data class HRResponse(
    val success: Boolean,
    val data: HRData?,
    val error: String?
)

data class Agreement(
    val id: Int,
    @SerializedName("agreement_no") val agreementNo: String?,
    @SerializedName("agency_name") val agencyName: String?,
    @SerializedName("tendered_amount") val tenderedAmount: Double?,
    val status: String?
)

data class WorkOrder(
    val id: Int,
    @SerializedName("work_order_no") val workOrderNo: String?,
    val amount: Double?,
    @SerializedName("issue_date") val issueDate: String?,
    val status: String?
)

data class SupplyOrder(
    val id: Int,
    @SerializedName("order_no") val orderNo: String?,
    @SerializedName("total_amount") val totalAmount: Double?,
    @SerializedName("issue_date") val issueDate: String?,
    val status: String?
)

data class WorksData(
    val agreements: List<Agreement>?,
    val workOrders: List<WorkOrder>?,
    val supplyOrders: List<SupplyOrder>?
)

data class WorksResponse(
    val success: Boolean,
    val data: WorksData?,
    val error: String?
)

data class Office(
    val id: Int,
    val name: String?,
    val location: String?,
    val code: String?
)

data class Agency(
    val id: Int,
    val name: String?,
    @SerializedName("contact_person") val contactPerson: String?,
    val email: String?,
    @SerializedName("gst_no") val gstNo: String?
)

data class AdminData(
    val offices: List<Office>?,
    val agencies: List<Agency>?
)

data class AdminResponse(
    val success: Boolean,
    val data: AdminData?,
    val error: String?
)
