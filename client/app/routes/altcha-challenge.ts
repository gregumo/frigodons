import type {Route} from './+types/altcha-challenge'

/**
 * Relays the ALTCHA challenge of the API, so the browser never calls the API directly.
 */
export async function loader({context}: Route.LoaderArgs) {
  const response = await fetch(`${context.cloudflare.env.API_URL}/altcha-challenge`)

  return new Response(response.body, {
    status: response.status,
    headers: {'Content-Type': 'application/json', 'Cache-Control': 'no-store'},
  })
}
