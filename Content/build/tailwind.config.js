/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './App/**/*.php',
    './Forecor/**/*.php',
    './Inc/Template/**/*.twig',
    './Inc/Template/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#1a252f',
          dark: '#172029',
          light: '#243447',
        },
      },
    },
  },
  plugins: [],
};
