const colors = require('tailwindcss/colors');

/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./src/**/*.{js,jsx,ts,tsx}"],
  theme: {
    extend: {
      // Semantic tokens: components reference these, never raw palette names.
      // Rebranding the app = remapping these five entries.
      colors: {
        primary: colors.blue,
        surface: colors.gray,
        success: colors.emerald,
        danger: colors.red,
        warning: colors.amber,
      },
    },
  },
  plugins: [],
}
