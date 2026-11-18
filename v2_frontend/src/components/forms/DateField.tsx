import { Datepicker, type DatepickerTheme } from 'flowbite-react';
import { useState, type ChangeEvent, type FocusEvent, type InputHTMLAttributes, type ReactNode } from 'react';
import { cx, textInputTheme } from './fieldStyles';
import { FormField, type FieldError } from './FormField';

export type DateFieldProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'type' | 'min' | 'max' | 'value' | 'defaultValue'> & {
  id: string;
  label: ReactNode;
  value?: string;
  min?: string;
  max?: string;
  optional?: boolean;
  error?: FieldError;
  errorId?: string;
  className?: string;
  labelClassName?: string;
  inputClassName?: string;
};

function parseDate(value?: string): Date | null {
  if (!value) return null;
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
  if (!match) return null;
  const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
  return date.getFullYear() === Number(match[1]) && date.getMonth() === Number(match[2]) - 1 && date.getDate() === Number(match[3]) ? date : null;
}

function formatDateValue(date: Date | null): string {
  if (!date) return '';
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function calendarTheme(openAbove: boolean, inputClassName?: string): Partial<DatepickerTheme> {
  return {
    root: {
      base: 'relative w-full',
      input: {
        ...textInputTheme,
        addon: '',
        field: {
          ...textInputTheme.field,
          icon: {
            base: 'pointer-events-none absolute inset-y-0 right-0 left-auto flex items-center pr-3',
            svg: 'h-5 w-5 text-[var(--muted)]',
          },
          rightIcon: {
            base: 'pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3',
            svg: 'h-5 w-5 text-[var(--muted)]',
          },
          input: {
            ...textInputTheme.field?.input,
            base: cx(textInputTheme.field?.input?.base, 'date-field-input pl-3 pr-10', inputClassName),
            sizes: { sm: '', md: '', lg: '' },
            colors: { gray: '', info: '', failure: '', warning: '', success: '' },
            withIcon: { on: 'pl-3 pr-10', off: '' },
          },
        },
      },
    },
    popup: {
      root: {
        base: cx('absolute z-[120] block', openAbove ? 'top-auto right-0 bottom-full pb-2' : 'top-full right-auto bottom-auto left-0 pt-2'),
        inline: '',
        inner: 'inline-block rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3 shadow-2xl',
      },
      header: {
        base: '',
        title: 'px-2 py-2 text-center font-semibold text-[var(--text)]',
        selectors: {
          base: 'mb-2 flex justify-between',
          button: {
            base: 'rounded-lg bg-[var(--surface)] px-3 py-2 text-sm font-semibold text-[var(--text)] hover:bg-[var(--surface-hover)] focus:outline-none focus:ring-2 focus:ring-[color-mix(in_srgb,var(--blue)_28%,transparent)]',
            prev: '',
            next: '',
            view: '',
          },
        },
      },
      view: { base: 'p-1' },
      footer: {
        base: 'mt-2 flex space-x-2',
        button: {
          base: 'w-full rounded-lg px-4 py-2 text-center text-sm font-medium focus:ring-2 focus:ring-[color-mix(in_srgb,var(--blue)_28%,transparent)]',
          today: 'bg-[var(--blue-hover)] text-white hover:brightness-95',
          clear: 'border border-[var(--border-strong)] bg-[var(--surface)] text-[var(--text)] hover:bg-[var(--surface-hover)]',
        },
      },
    },
  };
}

export function DateField({ id, label, optional, required, error, errorId, className, labelClassName, inputClassName, value = '', min, max, onChange, name, disabled, ...inputProps }: DateFieldProps) {
  const [openAbove, setOpenAbove] = useState(false);

  function selectDate(date: Date | null) {
    const nextValue = formatDateValue(date);
    onChange?.({
      target: { value: nextValue, name: name ?? '', id },
      currentTarget: { value: nextValue, name: name ?? '', id },
    } as unknown as ChangeEvent<HTMLInputElement>);
  }

  function choosePlacement(event: FocusEvent<HTMLInputElement>) {
    const rect = event.currentTarget.getBoundingClientRect();
    setOpenAbove(window.innerHeight - rect.bottom < 420 && rect.top > 380);
  }

  return <FormField id={id} label={label} optional={optional} required={required} error={error} errorId={errorId} className={className} labelClassName={labelClassName}>
    <div className="min-w-0" onFocusCapture={choosePlacement}>
      <Datepicker
        {...inputProps}
        id={id}
        name={name}
        value={parseDate(value)}
        onChange={selectDate}
        minDate={parseDate(min) ?? undefined}
        maxDate={parseDate(max) ?? undefined}
        disabled={disabled}
        required={required}
        autoHide
        showClearButton={!required}
        showTodayButton
        label="Select date"
        className="w-full"
        theme={calendarTheme(openAbove, inputClassName)}
      />
    </div>
  </FormField>;
}
