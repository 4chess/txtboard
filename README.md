# txtboard
a fork of 4shout. one php file.  sets up the sqlite3 db for you. works on latest Php version. 
![screenshot](https://github.com/adelia4/txtboard/assets/128197007/fa2e7ccf-6e99-4523-b33f-6e98bb641889)

If you want to change anything just feed the code to chatgpt or similar and tell it what to change. 
jan 7 2024 did some serious work with this. Pushed the posting limits. You probably should not go over
100,000 chars but i tested this up to 600,000 chars. Major improvments to reliability. But sqlite3 
is not made to work that hard. I absolutely reccomend you never go over 100,000 chars but its nice to know it 
can be pushed. You set the char limit in the first lines of the file. 10,000 or 20,000 chars is reasonable for \
a board. its way better to set it lower, too. the lower it is, the faster it runs and more reliable it is. 
