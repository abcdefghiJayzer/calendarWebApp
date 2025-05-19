# SMTP Configuration for Event Reminders

To enable email notifications for event reminders, you need to configure SMTP in your `.env` file. Follow these steps:

## 1. Update .env File

Edit your `.env` file and update the following settings:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com 
MAIL_PORT=587
MAIL_USERNAME=josephmaniquis0@gmail.com
MAIL_PASSWORD=gjtw zovc onfn hxum
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=donotreply@philrice.com
MAIL_FROM_NAME="${APP_NAME}"
```

For Gmail users:
- You'll need to use an "App Password" instead of your regular password
- Enable 2-factor authentication on your Google account, then generate an app password specifically for this application

## 2. Test the Email Configuration

You can test your email configuration by running this Artisan command:

```
php artisan tinker
Mail::raw('Test email from Calendar App', function($message) { $message->to('your_test_email@example.com')->subject('Test Email'); });
```

## 3. Set Up the Scheduler

The email reminders are scheduled to run daily at 8:00 AM. To enable this functionality, set up Laravel's scheduler to run automatically by adding this Cron entry to your server:

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

For Windows users:
- Create a scheduled task that runs `php artisan schedule:run` every minute
- Or use the Task Scheduler to run the command at your preferred time

## 4. Manual Testing

You can manually test the event reminder functionality by running:

```
php artisan events:send-reminders
```

This will send reminder emails for all events scheduled for tomorrow. 