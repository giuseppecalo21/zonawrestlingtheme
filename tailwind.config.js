/** Configurazione Tailwind del tema Zona Wrestling (stessi token della bozza). */
module.exports = {
  content: ['./*.php', './inc/**/*.php', './template-parts/**/*.php', './assets/js/*.js'],
  theme: {
    extend: {
      colors: {
        zw: {
          red: '#d7182a',
          redSoft: '#ff3b4e',
          redDark: '#a50f1e',
          acid: '#dfff00',
          acidHover: '#c8e600',
          velvet: '#090a0f',
          surfaceDark: '#12141c',
          surfaceElevated: '#1a1d28',
          lightBg: '#f2f4f8',
          ink: '#0f1117',
          muted: '#64687a'
        }
      },
      fontFamily: {
        sofia: ['"Sofia Sans"', 'sans-serif'],
        sofiaCond: ['"Sofia Sans Condensed"', 'sans-serif'],
        chivo: ['"Chivo"', 'sans-serif']
      },
      borderRadius: {
        card: 'var(--card-radius)',
        bento: 'var(--card-radius)',
        modal: 'var(--card-radius)'
      }
    }
  },
  plugins: []
};
