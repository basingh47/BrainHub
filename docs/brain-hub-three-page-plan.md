# Brain Hub --- Application Idea

## 1. Project Overview

**Brain Hub** is a simple personal productivity application built with
Laravel 12, PHP, MySQL, Blade, and Bootstrap 5.

Its purpose is to help you organise your day in one place by managing
daily tasks and regular routines, then seeing a quick overview on a
dashboard.

### Main goals

-   Keep track of tasks that need to be completed.
-   Organise regular morning and evening routines.
-   See today's progress at a glance.
-   Keep a history of completed tasks.
-   Keep the application simple and easy to maintain.

## 2. Application Scope: Three Pages

The application has exactly three main pages:

1.  Dashboard
2.  Daily Tasks
3.  Daily Routine

------------------------------------------------------------------------

## 3. Page One --- Dashboard

**Purpose:** Give a quick overview of the day.

### Suggested content

-   Today's date and a greeting.
-   Total tasks due today.
-   Completed tasks count.
-   Pending tasks count.
-   Overdue tasks count.
-   Today's routine completion progress.
-   A short list of today's tasks.
-   Quick links to add a task or open the routine page.

### Example layout

``` text
------------------------------------------------
Brain Hub                         Today's Date
------------------------------------------------
Good morning!

[ Total Tasks ] [ Completed ] [ Pending ] [ Overdue ]

Today's Tasks
- Finish project ticket       Pending
- Study Laravel relationships In Progress
- Review PHP concepts         Completed

Today's Routine
Morning routine: 3 / 4 completed
Evening routine: Not started
------------------------------------------------
```

### Dashboard behaviour

-   Show task counts based on their due dates and statuses.
-   Show overdue tasks separately.
-   Show routine progress for today.
-   Keep this page focused on information; task editing happens on the
    Daily Tasks page.

------------------------------------------------------------------------

## 4. Page Two --- Daily Tasks

**Purpose:** Create, organise, update, complete, and review individual
tasks.

This is the first page to build.

### Task fields

  Field          Description
  -------------- ------------------------------------
  Title          Required task name
  Description    Optional details
  Category       Office, Learning, or Personal
  Priority       High, Medium, or Low
  Due date       Date the task should be done
  Due time       Optional time
  Status         Pending, In Progress, or Completed
  Completed at   Date and time it was completed

### Main features

1.  Add a task.
2.  View all tasks.
3.  Edit a task.
4.  Delete a task.
5.  Mark a task as completed.
6.  Change a task's status.
7.  Filter tasks by date, status, and category.
8.  View overdue tasks.
9.  Manually carry a task forward to another date.

### Manual carry-forward rule

Use **manual carry forward**, not automatic duplication:

-   If a task is not completed by its due date, it remains assigned to
    its original date and becomes overdue.
-   You decide whether to move it to a future date.
-   Moving a task updates its existing `due_date`; it does not create a
    duplicate.
-   Completed tasks remain in the database as history.
-   A completed task cannot be carried forward.

### Suggested task-list layout

``` text
Daily Tasks                              [+ Add Task]

Date: [Select date]  Status: [All]  Category: [All]

Task                  Priority   Due time   Status
----------------------------------------------------
Finish project ticket High       11:00 AM   Pending
Study Laravel         Medium      6:00 PM   In Progress
Read PHP notes        Low         --        Completed
```

### Suggested task actions

-   **Edit:** Open the edit form.
-   **Complete:** Mark the task completed.
-   **Carry forward:** Select a future date and update the task.
-   **Delete:** Delete the task after confirmation.

### Recommended first implementation

Build the following in order:

1.  Create the `DailyTask` model and migration.
2.  Create the resource controller.
3.  Register the resource routes.
4.  Build the task list page.
5.  Build the create-task form.
6.  Build the edit-task form.
7.  Add complete and delete actions.
8.  Add date, status, and category filters.
9.  Add manual carry forward.
10. Test validation and edge cases.

------------------------------------------------------------------------

## 5. Page Three --- Daily Routine

**Purpose:** Track recurring activities that are part of your normal
day.

Unlike Daily Tasks, routines are repeated activities, such as exercise,
reading, planning, or reviewing the day.

### Example routines

**Morning routine** - Wake up on time. - Exercise. - Plan the day. -
Read or study.

**Evening routine** - Review completed tasks. - Study or practise a
skill. - Prepare tomorrow's plan. - Sleep on time.

### Suggested features

-   Create a routine item.
-   Edit or delete a routine item.
-   Group items into Morning and Evening.
-   Set an optional target time.
-   Mark an item complete for the current day.
-   Reset daily completion state for the next day while preserving
    routine definitions.
-   Display today's routine completion progress.

### Important data-design idea

Keep the recurring routine definition separate from its daily completion
history.

For example:

-   `routines` stores the reusable routine item, such as "Exercise".
-   `routine_completions` stores whether "Exercise" was completed on a
    particular date.

This avoids losing previous days' completion history when a new day
begins.

------------------------------------------------------------------------

## 6. Suggested Navigation

``` text
Brain Hub
|
|-- Dashboard
|
|-- Daily Tasks
|   |-- Task List
|   |-- Add Task
|   |-- Edit Task
|   |-- Task Details (optional)
|
|-- Daily Routine
    |-- Morning Routine
    |-- Evening Routine
    |-- Manage Routine Items
```

The three main navigation items remain Dashboard, Daily Tasks, and Daily
Routine. Add/edit forms can be part of the relevant page rather than
extra main navigation pages.

## 7. Suggested Database Tables

  -----------------------------------------------------------------------
  Table                               Purpose
  ----------------------------------- -----------------------------------
  `users`                             User accounts, if authentication is
                                      enabled

  `daily_tasks`                       Individual tasks and their status

  `routines`                          Reusable morning/evening routine
                                      items

  `routine_completions`               Completion history for each routine
                                      item and date

  `migrations`                        Laravel migration history
  -----------------------------------------------------------------------

For a personal single-user version, keep the initial database design
simple. If you later support multiple users, associate each user's tasks
and routines with their user ID and enforce ownership checks.

## 8. Suggested Daily Tasks Table Structure

  Column           Suggested type        Notes
  ---------------- --------------------- ----------------------------
  `id`             bigint                Primary key
  `title`          string                Required
  `description`    text, nullable        Optional
  `category`       string, nullable      Office, Learning, Personal
  `priority`       string                Default: medium
  `due_date`       date                  Required
  `due_time`       time, nullable        Optional
  `status`         string                Default: pending
  `completed_at`   timestamp, nullable   Set when completed
  `created_at`     timestamp             Laravel timestamp
  `updated_at`     timestamp             Laravel timestamp

Add an index for commonly filtered columns, such as `due_date` and
`status`.

## 9. Technology Stack

  -----------------------------------------------------------------------
  Layer                               Technology
  ----------------------------------- -----------------------------------
  Backend                             Laravel 12

  Language                            PHP

  Database                            MySQL

  Frontend                            Blade

  CSS framework                       Bootstrap 5

  Local development                   PHP, Composer, MySQL

  Version control                     Git and GitHub

  Hosting idea                        Render for Laravel and Aiven for
                                      MySQL, subject to current free-plan
                                      limits
  -----------------------------------------------------------------------

Do not store database passwords or other secrets in source code. Use
environment variables in `.env` locally and the hosting provider's
environment settings in production.

## 10. Development Roadmap

Build the application in this order:

### Phase 1 --- Daily Tasks

-   [ ] Create the Laravel project and configure MySQL.
-   [ ] Create the `DailyTask` model and migration.
-   [ ] Create `DailyTaskController` as a resource controller.
-   [ ] Register
    `Route::resource('daily-tasks', DailyTaskController::class)`.
-   [ ] Build the task list.
-   [ ] Build create and edit forms.
-   [ ] Implement complete, delete, and status updates.
-   [ ] Add filters and overdue-task handling.
-   [ ] Implement manual carry forward.
-   [ ] Test all task operations.

### Phase 2 --- Daily Routine

-   [ ] Create routine definitions.
-   [ ] Group routines into morning and evening.
-   [ ] Create daily completion records.
-   [ ] Mark routine items complete for a date.
-   [ ] Display routine progress.

### Phase 3 --- Dashboard

-   [ ] Display today's task summary.
-   [ ] Display overdue task count.
-   [ ] Display today's task list.
-   [ ] Display morning and evening routine progress.
-   [ ] Add navigation to the other two pages.

### Phase 4 --- Polish and Deployment

-   [ ] Add validation messages and confirmation prompts.
-   [ ] Check responsive layout on mobile and desktop.
-   [ ] Test empty states and date boundaries.
-   [ ] Configure production environment variables.
-   [ ] Deploy and verify the application.
-   [ ] Confirm database backups and hosting limits.

------------------------------------------------------------------------

## 11. Important Project Rules

-   Keep the application limited to the three main pages for the first
    version.
-   Finish Daily Tasks before starting Daily Routine.
-   Finish Daily Routine before building the Dashboard.
-   Do not automatically duplicate overdue tasks.
-   Preserve completed task history.
-   Keep controllers responsible for request handling and validation;
    keep database structure in migrations and model behaviour in models.
-   Use Laravel validation and CSRF protection.
-   If the application is accessible publicly, add authentication and
    ensure users can access only their own records.

## 12. Definition of a Successful First Version

The first version is ready when you can:

1.  Open Daily Tasks and see tasks for a selected date.
2.  Add, edit, and delete a task.
3.  Change a task's status and mark it completed.
4.  See overdue tasks without losing their original due dates.
5.  Manually move an unfinished task to a future date without creating a
    duplicate.
6.  Keep completed tasks available as history.

After these features work reliably, move on to Daily Routine and then
Dashboard.
