import {Link, redirect, useNavigation} from 'react-router'
import {ApiError, createApi} from '~/api.server'
import {Alert, Field, Form, SubmitButton} from '~/components/Form'
import {commitUserSession, getUserSession} from '~/session.server'
import type {User} from '~/types'
import type {Route} from './+types/login'

export const meta = () => [{title: "Connexion - Frig'o dons"}]

/**
 * Only redirects to pages of the website.
 */
function redirectTo(request: Request): string {
  const value = new URL(request.url).searchParams.get('redirectTo') ?? ''

  return value.startsWith('/') && !value.startsWith('//') ? value : '/calendrier'
}

export async function loader({context, request}: Route.LoaderArgs) {
  const {user} = await getUserSession(context, request)
  if (user) {
    throw redirect(redirectTo(request))
  }

  return null
}

export async function action({context, request}: Route.ActionArgs) {
  const formData = await request.formData()
  const email = String(formData.get('email') ?? '')
  const api = createApi(context, request, null)

  let token: string
  try {
    ;({token} = await api.post<{token: string}>('/login', {email}))
  } catch (error) {
    if (error instanceof ApiError) {
      return {email, error: error.fieldErrors.email ?? error.message}
    }
    throw error
  }

  const user = await createApi(context, request, token).get<User>('/me')

  return redirect(redirectTo(request), {
    headers: {
      'Set-Cookie': await commitUserSession(context, request, token, {
        id: user.id,
        firstname: user.firstname,
        manager: user.manager,
      }),
    },
  })
}

export default function Login({actionData}: Route.ComponentProps) {
  const isSubmitting = useNavigation().state === 'submitting'

  return (
    <>
      <h1>Connexion</h1>
      <div className="md:w-2/3 lg:w-1/2 xl:w-1/3">
        <div className="mb-6">
          <p>Pour accéder au planning des bénévoles, entrez simplement votre adresse e-mail.</p>
          <p>
            Si votre adresse n'est pas reconnue ou que vous souhaitez devenir bénévole, merci de
            nous écrire en utilisant le{' '}
            <b>
              <Link to="/contact" className="link">
                formulaire de contact
              </Link>
            </b>
            .
          </p>
        </div>
        {actionData?.error && <Alert type="error">{actionData.error}</Alert>}
        <Form method="post">
          <Field
            label="Adresse e-mail"
            name="email"
            type="email"
            autoComplete="email"
            defaultValue={actionData?.email}
            isRequired
            autoFocus
          />
          <SubmitButton isPending={isSubmitting}>Connexion</SubmitButton>
        </Form>
      </div>
    </>
  )
}
