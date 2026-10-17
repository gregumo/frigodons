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
    // www.frigodons.org is also attached to the Worker (see infra/terraform/cloudflare.tf): redirect to frigodons.org.
    const url = new URL(request.url)
    if (url.hostname.startsWith('www.')) {
      url.hostname = url.hostname.slice('www.'.length)
      return Response.redirect(url.toString(), 301)
    }

    return requestHandler(request, {
      cloudflare: {env, ctx},
    })
  },
} satisfies ExportedHandler<Env>
