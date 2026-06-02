# SCIM 2.0 compliant user provisioning plugin for Moodle

This plugin makes use of 3rd party AltoRouter class for routing the SCIM endpoints (https://github.com/dannyvankooten/AltoRouter)

## How to use

### Manage client credentials

1. Go to *Site administration > Plugins > Local plugins > SCIM User Provisioning > Clients*.
2. Click *Add client*.
3. Fill in the form.

Notes:

* `Client identifier` should match your intended tenant/idnumber usage.
* Redirect URL is not used for this plugin when using `client_credentials`.
* Grant Types should include `client_credentials`.
* Scope should be set to `SCIMv2`.
* User ID is typically `0`.

Security behavior:

* Client token material is stored hashed at rest (not retrievable later).
* The plain value is shown once on create/regenerate with a copy button.
* If lost, use *Regenerate token* for that client.

### SCIM base URL
`https://{siteurl}/local/user_provisioning/scim/rest.php/v2`

### API reference

Schema definitions and endpoint contracts are documented in Swagger/OpenAPI and should be treated as the source of truth:

* `docs/scim.php` (Swagger UI)
* `docs/openapi.php` (OpenAPI spec)

Workplace note (title and job assignment):

* `title` is treated as a Workplace position name for job assignment.
* If `title` is provided, job prerequisites must resolve (framework config, position, department) or the API returns `400 invalidValue`.
* On create requests, user creation happens before Workplace job assignment. If job assignment fails, the user account may already exist.

### Extending custom SCIM fields

Custom fields are configured via admin setting:

* *Site administration > Plugins > Local plugins > SCIM User Provisioning > Settings*
* Field: `Custom SCIM fields`

In Moodle Workplace, you can also define tenant-specific custom fields under each tenant section in the same settings page:

* Field: `Tenant custom SCIM fields`

When tenant-specific fields are defined, requests authenticated for that tenant use global fields plus tenant fields.

One field per line using:

`shortname|Label|unique`

* `shortname` must use lowercase letters, numbers, or underscore only.
* `unique` is optional. Accepted values: `1`, `true`, `yes`, `y`, `unique`.

Example:

```text
pernr|PERNR|unique
midname|Mid Name
dutystcode|Duty Station Code
```

Notes:

* Use `unique` only for fields that must be globally unique (for example `pernr`).
* Missing Moodle profile fields are created when settings are saved.
* Fields defined in this registry are handled for schema output, request mapping, response mapping, persistence, and uniqueness validation.

### Generate a Long-lived bearer token:


### Valid end points for this API are:

#### `/token` - To validate / generate a token using the Client identifier and Client secret.

curl https://{siteurl}/local/user_provisioning/scim/rest.php/v2/token -d 'grant_type=client_credentials&client_id=<CLIENT_ID>&client_secret=<CLIENT_SECRET>'

Where CLIENT_ID is the Client identifier and CLIENT_SECRET is the client token key value shown once when creating or regenerating a client. The OAuth request parameter name remains `client_secret`.

#### `/ServiceProviderConfig` - To view the SCIM service provider config.

curl --location --request GET 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/ServiceProviderConfig' \
--header 'Authorization: Bearer {BEARER_TOKEN}'

#### `/Schemas` - Schema endpoint

The schema endpoint is supported, but the authoritative schema definitions are documented in Swagger/OpenAPI.

#### `/Users?filters={SEARCH_CRITERIA}` - GET request to fetch a given user's data

curl --location --request GET 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users?filter={SEARCH_CRITERIA}' \
--header 'Authorization: Bearer {BEARER_TOKEN}'

Supported SEARCH_CRITERIA fields are:
userName, name.familyName, name.givenName and emails.

Supported SCIM filter types are:
eq (equal) Ee.g. filter=userName+eq+"joeb@job.com"
neq (Not Equal) Ee.g. filter=userName+neq+"joeb@job.com"
co (Contains) e.g. filter=userName+co+"joeb@job.com"
sw (Starts With) e.g. filter=userName+sw+"joeb@job.com"
ew (Ends With) e.g. filter=userName+ew+"joeb@job.com"

#### `/Users/{IDNUMBER}` - GET request to fetch a given user's data

curl --location --request GET 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users/{IDNUMBER}' \
--header 'Authorization: Bearer {BEARER_TOKEN}'

#### `/Users` - POST request to create / provision a new user.

curl --location --request POST 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users' \
--header 'Authorization: Bearer {BEARER_TOKEN}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "schemas": ["urn:ietf:params:scim:schemas:core:2.0:User", "urn:ietf:params:scim:schemas:extension:enterprise:2.0:User"],
    "externalId": "XXXXXXXX",
    "userName": "catalyst.admin@catalyst-eu.net",
    "active": true,
    "addresses": [{
        "primary": true,
        "type": "work",
        "country": "United Kingdom",
        "locality": "Brighton"
    }],
    "displayName": "Catalyst Admin",
    "emails": [{
        "primary": true,
        "type": "work",
        "value": "catalyst.admin@catalyst-eu.net"
    }],
    "name": {
        "familyName": "Catalyst",
        "givenName": "Admin"
    },
    "title": "Site Administrator",
    "urn:ietf:params:scim:schemas:extension:enterprise:2.0:User": {
        "department": "Internet Technologist",
        "manager": {
            "value": ""
        }
    },
    "urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User": {
        "auth": "saml2"
    }
}'

#### `/Users/{IDNUMBER}` - PATCH request to update given user's data.

curl --location --request PATCH 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users/{IDNUMBER}' \
--header 'Authorization: Bearer {BEARER_TOKEN}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "schemas": [
        "urn:ietf:params:scim:api:messages:2.0:PatchOp"
    ],
    "Operations": [
        {
            "op": "Add",
            "path": "displayName",
            "value": "Catalyst Admin"
        },
        {
            "op": "Add",
            "path": "emails[type eq \"work\"].value",
            "value": "bitsprintuk+catalyst@gmail.com"
        },
        {
            "op": "Replace",
            "path": "name.givenName",
            "value": "Admin"
        },
        {
            "op": "Replace",
            "path": "name.familyName",
            "value": "Catalyst"
        }
    ]
}'
