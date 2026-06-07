import { StyleSheet, Text, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

import { Card } from '../components/Card';
import { EmptyState } from '../components/EmptyState';
import { Screen } from '../components/Screen';
import { colors, radius } from '../theme';
import type { WorkoutPlan } from '../types/profile';

type Props = {
  workouts: WorkoutPlan[];
};

export function WorkoutsScreen({ workouts }: Props) {
  return (
    <Screen>
      <Text style={styles.title}>Workout Plans</Text>
      <Text style={styles.subtitle}>{workouts.length} assigned</Text>

      {!workouts.length ? <EmptyState icon="barbell-outline" text="No workout plan assigned yet" /> : null}

      {workouts.map((workout, index) => (
        <Card key={`${workout.title}-${index}`}>
          <View style={styles.planHeader}>
            <View style={styles.planCopy}>
              <Text style={styles.kicker}>{index === 0 ? 'Active Plan' : 'Plan'}</Text>
              <Text style={styles.planTitle}>{workout.title}</Text>
              {workout.creator_name ? <Text style={styles.meta}>by {workout.creator_name}</Text> : null}
            </View>
            <View style={styles.planIcon}>
              <Ionicons name="flash" size={20} color="#fff" />
            </View>
          </View>

          <View style={styles.planStats}>
            <Pill label={`${workout.duration_weeks || '-'} weeks`} />
            <Pill label={`${workout.days?.length || 0} days`} />
            <Pill label={workout.effective_date || 'No start date'} />
          </View>

          {workout.days?.map((day) => (
            <View key={`${day.day_number}-${day.title}`} style={styles.day}>
              <Text style={styles.dayTitle}>Day {day.day_number || '-'}{day.title ? ` · ${day.title}` : ''}</Text>
              {day.exercises?.map((exercise, exerciseIndex) => (
                <View key={`${exercise.exercise_name}-${exerciseIndex}`} style={styles.exercise}>
                  <Text style={styles.exerciseName}>{exercise.exercise_name}</Text>
                  <Text style={styles.exerciseMeta}>
                    {exercise.sets || '-'} sets · {exercise.reps || '-'} reps
                    {exercise.rest_seconds ? ` · ${exercise.rest_seconds}s rest` : ''}
                  </Text>
                </View>
              ))}
            </View>
          ))}
        </Card>
      ))}
    </Screen>
  );
}

function Pill({ label }: { label: string }) {
  return <Text style={styles.pill}>{label}</Text>;
}

const styles = StyleSheet.create({
  title: {
    color: colors.ink,
    fontSize: 27,
    fontWeight: '900'
  },
  subtitle: {
    color: colors.faint,
    fontSize: 13,
    fontWeight: '700',
    marginTop: 4,
    marginBottom: 18
  },
  planHeader: {
    flexDirection: 'row',
    gap: 14,
    alignItems: 'flex-start',
    justifyContent: 'space-between'
  },
  planCopy: {
    minWidth: 0,
    flex: 1
  },
  kicker: {
    color: colors.accent,
    fontSize: 11,
    fontWeight: '900',
    marginBottom: 6
  },
  planTitle: {
    color: colors.ink,
    fontSize: 19,
    fontWeight: '900'
  },
  meta: {
    color: colors.faint,
    fontSize: 12,
    fontWeight: '700',
    marginTop: 4
  },
  planIcon: {
    width: 40,
    height: 40,
    borderRadius: radius.md,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.accent
  },
  planStats: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 14,
    marginBottom: 10
  },
  pill: {
    overflow: 'hidden',
    borderRadius: 999,
    backgroundColor: '#f3f4f6',
    color: colors.muted,
    paddingHorizontal: 10,
    paddingVertical: 5,
    fontSize: 11,
    fontWeight: '800'
  },
  day: {
    borderTopWidth: 1,
    borderTopColor: '#f3f4f6',
    marginTop: 14,
    paddingTop: 14
  },
  dayTitle: {
    color: colors.ink,
    fontWeight: '900',
    fontSize: 14,
    marginBottom: 10
  },
  exercise: {
    paddingVertical: 8
  },
  exerciseName: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '800'
  },
  exerciseMeta: {
    color: colors.faint,
    fontSize: 12,
    fontWeight: '700',
    marginTop: 3
  }
});
