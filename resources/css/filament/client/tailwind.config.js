import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Client/**/*.php',
        './resources/views/filament/client/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        fontSize: {
            'xs': '.7rem',
            'sm': '.8rem',
            'base': '.9rem',
            'lg': '1.1rem',
            'xl': '1.2rem',
            '2xl': '1.35rem',
            '3xl': '1.9rem',
        }
    }
}
