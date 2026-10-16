import clsx from 'clsx'
import {
  isRouteErrorResponse,
  Link,
  Links,
  Meta,
  Outlet,
  Scripts,
  ScrollRestoration,
  useLocation,
  useRouteLoaderData,
} from 'react-router'
import type {Route} from './+types/root'
import './app.css'
import {Nav} from './components/Nav'
import {getUserSession} from './session.server'

export const links: Route.LinksFunction = () => [
  {rel: 'preconnect', href: 'https://fonts.googleapis.com'},
  {rel: 'preconnect', href: 'https://fonts.gstatic.com', crossOrigin: 'anonymous'},
  {
    rel: 'stylesheet',
    href: 'https://fonts.googleapis.com/css2?family=Chelsea+Market&family=Oswald:wght@200;300;400;500;700&display=swap',
  },
  {rel: 'icon', href: '/favicon.ico', sizes: '32x32'},
  {rel: 'apple-touch-icon', sizes: '180x180', href: '/img/favicon/apple-touch-icon.png'},
  {rel: 'manifest', href: '/img/favicon/site.webmanifest'},
]

export async function loader({context, request}: Route.LoaderArgs) {
  const {user} = await getUserSession(context, request)

  return {user}
}

export function Layout({children}: {children: React.ReactNode}) {
  const data = useRouteLoaderData<typeof loader>('root')
  // The home page has full width sections.
  const isHome = useLocation().pathname === '/'

  return (
    <html lang="fr">
      <head>
        <meta charSet="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
        <meta name="theme-color" content="#ffffff" />
        <Meta />
        <Links />
      </head>
      <body className="bg-beige text-black">
        <Nav user={data?.user ?? null} />
        <main className={clsx(!isHome && 'container mx-auto mt-5 mb-10 px-5')}>{children}</main>
        <ScrollRestoration />
        <Scripts />
      </body>
    </html>
  )
}

export default function App() {
  return <Outlet />
}

export function ErrorBoundary({error}: Route.ErrorBoundaryProps) {
  let title = 'Une erreur est survenue'
  let message = 'Merci de recharger la page. Si cette erreur persiste, contactez-nous.'

  if (isRouteErrorResponse(error)) {
    if (error.status === 404) {
      title = 'Page introuvable'
      message = "La page que vous cherchez n'existe pas."
    } else if (error.status === 403) {
      title = 'Accès refusé'
      message = "Vous n'avez pas accès à cette page."
    }
  } else if (import.meta.env.DEV && error instanceof Error) {
    message = error.message
  }

  return (
    <>
      <title>{`${title} - Frig'o dons`}</title>
      <h1>{title}</h1>
      <p>{message}</p>
      <p className="mt-4">
        <Link to="/" className="link">
          Retour à l'accueil
        </Link>{' '}
        ou{' '}
        <Link to="/contact" className="link">
          formulaire de contact
        </Link>
      </p>
    </>
  )
}
