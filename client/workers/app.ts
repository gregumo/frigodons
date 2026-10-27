import {createRequestHandler} from 'react-router'

declare module 'react-router' {
  export interface AppLoadContext {
    cloudflare: {
      env: Env
      ctx: ExecutionContext
    }
  }
}

const requestHandler = createRequestHandler(
  () => import('virtual:react-router/server-build'),
  import.meta.env.MODE,
)

export default {
  fetch(request, env, ctx) {
    // The redirects to HTTPS and from www.frigodons.org are made by Cloudflare (see infra/terraform/cloudflare.tf).
    return requestHandler(request, {
      cloudflare: {env, ctx},
    })
  },
} satisfies ExportedHandler<Env>
