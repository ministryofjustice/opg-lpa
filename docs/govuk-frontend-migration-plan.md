# GOV.UK Frontend v5 Migration — `service-front`

## Introduction

`service-front` currently runs **two GOV.UK Frontend stacks in parallel**:

- **Legacy:** `govuk_frontend_toolkit` (^9.0.1), `govuk_template_mustache` (^0.26.0), `govuk-elements-sass` (^3.1.3).
- **Modern:** `govuk-frontend` (^5.11.1) — which partially adopted (page wrapper, header, footer, fonts, `initAll`, cookies page, type/index, accordion details).

The majority of LPA flow templates and the bespoke SCSS in `assets/sass/patterns/*` and `assets/sass/extensions/*` still depend on legacy mixins, variables, and class names. Removing the three legacy npm packages today would break SCSS compilation and visually regress most pages.

Cypress impact is small (only 2 selectors reference legacy classes — most use `data-cy`).

---

## 1. Current state inventory

### 1.1 OLD libraries — and where they're still required

| Concern | File | Detail |
|---|---|---|
| SCSS load paths | `service-front/build-css.sh` | `--load-path` adds `govuk_frontend_toolkit/stylesheets` and `govuk-elements-sass/public/sass` |
| CSS asset copy | `service-front/build-css.sh` | Copies `govuk-template.css` + `govuk-template-print.css` from `govuk_template_mustache` into `public/assets/v2/css/` |
| JS bundle | `service-front/build.js` (~line 20) | Concatenates `govuk_frontend_toolkit/javascripts/govuk/show-hide-content.js` |
| SCSS root import | `service-front/assets/sass/application.scss` (line 10) | `@import "govuk-elements";` |
| Compatibility flags | `service-front/assets/sass/settings/_variables.scss` | `$govuk-compatibility-govukfrontendtoolkit: true;` `$govuk-compatibility-govukelements: true;` |
| Legacy CSS `<link>`s | `service-front/module/Application/view/layout/layout.twig` (lines 8, 11) | Loads `govuk-template.css` + `govuk-template-print.css` alongside `application.css` |
| Bespoke SCSS using toolkit mixins/vars | `assets/sass/patterns/_buttons.scss`, `_panels.scss`, `_accordion.scss`, `_popup.scss`, `_help-system.scss`, `_form-popup.scss`, `_dialog-popup.scss`, `_session-timeout.scss`, `_meta-data.scss`, `_global-nav.scss`, `_appstatus.scss`, `extensions/elements/_elements-typography.scss`, `extensions/elements/_icons.scss`, `extensions/elements/_forms.scss`, `utilities/_helpers.scss`, `print.scss` | Uses `@include media(...)`, `core-16/19`, `bold-19/24/36/48`, `heading-24/27`, `button($colour)`, `box-sizing`, `box-shadow`, `ie-lte`, `em(...)`, vars `$panel-colour`, `$grey-1..4`, `$black`, `$link-colour`, `$focus-colour`, `$error-colour`, `$gutter` |

### 1.2 Legacy class names in templates (approximate counts via AI grep)

| Class family | Count | Hot files |
|---|---|---|
| `heading-xlarge / -large / -medium / -small` | ≥ 20 | `layout/layout.twig`, `layout/accordion-layout.twig`, `application/macros.twig`, `general/stats.html.twig`, `layout/partials/timeout.twig` |
| `button`, `button-secondary`, `button-warning`, `button-link`, `button-input-to-link` | ≥ 20 | LPA flows: `donor`, `summary`, `people-to-notify`, plus `auth`, `feedback`, `forgot-password`, `register` |
| `form-control`, `form-group`, `form-label`, `form-hint`, `error-summary`, `error-summary-heading`, `error-summary-list`, `error-summary-text`, `error-message`, `form-group-error` | many | `application/macros.twig`, `layout/partials/form-element-errors.twig`, `change-password/index.twig`, `register/resend-email.twig`, all form-bearing flows |
| `lede`, `notice`, `text` | several | shared macros + flow pages |
| `global-cookie-message__*` (bespoke) | 1 | `layout/layout.twig` |

### 1.3 NEW (`govuk-frontend@^5.11.1`) — already adopted

- SCSS: `@use "govuk-frontend/dist/govuk/index"` with `$govuk-assets-path: "/assets/v2/"` (`assets/sass/application.scss`).
- JS: `govuk-frontend.min.js` copied in `build.js` (~lines 177–188); `initAll()` in `assets/js/opg/govuk-init.js`.
- HTML wrapper: `<html class="govuk-template govuk-template--rebranded">`, body `govuk-template__body govuk-frontend-supported` in `layout.twig`.
- Components in use: `govuk-header` (full SVG), `govuk-footer`, `govuk-notification-banner` (cookie-success), `govuk-details` (accordion-layout, type/index), `govuk-grid-row`/`govuk-grid-column-*`/`govuk-heading-m` (cookies/index).
- Fonts copied from `govuk-frontend/dist/govuk/assets/fonts` (`build-css.sh` ~line 54).

### 1.4 Cypress impact

Only **two** legacy selectors found:

- `div.form-group-error` — `cypress/e2e/common/i_can_find.js`
- `.error-summary-heading` — `cypress/e2e/common/after_checks.js`

Most assertions use `data-cy` attributes, so Cypress churn is minimal.

---

## 2. Gap analysis

| Area | Old | New v5 equivalent | Effort |
|---|---|---|---|
| Page template wrapper | `govuk_template_mustache` CSS | `govuk-template` / `govuk-template__body` (already present) | S |
| Header / Footer | already migrated | `govuk-header` / `govuk-footer` | S (cleanup only) |
| Cookie banner | bespoke `global-cookie-message__*` | `govuk-cookie-banner` | M |
| Buttons | `.button`, `--secondary`, `--warning`, `.button-link`, `.button-input-to-link` | `govuk-button`, `--secondary`, `--warning`, `govuk-button-group` | L (20+ files) |
| Headings | `.heading-xlarge/large/medium/small` | `govuk-heading-xl/l/m/s` | M (~30 hits) |
| Form group/label/input/hint | `.form-group`, `.form-control`, `.form-label`, `.form-hint` | `govuk-form-group`, `govuk-input`, `govuk-label`, `govuk-hint` | L (every form view + `macros.twig`) |
| Date input | `.form-date`, `.form-group-day/month/year` | `govuk-date-input` | M |
| Radios / checkboxes | `.block-label`, `.form-group-checkbox` | `govuk-radios` / `govuk-checkboxes` | M |
| Error summary / message | `.error-summary`, `.error-summary-list`, `.error-message`, `.form-group-error` | `govuk-error-summary`, `govuk-error-message`, `govuk-form-group--error` | M (+ Cypress 2 selectors) |
| Typography helpers | `.lede`, `.text`, `.notice` | `govuk-body-l`, `govuk-body`, `govuk-warning-text` / `govuk-inset-text` | S–M |
| Grid | mixed legacy + v5 | normalise to `govuk-grid-row` / `govuk-grid-column-*` | S |
| Show/hide JS | `govuk_frontend_toolkit/.../show-hide-content.js` | v5 conditional reveals via `govuk-radios__conditional` + `data-aria-controls` (auto-init) | M (template restructure) |
| Bespoke pattern SCSS | toolkit mixins/vars | rewrite using `govuk-font`, `govuk-typography-responsive`, `govuk-colour()`, `govuk-spacing()`, `govuk-media-query()` | L |

---

## 3. Draft migration plan

The approach mirrors the **mezzio migration**: convert templates first (one area at a time), keep both stacks working in parallel, then strip legacy build steps and packages once nothing references them.

> **Guiding rule:** at the end of every PR the app must still build and render. Legacy CSS stays loaded until the final cleanup phase, so any not-yet-migrated page continues to look correct.

### Phase 1 — Migrate classes & layout (the bulk of the work)

Convert templates from legacy class names to `govuk-*` v5 classes/components. Legacy CSS remains in place as a safety net.

**1a. Shared templates first** (unblocks every flow):

- `application/macros.twig` — error summary, form group/label/input/hint, buttons.
- `layout/partials/form-element-errors.twig` — `govuk-error-message`.
- `layout/layout.twig` + `layout/accordion-layout.twig` — headings, cookie banner → `govuk-cookie-banner`.
- `layout/partials/timeout.twig`, `application/general/stats.html.twig`.

**1b. Per-area template PRs** — one PR per area, ordered by traffic / risk:

1. `application/general` (auth, register, forgot-password, feedback, cookies, stats)
2. `authenticated/about-you`, `change-email-address`, `change-password`, `dashboard`, `delete`
3. LPA flows under `authenticated/lpa/` — one PR each:
   `type`, `donor`, `when-lpa-starts`, `life-sustaining`, `primary-attorney`, `how-primary-attorneys-make-decision`, `replacement-attorney`, `when-replacement-attorney-step-in`, `how-replacement-attorneys-make-decision`, `certificate-provider`, `people-to-notify`, `instructions`, `applicant`, `correspondent`, `who-are-you`, `repeat-application`, `fee-reduction`, `checkout`, `complete`, `summary`, `status`, `date-check`, `reuse-details`.

Per-PR checklist:

- Swap legacy classes for `govuk-*` equivalents.
- Replace `show-hide-content` markup with `govuk-radios__conditional` + `data-aria-controls`.
- Run the page in the browser; run the matching Cypress flow.

### Phase 2 — Cypress + bespoke SCSS catch-up

Once the templates above are migrated:

- Update the two Cypress selectors: `div.form-group-error` → `.govuk-form-group--error`; `.error-summary-heading` → `.govuk-error-summary__title`. (Also: `.error-message` → `.govuk-error-message` in `cypress/e2e/common/date_check.js`.)
  > **Follow-up (separate ticket):** these are still class-based and therefore coupled to GOV.UK Frontend's CSS naming. A subsequent PR should add stable `data-cy` (or `data-form-errors`-style) hooks to the relevant templates (`layout/partials/form-element-errors.twig`, `application/macros.twig`, `assets/js/lpa/templates/alert.withinForm.html`) and rewrite the Cypress assertions to use them, so future framework upgrades don't break tests for purely visual reasons.
- Rewrite bespoke partials in `assets/sass/patterns/*` and `assets/sass/extensions/*` that still use toolkit mixins/vars (`@include media`, `core-*`, `bold-*`, `button()`, `em()`, `$grey-*`, `$gutter`, etc.) using v5 equivalents (`govuk-font`, `govuk-typography-responsive`, `govuk-colour()`, `govuk-spacing()`, `govuk-media-query()`).
- Rework `print.scss` to v5 selectors.

### Phase 3 — Remove legacy build steps, SCSS imports, and packages

Only run this once Phases 1–2 are complete and nothing references the old stack.

- `layout.twig`: drop `<link>`s for `govuk-template.css` and `govuk-template-print.css`.
- `build-css.sh`: drop the `cp govuk_template_mustache/...` step and the two legacy `--load-path` flags.
- `build.js`: drop the `govuk_frontend_toolkit/.../show-hide-content.js` concat.
- `assets/sass/application.scss`: delete `@import "govuk-elements";`.
- `assets/sass/settings/_variables.scss`: delete `$govuk-compatibility-govukfrontendtoolkit` and `$govuk-compatibility-govukelements`.
- `npm uninstall govuk_frontend_toolkit govuk_template_mustache govuk-elements-sass`.
- Confirm `/assets/v2/` font/image paths still resolve.

---
