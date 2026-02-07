/**
 * i18n Module - Static JSON-based internationalization
 * Usage:
 *   await I18n.init('th');       // Initialize with Thai
 *   I18n.t('login');             // Get translation
 *   I18n.setLocale('en');        // Switch to English
 */
const I18n = (function () {
    let currentLocale = 'th';
    let translations = {};
    let basePath = '';

    /**
     * Initialize i18n with locale and load translations
     * @param {string} locale - 'th' or 'en'
     * @param {string} appBasePath - Base path for the app (e.g., '/myhr_services')
     */
    async function init(locale = 'th', appBasePath = '') {
        // Auto-detect base path from window.APP_BASE_PATH if not provided
        basePath = appBasePath || (window.APP_BASE_PATH || '');
        basePath = basePath.replace(/\/$/, '');
        currentLocale = locale;

        // Load from localStorage if available
        const savedLocale = localStorage.getItem('i18n_locale');
        if (savedLocale && ['th', 'en', 'mm'].includes(savedLocale)) {
            currentLocale = savedLocale;
        }

        await loadTranslations(currentLocale);
        apply();
    }

    /**
     * Load translations from JSON file
     */
    async function loadTranslations(locale) {
        try {
            // Use ASSET_BASE if available (passed from header.php), otherwise construct
            const assetBase = (window.ASSET_BASE) || ((basePath) ? `${basePath}/public/` : '/');

            // Try multiple possible locations for locale files to support different docroot setups
            // Prioritize assetBase which is dynamically determined by PHP
            const candidates = [
                `${assetBase}locales/${locale}.json`,
                `${basePath}/locales/${locale}.json`, // Fallback for some structures
                `/locales/${locale}.json` // Absolute fallback
            ];

            let response = null;
            for (const url of candidates) {
                try {
                    response = await fetch(url);
                    if (response && response.ok) {
                        translations = await response.json();
                        return;
                    }
                } catch (e) {
                    // ignore and try next
                }
            }
            throw new Error(`Failed to load ${locale}.json from candidates`);
        } catch (error) {
            console.error('i18n load error:', error);
            translations = {};
        }
    }

    /**
     * Get translation for a key
     * @param {string} key - Translation key
     * @param {object} vars - Variables for interpolation (optional)
     * @returns {string} Translated text or key if not found
     */
    function t(key, vars = {}) {
        let text = translations[key] || key;

        // Simple variable interpolation: {{name}} -> value
        Object.keys(vars).forEach(varKey => {
            text = text.replace(new RegExp(`{{${varKey}}}`, 'g'), vars[varKey]);
        });

        return text;
    }

    /**
     * Set locale and reload translations
     */
    async function setLocale(locale) {
        if (!['th', 'en', 'mm'].includes(locale)) {
            console.warn(`Unsupported locale: ${locale}`);
            return;
        }

        currentLocale = locale;
        localStorage.setItem('i18n_locale', locale);
        await loadTranslations(locale);
        apply();

        // Dispatch event for components to react
        window.dispatchEvent(new CustomEvent('language-changed', {
            detail: { locale: locale }
        }));
    }

    /**
     * Get current locale
     */
    function getLocale() {
        return currentLocale;
    }

    /**
     * Apply translations to all elements with data-i18n attributes
     */
    function apply() {
        // Text content: data-i18n="key"
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (key && translations[key]) {
                el.textContent = translations[key];
            }
        });

        // Placeholder: data-i18n-placeholder="key"
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            if (key && translations[key]) {
                el.placeholder = translations[key];
            }
        });

        // Title attribute: data-i18n-title="key"
        document.querySelectorAll('[data-i18n-title]').forEach(el => {
            const key = el.getAttribute('data-i18n-title');
            if (key && translations[key]) {
                el.title = translations[key];
            }
        });
    }

    /**
     * Get all available locales
     */
    function getAvailableLocales() {
        return [
            { code: 'th', name: 'ไทย', flag: 'TH' },
            { code: 'en', name: 'English', flag: 'EN' },
            { code: 'mm', name: 'မြန်မာ', flag: 'MM' }
        ];
    }

    return {
        init,
        t,
        setLocale,
        getLocale,
        apply,
        getAvailableLocales
    };
})();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = I18n;
}
