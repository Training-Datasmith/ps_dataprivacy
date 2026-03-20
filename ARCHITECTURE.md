# Architecture: ps_dataprivacy

## Purpose

A PrestaShop module that adds a GDPR-compliant data privacy notice and consent checkbox
to forms where personal data is collected (account creation, contact form, newsletter
subscription). Configurable message text per language.

## Directory Structure

```
ps_dataprivacy.php   # Main module class; hooks into relevant form display events
views/templates/      # Smarty/Twig templates for privacy notice blocks
translations/         # Translation files
tests/                # PHPStan and unit tests
```

## Key Design Decisions

The module hooks into `displayGDPRConsent` (or similar hooks on the targeted forms) and
renders a configurable notice with a mandatory checkbox. Validation is enforced at the
controller level. Message text is stored per-language in `Configuration`.

## Extension Points

Configure the privacy policy link and notice text per language in the module back-office
settings. Override templates in theme for custom styling.
