# strava-shoe-weather-zone-fixer

A Strava app to manipulate the latest activities: select shoes, change the name and recreate the description with the following info: weather + MAF zone + HR zones. Strava is a website for tracking physical exercise with social network features used for cycling and running using Global Positioning System data. Strava URL: https://www.strava.com

## Requirements

Any web host with PHP, `php_sqlite3` extension and PHP Curl support enabled.

## Installation

1. Login to Strava
2. Register a Strava app at https://www.strava.com/settings/api
3. Upload the files to the host and open config.php with a text editor:
  - Paste Strava ClientID and Strava ClientSecret
  - Replace `$secret_salt_hashing` with a random string
  - Create a new folder with a random name e.g. `ABC123` and set `$store_gpx_files_dir = 'ABC123';` instead of 'DATA'
  - CHMOD `ABC123` to 777

## How it works

When users visit the URL where the app is hosted, they are redirected to the Strava website to allow the app to edit their activities and read their shoe IDs. After successful authorization, users receive a personalized URL. After uploading an activity, users visit the URL to quickly add shoes, edit the activity name and description, and automatically add weather, HR zone, and MAF zone information to the description.

## License
MIT
