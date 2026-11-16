type AvatarProps = {
  size?: 'default' | 'large';
  name?: string;
};

export function Avatar({ size = 'default', name = '' }: AvatarProps) {
  const initials = name.trim().split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]?.toLocaleUpperCase()).join('');

  return (
    <span className={`profile-avatar${size === 'large' ? ' profile-avatar--large' : ''}`} aria-hidden="true" title={name || undefined}>
      {initials || '?'}
    </span>
  );
}
