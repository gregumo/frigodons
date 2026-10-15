export const meta = () => [{title: "Frig'o dons"}]

export default function Home() {
  return (
    <div className="grid pb-20 lg:h-[calc(100vh-124px)] lg:pb-0">
      <div className="flex-row lg:flex lg:space-x-16">
        <div className="mt-20 flex h-[70vh] bg-[url(/img/flyer-pique-nique.jpg)] bg-contain bg-center bg-no-repeat lg:mt-0 lg:h-auto lg:basis-2/3" />
        <div className="mt-8 flex px-5 text-center lg:mt-0 lg:basis-1/3">
          <div className="m-auto">
            <p className="mt-8">L’équipe du Frig’o dons vous invite à un</p>
            <h2>Pique-Nique (et BBQ) partagé</h2>
            <p>Le samedi 14 juin à partir de 12h</p>
            <p>
              Aux Jardins Familiaux, rue de la Gaudinière à La Montagne, (face à la salle de sport
              Jules Ladoumègue)
            </p>
            <p className="underline">
              <a target="_blank" rel="noreferrer" href="https://maps.app.goo.gl/kozjsxyWQU6kFA7E8">
                Voir sur une carte
              </a>
            </p>
            <p className="mt-8">
              Chacun apporte un pique nique à partager et/ou des grillades pour le barbecue.
            </p>
            <p>
              L'équipe de La Cordée prévoit des bancs et tables, tout ce qu'il faut pour le barbecue
              et des jeux pour prolonger l’après-midi.
            </p>
            <p>Venez nombreux !</p>
          </div>
        </div>
      </div>
    </div>
  )
}
