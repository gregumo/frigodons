export type Gender = 'm' | 'w'

export type User = {
  id: number
  email: string
  firstname: string
  lastname: string
  phone: string | null
  gender: Gender | null
  callbackMailOptIn: boolean
  missingVolunteerMailOptIn: boolean
  hasBeenTrained: boolean
  manager: boolean
}

/**
 * A user of the directory, with the number of dates booked this year.
 */
export type DirectoryUser = User & {
  yearCleaningCount: number
  yearSupervisingCount: number
}

/**
 * The person who booked a date.
 */
export type DateUser = Pick<
  User,
  'id' | 'email' | 'firstname' | 'lastname' | 'phone' | 'gender' | 'hasBeenTrained' | 'manager'
>

/**
 * The kind of date: cleaning the Frig'o dons (volunteers) or supervising (managers).
 */
export type DateContext = 'cleaning' | 'supervising'

export type CalendarDate = {
  id: number
  day: string
  cancellable: boolean
  user: DateUser
}

export type Participation = {
  yearDoneCleaningCount: number
  yearScheduledCleaningCount: number
  yearDoneSupervisingCount: number
  yearScheduledSupervisingCount: number
}

export type Stats = {
  volunteerCount: number
  managerCount: number
}

export type KeyValueInfo = {key: string; value: string}
