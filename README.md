# JobJoker 

I made some additions which makes the awesome JobJoker more suitable for scraperjobs:

* easier restarting of same job 
* ability to define job id in userinterface (if empty it will generate the hash)
* which means more readable id's for restcalls (and shown in Job overview) 
* improved logs (contains datetime and also shows php errors)
* lazy loading of status (many times job stayed on 'idle' in userinterface, while already 'active') 
* fullscreen UI (added Ext viewport so sticky footer resizes automatically)
* job overview automatically refreshes every 5 secs, removed reload-button
* receive errormail when worker triggered error or uncaught exception (defined in config)
* added extra restcall: PUT /jobs/job-id/parameters
* therefore you can now edit/validate the json parameters using the webinterface or any other REST-capable device
* allowed characters for id are now: [A-Za-z0-9_-]
* validation of those characters in the webinterface
* added 3 types of scheduling when adding job: none, crontab, repeat (with optional sleep)
* introduced insertion of json parameter-template when added (can be added later using the parameterbutton)

### Why JobJoker?

Well, there are many worker/job management packages for php.
However I did find any which had a REST api & management console & simple to extend.
Also, I've looked into gearmand but I prefer a more lightweight approach (gearmand is a heavyweight).

### Issues: 

it seems that the webauthentication for the webinterface breaks the rest-calls done in run.php.
This could be my fault, have to look into it more carefully.

### Todo's:

make the storage more abstract (flexible backend: REDIS, Memcache, MySQL).

For the original docs see: https://github.com/diogok/JobJoker
