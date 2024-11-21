const defaultConfig = require( '@wordpress/scripts/config/.eslintrc.js' );

// Add customizations to WordPress eslint config.
const config = {
    ...defaultConfig,
    settings: {
        'import/parsers': {
            '@typescript-eslint/parser': [ '.js', '.jsx', '.ts', '.tsx' ],
        },
        'import/resolver': {
            typescript: {
                alwaysTryTypes: true,
            },
        },
    },
    rules: {
        ...defaultConfig?.rules,
        '@wordpress/i18n-text-domain': [
            'error',
            {
                allowedTextDomain: 'stellarpay',
            },
        ],
    },
};

module.exports = config;
