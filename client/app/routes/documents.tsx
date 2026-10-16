import {requireApi} from '~/api.server'
import type {KeyValueInfo} from '~/types'
import type {Route} from './+types/documents'

export const meta = () => [{title: "Documents et codes - Frig'o dons"}]

export async function loader({context, request}: Route.LoaderArgs) {
  const {api} = await requireApi(context, request)
  const infos = await api.get<KeyValueInfo[]>('/key-value-infos')
  const value = (key: string) => infos.find((info) => info.key === key)?.value ?? '?'

  return {
    closetPadlockCode: value('closetPadlockCode'),
  }
}

export default function Documents({loaderData}: Route.ComponentProps) {
  return (
    <>
      <h2>Documents</h2>
      <h3>Guide du bénévole</h3>
      <p>
        <a
          href="https://drive.google.com/file/d/1MSCQIa2glBZnXmoUSF225S3GgAKMha7d/view?usp=sharing"
          target="_blank"
          rel="noreferrer"
          className="underline"
        >
          Télécharger le guide du bénévole
        </a>
      </p>
      <h3>Engagement moral du bénévole</h3>
      <p>
        <a
          href="https://drive.google.com/file/d/1vJr42BLkKKyg5FZlPtr5leGYWtdGf7wI/view?usp=sharing"
          target="_blank"
          rel="noreferrer"
          className="underline"
        >
          Télécharger l'engagement moral du bénévole
        </a>
      </p>
      <h2>Code du cadenas</h2>
      <p>
        Le code du cadenas du placard est <b>{loaderData.closetPadlockCode}</b>
      </p>
    </>
  )
}
