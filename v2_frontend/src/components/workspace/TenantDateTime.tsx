import { useEffect, useState } from 'react';
import type { TenantDateFormat, TenantTimeFormat } from '../../lib/api';
import { formatDateTime } from '../../lib/dateTime';
import { FormattedDate } from './FormattedDate';
import { FormattedTime } from './FormattedTime';

type TenantDateTimeProps = {
  dateFormat: TenantDateFormat;
  timeFormat: TenantTimeFormat;
};

export function TenantDateTime({ dateFormat, timeFormat }: TenantDateTimeProps) {
  const [now, setNow] = useState(() => new Date());

  useEffect(() => {
    const timer = window.setInterval(() => setNow(new Date()), 30_000);
    return () => window.clearInterval(timer);
  }, []);

  return (
    <div className="workspace-datetime" role="group" aria-label={`Local time ${formatDateTime(now, dateFormat, timeFormat)}`}>
      <FormattedDate value={now} format={dateFormat} />
      <span className="datetime-divider" aria-hidden="true" />
      <FormattedTime value={now} format={timeFormat} />
      <span className="datetime-local-label">LOCAL TIME</span>
    </div>
  );
}