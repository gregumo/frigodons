import {index, type RouteConfig, route} from '@react-router/dev/routes'

export default [
  index('routes/home.tsx'),
  route('devenir-benevole', 'routes/volunteer.tsx'),
  route('contact', 'routes/contact.tsx'),
  route('altcha-challenge', 'routes/altcha-challenge.ts'),
  route('connexion', 'routes/login.tsx'),
  route('deconnexion', 'routes/logout.ts'),
  route('calendrier/:month?/:year?', 'routes/calendar.tsx'),
  route('documents', 'routes/documents.tsx'),
  route('mes-informations', 'routes/profile.tsx'),
  route('annuaire', 'routes/directory.tsx'),
] satisfies RouteConfig
