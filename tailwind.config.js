/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {
      colors: {
        'purple': '#747ad5',
        'blue-200': '#B2CBF1',
        'purple-200': '#B2B2D2',
        'purple-600': '#6269d0',
        'yellow': '#f1d275',
        'yellow-700': '#8D6F17',
        'beige': '#F2F0F0',
        'black-200': '#9B9797',
        'black-400': '#686565',
        'black': '#210b09',
        'red-soft': '#F56A68',
        'orange-soft': '#F98B41',
        'green-soft': '#1ABC59'
      },
    },
    fontSize: {
      'xxs': '.5rem',
      'xs': '.75rem',
      'sm': '.875rem',
      'tiny': '.875rem',
      'base': '1rem',
      'lg': '1.125rem',
      'xl': '1.25rem',
      '2xl': '1.4rem',
      '3xl': '1.7rem',
      '4xl': '2.25rem',
      '5xl': '3rem',
      '6xl': '4rem',
      '7xl': '5rem',
    }
  },
  backgroundImage: {
    'flyer': "url('../img/flyer-recto.jpg')",
  },
  plugins: [],
}
