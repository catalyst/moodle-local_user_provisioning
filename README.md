# SCIM 2.0 compliant user provisioning plugin for Moodle

Plugin has dependencies in local/oauth and local/catalystlms

local/oauth - Required to create a new client with client_credentials authorization used to generate token.

Plugin is used to Create, Update and Suspend users for a given Organisation based on the Bearer token.

This plugin makes use of 3rd party AltoRouter class for routing the SCIM endpoints (https://github.com/dannyvankooten/AltoRouter)

## How to use

### Add Client identifier:

1. Go to *Site Administration > Server > OAuth provider settings*

2. Click *Add new client*

3. Fill in the form. Your Client Identifier and Client Secret (which will be given later) will be used for you to authenticate. The Redirect URL is not used for this plugin as we using `client_credentials` as the authorization grant type.

* Client identifier: must be same as set in the Organisation short name for the Portal organisation.
e.g. If we are creating a Client secret key for Organisation `MSI Reproductive Choices`, the Client identifier will be `portal-61`
* Redirect URL: is not in use and can be set to N/A
* Grant Types: should be set to `client_credentials`
* Scope: should be set to `SCIMv2`
* User ID: should be set to `0`

### Generate a Long-lived bearer token:


### Valid end points for this API are:

#### `/token` - To validate / generate a token using the Client identifier and Client secret.

curl https://{siteurl}/local/user_provisioning/scim/rest.php/v2/token -d 'grant_type=client_credentials&client_id=<CLIENT_ID>&client_secret=<CLIENT_SECRET>'

Where CLIENT_ID is the Client identifier and the CLIENT_SECRET is the key generated when adding a new client as part of `Add Client identifier`

####  `/ServiceProviderConfig` - To view the SCIM service provider config.

curl --location --request GET 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/ServiceProviderConfig' \
--header 'Authorization: Bearer {BEARER_TOKEN}'

####  /Schemas` - To view the SCIM schemas that are used. Append following to view the individual User, Enterprise and Custom extention schema:

* urn:ietf:params:scim:schemas:core:2.0:User
* urn:ietf:params:scim:schemas:extension:enterprise:2.0:User
* urn:ietf:params:scim:schemas:extension:CustomExtensionName:2.0:User

curl --location --request GET 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Schemas' \
--header 'Authorization: Bearer {BEARER_TOKEN}'

#### `/Users?filters={SEARCH_CRITERIA}` - GET request to fetch a given user's data

curl --location --request POST 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users?filter={SEARCH_CRITERIA}' \
--header 'Authorization: Bearer {BEARER_TOKEN}'

Supported SEARCH_CRITERIA fields are:
userName, name.familyName, name.givenName and emails.

Supported SCIM filter types are:
eq (equal) Ee.g. filter=userName+eq+"joeb@job.com"
neq (Not Equal) Ee.g. filter=userName+neq+"joeb@job.com"
co (Contains) e.g. filter=userName+co+"joeb@job.com"
sw (Starts With) e.g. filter=userName+sw+"joeb@job.com"
ew (Ends With) e.g. filter=userName+ew+"joeb@job.com"

#### `/Users/{USER_ID}` - GET request to fetch a given user's data

curl --location --request POST 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users' \
--header 'Authorization: Bearer {BEARER_TOKEN}'

#### `/Users` - POST request to create / provision a new user.

curl --location --request POST 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users' \
--header 'Authorization: Bearer {BEARER_TOKEN}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "schemas": [
        "urn:ietf:params:scim:schemas:core:2.0:User",
        "urn:ietf:params:scim:schemas:extension:enterprise:2.0:User"
    ],
    "userName": "catalyst1@testmsi365.onmicrosoft.com",
    "active": true,
    "displayName": "Catalyst One",
    "emails": [
        {
            "primary": true,
            "type": "work",
            "value": "bitsprintuk+catalyst1@gmail.com"
        }
    ],
    "meta": {
        "resourceType": "User"
    },
    "name": {
        "familyName": "One",
        "givenName": "Catalyst"
    },
}'

#### `/Users` - PATCH request to update given user's data.

curl --location --request PATCH 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users' \
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
