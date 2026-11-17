import { forwardRef, type ComponentProps, type ReactNode } from 'react';
import { Button as FlowbiteButton } from 'flowbite-react';

type Tone = 'primary' | 'secondary' | 'danger' | 'ghost';

type Props = ComponentProps<'button'> & { children: ReactNode; tone?: Tone; compact?: boolean };

export const Button = forwardRef<HTMLButtonElement, Props>(function Button(
  { className = '', tone = 'secondary', compact = false, type = 'button', ...props },
  ref,
) {
  return <FlowbiteButton ref={ref} type={type} color="light" size={compact ? 'xs' : 'sm'} className={`ui-button ui-button--${tone}${compact ? ' ui-button--compact' : ''} ${className}`.trim()} {...props} />;
});
