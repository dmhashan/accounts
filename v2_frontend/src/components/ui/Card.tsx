import { type ComponentProps } from 'react';
import { Card as FlowbiteCard } from 'flowbite-react';

export function Card({ className = '', ...props }: ComponentProps<'div'>) {
  return <FlowbiteCard className={`ui-card ${className}`.trim()} {...props} />;
}
