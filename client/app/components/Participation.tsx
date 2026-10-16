import {daysInYear} from '~/dates'
import type {Participation as ParticipationData, Stats} from '~/types'

function plural(count: number, singular: string, pluralForm: string, none: string) {
  if (count === 0) {
    return none
  }

  return count === 1 ? `1 ${singular}` : `${count} ${pluralForm}`
}

function Badge({color, children}: {color: 'teal' | 'rose'; children: string}) {
  return (
    <p>
      <span
        className={`mt-2 mr-2 inline-block rounded px-2.5 py-0.5 text-white ${color === 'teal' ? 'bg-teal-500' : 'bg-rose-500'}`}
      >
        {children}
      </span>
    </p>
  )
}

type Props = {
  participation: ParticipationData
  stats: Stats
  manager: boolean
  year: number
  /**
   * Headings separate the volunteer and manager parts, they are hidden in modals.
   */
  withHeadings?: boolean
}

/**
 * The number of dates done and planned by the user this year, compared to the ideal.
 */
export function Participation({participation, stats, manager, year, withHeadings = true}: Props) {
  const days = daysInYear(year)

  return (
    <>
      {manager && withHeadings && <h3>En tant que bénévole</h3>}
      <p>
        Nous sommes {stats.volunteerCount} bénévoles cette année. Pour que le Frig'o dons soit
        nettoyé chaque jour, l'idéal serait que chacun réalise{' '}
        <b>{Math.round(days / stats.volunteerCount)} jours de nettoyage</b> dans l'année.
      </p>
      <p>Voici les chiffres que nous avons enregistrés pour vous cette année :</p>
      <Badge color="teal">
        {plural(
          participation.yearDoneCleaningCount,
          'jour de nettoyage réalisé',
          'jours de nettoyage réalisés',
          'Aucun jour de nettoyage réalisé',
        )}
      </Badge>
      <Badge color="rose">
        {plural(
          participation.yearScheduledCleaningCount,
          'jour de nettoyage à venir',
          'jours de nettoyage à venir',
          'Aucun jour de nettoyage à venir',
        )}
      </Badge>
      <p className="mt-2">Toute l'équipe de La Cordée tient à vous remercier pour votre aide !</p>

      {manager && (
        <>
          {withHeadings && <h3>En tant que responsable</h3>}
          <p>
            Nous sommes {stats.managerCount} responsables cette année. Il faudrait que chacun
            programme <b>{Math.round(days / stats.managerCount)} jours en tant que responsable</b>{' '}
            dans l'année.
          </p>
          <Badge color="teal">
            {plural(
              participation.yearDoneSupervisingCount,
              'jour de responsable réalisé',
              'jours de responsable réalisés',
              'Aucun jour de responsable réalisé',
            )}
          </Badge>
          <Badge color="rose">
            {plural(
              participation.yearScheduledSupervisingCount,
              'jour de responsable à venir',
              'jours de responsable à venir',
              'Aucun jour de responsable à venir',
            )}
          </Badge>
        </>
      )}
    </>
  )
}
