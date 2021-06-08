Instructions using GUI and Commands

    Download GitHub Desktop
    Clone Project https://github.com/it-dev-fumaco/athenaerp.git
    Locate Project Folder on your local PC
    Open CMD and change directory to your Local project folder
    Type copy .env.example .env
    Setup database connections in .env file
            - host = 10.0.48.84
            - database name = '3f2ec5a818bccb73'
            - username = erp
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
    
