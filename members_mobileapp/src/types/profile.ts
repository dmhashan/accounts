export type ProfileMeta = {
  name: string;
  username?: string | null;
  gender?: string | null;
  joined_date?: string | null;
  member_role?: string | null;
  email?: string | null;
  phone_number?: string | null;
  tenant_name?: string | null;
  total_outstanding?: string | number | null;
  current_balance?: number | string | null;
  profile_photo_url?: string | null;
};

export type WorkoutExercise = {
  exercise_name: string;
  w1_w3_exercise?: string | null;
  w2_w4_exercise?: string | null;
  sets?: number | string | null;
  reps?: number | string | null;
  tempo?: string | null;
  rest_seconds?: number | string | null;
};

export type WorkoutDay = {
  day_number?: number | string | null;
  title?: string | null;
  exercises?: WorkoutExercise[];
};

export type WorkoutPlan = {
  title: string;
  duration_weeks?: number | string | null;
  creator_name?: string | null;
  effective_date?: string | null;
  days?: WorkoutDay[];
  extras?: Array<Record<string, unknown>>;
};

export type Sale = {
  id: number | string;
  created_at?: string | null;
  total_amount?: string | number | null;
  paid_amount?: string | number | null;
  balance?: string | number | null;
  is_paid?: boolean;
  payment_method?: string | null;
  reference_number?: string | null;
  items?: Array<{
    product_name?: string | null;
    variation_name?: string | null;
    quantity?: number | string | null;
    unit_price?: string | number | null;
    subtotal?: string | number | null;
  }>;
};

export type WalletTransaction = {
  id?: number | string;
  created_at?: string | null;
  description?: string | null;
  type?: string | null;
  amount?: string | number | null;
  balance_after?: string | number | null;
};

export type WalletTxMeta = {
  current_page?: number;
  last_page?: number;
  total?: number;
  per_page?: number;
};

export type PublicProfilePayload = {
  meta: ProfileMeta;
  workouts: WorkoutPlan[];
  sales: Sale[];
  wallet_transactions: WalletTransaction[];
  wallet_tx_meta?: WalletTxMeta;
};
