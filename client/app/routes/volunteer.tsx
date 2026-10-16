import type {ReactNode} from 'react'
import {Link} from 'react-router'
import {createApi} from '~/api.server'
import type {Stats} from '~/types'
import type {Route} from './+types/volunteer'

export const meta = () => [{title: "Devenir bénévole - Frig'o dons"}]

export async function loader({context, request}: Route.LoaderArgs) {
  return createApi(context, request, null).get<Stats>('/stats')
}

function Column({icon, title, children}: {icon: string; title: ReactNode; children: ReactNode}) {
  return (
    <div className="mt-12 first:mt-4 md:mt-0 md:basis-1/3">
      <img src={`/img/icons/${icon}.svg`} className="m-auto mb-6 w-36 md:mt-2 lg:w-48" alt="" />
      <h3>{title}</h3>
      {children}
    </div>
  )
}

const contactLink = (
  <b>
    <Link to="/contact" className="link">
      formulaire de contact
    </Link>
  </b>
)

export default function Volunteer({loaderData}: Route.ComponentProps) {
  return (
    <>
      <h1>Devenir bénévole</h1>

      <h2>Donner un coup de main pour le Frig'o dons</h2>
      <div className="flex-row md:flex md:space-x-16">
        <Column
          icon="cleaning"
          title="Nous recherchons des bénévoles pour participer à l'aventure du Frig'o dons"
        >
          <p>
            Pour assurer le bon fonctionnement du Frig'o dons, un passage quotidien de 5mn est
            nécessaire afin de garder l'espace propre et accueillant.
          </p>
          <p>
            Nous sommes déjà <b>{loaderData.volunteerCount} bénévoles</b>.
          </p>
          <p>
            Nous espérons être bientôt 60 bénévoles pour que chacun n'ait que 6 passages à faire
            dans l'année, soit seulement 1h de bénévolat !
          </p>
        </Column>
        <Column icon="calendar" title="Vous choisissez vous-mêmes vos jours de passage">
          <p>
            Sur notre calendrier partagé en ligne, vous sélectionnez les jours qui vous intéressent
            en fonction de vos disponibilités.
          </p>
          <p>
            Nous vous expliquons sur place comment fonctionne l'espace Frig'o dons, ce que vous
            devez faire et vous remettons le Guide du bénévole.
          </p>
        </Column>
        <Column icon="contact" title="Convaincu ? Alors contactez-nous !">
          <p>
            Écrivez-nous via le {contactLink} en précisant votre nom, prénom et numéro de téléphone.
          </p>
          <p>Nous vous créerons un compte pour accéder au planning des bénévoles.</p>
        </Column>
      </div>

      <h2>Participer à l'organisation de La Cordée et de ses actions solidaires</h2>
      <div className="flex-row md:flex md:space-x-16">
        <Column
          icon="team"
          title={
            <>
              Nous sommes <b>La Cordée</b>, un collectif d’habitants
            </>
          }
        >
          <p>
            Nous mettons en place de nouvelles initiatives solidaires sur la commune de La Montagne.
          </p>
        </Column>
        <Column icon="meeting" title="Nous nous réunissons 1 fois par mois">
          <p>
            Pour organiser des projets comme le Frig'o dons, les boîtes de Noël et d'autres actions
            à venir...
          </p>
        </Column>
        <Column
          icon="party"
          title="Et parfois nous nous réunissons juste pour des moments conviviaux !"
        >
          <p>
            Pour le Frig’o dons ou d’autres projets, rejoignez-nous en nous écrivant via le{' '}
            {contactLink}.
          </p>
        </Column>
      </div>
    </>
  )
}
