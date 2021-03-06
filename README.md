# mybitcoinsaver

![Preview](https://lh4.googleusercontent.com/PD8a5-P4aUmDojA93HdCKiIQLwJkntyjvVtK7Ycsfo9VOhUsz0z3gN5vKQvhSDnyKtMMkbZVbAqaBok4lO4D=w3028-h1614-rw)

# setup

You'll need to install a local copy of mysql.

Then log in to the database `mysql -u root` and create the following database:

```
create database bitcoin_saver;
```

You'll also need to create a .env file (it is excluded by .gitignore):
```
cp .env.example .env
```


And then use this database configuration:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bitcoin_saver
DB_USERNAME=root
DB_PASSWORD=
```

Afterwards, you'll need to run the database migration:
```
php artisan migrate
```

I followed this guide to getting set up quickly:

https://laravel-news.com/your-first-laravel-application

It includes using Valet:

https://laravel.com/docs/5.4/valet

Which configures a webserver, etc. for you very easily.
