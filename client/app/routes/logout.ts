import {redirect} from 'react-router'
import {destroyUserSession} from '~/session.server'
import type {Route} from './+types/logout'

export async function loader({context, request}: Route.LoaderArgs) {
  return redirect('/', {headers: {'Set-Cookie': await destroyUserSession(context, request)}})
}
