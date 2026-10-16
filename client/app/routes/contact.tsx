import {useEffect, useRef, useState} from 'react'
import {useNavigation} from 'react-router'
import {ApiError, createApi} from '~/api.server'
import {Alert, Field, Form, formatPhone, SubmitButton} from '~/components/Form'
import type {Route} from './+types/contact'

export const meta = () => [{title: "Contact - Frig'o dons"}]

export async function action({context, request}: Route.ActionArgs) {
  const formData = await request.formData()
  const value = (name: string) => String(formData.get(name) ?? '').trim()

  try {
    await createApi(context, request, null).post('/contact', {
      firstname: value('firstname'),
      lastname: value('lastname'),
      email: value('email'),
      phone: value('phone') || null,
      message: value('message'),
      website: value('website') || null,
      altcha: value('altcha'),
    })
  } catch (error) {
    if (error instanceof ApiError && error.status === 422) {
      const {altcha, ...fieldErrors} = error.fieldErrors

      return {success: false, fieldErrors, error: altcha ?? null}
    }

    return {
      success: false,
      fieldErrors: {},
      error: "Une erreur s'est produite. Votre message n'a pas été envoyé, merci de réessayer.",
    }
  }

  return {success: true, fieldErrors: {}, error: null}
}

export default function Contact({actionData}: Route.ComponentProps) {
  const isSubmitting = useNavigation().state === 'submitting'
  const [phone, setPhone] = useState('')
  const altchaRef = useRef<HTMLElement & {reset?: () => void}>(null)

  // The widget is a web component, which only works in the browser.
  useEffect(() => {
    import('altcha').then(() => import('altcha/i18n/fr-fr'))
  }, [])

  // A challenge can only be used once: a new one must be solved after an error.
  useEffect(() => {
    if (actionData && !actionData.success) {
      altchaRef.current?.reset?.()
    }
  }, [actionData])

  return (
    <>
      <h1>Contact</h1>
      <div className="md:w-2/3 lg:w-1/2 xl:w-1/3">
        <div className="mb-6">
          <p>Pour nous contacter, merci d'utiliser le formulaire ci-dessous.</p>
          <p>N'hésitez pas à nous laisser votre numéro de téléphone si vous le souhaitez.</p>
          <p>Nous reviendrons vers vous au plus vite.</p>
        </div>

        {actionData?.success ? (
          <Alert type="success">Votre message a bien été envoyé. Merci !</Alert>
        ) : (
          <>
            {actionData?.error && <Alert type="error">{actionData.error}</Alert>}
            <Form method="post" validationErrors={actionData?.fieldErrors}>
              {/* Honeypot: hidden to visitors, only bots fill it in. */}
              <input
                type="text"
                name="website"
                tabIndex={-1}
                autoComplete="off"
                aria-hidden="true"
                className="absolute -left-[9999px] h-0 w-0 opacity-0"
              />
              <Field label="Prénom" name="firstname" autoComplete="given-name" isRequired />
              <Field label="Nom" name="lastname" autoComplete="family-name" isRequired />
              <Field
                label="Adresse e-mail"
                name="email"
                type="email"
                autoComplete="email"
                isRequired
              />
              <Field
                label="Téléphone"
                name="phone"
                type="tel"
                autoComplete="tel"
                value={phone}
                onChange={(value) => setPhone(formatPhone(value))}
              />
              <Field label="Message" name="message" multiline isRequired />
              <altcha-widget
                ref={altchaRef}
                challengeurl="/altcha-challenge"
                language="fr-fr"
                name="altcha"
                hidelogo
                hidefooter
              />
              <SubmitButton isPending={isSubmitting}>Envoyer</SubmitButton>
            </Form>
          </>
        )}
      </div>
    </>
  )
}
