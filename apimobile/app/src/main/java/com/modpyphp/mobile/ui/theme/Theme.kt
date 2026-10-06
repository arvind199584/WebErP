package com.modpyphp.mobile.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

val ErpPrimary = Color(0xFF0D6EFD)
val ErpPrimaryDark = Color(0xFF0B5ED7)
val ErpNavy = Color(0xFF1E293B)
val ErpDeepNavy = Color(0xFF0F172A)
val ErpSuccess = Color(0xFF10B981)
val ErpWarning = Color(0xFFF59E0B)
val ErpDanger = Color(0xFFEF4444)
val ErpInfo = Color(0xFF0EA5E9)
val ErpPurple = Color(0xFF8B5CF6)
val ErpBackground = Color(0xFFF8FAFC)
val ErpSurface = Color(0xFFFFFFFF)
val ErpBorder = Color(0xFFE2E8F0)
val ErpTextMain = Color(0xFF1E293B)
val ErpTextMuted = Color(0xFF64748B)

private val LightColorScheme = lightColorScheme(
    primary = ErpPrimary,
    onPrimary = Color.White,
    primaryContainer = Color(0xFFDBEAFE),
    onPrimaryContainer = Color(0xFF1E40AF),
    secondary = ErpNavy,
    onSecondary = Color.White,
    secondaryContainer = Color(0xFFF1F5F9),
    onSecondaryContainer = Color(0xFF0F172A),
    tertiary = ErpSuccess,
    background = ErpBackground,
    onBackground = ErpTextMain,
    surface = ErpSurface,
    onSurface = ErpTextMain,
    surfaceVariant = Color(0xFFF1F5F9),
    onSurfaceVariant = ErpTextMuted,
    outline = ErpBorder
)

private val DarkColorScheme = darkColorScheme(
    primary = Color(0xFF60A5FA),
    onPrimary = Color(0xFF0F172A),
    primaryContainer = Color(0xFF1E3A8A),
    onPrimaryContainer = Color(0xFFDBEAFE),
    secondary = Color(0xFF94A3B8),
    onSecondary = Color(0xFF0F172A),
    background = ErpDeepNavy,
    onBackground = Color(0xFFF8FAFC),
    surface = ErpNavy,
    onSurface = Color(0xFFF8FAFC),
    surfaceVariant = Color(0xFF334155),
    onSurfaceVariant = Color(0xFF94A3B8),
    outline = Color(0xFF475569)
)

@Composable
fun ModPyPhpTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit
) {
    val colorScheme = if (darkTheme) DarkColorScheme else LightColorScheme

    MaterialTheme(
        colorScheme = colorScheme,
        content = content
    )
}
