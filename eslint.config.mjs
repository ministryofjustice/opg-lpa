import promise from "eslint-plugin-promise";
import globals from "globals";
import babelParser from "@babel/eslint-parser";
import path from "node:path";
import { fileURLToPath } from "node:url";
import js from "@eslint/js";
import { FlatCompat } from "@eslint/eslintrc";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const compat = new FlatCompat({
    baseDirectory: __dirname,
    recommendedConfig: js.configs.recommended,
    allConfig: js.configs.all
});

export default [
    // Global ignores
    {
        ignores: [
            "service-front/assets/js/opg/env-vars.template.js",
            "service-front/assets/js/opg/env-vars.js",
            "service-front/assets/js/lpa/lpa.templates.js",
        ],
    },

    // Service-front JavaScript configuration
    {
        files: ["service-front/assets/js/**/*.js"],

        ...compat.extends(
            "plugin:prettier/recommended",
            "eslint:recommended",
            "plugin:promise/recommended",
        )[0],

        plugins: {
            promise,
        },

        languageOptions: {
            globals: {
                ...globals.browser,
                _: "readonly",
                GOVUK: "readonly",
                moj: "readonly",
                jQuery: "readonly",
                gaConfig: "readonly",
                ga: "readonly",
                _gaq: "readonly",
                lpa: "readonly",
                $: "readonly",
            },

            parser: babelParser,
            ecmaVersion: 6,
            sourceType: "module",

            parserOptions: {
                requireConfigFile: false,
            },
        },

        rules: {
            // Allow unused variables if they start with underscore
            "no-unused-vars": ["error", { "argsIgnorePattern": "^_", "varsIgnorePattern": "^_", "caughtErrorsIgnorePattern": "^_" }],
        },
    },

    // Cypress test files configuration
    {
        files: ["cypress/e2e/common/**/*.js"],

        languageOptions: {
            globals: {
                ...globals.browser,
                cy: "readonly",
                Cypress: "readonly",
                require: "readonly",
                expect: "readonly",
                assert: "readonly",
            },

            ecmaVersion: 2022,
            sourceType: "module",

            parserOptions: {
                requireConfigFile: false,
            },
        },

        rules: {
            // Allow unused variables if they start with underscore
            "no-unused-vars": ["error", { "argsIgnorePattern": "^_", "varsIgnorePattern": "^_", "caughtErrorsIgnorePattern": "^_" }],
            // Disable promise rules for Cypress tests
            // Cypress uses a different command chaining pattern where .then()
            // callbacks don't need to return values and nesting is common
            "promise/always-return": "off",
            "promise/no-nesting": "off",
            "promise/catch-or-return": "off",
        },
    },
];
