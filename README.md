# LingWhaat?

This project aims to detect the language of written text with high speed and accuracy.

## Features

- Fast and accurate language detection
- Support for 38+ languages including modern and historical variants
- IPA-based transliteration system
- Data sourced from Wiktionary using MediaWiki APIs
- Docker-based deployment

## Supported Languages

|                 |              |                    |           |
|-----------------|--------------|--------------------|-----------|
| Afar            | Afrikaans    | Albanian           | Armenian  |
| Bengali         | Breton       | Czech              | Danish    |
| Dutch           | English      | Estonian           | French    |
| Georgian        | German       | Greek              | Hebrew    |
| Hindi           | Hungarian    | Icelandic          | Italian   |
| Kazakh          | Komi         | Latin              | Latvian   |
| Lithuanian      | Middle Dutch | Norwegian          | Old Dutch |
| Polish          | Portuguese   | Romanian           | Russian   |
| Serbo-Croatian* | Spanish      | Tagalog (Filipino) | Turkish   |
| Ukrainian       | Uzbek        | Swahili            |           |

*Serbo-Croatian includes: Bosnian, Croatian, Montenegrin, Serbian

## Data Sources

IPA data for universal transliteration is parsed from [Wiktionary](https://en.wiktionary.org/wiki/Wiktionary:Main_Page) using 
[MediaWiki API](https://www.mediawiki.org/wiki/API)

Note: for some languages used Wiktionary in it's respective language, e.g. https://nl.wiktionary.org/w/api.php for Dutch.

Implementations:
- [WiktionaryArticlesCategoriesService](src/Service/WiktionaryArticlesCategoriesService.php)
- [WiktionaryArticlesIpaParserService](src/Service/WiktionaryArticlesIpaParserService.php)

For a complete list of all source articles organized by language, see **[WORD_LISTS.md](WORD_LISTS.md)**.

## Requirements

- Docker

## Installation & Setup

1. **Copy and configure the environment file:**
   ```bash
   cp env.dist .env
   # Edit .env and adjust values for your needs
   ```

2. **Start Docker containers:**
   ```bash
   docker compose up -d
   ```

3. **Load environment variables:**
   ```bash
   export $(grep -v '^#' .env | xargs)
   ```

4. **Import database:**
   ```bash
   docker exec -i database mysql --default-character-set=utf8mb4 --force -u root -p"${MYSQL_ROOT_PASSWORD}" -P "${MYSQL_PORT}" "${MYSQL_DATABASE}" < imports/import.sql
   ```

5. **Create web user:**
   ```bash
   envsubst < imports/create_web_user.sql | docker exec -i database mysql --default-character-set=utf8mb4 --force -u root -p"${MYSQL_ROOT_PASSWORD}" -P "${MYSQL_PORT}" "${MYSQL_DATABASE}"
   ```

**Note:** The database listens on port 3327 by default. To change this, adjust the port in `my.cnf`.
