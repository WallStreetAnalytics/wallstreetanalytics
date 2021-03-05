# Security Policy

NOTE: This policy is more developer focused than end-user focused. This will change when there is a MVP and the application is available to users. 

Before contributing to this project please take some time to read through this. Coordination with the Security team is important to keeping our data and our users safe. 

## Reporting a Vulnerability

To report a vulnerability, message @JackDawsen in the Discord for further instructions regarding secure communications about the vulnerability.

Generally we follow the Google Project Zero reporting and disclosure timeline. Vulnerability reporters can choose to claim credit for their findings and we will attach their name as appropriate.

## Security Update Policy

SECURITY.md will be the primary document for security policy and it will be updated as necessary. Because the project is in its early design stages we aren't implementing security measures now. Instead we're documenting security requirements so that they can be implemented as the project gets off the ground.

## Secure Code Standards

This will be addressed as platforms, languages, and frameworks are decided. As development starts we consider the following general principles important:

1. Validate input.
2. Keep it simple.
3. Default deny.
4. Adhere to the principle of least privilege.
5. Sanitize data sent to other systems.
6. Only store data that you need. 
7. Personally identifiable information and other non-public data MUST be encrypted.

## Communications Security

All communications into and out of the platform and connections between components must be encrypted. External connections from the platform, such as from web browsers to our web servers, must use TLS. Internal connections, such as from web servers to database servers, must also be encrypted using appropriate configurations. The only exceptions are for external systems that don't support encryption and sensitive or non-public data will not be sent to or requested from those systems.

## Data Storage Security

All stored user data must be encrypted. Financial data of a non-public nature must be encrypted. Credentials, access tokens, and other user-specific access devices must be encrypted using a salt that is different between users.  
