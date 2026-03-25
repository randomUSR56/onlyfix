# Help – Administrator Guide

The administrator role has full access to all system features. This guide covers admin-specific capabilities and responsibilities.

---

## Dashboard

The admin dashboard shows the overall state of the system:

- **Total users** – number of registered customers
- **Total mechanics** – number of active mechanics
- **Total tickets** – all tickets in the system
- **Open / In Progress / Completed** – ticket breakdown by status

The page also displays the most recent tickets and newly registered users.

---

## Managing Users

### Listing users

1. Navigate to **Users**.
2. You will see all registered users with name, email, role, and ticket count.
3. Search and filter by name or email.

### Creating a new user

1. Click **Add User**.
2. Enter name, email, password, and role (user / mechanic / admin).
3. Click **Create**.

### Editing a user

1. Click on the user's row.
2. Choose **Edit**.
3. Update the details – including the role.
4. Save.

### Deleting a user

Deletion is permanent. Related data (cars, tickets) remains in the system but the account is removed. Confirm the action in the confirmation dialog.

---

## Managing Tickets

As an administrator you can fully manage every ticket:

- **Accept / Start Work / Complete** – same actions as mechanics
- **Assign mechanic** – manually assign a mechanic when editing a ticket
- **Delete ticket** – permanently removes a ticket from the system

### Assigning a mechanic to a ticket

1. Open the ticket detail view.
2. Click **Edit**.
3. In the **Customer & Mechanic** section, select the desired mechanic.
4. Save.

---

## Problem Catalog

Administrators have full permissions: create, edit, and **delete** problems.

### Deleting a problem

1. Open the problem list.
2. Click the item you want to delete.
3. Choose **Delete** and confirm.

> **Note:** Deleting a problem removes it from future ticket creation but does not affect existing tickets that already include it.

---

## Statistics and Reports

The **Statistics** section provides detailed analysis:

- **Ticket statistics**: status breakdown, priority distribution, time trends
- **Problem statistics**: most common faults, breakdown by category

These insights help identify recurring issues and resource requirements.

---

## Mechanics List

The **Mechanics** menu shows all active mechanics and their work history.

---

## Tips and FAQ

**How do I assign a ticket to a specific mechanic?**
On the ticket edit page, select the mechanic from the dropdown and save.

**Can a deleted user be restored?**
No. Deletion is permanent – create a new account if needed.

**Why can't I delete a problem?**
A problem linked to active tickets cannot be deleted. Close the related tickets first.

**How do I monitor overall system performance?**
The dashboard and the Statistics pages together provide a complete picture of system health.
