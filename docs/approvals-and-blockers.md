# Outstanding approvals and production blockers

Nothing here is decided. Do not convert any item into a policy without the
named approval. Updated 24 October 2026.

## Inputs still needed from the project owner

| Item | Needed for | Status |
| --- | --- | --- |
| Ministry sitemap | Confirming navigation and child pages | Not received (the research report refers to it) |
| Coat of arms artwork | Header identity | Received 24 October 2026 (512px PNG seal); in use |
| Coat of arms vector master and usage rules (clear space, minimum size) | Print-quality use, National Symbols page | Not received |
| Approved MFA&IC wordmark | Header identity | Not received |
| Official dark-blue colour specification | Replacing the temporary token | Not received |
| Official flag artwork or technical colour standard | Any use of national colours | Not received; research found none published |
| Flag ribbon picture | Header, behind the Ministry name | Received 25 October 2026 from the project owner, replacing an earlier stylised flag picture; in use. Its rights and official approval are not yet confirmed |
| Authorized photographs and approved map | Replacing image placeholders | Not received |

## Government decisions (from the specification)

| Decision | Blocks |
| --- | --- |
| Official diplomatic jurisdiction directory and its owner | Publishing mission jurisdictions; any routing |
| Approved government content, statistics and national maps | Publishing real content |
| Population figure approved by the National Bureau of Statistics | Quick facts (research shows 8.26m 2008 census, 16.4m 2026 NBS-linked estimate, 12.19m World Bank 2025) |
| Editorial decision on post-2013 conflict and the 2018 agreement | History and Government page |
| Dated, verified wording on the current transition and elections | Any current-context notice |
| Wording on official and national languages | People and Culture page (sources conflict; research recommends the constitutional wording) |
| Administrative structure wording (states and administrative areas) | Basic Information page |
| Joint editorial approval arrangement | Final workflow configuration |
| Legal basis and privacy policy | Any personal data, contact forms, analytics |
| Approved cloud provider and hosting region | Staging and production hosting |
| Arabic rollout date and variety | Arabic content |
| Final security architecture approval, independent security and accessibility testing | Launch |
| Deployment authorization | Launch |
| Registration architecture for real use (prototype follows ADR 0010) | Any real registration |
| Registration eligibility, minors, field purposes and required fields | Real registration form |
| Country and territory list for the form | Real registration form (prototype uses the ISO 3166 list as a placeholder) |
| Duplicate handling, verification, correction, retention and export rules | Real registration records |
| Confirmation email: sender, provider and wording | Sending any email (the prototype sends none) |
| Help and enquiry requests: who responds, response times, out-of-hours cover, email or SMS provider | Any real help or enquiry service (the prototype sends nothing) |
| Whether to ask the browser for the person's location | Automatic location on the help form (the prototype uses typed-in location only) |

## Technical blockers before any production use

### Registration service
- Replace environment-variable keys with a key management service (ADR 0014).
- Run the registry on its own PostgreSQL instance with separate network rules.
- Use separate database roles per scope so row-level security fails closed (ADR 0016).
- Decide on central sign-on, add security keys and account recovery (ADR 0017).
- Approve the role matrix, the reveal rule and who owns the jurisdiction directory.
- Remove the synthetic-only guards only through a reviewed change after approval (ADR 0015).
- Approved privacy notice in place of the draft placeholder.

### Website

- Stop running `drush mofa:placeholder-content` and remove placeholder items once real content editing starts.
- Make the home page introduction editable once approved text exists (ADR 0012).

- Remove or disable `mofa_styleguide` and its anonymous permission; change the front page.
- Add multifactor login for all staff accounts.
- Add Config Split (or equivalent) so dev-only modules never reach production.
- Add Metatag and remove the global `noindex` only when launch is authorized.
- Set `DRUPAL_TRUSTED_HOSTS`, HTTPS, security headers and a web application firewall.
- Set up encrypted backups with a tested restore, monitoring and alerting.
- Configure private file storage if any non-public documents are added.
- Enable update status notifications and a patching routine.
