import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
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
