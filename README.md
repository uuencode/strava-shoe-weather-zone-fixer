# strava-shoe-weather-zone-fixer
A Strava app to manipulate the latest activities: select shoes, change the name and recreate the description with the following info: weather + MAF zone + HR zones. 

Strava is a website for tracking physical exercise with social network features used for cycling and running using Global Positioning System data.  
Strava URL: https://www.strava.com

## Requirements

Any web host with PHP, `php_sqlite3` extension and PHP Curl support enabled.

## Installation

1. Login to Strava
2. Register a Strava app at https://www.strava.com/settings/api
3. Upload the files to your host and open config.php with a text editor:
  - Paste Strava ClientID and Strava ClientSecret
  - Replace `$secret_salt_hashing` with a random string
  - Create a new folder with a random name e.g. `ABC123` and set `$store_gpx_files_dir = 'ABC123';` instead of 'DATA'
  - CHMOD `ABC123` to 777

## How it works

When users visit the URL where your app is deployed, they are redirected to the Strava website to authorize the app to edit their activities and read their shoe IDs. After a successful authorization, users get a personalized URL. Visit the URL to quickly add shoes, edit activity name and description and automatically add weather info, HR Zones and MAF zone to the description.

## License
MIT
