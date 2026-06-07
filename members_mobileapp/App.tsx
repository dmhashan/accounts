import { StatusBar } from 'expo-status-bar';
import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { IdentifyScreen } from './src/screens/IdentifyScreen';
import { OtpScreen } from './src/screens/OtpScreen';
import { HomeScreen } from './src/screens/HomeScreen';
import { WorkoutsScreen } from './src/screens/WorkoutsScreen';
import { TransactionsScreen } from './src/screens/TransactionsScreen';
import { ProfileScreen } from './src/screens/ProfileScreen';
import { ApiError, fetchProfile, requestOtp, verifyOtp } from './src/services/api';
import { clearSession, getSession, saveBaseUrl, saveSession } from './src/services/session';
import type { PublicProfilePayload } from './src/types/profile';
import { colors } from './src/theme';

type AuthScreen = 'loading' | 'identify' | 'otp' | 'profile';
type RootTabs = {
  Home: undefined;
  Workouts: undefined;
  Transactions: undefined;
  Profile: undefined;
};

const Tab = createBottomTabNavigator<RootTabs>();

export default function App() {
  const [screen, setScreen] = useState<AuthScreen>('loading');
  const [baseUrl, setBaseUrl] = useState('');
  const [phoneNumber, setPhoneNumber] = useState('');
  const [otp, setOtp] = useState('');
  const [token, setToken] = useState<string | null>(null);
  const [profile, setProfile] = useState<PublicProfilePayload | null>(null);
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    let mounted = true;

    async function bootstrap() {
      const session = await getSession();
      if (!mounted) return;

      setBaseUrl(session.baseUrl);
      if (session.token && session.baseUrl) {
        setToken(session.token);
        await loadProfile(session.baseUrl, session.token);
        return;
      }

      setScreen('identify');
    }

    bootstrap();
    return () => {
      mounted = false;
    };
  }, []);

  const memberName = profile?.meta.name ?? '';
  const initials = useMemo(() => {
    const parts = memberName.trim().split(/\s+/).filter(Boolean);
    if (parts.length > 1) return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
    return (parts[0]?.[0] ?? '?').toUpperCase();
  }, [memberName]);

  async function loadProfile(nextBaseUrl = baseUrl, nextToken = token) {
    if (!nextBaseUrl || !nextToken) {
      setScreen('identify');
      return;
    }

    setIsLoading(true);
    setError('');
    try {
      const data = await fetchProfile(nextBaseUrl, nextToken);
      setProfile(data);
      setScreen('profile');
    } catch (err) {
      await clearSession();
      setToken(null);
      setProfile(null);
      setScreen('identify');
      setError(err instanceof ApiError ? err.message : 'Could not load your profile. Please sign in again.');
    } finally {
      setIsLoading(false);
    }
  }

  async function handleRequestOtp() {
    setIsLoading(true);
    setError('');
    try {
      await saveBaseUrl(baseUrl);
      await requestOtp(baseUrl, phoneNumber);
      setScreen('otp');
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Network error. Please try again.');
    } finally {
      setIsLoading(false);
    }
  }

  async function handleVerifyOtp() {
    setIsLoading(true);
    setError('');
    try {
      const verified = await verifyOtp(baseUrl, phoneNumber, otp);
      setToken(verified.token);
      await saveSession(baseUrl, verified.token);
      await loadProfile(baseUrl, verified.token);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Invalid OTP. Please try again.');
    } finally {
      setIsLoading(false);
    }
  }

  async function handleLogout() {
    await clearSession();
    setToken(null);
    setProfile(null);
    setPhoneNumber('');
    setOtp('');
    setError('');
    setScreen('identify');
  }

  if (screen === 'loading') {
    return (
      <SafeAreaProvider>
        <View style={styles.loading}>
          <ActivityIndicator color={colors.ink} />
          <Text style={styles.loadingText}>Loading member portal...</Text>
        </View>
        <StatusBar style="dark" />
      </SafeAreaProvider>
    );
  }

  if (screen === 'identify') {
    return (
      <SafeAreaProvider>
        <IdentifyScreen
          baseUrl={baseUrl}
          phoneNumber={phoneNumber}
          error={error}
          isLoading={isLoading}
          onBaseUrlChange={setBaseUrl}
          onPhoneNumberChange={setPhoneNumber}
          onSubmit={handleRequestOtp}
        />
        <StatusBar style="dark" />
      </SafeAreaProvider>
    );
  }

  if (screen === 'otp') {
    return (
      <SafeAreaProvider>
        <OtpScreen
          phoneNumber={phoneNumber}
          otp={otp}
          error={error}
          isLoading={isLoading}
          onOtpChange={setOtp}
          onBack={() => {
            setOtp('');
            setError('');
            setScreen('identify');
          }}
          onSubmit={handleVerifyOtp}
        />
        <StatusBar style="dark" />
      </SafeAreaProvider>
    );
  }

  if (!profile) {
    return (
      <SafeAreaProvider>
        <View style={styles.loading}>
          <ActivityIndicator color={colors.ink} />
        </View>
      </SafeAreaProvider>
    );
  }

  return (
    <SafeAreaProvider>
      <NavigationContainer>
        <Tab.Navigator
          screenOptions={({ route }) => ({
            headerShown: false,
            tabBarActiveTintColor: colors.ink,
            tabBarInactiveTintColor: colors.muted,
            tabBarStyle: styles.tabBar,
            tabBarLabelStyle: styles.tabLabel,
            tabBarIcon: ({ color, size }) => {
              const iconName =
                route.name === 'Home'
                  ? 'home'
                  : route.name === 'Workouts'
                    ? 'barbell'
                    : route.name === 'Transactions'
                      ? 'receipt'
                      : 'person-circle';
              return <Ionicons name={iconName} size={size} color={color} />;
            }
          })}
        >
          <Tab.Screen name="Home">
            {() => <HomeScreen profile={profile} initials={initials} />}
          </Tab.Screen>
          <Tab.Screen name="Workouts">
            {() => <WorkoutsScreen workouts={profile.workouts} />}
          </Tab.Screen>
          <Tab.Screen name="Transactions">
            {() => <TransactionsScreen sales={profile.sales} walletTransactions={profile.wallet_transactions} />}
          </Tab.Screen>
          <Tab.Screen name="Profile">
            {() => <ProfileScreen profile={profile} initials={initials} onLogout={handleLogout} />}
          </Tab.Screen>
        </Tab.Navigator>
      </NavigationContainer>
      <StatusBar style="dark" />
    </SafeAreaProvider>
  );
}

const styles = StyleSheet.create({
  loading: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.background,
    gap: 12
  },
  loadingText: {
    color: colors.muted,
    fontSize: 14,
    fontWeight: '600'
  },
  tabBar: {
    borderTopColor: colors.border,
    height: 68,
    paddingBottom: 10,
    paddingTop: 8
  },
  tabLabel: {
    fontSize: 11,
    fontWeight: '700'
  }
});
