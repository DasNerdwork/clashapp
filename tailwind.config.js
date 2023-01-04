/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.{html,js,php}",
    "./accounts/*.php",
    "./js/*.js",
    "./live/*.php",
    "./templates/*.{html,php}",
  ],
  safelist: [
    'bg-lose',
    'text-online',
    'text-offline',
    'text-yellow-400',
    'text-darker',
    'text-dark',
    {
      pattern: /text-(silver|gold|bronze|platinum|iron|diamond|master|grandmaster|challenger)/,
    },
    {
      pattern: /text-(threat-xxs|threat-xs|threat-s|threat-m|threat-l|threat-xl|threat-xxl)/,
    },
  ],
  theme: {
    extend: {
      colors: {
        'silver': '#99a0b5',
        'gold': '#d79c5d',
        'bronze': '#cd8d7f',
        'platinum': '#23af88',
        'iron': '#392b28',
        'diamond': '#617ecb',
        'master': '#b160f3',
        'grandmaster': '#cd423a',
        'challenger': '#52cfff',
        'threat-xxs': '#dddddd',
        'threat-xs': '#e1c1c1',
        'threat-s': '#ea8a8a',
        'threat-m': '#f25353',
        'threat-l': '#f73737',
        'threat-xl': '#fbc1c1',
        'threat-xxl': '#ff0000',
	'lose': '#3c000f80',
	'win': '#003c0f80',
	'offline': '#b31414',
	'online': '#1aa23a',
	'light': '#2a2d40',
	'dark' : '#141624',
	'darker': '#0e0f18',
      },
      gridTemplateColumns: {
        'topbartwok': '25% 328px auto 328px 25%',
        'topbarfullhd': 'calc((100vw - 1172px)/2) 328px 420px 328px calc((100vw - 1172px)/2)',
      },
    },
    screens: {
      'hd': '721px',
      'fullhd': '1281px',
      'twok': '1921px',
    },
  },
  plugins: [],
}
