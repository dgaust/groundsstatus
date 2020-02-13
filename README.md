# groundsstatus

Various pieces of code to parse information from the Wollongong Sportsgrounds website and make it available as a Wordpress Widget.

Wordpress Widget - cawleyparkstatus.php

Provides a widget to display the status of a chosen sportsground. This widget will work without any other components as it pulls the response directly from the Russell Vale Football club's site.

File parser - grounds_nobs4.py

Code to pull information from the website and covert it to json. Also provides the ability to upload the resulting file to an FTP server of your choice.

Simple search function - sensor.php

Built to provide a restful status to Home Assistant. This returns the details of a single park by adding ?park=[chosen park] to the end of the URL.
