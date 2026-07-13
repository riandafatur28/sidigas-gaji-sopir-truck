/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        pupr: {
          yellow: '#FFC107',
          'yellow-light': '#FFD54F',
          'yellow-dark': '#F59E0B',
          black: '#1a1a1a',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
        'medium': '0 4px 20px -2px rgba(0, 0, 0, 0.1), 0 8px 25px -3px rgba(0, 0, 0, 0.08)',
        'large': '0 10px 40px -10px rgba(0, 0, 0, 0.15), 0 20px 50px -5px rgba(0, 0, 0, 0.1)',
      },
    },
  },
  plugins: [],
}
