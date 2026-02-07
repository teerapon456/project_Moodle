/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./public/**/*.php",
        "./core/**/*.php",
        "./Modules/**/*.php",
        "./app/**/*.php"
    ],
    theme: {
        extend: {
            colors: {
                'primary': '#A21D21',
                'primary-light': '#c62828',
                'primary-dark': '#8b181b',
                'secondary': '#374151',
                'success': '#10b981',
                'warning': '#f59e0b',
                'danger': '#ef4444',
                'info': '#3b82f6'
            },
            fontFamily: {
                'kanit': ['Kanit', 'sans-serif'],
                'fredoka': ['Fredoka', 'sans-serif'],
                'nunito': ['Nunito', 'sans-serif'],
            }
        },
    },
    plugins: [],
}
