# LingWhaat?

This project aims to detect the language of written text with high speed and accuracy.

IPA data for universal transliteration was parsed from [Wiktionary](https://en.wiktionary.org/wiki/Wiktionary:Main_Page) [dumps](https://dumps.wikimedia.org/enwiktionary/latest/) (specifically: enwiktionary-latest-pages-articles-multistream.xml.bz2)

## Start

```bash
cp env.dist .env #adjust the values for your needs

docker compose up -d

gunzip -k imports/import.sql.gz

docker exec -i database mysql -u root -p${MYSQL_ROOT_PASSWORD} -P 3327 lingwhaat < imports/import.sql
```

Database listens on port 3327 by default - configurable in my.cnf