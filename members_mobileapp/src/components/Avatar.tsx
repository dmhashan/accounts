import { Image, StyleSheet, Text, View } from 'react-native';

import { colors } from '../theme';

type Props = {
  url?: string | null;
  initials: string;
  size?: number;
};

export function Avatar({ url, initials, size = 58 }: Props) {
  const style = { width: size, height: size, borderRadius: size / 2 };

  if (url) {
    return <Image source={{ uri: url }} style={[styles.image, style]} />;
  }

  return (
    <View style={[styles.fallback, style]}>
      <Text style={[styles.initials, { fontSize: Math.max(16, size * 0.36) }]}>{initials}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  image: {
    backgroundColor: '#e5e7eb'
  },
  fallback: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.ink
  },
  initials: {
    color: '#fff',
    fontWeight: '900'
  }
});
