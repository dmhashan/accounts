import { KeyboardAvoidingView, Platform, StyleSheet, Text, TextInput, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

import { AppButton } from '../components/AppButton';
import { Card } from '../components/Card';
import { Screen } from '../components/Screen';
import { colors, radius } from '../theme';

type Props = {
  phoneNumber: string;
  otp: string;
  error: string;
  isLoading: boolean;
  onOtpChange: (value: string) => void;
  onBack: () => void;
  onSubmit: () => void;
};

export function OtpScreen({ phoneNumber, otp, error, isLoading, onOtpChange, onBack, onSubmit }: Props) {
  return (
    <Screen scroll={false}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={styles.wrap}>
        <View style={styles.header}>
          <View style={styles.mark}>
            <Ionicons name="lock-closed" size={30} color="#fff" />
          </View>
          <Text style={styles.title}>Verify OTP</Text>
          <Text style={styles.subtitle}>Code sent to {phoneNumber}</Text>
        </View>

        <Card>
          {error ? <Text style={styles.error}>{error}</Text> : null}

          <Text style={styles.label}>Verification Code</Text>
          <TextInput
            value={otp}
            onChangeText={(value) => onOtpChange(value.replace(/\D/g, '').slice(0, 6))}
            keyboardType="number-pad"
            autoComplete="one-time-code"
            maxLength={6}
            placeholder="000000"
            placeholderTextColor={colors.faint}
            style={styles.input}
          />

          <AppButton label="Verify & Continue" loading={isLoading} disabled={otp.length !== 6} onPress={onSubmit} />
          <View style={styles.back}>
            <AppButton label="Change number" variant="ghost" onPress={onBack} />
          </View>
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
    marginTop: 6
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
    marginBottom: 7
  },
  input: {
    minHeight: 58,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 15,
    marginBottom: 14,
    color: colors.ink,
    fontSize: 22,
    fontWeight: '900',
    textAlign: 'center',
    letterSpacing: 8,
    backgroundColor: '#fff'
  },
  back: {
    marginTop: 4
  }
});
