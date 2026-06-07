import { ActivityIndicator, Pressable, StyleSheet, Text } from 'react-native';

import { colors, radius } from '../theme';

type Props = {
  label: string;
  onPress: () => void;
  disabled?: boolean;
  loading?: boolean;
  variant?: 'primary' | 'danger' | 'ghost';
};

export function AppButton({ label, onPress, disabled, loading, variant = 'primary' }: Props) {
  const isDisabled = disabled || loading;

  return (
    <Pressable
      accessibilityRole="button"
      disabled={isDisabled}
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        variant === 'danger' && styles.danger,
        variant === 'ghost' && styles.ghost,
        pressed && !isDisabled && styles.pressed,
        isDisabled && styles.disabled
      ]}
    >
      {loading ? <ActivityIndicator color={variant === 'primary' ? '#fff' : colors.ink} /> : <Text style={[styles.label, variant !== 'primary' && styles.altLabel]}>{label}</Text>}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  button: {
    minHeight: 52,
    borderRadius: radius.lg,
    backgroundColor: colors.ink,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 18
  },
  danger: {
    backgroundColor: colors.dangerSoft,
    borderWidth: 1,
    borderColor: '#fecaca'
  },
  ghost: {
    backgroundColor: 'transparent'
  },
  label: {
    color: '#fff',
    fontSize: 15,
    fontWeight: '800'
  },
  altLabel: {
    color: colors.danger,
    fontWeight: '800'
  },
  pressed: {
    opacity: 0.88,
    transform: [{ scale: 0.99 }]
  },
  disabled: {
    opacity: 0.55
  }
});
