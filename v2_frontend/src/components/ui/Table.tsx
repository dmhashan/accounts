import { type ComponentProps } from 'react';
import { Table as FlowbiteTable, TableBody, TableCell, TableHead, TableHeadCell, TableRow } from 'flowbite-react';

type RootProps = ComponentProps<'table'> & { striped?: boolean; hoverable?: boolean };

type TableComponent = typeof FlowbiteTable & { Head: typeof TableHead; HeadCell: typeof TableHeadCell; Body: typeof TableBody; Row: typeof TableRow; Cell: typeof TableCell };

function TableRoot({ className = '', ...props }: RootProps) {
  return <FlowbiteTable className={`ui-table ${className}`.trim()} {...props} />;
}

export const Table = TableRoot as TableComponent;
Table.Head = TableHead;
Table.HeadCell = TableHeadCell;
Table.Body = TableBody;
Table.Row = TableRow;
Table.Cell = TableCell;
