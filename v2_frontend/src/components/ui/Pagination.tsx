import { Pagination as FlowbitePagination } from 'flowbite-react';

type Props = {
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  previousLabel?: string;
  nextLabel?: string;
  className?: string;
  ariaLabel?: string;
};

export function Pagination({ className = '', ariaLabel = 'Pagination', ...props }: Props) {
  return <FlowbitePagination layout="navigation" showIcons className={`ui-pagination ${className}`.trim()} aria-label={ariaLabel} {...props} />;
}
