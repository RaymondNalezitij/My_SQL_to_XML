<h1> Project requirements </h1>

- PHP >= 7.4
- MySQL >= 5.7.39
- composer 2.3.3

Installation

1. Installing PHP on the system. Installation guides for all systems can be found here: https://www.php.net/manual/en/install.php

2. Downloading and installing composer. Command line: Download: php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" Local install: php composer-setup.php Global install: php composer-setup.php --install-dir=/usr/local/bin --filename=composer Test: composer Or use the installer from https://getcomposer.org/

3. To add a database to the project you will need to install MySql. Installation guide: https://ubuntu.com/server/docs/databases-mysql

4. Install the needed dependencies using: composer install

5. Run 'products.sql' to create the database. (it will auto generate a schema called products and add all the tables in to it)

6. In the mySQLtoXML main class change the private string values:
   - 'localhost'
   - 'user'
   - 'password'
   - 'products' 

   to the appropriate values for your database

7. run the program mySQLtoXML.php

<h1>Description</h1>

Program that converts mysql repositories in to an XML file that has collection of needed information.
