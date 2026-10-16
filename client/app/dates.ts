/**
 * Days are handled as `YYYY-MM-DD` strings (the format of the API), in the timezone of Paris.
 */

const TIMEZONE = 'Europe/Paris'

export function today(): string {
  return new Intl.DateTimeFormat('en-CA', {timeZone: TIMEZONE}).format(new Date())
}

function toDate(day: string): Date {
  return new Date(`${day}T00:00:00Z`)
}

function toDay(date: Date): string {
  return date.toISOString().slice(0, 10)
}

export function addDays(day: string, count: number): string {
  const date = toDate(day)
  date.setUTCDate(date.getUTCDate() + count)

  return toDay(date)
}

/**
 * The days displayed on the calendar of a month: complete weeks, from Monday to Sunday.
 */
export function monthViewDays(year: number, month: number): string[] {
  const first = new Date(Date.UTC(year, month - 1, 1))
  const last = new Date(Date.UTC(year, month, 0))
  // getUTCDay() returns 0 for Sunday: shift so that Monday is 0.
  const start = addDays(toDay(first), -((first.getUTCDay() + 6) % 7))
  const end = addDays(toDay(last), (7 - last.getUTCDay()) % 7)

  const days: string[] = []
  for (let day = start; day <= end; day = addDays(day, 1)) {
    days.push(day)
  }

  return days
}

export function daysInYear(year: number): number {
  return (Date.UTC(year + 1, 0, 1) - Date.UTC(year, 0, 1)) / 86_400_000
}

function capitalize(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1)
}

/**
 * Eg. "Lundi 19 octobre 2026".
 */
export function formatDay(day: string): string {
  return capitalize(
    new Intl.DateTimeFormat('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      timeZone: 'UTC',
    }).format(toDate(day)),
  )
}

/**
 * Eg. "Octobre 2026".
 */
export function formatMonth(year: number, month: number): string {
  return capitalize(
    new Intl.DateTimeFormat('fr-FR', {month: 'long', year: 'numeric', timeZone: 'UTC'}).format(
      new Date(Date.UTC(year, month - 1, 1)),
    ),
  )
}
