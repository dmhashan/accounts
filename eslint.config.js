import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

export default [
    // Global ignores
    {
        ignores: [
            'vendor/**',
            'node_modules/**',
            'public/build/**',
            'storage/**',
            'bootstrap/cache/**',
        ],
    },

    // Base JS recommended rules
    js.configs.recommended,

    // Vue 3 recommended rules
    ...pluginVue.configs['flat/recommended'],

    // Project-wide settings
    {
        files: ['resources/js/**/*.{js,vue}'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.es2022,
            },
        },
        rules: {
            // === Vue best practices ===
            'vue/component-name-in-template-casing': ['error', 'PascalCase'],
            'vue/component-definition-name-casing': ['error', 'PascalCase'],
            'vue/define-macros-order': ['error', {
                order: ['defineOptions', 'defineProps', 'defineEmits', 'defineExpose'],
            }],
            'vue/no-unused-vars': 'error',
            'vue/no-use-v-if-with-v-for': 'error',
            'vue/no-v-html': 'warn',
            'vue/require-default-prop': 'error',
            'vue/require-prop-types': 'error',
            'vue/v-bind-style': 'error',
            'vue/v-on-style': 'error',
            'vue/no-template-shadow': 'error',
            'vue/no-empty-component-block': 'error',
            'vue/padding-line-between-blocks': 'error',
            'vue/prefer-true-attribute-shorthand': 'error',
            'vue/html-self-closing': ['error', {
                html: { void: 'always', normal: 'always', component: 'always' },
                svg: 'always',
                math: 'always',
            }],
            'vue/max-attributes-per-line': ['error', {
                singleline: { max: 3 },
                multiline: { max: 1 },
            }],
            'vue/attributes-order': ['error', { alphabetical: false }],
            'vue/order-in-components': 'error',

            // === JavaScript best practices ===
            'no-console': ['warn', { allow: ['warn', 'error'] }],
            'no-debugger': 'error',
            'no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
            'no-var': 'error',
            'prefer-const': 'error',
            'prefer-arrow-callback': 'error',
            'object-shorthand': 'error',
            'no-duplicate-imports': 'error',
            'eqeqeq': ['error', 'always'],
            'no-eval': 'error',
            'no-implied-eval': 'error',
            'no-new-func': 'error',

            // === Security (OWASP basics) ===
            'no-alert': 'warn',
        },
    },
];
