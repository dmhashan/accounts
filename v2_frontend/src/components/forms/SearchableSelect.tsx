import Select, { type Props as SelectProps, type StylesConfig } from 'react-select';

export type SelectOption<TValue extends string = string> = {
  value: TValue;
  label: string;
};

type SearchableSelectProps<TValue extends string = string> = Omit<
  SelectProps<SelectOption<TValue>, false>,
  'options' | 'value' | 'onChange' | 'styles'
> & {
  options: SelectOption<TValue>[];
  value: TValue;
  onValueChange: (value: TValue) => void;
  inputId?: string;
};

const styles: StylesConfig<SelectOption, false> = {
  control: (base, state) => ({
    ...base,
    minHeight: 44,
    borderRadius: 9,
    borderColor: state.isFocused ? 'var(--blue)' : 'var(--border-strong)',
    backgroundColor: 'var(--surface)',
    boxShadow: state.isFocused ? '0 0 0 3px color-mix(in srgb, var(--blue) 18%, transparent)' : 'none',
    ':hover': { borderColor: state.isFocused ? 'var(--blue)' : 'var(--border-strong)' },
  }),
  valueContainer: (base) => ({ ...base, padding: '4px 11px' }),
  input: (base) => ({ ...base, color: 'var(--text)' }),
  singleValue: (base) => ({ ...base, color: 'var(--text)' }),
  placeholder: (base) => ({ ...base, color: 'var(--muted)' }),
  menu: (base) => ({ ...base, zIndex: 80, borderRadius: 10, border: '1px solid var(--border)', boxShadow: 'var(--shadow-popover)', backgroundColor: 'var(--surface)' }),
  option: (base, state) => ({
    ...base,
    minHeight: 44,
    color: 'var(--text)',
    backgroundColor: state.isSelected ? 'var(--blue-soft)' : state.isFocused ? 'var(--surface-hover)' : 'transparent',
    cursor: 'pointer',
  }),
  indicatorSeparator: () => ({ display: 'none' }),
  dropdownIndicator: (base) => ({ ...base, color: 'var(--muted)' }),
  clearIndicator: (base) => ({ ...base, color: 'var(--muted)' }),
  multiValue: () => ({ display: 'none' }),
};

export function SearchableSelect<TValue extends string = string>({ options, value, onValueChange, inputId, className, ...props }: SearchableSelectProps<TValue>) {
  const selected = options.find((option) => option.value === value) ?? null;

  return <Select<SelectOption<TValue>, false>
    {...props}
    inputId={inputId}
    isSearchable
    isClearable={props.isClearable ?? false}
    options={options}
    value={selected}
    onChange={(option) => onValueChange((option?.value ?? '') as TValue)}
    className={`app-select${className ? ` ${className}` : ''}`}
    classNamePrefix="app-select"
    styles={styles as StylesConfig<SelectOption<TValue>, false>}
  />;
}
