import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
		'./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
		 './vendor/laravel/jetstream/**/*.blade.php',
		 './storage/framework/views/*.php',
		 './resources/views/**/*.blade.php',
		 "./vendor/robsontenorio/mary/src/View/Components/**/*.php"
	],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    primary: '#2f6b9f',
                    primaryStrong: '#24557f',
                    secondary: '#27aaa9',
                    secondaryStrong: '#1b8d8b',
                    accent: '#f0bc72',
                    ink: '#16344c',
                    mist: '#edf8f8',
                },
            },
        },
    },

    plugins: [
		typography,
		require("daisyui")
	],
    daisyui: {
      themes: [
        {
          aces: {
            "primary": "#2f6b9f",
            "primary-content": "#f8fbff",
            "secondary": "#27aaa9",
            "secondary-content": "#f5ffff",
            "accent": "#f0bc72",
            "accent-content": "#493114",
            "neutral": "#16344c",
            "neutral-content": "#eef7fb",
            "base-100": "#ffffff",
            "base-200": "#f4fbfb",
            "base-300": "#e5f2f3",
            "base-content": "#16344c",
            "info": "#2f6b9f",
            "info-content": "#f5fbff",
            "success": "#27aaa9",
            "success-content": "#f4ffff",
            "warning": "#f0bc72",
            "warning-content": "#4b3215",
            "error": "#c75b5b",
            "error-content": "#fff6f6",
          }
        },
        {
          "aces-dark": {
            "primary": "#5aa2d8",
            "primary-content": "#071522",
            "secondary": "#38c7c5",
            "secondary-content": "#062322",
            "accent": "#f3c57d",
            "accent-content": "#2c1b08",
            "neutral": "#d7e5ec",
            "neutral-content": "#101820",
            "base-100": "#0f172a",
            "base-200": "#111c2e",
            "base-300": "#1e293b",
            "base-content": "#e5edf4",
            "info": "#6ab4e6",
            "info-content": "#071522",
            "success": "#4fd1c5",
            "success-content": "#052322",
            "warning": "#f3c57d",
            "warning-content": "#2c1b08",
            "error": "#f08484",
            "error-content": "#2b0909",
          }
        }
      ],
    },
  safelist: [
    'bg-primary',
    'bg-success',
    'bg-info',
    'bg-warning',
  ],
};
