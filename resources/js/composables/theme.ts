// Flips the theme: the `dark` class drives Tailwind, the cookie is what the Blade
// shell reads to render the next page's class, so the two are kept in step.
const THEME_COOKIE = 'recruivo:theme'

export const toggleTheme = () => {
    const dark = !document.documentElement.classList.contains('dark')

    document.documentElement.classList.toggle('dark', dark)
    document.cookie = `${THEME_COOKIE}=${dark ? 'dark' : 'light'}; path=/; max-age=31536000`
}
