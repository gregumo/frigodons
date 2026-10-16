import clsx from 'clsx'
import type {ReactNode} from 'react'
import {
  Form as AriaForm,
  Switch as AriaSwitch,
  Button,
  FieldError,
  Input,
  Label,
  TextArea,
  TextField,
  type TextFieldProps,
} from 'react-aria-components'
import {type FormProps, Form as RouterForm} from 'react-router'

/**
 * A React Router form, which displays the validation errors returned by the API under the fields.
 */
export function Form({
  validationErrors,
  children,
  ...props
}: FormProps & {validationErrors?: Record<string, string>}) {
  return (
    <AriaForm
      validationErrors={validationErrors}
      // The DOM props (ref, noValidate…) are typed for a plain form.
      render={(domProps) => <RouterForm {...(domProps as FormProps)} {...props} />}
    >
      {children}
    </AriaForm>
  )
}

type FieldProps = Omit<TextFieldProps, 'children'> & {
  label: string
  multiline?: boolean
}

/**
 * A text field, whose label is used as placeholder (as on the former website).
 */
export function Field({label, multiline = false, className, ...props}: FieldProps) {
  return (
    <TextField {...props} className={clsx('mb-4', className as string)}>
      <Label className="sr-only">{label}</Label>
      {multiline ? (
        <TextArea
          placeholder={label}
          rows={6}
          className="w-full rounded-2xl border-2 border-gray-200 bg-white px-5 py-2 outline-none focus:border-purple data-[invalid]:border-red-300"
        />
      ) : (
        <Input
          placeholder={label}
          className="w-full rounded-2xl border-2 border-gray-200 bg-white px-5 py-2 outline-none focus:border-purple data-[invalid]:border-red-300"
        />
      )}
      <FieldError className="mt-2 block rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700" />
    </TextField>
  )
}

type SwitchProps = {
  name: string
  label: string
  help: string
  defaultSelected: boolean
}

export function Switch({name, label, help, defaultSelected}: SwitchProps) {
  return (
    <div className="my-3">
      <AriaSwitch
        name={name}
        defaultSelected={defaultSelected}
        className="group inline-flex cursor-pointer items-center gap-3"
      >
        <div className="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition group-data-[selected]:bg-purple-600 group-data-[focus-visible]:ring-2 group-data-[focus-visible]:ring-purple-700">
          <span className="absolute top-0.5 left-0.5 h-5 w-5 rounded-full border border-gray-300 bg-white transition group-data-[selected]:translate-x-full group-data-[selected]:border-white" />
        </div>
        {label}
      </AriaSwitch>
      <p className="mt-1 ml-14 text-sm text-black-400">{help}</p>
    </div>
  )
}

export function SubmitButton({children, isPending}: {children: ReactNode; isPending: boolean}) {
  return (
    <Button
      type="submit"
      isPending={isPending}
      className="mt-4 mb-2 block min-h-12 w-full cursor-pointer rounded-2xl bg-purple-700 px-7 font-bold text-white transition-colors hover:bg-black data-[pending]:opacity-60"
    >
      {children}
    </Button>
  )
}

export function Alert({type, children}: {type: 'success' | 'error'; children: ReactNode}) {
  return (
    <div
      role={type === 'error' ? 'alert' : 'status'}
      className={clsx(
        'mb-4 rounded-lg px-4 py-2',
        type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700',
      )}
    >
      {children}
    </div>
  )
}

/**
 * Formats a phone number while typing, eg. "06 00 00 00 00".
 */
export function formatPhone(value: string): string {
  const digits = value.replace(/\D/g, '').slice(0, 10)

  return digits.replace(/(\d{2})(?=\d)/g, '$1 ')
}
