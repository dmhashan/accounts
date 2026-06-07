import { StyleSheet, Text, View } from 'react-native';

import { AppButton } from '../components/AppButton';
import { Avatar } from '../components/Avatar';
import { Card } from '../components/Card';
import { InfoRow } from '../components/InfoRow';
import { Screen } from '../components/Screen';
import { colors } from '../theme';
import type { PublicProfilePayload } from '../types/profile';
import { capitalize } from '../utils/format';

type Props = {
  profile: PublicProfilePayload;
  initials: string;
  onLogout: () => void;
};

export function ProfileScreen({ profile, initials, onLogout }: Props) {
  const { meta } = profile;

  return (
    <Screen>
      <View style={styles.hero}>
        <Avatar url={meta.profile_photo_url} initials={initials} size={96} />
        <Text style={styles.name}>{meta.name}</Text>
        {meta.username ? <Text style={styles.username}>@{meta.username}</Text> : null}
        {meta.member_role ? <Text style={styles.role}>{meta.member_role}</Text> : null}
      </View>

      <Text style={styles.sectionTitle}>Personal info</Text>
      <Card>
        <InfoRow icon="person-outline" label="Name" value={meta.name || '-'} />
        {meta.email ? <InfoRow icon="mail-outline" label="E-mail" value={meta.email} /> : null}
        {meta.phone_number ? <InfoRow icon="call-outline" label="Phone number" value={meta.phone_number} /> : null}
        {meta.gender ? <InfoRow icon="body-outline" label="Gender" value={capitalize(meta.gender)} /> : null}
        <InfoRow icon="calendar-outline" label="Member since" value={meta.joined_date || '-'} />
      </Card>

      <Text style={styles.sectionTitle}>Account info</Text>
      <Card>
        <InfoRow icon="barbell-outline" label="Workout Plans" value={`${profile.workouts.length} assigned`} />
        <InfoRow icon="receipt-outline" label="Transactions" value={`${profile.sales.length} total`} />
      </Card>

      <View style={styles.logout}>
        <AppButton label="Sign out" variant="danger" onPress={onLogout} />
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  hero: {
    alignItems: 'center',
    paddingTop: 10,
    paddingBottom: 24
  },
  name: {
    color: colors.ink,
    fontSize: 23,
    fontWeight: '900',
    marginTop: 14,
    textAlign: 'center'
  },
  username: {
    color: colors.faint,
    fontSize: 13,
    fontWeight: '700',
    marginTop: 4
  },
  role: {
    overflow: 'hidden',
    borderRadius: 999,
    backgroundColor: '#fff',
    color: colors.muted,
    borderColor: colors.border,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 5,
    fontSize: 12,
    fontWeight: '800',
    marginTop: 12
  },
  sectionTitle: {
    color: colors.ink,
    fontSize: 17,
    fontWeight: '900',
    marginBottom: 10,
    marginTop: 10
  },
  logout: {
    marginTop: 18,
    marginBottom: 10
  }
});
