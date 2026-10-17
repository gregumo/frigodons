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
    // Redirect to HTTPS, and www.frigodons.org (also attached to the Worker, see infra/terraform/cloudflare.tf) to
    // frigodons.org. Not in development, which is served over HTTP.
    const url = new URL(request.url)
    if (import.meta.env.PROD && (url.protocol === 'http:' || url.hostname.startsWith('www.'))) {
      url.protocol = 'https:'
      url.hostname = url.hostname.replace(/^www\./, '')
      return Response.redirect(url.toString(), 301)
    }

    return requestHandler(request, {
      cloudflare: {env, ctx},
    })
  },
} satisfies ExportedHandler<Env>
