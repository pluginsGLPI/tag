import stylisticjs from "@stylistic/eslint-plugin-js";
import globals from "globals";
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

export default [{
    ignores: ["node_modules/*", "vendor/*", "eslint.config.mjs", ".stylelintrc.js"], //ignore self config file because of import.meta.url
}, ...compat.extends("eslint:recommended"), {
    plugins: {
        "@stylistic/js": stylisticjs,
    },

    languageOptions: {
        globals: {
            ...globals.browser,
            ...globals.jquery,
            CFG_GLPI: true,
            GLPI_PLUGINS_PATH: true,
            __: true,
            _n: true,
            _x: true,
            _nx: true,
        },

        ecmaVersion: 8,
        sourceType: "script",
    },

    rules: {
        "no-console": ["error", {
            allow: ["warn", "error"],
        }],

        "no-unused-vars": ["error", {
            vars: "local",
        }],

        "@stylistic/js/eol-last": ["error", "always"],
        "@stylistic/js/indent": ["error", 4],
        "@stylistic/js/linebreak-style": ["error", "unix"],
        "@stylistic/js/semi": ["error", "always"],
    },
}, {}];
