import { type ComponentProps, type ReactNode } from 'react';
import { Alert as FlowbiteAlert } from 'flowbite-react';

type Tone = 'error' | 'success' | 'info';
type Props = Omit<ComponentProps<'div'>, 'color'> & { children: ReactNode; tone?: Tone };

export function Alert({ children, className = '', tone = 'info', ...props }: Props) {
  const color = tone === 'error' ? 'failure' : tone === 'success' ? 'success' : 'info';
  return <FlowbiteAlert color={color} className={`ui-alert ui-alert--${tone} ${className}`.trim()} {...props}>{children}</FlowbiteAlert>;
}
