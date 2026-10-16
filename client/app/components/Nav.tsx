import clsx from 'clsx'
import {Button, Dialog, DialogTrigger, Modal, ModalOverlay} from 'react-aria-components'
import {Link, NavLink} from 'react-router'
import type {SessionUser} from '~/session.server'

type Item = {to: string; text: string; anonymous: boolean; managerOnly?: boolean}

const items: Item[] = [
  {to: '/devenir-benevole', text: 'Devenir bénévole', anonymous: true},
  {to: '/contact', text: 'Contact', anonymous: true},
  {to: '/calendrier', text: 'Planning', anonymous: true},
  {to: '/documents', text: 'Documents', anonymous: false},
  {to: '/mes-informations', text: 'Mes infos', anonymous: false},
  {to: '/annuaire', text: 'Annuaire', anonymous: false, managerOnly: true},
  {to: '/deconnexion', text: 'Déconnexion', anonymous: false},
]

const linkClassName = ({isActive}: {isActive: boolean}) =>
  clsx('transition-colors duration-500', isActive ? 'bg-beige text-black' : 'hover:bg-purple-600')

export function Nav({user}: {user: SessionUser | null}) {
  const visibleItems = items.filter(
    (item) => (item.anonymous || user) && (!item.managerOnly || user?.manager),
  )

  return (
    <nav className="h-16 bg-purple text-white">
      <div className="container mx-auto flex h-full justify-between px-4">
        <Link
          to="/"
          className="flex items-center px-2 font-chelsea text-xl uppercase"
          aria-label="Accueil"
        >
          <img className="mr-3 h-10" src="/img/menu_logo.png" alt="" />
          Frig'o dons
        </Link>

        <div className={clsx('hidden items-center', user ? 'lg:flex' : 'md:flex')}>
          {visibleItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className={(state) => clsx(linkClassName(state), 'px-4 py-5')}
            >
              {item.text}
            </NavLink>
          ))}
        </div>

        <DialogTrigger>
          <Button
            className={clsx('flex items-center outline-none', user ? 'lg:hidden' : 'md:hidden')}
            aria-label="Ouvrir le menu"
          >
            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeWidth="2" d="m4 6h16M4 12h16M4 18h16" />
            </svg>
          </Button>
          <ModalOverlay isDismissable className="fixed inset-0 z-40 bg-black/40">
            <Modal className="fixed top-0 right-0 z-50 h-screen bg-purple text-white shadow-xl">
              <Dialog className="outline-none" aria-label="Menu">
                {({close}) => (
                  <>
                    <div className="h-16 pt-4 pr-4 text-right">
                      <Button onPress={close} aria-label="Fermer le menu" className="outline-none">
                        <svg width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                          <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z" />
                        </svg>
                      </Button>
                    </div>
                    {visibleItems.map((item) => (
                      <NavLink
                        key={item.to}
                        to={item.to}
                        onClick={close}
                        className={(state) => clsx(linkClassName(state), 'block px-4 py-4')}
                      >
                        {item.text}
                      </NavLink>
                    ))}
                  </>
                )}
              </Dialog>
            </Modal>
          </ModalOverlay>
        </DialogTrigger>
      </div>
    </nav>
  )
}
