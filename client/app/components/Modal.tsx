import type {ReactNode} from 'react'
import {Modal as AriaModal, Dialog, Heading, ModalOverlay} from 'react-aria-components'

type Props = {
  title: string
  isOpen: boolean
  onOpenChange: (isOpen: boolean) => void
  children: ReactNode
  /**
   * The buttons at the bottom of the modal.
   */
  footer: ReactNode
}

export function Modal({title, isOpen, onOpenChange, children, footer}: Props) {
  return (
    <ModalOverlay
      isOpen={isOpen}
      onOpenChange={onOpenChange}
      isDismissable
      className="fixed inset-0 z-50 flex overflow-y-auto bg-black/40 p-5"
    >
      {/* Auto margins center the modal, without hiding its top when it is higher than the screen. */}
      <AriaModal className="m-auto w-full max-w-3xl">
        <Dialog className="rounded-lg bg-white shadow-lg outline-none">
          <div className="border-b border-slate-200 p-5">
            <Heading slot="title" className="m-0 p-0 font-oswald text-2xl uppercase md:text-3xl">
              {title}
            </Heading>
          </div>
          <div className="p-6">{children}</div>
          <div className="flex flex-wrap justify-end gap-3 border-t border-slate-200 p-6">
            {footer}
          </div>
        </Dialog>
      </AriaModal>
    </ModalOverlay>
  )
}

export const modalButtonClassName =
  'inline-flex min-h-12 cursor-pointer items-center justify-center rounded-2xl bg-purple-700 px-6 text-sm font-bold text-white transition-colors hover:bg-black disabled:opacity-60'

/**
 * For the secondary action of a modal, next to a primary one (eg. "Retour" next to "Confirmer").
 */
export const modalSecondaryButtonClassName =
  'inline-flex min-h-12 cursor-pointer items-center justify-center rounded-2xl border-2 border-black bg-white px-6 text-sm font-bold text-black transition-colors hover:bg-black hover:text-white disabled:opacity-60'
