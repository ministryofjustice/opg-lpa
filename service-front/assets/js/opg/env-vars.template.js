// Handlebars template to create JavaScript file which will append specified
// variables, passed to this template as env, to the window object.
// This enables the build to put environment variables into the
// browser's JavaScript environment, such as the current git commit hash.

window.BUILD_ENV = {};

{{#if ENV_VARS}}
    {{#each ENV_VARS}}
window.BUILD_ENV.{{@key}} = "{{this}}";
    {{/each}}
{{/if}}

// return a value from window.MAKE_ENV (if varName is a key in it),
// or undefined (if not)
window.getBuildEnvVar = function (varName) {
    if (!(varName in window.BUILD_ENV)) {
        return undefined;
    }

    return window.BUILD_ENV[varName];
};

window.getBuildRevision = function () {
    return window.getBuildEnvVar('revision');
};
