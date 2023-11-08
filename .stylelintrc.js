
module.exports = {
    "extends": "stylelint-config-standard",
    "ignoreFiles": [
        "node_modules/**/*",
        "vendor/**/*"
    ],
    "rules": {
        "selector-class-pattern": null, // DISABLE: Expected class selector to be kebab-case
    },
};
