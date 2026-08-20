import forms from '@tailwindcss/forms'
import type { Config } from 'tailwindcss'

export default {
  darkMode: 'class',
  content: [
    './resources/views/inertia.blade.php',
    './resources/js/**/*.vue',
    './resources/js/**/*.ts',
  ],
  safelist: [
    'lg:flex',
    'lg:hidden',
    'whitespace-nowrap',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        display: ['"Space Grotesk"', '"Plus Jakarta Sans"', 'system-ui', 'sans-serif']
      }
    }
  },
  plugins: [forms]
} satisfies Config