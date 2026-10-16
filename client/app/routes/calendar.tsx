import clsx from 'clsx'
import {useEffect, useState} from 'react'
import {Button, ToggleButton} from 'react-aria-components'
import {data, Link, useFetcher} from 'react-router'
import {ApiError, requireApi} from '~/api.server'
import {Modal, modalButtonClassName, modalSecondaryButtonClassName} from '~/components/Modal'
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

/**
 * A day selected on the calendar, to be booked (`add`) or cancelled (`delete`) once validated.
 */
type Selected = {dateContext: DateContext; day: string; intent: 'add' | 'delete'; id?: number}

type Failure = {dateContext: DateContext; day: string; message: string}

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

/**
 * Books and cancels all the selected days. Each day is handled on its own, so a day booked by someone
 * else in the meantime does not prevent the other ones from being saved.
 */
export async function action({context, request}: Route.ActionArgs) {
  const {api} = await requireApi(context, request)
  const formData = await request.formData()
  const selection = JSON.parse(String(formData.get('selection') ?? '[]')) as Selected[]

  let booked = 0
  let cancelled = 0
  const failures: Failure[] = []

  // Cancellations first, so a day can be cancelled as a volunteer and booked as a manager at once.
  const sorted = [...selection].sort(
    (a, b) => Number(a.intent === 'add') - Number(b.intent === 'add'),
  )
  for (const {dateContext, day, intent, id} of sorted) {
    const {path} = RESOURCES[dateContext === 'supervising' ? 'supervising' : 'cleaning']
    try {
      if (intent === 'delete') {
        await api.delete(`${path}/${Number(id)}`)
        cancelled++
      } else {
        await api.post(path, {day: String(day)})
        booked++
      }
    } catch (error) {
      if (!(error instanceof ApiError)) {
        throw error
      }
      failures.push({dateContext, day, message: error.violations[0]?.message ?? error.message})
    }
  }

  return {booked, cancelled, failures}
}

/**
 * What happens when clicking on a day:
 * - add: the day is free and can be selected to be booked;
 * - delete: the day is booked by the user, who can select it to cancel it;
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

const isSelectable = (cell: Cell) =>
  cell.clickable && (cell.mode === 'add' || cell.mode === 'delete')

const selectionKey = (dateContext: DateContext, day: string) => `${dateContext}:${day}`

function dayLabel({dateContext, day}: {dateContext: DateContext; day: string}) {
  return `${formatDay(day)}${dateContext === 'supervising' ? ' (responsable)' : ''}`
}

function plural(count: number, singular: string, pluralForm: string) {
  return `${count} ${count > 1 ? pluralForm : singular}`
}

function UserName({user}: {user: DateUser}) {
  return (
    <>
      <span className="hidden md:inline">
        {user.firstname} {user.lastname}
      </span>
      <span className="md:hidden">
        {user.firstname} {user.lastname.charAt(0)}.
      </span>
    </>
  )
}

const focusClassName =
  'cursor-pointer data-[focus-visible]:ring-2 data-[focus-visible]:ring-purple-700'

/**
 * Selected days have a dashed outline, the others none (the focus is shown with a ring).
 */
function outlineClassName(selected: Selected | undefined) {
  return selected ? 'outline-2 -outline-offset-4 outline-black outline-dashed' : 'outline-none'
}

/**
 * Displayed instead of the name on a day selected to be booked: a check on small screens.
 */
function ToBook() {
  return (
    <>
      <b className="hidden md:inline">À réserver</b>
      <svg className="m-auto size-5 md:hidden" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path
          d="M5 12.5l4.5 4.5L19 7.5"
          stroke="currentColor"
          strokeWidth="3"
          strokeLinecap="round"
          strokeLinejoin="round"
        />
      </svg>
    </>
  )
}

function CellButton({
  cell,
  selected,
  onSelect,
  className,
  children,
}: {
  cell: Cell
  selected: Selected | undefined
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

  if (isSelectable(cell)) {
    return (
      <ToggleButton
        isSelected={selected !== undefined}
        onChange={() => onSelect(cell)}
        aria-label={`${label}, ${cell.mode === 'add' ? 'à réserver' : 'à annuler'}`}
        className={clsx(className, focusClassName, outlineClassName(selected))}
      >
        {children}
      </ToggleButton>
    )
  }

  return (
    <Button
      onPress={() => onSelect(cell)}
      aria-label={label}
      className={clsx(className, focusClassName, outlineClassName(undefined))}
    >
      {children}
    </Button>
  )
}

function CleaningCell({
  cell,
  selected,
  me,
  month,
  isToday,
  onSelect,
}: {
  cell: Cell
  selected: Selected | undefined
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
  if (selected?.intent === 'add') {
    background = 'bg-yellow'
  } else if (selected?.intent === 'delete') {
    background = 'bg-purple-200'
  } else if (mode === 'delete' || mode === 'unauthorized_delete') {
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
        selected={selected}
        onSelect={onSelect}
        className="flex h-20 w-full flex-col overflow-hidden p-1 text-center"
      >
        <span className={clsx('h-5 w-full', booked && !selected ? 'text-white' : 'text-gray-500')}>
          {Number(day.slice(8, 10))}
        </span>
        <span
          className={clsx(
            'mt-3 w-full grow text-xs md:p-1 md:text-sm',
            selected ? 'text-black' : 'text-white',
            selected?.intent === 'delete' && 'line-through',
          )}
        >
          {selected?.intent === 'add' ? <ToBook /> : date && <UserName user={date.user} />}
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
  selected,
  isToday,
  onSelect,
}: {
  cell: Cell
  selected: Selected | undefined
  isToday: boolean
  onSelect: (cell: Cell) => void
}) {
  return (
    <td
      className={clsx(
        'h-10 p-0',
        selected?.intent === 'add' ? 'bg-yellow' : 'bg-stone-300',
        cell.clickable && !selected && 'hover:bg-gray-300',
        isToday ? 'border-2 border-t-0 border-[crimson]' : 'border border-gray-200',
      )}
    >
      <CellButton
        cell={cell}
        selected={selected}
        onSelect={onSelect}
        className={clsx(
          'flex h-10 w-full items-center justify-center overflow-hidden p-1 text-xs lg:text-sm',
          cell.clickable ? 'text-black' : 'text-black-200',
          selected?.intent === 'delete' && 'line-through',
        )}
      >
        {selected?.intent === 'add' ? <ToBook /> : cell.date && <UserName user={cell.date.user} />}
      </CellButton>
    </td>
  )
}

/**
 * The modal displayed when clicking on a day that cannot be selected: to contact the person who
 * booked it, or to explain why it cannot be cancelled.
 */
function DetailsModal({cell, onClose}: {cell: Cell; onClose: () => void}) {
  const supervising = cell.dateContext === 'supervising'
  const date = formatDay(cell.day).toLowerCase()
  const user = cell.date?.user as DateUser

  let title: string
  let content: React.ReactNode

  if (cell.mode === 'unauthorized_delete') {
    title = 'Annulation impossible'
    content = (
      <p>
        La date du {date} est réservée à votre nom. Vous ne pouvez pas annuler votre participation
        moins de 3 jours avant la date. Si besoin, n'hésitez pas à prendre contact avec le
        responsable de cette journée.
      </p>
    )
  } else {
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

  return (
    <Modal
      title={title}
      isOpen
      onOpenChange={(isOpen) => !isOpen && onClose()}
      footer={
        <Button onPress={onClose} className={modalButtonClassName}>
          Fermer
        </Button>
      }
    >
      {content}
    </Modal>
  )
}

function DayList({title, days}: {title: string; days: {dateContext: DateContext; day: string}[]}) {
  if (days.length === 0) {
    return null
  }

  return (
    <>
      <p className="mt-3 font-bold first:mt-0">{title}</p>
      <ul className="list-disc">
        {days.map((day) => (
          <li key={selectionKey(day.dateContext, day.day)}>{dayLabel(day)}</li>
        ))}
      </ul>
    </>
  )
}

/**
 * Confirms the selected days, then displays what could not be saved, if anything.
 */
function ConfirmModal({
  selection,
  onCancel,
  onDone,
}: {
  selection: Selected[]
  onCancel: () => void
  onDone: () => void
}) {
  const fetcher = useFetcher<typeof action>()
  const isSubmitting = fetcher.state !== 'idle'
  const result = fetcher.state === 'idle' ? fetcher.data : undefined

  useEffect(() => {
    if (result && result.failures.length === 0) {
      onDone()
    }
  }, [result])

  if (result) {
    const saved = [
      result.booked > 0 && plural(result.booked, 'date réservée', 'dates réservées'),
      result.cancelled > 0 && plural(result.cancelled, 'date annulée', 'dates annulées'),
    ].filter(Boolean)

    return (
      <Modal
        title="Certaines dates n'ont pas pu être enregistrées"
        isOpen
        onOpenChange={(isOpen) => !isOpen && onDone()}
        footer={
          <Button onPress={onDone} className={modalButtonClassName}>
            Fermer
          </Button>
        }
      >
        {saved.length > 0 && <p className="mb-3">{saved.join(' et ')}.</p>}
        <ul className="list-disc">
          {result.failures.map((failure) => (
            <li key={selectionKey(failure.dateContext, failure.day)}>
              {dayLabel(failure)} : {failure.message}
            </li>
          ))}
        </ul>
      </Modal>
    )
  }

  const sorted = [...selection].sort((a, b) => a.day.localeCompare(b.day))

  return (
    <Modal
      title="Valider vos choix ?"
      isOpen
      onOpenChange={(isOpen) => !isOpen && !isSubmitting && onCancel()}
      footer={
        <>
          <Button
            onPress={onCancel}
            isDisabled={isSubmitting}
            className={modalSecondaryButtonClassName}
          >
            Retour
          </Button>
          <Button
            type="submit"
            form="selection-form"
            isPending={isSubmitting}
            className={modalButtonClassName}
          >
            Confirmer
          </Button>
        </>
      }
    >
      <DayList title="Vous allez réserver :" days={sorted.filter((s) => s.intent === 'add')} />
      <DayList title="Vous allez annuler :" days={sorted.filter((s) => s.intent === 'delete')} />
      <fetcher.Form method="post" id="selection-form">
        <input type="hidden" name="selection" value={JSON.stringify(selection)} />
      </fetcher.Form>
    </Modal>
  )
}

/**
 * Stays at the bottom of the screen while days are selected.
 */
function SelectionBar({
  selection,
  onClear,
  onValidate,
}: {
  selection: Selected[]
  onClear: () => void
  onValidate: () => void
}) {
  const toBook = selection.filter((s) => s.intent === 'add').length
  const toCancel = selection.length - toBook
  const summary = [
    toBook > 0 && plural(toBook, 'date à réserver', 'dates à réserver'),
    toCancel > 0 && `${toCancel} à annuler`,
  ]
    .filter(Boolean)
    .join(' · ')

  return (
    <section
      aria-label="Dates sélectionnées"
      className="fixed inset-x-0 bottom-0 z-40 border-t-2 border-black bg-cream shadow-lg"
    >
      <div className="container mx-auto flex flex-wrap items-center justify-between gap-3 px-5 py-3">
        <p className="font-bold" aria-live="polite">
          {summary}
        </p>
        <div className="flex flex-wrap gap-3">
          <Button
            onPress={onClear}
            className="min-h-11 cursor-pointer rounded-2xl border-2 border-black px-5 text-sm font-bold transition-colors hover:bg-black hover:text-cream"
          >
            Tout désélectionner
          </Button>
          <Button
            onPress={onValidate}
            className="min-h-11 cursor-pointer rounded-2xl bg-purple-600 px-6 text-sm font-bold text-white transition-colors hover:bg-purple-700"
          >
            Valider
          </Button>
        </div>
      </div>
    </section>
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
        <Button onPress={() => onOpenChange(false)} className={modalButtonClassName}>
          Fermer
        </Button>
      }
    >
      <h2>Combien de jours participer ?</h2>
      <p>Vous participez autant de jours que vous le souhaitez.</p>
      {children}
      <h2>Comment réserver/annuler des dates ?</h2>
      <p>
        Cliquez sur les jours libres que vous souhaitez réserver, ou sur vos dates pour les annuler.
        Vous pouvez en sélectionner plusieurs, y compris sur plusieurs mois. Cliquez ensuite sur «
        Valider » en bas de l'écran, puis confirmez votre choix.
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
  // Kept when changing month (the route stays mounted), so days of several months can be booked at once.
  const [selection, setSelection] = useState<Record<string, Selected>>({})
  const [details, setDetails] = useState<Cell | null>(null)
  const [isConfirmOpen, setConfirmOpen] = useState(false)
  const [isHelpOpen, setHelpOpen] = useState(false)
  const selected = Object.values(selection)

  const onSelect = (cell: Cell) => {
    if (!isSelectable(cell)) {
      setDetails(cell)
      return
    }

    const key = selectionKey(cell.dateContext, cell.day)
    setSelection(({[key]: current, ...others}) =>
      current
        ? others
        : {
            ...others,
            [key]: {
              dateContext: cell.dateContext,
              day: cell.day,
              intent: cell.mode === 'add' ? 'add' : 'delete',
              id: cell.date?.id,
            },
          },
    )
  }

  const weeks: string[][] = []
  for (let i = 0; i < days.length; i += 7) {
    weeks.push(days.slice(i, i + 7))
  }

  return (
    <div className={clsx(selected.length > 0 && 'pb-28 md:pb-20')}>
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
                selection={selection}
                onSelect={onSelect}
              />
            ))}
          </tbody>
        </table>
      </div>

      {selected.length > 0 && (
        <SelectionBar
          selection={selected}
          onClear={() => setSelection({})}
          onValidate={() => setConfirmOpen(true)}
        />
      )}
      {isConfirmOpen && (
        <ConfirmModal
          selection={selected}
          onCancel={() => setConfirmOpen(false)}
          onDone={() => {
            setConfirmOpen(false)
            setSelection({})
          }}
        />
      )}
      {details && (
        <DetailsModal
          key={`${details.dateContext}-${details.day}`}
          cell={details}
          onClose={() => setDetails(null)}
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
    </div>
  )
}

function WeekRows({
  week,
  now,
  me,
  month,
  cleaningDates,
  supervisingDates,
  selection,
  onSelect,
}: {
  week: string[]
  now: string
  me: User
  month: number
  cleaningDates: Record<string, CalendarDate>
  supervisingDates: Record<string, CalendarDate>
  selection: Record<string, Selected>
  onSelect: (cell: Cell) => void
}) {
  return (
    <>
      <tr>
        {week.map((day) => (
          <CleaningCell
            key={day}
            cell={getCell('cleaning', day, cleaningDates[day], me, now)}
            selected={selection[selectionKey('cleaning', day)]}
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
            selected={selection[selectionKey('supervising', day)]}
            isToday={day === now}
            onSelect={onSelect}
          />
        ))}
      </tr>
    </>
  )
}
