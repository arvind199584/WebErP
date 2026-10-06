package com.modpyphp.mobile.data.api

import com.modpyphp.mobile.data.models.*
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.*
import java.util.concurrent.TimeUnit

interface ModPyPhpApiService {

    @POST("api/index.php?action=login")
    suspend fun login(@Body credentials: Map<String, String>): LoginResponse

    @GET("api/index.php?action=dashboard")
    suspend fun getDashboardStats(): DashboardResponse

    @GET("api/index.php?action=finance")
    suspend fun getFinanceData(): FinanceResponse

    @GET("api/index.php?action=hr")
    suspend fun getHRData(): HRResponse

    @GET("api/index.php?action=works")
    suspend fun getWorksData(): WorksResponse

    @GET("api/index.php?action=admin")
    suspend fun getAdminData(): AdminResponse

    companion object {
        const val DEFAULT_BASE_URL = "https://modpyphp-erp.onrender.com"
        private var instance: ModPyPhpApiService? = null

        fun create(baseUrl: String = DEFAULT_BASE_URL): ModPyPhpApiService {
            val formattedUrl = if (baseUrl.endsWith("/")) baseUrl else "$baseUrl/"

            val logging = HttpLoggingInterceptor().apply {
                level = HttpLoggingInterceptor.Level.BODY
            }

            val client = OkHttpClient.Builder()
                .addInterceptor(logging)
                .connectTimeout(30, TimeUnit.SECONDS)
                .readTimeout(30, TimeUnit.SECONDS)
                .writeTimeout(30, TimeUnit.SECONDS)
                .build()

            val retrofit = Retrofit.Builder()
                .baseUrl(formattedUrl)
                .client(client)
                .addConverterFactory(GsonConverterFactory.create())
                .build()

            val service = retrofit.create(ModPyPhpApiService::class.java)
            instance = service
            return service
        }

        fun getInstance(): ModPyPhpApiService? = instance
    }
}
