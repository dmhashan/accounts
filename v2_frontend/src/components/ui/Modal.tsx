import { type ReactNode } from 'react';
import { Modal as FlowbiteModal, ModalBody, ModalHeader } from 'flowbite-react';

type Props = { children: ReactNode; open: boolean; onClose: () => void; title: string; titleId: string; size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | '6xl' | '7xl'; className?: string; dismissible?: boolean };

export function Modal({ children, open, onClose, title, titleId, size = 'lg', className = '', dismissible = true }: Props) {
  const themeRoot = document.querySelector<HTMLElement>('.workspace-shell') || document.body;
  return <FlowbiteModal show={open} onClose={onClose} root={themeRoot} size={size} dismissible={dismissible} className={`ui-modal ${className}`.trim()} aria-labelledby={titleId}>
    <ModalHeader id={titleId}>{title}</ModalHeader>
    <ModalBody>{children}</ModalBody>
  </FlowbiteModal>;
}
