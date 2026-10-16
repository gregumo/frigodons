import {type AppLoadContext, data} from 'react-router'
import {getUserSession, redirectToLogin, type SessionUser} from './session.server'

export type Violation = {propertyPath: string; message: string}

/**
 * An error returned by the API, with the validation errors if any.
 */
export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly violations: Violation[] = [],
  ) {
    super(message)
  }

  /**
   * The validation errors indexed by field, as expected by the `validationErrors` of forms.
   */
  get fieldErrors(): Record<string, string> {
    return Object.fromEntries(this.violations.map((v) => [v.propertyPath, v.message]))
  }
}

type Method = 'GET' | 'POST' | 'PATCH' | 'DELETE'

export function createApi(context: AppLoadContext, request: Request, token: string | null) {
  async function call<T>(method: Method, path: string, body?: unknown): Promise<T> {
    const headers = new Headers({Accept: 'application/json'})
    if (token) {
      headers.set('Authorization', `Bearer ${token}`)
    }
    if (body !== undefined) {
      headers.set(
        'Content-Type',
        method === 'PATCH' ? 'application/merge-patch+json' : 'application/json',
      )
    }

    const response = await fetch(context.cloudflare.env.API_URL + path, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
    })

    // The token has expired, or the user has been deleted.
    if (response.status === 401 && token) {
      return redirectToLogin(context, request)
    }

    if (!response.ok) {
      const error = (await response.json().catch(() => ({}))) as {
        detail?: string
        violations?: Violation[]
      }
      throw new ApiError(response.status, error.detail ?? response.statusText, error.violations)
    }

    if (response.status === 202 || response.status === 204) {
      return undefined as T
    }

    return (await response.json()) as T
  }

  return {
    /**
     * Errors of GET requests are rendered by the error boundaries.
     */
    get: async <T>(path: string): Promise<T> => {
      try {
        return await call<T>('GET', path)
      } catch (error) {
        if (error instanceof ApiError) {
          throw data(error.message, {status: error.status})
        }
        throw error
      }
    },
    post: <T>(path: string, body: unknown) => call<T>('POST', path, body),
    patch: <T>(path: string, body: unknown) => call<T>('PATCH', path, body),
    delete: (path: string) => call<void>('DELETE', path),
  }
}

export type Api = ReturnType<typeof createApi>

/**
 * Returns a client of the API authenticated as the logged in user, or redirects to the login page.
 */
export async function requireApi(
  context: AppLoadContext,
  request: Request,
): Promise<{api: Api; user: SessionUser}> {
  const {token, user} = await getUserSession(context, request)
  if (!token || !user) {
    return redirectToLogin(context, request)
  }

  return {api: createApi(context, request, token), user}
}
