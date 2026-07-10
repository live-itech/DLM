import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Orbitron', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Brand identity PT Dimas Love Medika
                gold: {
                    light: '#F5D98A',
                    DEFAULT: '#CDA45E',
                    dark: '#A67C2A',
                    50: '#FBF4E4',
                    100: '#F5D98A',
                    200: '#E9C87A',
                    300: '#DDB86A',
                    400: '#D3AE62',
                    500: '#CDA45E',
                    600: '#B8923F',
                    700: '#A67C2A',
                    800: '#7E5E20',
                    900: '#553F15',
                },
                navy: {
                    light: '#1E3350',
                    DEFAULT: '#14233A',
                    dark: '#0F1B2D',
                    darker: '#0A1320',
                },
            },
            boxShadow: {
                gold: '0 4px 14px 0 rgba(205, 164, 94, 0.25)',
            },
            backgroundImage: {
                'gold-gradient': 'linear-gradient(135deg, #A67C2A 0%, #CDA45E 50%, #F5D98A 100%)',
            },
        },
    },

    plugins: [forms],
};
