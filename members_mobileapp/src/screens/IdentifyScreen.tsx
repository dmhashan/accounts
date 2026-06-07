import { KeyboardAvoidingView, Platform, StyleSheet, Text, TextInput, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

import { AppButton } from '../components/AppButton';
import { Card } from '../components/Card';
import { Screen } from '../components/Screen';
import { colors, radius } from '../theme';

type Props = {
  baseUrl: string;
  phoneNumber: string;
  error: string;
  isLoading: boolean;
  onBaseUrlChange: (value: string) => void;
  onPhoneNumberChange: (value: string) => void;
  onSubmit: () => void;
};

export function IdentifyScreen({ baseUrl, phoneNumber, error, isLoading, onBaseUrlChange, onPhoneNumberChange, onSubmit }: Props) {
  return (
    <Screen scroll={false}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={styles.wrap}>
        <View style={styles.header}>
          <View style={styles.mark}>
            <Ionicons name="call" size={30} color="#fff" />
          </View>
          <Text style={styles.title}>Member Portal</Text>
          <Text style={styles.subtitle}>Enter your registered mobile number to continue.</Text>
        </View>

        <Card>
          {error ? <Text style={styles.error}>{error}</Text> : null}

          <Text style={styles.label}>Base URL</Text>
          <TextInput
            value={baseUrl}
            onChangeText={onBaseUrlChange}
            autoCapitalize="none"
            autoCorrect={false}
            keyboardType="url"
            placeholder="https://your-tenant.example.com"
            placeholderTextColor={colors.faint}
            style={styles.input}
          />

          <Text style={styles.label}>Mobile Number</Text>
          <TextInput
            value={phoneNumber}
            onChangeText={onPhoneNumberChange}
            keyboardType="phone-pad"
            autoComplete="tel"
            placeholder="e.g. 0771234567"
            placeholderTextColor={colors.faint}
            style={styles.input}
          />

          <AppButton label="Send OTP" loading={isLoading} disabled={!baseUrl.trim() || !phoneNumber.trim()} onPress={onSubmit} />
        </Card>
      </KeyboardAvoidingView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  wrap: {
    flex: 1,
    justifyContent: 'center'
  },
  header: {
    alignItems: 'center',
    marginBottom: 28
  },
  mark: {
    width: 66,
    height: 66,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.ink,
    marginBottom: 16
  },
  title: {
    color: colors.ink,
    fontSize: 27,
    fontWeight: '900'
  },
  subtitle: {
    color: colors.muted,
    fontSize: 14,
    fontWeight: '600',
    marginTop: 6,
    textAlign: 'center'
  },
  error: {
    color: colors.danger,
    backgroundColor: colors.dangerSoft,
    borderColor: '#fecaca',
    borderWidth: 1,
    borderRadius: radius.md,
    padding: 12,
    fontSize: 13,
    fontWeight: '700',
    marginBottom: 14
  },
  label: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0,
    marginBottom: 7,
    marginTop: 4
  },
  input: {
    minHeight: 52,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 15,
    marginBottom: 14,
    color: colors.ink,
    fontSize: 15,
    fontWeight: '700',
    backgroundColor: '#fff'
  }
});
