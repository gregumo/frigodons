import {type AppLoadContext, createCookieSessionStorage, redirect} from 'react-router'

/**
 * What the layout needs to know about the logged in user, without calling the API.
 */
export type SessionUser = {id: number; firstname: string; manager: boolean}

type SessionData = {token: string; user: SessionUser}

// Same lifetime as the JWT delivered by the API.
const SESSION_MAX_AGE = 60 * 60 * 24 * 30

function getStorage(context: AppLoadContext, request: Request) {
  return createCookieSessionStorage<SessionData>({
    cookie: {
      name: '__session',
      httpOnly: true,
      path: '/',
      sameSite: 'lax',
      secure: new URL(request.url).protocol === 'https:',
      secrets: [context.cloudflare.env.SESSION_SECRET],
      maxAge: SESSION_MAX_AGE,
    },
  })
}

export async function getUserSession(context: AppLoadContext, request: Request) {
  const session = await getStorage(context, request).getSession(request.headers.get('Cookie'))

  return {token: session.get('token') ?? null, user: session.get('user') ?? null}
}

/**
 * Returns the `Set-Cookie` header value storing the token and the user.
 */
export async function commitUserSession(
  context: AppLoadContext,
  request: Request,
  token: string,
  user: SessionUser,
) {
  const storage = getStorage(context, request)
  const session = await storage.getSession(request.headers.get('Cookie'))
  session.set('token', token)
  session.set('user', user)

  return storage.commitSession(session)
}

/**
 * Returns the `Set-Cookie` header value removing the session.
 */
export async function destroyUserSession(context: AppLoadContext, request: Request) {
  const storage = getStorage(context, request)

  return storage.destroySession(await storage.getSession(request.headers.get('Cookie')))
}

/**
 * Redirects to the login page, which redirects back to the current page once logged in.
 */
export async function redirectToLogin(context: AppLoadContext, request: Request): Promise<never> {
  const url = new URL(request.url)
  const params = new URLSearchParams({redirectTo: url.pathname + url.search})

  throw redirect(`/connexion?${params}`, {
    headers: {'Set-Cookie': await destroyUserSession(context, request)},
  })
}
