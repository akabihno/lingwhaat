# LingWhaat?

This project aims to detect the language of written text with high speed and accuracy.

Initial IPA data for universal transliteration was parsed from [Wiktionary](https://en.wiktionary.org/wiki/Wiktionary:Main_Page) [dumps](https://dumps.wikimedia.org/enwiktionary/latest/) (specifically: enwiktionary-latest-pages-articles-multistream.xml.bz2)\
List of all articles IPA data has been taken from:
* Russian:
[а](docs/en_wiktionary_russian_а.md), 
[б](docs/en_wiktionary_russian_б.md),
[в](docs/en_wiktionary_russian_в.md),
[г](docs/en_wiktionary_russian_г.md),
[д](docs/en_wiktionary_russian_д.md),
[е](docs/en_wiktionary_russian_е.md),
[ж](docs/en_wiktionary_russian_ж.md),
[з](docs/en_wiktionary_russian_з.md),
[и](docs/en_wiktionary_russian_и.md),
[й](docs/en_wiktionary_russian_й.md),
[к](docs/en_wiktionary_russian_к.md),
[л](docs/en_wiktionary_russian_л.md),
[м](docs/en_wiktionary_russian_м.md),
[н](docs/en_wiktionary_russian_н.md),
[о](docs/en_wiktionary_russian_о.md),
[п](docs/en_wiktionary_russian_п.md),
[р](docs/en_wiktionary_russian_р.md),
[с](docs/en_wiktionary_russian_с.md),
[т](docs/en_wiktionary_russian_т.md),
[у](docs/en_wiktionary_russian_у.md),
[ф](docs/en_wiktionary_russian_ф.md),
[х](docs/en_wiktionary_russian_х.md),
[ц](docs/en_wiktionary_russian_ц.md),
[ч](docs/en_wiktionary_russian_ч.md),
[ш](docs/en_wiktionary_russian_ш.md),
[щ](docs/en_wiktionary_russian_щ.md),
[ы](docs/en_wiktionary_russian_ы.md),
[э](docs/en_wiktionary_russian_э.md),
[ю](docs/en_wiktionary_russian_ю.md),
[я](docs/en_wiktionary_russian_я.md)

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