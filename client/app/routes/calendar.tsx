import clsx from 'clsx'
import {useEffect, useState} from 'react'
import {Button} from 'react-aria-components'
import {data, Link, useFetcher} from 'react-router'
import {ApiError, requireApi} from '~/api.server'
import {Alert} from '~/components/Form'
import {Modal, modalButtonClassName} from '~/components/Modal'
import {Participation} from '~/components/Participation'
import {formatDay, formatMonth, monthViewDays, today} from '~/dates'
import type {
  CalendarDate,
  DateContext,
  DateUser,
  Participation as ParticipationData,
  Stats,
  User,
} from '~/types'
import type {Route} from './+types/calendar'

export const meta = () => [{title: "Planning des bénévoles - Frig'o dons"}]

const WEEK_DAYS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']

const RESOURCES: Record<DateContext, {path: string; userField: 'cleaner' | 'supervisor'}> = {
  cleaning: {path: '/cleaning-dates', userField: 'cleaner'},
  supervising: {path: '/supervising-dates', userField: 'supervisor'},
}

type ApiDate = Omit<CalendarDate, 'user'> & {cleaner?: DateUser; supervisor?: DateUser}

export async function loader({context, request, params}: Route.LoaderArgs) {
  const {api} = await requireApi(context, request)

  const now = today()
  const month = params.month ? Number(params.month) : Number(now.slice(5, 7))
  const year = params.year ? Number(params.year) : Number(now.slice(0, 4))
  if (
    !Number.isInteger(month) ||
    month < 1 ||
    month > 12 ||
    !Number.isInteger(year) ||
    year < 2000 ||
    year > 2100
  ) {
    throw data('Page introuvable', {status: 404})
  }

  const days = monthViewDays(year, month)
  const range = `day[after]=${days[0]}&day[before]=${days[days.length - 1]}`
  const fetchDates = async (dateContext: DateContext) => {
    const {path, userField} = RESOURCES[dateContext]
    const dates = await api.get<ApiDate[]>(`${path}?${range}`)

    return Object.fromEntries(
      dates.map(({cleaner, supervisor, ...date}) => [
        date.day,
        {...date, user: (userField === 'cleaner' ? cleaner : supervisor) as DateUser},
      ]),
    )
  }

  const [me, cleaningDates, supervisingDates, participation, stats] = await Promise.all([
    api.get<User>('/me'),
    fetchDates('cleaning'),
    fetchDates('supervising'),
    api.get<ParticipationData>('/participation'),
    api.get<Stats>('/stats'),
  ])

  return {today: now, year, month, days, me, cleaningDates, supervisingDates, participation, stats}
}

export async function action({context, request}: Route.ActionArgs) {
  const {api} = await requireApi(context, request)
  const formData = await request.formData()
  const {path} = RESOURCES[formData.get('context') === 'supervising' ? 'supervising' : 'cleaning']

  try {
    if (formData.get('intent') === 'delete') {
      await api.delete(`${path}/${Number(formData.get('id'))}`)
    } else {
      await api.post(path, {day: String(formData.get('day'))})
    }
  } catch (error) {
    if (error instanceof ApiError) {
      return {ok: false, error: error.violations[0]?.message ?? error.message}
    }
    throw error
  }

  return {ok: true, error: null}
}

/**
 * What happens when clicking on a day:
 * - add: the day is free and can be booked;
 * - delete: the day is booked by the user, who can cancel it;
 * - unauthorized_delete: the day is booked by the user, but it is too late to cancel it;
 * - contact: the day is booked by someone else, whose contact details are displayed;
 * - blank: volunteers cannot book supervising days.
 */
type Mode = 'add' | 'delete' | 'unauthorized_delete' | 'contact' | 'blank'

type Cell = {
  dateContext: DateContext
  day: string
  date: CalendarDate | undefined
  mode: Mode
  // Past days cannot be changed.
  clickable: boolean
}

function getCell(
  dateContext: DateContext,
  day: string,
  date: CalendarDate | undefined,
  me: User,
  now: string,
): Cell {
  if (date) {
    const mine = date.user.id === me.id
    const mode = mine ? (date.cancellable ? 'delete' : 'unauthorized_delete') : 'contact'

    return {dateContext, day, date, mode, clickable: day >= now}
  }

  if (dateContext === 'supervising' && !me.manager) {
    return {dateContext, day, date, mode: 'blank', clickable: false}
  }

  return {dateContext, day, date, mode: 'add', clickable: day >= now}
}

function UserName({user}: {user: DateUser}) {
  return (
    <>
      <span className="hidden md:inline-block">
        {user.firstname} {user.lastname}
      </span>
      <span className="inline-block md:hidden">
        {user.firstname} {user.lastname.charAt(0)}.
      </span>
    </>
  )
}

function CellButton({
  cell,
  onSelect,
  className,
  children,
}: {
  cell: Cell
  onSelect: (cell: Cell) => void
  className: string
  children: React.ReactNode
}) {
  const label = `${formatDay(cell.day)}${cell.dateContext === 'supervising' ? ', responsable' : ''} : ${
    cell.date ? `${cell.date.user.firstname} ${cell.date.user.lastname}` : 'libre'
  }`

  if (!cell.clickable) {
    return <div className={className}>{children}</div>
  }

  return (
    <Button
      onPress={() => onSelect(cell)}
      aria-label={label}
      className={clsx(
        className,
        'cursor-pointer outline-none data-[focus-visible]:ring-2 data-[focus-visible]:ring-purple-700',
      )}
    >
      {children}
    </Button>
  )
}

function CleaningCell({
  cell,
  me,
  month,
  isToday,
  onSelect,
}: {
  cell: Cell
  me: User
  month: number
  isToday: boolean
  onSelect: (cell: Cell) => void
}) {
  const {date, mode, clickable, day} = cell
  const mustBeTrained =
    me.manager && date !== undefined && !date.user.manager && !date.user.hasBeenTrained
  const booked = mode === 'delete' || mode === 'unauthorized_delete' || mode === 'contact'

  let background = ''
  if (mode === 'delete' || mode === 'unauthorized_delete') {
    background = clickable ? 'bg-purple hover:bg-purple-600' : 'bg-purple-200'
  } else if (mode === 'contact') {
    if (mustBeTrained) {
      background = 'bg-red-500 hover:bg-red-600 md:bg-blue-400 md:hover:bg-blue-500'
    } else {
      background = clickable ? 'bg-blue-400 hover:bg-blue-500' : 'bg-blue-200'
    }
  } else if (Number(day.slice(5, 7)) !== month) {
    background = clsx('bg-gray-100', clickable && 'hover:bg-gray-300')
  } else if (clickable) {
    background = 'hover:bg-gray-300'
  }

  return (
    <td
      className={clsx(
        'relative h-20 p-0 transition duration-300',
        background,
        isToday ? 'border-2 border-b-0 border-[crimson]' : 'border border-gray-200',
      )}
    >
      <CellButton
        cell={cell}
        onSelect={onSelect}
        className="flex h-20 w-full flex-col overflow-hidden p-1 text-center"
      >
        <span className={clsx('h-5 w-full', booked ? 'text-white' : 'text-gray-500')}>
          {Number(day.slice(8, 10))}
        </span>
        <span className="mt-3 w-full grow text-xs text-white md:p-1 md:text-sm">
          {date && <UserName user={date.user} />}
        </span>
      </CellButton>
      {mustBeTrained && (
        <div
          className="absolute top-0 right-0 hidden h-6 w-6 bg-red-500 text-center font-bold text-white md:block"
          title="Ce bénévole n'a pas encore été formé"
        >
          !
        </div>
      )}
    </td>
  )
}

function SupervisingCell({
  cell,
  isToday,
  onSelect,
}: {
  cell: Cell
  isToday: boolean
  onSelect: (cell: Cell) => void
}) {
  return (
    <td
      className={clsx(
        'h-10 bg-stone-300 p-0',
        cell.clickable && 'hover:bg-gray-300',
        isToday ? 'border-2 border-t-0 border-[crimson]' : 'border border-gray-200',
      )}
    >
      <CellButton
        cell={cell}
        onSelect={onSelect}
        className={clsx(
          'flex h-10 w-full items-center justify-center overflow-hidden p-1 text-xs lg:text-sm',
          cell.clickable ? 'text-black' : 'text-black-200',
        )}
      >
        {cell.date && <UserName user={cell.date.user} />}
      </CellButton>
    </td>
  )
}

/**
 * The modal displayed when clicking on a day, to book it, cancel it or contact the person who booked it.
 */
function DateModal({cell, onClose}: {cell: Cell; onClose: () => void}) {
  const fetcher = useFetcher<typeof action>()
  const isSubmitting = fetcher.state !== 'idle'
  const supervising = cell.dateContext === 'supervising'
  const date = formatDay(cell.day).toLowerCase()

  useEffect(() => {
    if (fetcher.state === 'idle' && fetcher.data?.ok) {
      onClose()
    }
  }, [fetcher.state, fetcher.data])

  let title: string
  let content: React.ReactNode
  let closeText = 'Fermer'
  let submitText: string | null = null

  switch (cell.mode) {
    case 'add':
      title = supervising ? 'Réserver la date en tant que responsable ?' : 'Réserver la date ?'
      content = `La date du ${date} est disponible, souhaitez-vous la réserver ?`
      closeText = 'Annuler'
      submitText = 'Confirmer'
      break
    case 'delete':
      title = supervising ? 'Annuler la date en tant que responsable ?' : 'Annuler la date ?'
      content = `La date du ${date} est réservée à votre nom, souhaitez-vous annuler votre participation ?`
      closeText = 'Non'
      submitText = 'Confirmer'
      break
    case 'unauthorized_delete':
      title = 'Annulation impossible'
      content = `La date du ${date} est réservée à votre nom. Vous ne pouvez pas annuler votre participation moins de 3 jours avant la date. Si besoin, n'hésitez pas à prendre contact avec le responsable de cette journée.`
      break
    default: {
      const user = cell.date?.user as DateUser
      const man = user.gender === 'm'
      const role = supervising ? 'responsable' : 'bénévole'
      title = `Contacter ${man ? 'le' : 'la'} ${role}`
      content = (
        <>
          <p>
            {man ? 'Le' : 'La'} {role} du {date} est{' '}
            <b>
              {user.firstname} {user.lastname}
            </b>
            .
          </p>
          <p>Vous pouvez {man ? 'le' : 'la'} contacter :</p>
          <ul className="list-disc">
            {user.phone && (
              <li>
                par téléphone au{' '}
                <b>
                  <a href={`tel:${user.phone}`} className="link">
                    {user.phone}
                  </a>
                </b>
              </li>
            )}
            <li>
              par mail à l'adresse{' '}
              <b>
                <a href={`mailto:${user.email}`} className="link">
                  {user.email}
                </a>
              </b>
            </li>
          </ul>
        </>
      )
    }
  }

  return (
    <Modal
      title={title}
      isOpen
      onOpenChange={(isOpen) => !isOpen && onClose()}
      footer={
        <>
          <Button
            onPress={onClose}
            isDisabled={isSubmitting}
            className={clsx(modalButtonClassName, 'bg-purple-600 hover:bg-purple-700')}
          >
            {closeText}
          </Button>
          {submitText && (
            <Button
              type="submit"
              form="date-form"
              isPending={isSubmitting}
              className={clsx(modalButtonClassName, 'bg-blue-600 hover:bg-blue-700')}
            >
              {submitText}
            </Button>
          )}
        </>
      }
    >
      {fetcher.data?.error && <Alert type="error">{fetcher.data.error}</Alert>}
      {typeof content === 'string' ? <p>{content}</p> : content}
      <fetcher.Form method="post" id="date-form">
        <input type="hidden" name="intent" value={cell.mode} />
        <input type="hidden" name="context" value={cell.dateContext} />
        <input type="hidden" name="day" value={cell.day} />
        <input type="hidden" name="id" value={cell.date?.id ?? ''} />
      </fetcher.Form>
    </Modal>
  )
}

function HelpModal({
  isOpen,
  onOpenChange,
  children,
}: {
  isOpen: boolean
  onOpenChange: (isOpen: boolean) => void
  children: React.ReactNode
}) {
  return (
    <Modal
      title="Comment ça marche ?"
      isOpen={isOpen}
      onOpenChange={onOpenChange}
      footer={
        <Button
          onPress={() => onOpenChange(false)}
          className={clsx(modalButtonClassName, 'bg-purple-600 hover:bg-purple-700')}
        >
          Fermer
        </Button>
      }
    >
      <h2>Combien de jours participer ?</h2>
      <p>Vous participez autant de jours que vous le souhaitez.</p>
      {children}
      <h2>Comment réserver/annuler une date ?</h2>
      <p>
        Pour réserver ou annuler une date, il suffit simplement de cliquer dessus sur le calendrier,
        puis de confirmer votre choix.
      </p>
      <h2>Quelques conditions pour modifier les dates</h2>
      <ul className="list-disc">
        <li>Vous ne pouvez pas annuler une journée de bénévolat moins de 3 jours avant</li>
        <li>Une fois la date passée, on ne peut plus modifier les informations</li>
      </ul>
      <h2>Comment contacter un autre bénévole ou responsable ?</h2>
      <p>
        Pour avoir les coordonnées (téléphone et adresse e-mail) d'une personne, cliquez simplement
        sur son nom sur le calendrier.
      </p>
    </Modal>
  )
}

function monthLink(year: number, month: number, offset: number) {
  const date = new Date(Date.UTC(year, month - 1 + offset, 1))

  return `/calendrier/${date.getUTCMonth() + 1}/${date.getUTCFullYear()}`
}

export default function Calendar({loaderData}: Route.ComponentProps) {
  const {
    today: now,
    year,
    month,
    days,
    me,
    cleaningDates,
    supervisingDates,
    participation,
    stats,
  } = loaderData
  const [selected, setSelected] = useState<Cell | null>(null)
  const [isHelpOpen, setHelpOpen] = useState(false)

  const weeks: string[][] = []
  for (let i = 0; i < days.length; i += 7) {
    weeks.push(days.slice(i, i + 7))
  }

  return (
    <>
      <h1>Planning des bénévoles</h1>

      <div className="md:flex md:flex-row md:space-x-10">
        <div className="md:basis-5/6">
          <span className="mr-2 mb-2 inline-block rounded bg-purple px-2.5 py-0.5 text-xs font-semibold text-white md:text-sm">
            En violet : vos jours de bénévolat
          </span>
          <span className="mr-2 mb-2 inline-block rounded bg-blue-400 px-2.5 py-0.5 text-xs font-semibold text-white md:text-sm">
            En bleu : les jours réservés par les autres bénévoles
          </span>
          <span className="mr-2 mb-2 inline-block rounded bg-stone-300 px-2.5 py-0.5 text-xs font-semibold text-stone-600 md:text-sm">
            En gris : le responsable à contacter en cas de problème
          </span>
        </div>
        <div className="mt-2 text-left md:mt-0 md:basis-1/6 md:text-right">
          <Button
            onPress={() => setHelpOpen(true)}
            className="cursor-pointer rounded-md bg-rose-400 px-3 py-2 text-xs font-semibold text-white transition duration-300 hover:bg-rose-500 md:text-sm"
          >
            Comment ça marche ?
          </Button>
        </div>
      </div>

      <div className="mt-5 w-full rounded bg-white shadow">
        <div className="flex justify-between border-b border-gray-200 px-2 pt-2 pb-1 md:px-4">
          <span className="text-xl font-bold">{formatMonth(year, month)}</span>
          <div className="flex gap-1">
            <Link to={monthLink(year, month, -1)} aria-label="Mois précédent" className="p-1">
              <svg width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm3.5 7.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z" />
              </svg>
            </Link>
            <Link to={monthLink(year, month, 1)} aria-label="Mois suivant" className="p-1">
              <svg width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0zM4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H4.5z" />
              </svg>
            </Link>
          </div>
        </div>
        <table className="w-full table-fixed border-collapse">
          <thead>
            <tr>
              {WEEK_DAYS.map((day) => (
                <th key={day} className="h-10 border border-gray-200 p-2 text-xs xl:text-sm">
                  <span className="hidden sm:block">{day}</span>
                  <span className="block sm:hidden">{day.slice(0, 2)}</span>
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {weeks.map((week) => (
              <WeekRows
                key={week[0]}
                week={week}
                now={now}
                me={me}
                month={month}
                cleaningDates={cleaningDates}
                supervisingDates={supervisingDates}
                onSelect={setSelected}
              />
            ))}
          </tbody>
        </table>
      </div>

      {selected && (
        <DateModal
          key={`${selected.dateContext}-${selected.day}`}
          cell={selected}
          onClose={() => setSelected(null)}
        />
      )}
      <HelpModal isOpen={isHelpOpen} onOpenChange={setHelpOpen}>
        <Participation
          participation={participation}
          stats={stats}
          manager={me.manager}
          year={Number(now.slice(0, 4))}
          withHeadings={false}
        />
      </HelpModal>
    </>
  )
}

function WeekRows({
  week,
  now,
  me,
  month,
  cleaningDates,
  supervisingDates,
  onSelect,
}: {
  week: string[]
  now: string
  me: User
  month: number
  cleaningDates: Record<string, CalendarDate>
  supervisingDates: Record<string, CalendarDate>
  onSelect: (cell: Cell) => void
}) {
  return (
    <>
      <tr>
        {week.map((day) => (
          <CleaningCell
            key={day}
            cell={getCell('cleaning', day, cleaningDates[day], me, now)}
            me={me}
            month={month}
            isToday={day === now}
            onSelect={onSelect}
          />
        ))}
      </tr>
      <tr>
        {week.map((day) => (
          <SupervisingCell
            key={day}
            cell={getCell('supervising', day, supervisingDates[day], me, now)}
            isToday={day === now}
            onSelect={onSelect}
          />
        ))}
      </tr>
    </>
  )
}
