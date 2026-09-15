# Setup Instructions for Birthday Reminder Task (Windows)

The birthday reminder feature uses a PHP CLI script (`utils/birthday_reminder.php`) to query for members with birthdays today and email the admin/staff team. 

To automate this, you should set up a **Windows Scheduled Task** to run the script once a day (e.g., at 8:00 AM).

## Prerequisites

1.  **XAMPP PHP:** Make sure you know the path to your PHP executable. Usually, it's `C:\xampp\php\php.exe`.
2.  **Script Path:** The script is located at `C:\xampp\htdocs\V3\utils\birthday_reminder.php`.
3.  **SMTP Setup:** Ensure your `.env` file in the project root has the correct `MAIL_*` settings (which it currently does).

## Steps to create the Scheduled Task

1.  **Open Task Scheduler:**
    *   Press the `Windows Key`, type **Task Scheduler**, and open the application.
2.  **Create Basic Task:**
    *   In the right-hand "Actions" pane, click **Create Basic Task...**
3.  **Name and Description:**
    *   **Name:** `Church System - Daily Birthday Reminder`
    *   **Description:** Runs the daily script to check for birthdays and send email notifications.
    *   Click **Next**.
4.  **Trigger:**
    *   Select **Daily**.
    *   Click **Next**.
5.  **Schedule:**
    *   Set the start date to today, and the time to **08:00:00 AM** (or whatever time you prefer the emails to go out).
    *   Recur every: **1 days**.
    *   Click **Next**.
6.  **Action:**
    *   Select **Start a program**.
    *   Click **Next**.
7.  **Program/Script Settings:**
    *   **Program/script:** `"C:\xampp\php\php.exe"` (include the quotes).
    *   **Add arguments (optional):** `-f "C:\xampp\htdocs\V3\utils\birthday_reminder.php"`
    *   **Start in (optional):** `"C:\xampp\htdocs\V3\utils"`
    *   Click **Next**.
8.  **Finish:**
    *   Review the settings.
    *   Click **Finish**.

## Testing the Task

To test if it works right away:
1.  In Task Scheduler, click on **Task Scheduler Library** on the left pane.
2.  Find `Church System - Daily Birthday Reminder` in the middle list.
3.  Right-click it and select **Run**.
4.  You can verify it ran by checking the log file created at `C:\xampp\htdocs\V3\storage\logs\birthday_reminder.log`. If someone has a birthday today, an email should have been sent!
