

### Create .env file 

```
cp .env.example .env
php artisan key:generate
```


### Set Android res folder
Add this key to the end of the .env file

```
ANDROID_RES_PATH="/Users/marie/Documents/Projects/BAM/Clients/SmokeFree/smokefree-android/app/src/main/res"
```


### Start server
```
php artisan serve
```


### Run 

```
curl --location --request POST 'http://127.0.0.1:8000/api/sync' --form 'csv_file=@"/Users/marie/Downloads/Android Strings - Localisations.csv"'
```
