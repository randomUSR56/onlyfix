# OnlyFix MVP Spec

In the following sections, you will find the details of the OnlyFix MVP specifications. This document describes the main functions of the system as well as the technical stack.

## Content Table

- [Core Features](#core-features)
- [User Roles](#user-roles)
	- [Roles permissions](#roles-have-the-following-permissions)

## Core Features

The OnlyFix application will implement  **role-based user authentication**, ensuring that each user can only access functionalities appropriate to their role.

The system includes a  **ticketing system**, allowing users to submit service requests and mechanics to manage them efficiently. Tickets will have clearly defined states, which can be updated by mechanics and tracked by users.

The application will  **send email notifications**  automatically whenever the status of a ticket changes. Users will receive updates about their own tickets, and mechanics will be notified of new tickets submitted to the workshop.

The  **admin role**  provides full system oversight, including the ability to maintain and manage users and tickets. Admins can create, modify, or delete any ticket or user in the system and can manually send email notifications when necessary.

## User Roles

The system includes three roles: the general user (hereinafter referred to as the “user”), the mechanic, and the system administrator (hereinafter referred to as the “admin”).

The user and the mechanic can access the system only through a web browser, while the admin can only use the mobile application or the desktop application.

### Roles have the following permissions:

**User:**

- can submit a ticket (CRUD)
	- an email should be automatically sent to all the mechanics at the workshop
- only sees their own tickets
- can save their own car(s) (CRUD)

**Mechanic:**

- can browse tickets
- can work on tickets
- can update the state of tickets
	- an email should be automatically sent to the user

**Admin:**

- can create, modify, or delete any tickets on both ends (CRUD)
- can create, modify, or delete any users (CRUD)
- can send emails manually
