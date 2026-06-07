import { Ionicons } from '@expo/vector-icons';
import { StyleSheet, Text, View } from 'react-native';

import { colors } from '../theme';

type Props = {
  icon: keyof typeof Ionicons.glyphMap;
  text: string;
};

export function EmptyState({ icon, text }: Props) {
  return (
    <View style={styles.empty}>
      <Ionicons name={icon} size={42} color={colors.faint} />
      <Text style={styles.text}>{text}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  empty: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 54,
    gap: 10
  },
  text: {
    color: colors.muted,
    fontWeight: '700'
  }
});
