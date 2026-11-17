export const controlBase = 'block w-full min-w-0 min-h-11 rounded-[9px] border border-[var(--border-strong)] bg-[var(--surface)] px-3 py-2.5 text-[13px] font-normal text-[var(--text)] shadow-none outline-none transition-[border-color,box-shadow,background-color] duration-150 placeholder:text-[var(--quiet)] hover:border-[#b9c5d5] focus:border-[var(--blue)] focus:ring-[3px] focus:ring-[color-mix(in_srgb,var(--blue)_18%,transparent)] disabled:cursor-not-allowed disabled:bg-[var(--surface-soft)] disabled:opacity-70 aria-[invalid=true]:border-[var(--danger)] aria-[invalid=true]:focus:ring-[color-mix(in_srgb,var(--danger)_18%,transparent)]';

export const textInputTheme = {
  base: 'flex min-w-0 w-full',
  field: {
    base: 'relative w-full min-w-0',
    input: {
      base: controlBase,
      colors: { gray: '' },
      sizes: { md: '' },
      withIcon: { on: 'pl-10', off: '' },
      withRightIcon: { on: 'pr-12', off: '' },
      withAddon: { on: 'rounded-r-[9px]', off: 'rounded-[9px]' },
      withShadow: { on: '', off: '' },
    },
  },
} satisfies NonNullable<import('react').ComponentProps<typeof import('flowbite-react').TextInput>['theme']>;

export const textareaTheme = {
  base: controlBase,
  colors: { gray: '' },
  withShadow: { on: '', off: '' },
} satisfies NonNullable<import('react').ComponentProps<typeof import('flowbite-react').Textarea>['theme']>;

export function cx(...classes: Array<string | false | null | undefined>) {
  return classes.filter(Boolean).join(' ');
}
