# JobJoker 

I made some additions which makes the awesome JobJoker more suitable for scraperjobs:

* easier restarting of same job 
* ability to define job id in userinterface (if empty it will generate the hash)
* which means more readable id's for restcalls (and shown in Job overview) 
* improved logs (contains datetime)
* lazy loading of status (many times job stayed on 'idle' in userinterface, while already 'active') 
* fullscreen UI (added Ext viewport so sticky footer resizes automatically)
* job overview automatically refreshes every 5 secs

Issues: 

* it seems that the webauthentication for the webinterface breaks the rest-calls done in run.php

For the original docs see: https:// github.com/diogok/JobJoker
