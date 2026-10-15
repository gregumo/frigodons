import {useState} from 'react'
import {data, useNavigation} from 'react-router'
import {ApiError, requireApi} from '~/api.server'
import {Alert, Field, Form, formatPhone, SubmitButton, Switch} from '~/components/Form'
import {Participation} from '~/components/Participation'
import {formatDay, today} from '~/dates'
import {commitUserSession, getUserSession} from '~/session.server'
import type {Participation as ParticipationData, Stats, User} from '~/types'
import type {Route} from './+types/profile'

export const meta = () => [{title: "Mes informations - Frig'o dons"}]

export async function loader({context, request}: Route.LoaderArgs) {
  const {api, user} = await requireApi(context, request)
  const now = today()
  const upcoming = (path: string, userField: string) =>
    api.get<{day: string}[]>(`${path}?${userField}=/users/${user.id}&day[after]=${now}`)

  const [me, participation, stats, cleaningDates, supervisingDates] = await Promise.all([
    api.get<User>('/me'),
    api.get<ParticipationData>('/participation'),
    api.get<Stats>('/stats'),
    upcoming('/cleaning-dates', 'cleaner'),
    user.manager ? upcoming('/supervising-dates', 'supervisor') : Promise.resolve([]),
  ])

  return {
    me,
    participation,
    stats,
    year: Number(now.slice(0, 4)),
    cleaningDays: cleaningDates.map((date) => date.day),
    supervisingDays: supervisingDates.map((date) => date.day),
  }
}

export async function action({context, request}: Route.ActionArgs) {
  const {api, user} = await requireApi(context, request)
  const formData = await request.formData()
  const value = (name: string) => String(formData.get(name) ?? '').trim()

  let updated: User
  try {
    updated = await api.patch<User>(`/users/${user.id}`, {
      firstname: value('firstname'),
      lastname: value('lastname'),
      email: value('email'),
      phone: value('phone'),
      callbackMailOptIn: formData.has('callbackMailOptIn'),
      missingVolunteerMailOptIn: formData.has('missingVolunteerMailOptIn'),
    })
  } catch (error) {
    if (error instanceof ApiError && error.status === 422) {
      return {success: false, fieldErrors: error.fieldErrors}
    }
    throw error
  }

  // The firstname is displayed in the layout.
  const {token} = await getUserSession(context, request)
  return data(
    {success: true, fieldErrors: {}},
    {
      headers: {
        'Set-Cookie': await commitUserSession(context, request, token as string, {
          id: updated.id,
          firstname: updated.firstname,
          manager: updated.manager,
        }),
      },
    },
  )
}

function DayList({days}: {days: string[]}) {
  if (days.length === 0) {
    return <p>Vous n'avez aucune date à venir.</p>
  }

  return (
    <ul className="list-disc">
      {days.map((day) => (
        <li key={day}>{formatDay(day)}</li>
      ))}
    </ul>
  )
}

export default function Profile({loaderData, actionData}: Route.ComponentProps) {
  const {me, participation, stats, year, cleaningDays, supervisingDays} = loaderData
  const isSubmitting = useNavigation().state === 'submitting'
  const [phone, setPhone] = useState(me.phone ?? '')

  return (
    <>
      <h1>Mes informations</h1>
      <div className="flex-row lg:flex lg:space-x-16">
        <div className="lg:basis-1/3">
          <h2>Modifier mes informations</h2>
          {actionData?.success && (
            <Alert type="success">Vos informations ont bien été mises à jour.</Alert>
          )}
          <Form method="post" validationErrors={actionData?.fieldErrors}>
            <Field
              label="Prénom"
              name="firstname"
              autoComplete="given-name"
              defaultValue={me.firstname}
              isRequired
            />
            <Field
              label="Nom"
              name="lastname"
              autoComplete="family-name"
              defaultValue={me.lastname}
              isRequired
            />
            <Field
              label="Adresse e-mail"
              name="email"
              type="email"
              autoComplete="email"
              defaultValue={me.email}
              isRequired
            />
            <Field
              label="Téléphone"
              name="phone"
              type="tel"
              autoComplete="tel"
              value={phone}
              onChange={(value) => setPhone(formatPhone(value))}
              isRequired
            />
            <Switch
              name="callbackMailOptIn"
              label="Recevoir les mails de rappel de vos dates"
              help="Tous les dimanches, vous recevrez un mail pour vous rappeler vos dates de nettoyage pour la semaine à venir. Si vous n'avez programmé aucune date cette semaine là, vous ne recevrez aucun mail."
              defaultSelected={me.callbackMailOptIn}
            />
            <Switch
              name="missingVolunteerMailOptIn"
              label="Recevoir les mails quand il manque des bénévoles"
              help="Tous les 15 jours, nous vous enverrons un mail s'il manque des bénévoles pour les 15 jours à venir. Ce mail contiendra la liste des créneaux disponibles."
              defaultSelected={me.missingVolunteerMailOptIn}
            />
            <SubmitButton isPending={isSubmitting}>Enregistrer</SubmitButton>
          </Form>
        </div>
        <div className="mt-10 lg:mt-0 lg:basis-1/3">
          <h2>Votre participation en {year}</h2>
          <Participation
            participation={participation}
            stats={stats}
            manager={me.manager}
            year={year}
          />
        </div>
        <div className="mt-10 lg:mt-0 lg:basis-1/3">
          <h2>Vos dates de bénévolat à venir</h2>
          {me.manager && <h3>En tant que bénévole</h3>}
          <DayList days={cleaningDays} />
          {me.manager && (
            <>
              <h3>En tant que responsable</h3>
              <DayList days={supervisingDays} />
            </>
          )}
        </div>
      </div>
    </>
  )
}
