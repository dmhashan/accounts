export type MemberListItem = {
  id: number;
  biometric_member_id: string | null;
  name: string;
  email: string | null;
  phone_number: string | null;
  plan_name: string | null;
  membership_expiry_date: string | null;
  last_attendance_date: string | null;
  total_outstanding_amount: number;
  is_active: boolean;
  is_verified: boolean;
  is_temp: boolean;
  profile_photo_url: string | null;
};

export type MemberListResponse = {
  data: MemberListItem[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
  permissions: { create: boolean; edit: boolean; delete: boolean };
};

export type MemberDetail = {
  id: number;
  biometric_member_id: string | null;
  name: string;
  gender: string | null;
  email: string | null;
  phone_number: string | null;
  allow_sms: boolean;
  allow_whatsapp: boolean;
  whatsapp_number: string | null;
  nic: string | null;
  date_of_birth: string | null;
  address: string | null;
  admission_fee: string | number | null;
  payment_plan_id: number | null;
  payment_plan: string | null;
  price: string | number | null;
  current_balance: string | number | null;
  joined_date: string | null;
  comment: string | null;
  is_active: boolean;
  is_verified: boolean;
  is_temp: boolean;
  registration_source: string | null;
  campaign: { id: number; title: string; slug: string; status: string } | null;
  profile_photo_url: string | null;
  created_at: string | null;
  biometric_last_synced_at: string | null;
};

export type MemberDetailResponse = { data: MemberDetail; permissions: { edit: boolean; verify: boolean; delete: boolean } };
export type PaymentPlan = { id: number; name: string; price: number; is_active: boolean; duration_value: number; duration_unit: string };

export type MemberForm = {
  name: string;
  gender: string;
  email: string;
  phone_number: string;
  allow_sms: boolean;
  allow_whatsapp: boolean;
  whatsapp_number: string;
  nic: string;
  date_of_birth: string;
  address: string;
  admission_fee: string;
  payment_plan_id: string;
  joined_date: string;
  comment: string;
};