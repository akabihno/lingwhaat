# LingWhaat?

This project aims to detect the language of written text with high speed and accuracy.

Initial IPA data for universal transliteration was parsed from [Wiktionary](https://en.wiktionary.org/wiki/Wiktionary:Main_Page) [dumps](https://dumps.wikimedia.org/enwiktionary/latest/) (specifically: enwiktionary-latest-pages-articles-multistream.xml.bz2)\
List of all articles IPA data has been taken from:
* Latvian:
[а/ā](docs/Latvian/en_wiktionary_latvian_a.md),
[b](docs/Latvian/en_wiktionary_latvian_b.md),
[c/č](docs/Latvian/en_wiktionary_latvian_c.md),
[d](docs/Latvian/en_wiktionary_latvian_d.md),
[e/ē](docs/Latvian/en_wiktionary_latvian_e.md),
[f](docs/Latvian/en_wiktionary_latvian_f.md),
[g/ģ](docs/Latvian/en_wiktionary_latvian_g.md),
[h](docs/Latvian/en_wiktionary_latvian_h.md),
[i/ī](docs/Latvian/en_wiktionary_latvian_i.md),
[j](docs/Latvian/en_wiktionary_latvian_j.md),
[k/ķ](docs/Latvian/en_wiktionary_latvian_k.md),
[l/ļ](docs/Latvian/en_wiktionary_latvian_l.md),
[m](docs/Latvian/en_wiktionary_latvian_m.md),
[n/ņ](docs/Latvian/en_wiktionary_latvian_n.md),
[o](docs/Latvian/en_wiktionary_latvian_o.md),
[p](docs/Latvian/en_wiktionary_latvian_p.md),
[r](docs/Latvian/en_wiktionary_latvian_r.md),
[s/š](docs/Latvian/en_wiktionary_latvian_s.md),
[t](docs/Latvian/en_wiktionary_latvian_t.md),
[u/ū](docs/Latvian/en_wiktionary_latvian_u.md),
[v](docs/Latvian/en_wiktionary_latvian_v.md),
[z/ž](docs/Latvian/en_wiktionary_latvian_z.md)
* Polish:
[а/ą](docs/Polish/en_wiktionary_polish_a.md),
[b](docs/Polish/en_wiktionary_polish_b.md),
[c/ć](docs/Polish/en_wiktionary_polish_c.md),
[d](docs/Polish/en_wiktionary_polish_d.md),
[e/ę](docs/Polish/en_wiktionary_polish_e.md),
[f](docs/Polish/en_wiktionary_polish_f.md),
[g](docs/Polish/en_wiktionary_polish_g.md),
[h](docs/Polish/en_wiktionary_polish_h.md),
[i](docs/Polish/en_wiktionary_polish_i.md),
[j](docs/Polish/en_wiktionary_polish_j.md),
[k](docs/Polish/en_wiktionary_polish_k.md),
[l/ł](docs/Polish/en_wiktionary_polish_l.md),
[m](docs/Polish/en_wiktionary_polish_m.md),
[n](docs/Polish/en_wiktionary_polish_n.md),
[o/ó](docs/Polish/en_wiktionary_polish_o.md),
[p](docs/Polish/en_wiktionary_polish_p.md),
[q](docs/Polish/en_wiktionary_polish_q.md),
[r](docs/Polish/en_wiktionary_polish_r.md),
[s/ś](docs/Polish/en_wiktionary_polish_s.md),
[t](docs/Polish/en_wiktionary_polish_t.md),
[u](docs/Polish/en_wiktionary_polish_u.md),
[v](docs/Polish/en_wiktionary_polish_v.md),
[w](docs/Polish/en_wiktionary_polish_w.md),
[x](docs/Polish/en_wiktionary_polish_x.md),
[y](docs/Polish/en_wiktionary_polish_y.md),
[z/ź/ż](docs/Polish/en_wiktionary_polish_z.md)
* Portuguese:
[а](docs/Portuguese/en_wiktionary_portuguese_a.md),
[b](docs/Portuguese/en_wiktionary_portuguese_b.md),
[c](docs/Portuguese/en_wiktionary_portuguese_c.md),
[d](docs/Portuguese/en_wiktionary_portuguese_d.md),
[e](docs/Portuguese/en_wiktionary_portuguese_e.md),
[f](docs/Portuguese/en_wiktionary_portuguese_f.md),
[g](docs/Portuguese/en_wiktionary_portuguese_g.md),
[h](docs/Portuguese/en_wiktionary_portuguese_h.md),
[i](docs/Portuguese/en_wiktionary_portuguese_i.md),
[j](docs/Portuguese/en_wiktionary_portuguese_j.md),
[k](docs/Portuguese/en_wiktionary_portuguese_k.md),
[l](docs/Portuguese/en_wiktionary_portuguese_l.md),
[m](docs/Portuguese/en_wiktionary_portuguese_m.md),
[n](docs/Portuguese/en_wiktionary_portuguese_n.md),
[o](docs/Portuguese/en_wiktionary_portuguese_o.md),
[p](docs/Portuguese/en_wiktionary_portuguese_p.md),
[q](docs/Portuguese/en_wiktionary_portuguese_q.md),
[r](docs/Portuguese/en_wiktionary_portuguese_r.md),
[s](docs/Portuguese/en_wiktionary_portuguese_s.md),
[t](docs/Portuguese/en_wiktionary_portuguese_t.md),
[u](docs/Portuguese/en_wiktionary_portuguese_u.md),
[v](docs/Portuguese/en_wiktionary_portuguese_v.md),
[w](docs/Portuguese/en_wiktionary_portuguese_w.md),
[x](docs/Portuguese/en_wiktionary_portuguese_x.md),
[y](docs/Portuguese/en_wiktionary_portuguese_y.md),
[z](docs/Portuguese/en_wiktionary_portuguese_z.md),
* Russian:
[а](docs/Russian/en_wiktionary_russian_а.md), 
[б](docs/Russian/en_wiktionary_russian_б.md),
[в](docs/Russian/en_wiktionary_russian_в.md),
[г](docs/Russian/en_wiktionary_russian_г.md),
[д](docs/Russian/en_wiktionary_russian_д.md),
[е](docs/Russian/en_wiktionary_russian_е.md),
[ж](docs/Russian/en_wiktionary_russian_ж.md),
[з](docs/Russian/en_wiktionary_russian_з.md),
[и](docs/Russian/en_wiktionary_russian_и.md),
[й](docs/Russian/en_wiktionary_russian_й.md),
[к](docs/Russian/en_wiktionary_russian_к.md),
[л](docs/Russian/en_wiktionary_russian_л.md),
[м](docs/Russian/en_wiktionary_russian_м.md),
[н](docs/Russian/en_wiktionary_russian_н.md),
[о](docs/Russian/en_wiktionary_russian_о.md),
[п](docs/Russian/en_wiktionary_russian_п.md),
[р](docs/Russian/en_wiktionary_russian_р.md),
[с](docs/Russian/en_wiktionary_russian_с.md),
[т](docs/Russian/en_wiktionary_russian_т.md),
[у](docs/Russian/en_wiktionary_russian_у.md),
[ф](docs/Russian/en_wiktionary_russian_ф.md),
[х](docs/Russian/en_wiktionary_russian_х.md),
[ц](docs/Russian/en_wiktionary_russian_ц.md),
[ч](docs/Russian/en_wiktionary_russian_ч.md),
[ш](docs/Russian/en_wiktionary_russian_ш.md),
[щ](docs/Russian/en_wiktionary_russian_щ.md),
[ы](docs/Russian/en_wiktionary_russian_ы.md),
[э](docs/Russian/en_wiktionary_russian_э.md),
[ю](docs/Russian/en_wiktionary_russian_ю.md),
[я](docs/Russian/en_wiktionary_russian_я.md)
* Serbo-croatian:
[а](docs/SerboCroatian/en_wiktionary_serbocroatian_a.md),
[b](docs/SerboCroatian/en_wiktionary_serbocroatian_b.md),
[c](docs/SerboCroatian/en_wiktionary_serbocroatian_c.md),
[d](docs/SerboCroatian/en_wiktionary_serbocroatian_d.md),
[dž](docs/SerboCroatian/en_wiktionary_serbocroatian_dž.md),
[e](docs/SerboCroatian/en_wiktionary_serbocroatian_e.md),
[f](docs/SerboCroatian/en_wiktionary_serbocroatian_f.md),
[g](docs/SerboCroatian/en_wiktionary_serbocroatian_g.md),
[h](docs/SerboCroatian/en_wiktionary_serbocroatian_h.md),
[i](docs/SerboCroatian/en_wiktionary_serbocroatian_i.md),
[j](docs/SerboCroatian/en_wiktionary_serbocroatian_j.md),
[k](docs/SerboCroatian/en_wiktionary_serbocroatian_k.md),
[l](docs/SerboCroatian/en_wiktionary_serbocroatian_l.md),
[lj](docs/SerboCroatian/en_wiktionary_serbocroatian_lj.md),
[m](docs/SerboCroatian/en_wiktionary_serbocroatian_m.md),
[n](docs/SerboCroatian/en_wiktionary_serbocroatian_n.md),
[nj](docs/SerboCroatian/en_wiktionary_serbocroatian_nj.md),
[o](docs/SerboCroatian/en_wiktionary_serbocroatian_o.md),
[p](docs/SerboCroatian/en_wiktionary_serbocroatian_p.md),
[r](docs/SerboCroatian/en_wiktionary_serbocroatian_r.md),
[s](docs/SerboCroatian/en_wiktionary_serbocroatian_s.md),
[t](docs/SerboCroatian/en_wiktionary_serbocroatian_t.md),
[u](docs/SerboCroatian/en_wiktionary_serbocroatian_u.md),
[v](docs/SerboCroatian/en_wiktionary_serbocroatian_v.md),
[z](docs/SerboCroatian/en_wiktionary_serbocroatian_z.md),
[ć](docs/SerboCroatian/en_wiktionary_serbocroatian_ć.md),
[đ](docs/SerboCroatian/en_wiktionary_serbocroatian_đ.md),
[š](docs/SerboCroatian/en_wiktionary_serbocroatian_š.md),
[ž](docs/SerboCroatian/en_wiktionary_serbocroatian_ž.md)

## Requirements
* docker

## Start

```bash
cp env.dist .env #adjust the values for your needs
```
```bash
docker compose up -d
```
```bash
cd /opt/lingwhaat #or other location where you have the project
```
```bash
export $(grep -v '^#' .env | xargs)
```
```bash
docker exec -i database mysql --default-character-set=utf8mb4 --force -u root -p"${MYSQL_ROOT_PASSWORD}" -P "${MYSQL_PORT}" "${MYSQL_DATABASE}" < imports/import.sql
```
```bash
envsubst < imports/create_web_user.sql | docker exec -i database mysql --default-character-set=utf8mb4 --force -u root -p"${MYSQL_ROOT_PASSWORD}" -P "${MYSQL_PORT}" "${MYSQL_DATABASE}"
```

Note: database listens on port 3327 by default, if you wish to change it, make sure to adjust it in my.cnf