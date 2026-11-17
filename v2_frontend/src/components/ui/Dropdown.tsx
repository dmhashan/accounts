import { type ReactElement, type ReactNode } from 'react';
import { Dropdown as FlowbiteDropdown, DropdownDivider, DropdownHeader, DropdownItem } from 'flowbite-react';

type Props = { label: ReactNode; children: ReactNode; customTrigger?: ReactElement; className?: string; placement?: 'bottom' | 'bottom-start' | 'bottom-end' | 'left' | 'right' | 'top'; inline?: boolean; dismissOnClick?: boolean };

function DropdownRoot({ customTrigger, className = '', ...props }: Props) {
  return <FlowbiteDropdown renderTrigger={customTrigger ? () => customTrigger : undefined} className={`ui-dropdown ${className}`.trim()} {...props} />;
}

export const Dropdown = Object.assign(DropdownRoot, {
  Header: DropdownHeader,
  Item: DropdownItem,
  Divider: DropdownDivider,
});
