# Catalyst-IT-test


## Setup
1, Copy Env file

`
cp .env.example .env
`

2, Start Docker Container

```
docker compose up -d

docker exec -it php bash
```

2, Execute the script

``
php user_upload.php --file users.csv
``

