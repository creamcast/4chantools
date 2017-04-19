# 4chantools
tools to archive, analyze, rank threads and search 4chan

#usage 
php -S localhost:8081

then access localhost:8081/*.php from browser

# archiver.php
downloads an entire boards json files. 

# reps.php
ranks posts in a board by reply count. archiver.php has to be run once on the board at least once to get the json files.

# ikioi.php
ranks threads of a board by the speed of replies being posted.
