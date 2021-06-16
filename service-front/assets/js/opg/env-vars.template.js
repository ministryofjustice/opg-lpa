// Handlebars template to create JavaScript file which will append specified
// variables, passed to this template as env, to the window object.
// This enables the build to put environment variables into the
// browser's JavaScript environment, such as the current git commit hash.

window.MAKE_ENV = {};

{{#if ENV_VARS}}
    {{#each ENV_VARS}}
window.MAKE_ENV.{{@key}} = "{{this}}";
    {{/each}}
{{/if}}
