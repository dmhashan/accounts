import { useEffect, useState } from 'react';

type BrandProps = {
  compact?: boolean;
  adminLabel?: boolean;
  tenantName?: string;
  logoUrl?: string | null;
  poweredBy?: boolean;
};

export function Brand({ compact = false, adminLabel = false, tenantName, logoUrl, poweredBy = false }: BrandProps) {
  const [logoFailed, setLogoFailed] = useState(false);

  useEffect(() => setLogoFailed(false), [logoUrl]);

  return (
    <span className={`brand-lockup${compact ? ' brand-lockup--compact' : ''}`}>
      {tenantName && logoUrl && !logoFailed ? (
        <img className="brand-logo" src={logoUrl} alt="" onError={() => setLogoFailed(true)} />
      ) : (
        <span className="brand-mark" aria-hidden="true"><span /><span /><span /></span>
      )}
      <span className="brand-name-group">
        <span className="brand-wordmark" title={tenantName || 'FitFlow'}>{tenantName || 'FitFlow'}</span>
        {poweredBy && <span className="brand-powered-by">POWERED BY FITFLOW</span>}
      </span>
      {adminLabel && <span className="brand-admin">ADMIN</span>}
    </span>
  );
}