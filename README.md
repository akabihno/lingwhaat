# LingWhaat?

This project aims to detect the language of written text with high speed and accuracy.

IPA data for universal transliteration was parsed from [Wiktionary](https://en.wiktionary.org/wiki/Wiktionary:Main_Page) [dumps](https://dumps.wikimedia.org/enwiktionary/latest/) (specifically: enwiktionary-latest-pages-articles-multistream.xml.bz2)

## Requirements
* docker
* gunzip

## Start

```bash
cp env.dist .env #adjust the values for your needs

docker compose up -d

gunzip -k imports/import.sql.gz

docker exec -i database mysql -u root -ppassword -P 3327 -e "ALTER DATABASE lingwhaat CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;"
docker exec -i database mysql -u root -ppassword -P 3327 -e "ALTER TABLE lingwhaat.pronunciation CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"
docker exec -i database mysql --default-character-set=utf8mb4 --force -u root -ppassword -P 3327 lingwhaat < imports/import.sql
```

Database listens on port 3327 by default - configurable in my.cnf