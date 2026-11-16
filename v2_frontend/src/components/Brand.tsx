type BrandProps = {
  compact?: boolean;
  adminLabel?: boolean;
};

export function Brand({ compact = false, adminLabel = false }: BrandProps) {
  return (
    <span className={`brand-lockup${compact ? ' brand-lockup--compact' : ''}`}>
      <span className="brand-mark" aria-hidden="true">
        <span />
        <span />
        <span />
      </span>
      <span className="brand-wordmark">fitflow</span>
      {adminLabel && <span className="brand-admin">ADMIN</span>}
    </span>
  );
}