# Security Policy

NOTE: Currently, this policy is more for the developers rather the end users. This will however change when there is a MVP and the application is available to users. 

If you are going to contribute to this project, please take some time out to read through this. Follow the set rules and NO exceptions. 

We focus on the following principles:

1. Validate input.
2. Keep it simple.
3. Default deny.
4. Adhere to the principle of least privilege.
5. Sanitize data sent to other systems.
6. Only store data that you need. 
7. PII and financial data MUST be encrypted

## Reporting a Vulnerability

To report a vulnerability, create a new topic in the Issues. Be straight to the point and precise. 


Types of vulnerabilities to report:

* Code quality issues.
  * We need overall quality and standardized code.
* Improper error handling.
  * Errors must only provide the necessary information to the user and not provide reveal any sensitive information
* Improper/non-existent input validation.
  * Focus on SQL injection and Cross-site scripting (XSS). 
* Sensitive credentials stored as code/config.
* Directory traversal.

This is a completely crowd sourced project and thus, there is no bug bounty as of now. However, this may change in the future.

## Security Update policy

As the security policy get updated, the community will be notified. The exact channels to be used is still yet to be decided. As of now, SECURITY.md will be the main channel for security updates.

## Known security gaps & future enhancements

Since the project is in its early stages of design, there is much to do before we can implement security measures. Keep a look out for this section in the future. Suggestions are also welcome.

## Secure Coding policiy

### Proper DB Connection

Only keep DB connections for as long as necessary and close them promptly

## UX/UI Security

Design for security. 

### 1. Different actions must look different. 

An user might perform critical tasks that might have dramatic impact, for example chanigng his password. In this case, the UI must make it clear what it is user needs to do right now. Conformaition feedback is also necessary. 

Use of appropriate icons is necessary.

### 2. Show the user what will happen next

Predictability is important and users will appreciate it. For example, if a link is bringing the user to another website or application, let them know before hand. 

### 3. Show only necessary information.

Do not present too much information. This will help the UI stay clean and not overwhelm the user. Not to mention, any sensitive information such as passwords needs to be masked using *s.

### 4. Never trade seucurity over beauty.

I know, we all love a clean minimalistic website but it must not compromise security. 

## PII handling


## OPT IN & OPT OUT Policy

We must treat the users with utmost respect to their information. If the user are not willing to provide their information, they must be able to choose to out of any personal information collection. If they do choose to opt out, all previously stored information must be purged and no backup may be stored. 

If the user is comfortable with providing us their informaiton, they can choose to opt in. At no point shall we collect user information without clearly asking for the user's permission. 



 
