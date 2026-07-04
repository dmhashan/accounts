# Business Overview

Last reviewed: 2026-07-04

## Purpose

This document explains the product in business language. It is intended for owners, managers, support teams, product planners, and AI assistants that need to understand what the system does before discussing changes.

Do not add implementation details here. Use `docs/system-architecture.md` and `docs/technical-reference.md` for engineering reference material.

## Documentation Maintenance Rule

After every product, workflow, permission, report, integration, or operational change, the AI assistant must advise whether these documents need updates:

- `docs/business-overview.md` for business process, product scope, user roles, or policy changes.
- `docs/system-architecture.md` for application structure, deployment, tenancy, integration, or major flow changes.
- `docs/technical-reference.md` for implementation details that engineers or AI assistants need to change the system safely.

If a change affects documented behavior, update the relevant document in the same work session.

## Product Summary

The application is a multi-tenant management platform for gyms and fitness businesses. Each fitness business operates as an independent tenant with its own brand, staff users, members, payments, inventory, sales, events, workouts, reports, and member self-service portal.

The platform helps a fitness business run daily operations from one place:

- Manage members and temporary walk-in customers.
- Collect membership payments and wallet top-ups.
- Sell products and track inventory.
- Manage business accounts, expenses, transfers, and daily reconciliation.
- Assign workout programs to members.
- Run events and registrations.
- Send member notifications by available channels.
- Track employee attendance, documents, and pay sheets.
- Provide reports for daily operations, profit review, and member follow-up analysis.
- Support member self-service through a portal and mobile app.
- Integrate with biometric access devices for attendance and access control.

## Business Users

| User group | Main responsibility |
| --- | --- |
| Business owner | Oversees revenue, expenses, members, staff, settings, reports, and controls. |
| Administrator | Manages users, roles, configuration, members, products, payments, and daily operations. |
| Front desk or sales staff | Registers members, collects payments, handles sales, manages attendance, and supports walk-ins. |
| Accountant or finance staff | Reviews accounts, expenses, transfers, settlements, reports, and reconciliation. |
| Trainer or fitness staff | Manages workouts, exercises, assignments, member progress, and measurements. |
| Employee manager | Maintains employee profiles, attendance, documents, adjustments, and pay sheets. |
| Member | Uses the public portal or mobile app to view profile information, wallet history, workouts, events, and notifications. |
| System operator | Supports tenant setup, manual imports, notification delivery, and operational health. |

## Tenant Model

Each tenant represents one fitness business. Tenants are separated by their business identity and domain. A tenant has its own branding, member portal link, users, roles, members, financial records, inventory, events, forms, reports, configuration, and media.

Business expectations:

- A user, member, payment, sale, or report from one tenant must not be visible to another tenant.
- Tenant branding should be reflected in public-facing member experiences.
- Tenant settings can change how dates, times, colors, notifications, body measurements, and biometric devices work.
- Tenant data must remain isolated even when automated work, imports, reports, or integrations run.

## Core Business Capabilities

### Dashboard

The dashboard gives staff a quick view of current business status, including operational and financial summaries.

### Members

Member management covers full members and temporary members. Staff can create, update, verify, activate, deactivate, search, and export member contact information. Member profiles bring together personal details, payments, wallet activity, sales, attendance, workouts, documents, body measurements, and biometric status.

Business rules:

- A full member is normally verified and has a complete profile.
- A temporary member is used for walk-ins or incomplete profiles.
- Members may have a default payment plan and a tenant-specific member price.
- Members can choose or be configured for SMS, WhatsApp, and email contact preferences.

### Payments, Plans, Wallets, and Vouchers

The platform supports membership payments, payment plans, payment methods, wallet top-ups, wallet payments, and voucher redemption.

Business rules:

- Payment plans define membership duration and price.
- A membership payment may create a membership period with start and end dates.
- Wallet top-ups increase a member's balance.
- Wallet payments reduce a member's balance.
- Vouchers can be issued and redeemed against member wallets.
- Some payment methods may require settlement before funds are treated as fully confirmed.

### Sales and Inventory

The sales module supports product sales, paid and outstanding sales, stock deduction, and sale item cost tracking. Inventory supports products, variations, stock entries, display quantities, and audit history.

Business rules:

- Products can have variations.
- Stock entries track available and display quantities.
- Sales can be outstanding or paid.
- Paid sales contribute to business account activity.
- Sale edits and deletes must preserve stock and financial correctness.

### Accounting, Expenses, Transfers, and Reconciliation

The accounting area tracks business accounts, account transactions, expenses, transfers, payment settlements, and daily reconciliation.

Business rules:

- Financial actions should create account movements through the correct business workflow.
- Transfers move money between business accounts.
- Expenses reduce account balances and may have supporting documents.
- Daily reconciliation compares expected business activity against actual counted values.
- Reconciliation has separate rights for performing daily counts and managing configuration or history.

### Employees and Pay Sheets

Employee management supports employee records, documents, attendance, pay sheet generation, adjustments, and payment status.

Business rules:

- Employees can be active or inactive.
- Pay sheets are generated for a period.
- Adjustments may affect pay sheet totals.
- Paying a pay sheet can create or link financial activity.

### Workouts

The workout area supports exercise libraries, exercise variations, workout programs, program days, day exercises, extras, and member assignments.

Business rules:

- Trainers can create reusable workout programs.
- Programs can be assigned to members.
- Assigned programs may preserve a snapshot so later program edits do not unexpectedly change existing assignments.

### Events

Events support public and staff-managed registration, paid or free attendance, additional guests, attendance marking, and event detail pages.

Business rules:

- Events can be active or inactive.
- Registrations can belong to members or external guests.
- Registrations may have ticket fees, payment status, and attendance status.

### Forms

Forms allow the business to create reusable templates and collect member submissions. Submissions can be reviewed and exported as documents.

Business rules:

- Form templates can be active or inactive.
- Fields should remain stable when a template is edited.
- Submissions are linked to a member and the staff user who submitted them when applicable.
- Templates can support translations.

### Notifications

Notifications support bulk messaging and automated member communication such as welcome messages, payment receipts, expiry reminders, birthdays, and anniversaries where configured.

Business rules:

- Notification delivery should respect tenant configuration and member contact preferences.
- Failed external delivery must not block core business records.
- In-app notifications are available for member-facing experiences.

### Reports

Reporting supports daily summaries, real profit review, member analysis, statistics, customer reporting, and product reporting.

Business rules:

- Reports must match the tenant's financial and operational records.
- Daily summary reports can be generated, downloaded, and emailed.
- Profit reporting depends on revenue, payment deductions, inventory cost, and expense data.
- Member analysis reporting identifies inactive members, payment-missed members, outstanding balances, paid members who are not attending, attendance after payment expiry, regular members, and new members.
- Member analysis reporting shows payment expiry dates, days until or since expiry, last attendance dates, days since last attendance, and biometric sync status when a biometric device is configured.
- Member analysis can be filtered by plan, active status, verification status, temporary-member status, payment expiry, expiry days, last attendance, attendance days, attendance count, biometric status, and outstanding balance.
- Member analysis offers predefined quick filters for common follow-up groups such as active, inactive, biometric sync status, long-expired payments, and members absent for more than two months.
- Staff with member edit rights can select members from the member analysis report and confirm bulk activation or deactivation.

### Member Portal and Mobile App

Members can access a self-service experience using their phone number and one-time password. The portal and mobile app expose member-facing information such as profile, wallet transactions, workouts, events, and notifications.

Business rules:

- Member access must be limited to the verified member session.
- Public event pages can be viewed without staff login.
- Member activity can be logged for usage visibility.

### Biometric Access

The platform can integrate with biometric devices to sync members, record access attempts, mark attendance, and support access control based on membership validity.

Business rules:

- Device setup is tenant-specific.
- Access control can use payment validity and a grace period.
- Successful device authentication can mark member attendance.
- Failed authentication attempts are still useful operational records.

## Business Controls

The system uses role-based access to control which staff can view or manage each business area. Permission groups include dashboard, members, inventory, accounting, sales, employees, reports, settings, workout, activity logs, reconciliation, member portal, notifications, events, vouchers, forms, biometric settings, and system operations.

Business expectations:

- Staff should only see navigation and actions they are allowed to use.
- Sensitive actions such as deleting records, changing financial data, changing roles, running imports, or controlling devices require explicit permissions.
- Auditability matters for financial and member-related operations.

## Business Integrations

| Integration area | Business purpose |
| --- | --- |
| SMS | Send OTPs, member notifications, receipts, reminders, and campaign messages. |
| Email | Send reports, forms, password reset links, and member notifications. |
| Social login | Allow staff account login through supported identity providers. |
| Biometric devices | Sync members, record entry attempts, mark attendance, and support access control. |
| Media storage | Store tenant logos, member documents, employee documents, expense documents, photos, PDFs, and device images. |
| Legacy imports | Bring members, attendance, and payments from previous systems. |

## Quality Expectations

The product should be:

- Accurate for payments, balances, sales, inventory, and reports.
- Clear for daily staff workflows.
- Safe across tenants.
- Reliable when integrations fail or are delayed.
- Easy to use on desktop and mobile-sized screens.
- Auditable for important business changes.
- Maintainable for future AI-assisted development.

## Product Change Checklist

When planning or implementing a change, confirm:

- Which business users are affected?
- Which tenant-specific rules apply?
- Does it change money, membership validity, inventory, attendance, access, or reporting?
- Does it need new or changed permissions?
- Does it affect member-facing portal or mobile behavior?
- Does it affect notifications, documents, reports, or external integrations?
- Which of the three documentation files must be updated?
