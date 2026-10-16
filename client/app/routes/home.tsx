import type {ReactNode} from 'react'
import {Link} from 'react-router'

export const meta = () => [
  {title: "Frig'o dons"},
  {
    name: 'description',
    content:
      "Le Frig'o dons est un frigo en libre service à La Montagne. Il a besoin de nouveaux bénévoles : 5 minutes par passage, c'est tout !",
  },
]

const buttonClassName =
  'inline-flex min-h-12 items-center justify-center rounded-2xl px-7 font-bold transition-colors'

function Check({children}: {children: ReactNode}) {
  return (
    <li className="flex items-center gap-3 text-lg font-bold">
      <svg className="size-9 shrink-0" viewBox="0 0 34 34" fill="none" aria-hidden="true">
        <circle cx="17" cy="17" r="16" className="fill-purple" />
        <path
          d="M10 17.5l4.5 4.5L24 12.5"
          className="stroke-black"
          strokeWidth="3"
          strokeLinecap="round"
          strokeLinejoin="round"
        />
      </svg>
      {children}
    </li>
  )
}

export default function Home() {
  return (
    <div className="overflow-hidden bg-cream">
      <section className="container mx-auto grid items-center gap-12 px-5 pt-10 pb-16 lg:grid-cols-[3fr_2fr] lg:gap-16 lg:pt-20 lg:pb-24">
        <div className="text-center lg:text-left">
          <p className="font-bold">
            À La Montagne · Place Similien Guérin, à côté du cinéma Le Montagnard
          </p>
          <h1 className="mt-5 p-0 font-chelsea text-4xl leading-tight font-normal text-balance sm:text-5xl lg:text-6xl">
            Le Frig'o dons a besoin de vous !
          </h1>
          <p className="mt-5 text-lg text-balance">
            Le Frig'o dons, c'est un frigo en libre service, ouvert à tous, pour donner et prendre
            des produits frais et secs.
          </p>
          <p className="mt-2 text-xl font-bold text-balance">
            Le frigo a besoin de nouveaux bénévoles pour continuer l'aventure.
          </p>
        </div>

        <div className="flex items-end justify-center">
          <img
            src="/img/frigo.png"
            alt="Le frigo, mascotte du Frig'o dons"
            width="712"
            height="1128"
            className="w-36 -rotate-4 sm:w-48 lg:w-56"
          />
          <div className="relative -ml-4 mb-6 rotate-3 bg-yellow px-5 pt-7 pb-5 text-center font-chelsea shadow-lg sm:px-8">
            <div className="absolute -top-3 left-1/2 h-6 w-20 -translate-x-1/2 -rotate-5 bg-cream/75" />
            <p className="text-5xl leading-none whitespace-nowrap sm:text-6xl xl:text-7xl">5 min</p>
            <p className="mt-1 sm:text-lg xl:text-xl">
              par passage,
              <br />
              c'est tout !
            </p>
          </div>
        </div>
      </section>

      <section className="container mx-auto px-5 pb-16 lg:pb-24">
        <ul className="mx-auto grid max-w-5xl gap-5 sm:grid-cols-3 sm:gap-8">
          <Check>Choisissez vos jours de passage</Check>
          <Check>Venez à l'heure qui vous arrange</Check>
          <Check>Aucun engagement minimal attendu</Check>
        </ul>

        <div className="mx-auto mt-14 max-w-2xl text-center">
          <h2 className="m-0 p-0 font-chelsea text-2xl md:text-3xl">Le rôle des bénévoles</h2>
          <p className="mt-3 text-lg text-pretty">
            Un petit passage au frigo pour vérifier les dates, retirer ce qui est périmé et donner
            un coup d'éponge, pour que l'espace reste propre et accueillant pour tous.
          </p>
        </div>
      </section>

      <section>
        <svg
          className="block h-4 w-full"
          viewBox="0 0 794 18"
          preserveAspectRatio="none"
          aria-hidden="true"
        >
          <path
            d="M0 12 L40 9 L78 13 L121 8 L160 11 L205 7 L248 12 L290 9 L333 13 L377 8 L420 11 L462 6 L505 12 L548 9 L590 13 L634 8 L676 11 L719 7 L760 12 L794 9 L794 18 L0 18 Z"
            className="fill-purple"
          />
        </svg>
        <div className="bg-purple px-5 pt-10 pb-12 text-center">
          <h2 className="m-0 p-0 font-chelsea text-3xl text-balance md:text-4xl">
            Envie de rejoindre l'aventure ?
          </h2>
          <p className="mt-3 text-lg">
            Contactez-nous, nous vous créerons un compte pour vous inscrire sur le planning en
            ligne.
          </p>
          <div className="mt-7 flex flex-wrap justify-center gap-4">
            <Link
              to="/contact"
              className={`${buttonClassName} bg-cream text-black hover:bg-black hover:text-cream`}
            >
              Nous contacter
            </Link>
            <Link
              to="/connexion"
              className={`${buttonClassName} bg-purple-700 text-white hover:bg-black`}
            >
              Déjà bénévole ? Se connecter
            </Link>
          </div>
          <p className="mt-4 text-sm italic">Un projet du groupe habitants La Cordée</p>
        </div>
      </section>
    </div>
  )
}
