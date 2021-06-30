PHP version 7.3.0

Instructions using GUI and Commands

    Download GitHub Desktop
    Clone Project https://github.com/it-dev-fumaco/athenaerp.git
    Locate Project Folder on your local PC
    Open CMD and change directory to your Local project folder
    Type copy .env.example .env
    Setup database connections in .env file
         - ERP (for dev)
            - host = 10.0.48.85
            - database name = '3f2ec5a818bccb73'
            - username = erp
            - password = 'fumaco'
        - MES (for dev)
            - host 10.0.0.93
            - database name = 'mes-testing'
            - username = dev
            - password = 'fumaco'
    Type php artisan key:generate
    Type php artisan optimize
    Type php artisan serve
    Access it via URL using your IP or localhost with default port = 8000
    Open VsCode

Note: Please specify the Summary and Description on your every commit﻿ Finalize and Review Your Code before Pushing to Dev Branch

Instructions on how to add php runtime to windows path environment variable

    Find your PHP installation directory and copy it
    Go Control Panel > System and Security > System
    Click Advanced System Settings
    Click Environment Variables
    Select the "Path" variable from system variables list then click Edit
    Click New then paste your PHP installation directory path
    Click OK, then restart your XAMPP
    
Instructions to enable LDAP support for PHP

    Go xampp > htdocs > php folder
    Open php.ini
    Find "extension=ldap"
    Remove ";" to uncomment the line
    Save and restart apache
