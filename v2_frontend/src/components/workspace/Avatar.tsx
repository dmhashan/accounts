import { UserRound } from 'lucide-react';

type AvatarProps = {
  size?: 'default' | 'large';
};

export function Avatar({ size = 'default' }: AvatarProps) {
  return (
    <span className={`profile-avatar${size === 'large' ? ' profile-avatar--large' : ''}`} aria-hidden="true">
      <UserRound size={size === 'large' ? 19 : 17} strokeWidth={1.9} />
    </span>
  );
}
