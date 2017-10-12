to run the unit tests:

-install composer if not already installed

-if no phpunit is installed:
  run: composer update in FindPath directory (for example c:\FindPath> composer update

-run phpunit pathTest.php



to  run the program directly  use :  php main.php when you are in FindPath directory.

Change  parameters in
 $path=new Path("A","E",8,$distances);
 line to check different scenarios.

 If successful path is found, we will have a dump of milestones used in traversng the path.
 and a SUCCESS message will show up.

 If path is not found, we will have a dump of the last tried milestones .
 and a FAILURE message will show up.