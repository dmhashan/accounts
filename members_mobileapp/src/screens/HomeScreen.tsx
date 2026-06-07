import { Ionicons } from '@expo/vector-icons';
import { StyleSheet, Text, View } from 'react-native';

import { Avatar } from '../components/Avatar';
import { Card } from '../components/Card';
import { EmptyState } from '../components/EmptyState';
import { Screen } from '../components/Screen';
import { colors, radius } from '../theme';
import type { PublicProfilePayload, Sale, WorkoutPlan } from '../types/profile';
import { firstName, greeting, money } from '../utils/format';

type Props = {
  profile: PublicProfilePayload;
  initials: string;
};

export function HomeScreen({ profile, initials }: Props) {
  const latestWorkout = profile.workouts[0];
  const latestSales = profile.sales.slice(0, 3);
  const outstanding = profile.sales.filter((sale) => !sale.is_paid);

  return (
    <Screen>
      <View style={styles.header}>
        <View style={styles.headerText}>
          <Text style={styles.greeting}>{greeting()},</Text>
          <Text style={styles.name}>{firstName(profile.meta.name)}</Text>
          <Text style={styles.tenant}>{profile.meta.tenant_name || 'Member Portal'}</Text>
        </View>
        <Avatar url={profile.meta.profile_photo_url} initials={initials} />
      </View>

      <View style={styles.balanceCard}>
        <Text style={styles.balanceLabel}>Wallet Balance</Text>
        <Text style={styles.balanceValue}>{money(profile.meta.current_balance)}</Text>
      </View>

      {latestWorkout ? <WorkoutSummary workout={latestWorkout} /> : null}

      {outstanding.length ? (
        <View style={styles.outstanding}>
          <Text style={styles.outstandingLabel}>Total outstanding</Text>
          <Text style={styles.outstandingValue}>{money(profile.meta.total_outstanding)}</Text>
        </View>
      ) : null}

      {latestSales.length ? (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Recent Payments</Text>
          <Card>
            {latestSales.map((sale) => (
              <SaleRow key={String(sale.id)} sale={sale} />
            ))}
          </Card>
        </View>
      ) : null}

      {!profile.workouts.length && !profile.sales.length ? <EmptyState icon="file-tray-outline" text="No data yet" /> : null}
    </Screen>
  );
}

function WorkoutSummary({ workout }: { workout: WorkoutPlan }) {
  return (
    <View style={styles.workoutCard}>
      <View style={styles.workoutTop}>
        <View>
          <Text style={styles.workoutKicker}>Active Plan</Text>
          <Text style={styles.workoutTitle}>{workout.title}</Text>
          {workout.creator_name ? <Text style={styles.workoutMeta}>by {workout.creator_name}</Text> : null}
        </View>
        <View style={styles.zap}>
          <Ionicons name="flash" color="#fff" size={20} />
        </View>
      </View>
      <View style={styles.stats}>
        <Stat label="Duration" value={`${workout.duration_weeks || '-'} wks`} />
        <Stat label="Start" value={workout.effective_date || '-'} />
        <Stat label="Days" value={String(workout.days?.length || '-')} />
      </View>
    </View>
  );
}

function Stat({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.stat}>
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={styles.statValue}>{value}</Text>
    </View>
  );
}

function SaleRow({ sale }: { sale: Sale }) {
  return (
    <View style={styles.saleRow}>
      <View style={[styles.saleIcon, !sale.is_paid && styles.saleIconDue]}>
        <Ionicons name="receipt-outline" size={18} color={!sale.is_paid ? colors.danger : colors.muted} />
      </View>
      <View style={styles.saleCopy}>
        <Text style={styles.saleTitle}>Invoice #{sale.id}</Text>
        <Text style={styles.saleDate}>{sale.created_at || '-'}</Text>
      </View>
      <View style={styles.saleAmount}>
        <Text style={styles.saleTotal}>{money(sale.total_amount)}</Text>
        <Text style={[styles.saleBadge, !sale.is_paid ? styles.saleDue : styles.salePaid]}>{sale.is_paid ? 'Paid' : 'Unpaid'}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 22
  },
  headerText: {
    minWidth: 0,
    flex: 1,
    paddingRight: 12
  },
  greeting: {
    color: colors.faint,
    fontSize: 14,
    fontWeight: '700'
  },
  name: {
    color: colors.ink,
    fontSize: 27,
    fontWeight: '900',
    marginTop: 2
  },
  tenant: {
    color: colors.faint,
    fontSize: 12,
    fontWeight: '700',
    marginTop: 3
  },
  balanceCard: {
    borderRadius: radius.xl,
    padding: 22,
    marginBottom: 18,
    backgroundColor: colors.success
  },
  balanceLabel: {
    color: '#bbf7d0',
    fontSize: 11,
    fontWeight: '900',
    marginBottom: 6
  },
  balanceValue: {
    color: '#fff',
    fontSize: 34,
    fontWeight: '900'
  },
  workoutCard: {
    borderRadius: radius.xl,
    padding: 22,
    marginBottom: 18,
    backgroundColor: '#18181b'
  },
  workoutTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 16
  },
  workoutKicker: {
    color: colors.accent,
    fontSize: 11,
    fontWeight: '900',
    marginBottom: 8
  },
  workoutTitle: {
    color: '#fff',
    fontSize: 21,
    fontWeight: '900'
  },
  workoutMeta: {
    color: '#a1a1aa',
    fontSize: 12,
    fontWeight: '700',
    marginTop: 4
  },
  zap: {
    width: 42,
    height: 42,
    borderRadius: radius.md,
    backgroundColor: colors.accent,
    alignItems: 'center',
    justifyContent: 'center'
  },
  stats: {
    flexDirection: 'row',
    marginTop: 22,
    gap: 22
  },
  stat: {
    flex: 1
  },
  statLabel: {
    color: '#a1a1aa',
    fontSize: 10,
    fontWeight: '800',
    marginBottom: 4
  },
  statValue: {
    color: '#fff',
    fontSize: 13,
    fontWeight: '900'
  },
  outstanding: {
    borderRadius: radius.xl,
    padding: 24,
    alignItems: 'center',
    marginBottom: 18,
    backgroundColor: colors.danger
  },
  outstandingLabel: {
    color: '#fee2e2',
    fontSize: 11,
    fontWeight: '900',
    marginBottom: 8
  },
  outstandingValue: {
    color: '#fff',
    fontSize: 38,
    fontWeight: '900'
  },
  section: {
    marginBottom: 18
  },
  sectionTitle: {
    color: colors.ink,
    fontSize: 17,
    fontWeight: '900',
    marginBottom: 10
  },
  saleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 11
  },
  saleIcon: {
    width: 38,
    height: 38,
    borderRadius: radius.md,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center'
  },
  saleIconDue: {
    backgroundColor: colors.dangerSoft
  },
  saleCopy: {
    minWidth: 0,
    flex: 1
  },
  saleTitle: {
    color: colors.ink,
    fontWeight: '900',
    fontSize: 14
  },
  saleDate: {
    color: colors.faint,
    fontWeight: '700',
    fontSize: 11,
    marginTop: 3
  },
  saleAmount: {
    alignItems: 'flex-end'
  },
  saleTotal: {
    color: colors.ink,
    fontWeight: '900',
    fontSize: 14
  },
  saleBadge: {
    marginTop: 4,
    overflow: 'hidden',
    borderRadius: 8,
    paddingHorizontal: 6,
    paddingVertical: 2,
    fontSize: 10,
    fontWeight: '900'
  },
  saleDue: {
    color: colors.danger,
    backgroundColor: colors.dangerSoft
  },
  salePaid: {
    color: colors.success,
    backgroundColor: colors.successSoft
  }
});
