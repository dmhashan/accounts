import {
  CreditCard,
  Wallet,
  Banknote,
  Coins,
  Building2,
  ArrowRightLeft,
  Smartphone,
  QrCode,
  Globe,
  Receipt,
  CheckSquare,
  ShieldCheck,
  Sparkles,
  Gift,
  Percent,
  HandCoins,
  Heart,
  User,
  Store,
  Calendar
} from 'lucide-vue-next';

export const predefinedIcons = [
  { key: 'CreditCard', label: 'Credit Card', component: CreditCard },
  { key: 'Wallet', label: 'Wallet', component: Wallet },
  { key: 'Banknote', label: 'Cash / Banknote', component: Banknote },
  { key: 'Coins', label: 'Coins', component: Coins },
  { key: 'Building2', label: 'Bank / Wire', component: Building2 },
  { key: 'ArrowRightLeft', label: 'Transfer', component: ArrowRightLeft },
  { key: 'Smartphone', label: 'Mobile Payment', component: Smartphone },
  { key: 'QrCode', label: 'QR Code', component: QrCode },
  { key: 'Globe', label: 'Online Payment', component: Globe },
  { key: 'Receipt', label: 'Receipt', component: Receipt },
  { key: 'CheckSquare', label: 'Check / Draft', component: CheckSquare },
  { key: 'ShieldCheck', label: 'Secure Pay', component: ShieldCheck },
  { key: 'Sparkles', label: 'Rewards / Points', component: Sparkles },
  { key: 'Gift', label: 'Gift Card', component: Gift },
  { key: 'Percent', label: 'Discount / Promo', component: Percent },
  { key: 'HandCoins', label: 'Hand Cash', component: HandCoins },
  { key: 'Heart', label: 'Donation', component: Heart },
  { key: 'User', label: 'Personal Member', component: User },
  { key: 'Store', label: 'In-Store checkout', component: Store },
  { key: 'Calendar', label: 'Subscription / Recurring', component: Calendar }
];

export const predefinedColors = [
  { key: 'emerald', label: 'Emerald', bg: 'bg-emerald-100 dark:bg-emerald-950/40', text: 'text-emerald-600 dark:text-emerald-400', border: 'border-emerald-200 dark:border-emerald-800' },
  { key: 'blue', label: 'Blue', bg: 'bg-blue-100 dark:bg-blue-950/40', text: 'text-blue-600 dark:text-blue-400', border: 'border-blue-200 dark:border-blue-800' },
  { key: 'indigo', label: 'Indigo', bg: 'bg-indigo-100 dark:bg-indigo-950/40', text: 'text-indigo-600 dark:text-indigo-400', border: 'border-indigo-200 dark:border-indigo-800' },
  { key: 'violet', label: 'Violet', bg: 'bg-violet-100 dark:bg-violet-950/40', text: 'text-violet-600 dark:text-violet-400', border: 'border-violet-200 dark:border-violet-800' },
  { key: 'purple', label: 'Purple', bg: 'bg-purple-100 dark:bg-purple-950/40', text: 'text-purple-600 dark:text-purple-400', border: 'border-purple-200 dark:border-purple-800' },
  { key: 'fuchsia', label: 'Fuchsia', bg: 'bg-fuchsia-100 dark:bg-fuchsia-950/40', text: 'text-fuchsia-600 dark:text-fuchsia-400', border: 'border-fuchsia-200 dark:border-fuchsia-800' },
  { key: 'pink', label: 'Pink', bg: 'bg-pink-100 dark:bg-pink-950/40', text: 'text-pink-600 dark:text-pink-400', border: 'border-pink-200 dark:border-pink-800' },
  { key: 'rose', label: 'Rose', bg: 'bg-rose-100 dark:bg-rose-950/40', text: 'text-rose-600 dark:text-rose-400', border: 'border-rose-200 dark:border-rose-800' },
  { key: 'red', label: 'Red', bg: 'bg-red-100 dark:bg-red-950/40', text: 'text-red-600 dark:text-red-400', border: 'border-red-200 dark:border-red-800' },
  { key: 'orange', label: 'Orange', bg: 'bg-orange-100 dark:bg-orange-950/40', text: 'text-orange-600 dark:text-orange-400', border: 'border-orange-200 dark:border-orange-800' },
  { key: 'amber', label: 'Amber', bg: 'bg-amber-100 dark:bg-amber-950/40', text: 'text-amber-600 dark:text-amber-400', border: 'border-amber-200 dark:border-amber-800' },
  { key: 'yellow', label: 'Yellow', bg: 'bg-yellow-100 dark:bg-yellow-950/40', text: 'text-yellow-600 dark:text-yellow-400', border: 'border-yellow-200 dark:border-yellow-800' },
  { key: 'lime', label: 'Lime', bg: 'bg-lime-100 dark:bg-lime-950/40', text: 'text-lime-600 dark:text-lime-400', border: 'border-lime-200 dark:border-lime-800' },
  { key: 'teal', label: 'Teal', bg: 'bg-teal-100 dark:bg-teal-950/40', text: 'text-teal-600 dark:text-teal-400', border: 'border-teal-200 dark:border-teal-800' },
  { key: 'cyan', label: 'Cyan', bg: 'bg-cyan-100 dark:bg-cyan-950/40', text: 'text-cyan-600 dark:text-cyan-400', border: 'border-cyan-200 dark:border-cyan-800' },
  { key: 'sky', label: 'Sky', bg: 'bg-sky-100 dark:bg-sky-950/40', text: 'text-sky-600 dark:text-sky-400', border: 'border-sky-200 dark:border-sky-800' },
  { key: 'slate', label: 'Slate', bg: 'bg-slate-100 dark:bg-slate-950/40', text: 'text-slate-600 dark:text-slate-400', border: 'border-slate-200 dark:border-slate-800' },
  { key: 'zinc', label: 'Zinc', bg: 'bg-zinc-100 dark:bg-zinc-950/40', text: 'text-zinc-600 dark:text-zinc-400', border: 'border-zinc-200 dark:border-zinc-800' },
  { key: 'stone', label: 'Stone', bg: 'bg-stone-100 dark:bg-stone-950/40', text: 'text-stone-600 dark:text-stone-400', border: 'border-stone-200 dark:border-stone-800' },
  { key: 'neutral', label: 'Neutral', bg: 'bg-neutral-100 dark:bg-neutral-950/40', text: 'text-neutral-600 dark:text-neutral-400', border: 'border-neutral-200 dark:border-neutral-800' }
];

export function getColorClasses(colorKey) {
  const match = predefinedColors.find(c => c.key === colorKey);
  if (match) return match;
  return predefinedColors.find(c => c.key === 'slate');
}

export function getIconComponent(iconKey) {
  const match = predefinedIcons.find(i => i.key === iconKey);
  return match ? match.component : CreditCard;
}
