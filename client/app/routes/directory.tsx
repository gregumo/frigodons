import clsx from 'clsx'
import {useState} from 'react'
import {Button} from 'react-aria-components'
import {requireApi} from '~/api.server'
import {Modal, modalButtonClassName} from '~/components/Modal'
import {daysInYear, today} from '~/dates'
import type {DirectoryUser} from '~/types'
import type {Route} from './+types/directory'

export const meta = () => [{title: "Annuaire - Frig'o dons"}]

export async function loader({context, request}: Route.LoaderArgs) {
  const {api} = await requireApi(context, request)
  const users = await api.get<DirectoryUser[]>('/users')
  const days = daysInYear(Number(today().slice(0, 4)))
  const managers = users.filter((user) => user.manager)

  return {
    managers,
    volunteers: users.filter((user) => !user.manager),
    // The number of days each one should do so that every day is covered.
    cleanersMustDo: Math.round(days / users.length),
    supervisorsMustDo: Math.round(days / managers.length),
  }
}

function Contact({href, children}: {href: string; children: string}) {
  return (
    <a
      href={href}
      className="rounded bg-blue-100 px-2.5 py-0.5 whitespace-nowrap text-blue-700 hover:bg-blue-200"
    >
      {children}
    </a>
  )
}

function Check({value}: {value: boolean}) {
  return (
    <svg
      width="20"
      height="20"
      fill="currentColor"
      className="mx-auto inline-block"
      viewBox="0 0 16 16"
      role="img"
      aria-label={value ? 'Oui' : 'Non'}
    >
      <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
      {value && (
        <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z" />
      )}
    </svg>
  )
}

function Quota({done, mustDo}: {done: number; mustDo: number}) {
  const ratio = (done / mustDo) * 100

  return (
    <span
      className={clsx(
        'inline-block rounded px-2.5 py-0.5',
        ratio < 34
          ? 'bg-red-100 text-red-700'
          : ratio < 67
            ? 'bg-orange-100 text-orange-700'
            : 'bg-green-100 text-green-700',
      )}
    >
      {done} / {mustDo}
    </span>
  )
}

type ListProps = {
  title: string
  users: DirectoryUser[]
  isManager: boolean
  cleanersMustDo: number
  supervisorsMustDo: number
  onSelect: (user: DirectoryUser) => void
}

const th =
  'border-b-2 border-gray-200 bg-blue-400 px-5 py-2 text-left text-sm font-normal text-white uppercase'
const td = 'border-b border-gray-200 px-5 py-2'

function UserTable({
  title,
  users,
  isManager,
  cleanersMustDo,
  supervisorsMustDo,
  onSelect,
}: ListProps) {
  return (
    <>
      <h2>{title}</h2>
      <table className="min-w-full table-auto text-sm leading-normal">
        <thead>
          <tr>
            <th className={clsx(th, 'hidden md:table-cell')}>Prénom</th>
            <th className={th}>Nom</th>
            <th className={clsx(th, 'hidden xl:table-cell')}>E-mail</th>
            <th className={clsx(th, 'hidden lg:table-cell')}>Téléphone</th>
            <th className={clsx(th, 'hidden text-center md:table-cell')}>Mail rappel</th>
            <th className={clsx(th, 'hidden text-center md:table-cell')}>Mail manque bénévole</th>
            {isManager ? (
              <th className={clsx(th, 'text-center')}>Quota responsable</th>
            ) : (
              <th className={clsx(th, 'text-center')}>Formé·e ?</th>
            )}
            <th className={clsx(th, 'text-center')}>Quota nettoyage</th>
            <th className="sr-only xl:hidden">Détails</th>
          </tr>
        </thead>
        <tbody>
          {users.map((user) => (
            <tr key={user.id} className="odd:bg-white even:bg-gray-100 hover:bg-gray-200">
              <td className={clsx(td, 'hidden md:table-cell')}>{user.firstname}</td>
              <td className={td}>
                <span className="hidden md:block">{user.lastname}</span>
                <span className="md:hidden">
                  {user.firstname} {user.lastname.charAt(0)}.
                </span>
              </td>
              <td className={clsx(td, 'hidden xl:table-cell')}>
                <Contact href={`mailto:${user.email}`}>{user.email}</Contact>
              </td>
              <td className={clsx(td, 'hidden lg:table-cell')}>
                {user.phone && <Contact href={`tel:${user.phone}`}>{user.phone}</Contact>}
              </td>
              <td className={clsx(td, 'hidden text-center md:table-cell')}>
                <Check value={user.callbackMailOptIn} />
              </td>
              <td className={clsx(td, 'hidden text-center md:table-cell')}>
                <Check value={user.missingVolunteerMailOptIn} />
              </td>
              <td className={clsx(td, 'text-center')}>
                {isManager ? (
                  <Quota done={user.yearSupervisingCount} mustDo={supervisorsMustDo} />
                ) : (
                  <Check value={user.hasBeenTrained} />
                )}
              </td>
              <td className={clsx(td, 'text-center')}>
                <Quota done={user.yearCleaningCount} mustDo={cleanersMustDo} />
              </td>
              {/* On small screens, the hidden columns are displayed in a modal. */}
              <td className={clsx(td, 'xl:hidden')}>
                <Button
                  onPress={() => onSelect(user)}
                  className="cursor-pointer rounded px-2 text-blue-700 hover:bg-blue-100"
                  aria-label={`Voir les informations de ${user.firstname} ${user.lastname}`}
                >
                  …
                </Button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </>
  )
}

export default function Directory({loaderData}: Route.ComponentProps) {
  const {managers, volunteers, cleanersMustDo, supervisorsMustDo} = loaderData
  const [selected, setSelected] = useState<DirectoryUser | null>(null)
  const props = {cleanersMustDo, supervisorsMustDo, onSelect: setSelected}

  return (
    <>
      <h1>Annuaire</h1>
      <UserTable title="Responsables" users={managers} isManager {...props} />
      <UserTable title="Bénévoles" users={volunteers} isManager={false} {...props} />

      {selected && (
        <Modal
          title="Informations utilisateur"
          isOpen
          onOpenChange={(isOpen) => !isOpen && setSelected(null)}
          footer={
            <Button
              onPress={() => setSelected(null)}
              className={clsx(modalButtonClassName, 'bg-purple-600 hover:bg-purple-700')}
            >
              Fermer
            </Button>
          }
        >
          <ul className="space-y-1">
            <li>
              <b>Prénom : </b>
              {selected.firstname}
            </li>
            <li>
              <b>Nom : </b>
              {selected.lastname}
            </li>
            <li>
              <b>E-mail : </b>
              <Contact href={`mailto:${selected.email}`}>{selected.email}</Contact>
            </li>
            <li>
              <b>Téléphone : </b>
              {selected.phone && <Contact href={`tel:${selected.phone}`}>{selected.phone}</Contact>}
            </li>
            <li>
              <b>Mail rappel : </b>
              {selected.callbackMailOptIn ? 'Oui' : 'Non'}
            </li>
            <li>
              <b>Mail manque bénévole : </b>
              {selected.missingVolunteerMailOptIn ? 'Oui' : 'Non'}
            </li>
            {selected.manager ? (
              <li>
                <b>Quota responsable : </b>
                <Quota done={selected.yearSupervisingCount} mustDo={supervisorsMustDo} />
              </li>
            ) : (
              <li>
                <b>Formé·e : </b>
                {selected.hasBeenTrained ? 'Oui' : 'Non'}
              </li>
            )}
            <li>
              <b>Quota nettoyage : </b>
              <Quota done={selected.yearCleaningCount} mustDo={cleanersMustDo} />
            </li>
          </ul>
        </Modal>
      )}
    </>
  )
}
